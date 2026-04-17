/**
 * App Calendar
 */

/**
 * ! If both start and end dates are same Full calendar will nullify the end date value.
 * ! Full calendar will end the event on a day before at 12:00:00AM thus, event won't extend to the end date.
 * ! We are getting events from a separate file named app-calendar-events.js. You can add or remove events from there.
 *
 **/

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const direction = isRtl ? 'rtl' : 'ltr';
  (function () {
    // DOM Elements
    const calendarEl = document.getElementById('calendar');
    const appCalendarSidebar = document.querySelector('.app-calendar-sidebar');
    const calendarFilterRoot =
      document.getElementById('app-calendar-sidebar') ||
      appCalendarSidebar ||
      (calendarEl && calendarEl.closest && calendarEl.closest('.app-calendar-wrapper')) ||
      document.body;
    const addEventSidebar = document.getElementById('addEventSidebar');
    const viewEventSidebar = document.getElementById('viewEventSidebar');
    const appOverlay = document.querySelector('.app-overlay');
    const offcanvasTitle = document.querySelector('.offcanvas-title');
    const btnToggleSidebar = document.querySelector('.btn-toggle-sidebar');
    const btnSubmit = document.getElementById('addEventBtn');
    const btnDeleteEvent = document.querySelector('.btn-delete-event');
    const btnCancel = document.querySelector('.btn-cancel');
    const eventTitle = document.getElementById('eventTitle');
    const eventStartDate = document.getElementById('eventStartDate');
    const eventEndDate = document.getElementById('eventEndDate');
    const eventUrl = document.getElementById('eventURL');
    const eventLocation = document.getElementById('eventLocation');
    const eventDescription = document.getElementById('eventDescription');
    const allDaySwitch = document.querySelector('.allDay-switch');
    const selectAll = document.querySelector('.select-all');
    const inlineCalendar = document.querySelector('.inline-calendar');
    const viewEventTitle = document.getElementById('viewEventTitle');
    const viewEventType = document.getElementById('viewEventType');
    const viewEventDateTime = document.getElementById('viewEventDateTime');
    const viewEventNotes = document.getElementById('viewEventNotes');
    const viewEventStudentRow = document.getElementById('viewEventStudentRow');
    const viewEventStudent = document.getElementById('viewEventStudent');
    const viewEventEditBtn = document.getElementById('viewEventEditBtn');
    const isStudentReadonlyCalendar = !!document.querySelector('[data-student-events-readonly="1"]');

    // Calendar settings
    const calendarColors = {
      Business: 'primary',
      Holiday: 'success',
      Personal: 'danger',
      Family: 'warning',
      ETC: 'info'
    };

    // External jQuery Elements
    const eventLabel = $('#eventLabel'); // ! Using jQuery vars due to select2 jQuery dependency
    const eventGuests = $('#eventGuests'); // ! Using jQuery vars due to select2 jQuery dependency

    // Event Data (read window.events so teacher page can replace demo data before this script runs)
    let currentEvents = Array.isArray(window.events) ? window.events : [];
    let isFormValid = false;
    let eventToUpdate = null;
    let eventToView = null;
    let inlineCalInstance = null;
    let calendar = null;

    // Offcanvas Instance
    var bsAddEventSidebar = null;
    var bsViewEventSidebar = null;
    if (addEventSidebar && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
      bsAddEventSidebar = new bootstrap.Offcanvas(addEventSidebar);
    }
    if (viewEventSidebar && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
      bsViewEventSidebar = new bootstrap.Offcanvas(viewEventSidebar);
    }

    //! TODO: Update Event label and guest code to JS once select removes jQuery dependency
    // Initialize Select2 with custom templates (theme demo: data-label → bg-*; portal: data-color → hex dot)
    if (eventLabel.length) {
      function normalizeEventTypeDotColor(raw) {
        if (raw == null) {
          return null;
        }
        var s = String(raw).replace(/\s+/g, '').trim();
        if (!s) {
          return null;
        }
        if (/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/i.test(s)) {
          var h = s.replace(/^#/, '');
          if (h.length === 3) {
            return '#' + h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
          }
          return '#' + h.slice(0, 6).toLowerCase();
        }
        if (/^[a-z]{1,30}$/i.test(s)) {
          return s.toLowerCase();
        }
        return null;
      }

      function renderBadges(option) {
        if (!option.id) {
          return option.text;
        }
        var $el = $(option.element);
        var attrColor = $el.attr('data-color');
        var normalized = normalizeEventTypeDotColor(
          attrColor !== undefined && attrColor !== null && attrColor !== '' ? attrColor : $el.data('color')
        );
        var label = ($el.attr('data-label') || $el.data('label') || '').toString().trim();
        var dot = '';
        if (normalized) {
          dot =
            "<span class='rounded-circle d-inline-block flex-shrink-0 me-2 align-middle' style='width:0.5625rem;height:0.5625rem;background-color:" +
            normalized +
            ";'></span>";
        } else if (label) {
          dot = "<span class='badge badge-dot bg-" + label + " me-2'></span>";
        } else {
          dot =
            "<span class='rounded-circle d-inline-block flex-shrink-0 me-2 align-middle' style='width:0.5625rem;height:0.5625rem;background-color:#696cff;'></span>";
        }
        return dot + option.text;
      }

      eventLabel.wrap('<div class="position-relative"></div>');
      var $eventTypeDropdownParent = $('#addEventSidebar');
      if (!$eventTypeDropdownParent.length) {
        $eventTypeDropdownParent = eventLabel.parent();
      }
      eventLabel.select2({
        placeholder: eventLabel.data('selectPlaceholder') || 'Select value',
        dropdownParent: $eventTypeDropdownParent,
        templateResult: renderBadges,
        templateSelection: renderBadges,
        minimumResultsForSearch: -1,
        escapeMarkup: function (es) {
          return es;
        }
      });
    }

    // Render guest avatars
    if (eventGuests.length) {
      function renderGuestAvatar(option) {
        if (!option.id) return option.text;
        return `
    <div class='d-flex flex-wrap align-items-center'>
      ${option.text}
    </div>`;
      }
      eventGuests.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Select value',
        dropdownParent: eventGuests.parent(),
        closeOnSelect: false,
        templateResult: renderGuestAvatar,
        templateSelection: renderGuestAvatar,
        escapeMarkup: function (es) {
          return es;
        }
      });
    }

    // Event start (flatpicker)
    if (eventStartDate) {
      var start = eventStartDate.flatpickr({
        monthSelectorType: 'static',
        static: true,
        enableTime: true,
        altFormat: 'Y-m-dTH:i:S',
        onReady: function (selectedDates, dateStr, instance) {
          if (instance.isMobile) {
            instance.mobileInput.setAttribute('step', null);
          }
        }
      });
    }

    // Event end (flatpicker)
    if (eventEndDate) {
      var end = eventEndDate.flatpickr({
        monthSelectorType: 'static',
        static: true,
        enableTime: true,
        altFormat: 'Y-m-dTH:i:S',
        onReady: function (selectedDates, dateStr, instance) {
          if (instance.isMobile) {
            instance.mobileInput.setAttribute('step', null);
          }
        }
      });
    }

    function syncMainCalendarToDate(rawDate) {
      if (!calendar || typeof calendar.gotoDate !== 'function' || !rawDate) {
        return;
      }
      const d = rawDate instanceof Date ? rawDate : new Date(rawDate);
      if (isNaN(d.getTime())) {
        return;
      }
      calendar.gotoDate(d);
      modifyToggler();
      if (appCalendarSidebar) {
        appCalendarSidebar.classList.remove('show');
      }
      if (appOverlay) {
        appOverlay.classList.remove('show');
      }
    }

    // Inline sidebar calendar (flatpicker)
    if (inlineCalendar) {
      inlineCalInstance = inlineCalendar.flatpickr({
        monthSelectorType: 'static',
        static: true,
        inline: true,
        onChange: function (selectedDates) {
          syncMainCalendarToDate(selectedDates && selectedDates[0] ? selectedDates[0] : null);
        },
        onMonthChange: function (selectedDates, dateStr, instance) {
          syncMainCalendarToDate(instance && instance.currentYear != null && instance.currentMonth != null ? new Date(instance.currentYear, instance.currentMonth, 1) : null);
        },
        onYearChange: function (selectedDates, dateStr, instance) {
          syncMainCalendarToDate(instance && instance.currentYear != null && instance.currentMonth != null ? new Date(instance.currentYear, instance.currentMonth, 1) : null);
        }
      });
    }

    // Teacher portal: map FullCalendar event into #eventForm (schedule_event_date, event_id, select2)
    function fillPortalTeacherEventForm(fcEvent) {
      const serverEventId = document.getElementById('serverEventId');
      if (!serverEventId) {
        return;
      }
      const ext = fcEvent.extendedProps || {};
      const rawId = fcEvent.id != null ? String(fcEvent.id) : '';
      const portalSaveBtn = document.getElementById('saveEventToServerBtn');
      if (rawId.indexOf('db-') === 0) {
        serverEventId.value = rawId.slice(3);
        if (portalSaveBtn) {
          portalSaveBtn.textContent =
            portalSaveBtn.getAttribute('data-text-edit') || portalSaveBtn.textContent;
        }
      } else {
        serverEventId.value = '';
        if (portalSaveBtn) {
          portalSaveBtn.textContent =
            portalSaveBtn.getAttribute('data-text-new') || portalSaveBtn.textContent;
        }
      }
      const fcStartDt = fcEvent.start;
      const scheduleDate = document.getElementById('schedule_event_date');
      const eventTime = document.getElementById('eventTime');
      if (scheduleDate && fcStartDt) {
        const d = fcStartDt instanceof Date ? fcStartDt : new Date(fcStartDt);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        scheduleDate.value = y + '-' + m + '-' + day;
      }
      if (eventTime && fcStartDt && !fcEvent.allDay) {
        const d = fcStartDt instanceof Date ? fcStartDt : new Date(fcStartDt);
        const h = String(d.getHours()).padStart(2, '0');
        const min = String(d.getMinutes()).padStart(2, '0');
        eventTime.value = h + ':' + min;
      }
      const $jq = window.jQuery;
      const allClassroomsToggle = document.getElementById('allClassroomsSwitch');
      const allBatchesToggle = document.getElementById('allBatchesSwitch');
      const allClassrooms =
        ext.all_classrooms === true || ext.all_classrooms === 1 || ext.all_classrooms === '1';
      const allBatches = ext.all_batches === true || ext.all_batches === 1 || ext.all_batches === '1';
      if (allClassroomsToggle) {
        allClassroomsToggle.checked = !!allClassrooms;
      }
      if (allBatchesToggle) {
        allBatchesToggle.checked = !!allBatches;
      }
      if ($jq && $jq('#eventTypeSelect').length) {
        $jq('#eventTypeSelect')
          .val(ext.event_type_id != null ? String(ext.event_type_id) : '')
          .trigger('change');
      }
      if ($jq && $jq('#eventLabel').length) {
        $jq('#eventLabel')
          .val(ext.event_type_id != null ? String(ext.event_type_id) : '')
          .trigger('change');
      }
      if ($jq && $jq('#eventClassroom').length) {
        const clsVal =
          allClassrooms || ext.classroom_id == null || ext.classroom_id === ''
            ? ''
            : String(ext.classroom_id);
        $jq('#eventClassroom').val(clsVal).trigger('change');
      }
      function portalNormalizeIdArray(raw) {
        if (raw == null) {
          return [];
        }
        if (Array.isArray(raw)) {
          return raw
            .map(function (id) {
              return String(id);
            })
            .filter(function (s) {
              return s !== '';
            });
        }
        return [String(raw)];
      }
      var isTeacherMultiClassrooms =
        $jq && $jq('#eventGuests').length && $jq('#eventGuests').attr('name') === 'classrooms[]';
      if (isTeacherMultiClassrooms) {
        var clsArr = portalNormalizeIdArray(ext.classrooms);
        $jq('#eventGuests').val(clsArr.length ? clsArr : null).trigger('change');
        if ($jq('#eventBatches').length) {
          var batchArr = portalNormalizeIdArray(ext.batches);
          window.requestAnimationFrame(function () {
            $jq('#eventBatches').val(batchArr.length ? batchArr : null).trigger('change');
            document.dispatchEvent(new CustomEvent('teacherCalendarPrefill'));
          });
        } else {
          document.dispatchEvent(new CustomEvent('teacherCalendarPrefill'));
        }
      } else if ($jq && $jq('#eventBatches').length) {
        const batchVal =
          allBatches || ext.batch_id == null || ext.batch_id === '' ? '' : String(ext.batch_id);
        window.requestAnimationFrame(function () {
          $jq('#eventBatches').val(batchVal).trigger('change');
          document.dispatchEvent(new CustomEvent('teacherCalendarPrefill'));
        });
      } else {
        document.dispatchEvent(new CustomEvent('teacherCalendarPrefill'));
      }
      if ($jq && $jq('#eventStatus').length) {
        $jq('#eventStatus').val(String(ext.status != null ? ext.status : 0)).trigger('change');
      }
      if (eventDescription) {
        eventDescription.value = ext.description != null ? String(ext.description) : '';
      }
    }

    // Event click function
    function eventClick(info) {
      eventToUpdate = info.event;
      if (eventToUpdate.url) {
        info.jsEvent.preventDefault();
        window.open(eventToUpdate.url, '_blank');
      }
      if (bsAddEventSidebar) {
        bsAddEventSidebar.show();
      }
      // For update event set offcanvas title text: Update Event
      if (offcanvasTitle) {
        offcanvasTitle.innerHTML = 'Update Event';
      }
      if (btnSubmit) {
        btnSubmit.innerHTML = 'Update';
        btnSubmit.classList.add('btn-update-event');
        btnSubmit.classList.remove('btn-add-event');
      }
      if (btnDeleteEvent) {
        btnDeleteEvent.classList.remove('d-none');
      }

      if (eventTitle) {
        eventTitle.value = eventToUpdate.title;
      }
      if (typeof start !== 'undefined' && start) {
        start.setDate(eventToUpdate.start, true, 'Y-m-d');
      }
      if (allDaySwitch) {
        eventToUpdate.allDay === true ? (allDaySwitch.checked = true) : (allDaySwitch.checked = false);
      }
      if (typeof end !== 'undefined' && end) {
        eventToUpdate.end !== null
          ? end.setDate(eventToUpdate.end, true, 'Y-m-d')
          : end.setDate(eventToUpdate.start, true, 'Y-m-d');
      }
      if (eventLabel.length && eventToUpdate.extendedProps) {
        if (eventToUpdate.extendedProps.event_type_id != null && eventToUpdate.extendedProps.event_type_id !== '') {
          eventLabel.val(String(eventToUpdate.extendedProps.event_type_id)).trigger('change');
        } else if (eventToUpdate.extendedProps.calendar) {
          eventLabel.val(eventToUpdate.extendedProps.calendar).trigger('change');
        }
      }
      if (eventLocation && eventToUpdate.extendedProps && eventToUpdate.extendedProps.location !== undefined) {
        eventLocation.value = eventToUpdate.extendedProps.location;
      }
      if (eventGuests.length && eventToUpdate.extendedProps) {
        if (eventToUpdate.extendedProps.classrooms !== undefined) {
          var pc = eventToUpdate.extendedProps.classrooms;
          var pcArr = Array.isArray(pc) ? pc.map(function (id) { return String(id); }) : [String(pc)];
          eventGuests.val(pcArr.length ? pcArr : null).trigger('change');
        } else if (eventToUpdate.extendedProps.guests !== undefined) {
          eventGuests.val(eventToUpdate.extendedProps.guests).trigger('change');
        }
      }
      if (eventDescription && eventToUpdate.extendedProps && eventToUpdate.extendedProps.description !== undefined) {
        eventDescription.value = eventToUpdate.extendedProps.description;
      }
      fillPortalTeacherEventForm(info.event);
    }

    function eventOccursOnDate(eventObj, targetDate) {
      if (!eventObj || !eventObj.start || !targetDate) {
        return false;
      }
      const dayStart = new Date(targetDate.getFullYear(), targetDate.getMonth(), targetDate.getDate());
      const dayEnd = new Date(dayStart);
      dayEnd.setDate(dayEnd.getDate() + 1);
      const eventStart = new Date(eventObj.start);
      if (isNaN(eventStart.getTime())) {
        return false;
      }
      let eventEnd = eventObj.end ? new Date(eventObj.end) : null;
      if (!eventEnd || isNaN(eventEnd.getTime()) || eventEnd <= eventStart) {
        eventEnd = new Date(eventStart);
        eventEnd.setDate(eventEnd.getDate() + 1);
      }
      return eventStart < dayEnd && eventEnd > dayStart;
    }

    function eventTypeNameFromEvent(eventObj) {
      const ext = eventObj && eventObj.extendedProps ? eventObj.extendedProps : {};
      if (ext.event_type_title != null && String(ext.event_type_title).trim() !== '') {
        return String(ext.event_type_title).trim();
      }
      const typeId = ext.event_type_id != null ? String(ext.event_type_id) : '';
      if (!typeId) {
        return '-';
      }
      const opt = document.querySelector('#eventLabel option[value="' + typeId + '"]');
      return opt && opt.textContent ? opt.textContent.trim() : '-';
    }

    function formatEventDateText(eventObj, fallbackDate) {
      if (eventObj && eventObj.start) {
        const s = new Date(eventObj.start);
        const e = eventObj.end ? new Date(eventObj.end) : null;
        if (!isNaN(s.getTime())) {
          const datePart = s.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
          });
          if (eventObj.allDay) {
            return datePart + ' (All day)';
          }
          const opts = { hour: 'numeric', minute: '2-digit' };
          const startTime = s.toLocaleTimeString(undefined, opts);
          if (e && !isNaN(e.getTime())) {
            return datePart + ' • ' + startTime + ' - ' + e.toLocaleTimeString(undefined, opts);
          }
          return datePart + ' • ' + startTime;
        }
      }
      if (fallbackDate) {
        return fallbackDate.toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      }
      return '-';
    }

    function showEventDetailsOffcanvas(eventObj, fallbackDate) {
      eventToView = eventObj || null;
      const ext = eventObj && eventObj.extendedProps ? eventObj.extendedProps : {};
      if (viewEventTitle) {
        viewEventTitle.textContent = eventObj && eventObj.title ? eventObj.title : 'No event found';
      }
      if (viewEventType) {
        viewEventType.textContent = eventObj ? eventTypeNameFromEvent(eventObj) : '-';
      }
      if (viewEventDateTime) {
        viewEventDateTime.textContent = formatEventDateText(eventObj, fallbackDate);
      }
      if (viewEventStudentRow && viewEventStudent) {
        const sn = ext.portal_student_name;
        if (sn != null && String(sn).trim() !== '') {
          viewEventStudent.textContent = String(sn).trim();
          viewEventStudentRow.classList.remove('d-none');
        } else {
          viewEventStudent.textContent = '-';
          viewEventStudentRow.classList.add('d-none');
        }
      }
      if (viewEventNotes) {
        viewEventNotes.textContent =
          eventObj && ext.description != null && String(ext.description).trim() !== ''
            ? String(ext.description)
            : 'No notes';
      }
      if (viewEventEditBtn) {
        viewEventEditBtn.disabled = !eventObj;
      }
      if (bsViewEventSidebar) {
        bsViewEventSidebar.show();
      }
    }

    // Modify sidebar toggler
    function modifyToggler() {
      const fcSidebarToggleButton = document.querySelector('.fc-sidebarToggle-button');
      if (!fcSidebarToggleButton) {
        return;
      }
      fcSidebarToggleButton.classList.remove('fc-button-primary');
      fcSidebarToggleButton.classList.add('d-lg-none', 'd-inline-block', 'ps-0');
      while (fcSidebarToggleButton.firstChild) {
        fcSidebarToggleButton.firstChild.remove();
      }
      fcSidebarToggleButton.setAttribute('data-bs-toggle', 'sidebar');
      fcSidebarToggleButton.setAttribute('data-overlay', '');
      fcSidebarToggleButton.setAttribute('data-target', '#app-calendar-sidebar');
      fcSidebarToggleButton.insertAdjacentHTML(
        'beforeend',
        '<i class="icon-base ti tabler-menu-2 icon-lg text-heading"></i>'
      );
    }

    // Filter events by calender
    function selectedCalendars() {
      const selected = [];
      const root = calendarFilterRoot || document.body;
      const filterInputChecked = [].slice.call(root.querySelectorAll('.input-filter:checked'));

      filterInputChecked.forEach(item => {
        const v = item.getAttribute('data-value');
        if (v) {
          selected.push(String(v).toLowerCase().trim());
        }
      });

      return selected;
    }

    function refetchCalendarSafe() {
      if (calendar && typeof calendar.refetchEvents === 'function') {
        calendar.refetchEvents();
      }
    }

    // --------------------------------------------------------------------------------------------------
    // AXIOS: fetchEvents
    // * This will be called by fullCalendar to fetch events. Also this can be used to refetch events.
    // --------------------------------------------------------------------------------------------------
    function fetchEvents(info, successCallback) {
      if (Array.isArray(window.events)) {
        currentEvents = window.events;
      }
      const sourceList = Array.isArray(currentEvents) ? currentEvents : [];
      const calendars = selectedCalendars();
      const root = calendarFilterRoot || document.body;
      const totalTypeFilters = root.querySelectorAll('.input-filter').length;
      // We are reading event object from app-calendar-events.js file directly by including that file above app-calendar file.
      // You should make an API call, look into above commented API call for reference
      let selectedEvents = sourceList.filter(function (event) {
        const ext = event.extendedProps || {};
        var rawCal = ext.calendar;
        if (
          (rawCal === undefined || rawCal === null || rawCal === '') &&
          ext.event_type_id != null &&
          ext.event_type_id !== ''
        ) {
          rawCal = 'et' + String(ext.event_type_id);
        }
        const cal =
          rawCal !== undefined && rawCal !== null && rawCal !== ''
            ? String(rawCal).toLowerCase().trim()
            : '';
        if (totalTypeFilters === 0) {
          return true;
        }
        if (!calendars.length) {
          return false;
        }
        return cal !== '' && calendars.indexOf(cal) !== -1;
      });
      // if (selectedEvents.length > 0) {
      successCallback(selectedEvents);
      // }
    }

    if (!calendarEl) {
      return;
    }

    // Init FullCalendar
    // ------------------------------------------------
    calendar = new Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: fetchEvents,
      plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
      displayEventTime: true,
      eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short'
      },
      editable: true,
      dragScroll: true,
      dayMaxEvents: 2,
      eventResizableFromStart: true,
      customButtons: {
        sidebarToggle: {
          text: 'Sidebar'
        }
      },
      headerToolbar: {
        start: 'sidebarToggle, prev,next, title',
        end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
      },
      direction: direction,
      initialDate: new Date(),
      navLinks: true, // can click day/week names to navigate views
      eventClassNames: function (arg) {
        const calendarEvent = arg.event;
        const ext = calendarEvent.extendedProps || {};
        if (calendarEvent.backgroundColor || ext.color_code) {
          return ['fc-event-db-type-color'];
        }
        const label =
          calendarEvent._def.extendedProps && calendarEvent._def.extendedProps.calendar
            ? calendarEvent._def.extendedProps.calendar
            : 'Business';
        const colorName = calendarColors[label] || 'primary';
        return ['bg-label-' + colorName];
      },
      eventDidMount: function (info) {
        const ev = info.event;
        const ext = ev.extendedProps || {};
        let bg = ev.backgroundColor || '';
        let border = ev.borderColor || bg;
        let fg = ev.textColor || '';
        if (!bg && ext.color_code) {
          let c = String(ext.color_code).trim().replace(/\s+/g, '');
          if (c && !c.startsWith('#') && /^[0-9a-fA-F]{3,8}$/i.test(c)) {
            c = '#' + c;
          }
          if (c) {
            bg = border = c;
          }
        }
        if (bg && info.el) {
          info.el.style.setProperty('background-color', bg, 'important');
          info.el.style.setProperty('border-color', border || bg, 'important');
          if (fg) {
            info.el.style.setProperty('color', fg, 'important');
          }
        }
      },
      // Month / day-grid week: title on first line, time underneath
      eventContent: function (arg) {
        if (!arg.view.type.startsWith('dayGrid')) {
          return;
        }
        const wrap = document.createElement('div');
        wrap.className = 'fc-daygrid-event-stack text-start w-100';
        if (arg.event.textColor) {
          wrap.style.color = arg.event.textColor;
        }
        const titleEl = document.createElement('div');
        titleEl.className = 'fc-event-title text-truncate';
        titleEl.textContent = arg.event.title || '';
        wrap.appendChild(titleEl);
        if (!arg.event.allDay && arg.timeText) {
          const timeEl = document.createElement('div');
          timeEl.className = 'fc-event-time fc-daygrid-event-time-below';
          timeEl.textContent = arg.timeText;
          wrap.appendChild(timeEl);
        }
        return { domNodes: [wrap] };
      },
      dateClick: function (info) {
        if (isStudentReadonlyCalendar) {
          const clickedDate = info && info.date ? new Date(info.date) : null;
          let eventForDate = null;
          if (calendar && clickedDate) {
            const eventsOnDate = calendar.getEvents().filter(function (ev) {
              return eventOccursOnDate(ev, clickedDate);
            });
            if (eventsOnDate.length) {
              eventForDate = eventsOnDate[0];
            }
          }
          showEventDetailsOffcanvas(eventForDate, clickedDate);
          return;
        }
        let date = moment(info.date).format('YYYY-MM-DD');
        resetValues();
        if (bsAddEventSidebar) {
        bsAddEventSidebar.show();
      }

        // For new event set offcanvas title text: Add Event
        if (offcanvasTitle) {
          offcanvasTitle.innerHTML = 'Add Event';
        }
        if (btnSubmit) {
          btnSubmit.innerHTML = 'Add';
          btnSubmit.classList.remove('btn-update-event');
          btnSubmit.classList.add('btn-add-event');
        }
        if (btnDeleteEvent) {
          btnDeleteEvent.classList.add('d-none');
        }
        if (eventStartDate) {
          eventStartDate.value = date;
        }
        if (eventEndDate) {
          eventEndDate.value = date;
        }
        const scheduleEl = document.getElementById('schedule_event_date');
        if (scheduleEl) {
          scheduleEl.value = date;
        }
        const portalSaveNew = document.getElementById('saveEventToServerBtn');
        if (portalSaveNew && portalSaveNew.getAttribute('data-text-new')) {
          portalSaveNew.textContent = portalSaveNew.getAttribute('data-text-new');
        }
      },
      eventClick: function (info) {
        if (isStudentReadonlyCalendar) {
          if (info && info.jsEvent && typeof info.jsEvent.preventDefault === 'function') {
            info.jsEvent.preventDefault();
          }
          showEventDetailsOffcanvas(info.event, info && info.event ? info.event.start : null);
          return;
        }
        eventClick(info);
      },
      datesSet: function () {
        modifyToggler();
      },
      viewDidMount: function () {
        modifyToggler();
      }
    });

    // Render calendar
    calendar.render();
    // Modify sidebar toggler
    modifyToggler();

    const eventForm = document.getElementById('eventForm');
    if (
      eventForm &&
      typeof FormValidation !== 'undefined' &&
      FormValidation.formValidation &&
      FormValidation.plugins
    ) {
      const fvFields = {
        eventTitle: {
          validators: {
            notEmpty: {
              message: 'Please enter event title '
            }
          }
        }
      };
      if (eventStartDate) {
        fvFields.eventStartDate = {
          validators: {
            notEmpty: {
              message: 'Please enter start date '
            }
          }
        };
      }
      if (eventEndDate) {
        fvFields.eventEndDate = {
          validators: {
            notEmpty: {
              message: 'Please enter end date '
            }
          }
        };
      }
      FormValidation.formValidation(eventForm, {
        fields: fvFields,
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({
            // Use this for enabling/changing valid/invalid class
            eleValidClass: '',
            rowSelector: function (field, ele) {
              // field is the field name & ele is the field element
              return '.form-control-validation';
            }
          }),
          submitButton: new FormValidation.plugins.SubmitButton(),
          // Submit the form when all fields are valid
          // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
          autoFocus: new FormValidation.plugins.AutoFocus()
        }
      })
        .on('core.form.valid', function () {
          // Jump to the next step when all fields in the current step are valid
          isFormValid = true;
        })
        .on('core.form.invalid', function () {
          // if fields are invalid
          isFormValid = false;
        });
    }

    // Sidebar Toggle Btn
    if (btnToggleSidebar && btnCancel) {
      btnToggleSidebar.addEventListener('click', e => {
        btnCancel.classList.remove('d-none');
      });
    }

    // Add Event
    // ------------------------------------------------
    function addEvent(eventData) {
      // ? Add new event data to current events object and refetch it to display on calender
      // ? You can write below code to AJAX call success response

      currentEvents.push(eventData);
      calendar.refetchEvents();

      // ? To add event directly to calender (won't update currentEvents object)
      // calendar.addEvent(eventData);
    }

    // Update Event
    // ------------------------------------------------
    function updateEvent(eventData) {
      // ? Update existing event data to current events object and refetch it to display on calender
      // ? You can write below code to AJAX call success response
      eventData.id = parseInt(eventData.id);
      currentEvents[currentEvents.findIndex(el => el.id === eventData.id)] = eventData; // Update event by id
      calendar.refetchEvents();

      // ? To update event directly to calender (won't update currentEvents object)
      // let propsToUpdate = ['id', 'title', 'url'];
      // let extendedPropsToUpdate = ['calendar', 'guests', 'location', 'description'];

      // updateEventInCalendar(eventData, propsToUpdate, extendedPropsToUpdate);
    }

    // Remove Event
    // ------------------------------------------------

    function removeEvent(eventId) {
      // ? Delete existing event data to current events object and refetch it to display on calender
      // ? You can write below code to AJAX call success response
      currentEvents = currentEvents.filter(function (event) {
        return event.id != eventId;
      });
      calendar.refetchEvents();

      // ? To delete event directly to calender (won't update currentEvents object)
      // removeEventInCalendar(eventId);
    }

    // (Update Event In Calendar (UI Only)
    // ------------------------------------------------
    const updateEventInCalendar = (updatedEventData, propsToUpdate, extendedPropsToUpdate) => {
      const existingEvent = calendar.getEventById(updatedEventData.id);

      // --- Set event properties except date related ----- //
      // ? Docs: https://fullcalendar.io/docs/Event-setProp
      // dateRelatedProps => ['start', 'end', 'allDay']
      // eslint-disable-next-line no-plusplus
      for (var index = 0; index < propsToUpdate.length; index++) {
        var propName = propsToUpdate[index];
        existingEvent.setProp(propName, updatedEventData[propName]);
      }

      // --- Set date related props ----- //
      // ? Docs: https://fullcalendar.io/docs/Event-setDates
      existingEvent.setDates(updatedEventData.start, updatedEventData.end, {
        allDay: updatedEventData.allDay
      });

      // --- Set event's extendedProps ----- //
      // ? Docs: https://fullcalendar.io/docs/Event-setExtendedProp
      // eslint-disable-next-line no-plusplus
      for (var index = 0; index < extendedPropsToUpdate.length; index++) {
        var propName = extendedPropsToUpdate[index];
        existingEvent.setExtendedProp(propName, updatedEventData.extendedProps[propName]);
      }
    };

    // Remove Event In Calendar (UI Only)
    // ------------------------------------------------
    function removeEventInCalendar(eventId) {
      calendar.getEventById(eventId).remove();
    }

    // Add new event
    // ------------------------------------------------
    if (btnSubmit) {
      btnSubmit.addEventListener('click', e => {
        if (btnSubmit.classList.contains('btn-add-event')) {
          if (isFormValid) {
            let newEvent = {
              id: calendar.getEvents().length + 1,
              title: eventTitle.value,
              start: eventStartDate.value,
              end: eventEndDate.value,
              startStr: eventStartDate.value,
              endStr: eventEndDate.value,
              display: 'block',
              extendedProps: {
                location: eventLocation.value,
                guests: eventGuests.val(),
                calendar: eventLabel.val(),
                description: eventDescription.value
              }
            };
            if (eventUrl.value) {
              newEvent.url = eventUrl.value;
            }
            if (allDaySwitch && allDaySwitch.checked) {
              newEvent.allDay = true;
            }
            addEvent(newEvent);
            bsAddEventSidebar.hide();
          }
        } else {
          // Update event
          // ------------------------------------------------
          if (isFormValid) {
            let eventData = {
              id: eventToUpdate.id,
              title: eventTitle.value,
              start: eventStartDate.value,
              end: eventEndDate.value,
              url: eventUrl.value,
              extendedProps: {
                location: eventLocation.value,
                guests: eventGuests.val(),
                calendar: eventLabel.val(),
                description: eventDescription.value
              },
              display: 'block',
              allDay: allDaySwitch && allDaySwitch.checked ? true : false
            };

            updateEvent(eventData);
            if (bsAddEventSidebar) {
              bsAddEventSidebar.hide();
            }
          }
        }
      });
    }

    // Call removeEvent function
    if (btnDeleteEvent) {
      btnDeleteEvent.addEventListener('click', e => {
        removeEvent(parseInt(eventToUpdate.id));
        // eventToUpdate.remove();
        if (bsAddEventSidebar) {
          bsAddEventSidebar.hide();
        }
      });
    }

    // Reset event form inputs values
    // ------------------------------------------------
    function resetValues() {
      if (typeof start !== 'undefined' && start) {
        start.clear();
      }
      if (typeof end !== 'undefined' && end) {
        end.clear();
      }
      if (eventEndDate) {
        eventEndDate.value = '';
      }
      if (eventUrl) {
        eventUrl.value = '';
      }
      if (eventStartDate) {
        eventStartDate.value = '';
      }
      if (eventTitle) {
        eventTitle.value = '';
      }
      if (eventLocation) {
        eventLocation.value = '';
      }
      if (allDaySwitch) {
        allDaySwitch.checked = false;
      }
      const allClassroomsToggle = document.getElementById('allClassroomsSwitch');
      const allBatchesToggle = document.getElementById('allBatchesSwitch');
      if (allClassroomsToggle) {
        allClassroomsToggle.checked = false;
      }
      if (allBatchesToggle) {
        allBatchesToggle.checked = false;
      }
      const portalJq = window.jQuery;
      if (portalJq && portalJq('#eventForm').length) {
        if (portalJq('#eventClassroom').length) {
          portalJq('#eventClassroom').val('').trigger('change');
        }
        if (portalJq('#eventBatches').length) {
          portalJq('#eventBatches').val(null).trigger('change');
        }
        if (portalJq('#eventLabel').length) {
          portalJq('#eventLabel').val(null).trigger('change');
        }
        if (portalJq('#eventTypeSelect').length) {
          portalJq('#eventTypeSelect').val('').trigger('change');
        }
      }
      if (eventGuests.length) {
        eventGuests.val('').trigger('change');
      }
      if (eventDescription) {
        eventDescription.value = '';
      }
      const serverEventId = document.getElementById('serverEventId');
      if (serverEventId) {
        serverEventId.value = '';
      }
      const portalSaveBtn = document.getElementById('saveEventToServerBtn');
      if (portalSaveBtn && portalSaveBtn.getAttribute('data-text-new')) {
        portalSaveBtn.textContent = portalSaveBtn.getAttribute('data-text-new');
      }
    }

    // When modal hides reset input values
    if (addEventSidebar) {
      addEventSidebar.addEventListener('hidden.bs.offcanvas', function () {
        resetValues();
      });
    }

    // Hide left sidebar if the right sidebar is open
    if (btnToggleSidebar) {
      btnToggleSidebar.addEventListener('click', e => {
        if (offcanvasTitle) {
          offcanvasTitle.innerHTML = 'Add Event';
        }
        if (btnSubmit) {
          btnSubmit.innerHTML = 'Add';
          btnSubmit.classList.remove('btn-update-event');
          btnSubmit.classList.add('btn-add-event');
        }
        if (btnDeleteEvent) {
          btnDeleteEvent.classList.add('d-none');
        }
        if (appCalendarSidebar) {
          appCalendarSidebar.classList.remove('show');
        }
        if (appOverlay) {
          appOverlay.classList.remove('show');
        }
      });
    }

    // Calender filter functionality
    // ------------------------------------------------
    const filterRoot = calendarFilterRoot || document.body;
    filterRoot.addEventListener('change', function (e) {
      const t = e.target;
      if (!(t instanceof HTMLInputElement)) {
        return;
      }
      if (t.classList.contains('select-all')) {
        filterRoot.querySelectorAll('.input-filter').forEach(function (c) {
          c.checked = t.checked;
        });
      } else if (t.classList.contains('input-filter')) {
        const checked = filterRoot.querySelectorAll('.input-filter:checked').length;
        const total = filterRoot.querySelectorAll('.input-filter').length;
        const sa = filterRoot.querySelector('.select-all') || selectAll;
        if (sa) {
          sa.checked = total > 0 && checked === total;
        }
      } else {
        return;
      }
      refetchCalendarSafe();
    });

    // Inline calendar navigation handlers are bound during flatpickr init.
  })();
});

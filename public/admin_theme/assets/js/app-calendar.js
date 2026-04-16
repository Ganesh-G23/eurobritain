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
    const calendarFilterRoot = appCalendarSidebar || document.getElementById('app-calendar-sidebar') || document;
    const addEventSidebar = document.getElementById('addEventSidebar');
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
    const filterInputs = Array.from(calendarFilterRoot.querySelectorAll('.input-filter'));
    const inlineCalendar = document.querySelector('.inline-calendar');

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
    let inlineCalInstance = null;

    // Offcanvas Instance
    const bsAddEventSidebar = new bootstrap.Offcanvas(addEventSidebar);

    //! TODO: Update Event label and guest code to JS once select removes jQuery dependency
    // Initialize Select2 with custom templates
    if (eventLabel.length) {
      function renderBadges(option) {
        if (!option.id) {
          return option.text;
        }
        var $badge =
          "<span class='badge badge-dot bg-" + $(option.element).data('label') + " me-2'> " + '</span>' + option.text;

        return $badge;
      }
      eventLabel.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Select value',
        dropdownParent: eventLabel.parent(),
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
      <div class='avatar avatar-xs me-2'>
        <img src='${assetsPath}img/avatars/${$(option.element).data('avatar')}'
          alt='avatar' class='rounded-circle' />
      </div>
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

    // Inline sidebar calendar (flatpicker)
    if (inlineCalendar) {
      inlineCalInstance = inlineCalendar.flatpickr({
        monthSelectorType: 'static',
        static: true,
        inline: true
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
      const scheduleDate = document.getElementById('schedule_event_date');
      const eventTime = document.getElementById('eventTime');
      const start = fcEvent.start;
      if (scheduleDate && start) {
        const y = start.getFullYear();
        const m = String(start.getMonth() + 1).padStart(2, '0');
        const d = String(start.getDate()).padStart(2, '0');
        scheduleDate.value = y + '-' + m + '-' + d;
      }
      if (eventTime && start && !fcEvent.allDay) {
        const h = String(start.getHours()).padStart(2, '0');
        const min = String(start.getMinutes()).padStart(2, '0');
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
      if ($jq && $jq('#eventClassroom').length) {
        const clsVal =
          allClassrooms || ext.classroom_id == null || ext.classroom_id === ''
            ? ''
            : String(ext.classroom_id);
        $jq('#eventClassroom').val(clsVal).trigger('change');
      }
      if ($jq && $jq('#eventBatches').length) {
        const batchVal =
          allBatches || ext.batch_id == null || ext.batch_id === '' ? '' : String(ext.batch_id);
        window.requestAnimationFrame(function () {
          $jq('#eventBatches').val(batchVal).trigger('change');
        });
      }
      if ($jq && $jq('#eventStatus').length) {
        $jq('#eventStatus').val(String(ext.status != null ? ext.status : 0)).trigger('change');
      }
      if (eventDescription) {
        eventDescription.value = ext.description != null ? String(ext.description) : '';
      }
      document.dispatchEvent(new CustomEvent('teacherCalendarPrefill'));
    }

    // Event click function
    function eventClick(info) {
      eventToUpdate = info.event;
      if (eventToUpdate.url) {
        info.jsEvent.preventDefault();
        window.open(eventToUpdate.url, '_blank');
      }
      bsAddEventSidebar.show();
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
      if (eventLabel.length && eventToUpdate.extendedProps && eventToUpdate.extendedProps.calendar) {
        eventLabel.val(eventToUpdate.extendedProps.calendar).trigger('change');
      }
      if (eventLocation && eventToUpdate.extendedProps && eventToUpdate.extendedProps.location !== undefined) {
        eventLocation.value = eventToUpdate.extendedProps.location;
      }
      if (eventGuests.length && eventToUpdate.extendedProps && eventToUpdate.extendedProps.guests !== undefined) {
        eventGuests.val(eventToUpdate.extendedProps.guests).trigger('change');
      }
      if (eventDescription && eventToUpdate.extendedProps && eventToUpdate.extendedProps.description !== undefined) {
        eventDescription.value = eventToUpdate.extendedProps.description;
      }
      fillPortalTeacherEventForm(info.event);
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
      const filterInputChecked = [].slice.call(calendarFilterRoot.querySelectorAll('.input-filter:checked'));

      filterInputChecked.forEach(item => {
        const v = item.getAttribute('data-value');
        if (v) {
          selected.push(String(v).toLowerCase().trim());
        }
      });

      return selected;
    }

    // --------------------------------------------------------------------------------------------------
    // AXIOS: fetchEvents
    // * This will be called by fullCalendar to fetch events. Also this can be used to refetch events.
    // --------------------------------------------------------------------------------------------------
    function fetchEvents(info, successCallback) {
      const calendars = selectedCalendars();
      const totalTypeFilters = calendarFilterRoot.querySelectorAll('.input-filter').length;
      // We are reading event object from app-calendar-events.js file directly by including that file above app-calendar file.
      // You should make an API call, look into above commented API call for reference
      let selectedEvents = currentEvents.filter(function (event) {
        const cal =
          event.extendedProps && typeof event.extendedProps.calendar === 'string'
            ? event.extendedProps.calendar.toLowerCase()
            : '';
        if (totalTypeFilters === 0) {
          return true;
        }
        if (!calendars.length) {
          return false;
        }
        return cal && calendars.includes(cal);
      });
      // if (selectedEvents.length > 0) {
      successCallback(selectedEvents);
      // }
    }

    // Init FullCalendar
    // ------------------------------------------------
    let calendar = new Calendar(calendarEl, {
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
        let date = moment(info.date).format('YYYY-MM-DD');
        resetValues();
        bsAddEventSidebar.show();

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
    if (eventForm) {
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
            bsAddEventSidebar.hide();
          }
        }
      });
    }

    // Call removeEvent function
    if (btnDeleteEvent) {
      btnDeleteEvent.addEventListener('click', e => {
        removeEvent(parseInt(eventToUpdate.id));
        // eventToUpdate.remove();
        bsAddEventSidebar.hide();
      });
    }

    // Reset event form inputs values
    // ------------------------------------------------
    function resetValues() {
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
        portalJq('#eventClassroom').val('').trigger('change');
        portalJq('#eventBatches').val('').trigger('change');
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
    addEventSidebar.addEventListener('hidden.bs.offcanvas', function () {
      resetValues();
    });

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
    if (selectAll) {
      selectAll.addEventListener('click', e => {
        if (e.currentTarget.checked) {
          calendarFilterRoot.querySelectorAll('.input-filter').forEach(c => {
            c.checked = true;
          });
        } else {
          calendarFilterRoot.querySelectorAll('.input-filter').forEach(c => {
            c.checked = false;
          });
        }
        calendar.refetchEvents();
      });
    }

    if (filterInputs) {
      filterInputs.forEach(item => {
        item.addEventListener('click', () => {
          const checked = calendarFilterRoot.querySelectorAll('.input-filter:checked').length;
          const total = calendarFilterRoot.querySelectorAll('.input-filter').length;
          selectAll.checked = total > 0 && checked === total;
          calendar.refetchEvents();
        });
      });
    }

    // Jump to date on sidebar(inline) calendar change
    if (inlineCalInstance && inlineCalInstance.config && inlineCalInstance.config.onChange) {
      inlineCalInstance.config.onChange.push(function (date) {
        calendar.changeView(calendar.view.type, moment(date[0]).format('YYYY-MM-DD'));
        modifyToggler();
        if (appCalendarSidebar) {
          appCalendarSidebar.classList.remove('show');
        }
        if (appOverlay) {
          appOverlay.classList.remove('show');
        }
      });
    }
  })();
});

            const enrollmentOptionsUrl = "{{ url('admin/student/enrollment-options') }}";
            const listEnrAllTeachers = @json(
                ($teachers ?? collect())->map(static fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name])->values()
            );
            let listEnrMapsByTeacher = [];
            let listEnrOptionsCache = {};
            let listEnrBlockCounter = 0;

            function getListMapsForTeacher(teacherId) {
                const t = String(teacherId);
                const hit = (listEnrMapsByTeacher || []).find(function(row) {
                    return String(row.teacher_id) === t;
                });
                return hit && hit.maps ? hit.maps : [];
            }

            function showStudentListEnrollmentModal() {
                const modalEl = document.getElementById('studentListEnrollmentsModal');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else {
                    $('#studentListEnrollmentsModal').modal('show');
                }
            }

            function fetchListEnrollmentOptions(teacherId) {
                const tid = parseInt(teacherId, 10) || 0;
                if (!tid) {
                    return $.Deferred().reject('Select a teacher.').promise();
                }
                if (listEnrOptionsCache[tid]) {
                    return $.Deferred().resolve(listEnrOptionsCache[tid]).promise();
                }
                return $.ajax({
                    url: enrollmentOptionsUrl,
                    method: 'GET',
                    data: {
                        teacher_id: tid
                    },
                    dataType: 'json',
                }).then(function(res) {
                    if (!res || parseInt(res.status, 10) !== 1) {
                        throw new Error((res && res.error) ? String(res.error) : 'Could not load classrooms.');
                    }
                    const data = res.data || {};
                    listEnrOptionsCache[tid] = {
                        classrooms: data.classrooms || [],
                        batches: data.batches || [],
                    };
                    return listEnrOptionsCache[tid];
                });
            }

            function loadListBatchesIntoSelect($batchSelect, batches, classroomId, selectedBatchId, done) {
                selectedBatchId = selectedBatchId || '';
                if (!classroomId) {
                    $batchSelect.html('<option value="">Choose classroom first</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                    return;
                }
                const filtered = (batches || []).filter(function(b) {
                    return String(b.classroom_id) === String(classroomId);
                });
                if (!filtered.length) {
                    $batchSelect.html('<option value="">No batches found</option>').prop('disabled', true);
                    if (typeof done === 'function') done();
                    return;
                }
                $batchSelect.empty().append($('<option/>', {
                    value: '',
                    text: 'Choose batch'
                }));
                filtered.forEach(function(batch) {
                    const o = $('<option/>').attr('value', batch.id).text(batch.name);
                    if (String(batch.id) === String(selectedBatchId)) {
                        o.prop('selected', true);
                    }
                    $batchSelect.append(o);
                });
                $batchSelect.prop('disabled', false);
                if (typeof done === 'function') done();
            }

            function appendListEnrollmentRowToBlock($block, blockIdx, classroomId, batchId, options) {
                const tpl = document.getElementById('student-list-enrollment-row-template');
                if (!tpl) {
                    return;
                }
                const frag = tpl.content.cloneNode(true);
                const el = frag.querySelector('.student-list-enrollment-row');
                $block.find('.student-list-enrollment-rows').append(el);
                const $row = $(el);
                const $c = $row.find('.list-enr-classroom');
                const $b = $row.find('.list-enr-batch');
                $c.attr('name', 'enrollment_blocks[' + blockIdx + '][map_classroom_id][]');
                $b.attr('name', 'enrollment_blocks[' + blockIdx + '][map_batch_id][]');
                (options.classrooms || []).forEach(function(c) {
                    $c.append($('<option/>').attr('value', c.id).text(c.name));
                });
                if (classroomId) {
                    $c.val(String(classroomId));
                }
                loadListBatchesIntoSelect($b, options.batches || [], $c.val(), batchId);
            }

            function populateTeacherBlock($block, blockIdx, teacherId, maps) {
                const $teacherSel = $block.find('.list-enr-block-teacher');
                $teacherSel.empty().append($('<option/>', {
                    value: '',
                    text: 'Select teacher'
                }));
                listEnrAllTeachers.forEach(function(t) {
                    $teacherSel.append($('<option/>').attr('value', t.id).text(t.name));
                });
                if (teacherId) {
                    $teacherSel.val(String(teacherId));
                }
                $block.attr('data-block-idx', String(blockIdx));
                $block.find('.student-list-enrollment-rows').html(
                    '<p class="small text-muted mb-0">Loading…</p>');
                const tid = parseInt($teacherSel.val(), 10) || 0;
                if (!tid) {
                    $block.find('.student-list-enrollment-rows').html(
                        '<p class="small text-muted mb-0">Select a teacher to load classrooms.</p>');
                    return $.Deferred().resolve().promise();
                }
                return fetchListEnrollmentOptions(tid).then(function(options) {
                    $block.find('.student-list-enrollment-rows').empty();
                    if (!options.classrooms.length) {
                        $block.find('.student-list-enrollment-rows').html(
                            '<div class="alert alert-warning mb-0 py-2 small">This teacher has no classrooms yet. Add them from the teacher page.</div>'
                        );
                        return;
                    }
                    const rows = maps && maps.length ? maps : [{
                        classroom_id: '',
                        batch_id: ''
                    }];
                    rows.forEach(function(m) {
                        appendListEnrollmentRowToBlock($block, blockIdx, m.classroom_id, m.batch_id, options);
                    });
                }).fail(function(err) {
                    let msg = 'Could not load classrooms.';
                    if (err && err.message) {
                        msg = err.message;
                    } else if (err && err.responseJSON && err.responseJSON.error) {
                        msg = err.responseJSON.error;
                    } else if (typeof err === 'string' && err) {
                        msg = err;
                    } else if (err && err.status) {
                        msg = 'Could not load classrooms (HTTP ' + err.status + ').';
                    }
                    $block.find('.student-list-enrollment-rows').html(
                        '<div class="alert alert-danger mb-0 py-2 small">' + msg + '</div>'
                    );
                });
            }

            function appendListTeacherBlock(teacherId, maps) {
                const tpl = document.getElementById('student-list-teacher-block-template');
                if (!tpl) {
                    return $.Deferred().reject().promise();
                }
                const blockIdx = listEnrBlockCounter++;
                const html = tpl.innerHTML.replace(/__IDX__/g, String(blockIdx));
                const $block = $(html);
                $('#student-list-enrollment-blocks').append($block);
                return populateTeacherBlock($block, blockIdx, teacherId, maps);
            }

            function refreshListTeacherBlock($block) {
                const blockIdx = parseInt($block.attr('data-block-idx'), 10);
                const teacherId = $block.find('.list-enr-block-teacher').val();
                return populateTeacherBlock($block, blockIdx, teacherId, []);
            }

            $(document).on('click', '.open-student-enrollments-list', function() {
                const studentId = $(this).data('student-id');
                const defaultTeacherId = $(this).data('teacher-id');
                try {
                    listEnrMapsByTeacher = JSON.parse($(this).attr('data-maps-by-teacher') || '[]');
                } catch (e) {
                    listEnrMapsByTeacher = [];
                }
                $('#list_enr_student_id').val(studentId);
                clearAjaxErrors('#studentListEnrollmentsModal');
                listEnrOptionsCache = {};
                listEnrBlockCounter = 0;

                const $initialIds = $('#list-enr-initial-teacher-ids').empty();
                listEnrMapsByTeacher.forEach(function(entry) {
                    $initialIds.append($('<input/>', {
                        type: 'hidden',
                        name: 'initial_teacher_ids[]',
                        value: entry.teacher_id
                    }));
                });

                const blocks = listEnrMapsByTeacher.length ? listEnrMapsByTeacher : [{
                    teacher_id: defaultTeacherId,
                    maps: []
                }];
                $('#student-list-enrollment-blocks').html('<p class="small text-muted mb-0">Loading…</p>');
                let p = $.Deferred().resolve().promise();
                blocks.forEach(function(entry) {
                    p = p.then(function() {
                        if ($('#student-list-enrollment-blocks .student-list-teacher-block').length === 0 &&
                            $('#student-list-enrollment-blocks p.text-muted').length) {
                            $('#student-list-enrollment-blocks').empty();
                        }
                        return appendListTeacherBlock(entry.teacher_id, entry.maps || []);
                    });
                });
                p.always(function() {
                    showStudentListEnrollmentModal();
                });
            });

            $(document).on('change', '#studentListEnrollmentsModal .list-enr-block-teacher', function() {
                refreshListTeacherBlock($(this).closest('.student-list-teacher-block'));
            });

            $(document).on('change', '#studentListEnrollmentsModal .list-enr-classroom', function() {
                const $block = $(this).closest('.student-list-teacher-block');
                const teacherId = parseInt($block.find('.list-enr-block-teacher').val(), 10) || 0;
                const options = listEnrOptionsCache[teacherId] || {
                    classrooms: [],
                    batches: []
                };
                const $row = $(this).closest('.student-list-enrollment-row');
                loadListBatchesIntoSelect($row.find('.list-enr-batch'), options.batches, $(this).val(), '');
            });

            $(document).on('click', '#student-list-add-teacher-block', function() {
                if ($('#student-list-enrollment-blocks .student-list-teacher-block').length === 0) {
                    $('#student-list-enrollment-blocks').empty();
                }
                appendListTeacherBlock('', []);
            });

            $(document).on('click', '.student-list-add-enrollment-row-in-block', function() {
                const $block = $(this).closest('.student-list-teacher-block');
                const blockIdx = parseInt($block.attr('data-block-idx'), 10);
                const teacherId = parseInt($block.find('.list-enr-block-teacher').val(), 10) || 0;
                const options = listEnrOptionsCache[teacherId];
                if (!options || !options.classrooms.length) {
                    return;
                }
                appendListEnrollmentRowToBlock($block, blockIdx, '', '', options);
            });

            $(document).on('click', '.student-list-remove-teacher-block', function() {
                const $blocks = $('#student-list-enrollment-blocks .student-list-teacher-block');
                if ($blocks.length <= 1) {
                    alert('At least one teacher section is required.');
                    return;
                }
                $(this).closest('.student-list-teacher-block').remove();
            });

            $(document).on('click', '.student-list-remove-enr-row', function() {
                const $rows = $(this).closest('.student-list-enrollment-rows').find('.student-list-enrollment-row');
                if ($rows.length <= 1) {
                    $(this).closest('.student-list-enrollment-row').find('.list-enr-classroom').val('');
                    $(this).closest('.student-list-enrollment-row').find('.list-enr-batch').html(
                        '<option value="">Choose classroom first</option>').prop('disabled', true);
                    return;
                }
                $(this).closest('.student-list-enrollment-row').remove();
            });

            const listEnrDuplicateClassroomMsg =
                'A student can only be assigned to one batch per classroom. Remove duplicate classrooms or choose a single batch for each classroom.';

            window.validateListEnrollmentFormBeforeSave = function() {
                let valid = true;
                let error = '';
                $('#student-list-enrollment-blocks .student-list-teacher-block').each(function() {
                    const seenClassrooms = {};
                    $(this).find('.student-list-enrollment-row').each(function() {
                        const cid = String($(this).find('.list-enr-classroom').val() || '');
                        const bid = String($(this).find('.list-enr-batch').val() || '');
                        if (!cid || !bid) {
                            if (!error) {
                                error = 'Each row needs a valid classroom and batch.';
                            }
                            valid = false;
                            return;
                        }
                        if (seenClassrooms[cid]) {
                            error = listEnrDuplicateClassroomMsg;
                            valid = false;
                            return false;
                        }
                        seenClassrooms[cid] = true;
                    });
                    if (!valid) {
                        return false;
                    }
                });
                return { valid: valid, error: error };
            };

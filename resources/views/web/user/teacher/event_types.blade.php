@extends('web.user.layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6 mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Event Types</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#eventTypeModal" id="btnAddEventType">
                    <i class="icon-base ti tabler-plus me-1"></i> Add Event Type
                </button>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Color</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($event_types ?? [] as $et)
                            @php
                                $raw = $et->color_code ? trim((string) $et->color_code) : '';
                                $swatch = $raw === '' ? '#696cff' : $raw;
                                if ($swatch !== '' && $swatch[0] !== '#' && preg_match('/^[0-9a-fA-F]{3,8}$/', $swatch)) {
                                    $swatch = '#' . $swatch;
                                }
                                if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $swatch)) {
                                    $swatch = '#696cff';
                                }
                            @endphp
                            <tr>
                                <td class="fw-medium">{{ $et->title }}</td>
                                <td>
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <span class="badge badge-dot me-2"
                                            style="background-color: {{ e($swatch) }} !important; border: 1px solid {{ e($swatch) }};"
                                            title="{{ e($swatch) }}" aria-hidden="true"></span>
                                        <span class="text-body-secondary small">{{ $swatch }}</span>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-{{ $et->status == 1 ? 'primary' : 'danger' }}">
                                        {{ $et->status == 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-label-primary edit-event-type"
                                        data-id="{{ $et->id }}" data-title="{{ e($et->title) }}"
                                        data-color="{{ e($swatch) }}">
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-label-danger delete-event-type"
                                        data-id="{{ $et->id }}" data-title="{{ e($et->title) }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-body-secondary py-5">No event types yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eventTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventTypeModalTitle">Add Event Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ajax-form-event-type" method="POST" action="{{ url('user/teacher/event_types/save') }}">
                        @csrf
                        <input type="hidden" name="id" id="event_type_id" value="" />
                        <div class="ajax-msg mb-3"></div>
                        <div class="mb-3 ajax-field">
                            <label class="form-label" for="event_type_title">Title</label>
                            <input type="text" class="form-control" id="event_type_title" name="title" />
                            <span class="ajax-error text-danger small"></span>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6 ajax-field">
                                <label class="form-label" for="color_code">Color</label>
                                <input type="color" class="form-control form-control-color w-100" id="color_code"
                                    name="color_code" value=""
                                    title="Pick a color for calendar events of this type" />
                                <span class="ajax-error text-danger small"></span>
                            </div>
                            <div class="col-6 ajax-field">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <span class="ajax-error text-danger small"></span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary submit-button">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            var $modal = $('#eventTypeModal');
            var $form = $('#ajax-form-event-type');

            function clearErrors() {
                $modal.find('.ajax-error').text('');
                $modal.find('.ajax-msg').empty();
            }

            function syncHexPreview() {
                var v = (($('#color_code').val() || '') + '').toUpperCase();
                $('#color_code_hex_preview').text(v || '#696cff');
            }

            $('#btnAddEventType').on('click', function() {
                $modal.find('.modal-title').text('Add Event Type');
                clearErrors();
                $('#event_type_id').val('');
                $('#event_type_title').val('');
                $('#color_code').val('#696cff');
                syncHexPreview();
            });

            $modal.on('shown.bs.modal', function() {
                syncHexPreview();
            });

            $(document).on('input change', '#color_code', syncHexPreview);

            $('.edit-event-type').on('click', function() {
                var btn = $(this);
                $modal.find('.modal-title').text('Edit Event Type');
                clearErrors();
                $('#event_type_id').val(btn.data('id'));
                $('#event_type_title').val(btn.data('title'));
                var c = (btn.data('color') || '#696cff').toString();
                if (c && !c.startsWith('#') && /^[0-9a-fA-F]{3,8}$/i.test(c)) {
                    c = '#' + c;
                }
                $('#color_code').val(c.length === 7 ? c : '#696cff');
                syncHexPreview();
                $modal.modal('show');
            });

            $('.delete-event-type').on('click', function() {
                var id = $(this).data('id');
                var title = $(this).data('title') || 'this type';
                if (!id || !confirm('Delete event type "' + title + '"?')) {
                    return;
                }
                $.post('{{ url('user/teacher/event_types/delete') }}', {
                    _token: '{{ csrf_token() }}',
                    id: id
                }, function(res) {
                    if (res.status == 1 && res.redirect_url) {
                        window.location.href = res.redirect_url;
                    } else if (res.error_array) {
                        alert((res.error_array.id && res.error_array.id[0]) || 'Could not delete.');
                    } else {
                        alert(res.error || 'Could not delete.');
                    }
                }, 'json').fail(function() {
                    alert('Request failed.');
                });
            });

            $form.on('submit', function(e) {
                e.preventDefault();
                clearErrors();
                var btn = $form.find('.submit-button').prop('disabled', true).text('Saving...');
                $.post($form.attr('action'), $form.serialize(), function(res) {
                    btn.prop('disabled', false).text('Save');
                    if (res.status == 1 && res.redirect_url) {
                        window.location.href = res.redirect_url;
                        return;
                    }
                    if (res.error_array) {
                        Object.keys(res.error_array).forEach(function(key) {
                            var msg = Array.isArray(res.error_array[key]) ? res.error_array[key][0] :
                                res.error_array[key];
                            $form.find('[name="' + key + '"]').closest('.ajax-field').find('.ajax-error').text(
                                msg);
                        });
                    } else if (res.error) {
                        $modal.find('.ajax-msg').html(
                            '<div class="alert alert-danger mb-0">' + res.error + '</div>');
                    }
                }, 'json').fail(function() {
                    btn.prop('disabled', false).text('Save');
                });
            });
        });
    </script>
@endsection

@extends('admin.layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-6">
            <div class="col-md-12">
                <div class="card mb-4">
                    <h5 class="card-header">{{ isset($edit) ? 'Edit Teacher' : 'Add Teacher' }}</h5>
                    <div class="card-body">
                        <form id="ajax-form" method="POST" action="{{ url('admin/teacher/save') }}">
                            @csrf
                            @if(isset($edit))
                                <input type="hidden" name="id" value="{{ base64_encode($edit->id) }}" />
                            @endif
                            <div class="col-12 ajax-msg"></div>
                            <div class="row">
                                <div class="mb-3 col-md-6 ajax-field">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="name" name="name"
                                        value="{{ $edit->name ?? '' }}" placeholder="Full Name"  />
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="mb-3 col-md-6 ajax-field">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" id="email" name="email"
                                        value="{{ $edit->email ?? '' }}" placeholder="teacher@example.com" {{ isset($edit) ? 'readonly' : '' }} />
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                <div class="mb-3 col-md-6 ajax-field">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="phone" name="phone"
                                        value="{{ $edit->phone ?? '' }}" placeholder="1234567890" {{ isset($edit) ? 'readonly' : '' }}
                                        inputmode="numeric" />
                                    <span class="ajax-error text-danger small"></span>
                                </div>
                                
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary me-2 submit-button">
                                    <i class="icon-base ti tabler-device-floppy me-1"></i> Save
                                </button>
                                <a href="{{ url('admin/teacher') }}" class="btn btn-label-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script>
    $(document).on('submit', '#ajax-form', function(e) {
        e.preventDefault();
        clearAjaxErrors();

        const _this = $(this);
        // Remove previous invalid styles
        _this.find('input').removeClass('is-invalid');

        // Client-side: only "field is required" validation for Add mode
        let hasError = false;
        const isEdit = {{ isset($edit) ? 'true' : 'false' }};
        const fields = ['#name', '#email', '#phone'];

        if (!isEdit) {
            fields.forEach(function(selector) {
                const $input = _this.find(selector);
                const value = ($input.val() || '').trim();
                const $field = $input.closest('.ajax-field');
                const $error = $field.find('.ajax-error');
                const fieldMessages = {
                    '#name': 'Name field required.',
                    '#email': 'Email field required.',
                    '#phone': 'Phone field required.'
                };

                if (value.length === 0) {
                    hasError = true;
                    $input.addClass('is-invalid');
                    $error.text(fieldMessages[selector] || 'This field is required.');
                } else {
                    $error.text('');
                }
            });
        }

        if (hasError) {
            const $firstInvalid = _this.find('.is-invalid').first();
            if ($firstInvalid.length) {
                $('html, body').animate({ scrollTop: $firstInvalid.offset().top - 120 }, 300);
                $firstInvalid.focus();
            }
            return;
        }

        _this.find('.submit-button').attr('disabled', 'disabled');
        _this.find('.submit-button').html('<i class="icon-base ti tabler-loader me-1"></i> Saving...');

        const url = _this.attr('action');
        const data = _this.serializeArray();

        $.post(url, data, function(res) {
            _this.find('.submit-button').removeAttr('disabled');
            _this.find('.submit-button').html('<i class="icon-base ti tabler-device-floppy me-1"></i> Save');

            processAjaxResponse(res, 1000);
        }, 'json');
    })
</script>
@endsection

<script>
    (function() {
        if (!$('#filter_associate_id').length || !$('#filter_client_id').length) {
            return;
        }

        const cascadeUrl = '{{ url('admin/common/clients-by-associate') }}';

        function loadFilterClients(associateId, currentClientId) {
            const $client = $('#filter_client_id');
            const placeholder = $client.find('option[value=""]').first().text() || 'All Clients';
            $client.prop('disabled', true).empty().append('<option value="">Loading...</option>');

            $.get(cascadeUrl, {
                associate_id: associateId || ''
            }, function(res) {
                $client.empty().append('<option value="">' + placeholder + '</option>');
                (res || []).forEach(function(c) {
                    $client.append('<option value="' + c.id + '">' + c.company_name + '</option>');
                });
                if (currentClientId) {
                    $client.val(String(currentClientId));
                }
                $client.prop('disabled', false);
            }, 'json');
        }

        $('#filter_associate_id').on('change', function() {
            loadFilterClients($(this).val(), null);
        });

        $(function() {
            const associateId = $('#filter_associate_id').val();
            const currentClientId = $('#filter_client_id').data('current-client');
            if (associateId) {
                loadFilterClients(associateId, currentClientId);
            }
        });
    })();
</script>

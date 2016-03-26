<script type="text/javascript">
    (function ($) {
        $(document).ready(function () {
            $('#{!! $tableSelector !!}').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                "autoWidth": false,
                "columnDefs": [
                    {"width": "88px", "targets": -1}
                ],
                pageLength:{{ $perPage }},
                ajax: {
                    "url": "{!! $ajaxUrl !!}",
                    "type": "POST",
                },
                columns: {!! $columns !!},
            });
        });

        $('#{!! $tableSelector !!}').on('click', '.delete-term-btn', function () {
            var c = confirm("Are you sure you want to delete this term?");

            if (c !== true)
                return false;

            var deleteBtn = $(this);
            var id = deleteBtn.data('term-id');
            var taxonomy = deleteBtn.data('taxonomy');
            var row = deleteBtn.closest('tr').get(0);

            $.ajax({
                url: "{!! route('taxonomy_term_deletePost') !!}",
                type: "post",
                data: {
                    term_id: id,
                    taxonomy: taxonomy,
                },
                error: function (data) {
                    alert(data.toString());
                },
                success: function (data) {
                    if (undefined != data.success) {
                        row.remove();
                        if (undefined != data.success.replaces) {
                            $.each(data.success.replaces, function (id, value) {
                                $('#' + id).html(value);
                            });
                        }
                    }
                }
            })
            ;
        });

    })(jQuery)
</script>
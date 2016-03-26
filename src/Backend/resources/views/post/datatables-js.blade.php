<script type="text/javascript">
    (function ($) {
        $(document).ready(function () {
            $('#{!! $tableSelector !!}').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                "autoWidth": false,
                pageLength:{{ $perPage }},
                ajax: {
                    "url": "{!! $ajaxUrl !!}",
                    "type": "POST",
                },
                columns: {!! $columns !!},
            });

            $('#{!! $tableSelector !!}').on('click', '.delete-term-btn', function () {
                var c = confirm("Are you sure you want to delete this term?");

                if (c !== true)
                    return false;

                var deleteBtn = $(this);
                var id = deleteBtn.data('post-id');
                var row = deleteBtn.closest('tr').get(0);

                $.ajax({
                    url: "{!! route('post_ajaxDeletePost') !!}",
                    type: "post",
                    data: {
                        post_id: id,
                    },
                    error: function (data) {
                        alert(data.toString());
                    },
                    success: function (data) {
                        if (undefined != data.success) {
                            row.remove();
                        }
                    }
                })
                ;
            });
        });
    })(jQuery)
</script>
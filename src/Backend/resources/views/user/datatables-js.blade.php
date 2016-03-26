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

            $('#{!! $tableSelector !!}').on('click', '.delete-user-btn', function () {
                var c = confirm("Are you sure you want to delete this user?");

                if (c !== true)
                    return false;

                var deleteBtn = $(this);
                var id = deleteBtn.data('user-id');
                var row = deleteBtn.closest('tr').get(0);

                $.ajax({
                    url: "{!! route('user_ajaxDeletePost') !!}",
                    type: "post",
                    data: {
                        user_id: id,
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
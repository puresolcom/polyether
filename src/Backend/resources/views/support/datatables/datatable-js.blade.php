<script type="text/javascript">
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
</script>
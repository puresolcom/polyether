<script type="text/javascript">
    $('#{!! $tableSelector !!}').DataTable({
        processing: true,
        serverSide: true,
        pageLength:{{ $perPage }},
        ajax: {
            "url": "{!! $ajaxUrl !!}",
            "type": "POST",
        },
        columns: {!! $columns !!},
    });
</script>
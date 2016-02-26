<table id="{!! $tableSelector !!}" class="table table-striped table-bordered">
    <thead>
    <tr>
        @foreach($columns as $column)
            <th>{{ $column->label }}</th>
        @endforeach
    </tr>
    </thead>
    <tfoot>
    <tr>
        @foreach($columns as $column)
            <th>{{ $column->label }}</th>
        @endforeach
    </tr>
    </tfoot>
</table>
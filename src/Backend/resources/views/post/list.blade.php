@extends('backend::layouts.app')

@section('main-content')
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    {!! $datatable_html !!}
                </div>
            </div>
        </div>
    </div>
@stop
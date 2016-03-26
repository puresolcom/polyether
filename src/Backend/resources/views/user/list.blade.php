@extends('backend::layouts.app')

@section('main-content')
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    {{--<ul class="post_statuses">--}}
                    {{--<li class="all"><a href="#" class="{{ null == (Request::segment(4)) || Request::segment(4) == 'all' ? 'current' : '' }}">All <span class="count">(1)</span></a></li>--}}
                    {{--<li class="publish"><a href="edit.php?post_status=publish&amp;post_type=post">Published <span class="count">(1)</span></a></li>--}}
                    {{--</ul>--}}
                    {!! $datatable_html !!}
                </div>
            </div>
        </div>
    </div>
@stop
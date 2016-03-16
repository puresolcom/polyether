@extends('backend::layouts.app')

@section('main-content')
    <div class="row">
        <div class="col-md-12">
            @include('backend::layouts.partials.session_messages')
            <div class="box">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-5">
                            {!! BootForm::open(['type' => 'post', 'url' => route('taxonomy_term_new', [$taxonomy->name])]) !!}
                            {!! BootForm::text('name', 'Name',null, ['required' => 'required']) !!}
                            {!! BootForm::text('slug', 'Slug') !!}
                            @if ($taxonomy->hierarchical)
                                <div class="form-group">
                                    {!! BootForm::label('parent', 'Parent') !!}

                                    {!!
                                             Taxonomy::UITerms( [
                                             'show_options_all'  => 'Select Parent', 'show_option_none' => 'None',
                                             'option_none_value' => '0', 'echo' => false, 'hierarchical' => true,
                                             'spacer'            => '&nbsp;&nbsp;&nbsp;&nbsp;',
                                             'name' => "parent", 'id' => "{$taxonomy->name}_parent",
                                             'class'             => 'form-control', 'selected' => 0, 'value_field' => 'term_id',
                                             'taxonomy'          => $taxonomy->name, 'type' => 'dropdown'
                                      ] )
                                     !!}
                                </div>
                            @endif
                            {!! BootForm::textarea('description', 'Description') !!}

                            {!! BootForm::submit('Add New') !!}
                            {!! BootForm::close() !!}

                        </div>
                        <div class="col-md-7">
                            {!! $datatable_html !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
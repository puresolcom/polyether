@extends('backend::layouts.app')

@section('main-content')
    <div class="row">
        <div class="col-md-12">
            @include('backend::layouts.partials.session_messages')
            <div class="box">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">

                            {!! BootForm::open(['method' => 'put', 'url' => route('term_taxonomy_editPut', [$term->taxonomy, $term->term_id])]) !!}
                            {!! BootForm::text('name', 'Name',$term->name, ['required' => 'required']) !!}
                            {!! BootForm::text('slug', 'Slug', $term->slug) !!}

                            @if ($taxonomy->hierarchical)
                                <div class="form-group">
                                    {!! BootForm::label('parent', 'Parent') !!}
                                    {!!
                                             Taxonomy::UITerms( [
                                             'show_options_all'  => 'Select Parent', 'show_option_none' => 'None',
                                             'option_none_value' => '0', 'echo' => false, 'hierarchical' => true,
                                             'spacer'            => '&nbsp;&nbsp;&nbsp;&nbsp;', 'exclude' => [$term->term_id],
                                             'name' => "parent",
                                             'class'             => 'form-control', 'selected' => $term->parent, 'value_field' => 'term_id',
                                             'taxonomy'          => $term->taxonomy, 'type' => 'dropdown'
                                      ] )
                                     !!}
                                </div>
                            @endif


                            {!! BootForm::textarea('description', 'Description', $term->description) !!}

                            {!! BootForm::submit('Update') !!}
                            {!! BootForm::close() !!}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
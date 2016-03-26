@extends('backend::layouts.app')

@section('main-content')
    <section class="create-post">
        @include('backend::layouts.partials.session_messages')
        {!! BootForm::open(['url' => route('post_createPost', $postTypeObject->name), 'method' => 'post']) !!}
        <div class="row">
            <div class="col-lg-9 col-md-8 col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit {{ ucfirst($postTypeObject->name) }}</h3>
                    </div>
                    <!-- /.box-header -->
                    <!-- form start -->
                    <div class="box-body">
                        <div class="post-title-wrapper">
                            {!! BootForm::text('post[post_title]', 'Title', null ,['id' => 'post_title']) !!}
                        </div>
                        {!! Backend::renderContentEditor('post[post_content]', 'Content', null , ['id' => 'post_content', 'class' => 'post-content-editor']) !!}
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-12">

                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Publish</h3>

                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                                        class="fa fa-minus"></i>
                            </button>
                        </div>
                        <!-- /.box-tools -->
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        {!! BootForm::select('post[post_status]', 'Status', ['publish' => 'Published', 'draft' => 'Draft', 'pending' => 'Pending Review'], null) !!}
                        <div class="form-group">
                            <label for="created_at" class="control-label">Published On</label>
                            <div class="input-group date" id="post_created_at_date">
                                <input type="text" name="post[created_at]"
                                       value=""
                                       class="form-control">
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- /.box-body -->
                    <div class="box-footer">
                        {!! BootForm::submit('Save') !!}
                    </div>
                </div>
                <!-- /.box -->

                @foreach($uiTaxonomies as $taxName=>$taxObj)
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title">{{ $taxObj->labels['name'] or str_plural($taxName) }}</h3>

                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                                            class="fa fa-minus"></i>
                                </button>
                            </div>
                            <!-- /.box-tools -->
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body">
                            <div id="{{$taxName}}_checklist">
                                @include('backend::post.taxonomy-checklist')
                            </div>
                        </div>
                        <!-- /.box-body -->
                        @if ($taxObj->hierarchical)
                            <div class="box-footer">
                                <h5 class="toggle-add-taxonomy-term"><a href="#"><i class="fa fa-plus-circle"></i>
                                        Add {{ ucfirst($taxName) }}</a></h5>
                                <div class="add-taxonomy-term-wrapper" style="display: none;">
                                    <div class="form-group">
                                        <input type="text" name="new{{$taxName}}" data-required="true"
                                               placeholder="Term name"
                                               class="form-control">
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <div id="{{$taxName}}_parent_select">
                                            @include('backend::post.taxonomy-parent-select')
                                        </div>
                                        <span class="input-group-btn">
                                      <button type="button" class="btn btn-info btn-flat"
                                              id="{{$taxName}}-add-submit">Add
                                      </button>
                                    </span>
                                    </div>
                                    <br/>
                                    <div class="ajax-alert">

                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <!-- /.box -->
                @endforeach

            </div>
        </div>
        {!! BootForm::close() !!}
    </section>
@stop
@extends('backend::layouts.app')

@section('main-content')
    <section class="edit-post">
        {!! BootForm::open(['model' => $post, 'update' => 'post_editPut', 'method' => 'post']) !!}
        <div class="row">
            <div class="col-lg-9 col-md-8 col-sm-12">
                @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> There were some problems with your input.<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit {{ ucfirst($post->post_type) }}</h3>
                    </div>
                    <!-- /.box-header -->
                    <!-- form start -->
                    <div class="box-body">
                        <div class="post-title-wrapper">
                            {!! BootForm::text('post_title', 'Title', null ,['id' => 'title']) !!}
                        </div>
                        {!! BootForm::textarea('post_content', 'Content', null , ['id' => 'content']) !!}
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
                        {!! BootForm::select('post_status', 'Status', ['publish' => 'Published', 'draft' => 'Draft', 'pending' => 'Pending Review']) !!}
                        <div class="form-group">
                            <label for="created_at" class="control-label">Published On</label>
                            <div class="input-group date" id="post_created_at_date">
                                <input type="text" name="created_at"
                                       value="{!! $post->created_at !!}"
                                       class="form-control">
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- /.box-body -->
                    <div class="box-footer">
                        {!! BootForm::submit('Update') !!}
                    </div>
                </div>
                <!-- /.box -->

                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Categories</h3>

                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                                        class="fa fa-minus"></i>
                            </button>
                        </div>
                        <!-- /.box-tools -->
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        {!! Taxonomy::UITerms([
                                                'orderby'           => 'id',
                                                'order'             => 'ASC',
                                                'with_post_counts'  => true,
                                                'hide_empty'        => false,
                                                'echo'              => false,
                                                'hierarchical'      => true,
                                                'name'              => 'cat',
                                                'selected'          => array_pluck(Taxonomy::getObjectTerms($post->id, 'category'), 'id'),
                                                'value_field'       => 'term_id',
                                                'taxonomy'          => 'category',
                                                ])
                        !!}
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->

            </div>
        </div>
        {!! BootForm::close() !!}
    </section>
@stop
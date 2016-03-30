@extends('backend::layouts.app')

@section('main-content')
    <section class="edit-post">
        @include('backend::layouts.partials.session_messages')
        {!! BootForm::open(['url' => route('user_createPost'), 'method' => 'post']) !!}
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $title }}</h3>
                    </div>
                    <!-- /.box-header -->
                    <!-- form start -->
                    <div class="box-body">
                        <h3>User Information</h3>

                        {!! BootForm::text('user[username]', 'Username', null ,['id' => 'username', 'required' => 'required']) !!}
                        {!! BootForm::text('user[first_name]', 'First Name', null ,['id' => 'first_name', 'required' => 'required']) !!}
                        {!! BootForm::text('user[last_name]', 'Last Name', null ,['id' => 'last_name', 'required' => 'required']) !!}
                        {!! BootForm::password('user[password]', 'Password', ['required' => 'required']) !!}

                        <h3>Contact Information</h3>
                        {!! BootForm::text('user[email]', 'E-Mail', null ,['id' => 'user_email', 'required' =>'required']) !!}

                        <h3>User Status</h3>
                        <div class="checkbox icheck">
                            <label>
                                <input type="checkbox" name="user['enabled']" value="1"> Enabled
                            </label>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>
        {!! BootForm::submit('Create') !!}
        {!! BootForm::close() !!}
    </section>
@stop
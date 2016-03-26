@extends('backend::layouts.app')

@section('main-content')
    <section class="edit-post">
        @include('backend::layouts.partials.session_messages')
        {!! BootForm::open(['url' => route('user_editPut', $user->id), 'method' => 'put']) !!}
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

                        {!! BootForm::text('user[username]', 'Username', $user->username ,['id' => 'username', 'disabled'=> 'disabled']) !!}
                        {!! BootForm::text('user[first_name]', 'First Name', $user->first_name ,['id' => 'first_name']) !!}
                        {!! BootForm::text('user[last_name]', 'Last Name', $user->last_name ,['id' => 'last_name']) !!}

                        <h3>Contact Information</h3>
                        {!! BootForm::text('user[email]', 'E-Mail', $user->email ,['id' => 'user_email', 'required' =>'required']) !!}
                        {!! BootForm::text('usermeta[website]', 'Website', Meta::get('user', $user->id, 'website', true) ,['id' => 'last_name']) !!}

                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>
        {!! BootForm::submit('Update') !!}
        {!! BootForm::close() !!}
    </section>
@stop
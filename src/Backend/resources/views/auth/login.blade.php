@extends('backend::layouts.auth')

@section('htmlheader_title')
    Log in
@endsection

@section('content')
    <body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="{{ url('/home') }}"><b>Admin</b>LTE</a>
        </div><!-- /.login-logo -->

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

        <div class="login-box-body">
            <p class="login-box-msg">Sign in to start your session</p>
            <form action="{{ route('loginPost') }}" method="post">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                {!! BootForm::text('username', null, null,['placeholder' => 'Username', 'class' => 'form-control']) !!}

                {!! BootForm::password('password', null, ['placeholder' => 'Password', 'class' => 'form-control']) !!}

                <div class="row">
                    <div class="col-xs-8">
                        <div class="checkbox icheck">
                            <label>
                                <input type="checkbox" name="remember"> Remember Me
                            </label>
                        </div>
                    </div><!-- /.col -->
                    <div class="col-xs-4">
                        <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
                    </div><!-- /.col -->
                </div>
            </form>

            <a href="{{ url('password/email') }}">I forgot my password</a><br>
            <a href="{{ route('register') }}" class="text-center">Register a new membership</a>

        </div><!-- /.login-box-body -->

    </div><!-- /.login-box -->

    @include('backend::layouts.partials.scripts_auth')
    </body>

@endsection

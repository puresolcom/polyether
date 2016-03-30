@if(Request::session()->has('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        @if (is_array(Request::session()->get('success')))
            @foreach(Request::session()->get('success') as $message)
                <li>{!! $message !!}</li>
            @endforeach
        @else
            {!! Request::session()->get('success') !!}
        @endif
    </div>
@endif

@if(Request::session()->has('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        @if (is_array(Request::session()->get('error')))
            @foreach(Request::session()->get('error') as $message)
                <li>{!! $message !!}</li>
            @endforeach
        @else
            {!! Request::session()->get('error') !!}
        @endif
    </div>
@endif


@if($errors->any())
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        @if (is_array($errors->all()))
            @foreach($errors->all() as $message)
                <li>{!! $message !!}</li>
            @endforeach
        @else
            {!! $errors !!}
        @endif
    </div>
@endif
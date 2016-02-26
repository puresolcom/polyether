<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{ $title or 'Dashboard' }} ‹ {{ Option::get('site_title', 'EtherCMS') }} </title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

    {!! Asset::container('backend_header')->show() !!}

</head>
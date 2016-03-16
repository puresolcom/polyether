<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{ $title or 'Dashboard' }} &lsaquo; {{ Option::get('site_title', 'EtherCMS') }} </title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    {!! Plugin::do_action('ether_backend_head') !!}
    <script type="text/javascript">
        {!! Plugin::do_action('ether_backend_global_js') !!}
    </script>
</head>
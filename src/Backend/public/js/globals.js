(function ($) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    window.checkBox = function () {
        $('.icheck input[type=checkbox]').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square-blue'
        });
    }

    checkBox();

    $('.apply-select2').select2({width: '100%'});

})
(jQuery)
(function ($) {
    $(document).ready(function () {
        var csrfToken = $('meta[name=csrf-token]').attr('content');
        $('.post-content-editor').ckeditor({
            filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
            filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token=' + csrfToken,
            filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
            filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token=' + csrfToken,
        });
    });
})(jQuery);
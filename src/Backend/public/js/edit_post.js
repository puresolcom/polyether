(function ($) {
    $(document).ready(function () {


        // Taxonomy Tags Auto-complete
        $('.taxonomy-tag-select').each(function () {
            var _this = $(this);
            $(this).select2({
                width: "100%",
                tags: true,
                tokenSeparators: [','],
                ajax: {
                    type: "post",
                    url: ajaxUrl + '/get_taxonomy_terms',
                    dataType: 'json',
                    delay: 500,
                    data: function (params) {
                        var query = {
                            term: params.term,
                            page: params.page,
                            taxonomy: _this.data('taxonomy'),
                            value: _this.data('value-field'),
                        }
                        return query;
                    },
                    processResults: function (data) {
                        return {results: data}
                    },
                },
                minimumInputLength: 2,
            });
        });

        $('.toggle-add-taxonomy-term').click(function (e) {
            $(this).next().toggle();
            e.preventDefault();
        });

        // Submit new taxonomy term
        $('.add-taxonomy-term-wrapper').each(function () {
            var _this = $(this);
            var _submit = _this.find('button');
            var params;
            var inputs = {};
            var doAjax = true;

            _submit.click(function (e) {
                _this.find('.ajax-alert').html("");
                doAjax = true;
                params = _this.find('input, select');
                params.each(function () {
                    inputs[$(this).attr('name')] = $(this).val();
                    if ($(this).attr('data-required') && $(this).data('required') == true && '' == $(this).val()) {
                        $(this).focus();
                        doAjax = false;
                    }
                });
                inputs['post_id'] = window.objectId;

                if (doAjax) {
                    var _that = _this;
                    $.ajax({
                        type: "post",
                        url: ajaxUrl + "/add_taxonomy_term",
                        dataType: "json",
                        data: inputs,
                        success: function (data) {
                            if (undefined != data.error) {
                                _that.find('.ajax-alert').html('<div class="alert alert-danger"> <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> <strong>Alert!</strong> ' + data.error + '</div>');
                            } else if (undefined != data.success) {
                                $.each(data.success.replaces, function (k, v) {
                                    $('#' + k).html(v);
                                });
                                window.checkBox();
                            }
                        }
                    });
                }

                e.preventDefault();
            });
        });
    });
})(jQuery);
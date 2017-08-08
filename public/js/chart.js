var $ = jQuery.noConflict();
$(document).ready(function () {

    function chart_form_action(selector) {
        $(selector).submit(function (e) {
            e.preventDefault();
            $(window).trigger('bro_ajax_comments_beforeSubmitForm');
            var form_method = $(this).attr('method');
            var form_action = $(this).attr('action');
            var form_elem = $(this);
            var form_data = $(this).serialize();
            $.ajax({
                type: form_method,
                url: form_action,
                data: form_data,
                beforeSend: function (jqXHR, status) {
                },
                success: function (json) {
                    if (json.hasOwnProperty('html')) {
                        var wrap = form_elem.parent();
                        wrap.html(json.html);
                    }
                }
                ,
                complete: function (jqXHR, status) {
                    chart_form_action_init();
                }
            });
        });
    }


    function chart_form_action_init() {
        chart_form_action(".dashboard__statistics-chart-filter");
    }
    chart_form_action_init();

});
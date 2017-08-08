var $ = jQuery.noConflict();
$(document).ready(function () {

    function wp_no_external_links_dashboard_statistics_form(selector) {
        $(selector).submit(function (e) {
            e.preventDefault();
            var m_method = $(this).attr('method');
            var m_action = $(this).attr('action');
            var m_form = $(this);
            var m_data = $(this).serialize();
            $.ajax({
                type: m_method,
                url: m_action,
                data: m_data,
                success: function (result) {
                    if (result) {
                        m_form.closest('.dashboard__statistics').find(".dashboard__statistics-content").append(result.items);
                    }
                }
            });
        });
    }

    wp_no_external_links_dashboard_statistics_form('.dashboard__statistics-header form');

    function autocomplete(selector) {
        var action = $(selector).attr('data-attr-action');
        $(selector).autocomplete({
            source: action
            , minLength: 3
        });
    }

    autocomplete(".dashboard__statistics-search-link");


    function clear(selector) {

        $(selector).on('click', function () {
            $(this).closest(".dashboard__statistics").find(".dashboard__statistics-conten-item").remove();
        });
    }

    clear(".dashboard__statistics-field .clear");

});
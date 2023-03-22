M.block_user_manager_close_table= {
    init: function (Y) {
        $(document).ready(function () {
            // $('span[data-close=\"tab\"]').click(function() {
            //     var tabpanel = $(this).parent();
            //     var tabID = tabpanel.attr('aria-labelledby');
            //     tabpanel.removeClass('active show');
            //     $('#' + tabID).removeClass('active show');
            // });

            $('.nav-item').click(function() {
                var tabpanel = $(this).parent();
                var tabID = tabpanel.attr('aria-labelledby');
                tabpanel.removeClass('active show');
                $('#' + tabID).removeClass('active show');
            });
        });
    }
}
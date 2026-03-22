$(document).ready(function() {
    // Sidebar Toggle
    $('#sidebarToggle').on('click', function() {
        if ($(window).width() > 768) {
            $('.sidebar').toggleClass('collapsed');
            $('.main-content').toggleClass('expanded');
        } else {
            $('.sidebar').toggleClass('active');
        }
    });

    // Submenu Toggle
    $('.has-submenu > a').on('click', function(e) {
        e.preventDefault();
        var parentLi = $(this).parent();
        parentLi.toggleClass('open');
        parentLi.find('.submenu').slideToggle();
    });

    // Submenu active state logic based on URL can be handled via PHP, 
    // but we can ensure submenus are open if their child is active
    $('.submenu li.active').closest('.has-submenu').addClass('open');
    $('.submenu li.active').closest('.has-submenu').find('.submenu').show();
});

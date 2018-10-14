let isTouchScreenDevice = false;
let isSmallScreen = false;

$(window).on('load', function () {
    if ("ontouchstart" in document.documentElement) {
        isTouchScreenDevice = true;
    }
    if ($(window).width() <= 800) {
        isSmallScreen = true;
    }

    if (isSmallScreen) {
        let $sidebar = $('#sidebar'),
            openSideBar = function () {
                $sidebar.addClass('active');
                $('body').addClass('overflow-hidden');
            },
            closeSidebar = function () {
                $sidebar.removeClass('active');
                $('body').removeClass('overflow-hidden');
            };

        $sidebar.css('visibility', 'visible')
        $('#openSidebar').on('click', openSideBar);
        $('#closeSidebar').on('click', closeSidebar);

        // Behaviors for TOUCH device only
        if (isTouchScreenDevice) {
            $sidebar.swipe({
                swipeLeft: function (event, direction, distance, duration, fingerCount) {
                    if (direction == "left" && distance > 75) {
                        closeSidebar();
                        return false;
                    }
                }
            });
        }
    }

    // Behaviors for NON-TOUCH device only
    if (!isTouchScreenDevice) {
        // Touch device have nice scroll bar, we don't handle it.
        $("#sidebar").mCustomScrollbar({
            theme: "minimal",
            contentTouchScroll: 0,
            documentTouchScroll: 0
        });
    }


    // Add "sticky" mode only if html content height is X more high than window height, X = 250px;
    $(window).scroll(function () {
        if ($(document).height() - $(window).height() > 200) {
            let $header = $('#header'),
                $main = $('#main'),
                scrollTop = $(this).scrollTop();

            if (scrollTop > 20) {
                $header.addClass('sticky');
                $main.addClass('under-sticky');
            } else {
                $header.removeClass('sticky');
                $main.removeClass('under-sticky');
            }
        }
    });
});



let isTouchScreenDevice = false;
let isSmallScreen = false;
// Delta in px between Viewport Height and Full Height
let deltaWpAndFh = 250;

$(window).on('load', function () {
    let $sidebar = $('#sidebar'),
        sidebarWidth = $sidebar.width();

    if ("ontouchstart" in document.documentElement) {
        isTouchScreenDevice = true;
    }
    if ($(window).width() <= 800) {
        isSmallScreen = true;
    }

    if (isSmallScreen) {
        let openSideBar = function () {
                $sidebar.addClass('active');
                $('body').addClass('overflow-hidden');
            },
            closeSidebar = function () {
                $sidebar.removeClass('active');
                $('body').removeClass('overflow-hidden');
            };

        $sidebar.css('visibility', 'visible');
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
        $("#sidebarMenu").mCustomScrollbar({
            theme: "minimal",
            contentTouchScroll: 0,
            documentTouchScroll: 0
        });

        // Detect width of sidebar and set it as CSS property (and for search form too)
        // $sidebar.css('width', sidebarWidth);
        // $sidebar.find('#searchform-inline').css('width', sidebarWidth);
    }

    // Add "sticky" mode only if html content height is X more high than window height, X = 250px;
    // @see https://gist.github.com/toshimaru/6102647
    $.fn.isInViewport = function () {
        let elementTop = $(this).offset().top;
        let elementBottom = elementTop + $(this).outerHeight();

        let viewportTop = $(window).scrollTop();
        let viewportBottom = viewportTop + $(window).height();

        return elementBottom > viewportTop && elementTop < viewportBottom;
    };

    let docHeight = $(document).height(),
        $header = $('#header'),
        $main = $('#main'),
        $footer = $('#footer');

    let handleScroll = function () {
        if (docHeight - $(window).height() > deltaWpAndFh) {
            let scrollTop = $(window).scrollTop();

            // Handle when start to scrolling from top
            if (scrollTop > 40) {
                $header.addClass('sticky');
                $main.addClass('under-sticky');
            } else {
                $header.removeClass('sticky');
                $main.removeClass('under-sticky');
            }

            // Handle when we scrolling close to the footer
            if ($footer.isInViewport()) {
                // $sidebar.addClass('absolute-sidebar');
            } else {
                // $sidebar.removeClass('absolute-sidebar');
            }
        }
    };

    // After windows loading : handle scroll
    handleScroll();

    $(window).scroll(handleScroll);
});



document.addEventListener('DOMContentLoaded', function () {

    // Get all "navbar-burger" elements
    var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

    var isTouchScreenDevice = false;
    var isSmallScreen = false;
    // Delta in px between Viewport Height and Full Height
    var deltaWpAndFh = 250;
    var $header = $('#header');
    var $headerTop = $('.topbar', $header);
    var headerTopHeight = $headerTop.height();
    var $sidebar = $('#sidebar');
    var sidebarWidth = $sidebar.width();

    // Check if there are any navbar burgers
    if ($navbarBurgers.length > 0) {

        // Add a click event on each of them
        $navbarBurgers.forEach(function ($el) {
            $el.addEventListener('click', function () {

                // Get the target from the "data-target" attribute
                var target = $el.dataset.target;
                var $target = document.getElementById(target);

                // Toggle the class on both the "navbar-burger" and the "navbar-menu"
                $el.classList.toggle('is-active');
                $target.classList.toggle('is-active');

            });
        });
    }

    $('.notification > button.delete').on('click', function (e) {
        $(this).parent().hide();
    });


    if ("ontouchstart" in document.documentElement) {
        isTouchScreenDevice = true;
    }
    if ($(window).width() <= 800) {
        isSmallScreen = true;
    }

    if (isSmallScreen) {
        var openSideBar = function () {
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

    // Add "sticky" mode only if html content height is X more high than window height, X = 250px;
    // @see https://gist.github.com/toshimaru/6102647
    $.fn.isInViewport = function () {
        var elementTop = $(this).offset().top;
        var elementBottom = elementTop + $(this).outerHeight();

        var viewportTop = $(window).scrollTop();
        var viewportBottom = viewportTop + $(window).height();

        return elementBottom > viewportTop && elementTop < viewportBottom;
    };

    var docHeight = $(document).height(),
        $main = $('#main-content');

    var handleScroll = function () {
        if (docHeight - $(window).height() > deltaWpAndFh) {
            var scrollTop = $(window).scrollTop();

            // Handle when start to scrolling from top
            if (scrollTop > headerTopHeight) {
                $header.addClass('sticky');
                $main.addClass('under-sticky');
            } else {
                $header.removeClass('sticky');
                $main.removeClass('under-sticky');
            }
        }
    };

    // After windows loading : handle scroll
    handleScroll();

    $(window).scroll(handleScroll);
});

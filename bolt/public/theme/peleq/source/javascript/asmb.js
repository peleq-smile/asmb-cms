$(document).ready(function($) {
    /** Gestion du toggle sur le message d'alerte de l'accueil **/
    $('.static-alert-container h2').on('click', function() {
        $(this).parent().toggleClass('visible');
    });

    /** Déclaration des fonctions **/
    // Initialisation du JS commun de navigation du site ASMB
    function initAsmbCommonNavJs() {
        // Get all "navbar-burger" elements
        var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

        var isTouchScreenDevice = false;
        var isSmallScreen = false;

        // Delta in px between Viewport Height and Full Height
        var deltaWpAndFh = 250;
        var $header = $('#header');
        var $headerTop = $('.topbar', $header);
        var headerTopHeight = $headerTop.height();

        var $navbarMenu = $('#navbar-menu');

        $('.notification > button.delete').on('click', function () {
            $(this).parent().hide();
        });


        if ("ontouchstart" in document.documentElement) {
            isTouchScreenDevice = true;
        }
        if ($(window).width() <= 1088) {
            isSmallScreen = true;
        }

        if (isSmallScreen) {
            // Behaviors for TOUCH device only
            if (isTouchScreenDevice) {

                // Handle swipe to right event when navbar menu is active (= opened)
                $navbarMenu.swipe({
                    swipeRight: function (event, direction, distance, duration, fingerCount) {
                        if (direction == "right" && distance > 75) {
                            $navbarMenu.removeClass('is-active');
                            return false;
                        }
                    }
                });

                // Handle dropdown of navbar menu
                $navbarMenu.find('.has-dropdown .navbar-link').on('click', function () {
                    var $clickedItem = $(this),
                        isActive = $clickedItem.next('.navbar-dropdown.is-active').length; // if entry active, will return 1
                    if (isActive) {
                        // Entry is active, so let's close ALL entries
                        $clickedItem.removeClass('is-active');
                        $clickedItem.next('.navbar-dropdown').removeClass('is-active');
                    } else {
                        var $othersActive = $navbarMenu.find('.navbar-dropdown.is-active');
                        // Another entry is active, let's check if this is a parent of item to active

                        $othersActive.each(function () {
                            var isParentActive = $(this).find($clickedItem).length;
                            if (isParentActive == 0) {
                                $(this).removeClass('is-active');
                                $(this).prev('.navbar-link').removeClass('is-active');
                            }
                        });

                        $clickedItem.addClass('is-active');
                        $clickedItem.next('.navbar-dropdown').addClass('is-active');
                    }

                    return false;
                });
            }
        }

        // Check if there are any navbar burgers
        if ($navbarBurgers.length > 0) {
            // Add a click event on each of them
            $navbarBurgers.forEach(function ($el) {
                $el.addEventListener('click', function () {
                    // Get the target from the "data-target" attribute
                    var targetId = $el.dataset.target;
                    var target = document.getElementById(targetId);

                    // Toggle the class on both the "navbar-burger" and the "navbar-menu"
                    target.classList.toggle('is-active');

                    // And collapse all entries if active
                    $(target).find('.navbar-dropdown.is-active').removeClass('is-active');
                    $(target).find('.navbar-link.is-active').removeClass('is-active');
                });
            });
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

        var $scrollup = $('.scrollup'),
            $scrollto = $('.scrollto'),
            $targetElt = $($scrollto.data('target')),
            gapBeforeTargetElt = isTouchScreenDevice ? 0 : 110;

        if ($targetElt.length > 0) {
            var targetEltTop = $targetElt.offset().top,
                targetEltBottom = targetEltTop + $targetElt.height();
        }

        var handleScroll = function () {
            if (docHeight - $(window).height() > deltaWpAndFh) {
                var scrollTop = $(window).scrollTop();

                // Gestion de l'en-tête "sticky"
                if (scrollTop > headerTopHeight) {
                    $header.addClass('sticky');
                    $main.addClass('under-sticky');
                } else {
                    $header.removeClass('sticky');
                    $main.removeClass('under-sticky');
                }

                // Gestion du raccourci "Scroll to Top"
                if (scrollTop > docHeight / 4) {
                    $scrollup.fadeIn();
                } else {
                    $scrollup.fadeOut();
                }

                // Gestion du raccourci générique "Go to"
                if ($targetElt.length > 0) {
                    if (scrollTop > headerTopHeight && (targetEltBottom < scrollTop || targetEltTop > (scrollTop + $(window).height()))) {
                        // Le bloc visé n'est pas à l'écran => on affiche le lien de raccourci 'scroll to'
                        $scrollto.fadeIn();
                    } else {
                        // Le bloc visé est à l'écran, on cache le lien
                        $scrollto.fadeOut();
                    }
                }
            }
        };

        var handleStopAnimation = function () {
            $('html, body').bind('scroll mousedown DOMMouseScroll mousewheel keyup', function () {
                $('html, body').stop();
            });
        };

        // On "bind" la gestion du scroll sur l'événement scroll de la fenêtre
        $(window).scroll(handleScroll);
        $(window).scroll(handleStopAnimation);

        $scrollup.on('click', function () {
            $('html, body').animate({scrollTop: 0}, 500);
            return false;
        });

        if ($targetElt.length > 0) {
            $scrollto.on('click', function () {
                $('html, body').animate({scrollTop: (targetEltTop - gapBeforeTargetElt)}, 500);
                return false;
            });
        }
    }

    // JS pour les tournois
    function handleTournamentJsNav($sectionTournament) {
        var $plaPart = $('.planning-container', $sectionTournament), // PLANNING
            $jouPart = $('.players-container', $sectionTournament), // JOUEURS
            $tabPart = $('.tables-container', $sectionTournament), // TABLEAUX
            $resPart = $('.results-container', $sectionTournament), // RESULTATS
            $unJouParts = $('.one-player-container', $sectionTournament); // UN JOUEUR

        function updateView(hash, param = null) {
            switch (hash) {
                case '#pla':
                    // On retire le flag 'is-active' du sous-menu
                    $('.planning-nav a', $sectionTournament).removeClass('is-active');

                    // Affichage du jour approprié
                    if (param != null && param != '-') {
                        $('.planning-day', $plaPart).hide();
                        $('#planning-day-' + param, $plaPart).show();

                        // On met à jour le select du menu principal du tournoi
                        $('#select-planning option[value="' + param + '"]', $sectionTournament).prop('selected', true);
                        // On met à jour le flag 'is-active' du sous-menu
                        $('.planning-nav a[data-param="' + param + '"]', $sectionTournament).addClass('is-active');
                    } else {
                        // On met à jour le select du menu principal du tournoi
                        $('#select-planning option[value="-"]', $sectionTournament).prop('selected', true);
                        // Pas de paramètre : on montre tout le planning !
                        $('.planning-day', $plaPart).show();
                    }

                    $plaPart.show();
                    $jouPart.hide();
                    $tabPart.hide();
                    $resPart.hide();
                    $unJouParts.hide();

                    // On reset la navigation principale
                    $('#select-table option[value="-"]', $sectionTournament).prop('selected', true);
                    break;
                case '#jou':
                    $plaPart.hide();
                    $tabPart.hide();
                    $resPart.hide();
                    $jouPart.hide();
                    $unJouParts.hide();

                    if (param != null) {
                        $('#jou-' + param).show();
                    } else {
                        $jouPart.show();
                    }

                    // On reset la navigation principale
                    $('#select-table option[value="-"]', $sectionTournament).prop('selected', true);
                    $('#select-planning option[value="-"]', $sectionTournament).prop('selected', true);
                    break;
                case '#tab':
                    if (param != null && param != '-') {
                        $plaPart.hide();
                        $jouPart.hide();
                        $tabPart.hide(); // On masque tout avant de ne montrer que le tableau sélectionné
                        $resPart.hide();
                        $unJouParts.hide();
                        $tabPart.filter('[id="' + param + '"]').show();

                        // On reset la navigation principale
                        $('#select-planning option[value="-"]', $sectionTournament).prop('selected', true);
                    }
                    break;
                default:
                    $plaPart.hide();
                    $jouPart.hide();
                    $tabPart.hide();
                    $unJouParts.hide();
                    $resPart.show();

                    // On reset la navigation principale
                    $('#select-table option[value="-"]', $sectionTournament).prop('selected', true);
                    $('#select-planning option[value="-"]', $sectionTournament).prop('selected', true);
                    break;
            }

            return false;
        }

        // Au chargement de la page, on va cherche le hash dans l'url
        var hash = $(location).attr('hash'), param = null;
        if (hash) {
            // Gérer le cas des paramètres dans l'URL
            if (hash.length > 4) {
                param = hash.substring(5);
                hash = hash.substring(0, 4);
            }
            updateView(hash, param);
        }

        // Puis on gère les évènements de navigation pour mettre à jour la vue
        // -- Clic sur un lien de la navigation principale
        $('nav a', $sectionTournament).on('click', function (event) {
            var hash = $(event.target).data('hash');
            updateView(hash);
        });

        // -- Sélection dans un des menus du la navigation principale
        $('nav select', $sectionTournament).on('change', function (event) {
            var target = $(event.target),
                hash = target.data('hash'),
                param = target.find('option:selected').val();

            updateView(hash, param);

            // Mise à jour du hash de l'url
            window.location.hash = hash + ":" + param;
        });

        // -- Clic sur un lien de la sous-navigation "planning"
        $('.planning-nav a', $sectionTournament).on('click', function (event) {
            var target = $(event.target),
                hash = target.data('hash'),
                param = target.data('param');

            updateView(hash, param);
        });

        // Clic sur un lien vers un joueur
        $('a.player-link', $sectionTournament).on('click', function (event) {
            var target = $(event.target),
                hash = target.data('hash'),
                param = target.data('param');

            updateView(hash, param);
            $('html, body').animate({scrollTop: $sectionTournament.offset().top}, 200);
        });

        // Gestion des retours arrière/en avant avec le navigateur
        window.onpopstate = function(event) {
            var hash = $(location).attr('hash'), param = null;
            if (hash) {
                // Gérer le cas des paramètres dans l'URL
                if (hash.length > 4) {
                    param = hash.substring(5);
                    hash = hash.substring(0, 4);
                }
                updateView(hash, param);
            }
        };
    }

    /** Appels des fonctions**/
    initAsmbCommonNavJs();

    let $sectionTournament = $('#main-content .section-tournament');
    if ($sectionTournament.length) {
        handleTournamentJsNav($sectionTournament);
    }
});
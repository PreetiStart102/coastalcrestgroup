(function ($) {
    "use strict";

    // Spinner - instant hide
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);

    // Initiate WOW.js - zero-lag fast trigger
    if (typeof WOW === 'function') {
        new WOW({
            boxClass: 'wow',
            animateClass: 'animated',
            offset: 10,
            mobile: true,
            live: true
        }).init();
    }

    // Auto-cycle carousel
    var heroCarousel = document.getElementById('carouselId');
    if (heroCarousel && typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
        new bootstrap.Carousel(heroCarousel, {
            interval: 4500,
            ride: 'carousel',
            pause: false,
            wrap: true
        });
    }

    // Sticky Navbar handling
    $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
            $('.sticky-top').addClass('shadow-sm').css('top', '0px');
        } else {
            $('.sticky-top').removeClass('shadow-sm');
        }
    });

    // Navigation Dropdown Management (Desktop hover vs Mobile tap)
    function setupNavDropdowns() {
        if (window.matchMedia('(min-width: 992px)').matches) {
            // Remove mobile click overrides when on desktop
            $('.navbar .dropdown-toggle').off('click.mobileDropdown');

            // Close any lingering dropdowns whenever mouse moves across any nav item
            $('.navbar-nav .nav-item').off('mouseenter').on('mouseenter', function () {
                $(this).siblings().removeClass('show')
                    .find('.dropdown-menu, .dropdown-mega-menu').removeClass('show');
                $(this).siblings().find('.nav-link').attr('aria-expanded', 'false');
            });

            // Specific hover management for dropdown items
            $('.navbar .nav-item.dropdown, .navbar .nav-item.dropdown-mega').off('mouseenter mouseleave').hover(
                function () {
                    $(this).siblings().removeClass('show')
                        .find('.dropdown-menu, .dropdown-mega-menu').removeClass('show');
                    $(this).siblings().find('.nav-link').attr('aria-expanded', 'false');
                    
                    $(this).addClass('show');
                    $(this).find('> .dropdown-menu, > .dropdown-mega-menu').addClass('show');
                    $(this).find('> .nav-link').attr('aria-expanded', 'true');
                },
                function () {
                    $(this).removeClass('show');
                    $(this).find('> .dropdown-menu, > .dropdown-mega-menu').removeClass('show');
                    $(this).find('> .nav-link').attr('aria-expanded', 'false');
                }
            );

            // Clear all open dropdowns when mouse leaves the navbar area completely
            $('.navbar-nav').off('mouseleave').on('mouseleave', function () {
                $(this).find('.nav-item, .dropdown, .dropdown-mega').removeClass('show');
                $(this).find('.dropdown-menu, .dropdown-mega-menu').removeClass('show');
                $(this).find('.nav-link').attr('aria-expanded', 'false');
            });

            $('.navbar .nav-link.dropdown-toggle').off('click.desktopNav').on('click.desktopNav', function (e) {
                if (window.matchMedia('(min-width: 992px)').matches) {
                    var href = $(this).attr('href');
                    if (href && href !== '#' && href !== 'javascript:;') {
                        window.location.href = href;
                    }
                }
            });
        } else {
            // Mobile (max-width: 991.98px)
            $('.navbar-nav .nav-item').off('mouseenter mouseleave');
            $('.navbar-nav').off('mouseleave');
            $('.navbar .nav-link.dropdown-toggle').off('click.desktopNav');

            // On mobile, tapping a dropdown toggle smoothly closes any open sibling dropdowns
            $('.navbar .dropdown-toggle').off('click.mobileDropdown').on('click.mobileDropdown', function () {
                var $parent = $(this).closest('.nav-item');
                $parent.siblings('.dropdown, .dropdown-mega').removeClass('show')
                    .find('.dropdown-menu, .dropdown-mega-menu').removeClass('show');
                $parent.siblings().find('.dropdown-toggle').attr('aria-expanded', 'false');
            });
        }
    }
    setupNavDropdowns();
    $(window).on('resize', setupNavDropdowns);

    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });

    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 800, 'easeInOutExpo');
        return false;
    });

    // Smooth scroll for anchor links on same page
    $(document).on('click', 'a[href^="#"]:not([data-bs-toggle])', function (event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            var navHeight = $('.sticky-top').outerHeight() || 80;
            $('html, body').stop().animate({
                scrollTop: target.offset().top - navHeight + 10
            }, 700, 'easeInOutExpo');

            // Close mobile menu if open
            if ($('#navbarCollapse').hasClass('show')) {
                $('#navbarCollapse').collapse('hide');
            }
        }
    });

    // Scroll Reveal Animation (IntersectionObserver Engine - pre-triggers 50px before entering viewport)
    function initScrollReveal() {
        var reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px 50px 0px',
                threshold: 0.01
            });

            reveals.forEach(function(el) {
                observer.observe(el);
            });
        } else {
            // Fallback for older browsers
            function revealOnScroll() {
                var windowHeight = window.innerHeight;
                reveals.forEach(function(el) {
                    var elementTop = el.getBoundingClientRect().top;
                    if (elementTop < windowHeight + 50) {
                        el.classList.add('revealed');
                    }
                });
            }
            window.addEventListener('scroll', revealOnScroll, { passive: true });
            revealOnScroll();
        }
    }
    initScrollReveal();

    // Counter animation for stat numbers (IntersectionObserver powered)
    function animateCounters() {
        var counters = document.querySelectorAll('.counter-value');
        if (!counters.length) return;

        function startCounter(counter) {
            if (counter.dataset.animated) return;
            counter.dataset.animated = 'true';
            var target = parseInt(counter.getAttribute('data-count'));
            var suffix = counter.getAttribute('data-suffix') || '';
            var prefix = counter.getAttribute('data-prefix') || '';
            var duration = 1600;
            var startTime = null;
            function step(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var easeOut = 1 - Math.pow(1 - progress, 3);
                var current = Math.floor(easeOut * target);
                counter.textContent = prefix + current + suffix;
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    counter.textContent = prefix + target + suffix;
                }
            }
            requestAnimationFrame(step);
        }

        if ('IntersectionObserver' in window) {
            var counterObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        startCounter(entry.target);
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -20px 0px', threshold: 0.1 });

            counters.forEach(function(c) { counterObserver.observe(c); });
        } else {
            counters.forEach(function(c) { startCounter(c); });
        }
    }
    animateCounters();

    // Circle progress ring stats animation
    function initCircleProgressStats() {
        var circleCards = document.querySelectorAll('.circle-stat-card');
        if (!circleCards.length) return;

        if ('IntersectionObserver' in window) {
            var circleObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var card = entry.target;
                        var circle = card.querySelector('.circle-progress-bar');
                        if (circle && !card.dataset.animated) {
                            card.dataset.animated = 'true';
                            var percent = parseInt(card.getAttribute('data-percent')) || 80;
                            var maxDash = 314;
                            var offset = maxDash - (maxDash * (percent / 100));
                            circle.style.strokeDashoffset = offset;
                        }
                    }
                });
            }, { threshold: 0.2 });

            circleCards.forEach(function(card) {
                circleObserver.observe(card);
            });
        }
    }
    initCircleProgressStats();

    // Stacked Cards dynamic scroll scale and dimming effect with requestAnimationFrame
    function initStackedCardsAnimation() {
        var stackedCards = document.querySelectorAll('.stacked-card');
        if (!stackedCards.length) return;

        var ticking = false;

        function updateStackedCards() {
            stackedCards.forEach(function(card, index) {
                var nextCard = stackedCards[index + 1];
                if (nextCard) {
                    var nextRect = nextCard.getBoundingClientRect();
                    var stickyTop = 90 + (index * 18);
                    if (nextRect.top <= stickyTop + 160) {
                        var progress = Math.max(0, Math.min(1, (stickyTop + 160 - nextRect.top) / 160));
                        var scale = 1 - (progress * 0.04);
                        var opacity = 1 - (progress * 0.18);
                        card.style.transform = 'scale(' + scale + ') translateZ(0)';
                        card.style.opacity = opacity.toFixed(3);
                    } else {
                        card.style.transform = 'scale(1) translateZ(0)';
                        card.style.opacity = '1';
                    }
                }
            });
            ticking = false;
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(updateStackedCards);
                ticking = true;
            }
        }, { passive: true });

        updateStackedCards();
    }
    initStackedCardsAnimation();

    // Career Application Form submission handler
    $('#careerForm').on('submit', function (e) {
        e.preventDefault();
        $('#careerSuccessMsg').removeClass('d-none').hide().fadeIn();
        this.reset();
        setTimeout(function() {
            var modalEl = document.getElementById('careerModal');
            if (modalEl) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        }, 2000);
    });

    // Contact Form handler
    $('#contactForm').on('submit', function (e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...').prop('disabled', true);
        setTimeout(function() {
            btn.html('<i class="fas fa-check me-2"></i>Sent Successfully!').removeClass('btn-secondary').addClass('btn-success');
            setTimeout(function() {
                btn.html('<i class="fas fa-paper-plane me-2"></i>Send Message').removeClass('btn-success').addClass('btn-secondary').prop('disabled', false);
            }, 3000);
        }, 1500);
        this.reset();
    });

})(jQuery);


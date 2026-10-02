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

    // Initiate WOW.js
    if (typeof WOW === 'function') {
        new WOW({
            boxClass: 'wow',
            animateClass: 'animated',
            offset: 80,
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

    // Scroll Reveal Animation (IntersectionObserver Engine)
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
                rootMargin: '0px 0px -40px 0px',
                threshold: 0.05
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
                    if (elementTop < windowHeight - 40) {
                        el.classList.add('revealed');
                    }
                });
            }
            window.addEventListener('scroll', revealOnScroll);
            revealOnScroll();
        }
    }
    initScrollReveal();

    // Counter animation for stat numbers
    function animateCounters() {
        var counters = document.querySelectorAll('.counter-value');
        counters.forEach(function(counter) {
            if (counter.dataset.animated) return;
            var rect = counter.getBoundingClientRect();
            if (rect.top < window.innerHeight - 50) {
                counter.dataset.animated = 'true';
                var target = parseInt(counter.getAttribute('data-count'));
                var suffix = counter.getAttribute('data-suffix') || '';
                var prefix = counter.getAttribute('data-prefix') || '';
                var duration = 1800;
                var start = 0;
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
        });
    }
    window.addEventListener('scroll', animateCounters);
    animateCounters();

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

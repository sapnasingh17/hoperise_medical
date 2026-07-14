$(document).ready(function() {
    // 1. Initialize AOS (Animate On Scroll)
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out-cubic'
        });
    }

    // 2. Statistics Counter Animation
    const counterElements = document.querySelectorAll('.stat-counter');
    
    if (counterElements.length > 0) {
        const countUp = (element) => {
            const target = +element.getAttribute('data-target');
            const duration = 2000; // 2 seconds
            const stepTime = 30; // ms per step
            const totalSteps = duration / stepTime;
            const increment = target / totalSteps;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target.toLocaleString() + (element.getAttribute('data-suffix') || '');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current).toLocaleString() + (element.getAttribute('data-suffix') || '');
                }
            }, stepTime);
        };

        // Use IntersectionObserver to animate only when the achievements section is scrolled into view
        const observerOptions = {
            threshold: 0.2
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stat-counter');
                    counters.forEach(counter => {
                        // Prevent re-triggering
                        if (!counter.classList.contains('counted')) {
                            counter.classList.add('counted');
                            countUp(counter);
                        }
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const achievementsSection = document.querySelector('.achievements-section');
        if (achievementsSection) {
            observer.observe(achievementsSection);
        }
    }

    // 3. Testimonials Slick Slider
    const $testimonialSlider = $('.testimonial-slider');
    if ($testimonialSlider.length > 0 && typeof $.fn.slick !== 'undefined') {
        $testimonialSlider.slick({
            dots: true,
            arrows: false,
            autoplay: true,
            autoplaySpeed: 4000,
            infinite: true,
            speed: 600,
            slidesToShow: 2,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }

    // 4. Smooth Anchor Scroll for Call to Action buttons
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });
});

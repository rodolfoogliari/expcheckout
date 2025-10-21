// Global Swiper Configuration with Overflow Fix
document.addEventListener('DOMContentLoaded', function() {
    // Prevent horizontal overflow on page load
    document.body.style.overflowX = 'hidden';
    document.documentElement.style.overflowX = 'hidden';
    
    // Default configuration for all swipers
    const defaultSwiperConfig = {
        watchOverflow: true,
        watchSlidesProgress: true,
        centeredSlides: true,
        observer: true,
        observeParents: true,
        on: {
            init: function() {
                // Ensure no horizontal scroll on init
                document.body.style.overflowX = 'hidden';
                document.documentElement.style.overflowX = 'hidden';
            },
            slideChange: function() {
                // Maintain overflow hidden on slide change
                document.body.style.overflowX = 'hidden';
            }
        }
    };
    
    // Initialize all swiper instances
    const swiperSelectors = [
        '.swiper-container',
        '.swiper',
        '.teachers',
        '.testimonials-slider',
        '.hero-slider',
        '.gallery-slider'
    ];
    
    swiperSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            // Skip if already initialized
            if (element.swiper) return;
            
            // Merge default config with any data attributes
            const config = {
                ...defaultSwiperConfig,
                slidesPerView: element.dataset.slidesPerView || 1,
                spaceBetween: parseInt(element.dataset.spaceBetween) || 30,
                loop: element.dataset.loop !== 'false',
                autoplay: element.dataset.autoplay !== 'false' ? {
                    delay: parseInt(element.dataset.autoplayDelay) || 5000,
                    disableOnInteraction: false,
                } : false,
                pagination: {
                    el: element.querySelector('.swiper-pagination'),
                    clickable: true,
                    dynamicBullets: true
                },
                navigation: {
                    nextEl: element.querySelector('.swiper-button-next'),
                    prevEl: element.querySelector('.swiper-button-prev'),
                },
                breakpoints: {
                    320: {
                        slidesPerView: 1,
                        spaceBetween: 20
                    },
                    768: {
                        slidesPerView: element.dataset.slidesPerViewMd || 1,
                        spaceBetween: 30
                    },
                    1024: {
                        slidesPerView: element.dataset.slidesPerViewLg || 1,
                        spaceBetween: 40
                    }
                }
            };
            
            // Initialize swiper
            new Swiper(element, config);
        });
    });
});

// Additional safety: Fix overflow on resize
window.addEventListener('resize', function() {
    document.body.style.overflowX = 'hidden';
    document.documentElement.style.overflowX = 'hidden';
});

// Fix overflow on orientation change (mobile)
window.addEventListener('orientationchange', function() {
    setTimeout(function() {
        document.body.style.overflowX = 'hidden';
        document.documentElement.style.overflowX = 'hidden';
    }, 100);
});
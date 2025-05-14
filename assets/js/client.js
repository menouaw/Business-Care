document.addEventListener('DOMContentLoaded', function() {
    
    const testimonialCarousel = document.getElementById('testimonialCarousel');
    if (testimonialCarousel) {
        try {
            
            testimonialCarousel.addEventListener('mouseenter', function() {
                const instance = bootstrap.Carousel.getInstance(testimonialCarousel);
                if (instance) {
                    instance.pause();
                }
            });

            
            testimonialCarousel.addEventListener('mouseleave', function() {
                const instance = bootstrap.Carousel.getInstance(testimonialCarousel);
                if (instance) {
                    instance.cycle();
                }
            });
        } catch (error) {
            
            console.error("Error initializing carousel listeners:", error);
        }
    }
    
});

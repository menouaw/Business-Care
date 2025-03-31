document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des tooltips bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialisation des popovers bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Effet de défilement fluide pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Animation au défilement
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.classList.add('animated');
            }
        });
    };
    
    // Exécuter une fois au chargement
    animateOnScroll();
    
    // Puis à chaque défilement
    window.addEventListener('scroll', animateOnScroll);
    
    // Gestion du bouton "Retour en haut"
    const backToTopButton = document.getElementById('back-to-top');
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Validation des formulaires
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Gestion du carousel de témoignages
    const testimonialCarousel = document.getElementById('testimonialCarousel');
    if (testimonialCarousel) {
        // Arrêter le carousel au survol
        testimonialCarousel.addEventListener('mouseenter', function() {
            bootstrap.Carousel.getInstance(testimonialCarousel).pause();
        });
        
        testimonialCarousel.addEventListener('mouseleave', function() {
            bootstrap.Carousel.getInstance(testimonialCarousel).cycle();
        });
    }
    
    // Animation des cartes de service au survol
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('service-card-hover');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('service-card-hover');
        });
    });
    
    // Animation des cartes de tarification au survol
    const pricingCards = document.querySelectorAll('.pricing-card');
    pricingCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('border-primary')) {
                this.classList.add('pricing-card-hover');
            }
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('pricing-card-hover');
        });
    });
    
    // Gestion de la barre de navigation fixe
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    }
    
    // Basculer entre l'affichage et le masquage du mot de passe (page login)
    const togglePasswordButton = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    
    if (togglePasswordButton && passwordField) {
        togglePasswordButton.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Changer l'icône
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
});
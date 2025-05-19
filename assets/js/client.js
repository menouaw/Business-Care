document.addEventListener('DOMContentLoaded', function () {

    const testimonialCarousel = document.getElementById('testimonialCarousel');
    if (testimonialCarousel) {
        try {

            testimonialCarousel.addEventListener('mouseenter', function () {
                const instance = bootstrap.Carousel.getInstance(testimonialCarousel);
                if (instance) {
                    instance.pause();
                }
            });


            testimonialCarousel.addEventListener('mouseleave', function () {
                const instance = bootstrap.Carousel.getInstance(testimonialCarousel);
                if (instance) {
                    instance.cycle();
                }
            });
        } catch (error) {

            console.error("Error initializing carousel listeners:", error);
        }
    }

    const quoteForm = document.getElementById('quote-request-form');
    const personalizeSection = document.getElementById('personalize-section');
    const quoteTotalDisplayElement = document.getElementById('quote-total-display');
    const quoteTypeSelectors = document.querySelectorAll('.quote-type-selector');
    const requestTypeHiddenInput = document.getElementById('request_type_hidden');
    const selectedPackIdHiddenInput = document.getElementById('selected_pack_id_hidden');
    const selectedPackInfoElement = document.getElementById('selected-pack-info');

    if (personalizeSection && quoteTotalDisplayElement && quoteTypeSelectors.length > 0 && requestTypeHiddenInput && selectedPackIdHiddenInput && selectedPackInfoElement) {
        const quantityInputs = personalizeSection.querySelectorAll('.quote-quantity-input');
        let selectedPackPrice = 0;
        let selectedPackName = "Aucun";

        function calculateAndDisplayTotal() {
            if (!quoteTotalDisplayElement || !selectedPackInfoElement) return;

            let extrasTotal = 0;
            quantityInputs.forEach(input => {
                const quantity = parseInt(input.value, 10) || 0;
                const price = parseFloat(input.dataset.price) || 0;
                if (quantity > 0 && price > 0) {
                    extrasTotal += quantity * price;
                }
            });

            const grandTotal = selectedPackPrice + extrasTotal;

            selectedPackInfoElement.innerHTML = `<small>Base: ${selectedPackName}</small>`;

            quoteTotalDisplayElement.textContent = grandTotal.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
        }

        personalizeSection.style.display = 'block';
        quantityInputs.forEach(input => input.disabled = false);

        quoteTypeSelectors.forEach(radio => {
            radio.addEventListener('change', function (event) {
                const choice = event.target.value;
                const selectedRadio = event.target;

                if (choice.startsWith('pack_')) {
                    requestTypeHiddenInput.value = 'pack';
                    selectedPackIdHiddenInput.value = choice.replace('pack_', '');
                    selectedPackPrice = parseFloat(selectedRadio.dataset.packPrice) || 0;
                    selectedPackName = selectedRadio.dataset.packName || "Inconnu";
                } else {
                    requestTypeHiddenInput.value = 'personalize';
                    selectedPackIdHiddenInput.value = '';
                    selectedPackPrice = 0;
                    selectedPackName = "Sur mesure";
                }
                calculateAndDisplayTotal();
            });
        });

        quantityInputs.forEach(input => {
            input.addEventListener('input', calculateAndDisplayTotal);
        });

        const checkedRadio = document.querySelector('.quote-type-selector:checked');
        if (checkedRadio) {
            if (checkedRadio.value.startsWith('pack_')) {
                requestTypeHiddenInput.value = 'pack';
                selectedPackIdHiddenInput.value = checkedRadio.value.replace('pack_', '');
                selectedPackPrice = parseFloat(checkedRadio.dataset.packPrice) || 0;
                selectedPackName = checkedRadio.dataset.packName || "Inconnu";
            } else {
                requestTypeHiddenInput.value = 'personalize';
                selectedPackIdHiddenInput.value = '';
                selectedPackPrice = 0;
                selectedPackName = "Sur mesure";
            }
        } else {
            requestTypeHiddenInput.value = 'personalize';
            selectedPackIdHiddenInput.value = '';
            selectedPackPrice = 0;
            selectedPackName = "Sur mesure";
        }

        calculateAndDisplayTotal();
    }
});
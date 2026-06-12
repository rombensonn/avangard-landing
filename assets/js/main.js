(function () {
    var chips = Array.prototype.slice.call(document.querySelectorAll('[data-service]'));
    var callSection = document.getElementById('call');
    var serviceHint = document.querySelector('[data-service-hint]');
    var primaryCall = document.querySelector('[data-primary-call]');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var mobileViewport = window.matchMedia('(max-width: 760px)');
    var hoverDevice = window.matchMedia('(hover: hover)');

    function animationsAllowed() {
        return !reduceMotion && !mobileViewport.matches && hoverDevice.matches;
    }

    function updateServiceHint(service) {
        if (!serviceHint || !service) {
            return;
        }

        serviceHint.textContent = 'Скажите администратору: «' + service + '». Добавьте марку авто, симптом, удобное время и вопрос по запчастям.';
    }

    chips.forEach(function (chip) {
        chip.setAttribute('aria-pressed', 'false');

        chip.addEventListener('click', function () {
            var service = chip.getAttribute('data-service') || '';

            chips.forEach(function (item) {
                item.classList.remove('is-selected');
                item.setAttribute('aria-pressed', 'false');
            });

            chip.classList.add('is-selected');
            chip.setAttribute('aria-pressed', 'true');
            updateServiceHint(service);

            if (callSection) {
                callSection.scrollIntoView({
                    behavior: animationsAllowed() ? 'smooth' : 'auto',
                    block: 'start'
                });
            }

            if (primaryCall) {
                window.setTimeout(function () {
                    try {
                        primaryCall.focus({ preventScroll: true });
                    } catch (error) {
                        primaryCall.focus();
                    }
                }, animationsAllowed() ? 260 : 0);
            }
        });
    });

    if (animationsAllowed()) {
        var glassItems = Array.prototype.slice.call(document.querySelectorAll(
            '.hero-panel, .service-card, .trust-card, .review-card, .phone-panel, .phone-card, .hours-card, .quick-chip, .amenities-list span, .process-visual, .rating-summary, .note-box'
        ));

        glassItems.forEach(function (item) {
            item.addEventListener('pointermove', function (event) {
                var rect = item.getBoundingClientRect();
                item.style.setProperty('--mx', Math.round(event.clientX - rect.left) + 'px');
                item.style.setProperty('--my', Math.round(event.clientY - rect.top) + 'px');
            });

            item.addEventListener('pointerleave', function () {
                item.style.removeProperty('--mx');
                item.style.removeProperty('--my');
            });
        });
    }
})();

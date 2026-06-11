(function () {
    var chips = Array.prototype.slice.call(document.querySelectorAll('[data-service]'));
    var serviceSelect = document.getElementById('service');
    var booking = document.getElementById('booking');
    var phoneInput = document.querySelector('input[name="phone"]');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var mobileViewport = window.matchMedia('(max-width: 760px)');
    var hoverDevice = window.matchMedia('(hover: hover)');

    function animationsAllowed() {
        return !reduceMotion && !mobileViewport.matches && hoverDevice.matches;
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var service = chip.getAttribute('data-service') || '';

            chips.forEach(function (item) {
                item.classList.remove('is-selected');
            });

            chip.classList.add('is-selected');

            if (serviceSelect && service) {
                var exists = Array.prototype.some.call(serviceSelect.options, function (option) {
                    return option.value === service;
                });

                if (!exists) {
                    var option = document.createElement('option');
                    option.value = service;
                    option.textContent = service;
                    serviceSelect.appendChild(option);
                }

                serviceSelect.value = service;
            }

            if (booking) {
                booking.scrollIntoView({
                    behavior: animationsAllowed() ? 'smooth' : 'auto',
                    block: 'start'
                });
            }

            if (phoneInput) {
                window.setTimeout(function () {
                    phoneInput.focus({ preventScroll: true });
                }, animationsAllowed() ? 260 : 0);
            }
        });
    });

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^\d()+\-\s]/g, '');
        });
    }

    var staticForm = document.querySelector('[data-static-form]');

    if (staticForm) {
        staticForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var oldMessage = staticForm.querySelector('[data-static-message]');
            if (oldMessage) {
                oldMessage.remove();
            }

            var message = document.createElement('div');
            message.className = 'form-message form-success';
            message.setAttribute('role', 'status');
            message.setAttribute('data-static-message', 'true');
            message.textContent = 'Это публичная демо-версия на GitHub Pages: заявка не отправляется на сервер. Для связи используйте телефон или маршрут в Яндекс.Картах.';
            staticForm.insertBefore(message, staticForm.firstElementChild.nextElementSibling);
        });
    }

    if (animationsAllowed()) {
        var glassItems = Array.prototype.slice.call(document.querySelectorAll(
            '.hero-panel, .service-card, .trust-card, .review-card, .lead-form, .hours-card, .quick-chip, .amenities-list span, .process-visual, .rating-summary, .note-box'
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

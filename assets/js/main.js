(function () {
    var nonBreakingSpace = '\u00a0';
    var hangingPrepositionsPattern = /(^|[\s([«"„“])((?:в|во|на|по|к|ко|с|со|у|о|об|обо|от|ото|до|из|изо|за|под|подо|над|надо|при|про|для|без|безо|через|перед|передо|после|около|возле|вокруг|между|среди|и|а|но|или|да|не|ни|же|ли|бы))[ \t\r\n\f]+/gi;

    function protectHangingPrepositions(text) {
        var result = text;
        var previous = '';
        var passes = 0;

        while (result !== previous && passes < 4) {
            previous = result;
            result = result.replace(hangingPrepositionsPattern, function (match, prefix, word) {
                return prefix + word + nonBreakingSpace;
            });
            passes += 1;
        }

        return result;
    }

    function shouldSkipTypography(node) {
        var parent = node.parentElement;

        return !parent || Boolean(parent.closest('script, style, noscript, textarea, select, option, svg, code, pre'));
    }

    function applyTypography(root) {
        if (!root || !document.createTreeWalker) {
            return;
        }

        var walker = document.createTreeWalker(
            root,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function (node) {
                    if (!node.nodeValue || !node.nodeValue.trim() || shouldSkipTypography(node)) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        var nodes = [];
        var node = walker.nextNode();

        while (node) {
            nodes.push(node);
            node = walker.nextNode();
        }

        nodes.forEach(function (textNode) {
            textNode.nodeValue = protectHangingPrepositions(textNode.nodeValue);
        });
    }

    applyTypography(document.body);

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

        serviceHint.textContent = protectHangingPrepositions('Скажите администратору: «' + service + '». Добавьте марку авто, симптом, удобное время и вопрос по запчастям.');
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

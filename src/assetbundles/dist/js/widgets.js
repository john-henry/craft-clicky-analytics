/* Clicky Analytics: country drilldown for the Top Countries dashboard widget. */
(function () {
    'use strict';

    function headers() {
        var h = { Accept: 'application/json' };
        if (window.Craft && Craft.csrfTokenName) {
            h[Craft.csrfTokenName] = Craft.csrfTokenValue;
        }
        return h;
    }

    var liveRegion = null;

    function t(message, params) {
        return window.Craft ? Craft.t('clicky-analytics', message, params) : message;
    }

    // The drilldown fills in after the toggle is pressed, so the outcome is
    // spoken; otherwise a screen reader user hears nothing once it loads.
    function announce(message) {
        if (window.Craft && Craft.cp && typeof Craft.cp.announce === 'function') {
            Craft.cp.announce(message);
            return;
        }
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.className = 'visually-hidden';
            liveRegion.setAttribute('role', 'status');
            document.body.appendChild(liveRegion);
        }
        liveRegion.textContent = '';
        setTimeout(function () {
            liveRegion.textContent = message;
        }, 100);
    }

    function init() {
        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('.clicky-rank__toggle');
            if (!toggle) {
                return;
            }

            var row = toggle.closest('[data-clicky-drill]');
            var panel = row ? row.nextElementSibling : null;
            if (!panel || !panel.hasAttribute('data-clicky-drill-panel')) {
                return;
            }

            if (toggle.getAttribute('aria-expanded') === 'true') {
                toggle.setAttribute('aria-expanded', 'false');
                row.classList.remove('clicky-rank__item--open');
                panel.setAttribute('hidden', '');
                return;
            }

            toggle.setAttribute('aria-expanded', 'true');
            row.classList.add('clicky-rank__item--open');
            panel.removeAttribute('hidden');

            if (panel.getAttribute('data-loaded') === '1') {
                return;
            }

            var url = row.getAttribute('data-clicky-url');
            var country = row.getAttribute('data-clicky-drill');
            var q =
                (url.indexOf('?') === -1 ? '?' : '&') +
                'country=' + encodeURIComponent(country) +
                '&date=' + encodeURIComponent(row.getAttribute('data-clicky-date'));

            panel.setAttribute('aria-busy', 'true');
            panel.innerHTML = '<div class="clicky-drill__loading">' + t('Loading…') + '</div>';

            fetch(url + q, { headers: headers(), credentials: 'same-origin' })
                .then(function (response) {
                    return response.ok ? response.json() : Promise.reject();
                })
                .then(function (payload) {
                    panel.innerHTML = payload.html;
                    panel.setAttribute('data-loaded', '1');
                    announce(t('Loaded cities and regions for {label}.', { label: country }));
                })
                .catch(function () {
                    var message = t('Couldn’t load this country.');
                    panel.innerHTML = '<div class="clicky-drill__empty">' + message + '</div>';
                    announce(message);
                })
                .finally(function () {
                    panel.removeAttribute('aria-busy');
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

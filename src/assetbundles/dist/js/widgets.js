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
            var q =
                (url.indexOf('?') === -1 ? '?' : '&') +
                'country=' + encodeURIComponent(row.getAttribute('data-clicky-drill')) +
                '&date=' + encodeURIComponent(row.getAttribute('data-clicky-date'));

            panel.setAttribute('aria-busy', 'true');
            panel.innerHTML = '<div class="clicky-drill__loading">' +
                (window.Craft ? Craft.t('clicky-analytics', 'Loading…') : 'Loading…') +
                '</div>';

            fetch(url + q, { headers: headers(), credentials: 'same-origin' })
                .then(function (response) {
                    return response.ok ? response.json() : Promise.reject();
                })
                .then(function (payload) {
                    panel.innerHTML = payload.html;
                    panel.setAttribute('data-loaded', '1');
                })
                .catch(function () {
                    panel.innerHTML = '<div class="clicky-drill__empty">' +
                        (window.Craft ? Craft.t('clicky-analytics', 'Couldn’t load this country.') : 'Error') +
                        '</div>';
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

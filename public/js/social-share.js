(function () {
    'use strict';

    function updateCounts(token, counts) {
        document.querySelectorAll('.df-social-share[data-share-token]').forEach(function (root) {
            if (root.getAttribute('data-share-token') !== token) {
                return;
            }

            root.querySelectorAll('[data-share-count-network]').forEach(function (element) {
                const network = element.getAttribute('data-share-count-network');

                if (network && Object.prototype.hasOwnProperty.call(counts, network)) {
                    element.textContent = String(counts[network]);
                }
            });

            root.querySelectorAll('[data-share-count-total]').forEach(function (element) {
                element.textContent = String(counts.total || 0);
            });
        });
    }

    function recordShare(root, network) {
        const endpoint = root.getAttribute('data-share-endpoint');
        const token = root.getAttribute('data-share-token');

        if (
            !endpoint ||
            !token ||
            !network ||
            typeof window.fetch !== 'function' ||
            typeof window.URLSearchParams !== 'function'
        ) {
            return Promise.resolve();
        }

        const data = new window.URLSearchParams();
        data.set('share_token', token);
        data.set('network', network);

        const csrf = root.querySelector('input[name="_token"]');

        if (csrf && csrf.value) {
            data.set('_token', csrf.value);
        }

        return window.fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: data.toString(),
            keepalive: true
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (payload && payload.counts) {
                updateCounts(token, payload.counts);
            }
        }).catch(function () {
            // Sharing must continue even when analytics cannot be recorded.
        });
    }

    function fallbackCopy(value) {
        const input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', 'readonly');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();

        const copied = document.execCommand('copy');
        input.remove();

        return copied ? Promise.resolve() : Promise.reject(new Error('Copy failed'));
    }

    function copyText(value) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(value);
        }

        return fallbackCopy(value);
    }

    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('.df-social-share__button');

        if (!link) {
            return;
        }

        const root = link.closest('.df-social-share');
        const network = link.getAttribute('data-share-network');
        const copyUrl = link.getAttribute('data-share-copy');

        if (!root || !network) {
            return;
        }

        if (!copyUrl) {
            recordShare(root, network);
            return;
        }

        event.preventDefault();

        copyText(copyUrl).then(function () {
            link.classList.add('is-copied');
            link.setAttribute('aria-label', 'Link copied');
            recordShare(root, network);

            setTimeout(function () {
                link.classList.remove('is-copied');
                link.setAttribute('aria-label', 'Copy link');
            }, 1800);
        }).catch(function () {
            link.setAttribute('aria-label', 'Unable to copy link');
        });
    });
})();

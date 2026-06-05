(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-share-copy]');

        if (!link) {
            return;
        }

        event.preventDefault();

        const url = link.getAttribute('data-share-copy');

        if (!url) {
            return;
        }

        navigator.clipboard.writeText(url).then(function () {
            link.classList.add('is-copied');
            link.setAttribute('aria-label', 'Link copied');

            setTimeout(function () {
                link.classList.remove('is-copied');
                link.setAttribute('aria-label', 'Copy link');
            }, 1800);
        });
    });
})();

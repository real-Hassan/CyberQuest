// Light/dark theme. Loaded in <head> (not deferred) so the page never flashes the wrong theme.
(function () {
    var KEY = 'cq-theme', root = document.documentElement, saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) { /* storage blocked */ }
    var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.setAttribute('data-theme', saved || (dark ? 'dark' : 'light'));

    var ICONS = '<svg class="i-moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>' +
        '<svg class="i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';

    document.addEventListener('DOMContentLoaded', function () {
        var btns = Array.prototype.slice.call(document.querySelectorAll('[data-theme-toggle]'));
        if (!btns.length) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'theme-toggle floating'; b.setAttribute('data-theme-toggle', '');
            b.setAttribute('aria-label', 'Toggle light or dark theme'); b.innerHTML = ICONS;
            document.body.appendChild(b); btns = [b];
        }
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem(KEY, next); } catch (e) { /* ignore */ }
            });
        });
    });
})();

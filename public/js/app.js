/**
 * Validación ligera en cliente y mejora de UX (sin dependencias).
 */

var icons = { success: '✓', error: '✕', info: 'ℹ' };

function showToast(msg, type) {
    type = type || 'success';
    var c = document.getElementById('toast-container');
    var t = document.createElement('div');
    t.className = 'toast' + (type !== 'success' ? ' toast-' + type : '');

    var iconEl = document.createElement('span');
    iconEl.className = 'toast-icon';
    iconEl.textContent = icons[type] || '✓';

    var msgEl = document.createElement('span');
    msgEl.className = 'toast-msg';
    msgEl.textContent = msg;

    var closeBtn = document.createElement('button');
    closeBtn.className = 'toast-close';
    closeBtn.textContent = '×';
    closeBtn.setAttribute('aria-label', 'Cerrar notificación');
    closeBtn.addEventListener('click', function () { dismissToast(t); });

    t.appendChild(iconEl);
    t.appendChild(msgEl);
    t.appendChild(closeBtn);
    c.appendChild(t);
    setTimeout(function () { dismissToast(t); }, 4000);
}

function dismissToast(t) {
    if (!t || !t.parentElement) return;
    t.classList.add('t-out');
    setTimeout(function () { if (t.parentElement) t.remove(); }, 300);
}

(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    // Formularios de registro / login: refuerzo de mensajes nativos
    var registerForm = qs('form[action="/register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            var p1 = qs('input[name="password"]', registerForm);
            var p2 = qs('input[name="password2"]', registerForm);
            if (p1 && p2 && p1.value !== p2.value) {
                e.preventDefault();
                p2.setCustomValidity('Las contraseñas no coinciden.');
                p2.reportValidity();
            }
        });
        qsa('input[name="password"], input[name="password2"]', registerForm).forEach(function (el) {
            el.addEventListener('input', function () {
                el.setCustomValidity('');
            });
        });
    }

    // Filtros del catálogo: trim al enviar
    var catalogFilters = qs('form.filters[action="/catalog.php"]');
    if (catalogFilters) {
        catalogFilters.addEventListener('submit', function () {
            var m = qs('input[name="model"]', catalogFilters);
            var s = qs('input[name="size"]', catalogFilters);
            if (m) m.value = m.value.trim();
            if (s) s.value = s.value.trim();
        });
    }

    var resetForm = qs('form[action^="/reset_password.php"]');
    if (resetForm) {
        resetForm.addEventListener('submit', function (e) {
            var p1 = qs('input[name="password"]', resetForm);
            var p2 = qs('input[name="password2"]', resetForm);
            if (p1 && p2 && p1.value !== p2.value) {
                e.preventDefault();
                p2.setCustomValidity('Las contraseñas no coinciden.');
                p2.reportValidity();
            }
        });
        qsa('input[name="password"], input[name="password2"]', resetForm).forEach(function (el) {
            el.addEventListener('input', function () {
                el.setCustomValidity('');
            });
        });
    }

    // ── AUTOCOMPLETADO EN EL BUSCADOR DEL HEADER ──
    var headerInput = qs('#header-q');
    if (headerInput) {
        var suggestList = null;
        var debounceTimer = null;
        var activeIdx = -1;

        function closeSuggest() {
            if (suggestList) {
                suggestList.remove();
                suggestList = null;
                activeIdx = -1;
            }
        }

        function buildSuggest(items) {
            closeSuggest();
            if (items.length === 0) return;

            suggestList = document.createElement('ul');
            suggestList.className = 'search-suggest';
            suggestList.setAttribute('role', 'listbox');

            items.forEach(function (item, i) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.setAttribute('data-idx', i);

                var name = document.createElement('span');
                name.className = 'suggest-name';
                name.textContent = item.seleccion;

                var cont = document.createElement('span');
                cont.className = 'suggest-continent';
                cont.textContent = item.continente;

                li.appendChild(name);
                li.appendChild(cont);

                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    headerInput.value = item.seleccion;
                    closeSuggest();
                    headerInput.closest('form').submit();
                });

                suggestList.appendChild(li);
            });

            var wrap = headerInput.closest('.header-search') || headerInput.parentElement;
            wrap.style.position = 'relative';
            wrap.appendChild(suggestList);
        }

        function fetchSuggestions(q) {
            fetch('/api/search_suggest.php?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) { buildSuggest(data); })
                .catch(function () { closeSuggest(); });
        }

        headerInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            var q = headerInput.value.trim();
            if (q.length < 2) { closeSuggest(); return; }
            debounceTimer = setTimeout(function () { fetchSuggestions(q); }, 220);
        });

        headerInput.addEventListener('keydown', function (e) {
            if (!suggestList) return;
            var items = suggestList.querySelectorAll('li');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, items.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(activeIdx - 1, -1);
            } else if (e.key === 'Escape') {
                closeSuggest();
                return;
            } else if (e.key === 'Enter' && activeIdx >= 0) {
                e.preventDefault();
                items[activeIdx].dispatchEvent(new MouseEvent('mousedown'));
                return;
            } else {
                return;
            }
            items.forEach(function (el, i) {
                el.classList.toggle('is-active', i === activeIdx);
                if (i === activeIdx) {
                    headerInput.value = el.querySelector('.suggest-name').textContent;
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (suggestList && !suggestList.contains(e.target) && e.target !== headerInput) {
                closeSuggest();
            }
        });
    }

})();

document.addEventListener('click', function (e) {
    if (!e.target.closest('.nav-item')) {
        document.activeElement?.blur();
    }
});

(function () {
    'use strict';

    function qs(root, sel) {
        return root.querySelector(sel);
    }

    function qsa(root, sel) {
        return Array.prototype.slice.call(root.querySelectorAll(sel));
    }

    function num(value, fallback) {
        var n = parseFloat(String(value).replace(/\s+/g, '').replace(',', '.'));
        return isFinite(n) ? n : fallback;
    }

    function init(root) {
        var form = qs(root, '[data-bsjf-form]');
        var grid = qs(root, '[data-bsjf-grid]');
        var moreBtn = qs(root, '[data-bsjf-more]');
        var emptyEl = qs(root, '[data-bsjf-empty]');
        var catInput = qs(root, '[data-bsjf-category]');
        var filterToggle = qs(root, '[data-bsjf-toggle]');

        if (!form || !grid) {
            return;
        }

        var state = {
            offset: grid.querySelectorAll('.bsjf-card').length,
            pending: false
        };

        function payload(offset) {
            var data = new URLSearchParams();
            data.set('option', 'com_ajax');
            data.set('module', 'bsjshop_filter');
            data.set('method', 'filter');
            data.set('format', 'json');
            data.set('helper', 'BsjshopFilterHelper');
            data.set('module_id', root.getAttribute('data-module-id') || '0');
            data.set('Itemid', root.getAttribute('data-itemid') || '0');
            data.set(root.getAttribute('data-token') || '1', '1');
            data.set('category_id', catInput ? catInput.value : '0');
            data.set('offset', String(offset));

            var priceFrom = form.querySelector('[data-bsjf-field="price_from"]');
            var priceTo = form.querySelector('[data-bsjf-field="price_to"]');
            var areaFrom = form.querySelector('[data-bsjf-field="area_from"]');
            var areaTo = form.querySelector('[data-bsjf-field="area_to"]');

            if (priceFrom) {
                data.set('price_from', priceFrom.value);
            }
            if (priceTo) {
                data.set('price_to', priceTo.value);
            }
            if (areaFrom) {
                data.set('area_from', areaFrom.value);
            }
            if (areaTo) {
                data.set('area_to', areaTo.value);
            }

            return data;
        }

        function setBusy(busy) {
            state.pending = busy;
            root.classList.toggle('is-loading', busy);
        }

        function toggle(el, show) {
            if (!el) {
                return;
            }
            el.classList.toggle('is-hidden', !show);
        }

        function setFilterOpen(open) {
            root.classList.toggle('is-filter-open', open);
            if (filterToggle) {
                filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        function closeMobileFilter() {
            if (window.matchMedia('(max-width: 767px)').matches) {
                setFilterOpen(false);
            }
        }

        function request(offset, append) {
            if (state.pending) {
                return;
            }

            setBusy(true);

            fetch(root.getAttribute('data-ajax-url') || 'index.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload(offset).toString()
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (json) {
                    if (!json || json.success === false) {
                        throw new Error((json && json.message) || 'filter');
                    }

                    var data = json.data || {};
                    var html = data.html || '';

                    if (append) {
                        grid.insertAdjacentHTML('beforeend', html);
                    } else {
                        grid.innerHTML = html;
                    }

                    var cards = grid.querySelectorAll('.bsjf-card').length;
                    state.offset = cards;
                    toggle(emptyEl, cards === 0);
                    toggle(moreBtn, !!data.hasMore);
                })
                .catch(function () {
                    if (!append) {
                        grid.innerHTML = '';
                        toggle(emptyEl, true);
                        toggle(moreBtn, false);
                    }
                })
                .then(function () {
                    setBusy(false);
                });
        }

        qsa(root, '[data-bsjf-cat]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                qsa(root, '[data-bsjf-cat]').forEach(function (el) {
                    el.classList.toggle('is-active', el === tab);
                });
                if (catInput) {
                    catInput.value = tab.getAttribute('data-bsjf-cat') || '0';
                }
                request(0, false);
            });
        });

        if (filterToggle) {
            filterToggle.addEventListener('click', function () {
                setFilterOpen(!root.classList.contains('is-filter-open'));
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            closeMobileFilter();
            request(0, false);
        });

        var resetBtn = qs(root, '[data-bsjf-reset]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                var priceFrom = form.querySelector('[data-bsjf-field="price_from"]');
                var priceTo = form.querySelector('[data-bsjf-field="price_to"]');
                var areaFrom = form.querySelector('[data-bsjf-field="area_from"]');
                var areaTo = form.querySelector('[data-bsjf-field="area_to"]');

                if (priceFrom) {
                    priceFrom.value = String(parseInt(root.getAttribute('data-price-min') || '0', 10));
                }
                if (priceTo) {
                    priceTo.value = String(parseInt(root.getAttribute('data-price-max') || '0', 10));
                }
                if (areaFrom) {
                    areaFrom.value = String(num(root.getAttribute('data-area-min'), 0));
                }
                if (areaTo) {
                    areaTo.value = String(num(root.getAttribute('data-area-max'), 0));
                }
                if (catInput) {
                    catInput.value = '0';
                }
                qsa(root, '[data-bsjf-cat]').forEach(function (el) {
                    el.classList.toggle('is-active', el.getAttribute('data-bsjf-cat') === '0');
                });
                closeMobileFilter();
                request(0, false);
            });
        }

        if (moreBtn) {
            moreBtn.addEventListener('click', function () {
                request(state.offset, true);
            });
        }
    }

    function boot() {
        qsa(document, '[data-bsjf]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

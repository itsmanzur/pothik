(function () {
    var root = document.querySelector('.pk-price');
    var select = document.querySelector('.pk-inquiry select[name="share"]');
    if (!root || !select) {
        return;
    }

    var prices = {};
    try {
        prices = JSON.parse(root.getAttribute('data-pk-prices') || '{}');
    } catch (error) {
        prices = {};
    }

    var rows = root.querySelectorAll('tr[data-share]');
    var line = root.querySelector('[data-pk-estimate-line]');
    var extra = root.querySelector('[data-pk-estimate-extra]');
    var adults = document.querySelector('.pk-inquiry input[name="adults"]');
    var children = document.querySelector('.pk-inquiry input[name="children"]');
    var infants = document.querySelector('.pk-inquiry input[name="infants"]');

    function money(amount) {
        var digits = String(Math.abs(parseInt(amount, 10) || 0));
        if (digits.length > 3) {
            var tail = digits.slice(-3);
            var head = digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
            digits = head + ',' + tail;
        }
        return '৳' + digits;
    }

    function count(input) {
        var value = parseInt(input && input.value, 10);
        return Number.isFinite(value) && value > 0 ? value : 0;
    }

    function paint() {
        var share = select.value;
        rows.forEach(function (row) {
            var on = row.getAttribute('data-share') === share;
            row.classList.toggle('is-lead', on);
            if (row.classList.contains('is-pick')) {
                row.setAttribute('aria-pressed', on ? 'true' : 'false');
            }
        });
        if (line) {
            var people = Math.max(1, count(adults));
            var price = parseInt(prices[share], 10) || 0;
            var label = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : share;
            line.textContent = people + ' ' + (root.getAttribute('data-adults-word') || '') + ' · ' + label + ' · ' + money(price * people);
        }
        if (!extra) {
            return;
        }
        var bits = [];
        var childCount = count(children);
        var infantCount = count(infants);
        if (childCount > 0) {
            bits.push(childCount + ' ' + (root.getAttribute('data-child-word') || ''));
        }
        if (infantCount > 0) {
            bits.push(infantCount + ' ' + (root.getAttribute('data-infant-word') || ''));
        }
        extra.textContent = bits.length
            ? bits.join(' · ') + '. ' + (root.getAttribute('data-office-note') || '')
            : (root.getAttribute('data-seat-note') || '');
    }

    function choose(row) {
        var share = row.getAttribute('data-share');
        if (!share) {
            return;
        }
        select.value = share;
        paint();
    }

    rows.forEach(function (row) {
        if (!row.classList.contains('is-pick')) {
            return;
        }
        row.addEventListener('click', function () {
            choose(row);
        });
        row.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            choose(row);
        });
    });

    [select, adults, children, infants].forEach(function (node) {
        if (!node) {
            return;
        }
        node.addEventListener('input', paint);
        node.addEventListener('change', paint);
    });
    paint();
}());

(function () {
    var root = document.querySelector('[data-pk-catalog]');
    if (!root) {
        return;
    }
    root.classList.add('is-js');
    var finder = root.querySelector('[data-pk-finder]');
    if (finder) {
        finder.addEventListener('submit', function (event) {
            event.preventDefault();
        });
    }

    var i18n = (window.pothikPublic && window.pothikPublic.i18n) || {};
    function t(key, fallback) {
        return i18n[key] || fallback;
    }
    function money(amount) {
        var digits = String(Math.abs(parseInt(amount, 10) || 0));
        if (digits.length > 3) {
            var tail = digits.slice(-3);
            var head = digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
            digits = head + ',' + tail;
        }
        return '৳' + digits;
    }
    function text(node, value) {
        if (node) {
            node.textContent = value;
        }
    }
    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    var grid = root.querySelector('[data-pk-grid]');
    var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.card')) : [];
    var count = root.querySelector('[data-pk-count]');
    var q = root.querySelector('[data-pk-q]');
    var bud = root.querySelector('[data-pk-bud]');
    var budOut = root.querySelector('[data-pk-bud-out]');
    var sort = root.querySelector('[data-pk-sort]');
    var dayBox = root.querySelector('[data-pk-days]');
    var monthBox = root.querySelector('[data-pk-month]');
    var emptyTpl = root.querySelector('[data-pk-empty]');
    var tray = root.querySelector('[data-pk-tray]');
    var trayList = root.querySelector('[data-pk-tray-list]');
    var cmpOpen = root.querySelector('[data-pk-cmp-open]');
    var modal = root.querySelector('[data-pk-modal]');
    var table = root.querySelector('[data-pk-table]');
    var toast = root.querySelector('[data-pk-toast]');
    var chosen = [];
    var state = { q: '', days: '', bud: bud ? Number(bud.max) : 0, sort: 'price', month: '' };
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var timers = [];
    var heroOn = false;
    var starsDrawn = false;
    var sceneTimer = 0;

    function readUrl() {
        var params = new URLSearchParams(window.location.search);
        state.days = params.get('days') || '';
        state.sort = params.get('sort') || 'price';
        state.month = params.get('month') || '';
        if (params.get('max') && bud) {
            state.bud = Number(params.get('max'));
        }
        if (params.get('q')) {
            state.q = String(params.get('q')).toLowerCase();
            if (q) {
                q.value = params.get('q');
            }
        }
        if (dayBox) {
            dayBox.querySelectorAll('button').forEach(function (button) {
                button.classList.toggle('on', (button.getAttribute('data-v') || '') === state.days);
            });
        }
        if (monthBox) {
            monthBox.querySelectorAll('.chip').forEach(function (button) {
                button.classList.toggle('on', (button.getAttribute('data-v') || '') === state.month);
            });
        }
        if (sort) {
            sort.value = state.sort;
        }
        if (bud) {
            bud.value = String(state.bud);
        }
        paintBudget();
    }

    function writeUrl() {
        var params = new URLSearchParams(window.location.search);
        ['package', 'pothik', 'pothik_lang'].forEach(function () {});
        function set(key, value, skip) {
            if (!value || value === skip) {
                params.delete(key);
            } else {
                params.set(key, value);
            }
        }
        set('q', state.q, '');
        set('days', state.days, '');
        set('sort', state.sort, 'price');
        set('month', state.month, '');
        if (!bud || state.bud >= Number(bud.max)) {
            params.delete('max');
        } else {
            params.set('max', String(state.bud));
        }
        var next = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
        window.history.replaceState(null, '', next);
    }

    function paintBudget() {
        if (!bud || !budOut) {
            return;
        }
        budOut.textContent = state.bud >= Number(bud.max)
            ? t('any', 'Any')
            : t('upTo', 'up to %s').replace('%s', money(state.bud));
    }

    function match(card) {
        var title = (card.getAttribute('data-title') || '') + ' ' + (card.getAttribute('data-hotels') || '');
        var days = Number(card.getAttribute('data-days') || 0);
        var price = Number(card.getAttribute('data-price') || 0);
        var month = card.getAttribute('data-month') || '';
        var ramadan = card.getAttribute('data-ramadan') === '1';
        if (state.q && title.indexOf(state.q) === -1) {
            return false;
        }
        if (state.days === 's' && days > 10) {
            return false;
        }
        if (state.days === 'm' && (days < 11 || days > 15)) {
            return false;
        }
        if (state.days === 'l' && days < 16) {
            return false;
        }
        if (bud && price > state.bud && price > 0) {
            return false;
        }
        if (state.month === 'ramadan' && !ramadan) {
            return false;
        }
        if (state.month && state.month !== 'ramadan' && month !== state.month) {
            return false;
        }
        return true;
    }

    function render() {
        var shown = cards.filter(match);
        var rank = { price: 'data-price', near: 'data-makkah-m', days: 'data-days' };
        shown.sort(function (a, b) {
            if (state.sort === 'stars') {
                return Number(b.getAttribute('data-stars') || 0) - Number(a.getAttribute('data-stars') || 0);
            }
            var key = rank[state.sort] || 'data-price';
            var av = Number(a.getAttribute(key) || 0);
            var bv = Number(b.getAttribute(key) || 0);
            if (key !== 'data-stars' && av === 0) {
                av = 999999;
            }
            if (key !== 'data-stars' && bv === 0) {
                bv = 999999;
            }
            return av - bv;
        });
        cards.forEach(function (card) {
            card.hidden = true;
        });
        shown.forEach(function (card) {
            card.hidden = false;
            if (grid) {
                grid.appendChild(card);
            }
        });
        var hole = grid ? grid.querySelector('.empty') : null;
        if (hole) {
            hole.remove();
        }
        if (shown.length === 0 && emptyTpl && grid && cards.length) {
            grid.appendChild(emptyTpl.content.cloneNode(true));
        }
        var label = shown.length === 1 ? t('one', '%d package') : t('many', '%d packages');
        text(count, label.replace('%d', String(shown.length)));
    }

    function cardById(id) {
        return cards.filter(function (card) {
            return card.getAttribute('data-id') === id;
        })[0] || null;
    }

    function paintTray() {
        if (!tray || !trayList || !cmpOpen) {
            return;
        }
        trayList.innerHTML = '';
        chosen.forEach(function (id) {
            var card = cardById(id);
            var span = document.createElement('span');
            span.textContent = card ? (card.querySelector('h3') ? card.querySelector('h3').textContent : id) : id;
            trayList.appendChild(span);
        });
        tray.classList.toggle('show', chosen.length > 0);
        cmpOpen.disabled = chosen.length < 2;
        cmpOpen.textContent = chosen.length < 2
            ? t('pick', 'Pick 2–3')
            : t('compareN', 'Compare %d').replace('%d', String(chosen.length));
    }

    function showToast(message) {
        if (!toast) {
            return;
        }
        toast.hidden = false;
        toast.textContent = message;
        window.setTimeout(function () {
            toast.hidden = true;
        }, 2400);
    }

    function bestClass(values, lowest) {
        var usable = values.filter(function (value) {
            return value > 0;
        });
        if (!usable.length) {
            return values.map(function () { return ''; });
        }
        var mark = lowest ? Math.min.apply(null, usable) : Math.max.apply(null, usable);
        return values.map(function (value) {
            return value === mark && value > 0 ? ' class="best"' : '';
        });
    }

    function openCompare() {
        if (!modal || !table || chosen.length < 2) {
            return;
        }
        var rows = chosen.map(cardById).filter(Boolean);
        var head = '<thead><tr><th></th>' + rows.map(function (card) {
            var title = card.querySelector('h3');
            return '<td>' + esc(title ? title.textContent : '') + '</td>';
        }).join('') + '</tr></thead>';
        function line(label, values, texts, lowest) {
            var marks = bestClass(values, lowest);
            return '<tr><th>' + label + '</th>' + texts.map(function (textValue, index) {
                return '<td' + marks[index] + '>' + textValue + '</td>';
            }).join('') + '</tr>';
        }
        var body = [
            line(t('daysLabel', 'Days'), rows.map(function (card) { return Number(card.getAttribute('data-days') || 0); }), rows.map(function (card) { return esc((card.getAttribute('data-days') || '0') + ' ' + t('daysWord', 'days')); }), true),
            line(t('makkahHotel', 'Makkah hotel'), rows.map(function (card) { return Number(card.getAttribute('data-ms') || 0); }), rows.map(function (card) { return esc((card.getAttribute('data-mh') || '') + (Number(card.getAttribute('data-ms') || 0) ? ' · ' + '★'.repeat(Number(card.getAttribute('data-ms'))) : '')); }), false),
            line(t('haram', 'To the Haram'), rows.map(function (card) { return Number(card.getAttribute('data-makkah-m') || 0); }), rows.map(function (card) { return esc((card.getAttribute('data-makkah-m') || '0') + ' m' + (card.getAttribute('data-walk-m') ? ' · ' + card.getAttribute('data-walk-m') : '')); }), true),
            line(t('madinahHotel', 'Madinah hotel'), rows.map(function (card) { return Number(card.getAttribute('data-ds') || 0); }), rows.map(function (card) { return esc((card.getAttribute('data-dh') || '') + (Number(card.getAttribute('data-ds') || 0) ? ' · ' + '★'.repeat(Number(card.getAttribute('data-ds'))) : '')); }), false),
            line(t('mosque', "To the Prophet's Mosque"), rows.map(function (card) { return Number(card.getAttribute('data-madinah-m') || 0); }), rows.map(function (card) { return esc((card.getAttribute('data-madinah-m') || '0') + ' m' + (card.getAttribute('data-walk-d') ? ' · ' + card.getAttribute('data-walk-d') : '')); }), true),
            line(t('quad', 'Quad price'), rows.map(function (card) { return Number(card.getAttribute('data-price') || 0); }), rows.map(function (card) { var price = Number(card.getAttribute('data-price') || 0); return price > 0 ? money(price) : t('contact', 'Contact the office'); }), true),
            line(t('triple', 'Triple price'), rows.map(function (card) { return Number(card.getAttribute('data-triple') || 0); }), rows.map(function (card) { var price = Number(card.getAttribute('data-triple') || 0); return price > 0 ? money(price) : '—'; }), true)
        ].join('');
        var links = '<tr><th></th>' + rows.map(function (card) {
            return '<td><a class="btn btn-primary btn-sm" href="' + esc(card.getAttribute('data-url') || '#') + '">' + esc(t('details', 'Details')) + '</a></td>';
        }).join('') + '</tr>';
        table.innerHTML = head + '<tbody>' + body + links + '</tbody>';
        modal.hidden = false;
        var close = modal.querySelector('[data-pk-cmp-close]');
        if (close) {
            close.focus();
        }
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
    }

    root.addEventListener('change', function (event) {
        var input = event.target;
        if (!input || !input.hasAttribute('data-pk-cmp')) {
            return;
        }
        var id = input.value;
        if (input.checked) {
            if (chosen.length >= 3) {
                input.checked = false;
                showToast(t('toast', 'You can compare up to 3 packages.'));
                return;
            }
            chosen.push(id);
        } else {
            chosen = chosen.filter(function (item) { return item !== id; });
        }
        input.parentElement.classList.toggle('on', input.checked);
        paintTray();
    });

    root.addEventListener('click', function (event) {
        var reset = event.target.closest ? event.target.closest('[data-pk-reset]') : null;
        if (reset) {
            state = { q: '', days: '', bud: bud ? Number(bud.max) : 0, sort: 'price', month: '' };
            if (q) {
                q.value = '';
            }
            if (bud) {
                bud.value = bud.max;
            }
            if (sort) {
                sort.value = 'price';
            }
            if (dayBox) {
                dayBox.querySelectorAll('button').forEach(function (button, index) {
                    button.classList.toggle('on', index === 0);
                });
            }
            if (monthBox) {
                monthBox.querySelectorAll('.chip').forEach(function (button, index) {
                    button.classList.toggle('on', index === 0);
                });
            }
            paintBudget();
            writeUrl();
            render();
            return;
        }
        var day = event.target.closest ? event.target.closest('[data-pk-days] button') : null;
        if (day) {
            state.days = day.getAttribute('data-v') || '';
            dayBox.querySelectorAll('button').forEach(function (button) {
                button.classList.toggle('on', button === day);
            });
            writeUrl();
            render();
            return;
        }
        var month = event.target.closest ? event.target.closest('[data-pk-month] .chip') : null;
        if (month) {
            state.month = month.getAttribute('data-v') || '';
            monthBox.querySelectorAll('.chip').forEach(function (button) {
                button.classList.toggle('on', button === month);
            });
            writeUrl();
            render();
        }
    });

    if (q) {
        q.addEventListener('input', function () {
            state.q = q.value.toLowerCase();
            writeUrl();
            render();
        });
    }
    if (bud) {
        bud.addEventListener('input', function () {
            state.bud = Number(bud.value);
            paintBudget();
            writeUrl();
            render();
        });
    }
    if (sort) {
        sort.addEventListener('change', function () {
            state.sort = sort.value;
            writeUrl();
            render();
        });
    }
    if (cmpOpen) {
        cmpOpen.addEventListener('click', openCompare);
    }
    var clearBtn = root.querySelector('[data-pk-cmp-clear]');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            chosen = [];
            root.querySelectorAll('[data-pk-cmp]').forEach(function (input) {
                input.checked = false;
                input.parentElement.classList.remove('on');
            });
            paintTray();
        });
    }
    var closeBtn = root.querySelector('[data-pk-cmp-close]');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        modal.addEventListener('keydown', function (event) {
            if (event.key !== 'Tab') {
                return;
            }
            var nodes = modal.querySelectorAll('a, button, input, select, textarea');
            if (!nodes.length) {
                return;
            }
            var first = nodes[0];
            var last = nodes[nodes.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    function startHero() {
        if (heroOn) {
            return;
        }
        heroOn = true;
        var sky = root.querySelector('[data-pk-sky]');
        if (sky && !reduced && !starsDrawn) {
            starsDrawn = true;
            for (var i = 0; i < 50; i++) {
                var star = document.createElement('i');
                star.style.left = (Math.random() * 100) + '%';
                star.style.top = (Math.random() * 60) + '%';
                star.style.animationDelay = (Math.random() * 4) + 's';
                sky.appendChild(star);
            }
        }
        var seasonBox = root.querySelector('[data-pk-season]');
        if (seasonBox && seasonBox.getAttribute('data-pk-season-js') === '1' && window.Intl && Intl.DateTimeFormat) {
            try {
                var now = new Date();
                var fmt = new Intl.DateTimeFormat('en-u-ca-islamic-umalqura', { month: 'numeric', day: 'numeric' });
                function parts(date) {
                    return fmt.formatToParts(date).reduce(function (bag, part) {
                        bag[part.type] = part.value;
                        return bag;
                    }, {});
                }
                function find(month, day) {
                    for (var n = 0; n < 400; n++) {
                        var when = new Date(now.getTime() + n * 86400000);
                        var bit = parts(when);
                        if (Number(bit.month) === month && Number(bit.day) === day) {
                            return n;
                        }
                    }
                    return null;
                }
                var today = parts(now);
                var ramadanNode = root.querySelector('[data-pk-ramadan-n]');
                var hajjNode = root.querySelector('[data-pk-hajj-n]');
                if (Number(today.month) === 9 && ramadanNode) {
                    ramadanNode.parentElement.textContent = t('ramadanNow', 'Ramadan Mubarak') + ' 🌙';
                } else if (ramadanNode) {
                    var ramadan = find(9, 1);
                    if (ramadan !== null) {
                        ramadanNode.textContent = String(ramadan);
                    }
                }
                if (Number(today.month) === 12 && Number(today.day) >= 8 && Number(today.day) <= 13 && hajjNode) {
                    hajjNode.parentElement.textContent = t('hajjNow', 'Hajj days') + ' 🤍';
                } else if (hajjNode) {
                    var hajj = find(12, 8);
                    if (hajj !== null) {
                        hajjNode.textContent = String(hajj);
                    }
                }
            } catch (error) {
                /* calendar optional */
            }
        }
        var clock = root.querySelector('#mkTime');
        function tick() {
            if (!clock) {
                return;
            }
            clock.textContent = new Date().toLocaleTimeString('en-GB', { timeZone: 'Asia/Riyadh', hour: '2-digit', minute: '2-digit' });
        }
        tick();
        timers.push(window.setInterval(tick, 30000));
        if (!reduced && !sceneTimer) {
            sceneTimer = window.setInterval(function () {
                var makkah = root.querySelector('#scMakkah');
                showScene(makkah && makkah.classList.contains('off') ? 'Makkah' : 'Madinah');
            }, 9000);
            timers.push(sceneTimer);
        }
    }

    function stopHero() {
        timers.forEach(function (id) { window.clearInterval(id); });
        timers = [];
        sceneTimer = 0;
        heroOn = false;
    }

    function showScene(name) {
        var makkah = root.querySelector('#scMakkah');
        var madinah = root.querySelector('#scMadinah');
        if (makkah) {
            makkah.classList.toggle('off', name !== 'Makkah');
        }
        if (madinah) {
            madinah.classList.toggle('off', name !== 'Madinah');
        }
        root.querySelectorAll('[data-scene]').forEach(function (button) {
            button.classList.toggle('on', button.getAttribute('data-scene') === name);
        });
    }

    root.querySelectorAll('[data-scene]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (sceneTimer) {
                window.clearInterval(sceneTimer);
                sceneTimer = 0;
            }
            showScene(button.getAttribute('data-scene') || 'Makkah');
        });
    });

    var hero = root.querySelector('.hero');
    if (hero && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && document.visibilityState !== 'hidden') {
                    startHero();
                }
            });
        }, { threshold: 0.15 });
        observer.observe(hero);
    } else if (hero) {
        startHero();
    }
    document.addEventListener('visibilitychange', function () {
        if (!hero) {
            return;
        }
        if (document.visibilityState === 'hidden') {
            stopHero();
        } else {
            startHero();
        }
    });

    readUrl();
    render();
    paintTray();
}());

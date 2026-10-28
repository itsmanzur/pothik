(function () {
    document.addEventListener('input', onFilter);
    document.addEventListener('click', onChips);
    document.addEventListener('input', onReceipt);
    document.addEventListener('change', onReceipt);
    document.addEventListener('submit', onConfirm);
    document.addEventListener('click', onMore);
    document.addEventListener('keydown', onMoreKey);
    document.addEventListener('click', onSlip);
    window.addEventListener('afterprint', function () {
        document.body.classList.remove('pothik-print-slip');
    });

    function onFilter(event) {
        var input = event.target;
        if (!input || !input.getAttribute || !input.hasAttribute('data-pk-filter')) {
            return;
        }
        var body = document.getElementById(input.getAttribute('data-pk-filter') || '');
        if (!body) {
            return;
        }
        var query = String(input.value || '').trim().toLowerCase();
        var active = document.querySelector('[data-pk-status-filter] button.is-on');
        var status = active ? String(active.getAttribute('data-pk-status') || '') : '';
        var shown = 0;
        body.querySelectorAll('[data-pk-hay]').forEach(function (row) {
            var hay = String(row.getAttribute('data-pk-hay') || '').toLowerCase();
            var rowStatus = String(row.getAttribute('data-pk-status') || '');
            var match = (query === '' || hay.indexOf(query) !== -1) && (status === '' || rowStatus === status);
            row.hidden = !match;
            if (match) {
                shown += 1;
            }
        });
        var empty = document.querySelector('[data-pk-filter-empty]');
        if (empty) {
            empty.hidden = shown !== 0;
        }
    }

    function onChips(event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-pk-status-filter] button') : null;
        if (!button) {
            return;
        }
        var group = button.parentElement;
        if (!group) {
            return;
        }
        group.querySelectorAll('button').forEach(function (item) {
            item.classList.toggle('is-on', item === button);
        });
        var input = document.querySelector('[data-pk-filter]');
        if (input) {
            onFilter({ target: input });
        }
    }

    function onReceipt(event) {
        var node = event.target;
        var form = node && node.closest ? node.closest('[data-pk-receipt]') : null;
        if (!form) {
            return;
        }
        var slip = document.querySelector('[data-pk-slip]');
        if (!slip) {
            return;
        }
        var amount = form.querySelector('[name="amount"]');
        var name = form.querySelector('[name="name"]');
        var trx = form.querySelector('[name="trx"]');
        var channel = form.querySelector('[name="channel"]');
        var date = form.querySelector('[name="date"]');
        var note = form.querySelector('[name="note"]');
        var amountNode = slip.querySelector('[data-pk-slip-amount]');
        var who = slip.querySelector('[data-pk-slip-who]');
        var trxNode = slip.querySelector('[data-pk-slip-trx]');
        var dateNode = slip.querySelector('[data-pk-slip-date]');
        var noteNode = slip.querySelector('[data-pk-slip-note]');
        if (amountNode && amount) {
            amountNode.textContent = money(amount.value);
        }
        if (who && name && channel) {
            var label = channel.options && channel.selectedIndex >= 0 ? channel.options[channel.selectedIndex].text : '';
            who.textContent = [name.value, label].filter(Boolean).join(' · ');
        }
        if (trxNode && trx) {
            trxNode.textContent = trx.value;
        }
        if (dateNode && date) {
            dateNode.textContent = date.value;
        }
        if (noteNode && note) {
            noteNode.textContent = note.value;
        }
    }

    function onSlip(event) {
        var copy = event.target && event.target.closest ? event.target.closest('[data-pk-copy]') : null;
        var printButton = event.target && event.target.closest ? event.target.closest('[data-pk-print]') : null;
        if (copy) {
            var slip = document.querySelector('[data-pk-slip]');
            if (!slip) {
                return;
            }
            var parts = ['[data-pk-slip-amount]', '[data-pk-slip-who]', '[data-pk-slip-trx]', '[data-pk-slip-date]', '[data-pk-slip-note]'].map(function (selector) {
                var node = slip.querySelector(selector);
                return node ? String(node.textContent || '').trim() : '';
            }).filter(Boolean);
            var done = slip.querySelector('[data-pk-copied]');
            copyText(parts.join('\n')).then(function () {
                if (!done) {
                    return;
                }
                done.hidden = false;
                window.setTimeout(function () {
                    done.hidden = true;
                }, 1600);
            });
        }
        if (printButton) {
            document.body.classList.add('pothik-print-slip');
            window.print();
        }
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve) {
            var area = document.createElement('textarea');
            area.value = text;
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            area.remove();
            resolve();
        });
    }

    function closeMenus() {
        document.querySelectorAll('[data-pk-more-menu]').forEach(function (menu) {
            menu.hidden = true;
        });
        document.querySelectorAll('[data-pk-more]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
    }

    function onMore(event) {
        var button = event.target.closest ? event.target.closest('[data-pk-more]') : null;
        if (button) {
            event.preventDefault();
            var menu = button.parentElement ? button.parentElement.querySelector('[data-pk-more-menu]') : null;
            var willOpen = menu && menu.hidden;
            closeMenus();
            if (willOpen && menu) {
                menu.hidden = false;
                button.setAttribute('aria-expanded', 'true');
            }
            return;
        }
        if (!event.target.closest || !event.target.closest('[data-pk-more-menu]')) {
            closeMenus();
        }
    }

    function onMoreKey(event) {
        if (event.key === 'Escape') {
            closeMenus();
        }
    }

    function onConfirm(event) {
        var form = event.target;
        if (!form || !form.getAttribute) {
            return;
        }
        var message = form.getAttribute('data-pk-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    }

    function money(raw) {
        var digits = String(Math.abs(parseInt(raw, 10) || 0));
        if (digits.length > 3) {
            var tail = digits.slice(-3);
            var head = digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',');
            digits = head + ',' + tail;
        }
        return '৳' + digits;
    }
}());

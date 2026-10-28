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

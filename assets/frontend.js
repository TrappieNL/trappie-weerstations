(function () {
    'use strict';

    var toolbar = document.querySelector('[data-compare-toolbar]');
    var checkboxes = Array.prototype.slice.call(document.querySelectorAll('[data-compare-station]'));

    if (!toolbar || !checkboxes.length) {
        return;
    }

    var count = toolbar.querySelector('[data-compare-count]');
    var compareButton = toolbar.querySelector('.trappie-compare-button');
    var maximum = Number(window.trappieFrontend.maxCompare) || 4;

    function selectedIds() {
        return checkboxes.filter(function (checkbox) {
            return checkbox.checked;
        }).map(function (checkbox) {
            return checkbox.value;
        });
    }

    function updateToolbar() {
        var ids = selectedIds();
        var canCompare = ids.length >= 2;
        var url = new URL(toolbar.getAttribute('data-compare-url'), window.location.href);

        if (ids.length) {
            url.searchParams.set('station_ids', ids.join(','));
        }

        toolbar.hidden = ids.length === 0;
        count.textContent = ids.length;
        compareButton.href = url.toString();
        compareButton.setAttribute('aria-disabled', canCompare ? 'false' : 'true');
        compareButton.classList.toggle('is-disabled', !canCompare);

        checkboxes.forEach(function (checkbox) {
            checkbox.disabled = ids.length >= maximum && !checkbox.checked;
        });
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (selectedIds().length > maximum) {
                checkbox.checked = false;
                window.alert(window.trappieFrontend.maxCompareMessage);
            }
            updateToolbar();
        });
    });

    compareButton.addEventListener('click', function (event) {
        if (compareButton.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
        }
    });

    updateToolbar();
}());

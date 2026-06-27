(function () {
    'use strict';

    var selectAll = document.getElementById('trappie-select-all');
    var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.trappie-candidate-checkbox'));

    if (!selectAll || !checkboxes.length) {
        return;
    }

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
    });

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var selected = checkboxes.filter(function (item) {
                return item.checked;
            }).length;

            selectAll.checked = selected === checkboxes.length;
            selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
        });
    });
}());

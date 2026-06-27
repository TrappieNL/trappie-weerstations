(function () {
    'use strict';

    function initializeBulkSelection() {
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
    }

    function initializeGallery() {
        var field = document.querySelector('[data-gallery-field]');

        if (!field || typeof window.wp === 'undefined' || !window.wp.media) {
            return;
        }

        var input = field.querySelector('[data-gallery-input]');
        var list = field.querySelector('[data-gallery-list]');
        var addButton = field.querySelector('[data-add-images]');
        var mediaFrame;

        function updateInput() {
            var ids = Array.prototype.map.call(list.querySelectorAll('[data-image-id]'), function (item) {
                return item.getAttribute('data-image-id');
            });
            input.value = ids.join(',');
        }

        function addImage(attachment) {
            if (list.querySelector('[data-image-id="' + attachment.id + '"]')) {
                return;
            }

            var sizes = attachment.attributes.sizes || {};
            var preview = sizes.thumbnail ? sizes.thumbnail.url : attachment.attributes.url;
            var item = document.createElement('li');
            var image = document.createElement('img');
            var removeButton = document.createElement('button');

            item.setAttribute('data-image-id', attachment.id);
            image.src = preview;
            image.alt = attachment.attributes.alt || '';
            removeButton.type = 'button';
            removeButton.className = 'button-link-delete';
            removeButton.setAttribute('data-remove-image', '');
            removeButton.setAttribute('aria-label', window.trappieAdmin.removeImage);
            removeButton.textContent = 'Verwijderen';

            item.appendChild(image);
            item.appendChild(removeButton);
            list.appendChild(item);
        }

        addButton.addEventListener('click', function () {
            if (!mediaFrame) {
                mediaFrame = window.wp.media({
                    title: window.trappieAdmin.mediaTitle,
                    button: { text: window.trappieAdmin.mediaButton },
                    library: { type: 'image' },
                    multiple: true
                });

                mediaFrame.on('select', function () {
                    mediaFrame.state().get('selection').each(addImage);
                    updateInput();
                });
            }

            mediaFrame.open();
        });

        list.addEventListener('click', function (event) {
            var removeButton = event.target.closest('[data-remove-image]');

            if (!removeButton) {
                return;
            }

            event.preventDefault();
            removeButton.closest('[data-image-id]').remove();
            updateInput();
        });
    }

    initializeBulkSelection();
    initializeGallery();
}());

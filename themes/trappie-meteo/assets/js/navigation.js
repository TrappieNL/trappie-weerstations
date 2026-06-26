(function () {
    'use strict';

    var button = document.querySelector('.menu-toggle');
    var navigation = document.querySelector('.primary-navigation');

    if (!button || !navigation) {
        return;
    }

    button.addEventListener('click', function () {
        var isOpen = navigation.classList.toggle('is-open');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
        if (!navigation.classList.contains('is-open') || navigation.contains(event.target) || button.contains(event.target)) {
            return;
        }

        navigation.classList.remove('is-open');
        button.setAttribute('aria-expanded', 'false');
    });
}());

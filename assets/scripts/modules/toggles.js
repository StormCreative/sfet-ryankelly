define(['jquery'], function () {

    function Toggles() {

        // The area to toggle up and down
        this.toggleArea = 'data-toggle';

        // The class for the toggle icon
        this.toggleIconClass = 'toggler--open';

        // Our nav icon class in its original state
        this.navIconClass = 'nav__icon';

        // The class for showing the nav is active
        this.activeIconClass = 'nav__icon--active';

    }

    /**
     * Toggles a div area up and down
     * @param {object} element
     */
    Toggles.prototype.toggle = function (element) {

        var area = element.attr(this.toggleArea);

        $('#js-' + area).toggle();
    }

    /**
     * Slide toggles a div area up and down
     * @param {object} element
     */
    Toggles.prototype.slideToggle = function (element) {

        var area = element.attr(this.toggleArea);

        $('#js-' + area).slideToggle();
    }

    /**
     * Toggleit = Toggles an area and change the text
     * @param {object} element
     */
    Toggles.prototype.toggleIt = function (element) {

        var area = element.attr(this.toggleArea),
            elementTxt = element.attr('data-text'),
            elementOriTxt = element.attr('data-oritext');

        $('.js-' + area).slideDown(600);

        element.toggleClass('active');

        function activeState() {
            if (element.hasClass('active')) {
                element.text(elementTxt);
            } else {
                element.text(elementOriTxt);
                $('.js-' + area).slideUp(600);
            }
        }

        activeState();
    }

    /**
     * Adds an active class of an element to show its active
     * @param {object} element
     */
    Toggles.prototype.activeIcon = function (elem) {

        if (!!elem.attr('data-active')) {

            var elem = $('.' + this.navIconClass);

            if (elem.hasClass(this.activeIconClass)) {
                elem.removeClass(this.activeIconClass);
            } else {
                elem.addClass(this.activeIconClass);
            }
        }
    }

    Toggles.prototype.activeAdd = function (elem) {
        var dataActive = (!elem.attr('data-active') || elem.attr('data-active') == 'false' ? 'true' : 'false');
        elem.attr('data-active', dataActive);

        if (elem.attr('data-active') == 'true') {
            elem.addClass(this.activeIconClass);
        } else {
            elem.removeClass(this.activeIconClass);
        }
    }

    /**
     * Switches the class of an element to change an icon
     * @param {object} element
     */
    Toggles.prototype.switchIcon = function (elem) {

        if (!!elem.attr('data-type')) {
            if (elem.hasClass(this.toggleIconClass)) {
                elem.removeClass(this.toggleIconClass);
            } else {
                elem.addClass(this.toggleIconClass);
            }
        }
    }

    return Toggles;

});
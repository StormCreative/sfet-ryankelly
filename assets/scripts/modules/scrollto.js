define(['jquery'], function () {

    function ScrollTo() {

        // The area to scroll to
        this.scrollArea = 'data-scroll';

        // The speed the area gets scrolled to
        this.scrollSpeed = 600;
    }

    /**
     * Scrolls to the respected area
     * @param {object} element
     *
     * Element that will be clicked needs to have a data-attr of 'data-scroll'
     * with the value equal to the area id you wish to scroll to without the js- infront.
     *
     * eg: data-area="content" then the id of the area we want to scroll to will be id="js-content"
     */
    ScrollTo.prototype.scrollPos = function (element) {

        var area = element.attr(this.scrollArea),
            offset;

        offset = $('#js-' + area).offset().top;

        // IF document top is not equal to the area offset then animate scroll
        if ($(document).scrollTop() != offset) {
            $('html, body').animate({
                scrollTop: offset
            }, this.scrollSpeed);
        }

    }

    return ScrollTo;

});
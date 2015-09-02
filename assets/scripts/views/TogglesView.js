define(['Backbone', '../modules/toggles'], function (Backbone, Toggles) {

    return Backbone.View.extend({

        initialize: function () {

            TogIt = new Toggles();
            
        },

        el: $('body'),

        events: {
            'click .js-toggle' : 'toggle'
        },

        toggle: function(e) {
            var element = $(e.target);
            var parent = element.parent();

            if (parent.hasClass('js-toggle')) {
                element = parent;
            }

            TogIt.toggle(element);
           
            e.preventDefault();
        }
    });

});
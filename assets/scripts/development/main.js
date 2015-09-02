requirejs.config({
    urlArgs: "bust=" + (new Date()).getTime(),
    paths: {
        Backbone: '../utils/backbone.min',
        jquery: '../utils/jquery.min',
        skrollr: '../plugins/skrollr.min',
        slick: '../utils/slick.min',
        async: '../plugins/async'
    },
    shim: {
        'Backbone': {
            deps: ['../utils/lodash.min', 'jquery'], // load dependencies
            exports: 'Backbone' // use the global 'Backbone' as the module value
        }
    }
});


require(['../views/TogglesView'], function (TogglesView) {

    var Tog = new TogglesView();

});
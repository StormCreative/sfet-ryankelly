requirejs.config({
	urlArgs: "bust=" + (new Date()).getTime(),
	paths: {
		Backbone: '../utils/backbone',
		jquery: '../utils/jquery',
		jqueryui: '../utils/jqueryui'
	},
	shim: {
		'Backbone': {
			deps: ['../utils/lodash', 'jquery', 'jqueryui'], // load dependencies
			exports: 'Backbone' // use the global 'Backbone' as the module value
		}
	}
});

require(['../views/TogglesView', '../views/FormsView', '../utils/jquery.placeholder'], function (TogglesView, FormsView, Placeholder) {
	var Toggles = new TogglesView(),
		Forms = new FormsView();

	$('input').placeholder();
	$('textarea').placeholder();

	if ($(".js-datepicker").length>0) { 
		$(".js-datepicker").datepicker({
			dateFormat: 'dd-mm-yy',
			showOn: "both"
		});
	}
});
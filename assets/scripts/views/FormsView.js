define(['Backbone', '../modules/form'], function (Backbone, FormHandler) {
	return Backbone.View.extend({
		initialize: function () {
			Form = new FormHandler();
			var fields = new Array();
			var aJaxInputTimeout;

			if ($('.js-ajax-form-autoload').length>0) {
				if ($('.js-ajax-form-autoload').attr('data-loaded') != 1) this.aJaxCallFilter();
			}
		},

		el: $('body'),

		events: {
			'click [data-update-field]'			: 'updateField',			// update field on click
			'submit .js-ajax-form'				: 'aJaxCall',				// submits aJax based form
			'click .js-filter-click '			: 'aJaxClickFilter',		// called aJax form when filter clicked
			'keyup .js-filter-input'			: 'aJaxInputFilter',		// called aJax form when filter clicked
			'change .js-ajax-form-select'		: 'aJaxCallFilter',			// called aJax form when filter clicked
			'keyup [data-required]'				: 'errorHandler',			// checks if field empty
			'keydown [data-required="number"]'	: 'forceNumber',			// forces use of numbers
			'keydown [data-validate="number"]'	: 'forceNumber',			// forces use of numbers
			'keydown [data-validate="min"]'		: 'validateMin',			// checks if value is above min value
			'keydown [data-validate="max"]'		: 'validateMax',			// checks if value is below max value
			'keydown [data-validate="range"]'	: 'validateRange',			// checks input value between min and max values
			'keyup [data-required="range"]'		: 'validateRange',			// checks input value between min and max values
			'keyup [data-required="match"]'		: 'validateMatch',			// validated a value matches another value (useful for passwords)
			'keyup [data-required="minchar"]'	: 'validateMinCharacters',	// checks if value is above min value (based on character length)
			'keyup [data-required="maxchar"]'	: 'validateMaxCharacters',	// checks if value is below max value (based on character length)
			'keyup [data-required="check"]'		: 'validateCheck',			// checks if value is below max value (based on character length)
			'keyup [data-required="group"]'		: 'validateGroup',			// checks if value is below max value (based on character length)
			'change [data-required="group"]'	: 'validateGroup',			// checks if value is below max value (based on character length)
			'click [data-action="disable"]'		: 'disableField',			// disables a field on click
			'click [data-action="enable"]'		: 'enableField',			// enables a field on click
			'click [data-enable]'				: 'enableField',			// enables a field on click
			'click [data-for]'					: 'forField',				// mark field as checked when clicked
			'click [data-action="click"]'		: 'clickField',				// clicks a field on click
			'click [data-action="add_template"]': 'addTemplate',			// adds a template to a target on click
			'click [data-show]'					: 'showElement',			// show an element by id
			'click [data-hide]'					: 'hideElement',			// show an element by id
			'keyup [data-calculate]'			: 'recalculateTotal',		// recalculate total based on input
			'click [data-remove]'				: 'removeElement',			// removes target element
			'click [data-remove-attr]'			: 'removeElementAttr',		// removes target elements attribute
			'click [data-add-attr]'				: 'addElementAttr',			// adds target elements attribute
			'click [data-submit]'				: 'submitDelayed',			// submit form with delay
			'click [data-confirm]'				: 'confirmClick'			// confirms selection before action is processed
		},

		disableField: function(e) {
			var thisItem = $(e.target);
			var fieldName = thisItem.attr('data-field').toString();
			var fieldArrName = fieldName.replace(/[\[\]']+/g,'');
			var fieldChk = (fieldName.indexOf('[]') !== -1) ? fieldArrName : fieldName;

			if (typeof($('#'+fieldName)) != 'undefined' && $('#'+fieldName).length>0)
				var field = $('#'+fieldName);
			else if (typeof($('[name="'+fieldChk+'"]')) != 'undefined' && ($('[data-group="'+fieldChk+'"]').length>0 || $('[name="'+fieldChk+'"]').length>0))
				var field = (fieldName.indexOf('[]') !== -1) ?
						$('[data-group="'+fieldArrName+'"]')
					:
						$('[name="'+fieldName+'"]');
			else
				console.log('Match element not found!');

			if (typeof(field) != 'undefined' && field.length==1) {
				field.prop('disabled', true);
				field.val('');
				field.attr('data-required-disabled', field.attr('data-required'));
				field.removeAttr('data-required');

				if ($('#js-'+thisItem.attr('data-field')).length>0) 
					this.hideElement('#js-'+thisItem.attr('data-field')+'.form__error.show');

				if ($('#js-check-'+thisItem.attr('data-field')).length>0)
					this.hideElement('#js-check-'+thisItem.attr('data-field')+'.form__error.show');
			} else if (typeof(field) != 'undefined' && field.length>1) {
				for (var i=0, l=field.length; i<l; i++) {
					$(field[i]).prop('disabled', true);
					$(field[i]).val('');
					$(field[i]).removeAttr('data-required');

					if ($('#js-'+thisItem.attr('data-field')).length>0) 
						this.hideElement('#js-'+thisItem.attr('data-field')+'.form__error.show');

					if ($('#js-check-'+thisItem.attr('data-field')).length>0)
						this.hideElement('#js-check-'+thisItem.attr('data-field')+'.form__error.show');
				}
			}
		},

		enableField: function(e) {
			var thisItem = $(e.target);
			var fieldName = thisItem.attr('data-field');
			if (typeof(fieldName) == 'undefined') {
				fieldName = thisItem.attr('data-enable');
			}
			var fieldArrName = fieldName.replace(/[\[\]']+/g,'');
			var fieldChk = (fieldName.indexOf('[]') !== -1) ? fieldArrName : fieldName;

			fieldName = fieldName.toString();

			if (typeof($('#'+fieldChk)) != 'undefined' && $('#'+fieldChk).length>0)
				var field = $('#'+fieldName);
			else if (typeof($('[name="'+fieldChk+'"]')) != 'undefined' && ($('[data-group="'+fieldChk+'"]').length>0 || $('[name="'+fieldChk+'"]').length>0 ))
				var field = (fieldName.indexOf('[]') !== -1) ?
						$('[data-group="'+fieldArrName+'"]')
					:
						$('[name="'+fieldName+'"]');
			else
				console.log('Match element not found!');

			if (typeof(field) != 'undefined') {
				field.prop('disabled', false);
				if (typeof(field.attr('data-required-disabled')) == 'undefined') {
					if (typeof(field.attr('data-required-disabled')) == 'undefined') {
						field.attr('data-required', field.attr('data-required'));
					} else {
						field.attr('data-required', 'check');
					}
				} else {
					field.attr('data-required', field.attr('data-required-disabled'));
				}
				field.removeAttr('data-required-disabled');
				if (typeof($('#'+fieldChk)) != 'undefined' && $('#'+fieldChk).length>0)
					$('#'+fieldName).focus();
				else if (typeof($('[name="'+fieldChk+'"]')) != 'undefined' && ($('[data-group="'+fieldChk+'"]').length>0 || $('[name="'+fieldChk+'"]').length>0 ))
					if ((fieldName.indexOf('[]') !== -1))
						$('[data-group="'+fieldArrName+'"]:first-child').focus();
					else
						$('[name="'+fieldName+'"]:first-child').focus();
			}
		},

		clickField: function(e) {
			var thisItem = $(e.target);
			var fieldName = thisItem.attr('data-field').toString();

			$('#'+fieldName).trigger('click');
		},

		forField: function(e) {
			var thisItem = $(e.target);
			var fieldName = thisItem.attr('data-for').toString();

			$('#'+fieldName).trigger('click');
		},

		removeElement: function(e) {
			var thisItem = $(e.target);
			var element = thisItem.attr('data-remove');

			if (typeof(element) != 'undefined') {
				thisItem.parents(element).remove();
			} else {
				thisItem.remove();
			}
		},

		addTemplate: function(e) {
			var thisItem = $(e.target);
			var target = thisItem.attr('data-target');
			var template = thisItem.attr('data-template');
			var group = thisItem.attr('data-add-group');
			var templateId = template.replace(/\[(.+?)\]/g, '');
			var findTemplate = template.match(/[^[\]]+(?=])/g);
			var targetId = target.replace(/\[(.+?)\]/g, '');
			var findTarget = target.match(/[^[\]]+(?=])/g);

			var targetElm = (typeof(findTarget) != 'undefined' && findTarget!=null && findTarget.length>0) ?
					$('#'+targetId).find(findTarget[0])
				:
					$('#'+targetId);

			var templateElm = (typeof(findTemplate) != 'undefined' && findTemplate!=null && findTemplate.length>0) ?
					$('#'+templateId).find(findTemplate[0]).clone()
				:
					$('#'+templateId).clone();

			if (typeof(group) != 'undefined')
				templateElm.find('[data-group=""]').attr('data-group', group).attr('data-required','group');

			var html = $('<div>').append(templateElm).html();

			targetElm.append(html);
		},

		// show input error
		showElement: function(elm) {
			var thisItem = $(elm.target);
			var showElm = thisItem.attr('data-show');
			var noDelay = thisItem.attr('data-show-nodelay');
			var delay = 300;
			if (typeof(showElm)!='undefined') {
				var elm = $('#'+showElm+'.hide');
			}
			if (typeof(noDelay)!='undefined') {
				delay = 0;
			}
			if (typeof(elm)!='undefined') {
				$(elm).stop().removeClass('hide').hide().slideDown(delay, function () {
					$(this).addClass('show');
				});
			}
		},

		// hide input error
		hideElement: function(elm) {
			var thisItem = $(elm.target);
			var groupChk = true;
			var showElm = thisItem.attr('data-hide');
			var noDelay = thisItem.attr('data-hide-nodelay');
			var groupElm = thisItem.attr('data-hide-group');
			var delay = 300;
			if (typeof(showElm)!='undefined') {
				var elm = $('#'+showElm+'.show');
			}
			if (typeof(noDelay)!='undefined') {
				delay = 0;
			}
			if (typeof(groupElm)!='undefined') {
				groupChk = false;
				var elmGroupChk = $('[data-show="'+groupElm+'"]:checked');
				if (elmGroupChk.length==0)
					groupChk = true;
			}
			if (typeof(elm)!='undefined' && groupChk) {
				$(elm).stop().removeClass('show').slideUp(delay, function () {
					$(this).addClass('hide');
				});
			}
		},

		recalculateTotal: function(e) {
			var thisItem = $(e.target);
			var sub_total = 0;
			var total = 0;
			var subTotalField = thisItem.attr('data-sub-field');
			var totalField = thisItem.attr('data-total-field');

			if (typeof(subTotalField) != 'undefined') {
				var subFields = $('[data-sub-field="'+subTotalField+'"][data-required]');
				if (subFields.length>0) {
					subFields.each(function () {
						if ($.isNumeric($(this).val())) {
							sub_total = parseFloat(sub_total)+parseFloat($(this).val());
						}
					});
					$('[name="'+subTotalField+'"]').val(parseFloat(sub_total));
				}
			}

			if (typeof(totalField) != 'undefined') {
				var totalFields = $('[data-total-field="'+totalField+'"][data-required]');
				if (totalFields.length>0) {
					totalFields.each(function () {
						if ($.isNumeric($(this).val())) {
							total = parseFloat(total)+parseFloat($(this).val());
						}
					});
				}
				$('[name="'+totalField+'"]').val(total);
			}
		},

		validateCheck: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();
			
		},

		// validate min characters
		validateMinCharacters: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();
			var minVal = parseFloat(thisItem.attr('data-min'));

			if ((thisVal.toString()).length>0) {
				if (thisVal.length >= minVal)
					this.hideElement('#js-minchar-'+thisItem.attr('name')+'.form__error.show');
				else
					this.showElement('#js-minchar-'+thisItem.attr('name')+'.form__error.hide');
			} else
				this.hideElement('#js-minchar-'+thisItem.attr('name')+'.form__error.show');
		},

		validateMaxCharacters: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();
			var maxVal = parseFloat(thisItem.attr('data-max'));

			if ((thisVal.toString()).length>0) {
				if (thisVal.length <= maxVal)
					this.hideElement('#js-maxchar-'+thisItem.attr('name')+'.form__error.show');
				else
					this.showElement('#js-maxchar-'+thisItem.attr('name')+'.form__error.hide');
			} else
				this.hideElement('#js-maxchar-'+thisItem.attr('name')+'.form__error.show');
		},

		validateMatch: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();
			if (typeof($('#'+thisItem.attr('data-match'))) != 'undefined' && $('#'+thisItem.attr('data-match')).length>0)
				var matchVal = $('#'+thisItem.attr('data-match')).val();
			else if (typeof($('[name='+thisItem.attr('data-match')+']')) != 'undefined' && $('[name='+thisItem.attr('data-match')+']').length>0)
				var matchVal = $('[name='+thisItem.attr('data-match')+']').val();
			else
				console.log('Match element not found!');

			if ((thisVal.toString()).length>0) {
				if (thisVal != matchVal)
					this.showElement('#js-match-'+thisItem.attr('name')+'.form__error.hide');
				else
					this.hideElement('#js-match-'+thisItem.attr('name')+'.form__error.show');
			} else
				this.hideElement('#js-match-'+thisItem.attr('name')+'.form__error.show');
		},

		validateMin: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();

			// Check if event key code is unrecognized and only allow one period (.)
			if (typeof(e.keyCode) == 'undefined' || (thisVal.indexOf('.') !== -1 && e.keyCode == 190)) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}

			if (
				// Allow: backspace, delete, tab, escape, enter and .
				$.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
				// Allow: Ctrl+A, Command+A
				(e.keyCode == 65 && (e.ctrlKey === true || e.metaKey === true)) || 
				// Allow: home, end, left, right, down, up
				(e.keyCode >= 35 && e.keyCode <= 40)
			) {
				return;
			}

			// Ensure that it is a number otherwise stop the key press
			if ((e.keyCode != 46 && e.keyCode != 45 && e.keyCode > 31 && (e.keyCode < 48 || e.keyCode > 57))) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}

			var min = parseFloat(thisItem.attr('data-min'));

			if (thisVal < min)
				this.showElement('#js-min-'+thisItem.attr('name')+'.form__error.show');
			else
				this.hideElement('#js-min-'+thisItem.attr('name')+'.form__error.show');
		},

		validateMax: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();

			// Check if event key code is unrecognized and only allow one period (.)
			if (typeof(e.keyCode) == 'undefined' || (thisVal.indexOf('.') !== -1 && e.keyCode == 190)) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}

			if (
				// Allow: backspace, delete, tab, escape, enter and .
				$.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
				// Allow: Ctrl+A, Command+A
				(e.keyCode == 65 && (e.ctrlKey === true || e.metaKey === true)) || 
				// Allow: home, end, left, right, down, up
				(e.keyCode >= 35 && e.keyCode <= 40)
			) {
				return;
			}

			// Ensure that it is a number otherwise stop the key press
			if ((e.keyCode != 46 && e.keyCode != 45 && e.keyCode > 31 && (e.keyCode < 48 || e.keyCode > 57))) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}

			var max = parseFloat(thisItem.attr('data-max'));

			if (thisVal > max)
				this.showElement('#js-max-'+thisItem.attr('name')+'.form__error.show');
			else
				this.hideElement('#js-max-'+thisItem.attr('name')+'.form__error.show');
		},

		validateRange: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();

			// Check if event key code is unrecognized and only allow one period (.)
			if (typeof(e.keyCode) == 'undefined' || (thisVal.indexOf('.') !== -1 && e.keyCode == 190)) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}

			if (
				// Allow: backspace, delete, tab, escape, enter and .
				$.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
				// Allow: Ctrl+A, Command+A
				(e.keyCode == 65 && (e.ctrlKey === true || e.metaKey === true)) || 
				// Allow: home, end, left, right, down, up
				(e.keyCode >= 35 && e.keyCode <= 40)
			) {
				return;
			}

			// Ensure that it is a number otherwise stop the key press
			if ((e.keyCode != 46 && e.keyCode != 45 && e.keyCode > 31 && (e.keyCode < 48 || e.keyCode > 57))) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}

			var min = parseFloat(thisItem.attr('data-min'));
			var max = parseFloat(thisItem.attr('data-max'));
			var maxlength = parseFloat(thisItem.attr('maxlength'));

			console.log(parseFloat(thisVal));
			console.log(parseFloat(max));
			if (parseFloat(thisVal) < parseFloat(min) || parseFloat(thisVal) > parseFloat(max)) {
				console.log('hello');
				if (parseFloat(thisVal) > parseFloat(max)) {
					console.log('woo way to high');
					thisItem.val(parseFloat(max));
				} else if (parseFloat(thisVal).length < parseFloat(min)) {
					thisItem.val(parseFloat(min));
				}
			}
			if (typeof(maxlength) != 'undefined') {
				console.log(thisVal.length+1);
				console.log(maxlength);
				if (thisVal.length+1 > maxlength)
					thisItem.val(thisVal.substring(0,parseInt(maxlength)-1));
			}
		},

		forceNumber: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();

			// Check if event key code is unrecognized and only allow one period (.)
			if (typeof(e.keyCode) == 'undefined' || (thisVal.indexOf('.') !== -1 && e.keyCode == 190)) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}

			if (
				// Allow: backspace, delete, tab, escape, enter and .
				$.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
				// Allow: Ctrl+A, Command+A
				(e.keyCode == 65 && (e.ctrlKey === true || e.metaKey === true)) || 
				// Allow: home, end, left, right, down, up
				(e.keyCode >= 35 && e.keyCode <= 40)
			) {
				return;
			}

			// Ensure that it is a number otherwise stop the key press
			if ((e.keyCode != 46 && e.keyCode != 45 && e.keyCode > 31 && (e.keyCode < 48 || e.keyCode > 57))) {
				return false;
				if (!e.defaultPrevented) e.preventDefault();
			}
		},

		validateGroup: function(e) {
			var thisItem = $(e.target);
			var checkData = false;
			var thisGroup = $('[data-group='+thisItem.attr('data-group')+']');
			var thisAltGroup = $('[data-group='+thisItem.attr('data-alt-group')+']');

			var isEmpty = false;
			var isAltEmpty = false;
			if (typeof(thisItem.attr('data-alt-group')) != 'undefined') {
				for (var i=0, l=thisAltGroup.length; i<l; i++) {
					if (((thisAltGroup[i].value).toString()).length==0)
						isAltEmpty = true;
				}
			}
			if (
				(typeof(thisItem.attr('data-alt-group')) != 'undefined' && isAltEmpty==true) ||
				typeof(thisItem.attr('data-alt-group')) == 'undefined'
			) {
				for (var i=0, l=thisGroup.length; i<l; i++) {
					if (((thisGroup[i].value).toString()).length==0)
						isEmpty = true;
				}
			}

			if (typeof(thisItem.attr('data-check')) != 'undefined') {
				checkData = true;
				var dataChk = thisItem.attr('data-check');
				var checkId = dataChk.replace(/\[(.+?)\]/g, '');
				var findVal = dataChk.match(/[^[\]]+(?=])/g);

				var chkElm = (typeof(findVal) != 'undefined' && findVal!=null && findVal.length>0) ?
					$('input[name='+checkId+'[]][value="'+findVal[0]+'"]')
				:
					$('input[name='+checkId+'[]]');
			}

			if ((typeof(chkElm)!='undefined' && chkElm.is(':checked')) || checkData == false) {
				if (!isEmpty || (typeof(thisItem.attr('data-alt-group')) != 'undefined' && !isAltEmpty))
					this.hideElement('#js-'+thisItem.attr('data-group')+'.form__error.show');
				else
					this.showElement('#js-'+thisItem.attr('data-group')+'.form__error.hide');
			} else
				this.hideElement('#js-'+thisItem.attr('data-group')+'.form__error.show');
		},

		errorHandler: function(e) {
			var thisItem = $(e.target);
			var thisVal = thisItem.val();

			if ((thisVal.toString()).length>0)
				this.hideElement('#js-'+thisItem.attr('name')+'.form__error.show');
			else
				this.showElement('#js-'+thisItem.attr('name')+'.form__error.hide');
		},

		addElementAttr: function(e) {
			var thisItem = $(e.target);
			var thisForm = thisItem.parents('form');
			var thisAttr = thisItem.attr('data-add-attr');

			thisForm.find('input,textarea').attr(thisAttr);
		},

		removeElementAttr: function(e) {
			var thisItem = $(e.target);
			var thisForm = thisItem.parents('form');
			var thisAttr = thisItem.attr('data-remove-attr');

			thisForm.find('['+thisAttr+']').removeAttr(thisAttr);
		},

		updateField: function (e) {
			var thisItem = $(e.target);
			var thisForm = thisItem.parents('form');
			var field = thisItem.attr('data-update-field');
			var name = thisItem.attr('data-update-name');
			var value = thisItem.attr('data-update-value');
			var startvalue = thisItem.attr('data-start-value');

			if (typeof(value) != 'undefined' && (value.toString()).length==0 && typeof(startvalue) != 'undefined') {
				value = startvalue;
			}

			if (typeof($('#'+field)) != 'undefined') {
				thisForm.find('#'+field).val(value);
				if (typeof(name) != 'undefined') {
					thisForm.find('#'+field).attr('name', name);
				}
				if (value == 'asc') {
					thisItem.attr('data-update-value', 'desc');
				} else if (value == 'desc') {
					thisItem.attr('data-update-value', 'asc');
				}
			} else {
				thisForm.find('[name='+field+']').val(value);
				if (value == 'asc') {
					thisItem.attr('data-update-value', 'desc');
				} else if (value == 'desc') {
					thisItem.attr('data-update-value', 'asc');
				}
			}
		},

		aJaxInputFilter: function(e) {
			if (this.aJaxInputTimeout) clearTimeout(this.aJaxInputTimeout);

			var aJaxForm = this;
			this.aJaxInputTimeout = setTimeout(function () {
				aJaxForm.aJaxCallFilter(e, true);
			}, 800);
		},

		submitDelayed: function (e) {
			var thisItem = $(e.target);
			var delay = thisItem.attr('data-submit');

			var aJaxForm = this;

			setTimeout(function () {
				aJaxForm.aJaxCallFilter(e, true);
			}, parseInt(delay));
		},

		confirmClick: function (e) {
			var thisItem = $(e.target);
			var confirm_msg = thisItem.attr('data-confirm');
			var href = thisItem.attr('data-href');

			if (typeof(confirm_msg) == 'undefined') {
				confirm_msg = 'Are you sure you want to do this?';
			}

			if (confirm(confirm_msg)) {
				if (typeof(href) != 'undefined') {
					window.document.location.href = href;
				}
			} else {
				if (typeof(e)!='undefined' && !e.defaultPrevented)
					e.preventDefault();
			}
		},

		aJaxClickFilter: function (e) {
			var thisItem = $(e.target);
			var itemVal = thisItem.attr('data-value');
			var itemChecked = thisItem.attr('data-checked');
			var itemClass = thisItem.attr('data-class');

			if (typeof(itemVal) != 'undefined' && typeof(itemChecked) != 'undefined' && typeof(itemClass) != 'undefined') {
				if (parseInt(itemVal) === 1) {
					thisItem.removeClass(itemClass);
					thisItem.attr('data-value', '0');
				} else if (parseInt(itemVal) === 0) {
					thisItem.addClass(itemClass);
					thisItem.attr('data-value', '1');
				}
			}

			var aJaxForm = this;

			setTimeout(function () {
				aJaxForm.aJaxCallFilter(e, true);
			}, 500);
		},

		// called when a user click a filter
		aJaxCallFilter: function(e, ignoreDefault) {
			if (typeof(ignoreDefault) == 'undefined') ignoreDefault = false;
			if (typeof(e)!='undefined' && !e.defaultPrevented && !ignoreDefault)
				e.preventDefault();
			else if (typeof(e) == 'undefined')
				var e = $('form.js-ajax-form');

			if (typeof(e.target) == 'undefined')
				var thisForm = (typeof(e)!='undefined' || ignoreDefault) ? $('form.js-ajax-form') : $('.js-ajax-form-autoload');
			else
				var thisForm = (typeof(e)!='undefined') ? $(e.target).parents('form.js-ajax-form') : $('.js-ajax-form-autoload');

			$('.js-ajax-form-autoload').attr('data-loaded','1');

			var filesArr = new Array();
			var filtersArr = new Array();
			var fields = thisForm.find('input:not([type=submit],[type=button],[type=file]),textarea').serialize();
			var required_fields = thisForm.find('input[data-required]:not([type=submit],[type=button]),textarea[data-required]').serialize();
			var filters = thisForm.find('.js-ajax-form-filter');
			var files = thisForm.find('[type=file]');
			var data_load = thisForm.attr('data-load-id');

			if (filters.length>0) {
				var countArr = 0;
				$(filters).each(function () {
					var thisItem = $(this);
					if (typeof(thisItem.attr('data-name')) != 'undefined') {
						var name = thisItem.attr('data-name');
					} else if (typeof(thisItem.attr('name')) != 'undefined') {
						var name = thisItem.attr('name');
					}
					if (typeof(thisItem.attr('data-value')) != 'undefined') {
						var value = thisItem.attr('data-value');
					} else if (typeof(thisItem.attr('value')) != 'undefined') {
						var value = thisItem.attr('value');
					}
					filtersArr[name] = $.isNumeric(value) ? parseFloat(value) : value.toString();
					countArr++;
				});
			}

			if (files.length>0) {
				for (var i=0, l=files.length; i<l; i++)
					filesArr[i] = files[i];
			}

			Form.aJax(thisForm, thisForm.attr('data-action'), fields, required_fields, filesArr, filtersArr, (typeof(data_load)!='undefined')?data_load:false);
		},

		// called when user submits form
		aJaxCall: function(e) {
			if (!e.defaultPrevented) e.preventDefault();

			var thisForm = $(e.target);
			var files = new Array();
			var fields = thisForm.find('input:not([type=submit],[type=button],[type=file]),textarea').serialize();
			var required_fields = thisForm.find('input[data-required]:not([type=submit],[type=button]),textarea[data-required]').serialize();

			if (thisForm.find('[type=file]').length>0) {
				for (var i=0, l=thisForm.find('[type=file]').length; i<l; i++) {
					files[i] = thisForm.find('[type=file]')[i];
				}
			}

			Form.aJax(thisForm, thisForm.attr('data-action'), fields, required_fields, files);
		}
	});
});
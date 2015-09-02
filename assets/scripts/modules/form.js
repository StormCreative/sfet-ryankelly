define(['jquery'], function () {
	function FormHandler() {
		/**
		 * URL in which to make the request to
		 */
		this.url = '';

		/**
		 * Array of fields and values to be sent
		 */
		this.fieldsArr = new Array();
	}

	FormHandler.prototype.aJax = function (formElm, url, fields, required_fields, files, filters, data_load) {
		var filtersOut = new Array();
		for (key in filters) {
			filtersOut.push(key + '=' + encodeURIComponent(filters[key]));
		}
		filters = filtersOut.join('&');

		if (files.length>0) {
			var formData = new FormData();
			formData.append('fields', fields);
			formData.append('required_fields', required_fields);
			formData.append('filters', filters);
			for (var i=0, l=files.length; i<l; i++) {
				formData.append(files[i].name, files[i].files[0]);
			}
		}

		var loadingElm = formElm.find('.js-form-loading');
		if (loadingElm.length>0) loadingElm.clearQueue().slideDown(100).removeClass('hide').addClass('show');

		$.ajax({
			url: url,
			data: (files.length>0)?formData:{
				fields: fields,
				required_fields: required_fields,
				filters: filters
			},
			type: "POST",
			cache: false,
			processData: (files.length>0)?false:true,
			contentType: (files.length>0)?false:'application/x-www-form-urlencoded',
			success: function (response) {
				var response = JSON.parse(response);
				if (loadingElm.length>0) loadingElm.clearQueue().slideUp(300).addClass('hide').removeClass('show');
				formElm.find('.form__error.show').clearQueue().slideUp(0).removeClass('show').addClass('hide');
				if (response.status == 'success') {
					var addAttr = formElm.attr('data-success-attr');
					var showElm = formElm.attr('data-success-show');
					var hideElm = formElm.attr('data-success-hide');
					if (typeof(addAttr)!='undefined') {
						formElm.find('input:not([type=submit],[type=button],[type=file]),textarea').attr(addAttr,'');
					}
					if (typeof(showElm)!='undefined') {
						formElm.find('#'+showElm+'.hide').slideDown(0, function () {
							$(this).removeClass('hide').addClass('show');
						});
					}
					if (typeof(hideElm)!='undefined') {
						formElm.find('#'+hideElm+'.show').slideUp(0, function () {
							$(this).removeClass('show').addClass('hide');
						});
					}
					if (typeof(response.redirect) != 'undefined') {
						window.document.location.href=response.redirect;
					} else if (typeof(response.msg) != 'undefined') {
						formElm.find('#js-success-msg').removeClass('hide').addClass('show');
						formElm.find('#js-success-msg').removeClass('hide').html(response.msg);
						if (typeof(response['data']) == 'undefined' && data_load == false)
							formElm.find('input,textarea').prop('disabled', true);
					} else {
						formElm.find('#js-success-msg').removeClass('hide').addClass('show');
						if (typeof(response['data']) == 'undefined' && data_load == false)
							formElm.find('input,textarea').prop('disabled', true);
					}
					if (typeof(response['data']) != 'undefined' && data_load != false) {
						var dataLoad = formElm.find('#'+data_load);
						if (dataLoad[0].localName == 'table') {
							dataLoad.find('tbody,tfoot').remove();
						}
						dataLoad.append(response['data']);
					}
				} else if (response.status == 'error') {
					if (typeof(response['fields']) != 'undefined') {
						if (typeof(response.msg) != 'undefined' && !$.isArray(response['fields'])) {
							formElm.find('#js-error-'+response['fields']).clearQueue().slideDown(300).removeClass('hide').addClass('show').html(response.msg);
						} else if ($.isArray(response['fields']) && response['fields'].length>0) {
							for (var i=0,l=response['fields'].length; i<l; i++) {
								formElm.find('#js-'+response['fields'][i]).clearQueue().slideDown(300).removeClass('hide').addClass('show');
							}
						}
					} else {
						if (typeof(response.msg) != 'undefined') {
							formElm.find('#js-error-msg').removeClass('hide').html(response.msg);
						} else {
							formElm.find('#js-error-msg').removeClass('hide');
						}
					}
				}
			},
			error: function (err) {
				console.log('Error occurred: No connection or server unreachable');
				formElm.find('#js-error-msg').removeClass('hide').html('Problem submitting this form, please try again later!');
			}
		});
	}

	return FormHandler;
});
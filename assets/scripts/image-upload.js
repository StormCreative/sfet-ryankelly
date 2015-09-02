$(function(){
	
	var dropbox = $('#page-update-banner #dropbox'),
		message = $('.message', dropbox);

	dropbox.filedrop({
		// The name of the $_FILES entry:
		paramname:'pic',
		
		maxfiles: 5,
		maxfilesize: 20000000000,
		url: '/system/inc/upload-banner.php',
		
		uploadFinished:function(i,file,response){
			$.data(file).addClass('done');
			$('#page-update-banner div.banners').append('<img src="' + response.status + '"  title="Click to preview" onClick="$(\'#page-banner\').attr(\'src\', this.src); $(\'#page-banner-image\').attr(\'value\' , this.src);" />');
			// response is the JSON object that upload-banner.php returns
		},
		dragOver: function() {
			dropbox.css("border", "1px solid #143352");
		},
		dragLeave: function() {
			dropbox.css("border", "");
		},
		error: function(err, file) {
			switch(err) {
				case 'BrowserNotSupported':
					showMessage('Your browser does not support HTML5 file uploads!');
					break;
				case 'TooManyFiles':
					alert('Please upload only 5 files at a time!');
					break;
				case 'FileTooLarge':
					alert(file.name+' is too large!');
					break;
				default:
					break;
			}
		},
		
		// Called before each upload is started
		beforeEach: function(file){
			if(!file.type.match(/^image\//)){
				alert('Only images are allowed!');
				
				// Returning false will cause the
				// file to be rejected
				return false;
			}
		},
		
		uploadStarted:function(i, file, len){
			createImage(file);
		},
		
		progressUpdated: function(i, file, progress) {
			$.data(file).find('.progress').width(progress+'%');
		}
		
	});
	
	var template = '<div class="preview">'+
						'<span class="imageHolder">'+
							'<img />'+
							'<span class="uploaded">DONE</span>'+
						'</span>'+
						'<div class="progressHolder">'+
							'<div class="progress"></div>'+
						'</div>'+
					'</div>'; 
	
	
	function createImage(file){

		var preview = $(template), 
			image = $('img', preview);
			
		var reader = new FileReader();
		
		image.width = 100;
		image.height = 30;
		
		reader.onload = function(e){
			
			// e.target.result holds the DataURL which
			// can be used as a source of the image:
			
			image.attr('src',e.target.result);
		};
		
		// Reading the file as a DataURL. When finished,
		// this will trigger the onload function above:
		reader.readAsDataURL(file);
		
		message.hide();
		preview.appendTo(dropbox);
		
		// Associating a preview container
		// with the file, using jQuery's $.data():
		
		$.data(file,preview);
	}

	function showMessage(msg){
		message.html(msg);
	}

});
<?php
require_once(SYS_PATH.'/system/modules.inc.php');
require_once(SYS_PATH.'/system/inc/modules.inc.php');

// Get all page settings
$chkEditPage = false;
$productOnly = false;
$categoryOnly = false;
$pageSettings = new page_settings();
$pageSettings->getNonLive();
$pageSettings->setTheme(str_replace('/', '', $sysThemeFolder));
$pageSettings->getPage($urlReferer);
$pageSettings->getNonDeleted();
if ($pageSettings->LoadRecords()) {
	while ($pageSettings->read()) {
		if ($urlReferer == $pageSettings->f('path')) {
			$_SERVER['REQUEST_URI'] = $pageSettings->f('path');
			$chkEditPage = true;
		}
	}
}
$pageSettings->FreeResult();
unset($pageSettings);

if (!$chkEditPage || $deleted) {
	$page404 = true;
}
$noCreate = false;

$siteEditorLang = array(
	'EN' => 'UK&nbsp;English',
	'IT' => 'Italian',
	'DE' => 'German',
	'NL' => 'Dutch',
	'FR' => 'French'
);

require_once('uploader.js');
?>

// Check if layer exists
jQuery.fn.exists = function() {
	return this.length > 0;
}

// Add Module
function setupModules() {
	$('#page-blocks section.grid-block section.col select.add-module').each(function () {
		this.onchange = function () {
			var opSelLabel = $(this).find('option:selected').parent().attr('label');
			if (opSelLabel == 'New Module') {
				var dataType = $(this).val();
				var parentBlock = $(this).parents('.col');
				$(this).parents('.col').append('<section id="NEWBLOCK" data-type="'+dataType+'" class="block"></section>');
				$(this).parents('.col').find('.add-module').remove();
				var moduleName = $(this).find('option:selected').text();
				var moduleCacheId = $(this).find('option:selected').attr('name');
				var moduleCacheId = $(this).find('option:selected').attr('name');
				var moduleId = $(this).val();
				var htmlBlockData = $('#page-blocks');
				htmlBlockData = $('<div>'+htmlBlockData.html()+'</div>');
				htmlBlockData.find('.grid-block').removeAttr('style');
				htmlBlockData.find('.add-module').remove();
				htmlBlockData.find('.edit-grid-tools').remove();
				htmlBlockData.find('section.block').each(function () {
					$(this).removeAttr('style').empty().html('<'+"?=loadModule('"+$(this).attr('id')+"')?"+'>');
				});
				
				$.ajax({
					url: "/editor.php",
					type: 'POST',
					data: {
						pageModule: dataType,
						blockType: dataType,
						pageContent: htmlBlockData.html(),
						url: 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>'
					},
					cache: false,
					success: function (data) {
						if (data != "" && data != "failed") {
							parentBlock.find('.add-module').remove();
							$('#NEWBLOCK').attr('id', data);
							$('#'+data).addClass('block');
							parentBlock.append('<select class="add-module">'+
							'<option disabled selected>Add a Module</option>'+
							'<optgroup label="New Module">' +
							<?php
							if (is_array($modules)) {
								foreach ($modules as $key => $val) {
									?>
									'<option value="<?php echo $key; ?>"><?php echo $val; ?></option>'+
									<?php
								}
							}
							?>
							'</optgroup>'+
							<?php
							if (isset($savedModules) && is_array($savedModules)) {
								?>
								'<optgroup label="Saved Modules">' +
								<?php
								foreach ($savedModules as $key => $val) {
									?>
									'<option value="<?php echo $val[0]; ?>" name="<?php echo $val[2]; ?>" id="cache:<?php echo $key; ?>"><?php echo $val[1]; ?></option>'+
									<?php
								}
								?>
								'</optgroup>'+
								<?php
							}
							?>
							'</select>');
							setupModules();
							enableBlockEdit(data, true);
							popupWindow('module_'+moduleId, 850, 550, 'Module: '+moduleName, { "block-id" : data, "url" : 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>' });
						} else {
							changesFailed(data);
						}
					},
					error: function (data) {
						changesFailed('Connection Failure!');
					}
				});
			} else {
				var parentBlock = $(this).parents('.col');
				var moduleName = $(this).find('option:selected').text();
				var moduleCacheId = $(this).find('option:selected').attr('id');
				moduleCacheId = moduleCacheId.replace('cache:', '');
				var moduleCacheString = $(this).find('option:selected').attr('name');
				var moduleId = $(this).val();
				$(this).parents('.col').append('<section id="'+moduleCacheId+'" data-type="'+moduleId+'" class="block"></section>');
				$(this).parents('.col').find('.add-module').remove();
				var htmlBlockData = $('#page-blocks');
				htmlBlockData = $('<div>'+htmlBlockData.html()+'</div>');
				htmlBlockData.find('.grid-block').removeAttr('style');
				htmlBlockData.find('.add-module').remove();
				htmlBlockData.find('.edit-grid-tools').remove();
				htmlBlockData.find('section.block').each(function () {
					$(this).removeAttr('style').empty().html('<'+"?=loadModule('"+$(this).attr('id')+"')?"+'>');
				});
				$.ajax({
					url: "/editor.php",
					type: 'POST',
					data: {
						pageCacheModule: $(this).val(),
						blockCacheType: $(this).val(),
						blockCacheId: moduleCacheId,
						blockCacheString: moduleCacheString,
						pageCacheContent: htmlBlockData.html(),
						url: 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>'
					},
					cache: false,
					success: function (data) {
						if (data != "" && data != "failed") {
							parentBlock.find('.add-module').remove();
							parentBlock.append('<select class="add-module">'+
							'<option disabled selected>Add a Module</option>'+
							'<optgroup label="New Module">' +
							<?php
							if (is_array($modules)) {
								foreach ($modules as $key => $val) {
									?>
									'<option value="<?php echo $key; ?>"><?php echo $val; ?></option>'+
									<?php
								}
							}
							?>
							'</optgroup>'+
							<?php
							if (isset($savedModules) && is_array($savedModules)) {
								?>
								'<optgroup label="Saved Modules">' +
								<?php
								foreach ($savedModules as $key => $val) {
									?>
									'<option value="<?php echo $val[0]; ?>" name="<?php echo $val[2]; ?>" id="cache:<?php echo $key; ?>"><?php echo $val[1]; ?></option>'+
									<?php
								}
								?>
								'</optgroup>'+
								<?php
							}
							?>
							'</select>');
							setupModules();
							$('#'+moduleCacheId).html(data);
							enableBlockEdit(moduleCacheId, true);
							popupWindow('module_'+moduleId, 850, 550, 'Module: '+moduleName, { "block-id" : moduleCacheId, "url" : 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>' });
						} else {
							changesFailed(data);
						}
					},
					error: function (data) {
						changesFailed('Connection Failure!');
					}
				});
			}
		}
	});
}

// Saving Changes
function savingChanges() {
	$('#update-info').empty().html('Saving Changes...');
}
function changesSaved() {
	$('#update-info').empty().html('<b>Last Updated:</b> <span id="date_time"></span>');
	date_time('date_time');
}
function changesFailed(error) {
	$('#update-info').empty().html(error);
}
function getModuleName(name) {
	var moduleName = new Array();
	<?php
	if (is_array($modules)) {
		foreach ($modules as $key => $val) {
			?>
			moduleName['<?php echo $key; ?>'] = '<?php echo $val; ?>';
			<?php
		}
	}
	?>
	return moduleName[name];
}

// Edit Block
function setupEditing(id, block) {
	if (typeof(block) == 'undefined') block = false;
	if (typeof(id) == 'undefined')
			id = '';
		else
			id = 'section#' + id;
	if (block == true && id != 'section') {
		id = id;
	} else if (block == false && id == 'section')
		id = 'section.grid-block';
	
	if (block == false) {
		// Delete Grid Block
		$(id + ' a.grid-del').click(function () {
			var blockId = $(this).parents(".grid-block").attr('id');
			var htmlBlockData = $('#page-blocks');
			htmlBlockData = $('<div>'+htmlBlockData.html()+'</div>');
			htmlBlockData.find('.grid-block').removeAttr('style');
			htmlBlockData.find('#'+blockId+'.grid-block').remove();
			htmlBlockData.find('select.add-module').remove();
			htmlBlockData.find('div.edit-grid-tools').remove();
			htmlBlockData.find('section.block').each(function () {
				$(this).removeAttr('style').empty().html('<'+"?=loadModule('"+$(this).attr('id')+"')?"+'>');
			});
			var confirmDel = confirm("Are you sure you want to delete this grid block!\n");
			if (confirmDel == true) {
				savingChanges();
				$.ajax({
					url: "/editor.php",
					type: 'POST',
					data: {
						'delete-grid-block': 1,
						pageContent: htmlBlockData.html(),
						url: 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>'
					},
					cache: false,
					success: function (data) {
						if (data == "success") {
							changesSaved();
							$('#'+blockId).slideUp();
						} else {
							changesFailed(data);
						}
					},
					error: function (data) {
						changesFailed('Connection Failure!');
					}
				});
			}
			//popupWindow('page-delete-grid-block', 320, 100, 'Confirm Deletion', { "grid-block-id" : blockId });
		});
	}
	
	// Edit Block
	$(id + ' a.edit-block').click(function () {
		var blockId = $(this).parents(".block").attr('id');
		var moduleId = $(this).parents(".block").data('type');
		var moduleName = getModuleName(moduleId);
		popupWindow('module_'+moduleId, 850, 550, 'Module: '+moduleName, { "block-id" : blockId, "url" : 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>' });
	});
	
	// Delete Block
	$(id + ' a.del-block').click(function () {
		var blockId = $(this).parents(".block").attr('id');
		var confirmDel = confirm("Are you sure you want to delete this block!\n");
		if (confirmDel == true) {
			savingChanges();
			$.ajax({
				url: "/editor.php",
				type: 'POST',
				data: {
					'delete-block': 1,
					moduleId: blockId,
					url: 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>'
				},
				cache: false,
				success: function (data) {
					if (data == 'success') {
						changesSaved();
						$('#'+blockId).slideUp();
					} else {
						changesFailed(data);
					}
				},
				error: function (data) {
					changesFailed(data);
				}
			});
		}
	});
}
function enableEditing(id, block) {
	if (typeof(block) == 'undefined') block = false;
	if (block) {
		$('#page-blocks section.grid-block section#'+id+'.block').prepend('<div class="edit-block-tools"><a class="edit-block">Edit</a> | <a class="del-block">Delete</a></div>');
	} else {
		$('#page-blocks section#'+id+'.grid-block').prepend('<div class="edit-grid-tools drag-handler" title="Click here and drag to move this grid block"><a href="javascript: void(0);" class="grid-del">Delete</a></div>');
		$('#page-blocks section#'+id+'.grid-block section.col').each(function () {
			$(this).append('<select class="add-module">'+
			'<option disabled selected>Add a Module</option>'+
			'<optgroup label="New Module">' +
			<?php
			if (is_array($modules)) {
				foreach ($modules as $key => $val) {
					?>
					'<option value="<?php echo $key; ?>"><?php echo $val; ?></option>'+
					<?php
				}
			}
			?>
			'</optgroup>'+
			<?php
			if (isset($savedModules) && is_array($savedModules)) {
				?>
				'<optgroup label="Saved Modules">' +
				<?php
				foreach ($savedModules as $key => $val) {
					?>
					'<option value="<?php echo $val[0]; ?>" name="<?php echo $val[2]; ?>" id="cache:<?php echo $key; ?>"><?php echo $val[1]; ?></option>'+
					<?php
				}
				?>
				'</optgroup>'+
				<?php
			}
			?>
			'</select>');
		});
		$('#page-blocks section#'+id+'.grid-block section.col section.block').prepend('<div class="edit-block-tools"><a href="javascript: void(0);" class="edit">Edit</a> | <a href="javascript: void(0);" class="del">Delete</a></div>');
		setupModules();
	}
	setupEditing(id, block);
}
function enableBlockEdit(id) {
	enableEditing(id, true);
}
/* Popup Window */
function popupWindow(displayPage, popupWidth, popupHeight, popupTitle, customVars) {
	if (typeof(resize) == "undefined") var resize = true;
	if (!$('#'+ displayPage).exists()) {
		var popupId = 'popup-' + new Date().getTime();
		$('#popup').append(
			'	<div id="'+ popupId +'" class="popup_container">' +
			'		<div class="popupWindow">' +
			'			<div class="popupTitle" title="Move Window"><span class="popupText"></span> <div class="popupClose" title="Close">X</div></div>' +
			'			<div class="popupWindowFrame">' +
			'				<div class="popupStatus"><div><span></span></div></div>' +
			'				<div class="popupContent"></div>' +
			'			</div>' +
			'		</div>' +
			'	</div>'
		);
		if ($('.popup_container').length == 1) {
			$('#popup').css('display', 'block').hide().fadeIn(1000);
		}
		if ($('.popup_container').length == 1) {
			$('#container').fadeTo("slow", 0.5);
		}
		
		$('.popup_container').css('z-index', 9999);
		$('#'+ popupId).css('z-index', 10000);
		
		var popupWidthHalf = "-" + (popupWidth/2) + "px";
		var popupHeightHalf = "-" + (popupHeight/2) + "px";
		var popupWidth = popupWidth+"px";
		var popupHeight = popupHeight+"px";
		if (typeof(popupTitle) != 'undefined') {
			$('#'+ popupId +' div.popupTitle span.popupText').html(popupTitle);
		}
		if (typeof(popupTitle) != 'undefined') var popupTitle = true;
		if (popupTitle == false) {
			$('#'+ popupId +' div.popupTitle').hide();
		}
		if (typeof(customVars) == 'undefined') customVars = '';
		console.log('/dialog/' + displayPage);
		$('#'+ popupId +' div.popupContent').css('min-width', popupWidth);
		$('#'+ popupId +' div.popupContent').css('min-height', popupHeight);
		$('#'+ popupId +' div.popupContent').load('/dialog/' + displayPage, { "url" : 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>', "module" : displayPage, "vars" : customVars });
		$('#'+ popupId +'.popup_container').css('left', popupWidthHalf);
		$('#'+ popupId +'.popup_container').css('top', popupHeightHalf);
		$('#'+ popupId +' div.popupContent').css('width', '100%');
		$('#'+ popupId +' div.popupContent').css('height', '100%');
		$('#'+ popupId +' div.popupContent').css('min-width', popupWidth);
		$('#'+ popupId +' div.popupContent').css('min-height', popupHeight);
		$('#'+ popupId).width(parseInt(popupWidth.replace('px', '')));
		$('#'+ popupId).height(parseInt(popupHeight.replace('px', ''))+28);
		var heightOffset = $('#'+ popupId).height()-26;
		var popupWindows = $('.popup_container');
		var startIndex = 9999;
		$('#'+ popupId +' .popupWindow').height(heightOffset+28);
		$('#'+ popupId +' .popupWindow div.popupWindowFrame').height(heightOffset);
		for (i=0; i < popupWindows.length ; i++) {
			var curWindow = popupWindows[i].id;
			if (curWindow == popupId) {
				$('#'+ curWindow).css('z-index', startIndex);
				$('#'+ curWindow).focus();
				$('#'+ curWindow).addClass('focused');
			} else {
				$('#'+ curWindow).css('z-index', startIndex-1);
				$('#'+ curWindow).removeClass('focused');
			}
		}
		if ($('#'+ popupId).exists()) {
			$('#'+ popupId).draggable({
				containment: "#popup_holder",
				scroll: false,
				handle: '.popupTitle',
				start: function () {
					$(this).focus();
					var selPopupId = $(this).attr('id');
					var popupWindows = $('.popup_container');
					var startIndex = 9999;
					for (i=0; i < popupWindows.length ; i++) {
						var curWindow = popupWindows[i].id;
						if (curWindow == selPopupId) {
							$('#'+ curWindow).css('z-index', startIndex);
							$('#'+ curWindow).focus();
							$('#'+ curWindow).addClass('focused');
						} else {
							$('#'+ curWindow).css('z-index', startIndex-1);
							$('#'+ curWindow).removeClass('focused');
						}
					}
				}
			});
			if (resize == true) {
				$('#' + popupId).resizable({
					minHeight: parseInt(popupHeight.replace('px', ''))+28,
					minWidth: parseInt(popupWidth.replace('px', '')),
					start: function( event, ui ) {
						$('#'+ popupId).find('.ui-resizable-se').width(200);
						$('#'+ popupId).find('.ui-resizable-se').height(200);
						$('#'+ popupId).find('iframe').hide(1);
					},
					resize: function( event, ui ) {
						$('#'+ popupId).find('iframe').hide(1);
						heightOffset = $('#'+ popupId).height()-26;
						$('#'+ popupId +' .popupWindow').height(heightOffset+28);
						$('#'+ popupId +' .popupWindow div.popupWindowFrame').height(heightOffset-2);
					},
					stop: function( event, ui ) {
						$('#'+ popupId).find('iframe').show(1);
						$('#'+ popupId).find('.ui-resizable-se').width(12);
						$('#'+ popupId).find('.ui-resizable-se').height(12);
					}
				});
			}
		}
	}
}
function date_time(id) {
	date = new Date;
	year = date.getFullYear();
	month = date.getMonth();
	months = new Array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec');
	d = date.getDate();
	day = date.getDay();
	days = new Array('Sun', 'Mon', 'Tues', 'Wed', 'Thurs', 'Fri', 'Sat');
	h = date.getHours();
	if (h < 10) {
		h = "0"+h;
	}
	m = date.getMinutes();
	if (m < 10){
		m = "0"+m;
	}
	s = date.getSeconds();
	if (s < 10) {
		s = "0"+s;
	}
	result = ''+days[day]+' '+months[month]+' '+d+' '+year+' '+h+':'+m+':'+s;
	$('#' + id).html(result);
	//setTimeout('date_time("'+id+'");','1000');
	//return true;
}
$(document).ready(function () {
	// Check if layer exists
	jQuery.fn.exists = function() {
		return this.length > 0;
	}

	/* Popup */
	$('#popup').hide();
	
	// Enable Blocks to be moved
	if ($("#page-blocks").exists()) {
		$("#page-blocks").sortable({
			revert: true,
			handle: ".drag-handler",
			placeholder: "drop-zone",
			stop: function (event, ui) {
				savingChanges();
				var htmlBlockData = $('#page-blocks').html();
				htmlBlockData = $('<div>'+htmlBlockData+'</div>');
				$(htmlBlockData).find('.grid-block').removeAttr('style');
				$(htmlBlockData).find('select.add-module').remove();
				$(htmlBlockData).find('div.edit-grid-tools').remove();
				$(htmlBlockData).find('section.block').each(function () {
					$(this).removeAttr('style').empty().html('<'+"?=loadModule('"+$(this).attr('id')+"')?"+'>');
				});
				htmlBlockData = htmlBlockData.html();
				htmlBlockData = htmlBlockData.split('<!--?=loadModule').join('<'+'?=loadModule');
				htmlBlockData = htmlBlockData.split(')?-->').join(')?'+'>');
				var dataCont = 'url=' + 'http<?php echo chkSSL(); ?>://<?php echo SYS_DOMAIN.$_SERVER['REQUEST_URI']; ?>' + '&pageBlock='+encodeURIComponent(htmlBlockData)+'&moveblocks=1';
				$.ajax({
					url: "/editor.php",
					type: 'POST',
					data: dataCont,
					cache: false,
					success: function (data) {
						if (data == 'success') {
							changesSaved();
						} else {
							changesFailed(data);
						}
					},
					error: function (data) {
						changesFailed(data);
					}
				});
				// Re-enable edit options on the page
				enableEditOptions();
				setupEditing();
			}
		});
		$("#page-blocks .grid-block .drag-handler").disableSelection();
	}
	
	// Set Editable Blocks & Grid Block
	function enableEditOptions() {
		if (!$("#popup").exists()) {
			$("body").prepend(
				'<div id="popup_holder"></div>' +
				'<div id="popup" align="center" tabindex="1"></div>'
			);
		}
		if (!$("#edit-mode").exists()) {
			var buildEditorToolbar = '<div id="edit-mode"><div class="container">' +
				'	<ul class="menu">' +
				'		<li onClick="document.location.href = \'/\';" class="main_menu home"><span title="Home"></span></li>' +
				'		<li class="main_menu page_options">' +
				'			<span title="Page Options"></span>' +
				'			<ul>' +
				<?php if ($page404) { ?>
				'				<li><a href="?create_page=<?php echo substr($urlReferer, 1); ?>">Create&nbsp;Blank&nbsp;Page</a></li>' +
				<?php } else if ($bannerOnlyPage) { ?>
				'				<li><a class="page-update-banner">Update&nbsp;Banner</a></li>' +
				<?php } else if ($productOnly) { ?>
				'				<li><a class="product-edit">Edit&nbsp;Product</a></li>' +
				<?php } else if ($categoryOnly) { ?>
				'				<li><a class="category-edit">Edit&nbsp;Category</a></li>' +
				<?php } else if (!$chkEditPage) { ?>
				'				<li><a class="page-update-banner">Update&nbsp;Banner</a></li>' +
				'				<li><a class="page-settings">Page&nbsp;Settings</a></li>' +
				<?php } else { ?>
				'				<li><a class="page-add-new-block">Add&nbsp;New&nbsp;Grid&nbsp;Block</a></li>' +
				//'				<li><a class="page-clone">Clone&nbsp;Page</a></li>' +
				'				<li><a class="page-update-banner">Update&nbsp;Banner</a></li>' +
				'				<li><a class="page-settings">Page&nbsp;Settings</a></li>' +
				'				<li><a class="page-delete">Delete&nbsp;Page</a></li>' +
				<?php } ?>
				'			</ul>' +
				'		</li>' +
				'		<li class="main_menu page_content">' +
				'			<span title="Page Content"></span>' +
				'			<ul>' +
				'				<li><a class="content-media">Media&nbsp;Library</a></li>' +
				//'				<li><a class="content-events">Events&nbsp;Manager</a></li>' +
				'				<li><a class="content-menu">Menu&nbsp;/&nbsp;Navigation</a></li>' +
				//'				<li><a class="content-header-footer">Header&nbsp;/&nbsp;Footer</a></li>' +
				'				<li><a class="content-manage-news">Manage&nbsp;News</a></li>' +
				'				<li><a class="content-manage-reviews">Manage&nbsp;Reviews</a></li>' +
				'			</ul>' +
				'		</li>' +
				'		<li class="main_menu ecommerce">' +
				'			<span title="eCommerce"></span>' +
				'			<ul>' +
				'				<li><a class="ecommerce-orders-invoices">Orders&nbsp;/&nbsp;Invoices</a></li>' +
				'				<li><a class="ecommerce-customers">Customers</a></li>' +
				'				<li><a class="ecommerce-categories-products">Categories&nbsp;/&nbsp;Products</a></li>' +
				'				<li><a class="ecommerce-manager">eCommerce&nbsp;Manager</a></li>' +
				'			</ul>' +
				'		</li>' +
				'		<li class="main_menu website_manager">' +
				'			<span title="Website Manager"></span>' +
				'			<ul>' +
				'				<li><a class="site-add-new-page">Add&nbsp;New&nbsp;Page</a></li>' +
				'				<li><a class="site-manage-pages">Website&nbsp;Pages</a></li>' +
				'				<li><a class="site-manage-website">Manage&nbsp;Website</a></li>' +
				'				<li><a class="site-manage-users">Manage&nbsp;User&nbsp;Accounts</a></li>' +
				'			</ul>' +
				'		</li>' +
				'		<li class="main_menu about">' +
				'			<span title="About"></span>' +
				'			<ul>' +
				'				<li><a class="about-info">About&nbsp;Information</a></li>' +
				'			</ul>' +
				'		</li>' +
				'		<li class="main_menu logout"><?php echo $account_name; ?>' +
				'			<span title="Welcome"></span>' +
				'			<ul>' +
				'				<li><a class="account-my-details" target="_blank">My&nbsp;Account</a></li>' +
				'				<li><a class="account-my-password" target="_blank">Change&nbsp;My&nbsp;Password</a></li>' +
				'				<li><a class="account-logout" target="_blank">Logout</a></li>' +
				'			</ul>' +
				'		</li>' +
				'	</ul>' +
				'	<div id="update-info"><b>RSPS.NET</b></div>' +
				'</div></div>';
			
			$("body").prepend(buildEditorToolbar);
		}
		<?php if (!$page404) { ?>
		if (!$('.grid-block.noneditable').exists()) {
			if ($("#page-blocks").exists()) {
				$("#page-blocks").addClass('editable');
				if ($("#page-blocks section.grid-block").exists() && !$("#page-blocks section.grid-block .edit-grid-tools").exists()) {
					$('#page-blocks section.grid-block').prepend('<div class="edit-grid-tools drag-handler" title="Click here and drag to move this grid block"><span>Grid Block</span><a href="javascript: void(0);" class="grid-del">Delete</a></div>');
					$('#page-blocks section.grid-block section.col').each(function () {
						$(this).append('<select class="add-module">'+
						'<option disabled selected>Add a Module</option>'+
						'<optgroup label="New Module">' +
						<?php
						if (is_array($modules)) {
							foreach ($modules as $key => $val) {
								?>
								'<option value="<?php echo $key; ?>"><?php echo $val; ?></option>'+
								<?php
							}
						}
						?>
						'</optgroup>'+
						<?php
						if (isset($savedModules) && is_array($savedModules)) {
							?>
							'<optgroup label="Saved Modules">' +
							<?php
							foreach ($savedModules as $key => $val) {
								?>
								'<option value="<?php echo $val[0]; ?>" name="<?php echo $val[2]; ?>" id="cache:<?php echo $key; ?>"><?php echo $val[1]; ?></option>'+
								<?php
							}
							?>
							'</optgroup>'+
							<?php
						}
						?>
						'</select>');
					});
					$('#page-blocks section.grid-block section.col section.block').each(function () {
						$(this).append('<div class="edit-block-tools"><a class="edit-block">Edit</a> &nbsp;|&nbsp; <a class="del-block">Delete</a></div>');
					});
					setupModules();
				}
			}
		}
		<?php } ?>
	}
	<?php if (!isset($_GET['no'])) { ?>
	enableEditOptions();
	<?php } ?>
	
	function disableEditOptions() {
		$("#popup_opacity").remove();
		$("#popup").remove();
		$("#edit-mode").remove();
		$('#page-blocks section.grid-block').css('padding-top', '30px');
		$('#page-blocks section.grid-block').animate({ paddingTop: '0px' }, 'slow');
		$('#page-blocks section.grid-block .edit-grid-tools').slideUp('slow', function () {
			$(this).remove();
			$('#page-blocks section.grid-block .add-module').slideDown('slow', function () {
				$(this).remove();
				$("#page-blocks").removeClass('editable');
			});
		});
		$('#page-blocks section.grid-block').animate({ paddingTop: '0px' }, 'slow');
		$('#page-blocks section.grid-block section.col section.block .edit-block-tools').remove();
		$('#page-blocks section.grid-block section.col section.block').css('border', '0px');
	}
	
	<?php if (!isset($_GET['no'])) { ?>
	if (!$('.grid-block.noneditable').exists()) {
		if ($("#page-blocks").exists()) {
			setupEditing();
		}
	}
	<?php } ?>
	
	function initialisePopups() {
		/* Gallery */
		$('.content-media').click(function () {
			popupWindow('content-media', 650, 354, 'Media Library');
		});
		/* Manage Menu */
		$('.content-menu').click(function () {
			popupWindow('content-menu', 650, 354, 'Menu / Navigation');
		});
		/* Manage New */
		$('.content-manage-news').click(function () {
			popupWindow('content-manage-news', 700, 380, 'Manage News');
		});
		/* Manage Reviews */
		$('.content-manage-reviews').click(function () {
			popupWindow('content-manage-reviews', 700, 380, 'Manage Customer Reviews');
		});
		
		
		/* Category / Product */
		$('.ecommerce-categories-products').click(function () {
			popupWindow('ecommerce-categories-products', 700, 380, 'Categories / Products');
		});
		/* Edit Product */
		$('.product-edit').click(function () {
			popupWindow('ecommerce-categories-products', 700, 380, 'Categories / Products', { "product-id" : '<?php echo $productPageId; ?>' });
		});
		/* Edit Product */
		$('.category-edit').click(function () {
			popupWindow('ecommerce-categories-products', 700, 380, 'Categories / Products', { "category-id" : '<?php echo $categoryPageId; ?>' });
		});
		
		
		/* Add a New Page */
		$('.site-add-new-page').click(function () {
			popupWindow('site-add-new-page', 320, 90, 'Add New Page');
		});
		/* Manage Pages */
		$('.site-manage-pages').click(function () {
			popupWindow('site-manage-pages', 650, 350, 'Website Pages');
		});
		/* Manage Website */
		$('.site-manage-website').click(function () {
			popupWindow('site-manage-website', 650, 350, 'Manage Website');
		});
		/* Manage Users */
		$('.site-manage-users').click(function () {
			popupWindow('site-manage-users', 650, 350, 'Manage Users');
		});
		
		
		/* Page Settings */
		$('.page-settings').click(function () {
			popupWindow('page-update-settings', 450, 350, 'Editing Page Settings');
		});
		/* Clone Page */
		$('.page-clone').click(function () {
			popupWindow('page-clone', 320, 90, 'Clone Page');
		});
		//  Change Page Banner
		$('.page-update-banner').click(function () {
			popupWindow('page-update-banner', 775, 350, 'Update Page Banner');
		});
		/* Add New Block */
		$('.page-add-new-block').click(function () {
			popupWindow('page-add-new-block', 540, 180, 'Add New Block');
		});
		/* Delete a Page */
		$('.page-delete').click(function () {
			popupWindow('page-delete', 320, 100, 'Confirm Page Deletion');
		});
		
		
		/* About Information */
		$('.about-info').click(function () {
			popupWindow('about-info', 400, 180, 'About Information');
		});
		/* About License Information */
		$('.about-license').click(function () {
			popupWindow('about-license', 480, 215, 'License Information');
		});
		/* Help */
		$('.about-check-update').click(function () {
			popupWindow('about-check-update', 380, 165, 'Check for Updates');
		});
		
		
		/* My Account - Details */
		$('.account-my-details').click(function () {
			popupWindow('account-my-details', 380, 250, 'My Account');
		});
		/* My Account - Password */
		$('.account-my-password').click(function () {
			popupWindow('account-my-password', 320, 150, 'Change My Password');
		});
		/* My Account - Logout */
		$('.account-logout').click(function () {
			window.location.href = '/logout';
		});
	}
	
	<?php if ($editMode) { ?>
	initialisePopups();
	<?php } ?>
	
	<?php if ($groupId == 10 && !$editMode && $ipChk) { ?>
	showEditMode = '<a id="editor-mode" class="enable_edit-mode">Show &or;</a>';
	<?php } else if ($groupId == 10 && $editMode && $ipChk) { ?>
	showEditMode = '<a id="editor-mode" class="disable_edit-mode">Hide &and;</a>';
	<?php } ?>
	
	<?php if ($groupId == 10 && $ipChk) { ?>
	if (!$('#editor-mode').exists()) $("body").prepend(showEditMode);
	<?php } ?>
	
	function enableDisable() {
		$('.enable_edit-mode').click(function () {
			var dataCont = 'edit-mode=yes';
			$.ajax({
				url: "/editor.php",
				type: 'GET',
				data: dataCont,
				cache: false,
				success: function (data) {
					if (data == 'success') {
						enableEditOptions();
						$('#page-blocks section.grid-block .add-module').hide();
						$('#page-blocks section.grid-block .edit-grid-tools').hide();
						$('#page-blocks section.grid-block').css('padding-top', '0px');
						$('#edit-mode').hide().slideDown('slow');
						if ($('#container').exists()) {
							$('#container').clearQueue().animate({ marginTop: '40px' }, 'slow');
						} else {
							$('#header').clearQueue().animate({ marginTop: '40px' }, 'slow');
						}
						//if (windowPos > 40) $(window).scrollTop(windowPos+40);
						$('#editor-mode').clearQueue().animate({
							top: '40'
						}, 'slow', function () {
							if (!$('.grid-block.noneditable').exists()) {
								if ($("#page-blocks").exists()) {
									setupEditing();
									$('#page-blocks section.grid-block').css('padding-top', '14px').clearQueue().animate({ paddingTop: '37px' }, 'slow');
									$('#page-blocks section.grid-block .add-module').slideUp('slow', function () {
										$('#page-blocks section.grid-block .edit-grid-tools').slideDown('slow');
									});
									$('#page-blocks section.grid-block .add-module').slideToggle();
									$('#page-blocks section.grid-block').css('padding-top', '0').clearQueue().animate({ paddingTop: '30px' }, 'slow');
								}
							}
							if (enableHide) clearTimeout(enableHide);
							var enableHide = setTimeout(function () {
								$('.enable_edit-mode').remove();
								if (!$('#editor-mode').exists()) $('body').prepend('<a id="editor-mode" class="disable_edit-mode">Hide &and;</a>');
								$('#editor-mode').css('top', '40');
								enableDisable();
							}, 700);
							initialisePopups();
						});
					} else {
						alert(data);
					}
				}
			});
		});
		$('.disable_edit-mode').click(function () {
			var dataCont = 'edit-mode=no';
			$.ajax({
				url: "/editor.php",
				type: 'GET',
				data: dataCont,
				cache: false,
				success: function (data) {
					if (data == 'success') {
						$('#edit-mode').slideUp('slow');
						if ($('#container').exists()) {
							$('#container').clearQueue().animate({ marginTop: '0px' }, 'slow');
						} else {
							$('#header').clearQueue().animate({ marginTop: '0px' }, 'slow');
						}
						$('#editor-mode').clearQueue().animate({
							top: '0'
						}, 'slow', function () {
							disableEditOptions();
							if (enableHide) clearTimeout(enableHide);
							$('#container').fadeTo("slow", 1);
							var enableHide = setTimeout(function () {
								$('.disable_edit-mode').remove();
								if (!$('#editor-mode').exists()) $('body').prepend('<a id="editor-mode" class="enable_edit-mode">Show &or;</a>');
								$('#editor-mode').css('top', '0');
								enableDisable();
							}, 700);
						});
					} else {
						alert(data);
					}
				}
			});
		});
	}
	enableDisable();
});
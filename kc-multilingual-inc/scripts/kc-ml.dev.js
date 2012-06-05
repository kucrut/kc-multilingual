jQuery(document).ready(function($) {
	var $body = $('body'),
	    $div  = $('div.kcml-wrap');

	if ( !$body.hasClass('media-php') && !$('#media-items').length ) {
		$div.closest('td').attr('colspan', 2)
			.siblings('th').remove();
		$div.siblings('label').remove();
	}

	$('#addtag').on('kcsRefreshed', function() {
		var $form = $(this);
		$form.find('div.kcml-wrap').siblings('label').remove();
	});


	// Widgets page
	$('.widgets-sortables').ajaxSuccess(function() {
		$('.kcs-tabs').kcTabs();
	});


	// Nav menus page
	var $menuItems = $('#menu-to-edit').children('li.menu-item');
	if ( !$menuItems.length )
		return;

	var menuID = $('#menu').val(),
	    mItems = [];

	$menuItems.each(function() {
		mItems.push( $('input.menu-item-data-db-id', this).val() );
	});

	$.getJSON(
		ajaxurl,
		{ action: 'kc_ml_get_menu_translations', menuID: menuID, items: mItems.join(',') },
		function( response ) {
			if ( response ) {
				// Menu names
				var menuTab = '<ul class="kcml-langs kcs-tabs">',
				    menuPanes = '';

				$.each(response.menu_names, function(lang, menuName) {
					menuTab += '<li><a href="#kcml-menuname-'+lang+'">'+response.languages[lang]+'</a></li>';
					menuPanes += '<div id="kcml-menuname-'+lang+'">';
					menuPanes += '<h4 class="screen-reader-text">'+response.languages[lang]+'</h4>';
					menuPanes += '<div class="field">';
					menuPanes += '<label>'+kcml_texts.menuNameLabel+'<input type="text" value="'+menuName+'" name="kc-termmeta[kcml][kcml-translation]['+menuID+']['+lang+'][title]" /></label>';
					menuPanes += '</div>';
					menuPanes += '</div>';
				});
				menuTab += '</ul>';
				$('#post-body-content').prepend('<div class="kcml-wrap kcml-menu-names"><h3>KC Multilingual</h3>'+menuTab+menuPanes+'</div>');

				// Menu items
				$.each(response.menu_items, function(itemIdx, itemData) {
					var itemTab = '<ul class="kcml-langs kcs-tabs">',
					    itemPanes = '';

					$.each(itemData.translation, function(lang, translation) {
						itemTab += '<li><a href="#kcml-'+itemData.id +'-'+lang+'">'+response.languages[lang]+'</a></li>';
						itemPanes += '<div id="kcml-'+itemData.id +'-'+lang+'">';
						itemPanes += '<h4 class="screen-reader-text">'+response.languages[lang]+'</h4>';
						itemPanes += '<p class="description description-thin">';
						itemPanes += '<label for="kcml-menu-item-title-'+itemData.id+'-'+lang+'">'+kcml_texts.title+'<br />';
						itemPanes += '<input type="text" value="'+translation.title+'" name="kc-postmeta[kcml][kcml-translation]['+itemData.id+']['+lang+'][title]" class="widefat edit-menu-item-title" id="kcml-menu-item-title-'+itemData.id+'-'+lang+'" />';
						itemPanes += '</label></p>';
						itemPanes += '<p class="description description-thin">';
						itemPanes += '<label for="kcml-menu-item-excerpt-'+itemData.id+'-'+lang+'">'+kcml_texts.excerpt+'<br />';
						itemPanes += '<input type="text" value="'+translation.excerpt+'" name="kc-postmeta[kcml][kcml-translation]['+itemData.id+']['+lang+'][excerpt]" class="widefat edit-menu-item-attr-title" id="kcml-menu-item-excerpt-'+itemData.id+'-'+lang+'" />';
						itemPanes += '</label></p>';
						itemPanes += '<p class="field-description description description-wide">';
						itemPanes += '<label for="kcml-menu-item-content-'+itemData.id+'-'+lang+'">'+kcml_texts.content+'<br />';
						itemPanes += '<textarea cols="20" rows="3" name="kc-postmeta[kcml][kcml-translation]['+itemData.id+']['+lang+'][content]" class="widefat edit-menu-item-description" id="kcml-menu-item-content-'+itemData.id+'-'+lang+'">'+translation.content+'</textarea>';
						itemPanes += '</label></p>';
						itemPanes += '</div>';
					});
					itemTab += '</ul>';

					$('div.menu-item-actions', $menuItems.eq(itemIdx)).before( '<div class="kcml-wrap clear"><h3>KC Multilingual</h3>'+itemTab+itemPanes+'</div>' );
				});

				$('.kcs-tabs').kcTabs();
			}

			window.wpNavMenu.menuList.hideAdvancedMenuItemFields();
		}
	);

});

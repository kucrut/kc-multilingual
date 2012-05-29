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

	var $menuItems = $('#menu-to-edit').children('li.menu-item');
	if ( !$menuItems.length )
		return;

	var menuIDs = [];
	$menuItems.each(function() {
		menuIDs.push( $('input.menu-item-data-db-id', this).val() );
	});

	$.getJSON(
		ajaxurl,
		{ action: 'kc_ml_get_menu_translations', ids: menuIDs.join(',') },
		function( response ) {
			if ( response ) {
				$.each(response, function(idx, data) {
					var tab = '<ul class="kcml-langs kcs-tabs">',
					    panes = '';

					$.each(data.translation, function(lang, translation) {
						tab += '<li><a href="#kcml-'+data.id +'-'+lang+'">'+translation.language+'</a></li>';

						panes += '<div id="kcml-'+data.id +'-'+lang+'">';
						panes += '<h4 class="screen-reader-text">'+translation.language+'</h4>';
						panes += '<p class="description description-thin">';
						panes += '<label for="kcml-menu-item-title-'+data.id+'-'+lang+'">'+kcml_texts.title+'<br />';
						panes += '<input type="text" value="'+translation.title+'" name="kc-postmeta[kcml][kcml-translation]['+data.id+']['+lang+'][title]" class="widefat edit-menu-item-title" id="kcml-menu-item-title-'+data.id+'-'+lang+'" />';
						panes += '</label></p>';
						panes += '<p class="description description-thin">';
						panes += '<label for="kcml-menu-item-excerpt-'+data.id+'-'+lang+'">'+kcml_texts.excerpt+'<br />';
						panes += '<input type="text" value="'+translation.excerpt+'" name="kc-postmeta[kcml][kcml-translation]['+data.id+']['+lang+'][excerpt]" class="widefat edit-menu-item-attr-title" id="kcml-menu-item-excerpt-'+data.id+'-'+lang+'" />';
						panes += '</label></p>';
						panes += '<p class="field-description description description-wide">';
						panes += '<label for="kcml-menu-item-content-'+data.id+'-'+lang+'">'+kcml_texts.content+'<br />';
						panes += '<textarea cols="20" rows="3" name="kc-postmeta[kcml][kcml-translation]['+data.id+']['+lang+'][content]" class="widefat edit-menu-item-description" id="kcml-menu-item-content-'+data.id+'-'+lang+'">'+translation.content+'</textarea>';
						panes += '</label></p>';
						panes += '</div>';
					});
					tab += '</ul>';

					$('div.menu-item-actions', $menuItems.eq(idx)).before( '<div class="kcml-wrap clear"><h3>'+kcml_texts.head+'</h3>'+tab+panes+'</div>');
				});

				$('.kcs-tabs').kcTabs();
			}
		}
	);

});

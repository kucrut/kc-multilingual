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
});

jQuery(document).ready(function($) {
	var $div = $('div.kcml-wrap');
	$div.closest('td').attr('colspan', 2)
		.siblings('th').remove();
	$div.siblings('label').remove();

	$('#addtag').on('kcsRefreshed', function() {
		var $form = $(this);
		$form.find('div.kcml-wrap').siblings('label').remove();
	});
});

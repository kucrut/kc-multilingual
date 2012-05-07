jQuery(document).ready(function($) {
	var $div = $('div.kcml-wrap');
	$div.closest('td').attr('colspan', 2)
		.siblings('th').remove();
	$div.siblings('label').remove();

	$div.find('ul.kcml-langs a').click(function(e) {
		e.preventDefault();
		var $el = $(this);
		var $t = $($el.attr('href'));
		$el.parent().addClass('tabs').siblings('li').removeClass('tabs');
		$t.siblings('div').hide();
		$t.show();
	}).first().trigger('click');
});

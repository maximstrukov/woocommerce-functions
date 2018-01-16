var select_id;
jQuery(document).ready(function($) {

	//update plans on changing site
	$('.select_plan').change(function(){
		update_plans($(this), true);
	});
	
	function update_plans(elem, async_mode) {
		select_id = $(elem).attr('name') + '_plan';
		var data = {
			'action': 'get_plans',
			'site_id': $(elem).val()
		};
		
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.ajax({
			url:  ajaxurl,
			type: 'POST',
			data: data,
			success: function(response) {
				plans = jQuery.parseJSON(response);
				val = $('#'+select_id).attr('_id');
				$('#'+select_id).find('option').remove();
				if (plans.length > 0) {
					$.each(plans, function (i, item) {
						$('#'+select_id).append($('<option>', { 
							value: item.ID,
							text : item.post_title 
						}));
					});
					$('#'+select_id).next().hide();
					$('#'+select_id).val(val);
					$('#'+select_id).show();
					$('#'+select_id).parents('tr').next().show();
				} else {
					$('#'+select_id).hide().next().show();
					$('#'+select_id).parents('tr').next().hide();
				}
			},
			async: async_mode
		});

	}
	
	//loading plans for initial values of sites
	update_plans($('select[name=membership]'), false);
	update_plans($('select[name=coaching]'), false);
	
});
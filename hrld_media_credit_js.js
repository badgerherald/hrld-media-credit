jQuery("body").on("keydown", ".hrld_media_credit_input", function(){

	var hrld_media_input = jQuery(this);
	var hrld_media_input_id = hrld_media_input.attr('id');
	hrld_media_input_id = hrld_media_input_id.split('-');
	hrld_media_input_id = hrld_media_input_id[1];
	//var hrld_user_tags = ['1','2','3'];
	hrld_media_input.autocomplete({
		source: hrld_user_tags,
		select: function (event, ui) {
			var hrld_data = {
				action: "hrld_save_credit_ajax",
				hrld_credit: ui.item.value,
				hrld_my_nonce: hrld_media_data.my_nonce,
				hrld_id: hrld_media_input_id
			}
			console.log(hrld_media_input);
			hrld_media_input.attr("value", ui.item.value);
			jQuery.post(ajaxurl, hrld_data);
			hrld_media_input.focusout();
		}  
	});
});
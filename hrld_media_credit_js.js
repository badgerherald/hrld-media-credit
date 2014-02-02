jQuery("body").on("keydown.autocomplete", ".hrld_media_credit_input", function(){
	jQuery(this).autocomplete({source: hrld_user_tags});
});
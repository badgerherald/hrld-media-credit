<?php
/**
 * Plugin Name: Herald Media Credit
 * Description: Adds credit field to uploaded media.
 * Version: 1.0
 * Author: Matthew Neil for The Badger Herald
 * License: GPL2
 */
 
/**
 * Add Herald Media Credit to media uploader
 *
 * @param $form_fields array, fields to include in attachment form
 * @param $post object, attachment record in database
 * @return $form_fields, modified form fields
 */
function hrld_attachment_field_credit( $form_fields, $post ) {
	 $value = get_post_meta( $post->ID, '_hrld_media_credit', true );
	 $form_fields['hrld_media_credit']['label'] = 'Media Credit';
	 $form_fields['hrld_media_credit']['input'] = 'html';
	 if (current_user_can('edit_post', $post->ID)) {
	 	$form_fields['hrld_media_credit']['html'] = '<input type="text" class="text hrld_media_credit_input" id="attachments-'.$post->ID.'-hrld_media_credit" name="attachments['.$post->ID.'][hrld_media_credit]" value="'.$value.'">';
	 } else {
	 	$form_fields['hrld_media_credit']['html'] = '<input type="text" class="text hrld_media_credit_input" id="attachments-'.$post->ID.'-hrld_media_credit" name="attachments['.$post->ID.'][hrld_media_credit]" value="'.$value.'" disabled>';
	 }
	 
	 $form_fields['hrld_media_credit']['helps'] = 'If photo was taken by a Herald photographer, type their name and select from the dropdown. If the photo is from an outside source, type the credit in the format of name/organization. e.g. "Jeff Miller/UW Communications"';

	return $form_fields;
}

add_filter( 'attachment_fields_to_edit', 'hrld_attachment_field_credit', 10, 2 );

/**
 * Save values of Herald Media Credit in media uploader
 *
 * @param $post array, the post data for database
 * @param $attachment array, attachment fields from $_POST form
 * @return $post array, modified post data
 */

function hrld_attachment_field_credit_save( $post, $attachment ) {
	if( isset( $attachment['hrld_media_credit'] ) )
		update_post_meta( $post['ID'], '_hrld_media_credit', $attachment['hrld_media_credit'] );

	return $post;
}

add_filter( 'attachment_fields_to_save', 'hrld_attachment_field_credit_save', 10, 2 );

/**
 * Save values of Author Name and URL in media uploader modal via AJAX
 */
function admin_attachment_field_media_author_credit_ajax_save() {

	check_ajax_referer( 'hrld_media_nonce', 'hrld_my_nonce' );

    if( isset( $_POST['hrld_credit'] ) )
    	echo $_POST['hrld_credit'];
        update_post_meta( $_POST['hrld_id'], '_hrld_media_credit', $_POST['hrld_credit'] );
	die();

} add_action('wp_ajax_hrld_save_credit_ajax', 'admin_attachment_field_media_author_credit_ajax_save', 0, 1); 


/**
 * Includes images to author query if they have credit to them.
 *
 * @param $query object, passed by reference
 */

/*
 * NOT IN USE CURRENTLY
 
function hrld_media_author_query($posts){
	global $wp_query;
	if(!is_admin() && is_author()){
		$query_author = $wp_query->query['author_name'];
		$media_args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'meta_key' => '_hrld_media_credit',
			'meta_value' => $query_author
		);
		$media_posts = get_posts($media_args);
		$all_posts = array_merge($media_posts, $posts);
		usort( $all_posts, create_function('$a,$b', 'return strcmp($b->post_date, $a->post_date);') );
		return $all_posts;
	}
	else{
		return $posts;
	}
}
add_filter('the_posts', 'hrld_media_author_query', 1);
*/


/**
 * Adds the credit byline to images added into post through "Add Media" button.
 *
 */
 
function hrld_media_credit_send_editor($html, $id, $caption, $title, $align, $url, $size){
		$html = get_image_tag($id, '', $title, $align, $size);
		$hrld_credit = get_hrld_media_credit($id);
		if(isset($hrld_credit) && !empty($hrld_credit)){
			if(get_user_by('login', $hrld_credit)){
				$hrld_user = get_user_by('login', $hrld_credit);
				$html_text = '<span class="hrld-media-credit"><span class="hrld-media-credit-name"><a><a href="'.get_bloginfo('url').'/author/'.$hrld_credit.'">'.$hrld_user->display_name.'</a></a></span><span class="hrld-media-credit-org">/The Badger Herald</span></span>'; 
			} else{
				$hrld_credit_name_org = explode("/", $hrld_credit);
				if($hrld_credit_name_org[1]){
					$html_text = '<span class="hrld-media-credit"><span class="hrld-media-credit-name">'.$hrld_credit_name_org[0].'</span><span class="hrld-media-credit-org">/'.$hrld_credit_name_org[1].'</span></span>';
				}
				else{
					$html_text = '<span class="hrld-media-credit"><span class="hrld-media-credit-org">'.$hrld_credit_name_org[0].'</span></span>';
				}
			}
			if($caption){
				$html = get_image_tag($id, '', $title, $align, $size);
				$html .= $caption;
				$html .= '<br />';
				$html .= $html_text;
			} else{
				$attach_attributes = wp_get_attachment_image_src($id, $isze);
				$html = '[caption id="attachment_'.$id.'" align="'.$align.'" width="'.$attach_attributes[1].'"]';
				$html .= get_image_tag($id, '', $title, $align, $size);
				$html .= $html_text;
				$html .= '[/caption]';
			}
		}
	return  $html;
}
add_filter( 'image_send_to_editor', 'hrld_media_credit_send_editor', 10, 7 );

/**
 * Removes Default image_add_caption filter and adds slightly
 * editied version for hrld_add_caption. 
 * Adds image shortcode with caption to editor
 *
 * @since 2.6.0
 *
 * @param string $html
 * @param integer $id
 * @param string $caption image caption
 * @param string $alt image alt attribute
 * @param string $title image title attribute
 * @param string $align image css alignment property
 * @param string $url image src url
 * @param string $size image size (thumbnail, medium, large, full or added with add_image_size() )
 * @return string
 */
function hrld_remove_filters(){
	remove_filter('image_send_to_editor', 'image_add_caption', 20, 8);
}
add_action('admin_init', 'hrld_remove_filters');
function hrld_add_caption( $html, $id, $caption, $title, $align, $url, $size, $alt = '' ) {

	/**
	 * Filter whether to disable captions.
	 *
	 * Prevents image captions from being appended to image HTML when inserted into the editor.
	 *
	 * @since 2.6.0
	 *
	 * @param bool $bool Whether to disable appending captions. Returning true to the filter
	 *                   will disable captions. Default empty string.
	 */
	if ( empty($caption) || apply_filters( 'disable_captions', '' ) )
		return $html;

	$hrld_credit = get_hrld_media_credit($id);

	$id = ( 0 < (int) $id ) ? 'attachment_' . $id : '';

	if ( ! preg_match( '/width=["\']([0-9]+)/', $html, $matches ) )
		return $html;

	$width = $matches[1];

	$caption = str_replace( array("\r\n", "\r"), "\n", $caption);
	$caption = preg_replace_callback( '/<[a-zA-Z0-9]+(?: [^<>]+>)*/', 'hrld_cleanup_image_add_caption', $caption );
	// convert any remaining line breaks to <br>
	$caption = preg_replace( '/[ \n\t]*\n[ \t]*/', '<br />', $caption );

	$html = preg_replace( '/(class=["\'][^\'"]*)align(none|left|right|center)\s?/', '$1', $html );
	if ( empty($align) )
		$align = 'none';


	

	if(isset($hrld_credit) && !empty($hrld_credit)){
		$shcode = '[caption id="' . $id . '" align="align' . $align	. '" width="' . $width . '"]' . $html . '[/caption]';
	} else{
		$shcode = '[caption id="' . $id . '" align="align' . $align	. '" width="' . $width . '"]' . $html . ' ' . $caption . '[/caption]';
	}
	/**
	 * Filter the image HTML markup including the caption shortcode.
	 *
	 * @since 2.6.0
	 *
	 * @param string $shcode The image HTML markup with caption shortcode.
	 * @param string $html   The image HTML markup.
	 */
	return apply_filters( 'image_add_caption_shortcode', $shcode, $html );
}
add_filter('image_send_to_editor', 'hrld_add_caption', 20, 8);

function hrld_cleanup_image_add_caption( $matches ) {
	// remove any line breaks from inside the tags
	return preg_replace( '/[\r\n\t]+/', ' ', $matches[0] );
}

function get_hrld_media_credit($id){
	$hrld_credit = get_post_custom($id);
	return $hrld_credit['_hrld_media_credit'][0];
}
function get_hrld_media_credit_user($id){
	$hrld_credit = get_post_custom($id);
	return get_user_by('login', $hrld_credit['_hrld_media_credit'][0]);
}
/**
 * Adds script to footer with wp_footer hook
 */
function hrld_auto_complete_js(){
	$hrld_ajax_data = array(
		'my_nonce' => wp_create_nonce('hrld_media_nonce')
	);
	wp_enqueue_script('jquery-ui-autocomplete');
	wp_enqueue_script('hrld_media_credit_js', plugins_url().'/hrld-media-credit/hrld_media_credit_js.js', array('jquery','jquery-ui-autocomplete'));
	wp_localize_script('hrld_media_credit_js','hrld_media_data', $hrld_ajax_data);
	echo '<script type="text/javascript">var hrld_user_tags = [';
	$users_exclude = get_users(array('order'=>'ASC', 'orderby'=>'login', 'role'=>'subscriber'));
	$exclude = array();
	foreach($users_exclude as $user_exclude){
		$exclude[] = $user_exclude->ID;
	}
	$hrld_users = get_users(array('order'=>'ASC', 'orderby'=>'login', 'exclude'=>$exclude));
	foreach($hrld_users as $hrld_user){
		if($hrld_user === end($hrld_users)){
			echo '{label:"'.$hrld_user->display_name.'",value:"'.$hrld_user->user_login.'"}';
		}
		else{
			echo '{label:"'.$hrld_user->display_name.'",value:"'.$hrld_user->user_login.'"},';
		}
	}
	echo '];</script>';
	echo '<style type="text/css">.ui-front{z-index:1600000 !important;}</style>';
} 
add_action('admin_head', 'hrld_auto_complete_js', 20);

function hrld_remove_old_media_credit($content){
	$txt= $content;

  $caption_pattern = '/(\\[caption.*?\\])(.*?)(\\[\\/caption\\])/';

  if ($c=preg_match_all ($caption_pattern, $txt, $matches))
  {
  	$txt = preg_replace_callback($caption_pattern, function($cap_matches){
  			$middle_replace = preg_replace_callback('/\[media-credit.*?(?:(name)|(id))=(?(1)["\'](.*?)["\']|(?(2)([0-9]+))).*?\](<[^>]+>)\[\/media-credit\](.+)/', function($middle_matches){
  				if($middle_matches[1]){
  					$credit_name = $middle_matches[3];
  				}
  				else{
  					$credit_user = get_user_by('id', $middle_matches[4]);
  					$credit_name = '<span class="hrld-media-credit-name">'.$credit_user->first_name.' '.$credit_user->last_name.'</span><span class="hrld-media-credit-org">/The Badger Herald</span>';
  				}
  				return $middle_matches[5].'<span class="hrld-media-credit">'.$credit_name.'</span>'.$middle_matches[6];
  			}, $cap_matches[2]);
  			return $cap_matches[1].$middle_replace.$cap_matches[3];
  		}, $txt);
  }
  	$txt = preg_replace_callback('/\[media-credit.*?(?:(name)|(id))=(?(1)["\'](.*?)["\']|(?(2)([0-9]+))).*?\](<[^>]+>)\[\/media-credit\]/', function($middle_matches){
		if($middle_matches[1]){
			$credit_name = $middle_matches[3];
		}
		else{
			$credit_user = get_user_by('id', $middle_matches[4]);
			$credit_name = '<span class="hrld-media-credit-name">'.$credit_user->first_name.' '.$credit_user->last_name.'</span><span class="hrld-media-credit-org">/The Badger Herald</span>';
		}
		return '<div class="wp-caption">'.$middle_matches[5].'<p class="wp-caption-text"><span class="hrld-media-credit">'.$credit_name.'</span></p></div>';
	}, $txt);
  return do_shortcode($txt);
}
add_filter('the_content', 'hrld_remove_old_media_credit',10);
?>
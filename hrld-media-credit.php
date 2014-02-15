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
	 $form_fields['hrld_media_credit']['html'] = '<input type="text" class="text hrld_media_credit_input" id="attachments-'.$post->ID.'-hrld_media_credit" name="attachments['.$post->ID.'][hrld_media_credit]" value="'.$value.'">';
	 $form_fields['hrld_media_credit']['helps'] = 'If photo was taken by a Herald photographer, use their username. e.g. "Bucky Badger" You should write "bbadger".';

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
				$html_text = '<span class="hrld-media-credit">Photo by '.$hrld_user->display_name.'.</span>';
			} else{
				$html_text = '<span class="hrld-media-credit">'.$hrld_credit.'.</span>';
			}
			if($caption){
				$html = get_image_tag($id, '', $title, $align, $size);
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
 * Returns the hrld-media-credit custom meta field data
 */
function get_hrld_media_credit($id){
	$hrld_credit = get_post_custom($id);
	return $hrld_credit['_hrld_media_credit'][0];
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
	$hrld_users = get_users(array('order'=>'ASC', 'orderby'=>'login'));
	foreach($hrld_users as $hrld_user){
		if($hrld_user === end($hrld_users)){
			echo '"'.$hrld_user->user_login.'"';
		}
		else{
			echo '"'.$hrld_user->user_login.'",';
		}
	}
	echo '];</script>';
	echo '<style type="text/css">.ui-front{z-index:1600000 !important;}</style>';
} 
add_action('admin_head', 'hrld_auto_complete_js', 20);


?>
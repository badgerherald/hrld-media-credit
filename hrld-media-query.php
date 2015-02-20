<?php
/**
 * Class for holding a WP_Query object for media credit of a specified author.
 */
Class HrldMediaQuery {
    public $query;
    function __construct($user_login) {
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_key' => '_hrld_media_credit',
            'meta_value' => $user_login,
            'posts_per_page' => -1
            );
        $this->query = new WP_Query($args);
    }

    /**
     * Returns the count of attachments credited author.
     * @return int          Count of attachments
     */
    public function creditCount() {
        return $this->query->found_posts;
    }
}
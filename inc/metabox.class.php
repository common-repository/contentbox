<?php

class ContentboxMetabox
{
    static function meta_init()
    {
        add_meta_box('contentbox_id', 'Contentbox ID', array('ContentboxMetabox', 'meta_showup'), 'post', 'side',
            'default');
    }

    static function meta_showup($post, $box)
    {
        $data = get_post_meta($post->ID, 'contentbox_id', true);

        wp_nonce_field('contentbox_id_action', 'metatest_nonce');

        echo '<p>Contentbox ID: <input type="text" name="contentbox_id" value="' . esc_attr($data) . '"/></p>';
    }

    static function meta_save($postID)
    {
        if (!isset($_POST['contentbox_id'])) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postID)) {
            return;
        }

        check_admin_referer('contentbox_id_action', 'metatest_nonce');

        $data = sanitize_text_field($_POST['contentbox_id']);

        update_post_meta($postID, 'contentbox_id', $data);

    }
}
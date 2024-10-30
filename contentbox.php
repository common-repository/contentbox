<?php
/**
 * @package Contentbox
 */
/*
Plugin Name: ContentBox
Plugin URI: https://contentbox.ru/articles/16-integracija-wordpress-s-contentbox
Description: Автоматическое наполнение блога с помощью контент-отдела contentBox.ru
Version: 1.1
Author: ContentBox
Author URI: https://contentbox.ru
License: GPLv2 or later
Text Domain: contentbox
Domain Path: /languages
*/

//Defines
define('CONTENTBOX_VERSION', '1.1');
define('CONTENTBOX_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOX_URL', plugin_dir_url(__FILE__));

//Includes
require_once(ABSPATH . "wp-admin" . '/includes/image.php');

require_once(CONTENTBOX_DIR . 'inc/metabox.class.php');
require_once(CONTENTBOX_DIR . 'inc/product.class.php');
require_once(CONTENTBOX_DIR . 'inc/client.class.php');
require_once(CONTENTBOX_DIR . 'inc/page.class.php');
require_once(CONTENTBOX_DIR . 'inc/contentbox.class.php');

//Hooks
register_activation_hook(__FILE__, array('Contentbox', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('Contentbox', 'plugin_deactivation'));

//Actions
add_action('add_meta_boxes', array('ContentboxMetabox', 'meta_init')); //Метабокс id записи из базы Contentbox
add_action('save_post', array('ContentboxMetabox', 'meta_save'));
add_action('contentbox_sync', array('Contentbox', 'contentbox_sync_func'));

//Filters
add_filter('cron_schedules', array('Contentbox', 'contentbox_custom_intervals'));

//Init options page
new ContentboxPage();

//Default class
class Contentbox
{

    static function contentbox_custom_intervals($schedules)
    {
        $schedules['every_thirty_minutes'] = array(
            'interval' => 1800,
            'display' => __('Каждые 30 минут', 'contentbox')
        );

        $schedules['every_hour'] = array(
            'interval' => 3600,
            'display' => __('Каждый час', 'contentbox')
        );

        $schedules['every_half_day'] = array(
            'interval' => 43200,
            'display' => __('2 раза в день', 'contentbox')
        );

        $schedules['every_day'] = array(
            'interval' => 86400,
            'display' => __('Каждый день', 'contentbox')
        );

        $schedules['every_week'] = array(
            'interval' => 604800,
            'display' => __('Каждую неделю', 'contentbox')
        );

        return $schedules;
    }

    function plugin_activation()
    {
        ContentboxCore::initShedule();
    }

    function plugin_deactivation()
    {
        wp_clear_scheduled_hook('contentbox_sync');

        ContentboxCore::deleteApiToken();
        ContentboxCore::deleteApiID();
        ContentboxCore::deleteCategory();
        ContentboxCore::deletePostStatus();
        ContentboxCore::deleteIsAddImg();
    }


    function contentbox_sync_func()
    {
        $contentbox_api_token = get_option('contentbox_api_token');

        if ($contentbox_api_token) {
            $contentboxCore = new ContentboxCore($contentbox_api_token);
            $contentboxCore->sync();
        }
    }
}
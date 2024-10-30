<?php

class ContentboxCore
{
    /**
     * API URL
     */
    public $apiUrl = 'https://contentbox.ru/api/v2';

    /**
     * Токен магазина в contentBox.ru
     */
    public $apiAccessToken;

    /**
     * Идентификатор магазина в contentBox.ru
     */
    public $shopId;
    protected $_client;
    protected $_shop;

    public function __construct($apiAccessToken)
    {
        $this->apiAccessToken = $apiAccessToken;
    }

    /**
     * Синхронизация заданий
     * @throws Exception
     */
    public function sync()
    {
        $items = $this->getNewItems();
        $data = [
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($items as $item) {

            try {

                $result = $this->syncPost($item);

                if ($result) {
                    $data['success']++;
                    $this->markWorkItemSynced($item->id);
                } else {
                    $data['failed']++;
                }

            } catch (Exception $e) {
                throw $e;
            }
        }

        return $data;
    }

    /**
     * Поиск заданий для синхронизации
     * @return array
     */
    public function getNewItems()
    {
        $client = new ContentboxClient($this->apiAccessToken);
        $response = $client->get($this->getApiUrl('work/items',
            array('shop_id' => $this->shopId, 'sync_status' => 0, 'status' => 3, 'per-page' => 50)));

        return json_decode($response);
    }

    /**
     * Синхронизация товара
     * @param array $item
     * @return null
     */
    public function syncPost($item)
    {
        $result = false;

        $contentbox_is_add_img = ContentboxCore::getIsAddImg();

        $post = $this->findPost($item->source->id);
        $post->post_title = $item->source->name ? $item->source->name : null;
        $post->post_content = $item->result->text ? $item->result->text : '';

        $ctbxId = $item->source->id;
        $postID = $this->savePost($post, $ctbxId);

        if ($postID > 0) {

            $attachHtml = ContentboxProduct::saveAttachments($item, $postID);

            if ($attachHtml != false && $contentbox_is_add_img) {
                $post->ID = $postID;
                $post->post_content = $post->post_content . '<br>' . $attachHtml;
                $this->savePost($post, $ctbxId);
            }

            $result = true;
        }

        return $result;
    }

    /**
     * Отметить обновленные задания как синхронизированные в contentBox
     * @param $itemID
     * @return bool
     */
    public function markWorkItemSynced($itemID)
    {
        if ($itemID > 0) {
            $client = new ContentboxClient($this->apiAccessToken);
            $response = $client->get($this->getApiUrl('work/items/' . $itemID . '/set-sync-status'), "value=1");

            return true;
        }
    }

    /**
     * Получаем готовую ссылку для запроса
     * @param string $route , array $params
     * @return string
     */
    public function getApiUrl($route, $params = array())
    {
        return sprintf('%s/%s?shop_id=%s&%s', $this->apiUrl, $route, $this->shopId, http_build_query($params));
    }

    /**
     * Поиск поста в базе данных
     * @param int $itemID
     * @return WP_Post
     */
    public function findPost($itemID)
    {
        return ContentboxProduct::findByContentboxID($itemID);
    }

    /**
     * Сохранение\обноление поста
     * @param WP_Post object $postArr, int $ctbxId
     * @return int $response
     */
    protected function savePost($postArr, $ctbxId)
    {
        $response = ContentboxProduct::save($postArr);

        if ($response && $response > 0) {
            ContentboxProduct::setContentboxID($response, $ctbxId);
        }

        return $response;
    }

    /**
     * Регистрация события
     * @param
     * @return null
     */
    public static function initShedule($interval = 'hourly')
    {
        wp_clear_scheduled_hook('contentbox_sync');
        wp_schedule_event(time(), $interval, 'contentbox_sync');
    }

    /**
     * Получение options
     * @param
     * @return string
     */
    public static function getApiToken()
    {
        return get_option('contentbox_api_token');
    }

    public static function getApiID()
    {
        return get_option('contentbox_api_id');
    }

    public static function getCategory()
    {
        return get_option('contentbox_cat');
    }

    public static function getPostStatus()
    {
        return get_option('contentbox_post_status');
    }

    public static function getSheduleType()
    {
        return get_option('contentbox_shedule_type');
    }

    public static function getIsAddImg()
    {
        return get_option('contentbox_is_add_img');
    }

    /**
     * Сохранение options
     * @param
     * @return string
     */
    public static function setApiToken($val)
    {
        return update_option('contentbox_api_token', $val);
    }

    public static function setApiID($val)
    {
        return update_option('contentbox_api_id', $val);
    }

    public static function setCategory($val)
    {
        return update_option('contentbox_cat', $val);
    }

    public static function setPostStatus($val)
    {
        return update_option('contentbox_post_status', $val);
    }

    public static function setSheduleType($val)
    {
        return update_option('contentbox_shedule_type', $val);
    }

    public static function setIsAddImg($val)
    {
        return update_option('contentbox_is_add_img', $val);
    }

    /**
     * Удаление options
     * @param
     * @return string
     */
    public static function deleteApiToken()
    {
        delete_option('contentbox_api_token');
    }

    public static function deleteApiID()
    {
        delete_option('contentbox_api_id');
    }

    public static function deleteCategory()
    {
        delete_option('contentbox_cat');
    }

    public static function deletePostStatus()
    {
        delete_option('contentbox_post_status');
    }

    public static function deleteSheduleType()
    {
        delete_option('contentbox_shedule_type');
    }

    public static function deleteIsAddImg()
    {
        delete_option('contentbox_is_add_img');
    }
}
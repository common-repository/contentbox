<?php

class ContentboxProduct
{
    public static $uploadDir = '/wp-content/uploads/';

    /**
     * Поиск загруженного изображения
     * @param $name
     * @return WP_Query
     */
    public static function checkImage($name)
    {
        //WP core function
        $query = new WP_Query(
            array(
                'post_mime_type' => 'image',
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'name' => $name,
            )
        );

        return $query;
    }

    /**
     * Сохранить вложение
     * @param WP_Post object $item, int $postID
     * @return null
     */
    public static function saveAttachments($item, $postID)
    {
        if ($item->result->images && count($item->result->images) > 0) {

            $imagesHtml = '';
            $newAttachments = 0;

            foreach ($item->result->images as $imageObj) {

                $file = $imageObj->url;
                $filenameFull = basename($file);
                $filename = preg_replace('/\.[^.]+$/', '', $filenameFull);

                $attachment = self::checkImage($filename);

                if (!isset($attachment->posts[0])) {
                    $upload_file = wp_upload_bits($filenameFull, null, file_get_contents($file));

                    if (!$upload_file['error']) {
                        $wp_filetype = wp_check_filetype($filenameFull, null);
                        $attachment = array(
                            'post_mime_type' => $wp_filetype['type'],
                            'post_parent' => $postID,
                            'post_title' => $filename,
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );
                        $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $postID);
                        if (!is_wp_error($attachment_id)) {
                            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                            wp_update_attachment_metadata($attachment_id, $attachment_data);

                            $imagesHtml .= '<img src="' . self::$uploadDir . $attachment_data['file'] . '" alt="">';
                            $newAttachments++;

                        }
                    }
                } else {
                    $attachment_data = wp_get_attachment_image_src($attachment->post->ID, 'large');
                    $imagesHtml .= '<img src="' . $attachment_data[0] . '" alt="">';
                    //echo '<pre>'.print_r($file,1).'</pre>';
                }
            }

            return $imagesHtml;
        }
        return false;
    }

    /**
     * Поиск поста по id
     * @param int $id
     * @return WP_Post object
     */
    public static function findByPk($id)
    {
        //WP core function
        return get_post($id);
    }

    /**
     * Поиск поста по post_meta contentbox_id
     * @param int $id
     * @return WP_Post object
     */
    public static function findByContentboxID($id)
    {
        if (intval($id) > 0) {
            //WP core function
            $query = new WP_Query(
                array(
                    'meta_query' => array(
                        array(
                            'key' => 'contentbox_id',
                            'value' => $id,
                        )
                    ),
                    'posts_per_page' => 1,
                )
            );
            if (!isset($query->posts[0])) {
                return new WP_Post((object)array());
            }
            return $query->posts[0];
        }
    }

    /**
     * Привязка поста к contentbox ID сущности
     * @param int $postID , int $ctbxId
     * @return true
     */
    public static function setContentboxID($postID, $ctbxId)
    {
        if ($postID > 0) {
            update_post_meta($postID, 'contentbox_id', $ctbxId);
        }

        return true;
    }

    /**
     * Сохранение\обноление товара
     * @param WP_Post object $postArr, int $ctbxId
     * @return int $response
     */
    public static function save($postArr)
    {
        if ($postArr->ID) {
            return wp_update_post($postArr);
        } else {
            $postStatus = ContentboxCore::getPostStatus();
            $catId = ContentboxCore::getCategory();

            if ($postStatus) {
                $postArr->post_status = $postStatus;
            }

            if ($catId) {
                $postArr->post_category = array($catId);
            }

            return wp_insert_post($postArr);
        }
    }

}
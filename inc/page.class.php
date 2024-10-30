<?php

class ContentboxPage
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'contentbox_admin_menu'));
    }

    function contentbox_admin_menu()
    {
        add_options_page('Настройки contentBox', 'contentBox', 'manage_options', 'wp-contentbox/settings',
            array($this, 'contentbox_admin_page'));
    }

    function contentbox_admin_page()
    {
        //Проверка прав
        if (!current_user_can('manage_options')) {
            wp_die(__('У вас нет прав доступа на эту страницу.', 'contentbox'));
        }

        //Сохранение
        $errors = array();
        $messages = array();

        $contentbox_shedule_type_old = ContentboxCore::getSheduleType();

        if (isset($_POST['contentbox_save'])) {
            if (!$_POST['contentbox_api_token'] || !$_POST['contentbox_api_id']) {
                $errors[] = 'Поля "API ID" и "API Token" обязательны!';
            } else {
                if (isset($_POST['contentbox_is_add_img'])) {
                    ContentboxCore::setIsAddImg(strip_tags($_POST['contentbox_is_add_img']));
                }

                if (isset($_POST['contentbox_api_token'])) {
                    ContentboxCore::setApiToken(strip_tags($_POST['contentbox_api_token']));
                }

                if (isset($_POST['contentbox_api_id'])) {
                    ContentboxCore::setApiID(strip_tags($_POST['contentbox_api_id']));
                }

                if (isset($_POST['contentbox_cat'])) {
                    ContentboxCore::setCategory(strip_tags($_POST['contentbox_cat']));
                }

                if (isset($_POST['contentbox_post_status'])) {
                    ContentboxCore::setPostStatus(strip_tags($_POST['contentbox_post_status']));
                }

                if (isset($_POST['contentbox_shedule_type'])) {
                    ContentboxCore::setSheduleType(strip_tags($_POST['contentbox_shedule_type']));
                }

                $messages[] = __('Настройки сохранены', 'contentbox');
            }
        }

        //Иницализация
        $shedule_types = $contentbox_post_status = Contentbox::contentbox_custom_intervals(array());

        $contentbox_is_add_img = ContentboxCore::getIsAddImg();
        $contentbox_api_token = ContentboxCore::getApiToken();
        $contentbox_api_id = ContentboxCore::getApiID();
        $contentbox_cat = ContentboxCore::getCategory();
        $contentbox_post_status = ContentboxCore::getPostStatus();
        $contentbox_shedule_type = ContentboxCore::getSheduleType();

        $sheduleHtml = '';
        if ($shedule_types) {
            foreach ($shedule_types as $sheduleId => $sheduleArr) {
                $sheduleHtml .= '<option value="' . $sheduleId . '" ' . ($contentbox_shedule_type == $sheduleId ? 'selected' : '') . '>' . $sheduleArr['display'] . '</option>';
            }
        }

        $statusesHtml = '<option value="">-</option>';
        foreach (get_post_statuses() as $statusID => $statusName) {
            $statusesHtml .= '<option value="' . $statusID . '" ' . ($contentbox_post_status == $statusID ? 'selected' : '') . '>' . $statusName . '</option>';
        }

        $catsHtml = '<option value="">-</option>';
        $argsArr = array(
            'type' => 'post',
            'child_of' => 0,
            'parent' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => 0,
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'number' => 0,
            'taxonomy' => 'category',
            'pad_counts' => false,
        );
        $catsArr = get_categories($argsArr);
        if ($catsArr) {
            foreach ($catsArr as $catArr) {
                $catsHtml .= '<option value="' . $catArr->term_id . '" ' . ($contentbox_cat == $catArr->term_id ? 'selected' : '') . '>' . $catArr->name . '</option>';
            }
        }

        //Инициализация wp-cron
        if ($contentbox_shedule_type != $contentbox_shedule_type_old) {
            ContentboxCore::initShedule($contentbox_shedule_type);
        }

        //СИНХРОНИЗАЦИЯ
        if (isset($_POST['contentbox_sync']) and $_POST['contentbox_sync']) {
            $contentboxCore = new ContentboxCore($contentbox_api_token);
            $result = $contentboxCore->sync();

            if ($result['success'] > 0) {
                $messages[] = 'Синхронизировано - ' . $result['success'];
            }

            if ($result['failed'] > 0) {
                $messages[] = 'Ошибка синхронизации - ' . $result['failed'];
            }

            if ($result['success'] === 0 and $result['failed'] === 0) {
                $messages[] = 'Нет публикаций для синхронизации';
            }
        }

        //Рендер
        echo '<div class="wrap">';
        echo '<h2>Контент-отдел contentBox</h2>';
        echo '<div id="poststuff">';
        echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '">';
        echo '<div class="postbox" >';
        echo '	
                
                <h3 class="hndle"><span>Настройки</span></h3>
                <div class="inside">
                    <p>Получить токен для доступа к API можно в личном кабинете на сайте <a href="https://contentbox.ru" target="_blank">contentbox.ru</a></p>                    
                    <table>
                        <tr>
                            <td>API ID</td>
                            <td><input id="contentbox_api_id" name="contentbox_api_id" value="' . $contentbox_api_id . '"></td>
                        </tr>
                        <tr>
                            <td>API Token</td>
                            <td><input id="contentbox_api_token" name="contentbox_api_token" value="' . $contentbox_api_token . '"></td>
                        </tr>							
                        <tr>
                            <td>Рубрика</td>
                            <td><select name="contentbox_cat">' . $catsHtml . '</select></td>
                        </tr>	
                        <tr>
                            <td>Статус для публикации</td>
                            <td><select name="contentbox_post_status">' . $statusesHtml . '</select></td>
                        </tr>	
                        <tr>
                            <td>Период синхронизации</td>
                            <td><select name="contentbox_shedule_type">' . $sheduleHtml . '</select></td>
                        </tr>
                        <tr>
                            <td>Добавлять картинки в конце текста?</td>
                            <td><input type="checkbox" name="contentbox_is_add_img" ' . ($contentbox_is_add_img ? 'checked' : '') . '></td>
                        </tr>							
                    </table>
                </div>
                ';

        echo '</div>';

        if (count($messages) > 0) {
            foreach ($messages as $message) {
                echo '<p><b style="color:#0073aa">' . $message . '</b></p>';
            }
        }

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                echo '<p><b style="color:#d54e21">' . $error . '</b></p>';
            }
        }

        echo '<p>
                <input type="submit" class="button-primary" name="contentbox_save" value="' . __('Сохранить', 'contentbox') . '"> 
                <input type="submit" class="button-secondary" name="contentbox_sync" value="Синхронизировать">
              </p>';

        echo '</form>';

        echo '</div></div>';

    }
}
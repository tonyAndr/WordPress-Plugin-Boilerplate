<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Yt_Link_Fixer
 * @subpackage Yt_Link_Fixer/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <?php
    $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'replace';

    $op_n = $this->plugin_name."-settings"; #option name
    $db = new Yt_Link_Fixer_DB();
    $parser = new Yt_Link_Fixer_Post_Parser($this->plugin_name);

    $options = get_option($op_n);

    ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=yt-link-fixer&tab=replace" class="nav-tab <?php echo $active_tab == 'replace' ? 'nav-tab-active' : ''; ?>">Поиск и замена</a>
        <a href="?page=yt-link-fixer&tab=options" class="nav-tab <?php echo $active_tab == 'options' ? 'nav-tab-active' : ''; ?>">Опции плагина</a>
    </h2>

    <?php if ($active_tab === "replace"): ?>

    <div class="ytlf-replace-tab-container">

        <div class="links-table">
            <div class="info-text">
                На этой вкладке представлены все нерабочие ссылки, которые были/будут найдены при проверке записей.
                <br><br>
                Поиск производится <u>автоматически дважды в день</u>, каждый раз проверяя определенное количество записей и страниц. Учитываются только опубликованные записи стандартных и произвольных типов.<br><br>
                Для замены видео в ручном режиме, воспользуйтесь кнопкой <strong>"Найти видео"</strong> - результаты поиска будут представлены под соответсвующей строкой.
            </div>

            <h2>Broken Links</h2>

            <div>
                <?php

                $rows = $db->get_rows();
                    ?>
                    <table>
                        <thead>
                        <tr>
                            <th><?php _e("Post Id", $this->plugin_name); ?></th>
                            <th><?php _e("Video Id", $this->plugin_name); ?></th>
                            <th><?php _e("Status", $this->plugin_name); ?></th>
                            <th><?php _e("Action", $this->plugin_name); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (!$rows) {
                            ?>
                            <tr><td colspan="4"><?php _e("No broken links found", $this->plugin_name); ?></td> </tr>
                            <tr><td colspan="4"></td></tr>
                        <?php
                        } else {
                            foreach ($rows as $row) {
                                echo "<tr id='data_item_id_" . $row->id . "'>
                                    <td><a href='" . get_edit_post_link($row->post_id) . "' title='Edit Post'>" . $row->post_id . "</a></td>
                                    <td><a href='https://www.youtube.com/watch?v=" . $row->vid_id . "' title='Check on YouTube'>" . $row->vid_id . "</td>
                                    <td>" . $row->status . "</td>
                                    <td>
                                        <button name=\"replace_action\" id=\"get_suggestions_" . $row->id . "\" class=\"button button-small\" value=\"" . $row->id . "\">Найти видео</button>
                                        <button name=\"replace_action\" id=\"hide_suggestions_" . $row->id . "\" class=\"button button-small ytlf-hide\" value=\"" . $row->id . "\">Свернуть</button>
                                    </td>
                                  </tr>
                                  
                                  <tr id='sugg_item_id_" . $row->id . "'><td colspan='4' id='suggestions_item_" . $row->id . "'></td></tr>";
                            }
                        }
                        ?>
                        </tbody>
                    </table>
            </div>
        </div>
        <div class="pro-options">

            <div id="suggestions_container">

            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="ytlf-options-tab-container">
        <div class="left">
            <h2 class="nav-tab-wrapper"><?php _e("Settings", $this->plugin_name); ?></h2>

            <form method="post" action="options.php">

                <?php

                $auto_replace = $options['auto_replace'];
                $replace_not_embeddable = $options['replace_not_embeddable'];
                $email_notify = $options['email_notify'];

                $apiv3 = new Yt_Link_Fixer_ApiV3($this->plugin_name);

                if (isset($_POST["clear_cache"])) {
                    $apiv3->clear_cache();
                }

                settings_fields( $op_n );
                do_settings_sections( $op_n );

                ?>
                <h3><?php _e("General", $this->plugin_name); ?></h3>
                <p>Авто-замена видео по расписанию:</p>
                <Label for="<?php echo $op_n; ?>-auto_replace"><?php _e("Do auto replace with cron?", $this->plugin_name); ?></Label>
                <input type="checkbox" value="1" name="<?php echo $op_n; ?>[auto_replace]" id="<?php echo $op_n; ?>-auto_replace" <?php checked($auto_replace, 1); ?> />
                <br>
                <!--        <Label for="--><?php //echo $op_n; ?><!---replace_not_embeddable">Auto replace not embeddable links?</Label>-->
                <!--        <input type="checkbox" value="1" name="--><?php //echo $op_n; ?><!--[replace_not_embeddable]" id="--><?php //echo $op_n; ?><!---replace_not_embeddable" --><?php //checked($replace_not_embeddable, 1); ?><!-- />
        <br>-->
                <p>Еженедельные уведомления на почту о количестве нерабочих ссылок (если они найдены):</p>
                <Label for="<?php echo $op_n; ?>-email_notify"><?php _e("Send email notifications?", $this->plugin_name); ?></Label>
                <input type="checkbox" value="1" name="<?php echo $op_n; ?>[email_notify]" id="<?php echo $op_n; ?>-email_notify" <?php checked($email_notify, 1); ?> />
                <?php submit_button(__('Save', $this->plugin_name), 'primary','submit', TRUE); ?>


                <h3><?php _e("Cache", $this->plugin_name); ?></h3>

                <p>В кэше сохраняются результаты поиска через YouTube Api, чтобы не запрашивать удаленный ресурс множество раз для одной и той же статьи, ведь результаты будут одинаковыми.</p>
                <p>Кэш обнуляется автоматически раз в неделю, но вы можете сбросить его вручную с помощью кнопки ниже. </p>

                <?php submit_button(__('Clear cache', $this->plugin_name), 'primary','clear_cache', TRUE); ?>
            </form>

        </div>
        <div class="right">
            <h2 class="nav-tab-wrapper"><?php _e("Manual search", $this->plugin_name); ?></h2>
            <p>Ручной поиск нерабочих ссылок. Можно выбрать количество постов обрабатываемых за один вызов функции. Следующий вызов продолжит поиск с последней проверенной записи.</p>
            <p>Поиск занимает некоторое время, в зависимости от количества проверяемых записей. В будущем будет прикручен поиск без обновления страницы и отображение прогресса.</p>
            <form method="post" action="tools.php?page=yt-link-fixer&tab=options">

                <?php

                settings_fields( $op_n );
                do_settings_sections( $op_n );

                if (isset($_POST["manual_parse"])) {
                    $parser->fetch_posts();
                }

                ?>

                <Label for="posts_num">Posts by step</Label><br>
                <input type="number" value="50" name="posts_num" id="posts_num"/>

                <?php submit_button(__('Search', $this->plugin_name), 'primary','manual_parse', false); ?>
            </form>

        </div>
    </div>

    <h2 class="nav-tab-wrapper"><?php _e("Event Log", $this->plugin_name); ?></h2>

    <form method="post" action="tools.php?page=yt-link-fixer&tab=options">

        <?php
        settings_fields( $op_n );
        do_settings_sections( $op_n );

        $logger = new Yt_Link_Fixer_Logging();


        if (isset($_POST["clear_log"])) {
            $logger->clear_log();
        }

        ?>
        <p>Самые свежие события выводятся сверху.</p>
        <p>Формат: Дата | [Модуль] | [Статус] | Текст сообщения или ошибки.</p>

        <textarea id="log" name="log" rows="10" style="width: 100%"><?php $log = $logger->read(); if (!$log) {_e("The log is empty", $this->plugin_name);} else { echo $log;} ?></textarea>

        <?php submit_button(__('Clear log', $this->plugin_name), 'primary','clear_log', TRUE); ?>

    </form>



    <?php endif; ?>

</div>
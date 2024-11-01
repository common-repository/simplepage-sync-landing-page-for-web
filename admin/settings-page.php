<?php
$simplepageSettings = get_option('simplepageSettings');
?>
<div id="simplepage-options" class="wrap simplepage-wrap">
    <h1><?php _e('SimplePage – Sync Landing Page For Web', 'simplepage') ?></h1>
    <div class="simplepage-body">
        <form method="post" action="options.php" id="simplepageOptions">
            <?php echo settings_fields('simplepageSettingsGroup'); ?>
            <table class="form-table" id="simplepageFormTable">
                <tr>
                    <th><?php _e('List of created Templates','simplepage');?></th>
                    <td>
                        <?php
                        if (isset($simplepageSettings['templates']) && $simplepageSettings['templates'] != '' && !empty($simplepageSettings['templates'])) {
                            foreach ($simplepageSettings['templates'] as $slug => $name) {
                                echo '<p class="templateSimplePage"><span>' . $name . '</span> <a href="#!" class="removeTemplate" data-slug="' . $slug . '"><img 
                                src="' . plugin_dir_url(__DIR__) . '/images/remove.png"
                                title="Xóa template này" style="width:20px"></a></p>';
                            }
                        } else {
                            echo 'Chưa có Template nào được tạo.';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="TokenSimplePage">Token SimplePage</label></th>
                    <td>
                        <input type="text" name="simplepageSettings[TokenSimplePage]" id="TokenSimplePage"
                               class="regular-text"
                               placeholder="FHSDFSFSEOIASOIHO"
                               required
                               value="<?php echo isset($simplepageSettings['TokenSimplePage']) && $simplepageSettings['TokenSimplePage'] != '' ? $simplepageSettings['TokenSimplePage'] : '' ?>"/>
                        <p class="description"><?php _e('See instructions for getting Token SimplePage:','simplepage');?> <a
                                    href="https://wiki.simplepage.vn/hd-lay-key-landing-page" target="_blank"><?php _e('Here','simplepage');?></a>.</p>
                    </td>
                </tr>
                <tr id="getListLDP">
                    <th></th>
                    <td>
                        <button class="button-primary"><?php _e('Get list Landing Page','simplepage');?></button>
                        <img class="imgLoading hidden" src="<?php echo plugin_dir_url(__DIR__) . '/images/loading-icon-red.gif';?>" alt="loading" width="30px" style="vertical-align: bottom">
                    </td>
                </tr>
                <tr class="hidden" id="listLDP">
                    <th><?php _e('List of created Landing Page','simplepage');?></th>
                    <td>
                        <select name="simplepageSettings[listLDP]" required>
                            <option value=""><?php _e('- Choose Landing Page -','simplepage');?></option>
                        </select>
                    </td>
                </tr>
                <tr class="hidden" id="listPage">
                    <th><?php _e('List of published Pages','simplepage');?></th>
                    <td>
                        <select name="simplepageSettings[listPage]" required>
                            <option value=""><?php _e('- Choose Page -','simplepage');?></option>
                            <?php echo $this->simplepageLoadListPage(); ?>
                        </select>
                    </td>
                </tr>
                <input type="hidden" id="simplepageFavicon" name="simplepageSettings[favicon]">
                <input type="hidden" id="simplepageTemplates" name="simplepageSettings[templates]">
                <?php
                if (isset($simplepageSettings['templates']) && $simplepageSettings['templates'] != '' && !empty($simplepageSettings['templates'])) {
                    foreach ($simplepageSettings['templates'] as $slug => $name) {
                        echo '<input type="hidden" name="simplepageSettings[templates][' . $slug . ']" value="' . $name . '">';
                    }
                }
                ?>
            </table>
            <?php submit_button(__('Connect Project', 'simplepage'), 'simplepageButton button-primary hidden'); ?>
        </form>
    </div>
</div>
<?php
if (isset($_GET['settings-updated'])) {
    $simplepageSettings = get_option('simplepageSettings');
    if (isset($simplepageSettings['rmConnectInPage']) && !empty($simplepageSettings['rmConnectInPage'])) {
        foreach ($simplepageSettings['rmConnectInPage'] as $template) {
            //Tìm ID post, nếu có thì đổi về mặc định không thì bỏ qua
            $spArgs = array(
                'posts_per_page' => -1,
                'post_type' => 'page',
                'meta_key' => '_wp_page_template',
                'meta_value' => $template
            );
            $spQuery = new WP_Query($spArgs);
            wp_reset_postdata();
            if ($spQuery->post_count) {
                foreach ($spQuery->posts as $spPage) {
                    update_post_meta($spPage->ID,'_wp_page_template','default');
                }
            }
        }
    }

    if (!isset($simplepageSettings['TokenSimplePage']) || $simplepageSettings['TokenSimplePage'] == '') {
        $message = 'Chưa nhập Token';
        echo '<div id="message" class="error notice is-dismissible"><p><strong>' . $message . '</strong></p></div>';
    } elseif (!isset($simplepageSettings['listLDP']) || $simplepageSettings['listLDP'] == '') {
        $message = 'Chưa chọn dự án Landing Page, vui lòng thử lại!';
        echo '<div id="message" class="error notice is-dismissible"><p><strong>' . $message . '</strong></p></div>';
    } elseif (!isset($simplepageSettings['listPage']) || $simplepageSettings['listPage'] == '') {
        $message = 'Chưa chọn trang cần liên kết, vui lòng thử lại!';
        echo '<div id="message" class="error notice is-dismissible"><p><strong>' . $message . '</strong></p></div>';
    } elseif (!isset($simplepageSettings['templates']) || empty($simplepageSettings['templates'])) {
        $message = 'Không tìm thấy template nào, vui lòng thử lại!';
        echo '<div id="message" class="error notice is-dismissible"><p><strong>' . $message . '</strong></p></div>';
    } else {
        $ldpSlug = $simplepageSettings['listLDP'];
        $idPage = $simplepageSettings['listPage'];
        $ldpFavicon = isset($simplepageSettings['favicon']) ? $simplepageSettings['favicon'] : '';
        $ldpName = $simplepageSettings['templates'][$ldpSlug . '.php'];
        $CreateTemplate = $this->simplepageCreateTemplate($ldpSlug, $ldpName,$ldpFavicon);
        if ($CreateTemplate) {
            $addTemplateInPage = $this->simplepageAddTemplateInPage($idPage, $CreateTemplate);
            if ($addTemplateInPage) {
                $message = __('Successful connect! View link created','simplepage').' <a href="' . get_home_url() . '?p=' . $idPage . '" target="_blank">'.__('here','simplepage').'</a>';
                echo '<div id="message" class="updated notice-success notice is-dismissible"><p><strong>' . $message . '</strong></p></div>';
            } else {
                $message = 'Liên kết thất bại, vui lòng thử lại!';
                echo '<div id="message" class="error notice is-dismissible"><p><strong>' . $message . '</strong></p></div>';
            }
        } else {
            $message = 'Tạo Template thất bại, vui lòng thử lại!';
            echo '<div id="message" class="error notice is-dismissible"><p><strong>' . $message . '</strong></p></div>';
        }
    }
}
?>

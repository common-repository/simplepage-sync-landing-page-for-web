<?php

/*
 * Plugin Name: SimplePage – Sync Landing Page For Web
 * Plugin URI: https://simplepage.vn
 * Description: Sync the designed Landing Page projects at SimplePage.vn on your Wordpress Website.
 * Version: 1.2.0
 * Author: SimplePage
 * Author URI: https://simplepage.vn
 * Domain Path: /languages/
 * Text Domain: simplepage
 */
if (!defined('ABSPATH')) exit; // Don't allow direct access

if (!class_exists('SimplePageSLPFW')) :
    class SimplePageSLPFW
    {
        protected $templates;

        public function __construct()
        {
            $this->templates = array();
            $simplepageSettings = get_option('simplepageSettings');

            //Backend
            add_action('admin_enqueue_scripts', [$this, 'simplepageAdminScriptAndStyle']);
            add_action('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'simplepageSettingsLink']);
            add_action('admin_menu', [$this, 'simplepageCreateMenu']);
            add_action('admin_init', [$this, 'simplepageRegisterSettings']);
            add_action('wp_ajax_getListLandingPage',[$this,'getListLandingPage']); //login

            //Add template
            if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {
                // 4.6 and older
                add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'register_project_templates' ) );
            } else {
                add_filter( 'theme_page_templates', [ $this, 'add_new_template'] );
            }

            add_filter('wp_insert_post_data', [ $this, 'register_project_templates' ] );
            add_filter('template_include', [ $this, 'view_project_template'] );

            if(isset($simplepageSettings['TokenSimplePage']) && $simplepageSettings['TokenSimplePage'] != ''){
                if(isset($simplepageSettings['templates']) && !empty($simplepageSettings['templates'])) {
                    $this->templates = $simplepageSettings['templates'];
                }
            }

            //Frontend
        }

        public function simplepageAdminScriptAndStyle()
        {
            wp_deregister_style('simplepage-admin-style-css');
            wp_register_style('simplepage-admin-style-css', plugins_url('/admin/css/style.css', __FILE__));
            wp_enqueue_style('simplepage-admin-style-css');

            $simplepageNonce = array('simplepageGetLDP_nonce' => wp_create_nonce('simplepageGetLDP_wp'));
            wp_deregister_script('simplepage-admin-script-js');
            wp_register_script('simplepage-admin-script-js', plugins_url('/admin/js/script.js', __FILE__), array('jquery'), $this->simplepageVersion(), true);
            wp_localize_script('simplepage-admin-script-js','simplepageGetLDP', $simplepageNonce );
            wp_enqueue_script('simplepage-admin-script-js');
        }

        public function simplepageSettingsLink($links)
        {
            $settings_url = admin_url('admin.php?page=simplepageslpfw');
            $settings_link = '<a href="' . $settings_url . '">' . __('Settings', 'simplepage') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        public function simplepagePluginLinks($links, $file)
        {
            if ($file == plugin_basename(dirname(__FILE__) . '/simplepage-sync-landing-page-for-web.php')) {
                $links[] = '<a href="https://simplepage.vn/simplepage-sync-landing-page-for-web" target="_blank">' . __('Document', 'simplepage') . '</a>';
            }
            return $links;
        }

        public function simplepageVersion()
        {
            $plugin_data = get_plugin_data(__FILE__);
            $plugin_version = $plugin_data['Version'];
            return $plugin_version;
        }

        public function simplepageRegisterSettings()
        {
            register_setting('simplepageSettingsGroup', 'simplepageSettings');
        }

        public function simplepageCreateMenu()
        {
            add_menu_page(__('SimplePage Settings', 'simplepage'), __('SimplePage', 'simplepage'), 'manage_options', 'simplepageslpfw', [$this, 'simplepageSettingsPage'], 'dashicons-welcome-widgets-menus', 20);
        }

        public function simplepageSettingsPage()
        {
            include plugin_dir_path(__FILE__) . 'admin/settings-page.php';
        }

        public function simplepageLoadListPage()
        {
            $listPage = '';
            $args = array(
                'post_type' => array('page'),
                'post_status' => array('publish'),
                'posts_per_page' => '-1',
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $listPage .= '<option value="' . get_the_ID() . '">' . get_the_title() . '</option>';
                }
            }
            wp_reset_postdata();
            return $listPage;
        }

        public function simplepageCreateTemplate($ldpSlug = '', $ldpName = '', $ldpFavicon = '')
        {
            if ($ldpSlug != '' && $ldpName != '') {
                $newFileName = plugin_dir_path(__FILE__) . 'templates/' . $ldpSlug . '.php';
                $domain = 'https://builder.simplepage.vn/';

                $newFileContent = '<?php
/*
Template Name: ' . $ldpName . '
*/
$result = "' . $domain . $ldpSlug . '";
$arrContextOptions=array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false));
$favicon = "' . $ldpFavicon . '";
$urlCurrent = get_permalink(get_the_ID());
$resultFileGet = file_get_contents($result, false, stream_context_create($arrContextOptions));
$html = new DOMDocument();
libxml_use_internal_errors(true);
$html->loadHTML($resultFileGet);
$xp = new DOMXPath($html);
$head = $html->getElementsByTagName("head")[0];
$canonicalLink = $xp->query("//link[@rel=\'canonical\']");
if ($canonicalLink->length == 0) {
    $newTag = $html->createElement("link");
    $newTag->setAttribute("rel", "canonical");
    $newTag->setAttribute("href", $urlCurrent);
    $head->insertBefore($newTag, $head->firstChild);
} else {
    $canonicalLink[0]->setAttribute("href", $urlCurrent);
}
$ogUrlMeta = $xp->query("//meta[@property=\'og:url\']");
if ($ogUrlMeta->length == 0) {
    $newTag = $html->createElement("meta");
    $newTag->setAttribute("property", "og:url");
    $newTag->setAttribute("content", $sslText . $domainCname);
    $head->insertBefore($newTag, $head->firstChild);
} else {
    $ogUrlMeta[0]->setAttribute("content", $urlCurrent);
}
$ogRobotsMeta = $xp->query("//meta[@name=\'robots\']");
if ($ogRobotsMeta->length == 0) {
    $newTag = $html->createElement("meta");
    $newTag->setAttribute("name", "robots");
    $newTag->setAttribute("content", "index, follow");
    $head->insertBefore($newTag, $head->firstChild);
} else {
    $ogRobotsMeta[0]->setAttribute("content", "index, follow");
}
$ogGgbotMeta = $xp->query("//meta[@name=\'googlebot\']");
if ($ogGgbotMeta->length == 0) {
    $newTag = $html->createElement("meta");
    $newTag->setAttribute("name", "googlebot");
    $newTag->setAttribute("content", "index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1");
    $head->insertBefore($newTag, $head->firstChild);
} else {
    $ogGgbotMeta[0]->setAttribute("content", "index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1");
}
$ogBingbotMeta = $xp->query("//meta[@name=\'bingbot\']");
if ($ogBingbotMeta->length == 0) {
    $newTag = $html->createElement("meta");
    $newTag->setAttribute("name", "bingbot");
    $newTag->setAttribute("content", "index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1");
    $head->insertBefore($newTag, $head->firstChild);
} else {
    $ogBingbotMeta[0]->setAttribute("content", "index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1");
}
if($favicon) {
    $appleTouchIconLink = $xp->query("//link[@rel=\'apple-touch-icon\']");
    if ($appleTouchIconLink->length == 0) {
        $newTag = $html->createElement("link");
        $newTag->setAttribute("rel", "apple-touch-icon");
        $newTag->setAttribute("href", $favicon);
        $head->insertBefore($newTag, $head->firstChild);
    } else {
        $appleTouchIconLink[0]->setAttribute("href", $favicon);
    }

    $sizes32Link = $xp->query("//link[@sizes=\'32x32\']");
    if ($sizes32Link->length == 0) {
        $newTag = $html->createElement("link");
        $newTag->setAttribute("rel", "icon");
        $newTag->setAttribute("sizes", "32x32");
        $newTag->setAttribute("href", $favicon);
        $head->insertBefore($newTag, $head->firstChild);
    } else {
        $sizes32Link[0]->setAttribute("href", $favicon);
    }

    $sizes192Link = $xp->query("//link[@sizes=\'192x192\']");
    if ($sizes192Link->length == 0) {
        $newTag = $html->createElement("link");
        $newTag->setAttribute("rel", "icon");
        $newTag->setAttribute("sizes", "192x192");
        $newTag->setAttribute("href", $favicon);
        $head->insertBefore($newTag, $head->firstChild);
    } else {
        $sizes192Link[0]->setAttribute("href", $favicon);
    }
}
$projectLinkInput = $xp->query("//input[@name=\'projectLink\']");
if ($projectLinkInput->length !== 0) {
    foreach ($projectLinkInput as $projectLinkip) {
        $projectLinkip->setAttribute("value", "https://".$_SERVER[\'HTTP_HOST\'].$_SERVER[\'REQUEST_URI\']);
    }
}
echo $html->saveHTML();
exit;';
                if (file_put_contents($newFileName, $newFileContent) !== false) {
                    return $ldpSlug . '.php';
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        public function simplepageAddTemplateInPage($postID = 0, $value = '')
        {
            if ($postID != 0 && $value != '') {
                $addTemplate = add_post_meta($postID, '_wp_page_template', $value,true);
                if ($addTemplate) {
                    return $addTemplate;
                } else {
                    $updateTemplate = update_post_meta($postID, '_wp_page_template', $value);
                    if ($updateTemplate) {
                        return $updateTemplate;
                    } else {
                        return true;
                    }
                }
            } else {
                return false;
            }

        }

        public function getListLandingPage() {
            if(isset($_POST['simplepageNonce']) || wp_verify_nonce($_POST['simplepageNonce'],'simplepageGetLDP_wp')){
                if(isset($_POST['tokenSimplePage']) && $_POST['tokenSimplePage'] != '' ? $_POST['tokenSimplePage'] : '') {
                    $tokenSimplePage = sanitize_text_field($_POST['tokenSimplePage']);
                    $url = sprintf(
                        "https://builder.simplepage.vn/api/getlistprojectofuser?simplepageKey=%s&domain=%s",
                        $tokenSimplePage, get_home_url()
                    );
                    $arrContextOptions = array(
                        "ssl" => array(
                            "verify_peer" => false,
                            "verify_peer_name" => false,
                        ),
                    );
                    $jsonString = file_get_contents($url, false, stream_context_create($arrContextOptions));
                    if ($jsonString) {
                        echo $jsonString;
                    }
                }
            }
            exit;
        }

        public function add_new_template($posts_templates)
        {
            $posts_templates = array_merge($posts_templates, $this->templates);
            return $posts_templates;
        }

        public function register_project_templates($atts)
        {

            // Create the key used for the themes cache
            $cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());

            // Retrieve the cache list.
            // If it doesn't exist, or it's empty prepare an array
            $templates = wp_get_theme()->get_page_templates();
            if (empty($templates)) {
                $templates = array();
            }

            // New cache, therefore remove the old one
            wp_cache_delete($cache_key, 'themes');

            // Now add our template to the list of templates by merging our templates
            // with the existing templates array from the cache.
            $templates = array_merge($templates, $this->templates);

            // Add the modified cache to allow WordPress to pick it up for listing
            // available templates
            wp_cache_add($cache_key, $templates, 'themes', 1800);

            return $atts;

        }

        public function view_project_template($template)
        {

            // Get global post
            global $post;

            // Return template if post is empty
            if (!$post) {
                return $template;
            }

            // Return default template if we don't have a custom one defined
            if (!isset($this->templates[get_post_meta(
                    $post->ID, '_wp_page_template', true
                )])) {
                return $template;
            }

            $file = plugin_dir_path(__FILE__) . 'templates/' . get_post_meta(
                    $post->ID, '_wp_page_template', true
                );

            // Just to be safe, we check if the file exist first
            if (file_exists($file)) {
                return $file;
            } else {
                echo $file;
            }

            // Return template
            return $template;

        }
    }

    new SimplePageSLPFW;
endif;

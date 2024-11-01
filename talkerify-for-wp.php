<?php
/*
Plugin Name: Talkerify for WP
Plugin URI: https://www.talkerify.com/blog/talkerify-for-wp/
Description: A plugin allows to easily install a <a href="https://www.talkerify.com/">Talkerify</a> widget on their wordpress website.
Version: 1.0.0
Author: Talkerify
Author URI: https://www.talkerify.com/
*/

$plugurldir = get_option('siteurl') . '/' . PLUGINDIR . '/talkerify-for-wp/';
$tlkf_domain = 'TalkerifyForWP';
load_plugin_textdomain($tlkf_domain, 'wp-content/plugins/talkerify-for-wp');
add_action('init', 'tlkf_init');
add_action('wp_footer', 'tlkf_insert');
add_action('admin_notices', 'tlkf_admin_notice');
add_filter('plugin_action_links', 'tlkf_plugin_actions', 10, 2);

function tlkf_init()
{
    if (function_exists('current_user_can') && current_user_can('manage_options')) {
        add_action('admin_menu', 'tlkf_add_settings_page');
    }

    if (!function_exists('get_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
}

function tlkf_settings()
{
    register_setting('talkerify-for-wp-group', 'tlkfID');
    register_setting('talkerify-for-wp-group', 'tlkfEnable');
    register_setting('talkerify-for-wp-group', 'tlkfExcluded');
    register_setting('talkerify-for-wp-group', 'tlkfExcludedList');
    add_settings_section('talkerify-for-wp', "Talkerify for WP", "", 'talkerify-for-wp-group');
}

function plugin_get_version()
{
    if (!function_exists('get_plugins'))
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    $plugin_folder = get_plugins('/' . plugin_basename(dirname(__FILE__)));
    $plugin_file = basename((__FILE__));
    return $plugin_folder[$plugin_file]['Version'];
}

function tlkf_insert()
{
    $display = true;

    if (!get_option('tlkfID')) {
        $display = false;
    }

    if (!get_option('tlkfEnable')) {
        $display = false;
    }

    if (get_option('tlkfExcluded')) {
        $excluded_url_list = get_option('tlkfExcludedList');
        $current_url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $current_url = urldecode($current_url);
        $excluded_url_list = preg_split("/,/", $excluded_url_list);
        foreach($excluded_url_list as $exclude_url)
        {
            $exclude_url = urldecode(trim($exclude_url));
            if (strpos($current_url, $exclude_url) !== false) {
                $display = false;
            }
        }
    }

    if (!$display) {
        return;
    }

    global $current_user;
    echo("<script type=\"text/javascript\">
var __p = {};
__p.license = \"" . get_option('tlkfID') . "\";");
    if (0 != $current_user->ID) {
        echo("__p.visitor = {
email: \"" . $current_user->user_email . "\",
first_name: \"" . $current_user->user_firstname . "\",
last_names: \"" . $current_user->user_lastname . "\",};");
    }

    if (0 != $current_user->ID) {
        echo("__p.params = [
{name: 'UserId', value: " . $current_user->ID . "},
{name: 'UserName', value: \"" . $current_user->user_login . "\"},];");
    }

    echo("    (function() {
    var p = document.createElement('script'); p.type = 'text/javascript'; p.async = true;
    p.src = 'https://www.talkerify.com/static/widget.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(p, s);
})();");
    echo("</script>");
}

function tlkf_admin_notice()
{
    if (!get_option('tlkfID')) {
        echo('<div class="error"><p><strong>' . sprintf(__('Talkerify for WP is disabled. Please go to the <a href="%s">plugin page</a> and enter a valid widget id to enable it.'), admin_url('options-general.php?page=talkerify-for-wp')) . '</strong></p></div>');
    }
}

function tlkf_plugin_actions($links, $file)
{
    static $this_plugin;
    if (!$this_plugin)
        $this_plugin = plugin_basename(__FILE__);
    if ($file == $this_plugin && function_exists('admin_url')) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=talkerify-for-wp') . '">' . __('Settings', $tlkf_domain) . '</a>';
        array_unshift($links, $settings_link);
    }
    return ($links);
}

function tlkf_add_settings_page()
{
    function tlkf_settings_page()
    {
        global $tlkf_domain, $plugurldir;
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>
                <?php _e('Talkerify for WP', $tlkf_domain); ?>
                <small>
                    <? echo plugin_get_version(); ?>
                </small>
            </h2>
            <div class="metabox-holder meta-box-sortables ui-sortable pointer">
                <div class="postbox" style="float:left;width:35em;margin-right:20px">
                    <h3 class="hndle">
                        <span>
                            <?php _e('Talkerify Widget Settings', $tlkf_domain); ?>
                        </span>
                    </h3>
                    <div class="inside" style="padding: 0 10px">
                        <p style="text-align:center">
                            <a href="https://www.talkerify.com/" title="<?php _e('Talk with your customers in real-time.', $tlkf_domain); ?>">
                                <img src="<?php echo($plugurldir); ?>talkerify.png" height="69" width="253" alt="<?php _e('Talkerify Logo', $tlkf_domain); ?>"/>
                            </a>
                        </p>

                        <form method="post" action="options.php">
                            <?php settings_fields('talkerify-for-wp-group'); ?>
                            <p>
                                <label for="tlkfID">
                                    <?php printf(__('Enter your %1$s...%2$sTalkerify%3$s widget id below to activate the plugin.', $tlkf_domain), '<strong><a href="https://www.talkerify.com/" title="', '">', '</a></strong>'); ?>
                                </label>
                                <input type="text" name="tlkfID" value="<?php echo get_option('tlkfID'); ?>" style="width:100%"/>
                            </p>

                            <p>
                                <label for="tlkfEnable">
                                    <?php echo(__('Always show Talkerify widget on every page', $tlkf_domain)); ?>
                                    <input type="checkbox" name="tlkfEnable" style="margin-left: 10px;" <?php if (get_option('tlkfEnable')) { echo('checked'); } ?>
                                </label>
                            </p>

                            <p>
                                <label for="tlkfExcluded">
                                    <?php echo(__('Exclude on specific url', $tlkf_domain)); ?>
                                    <input type="checkbox" name="tlkfExcluded" style="margin-left: 10px;" <?php if (get_option('tlkfExcluded')) { echo('checked'); } ?>
                                </label>
                            </p>

                            <p>
                                <textarea style="width: 100%;" rows="10" name="tlkfExcludedList"><?php echo get_option('tlkfExcludedList'); ?></textarea>
                                <label for="tlkfExcludedList">
                                    <?php echo(__('Enter fragment or slug of the url where you <b>don\'t</b> want the widget to show.', $tlkf_domain)); ?>
                                    <br>
                                    <?php echo(__('e.g. ' . $_SERVER["HTTP_HOST"] . '/contact-us/', $tlkf_domain)); ?>
                                    <br>
                                    <?php echo(__('to exclude this page from displaying the talkerify widget input <b>contact-us</b>', $tlkf_domain)); ?>
                                    <br>
                                    <?php echo(__('Separate entries with comma (,).', $tlkf_domain)); ?>
                                </label>
                            </p>

                            <p class="submit">
                                <input type="submit" class="button-primary" value="<?php _e('Save'); ?>"/>
                            </p>
                        </form>

                        <p style="font-size:smaller;color:#4490e3;background-color:#d5eeff;padding:0.4em 0.6em !important;border:1px solid #aad0e6;-moz-border-radius:3px;-khtml-border-radius:3px;-webkit-border-radius:3px;border-radius:3px">
                            <?php printf(__('Don&rsquo;t have an account? %1$sRegister for a free Talkerify account!%2$sRegister for a <strong>FREE</strong> Talkerify account right now!%3$s', $tlkf_domain), '<a href="https://www.talkerify.com" title="', '">', '</a>'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    add_action('admin_init', 'tlkf_settings');
    add_submenu_page('options-general.php', __('Talkerify for WP', $tlkf_domain), __('Talkerify for WP', $tlkf_domain), 'manage_options', 'talkerify-for-wp', 'tlkf_settings_page');
}
?>

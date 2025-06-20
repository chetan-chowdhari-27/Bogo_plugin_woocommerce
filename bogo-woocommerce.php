<?php
/**
 * Plugin Name: WooCommerce BOGO Discounts
 * Description: Adds BOGO (Buy One Get One) discount functionality to WooCommerce.
 * Version:     1.0.0
 * Author:      Aims InfoSoft
 * Author URI:  https://www.aimsinfosoft.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-bogo
 */

if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Define plugin path constant.
 */
define('WOCOMMERCE_BOGO_PLUGIN', plugin_dir_path(__FILE__));

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function my_woocommerce_plugin_is_woocommerce_active()
{
    return class_exists('WooCommerce');
}

/**
 * Show admin notice if WooCommerce is missing.
 *
 * @return void
 */
function my_woocommerce_plugin_admin_notice()
{
    echo '<div class="notice notice-error"><p><strong>WooCommerce BOGO Discounts</strong> requires WooCommerce to be installed and active. The plugin has been deactivated.</p></div>';
}

/**
 * Check and handle activation.
 *
 * @return void
 */
function my_woocommerce_plugin_activation_check()
{
    if (! my_woocommerce_plugin_is_woocommerce_active() ) {
        /**
            * Deactivate self. 
        */
        deactivate_plugins(plugin_basename(__FILE__));

        // Show admin notice.
        add_action('admin_notices', 'my_woocommerce_plugin_admin_notice');
    }
}
register_activation_hook(__FILE__, 'my_woocommerce_plugin_activation_check');

/**
 * Show notice if WooCommerce is missing post-activation or later.
 *
 * @return void
 */
function my_woocommerce_plugin_check_woocommerce_active_post_activation()
{
    if (current_user_can('activate_plugins') 
        && is_plugin_active(plugin_basename(__FILE__)) 
        && ! my_woocommerce_plugin_is_woocommerce_active()
    ) {
        // Deactivate plugin again in case activated manually (FTP, etc).
        deactivate_plugins(plugin_basename(__FILE__));
        add_action('admin_notices', 'my_woocommerce_plugin_admin_notice');
    }
}
add_action('admin_init', 'my_woocommerce_plugin_check_woocommerce_active_post_activation');

/**
 * Prevent WooCommerce from being deactivated while this plugin is active.
 *
 * @param  array  $actions     Array of plugin action links.
 * @param  string $plugin_file Plugin file path.
 * @return array Modified list of action links.
 */
function prevent_woocommerce_deactivation( $actions, $plugin_file )
{
    if ('woocommerce/woocommerce.php' === $plugin_file && is_plugin_active(plugin_basename(__FILE__)) ) {
        unset($actions['deactivate']);
        $actions['disabled'] = '<span style="color: #999;">Required by WooCommerce BOGO Plugin</span>';
    }
    return $actions;
}
add_filter('plugin_action_links', 'prevent_woocommerce_deactivation', 10, 4);

/**
 * Include required files.
 */
require_once WOCOMMERCE_BOGO_PLUGIN . 'includes/custom-function.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'includes/bogo-detail-page-option.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'includes/save-options.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'includes/ajax-functionality.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'includes/bogo-plugin-setting-page.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-x/buy-x-and-get-x.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-x/buy-x-and-get-x-ajax.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-x/buy-x-and-get-x-flash-sale.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-x/buy-x-and-get-x-counter-timer.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-y/buy-x-and-get-y.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-y/buy-x-and-get-y-ajax.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-y/buy-x-and-get-y-flash-sale.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/buy-x-and-get-y/buy-x-and-get-y-counter-timer.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'includes/bogo-flash-sales.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'features/cart-adjustment/cart-adjustment.php';
require_once WOCOMMERCE_BOGO_PLUGIN . 'includes/class-wc-bogo-deals.php';

/**
 * Enqueue front-end styles.
 *
 * @return void
 */
function wocommerce_bogo_enqueue_styles()
{
    wp_enqueue_style(
        'wocommerce-bogo-style',
        plugin_dir_url(__FILE__) . 'style.css',
        array(),
        '1.0.0',
        'all'
    );
}
add_action('wp_enqueue_scripts', 'wocommerce_bogo_enqueue_styles');

/**
 * Enqueue admin scripts and styles for BOGO CPT.
 *
 * @param  string $hook Current admin page hook.
 * @return void
 */
function enqueue_bogo_metabox_scripts( $hook )
{
    global $post;

    if ('post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }

    if (! isset($post) || 'wc_bogo' !== $post->post_type ) {
        return;
    }

    wp_enqueue_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array( 'jquery' ), '4.0.13', true);
    // For local JS file, use filemtime for versioning.
    wp_enqueue_style(
        'select2-css',
        '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
        array(),
        '4.0.13'
    );

    // For local JS file, use filemtime for versioning.
    wp_enqueue_script(
        'cart-adjustment-js',
        plugin_dir_url(__FILE__) . 'features/cart-adjustment/cart-adjustment-js.js',
        array( 'jquery', 'select2' ),
        filemtime(plugin_dir_path(__FILE__) . 'features/cart-adjustment/cart-adjustment-js.js'),
        true
    );

    wp_enqueue_script('buy-x-and-get-x-js', plugin_dir_url(__FILE__) . 'features/buy-x-and-get-x/buy-x-and-get-x.js', array( 'jquery', 'select2' ), '1.0', true);

    wp_enqueue_script('buy-x-and-get-y-js', plugin_dir_url(__FILE__) . 'features/buy-x-and-get-y/buy-x-and-get-y.js', array( 'jquery', 'select2' ), '1.0', true);
    // For local JS file, use filemtime for versioning.
    wp_enqueue_script(
        'cart-adjustment-js',
        plugin_dir_url(__FILE__) . 'features/cart-adjustment/cart-adjustment-js.js',
        array( 'jquery', 'select2' ),
        filemtime(plugin_dir_path(__FILE__) . 'features/cart-adjustment/cart-adjustment-js.js'),
        true
    );

    wp_enqueue_script('wc-bogo-admin-script', plugin_dir_url(__FILE__) . 'assets/js/wc-bogo-admin.js', array( 'jquery', 'select2' ), '1.0', true);
}
add_action('admin_enqueue_scripts', 'enqueue_bogo_metabox_scripts');

/**
 * Enqueue admin CSS for BOGO CPT.
 *
 * @return void
 */
function enqueue_bogo_admin_styles()
{
    global $post;

    if (! isset($post) || 'wc_bogo' !== $post->post_type ) {
        return;
    }

    wp_enqueue_style('bogo-admin-css', plugin_dir_url(__FILE__) . 'assets/css/bogo-admin.css', array(), '1.0');
}
add_action('admin_enqueue_scripts', 'enqueue_bogo_admin_styles');

/**
 * Register BOGO CPT.
 *
 * @return void
 */
function wc_bogo_register_cpt()
{
    $args = array(
    'label'               => __('BOGO Promotions', 'wc-bogo'),
    'public'              => false,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'menu_position'       => 25,
    'menu_icon'           => 'dashicons-cart',
    'capability_type'     => 'post',
    'supports'            => array( 'title', 'revisions' ),
    'show_in_rest'        => true,
    'hierarchical'        => false,
    'has_archive'         => false,
    'exclude_from_search' => true,
    'labels'              => array(
    'add_new'      => __('Add New Rule', 'wc-bogo'),
    'add_new_item' => __('Add New Rule', 'wc-bogo'),
    ),
    );
    register_post_type('wc_bogo', $args);
}
add_action('init', 'wc_bogo_register_cpt');

/**
 * Add BOGO Settings submenu under BOGO CPT menu.
 *
 * @return void
 */
function wc_bogo_add_settings_page()
{
    add_submenu_page(
        'edit.php?post_type=wc_bogo',
        __('BOGO Settings', 'wc-bogo'),
        __('BOGO Settings', 'wc-bogo'),
        'manage_options',
        'wc-bogo-settings',
        'wc_bogo_render_settings_page'
    );
}
add_action('admin_menu', 'wc_bogo_add_settings_page');

/**
 * Enqueue admin assets on BOGO settings pages.
 *
 * @param  string $hook Admin page hook.
 * @return void
 */
function wc_bogo_enqueue_admin_assets( $hook )
{
    if (strpos($hook, 'wc-bogo') === false ) {
        return;
    }

    wp_enqueue_style('wc-bogo-admin-style', plugins_url('assets/css/wc-bogo-admin.css', __FILE__), array(), time(), 'all');
}
add_action('admin_enqueue_scripts', 'wc_bogo_enqueue_admin_assets');

/**
 * Add custom columns to BOGO CPT list.
 *
 * @param  array $columns Existing columns.
 * @return array Modified columns.
 */
function wc_bogo_add_custom_columns( $columns )
{
    $columns = array(
    'cb'            => '<input type="checkbox" />',
    'title'         => __('Title', 'wc-bogo'),
    'discount_type' => __('Discount Type', 'wc-bogo'),
    'start_date'    => __('Start Date', 'wc-bogo'),
    'expired_on'    => __('Expired On', 'wc-bogo'),
    'status'        => __('Status', 'wc-bogo'),
    'date'          => __('Date'),
    'author'        => __('Author'),
    'usage_count'   => __('Usage Count', 'wc-bogo'),
    'deal_scope'    => __('Scope', 'wc-bogo'),
    );

    return $columns;
}
add_filter('manage_wc_bogo_posts_columns', 'wc_bogo_add_custom_columns');

// Bogo Saved Plugin 
// Prevent saving if title or discount type is invalid
add_filter('wp_insert_post_data', 'block_bogo_save_without_title_or_discount_type', 10, 2);
function block_bogo_save_without_title_or_discount_type($data, $postarr) {
    if ($data['post_type'] !== 'wc_bogo') {
        return $data;
    }
    $title = trim($data['post_title']);
    $default_title = 'Auto Draft';
    $is_title_invalid = empty($title) || $title === $default_title;
    $discount_type = isset($_POST['bogo_discount_type']) ? sanitize_text_field($_POST['bogo_discount_type']) : 'not_selected';
    $is_discount_invalid = $discount_type === 'not_selected';

    if (($is_title_invalid || $is_discount_invalid) && in_array($data['post_status'], ['publish', 'draft'])) {
        $data['post_status'] = 'auto-draft';

        add_filter('redirect_post_location', function($location) use ($is_title_invalid, $is_discount_invalid) {
            if ($is_title_invalid) {
                $location = add_query_arg('bogo_title_error', 1, $location);
            }
            if ($is_discount_invalid) {
                $location = add_query_arg('bogo_discount_error', 1, $location);
            }
            return $location;
        });
    }

    return $data;
}

// Show custom admin notices
add_action('admin_notices', function() {
    if (isset($_GET['bogo_title_error'])) {
        echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> You must enter a title before saving.</p></div>';
    }

    if (isset($_GET['bogo_discount_error'])) {
        echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Please select a discount type before saving.</p></div>';
    }
});

// Suppress default "Post published" or "updated" messages ONLY if there's an error
add_filter('post_updated_messages', function($messages) {
    global $post;
    if ($post && $post->post_type === 'wc_bogo') {
        $has_error = isset($_GET['bogo_title_error']) || isset($_GET['bogo_discount_error']);
        if ($has_error) {
            foreach ($messages['wc_bogo'] as &$msg) {
                $msg = ''; // Hide all messages
            }
        }
    }
    return $messages;
});
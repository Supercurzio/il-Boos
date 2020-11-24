<?php

/*
* Plugin Name: BetPress
* Plugin URI: http://www.web-able.com/betpress/
* Description: A game where users predict sports games (and not only) by placing betting slips.
* Author: WebAble modified by Supercurzio
* Author URI: http://www.ilboos.com
* Version: 1.0.2
*/


//don't allow direct access via url
if ( ! defined('ABSPATH') ) {
    exit();
}


global $betpress_version, $betpress_db_version;

$betpress_version = '1.0.11 Federico edition';
$betpress_db_version = '1.1.5';


//add some constants
define('BETPRESS_DIR_PATH', plugin_dir_path(__FILE__));
define('BETPRESS_MAIN_FILE_DIR', __FILE__);
define('BETPRESS_IMAGE_FOLDER', plugin_dir_url(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR);
define('BETPRESS_VIEWS_DIR', BETPRESS_DIR_PATH . 'views' . DIRECTORY_SEPARATOR);
define('BETPRESS_TIME', 'd-m-Y H:i O');
define('BETPRESS_TIME_NO_ZONE', 'd-m-Y H:i');
define('BETPRESS_TIME_HUMAN_READABLE', 'l jS \of F Y h:i:s A');
define('BETPRESS_DECIMAL', 'decimal');
define('BETPRESS_FRACTION', 'fraction');
define('BETPRESS_AMERICAN', 'american');
define('BETPRESS_SPORTS_TO_ADD_DURING_ACTIVATION', 2);
define('BETPRESS_EVENTS_TO_ADD_DURING_ACTIVATION', 2);
define('BETPRESS_BET_EVENTS_TO_ADD_DURING_ACTIVATION', 2);
define('BETPRESS_BET_EVENTS_CATS_TO_ADD_DURING_ACTIVATION', 2);
define('BETPRESS_STATUS_UNSUBMITTED', 'unsubmitted');
define('BETPRESS_STATUS_AWAITING', 'awaiting');
define('BETPRESS_STATUS_WINNING', 'winning');
define('BETPRESS_STATUS_LOSING', 'losing');
define('BETPRESS_STATUS_CANCELED', 'canceled');
define('BETPRESS_STATUS_TIMED_OUT', 'timed_out');
define('BETPRESS_STATUS_ACTIVE', 'active');
define('BETPRESS_STATUS_PAST', 'past');
define('BETPRESS_STATUS_FAIL', 'fail');
define('BETPRESS_STATUS_PAID', 'paid');
define('BETPRESS_VALUE_YES', 'yes');
define('BETPRESS_VALUE_ON', 'on');
define('BETPRESS_VALUE_ALL', 'all');
define('BETPRESS_IMPORT_NEW', 'new_data');
define('BETPRESS_IMPORT_UPDATE', 'update_data');
define('BETPRESS_POINTS', 'points');
define('BETPRESS_BOUGHT_POINTS', 'bought_points');

define('BETPRESS_XML_URL', 'http://www.ilboos.com/wp-content/plugins/web-able-betpress-federico-674293cf0e77/betpress.xml');


//include custom functions and db queries
if (file_exists(BETPRESS_DIR_PATH . 'functions.php')) {
    
    require_once 'functions.php';
}


//include wp ajax library
function betpress_add_ajax_library() {
    
    if (file_exists(BETPRESS_DIR_PATH . 'includes' . DIRECTORY_SEPARATOR . 'ajaxurl.php')) {
    
        require_once 'includes' . DIRECTORY_SEPARATOR . 'ajaxurl.php';
    } 
}
add_action('wp_head', 'betpress_add_ajax_library');


//load js & css
function betpress_register_scripts() {
    
    wp_register_script('js_front', plugins_url('/includes/js/front.js', __FILE__), array('jquery', 'wp-ajax-response'), false, true);
    wp_register_script('js_timepicker', plugins_url('/includes/js/timepicker.js', __FILE__), array('jquery'), false, true);
    wp_register_style('css_style', plugins_url('/includes/css/style.css', __FILE__));
}
add_action('init', 'betpress_register_scripts');


//use js & css
function betpress_use_scripts() {
    
    wp_enqueue_style('css_style', plugins_url('/includes/css/style.css', __FILE__));
        wp_enqueue_style('jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');


    wp_enqueue_script('jquery-ui-datepicker');

    wp_enqueue_script('js_timepicker', plugins_url('/includes/js/timepicker.js', __FILE__), array('jquery'), false, true);

    
    if (is_admin()) {
        
        wp_enqueue_style('wp-color-picker');
        
        wp_enqueue_script('js_admin', plugins_url('/includes/js/admin.js', __FILE__), array('jquery', 'js_timepicker', 'wp-color-picker'), false, true);
        
        wp_localize_script(
                'js_admin',
                'i18n_admin',
                array(
                    'sport_delete_confirm_message' => __('You are about to delete the sport and all the sport associated data. Are you sure?', 'BetPress'),
                    'event_delete_confirm_message' => __('You are about to delete the event and all the event associated data. Are you sure?', 'BetPress'),
                    'bet_event_delete_confirm_message' => __('You are about to delete the bet event and all the bet event associated data. Are you sure?', 'BetPress'),
                    'cat_delete_confirm_message' => __('You are about to delete the category and all the category associated data. Are you sure?', 'BetPress'),
                    'bet_option_delete_confirm_message' => __('You are about to delete the bet option and all the bet option associated data. Are you sure?', 'BetPress'),
                )
        );
        
    } else {
        
        wp_enqueue_script('js_front', plugins_url('/includes/js/front.js', __FILE__), array('jquery'), false, true);     
        
        wp_localize_script(
                'js_front',
                'i18n_front',
                array(
                    'show' => __('Show', 'BetPress'),
                    'hide' => __('Hide', 'BetPress'),
                    'toggle_symbol_minus' => __('-', 'BetPress'),
                    'toggle_symbol_plus' => __('+', 'BetPress'),
                    'loading' => __('Loading...', 'BetPress'),
                )
        );
    }
}
add_action('wp_enqueue_scripts', 'betpress_use_scripts');
add_action('admin_enqueue_scripts', 'betpress_use_scripts');


//load translations
function betpress_load_translations() {
    
    load_plugin_textdomain('BetPress', FALSE, dirname(plugin_basename(__FILE__)) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR);
}
add_action('plugins_loaded', 'betpress_load_translations');


function betpress_display_odd($decimal_odd_string) {
    
    $decimal_odd = (float) $decimal_odd_string;
    
    $desired_odd = betpress_get_desired_odd();
    
    switch ($desired_odd) {
        
        case BETPRESS_AMERICAN:

            if (2 > $decimal_odd) {
                
                $plus_minus = '-';
                $result = 100 / ($decimal_odd - 1);
                
            } else {
                
                $plus_minus = '+';
                $result = ($decimal_odd - 1) * 100;
            }
                
            return ($plus_minus . betpress_floordec($result, 2));
            
        case BETPRESS_FRACTION:
            
            if (2 == $decimal_odd) {
                return '1/1';
            }
            
            $dividend = intval(strval((($decimal_odd - 1) * 100)));
            $divisor = 100;
            
            $smaller = ($dividend > $divisor) ? $divisor : $dividend;
            
            //worst case: 100 iterations
            for ($common_denominator = $smaller; $common_denominator > 0; $common_denominator --) {
                
                if ( (0 === ($dividend % $common_denominator)) && (0 === ($divisor % $common_denominator)) ) {
                    
                    $dividend /= $common_denominator;
                    $divisor /= $common_denominator;
                    
                    return ($dividend . '/' . $divisor);
                }
            }
            
            return ($dividend . '/' . $divisor);
            
        //no filtering need for BETPRESS_DECIMAL, thats how we store the odd in db
        default:
            return $decimal_odd;
    }
}
add_filter('betpress_odd', 'betpress_display_odd');


//register the settings the wp way
function betpress_register_settings() {

    register_setting('bp_settings_group', 'bp_starting_points', 'betpress_sanitize_positive_number');
    register_setting('bp_settings_group', 'bp_close_bets', 'betpress_sanitize_positive_number');
    register_setting('bp_settings_group', 'bp_min_stake', 'betpress_sanitize_positive_number');
    register_setting('bp_settings_group', 'bp_max_stake', 'betpress_sanitize_positive_number');
    register_setting('bp_settings_group', 'bp_one_win_per_cat', 'betpress_sanitize_checkbox');
    register_setting('bp_settings_group', 'bp_only_int_stakes', 'betpress_sanitize_checkbox');
    register_setting('bp_settings_group', 'bp_default_odd_type', 'betpress_sanitize_odd_select');
    register_setting('bp_settings_group', 'bp_max_points_to_buy', 'betpress_sanitize_positive_number');
    register_setting('bp_settings_group', 'bp_max_allowed_points', 'betpress_sanitize_positive_number');
    register_setting('bp_settings_group', 'bp_paypal_mail', 'betpress_sanitize_email');
    register_setting('bp_settings_group', 'bp_paypal_url_fail', 'betpress_sanitize_url');
    register_setting('bp_settings_group', 'bp_paypal_token', 'betpress_sanitize');
    register_setting('bp_settings_group', 'bp_paypal_sandbox', 'betpress_sanitize_checkbox');
    register_setting('bp_settings_group', 'bp_paypal_success_message', 'betpress_sanitize_pp_success');
    register_setting('bp_settings_group', 'bp_paypal_error_message', 'betpress_sanitize_pp_error');
    register_setting('bp_settings_group', 'bp_sport_title_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_sport_title_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_sport_container_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_event_title_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_event_title_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_event_container_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_bet_event_title_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_bet_event_title_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_cat_title_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_cat_title_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_cat_container_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_button_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_button_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_featured_heading_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_featured_heading_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_featured_name_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_featured_name_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_featured_button_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_featured_button_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_lb_table_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_lb_heading_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_lb_odd_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_lb_even_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_slip_heading_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_slip_heading_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_slip_row_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_slip_row_text_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_slip_subrow_bg_color', 'betpress_sanitize_color');
    register_setting('bp_settings_group', 'bp_slip_subrow_text_color', 'betpress_sanitize_color');
    
    // as of version 1.0.2
    register_setting('bp_settings_group', 'bp_points_per_approved_comment', 'betpress_sanitize_positive_number_or_zero');
    add_option('bp_points_per_approved_comment', 0);
}
add_action('admin_init', 'betpress_register_settings');


//register admin menu
function betpress_register_admin_menu_page() {
    
    add_menu_page(
            __('BetPress settings', 'BetPress'),    //page title
            __('BetPress', 'BetPress'),             //menu title
            'manage_options',                       //capability
            'betpress-settings',                    //menu slug
            'betpress_settings_controller'          //callback
    );

    add_submenu_page(
            'betpress-settings',                    //parent slug
            __('Bettings', 'BetPress'),             //page title
            __('Bettings', 'BetPress'),             //menu title
            'manage_options',                       //capability
            'betpress-sports-and-events',           //menu slug
            'betpress_bettings_controller'          //callback
    );
    
    add_submenu_page(
            'betpress-settings',
            __('Leaderboards', 'BetPress'),
            __('Leaderboards', 'BetPress'),
            'manage_options',
            'bp-leaderboards',
            'betpress_leaderboards_controller'
    );
    
    add_submenu_page(
            'betpress-settings',
            __('PayPal log', 'BetPress'),
            __('PayPal log', 'BetPress'),
            'manage_options',
            'bp-paypal',
            'betpress_paypal_controller'
    );
    
    add_submenu_page(
            'betpress-settings',
            __('Points log', 'BetPress'),
            __('Points log', 'BetPress'),
            'manage_options',
            'bp-points-log',
            'betpress_points_log_controller'
    );
    
    add_submenu_page(
            'betpress-settings',
            __('Auto insert data', 'BetPress'),
            __('Auto insert data', 'BetPress'),
            'manage_options',
            'bp-auto-insert',
            'betpress_auto_insert_controller'
    );
    
    add_submenu_page(
            'betpress-settings',
            __('Import/Export', 'BetPress'),
            __('Import/Export', 'BetPress'),
            'manage_options',
            'bp-import-export',
            'betpress_import_export_controller'
    );

}
add_action('admin_menu', 'betpress_register_admin_menu_page');


function betpress_modify_admin_users_table($column) {
    
    $column['betpress_points'] = __('BetPress Points', 'BetPress');
    $column['betpress_buyed_points'] = __('BetPress Bought Points', 'BetPress');
    
    return $column;
}
add_filter('manage_users_columns', 'betpress_modify_admin_users_table');


function betpress_modify_admin_users_table_data($val, $column_name, $user_ID) {
    
    switch ($column_name) {
        
        case 'betpress_points' :
            $user_points_db = get_user_meta($user_ID, 'bp_points', true);
            $user_points = ('' === $user_points_db) ? get_option('bp_starting_points') : (float) $user_points_db;
            return $user_points;
            
        case 'betpress_buyed_points' :
            return ( (float) get_user_meta($user_ID, 'bp_points_buyed', true) );
            
        default:
            return;
    }
}
add_filter('manage_users_custom_column', 'betpress_modify_admin_users_table_data', 10, 3);


function betpress_admin_user_custom_profile($user) {
    
    $user_points_db = esc_attr(get_user_meta($user->ID, 'bp_points', true));
    $user_points_buyed_db = esc_attr(get_user_meta($user->ID, 'bp_points_buyed', true));

    $pass['user_points'] = ('' === $user_points_db) ? get_option('bp_starting_points') : (float) $user_points_db;
    $pass['user_buyed_points'] = (float) $user_points_buyed_db;
    $pass['admin_url'] = get_admin_url(null, 'admin.php?page=bp-points-log&user_id=' . $user->ID);
    betpress_get_view('user-edit-extra-fields', 'admin', $pass);
}
add_action('edit_user_profile', 'betpress_admin_user_custom_profile');
add_action('show_user_profile', 'betpress_admin_user_custom_profile');


function betpress_admin_user_custom_profile_save($user_ID) {
	
	if ( ! current_user_can('manage_options') ) {
            return false;
        }
        
        $new_user_points = betpress_sanitize($_POST['bp_points']);
        $new_user_bought_points = betpress_sanitize($_POST['bp_points_buyed']);
        
        if ( ( ! is_numeric($new_user_points) ) || ( ! is_numeric($new_user_bought_points) ) ) {
            wp_die(__('BetPress points and bought points must be numbers.', 'BetPress'));
        }
        
        $old_user_points = get_user_meta($user_ID, 'bp_points', true);
        $old_user_bought_points = get_user_meta($user_ID, 'bp_points_buyed', true);
	
	update_user_meta($user_ID, 'bp_points', $new_user_points);
	update_user_meta($user_ID, 'bp_points_buyed', $new_user_bought_points);
        
        if (strcmp(get_user_meta($user_ID, 'bp_points', true), $new_user_points) === 0) {
            
            if (strcmp($new_user_points, $old_user_points) !== 0) {
            
                betpress_insert(
                    'points_log',
                    array(
                        'user_id' => $user_ID,
                        'comment_id' => 0,
                        'admin_id' => get_current_user_id(),
                        'points_amount' => $new_user_points - $old_user_points,
                        'date' => time(),
                        'type' => BETPRESS_POINTS,
                    )
                );
            
            }
            
        } else {
            
            $db_error = true;
            
        }
        
        if (strcmp(get_user_meta($user_ID, 'bp_points_buyed', true), $new_user_bought_points) === 0) {
            
            if (strcmp($new_user_bought_points, $old_user_bought_points) !== 0) {
            
                betpress_insert(
                    'points_log',
                    array(
                        'user_id' => $user_ID,
                        'comment_id' => 0,
                        'admin_id' => get_current_user_id(),
                        'points_amount' => $new_user_bought_points - $old_user_bought_points,
                        'date' => time(),
                        'type' => BETPRESS_BOUGHT_POINTS,
                    )
                );
            
            }
            
        } else {
            
            $db_error = true;
            
        }
        
        if (isset($db_error) && $db_error === true) {
            
            wp_die(__('Database error.', 'BetPress'));
            
        }
}
add_action('edit_user_profile_update', 'betpress_admin_user_custom_profile_save');
add_action('personal_options_update', 'betpress_admin_user_custom_profile_save');

function betpress_add_dashboard_widgets() {
    
    if (current_user_can('manage_options')) {
        
        wp_add_dashboard_widget('betpress_dashboard', __('BetPress', 'BetPress'), 'betpress_render_admin_dashboard_widget');
    }
}
add_action('wp_dashboard_setup', 'betpress_add_dashboard_widgets' );

function betpress_comment_approved($comment_object) {
    
    $comment = $comment_object->to_array();
    
    betpress_award_user_for_approved_comment($comment['user_id'], $comment['comment_ID']);
}
add_action('comment_unapproved_to_approved', 'betpress_comment_approved');


function betpress_new_comment($comment_ID, $is_approved) {
    
    if (1 === $is_approved) {
        
        $comment = get_comment($comment_ID, ARRAY_A);
        
        betpress_award_user_for_approved_comment($comment['user_id'], $comment_ID);
    }
}
add_action('comment_post', 'betpress_new_comment', 10, 2);


//include folders
betpress_require('controllers');
betpress_require('widgets');
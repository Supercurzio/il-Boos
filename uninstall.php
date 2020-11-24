<?php

//don't allow direct access via url
if ( ! defined('ABSPATH') ) {
    exit();
}

//if uninstall is not called from WordPress, exit
if ( ! defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}

global $wpdb;

//delete plugin settings
delete_option('bp_starting_points');
delete_option('bp_close_bets');
delete_option('bp_min_stake');
delete_option('bp_max_stake');
delete_option('bp_default_odd_type');
delete_option('bp_one_win_per_cat');
delete_option('bp_only_int_stakes');
delete_option('bp_paypal_mail');
delete_option('bp_max_points_to_buy');
delete_option('bp_max_allowed_points');
delete_option('bp_paypal_url_fail');
delete_option('bp_paypal_token');
delete_option('bp_paypal_sandbox');
delete_option('bp_paypal_success_message');
delete_option('bp_paypal_error_message');
delete_option('bp_sport_title_bg_color');
delete_option('bp_sport_title_text_color');
delete_option('bp_sport_container_bg_color');
delete_option('bp_event_title_bg_color');
delete_option('bp_event_title_text_color');
delete_option('bp_event_container_bg_color');
delete_option('bp_bet_event_title_bg_color');
delete_option('bp_bet_event_title_text_color');
delete_option('bp_cat_title_bg_color');
delete_option('bp_cat_title_text_color');
delete_option('bp_cat_container_bg_color');
delete_option('bp_button_bg_color');
delete_option('bp_button_text_color');
delete_option('bp_featured_heading_bg_color');
delete_option('bp_featured_heading_text_color');
delete_option('bp_featured_name_bg_color');
delete_option('bp_featured_name_text_color');
delete_option('bp_featured_button_bg_color');
delete_option('bp_featured_button_text_color');
delete_option('bp_lb_table_text_color');
delete_option('bp_lb_heading_bg_color');
delete_option('bp_lb_odd_bg_color');
delete_option('bp_lb_even_bg_color');
delete_option('bp_slip_heading_bg_color');
delete_option('bp_slip_heading_text_color');
delete_option('bp_slip_row_bg_color');
delete_option('bp_slip_row_text_color');
delete_option('bp_slip_subrow_bg_color');
delete_option('bp_slip_subrow_text_color');
delete_option('bp_points_per_approved_comment');

//delete logical options
delete_option('bp_db_version');
delete_option('bp_paypal_txn_ids');

//delete pages
$inserted_pages = get_option('bp_pages_inserted');
delete_option('bp_pages_inserted');

foreach ($inserted_pages as $page_ID) {
    wp_delete_post($page_ID, true);
}

//delete db tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_bet_options");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_bet_events_cats");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_bet_events");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_events");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_sports");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_slips");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_paypal");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_leaderboards");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bp_points_log");

//delete users settings
$users_settings = array(
    'bp_points',
    'bp_points_buyed',
    'bp_odd_type',
);

$meta_type  = 'user';
$user_id    = 0;        //this will be ignored, since we are deleting for all users.
$meta_value = '';       //also ignored, the meta will be deleted regardless of value.
$delete_all = true;

foreach ($users_settings as $meta_key) {
    delete_metadata($meta_type, $user_id, $meta_key, $meta_value, $delete_all);
}

//custom delete old leaderboard records
$wpdb->query("DELETE FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE 'bp_lb_%'");
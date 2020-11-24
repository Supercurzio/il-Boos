<?php

//don't allow direct access via url
//if ( ! defined('ABSPATH') ) {
  //  exit();
//}

function prettyPrint ($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

function betpress_get_url ($params_to_remove = NULL) {
    
    $pageURL = 'http';
    
    if ( (isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'] == 'on') ) {
        
        $pageURL .= 's';
    }
   
    $pageURL .= '://';
    
    $pageURL .= $_SERVER['SERVER_NAME'];
    
    if ($_SERVER['SERVER_PORT'] != '80') {
        
        $pageURL .= ':' . $_SERVER['SERVER_PORT'];
        
    } 
    
    if (NULL == $params_to_remove) {
        
        $pageURL .= $_SERVER['REQUEST_URI'];
        
    } else {
        
        $url_arr = parse_url(betpress_get_url());
        
        $pageURL .= $url_arr['path'];
        
        $params_arr = isset($url_arr['query']) ? explode('&', $url_arr['query']) : array();
        
        foreach ($params_to_remove as $param_to_remove) {
        
            $params_arr = preg_grep('/^' . $param_to_remove . '*/', $params_arr, PREG_GREP_INVERT);
        }
        
        if (count($params_arr) > 0) {
            
            $pageURL .= '?';
        
            $pageURL .= implode('&', $params_arr);
        }
        
    }
    
    return $pageURL;
}

function betpress_get_last_url_param($url) {
    
    $params_arr = explode('&', $url);
    $params_arr_reversed = array_reverse($params_arr);
    
    return $params_arr_reversed[0];
}

function betpress_sanitize($string) {
    
    return sanitize_text_field($string);
}

function betpress_sanitize_positive_number($input) {
    
    if ( ! is_numeric($input) ) {
        wp_die(__('Expected number, saw ' . $input, 'BetPress'));
    }
    
    if (0 >= $input) {
        wp_die(__('Expected positive number, saw ' . $input, 'BetPress'));
    }
    
    return betpress_sanitize($input);
}

function betpress_sanitize_positive_number_or_zero($input) {
    
    if ( ! is_numeric($input) ) {
        wp_die(__('Expected number, saw ' . $input, 'BetPress'));
    }
    
    if (0 > $input) {
        wp_die(__('Expected positive number or zero, saw ' . $input, 'BetPress'));
    }
    
    return betpress_sanitize($input);
}

function betpress_sanitize_checkbox($input) {
    
    if ( (strcmp($input, '') !== 0) && (strcmp($input, BETPRESS_VALUE_YES) !== 0) ) {
        wp_die(sprintf(__('Expected empty string or %s, saw ' . $input, 'BetPress'), BETPRESS_VALUE_YES));
    }
    
    return betpress_sanitize($input);
}

function betpress_sanitize_email($input) {
    
    if ( ( ! filter_var($input, FILTER_VALIDATE_EMAIL) ) && (strcmp($input, '') !== 0) ) {
        wp_die(__('Expected email address, saw ' . $input, 'BetPress'));
    }
    
    if (strcmp($input, '') !== 0) {
        
        $email_arr = explode('@', $input);
        $email_arr_rev = array_reverse($email_arr);
    
        if ( ! checkdnsrr($email_arr_rev[0], 'MX') ) {
            wp_die(__('Expected registered email address, saw ' . $input, 'BetPress'));
        }
    }
    
    return betpress_sanitize($input);
}

function betpress_sanitize_url($input) {
    
    if ( ( ! filter_var($input, FILTER_VALIDATE_URL) ) && (strcmp($input, '') !== 0) ) {
        wp_die(__('Expected URL, saw ' . $input, 'BetPress'));
    }
    
    return betpress_sanitize($input);
}

function betpress_sanitize_pp_success($input) {
    
    betpress_register_string_for_translation('pp-success', $input);
    
    return betpress_sanitize($input);
}

function betpress_sanitize_pp_error($input) {
    
    betpress_register_string_for_translation('pp-error', $input);
    
    return betpress_sanitize($input);
}

function betpress_sanitize_color($input) {
    
    if (strcmp($input, '') === 0) {
        return '#ffffff';
    }
    
    if ( ! preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', $input) ) {
        wp_die(__('Expected color, saw ' . $input, 'BetPress'));
    }
    
    return betpress_sanitize($input);
}

function betpress_sanitize_odd_select($input) {
    
    $allowed_types = array(
        BETPRESS_DECIMAL,
        BETPRESS_FRACTION,
        BETPRESS_AMERICAN,
    );
    
    if ( ! in_array($input, $allowed_types, true) ) {
        wp_die(__('Expected odd type, saw ' . $input, 'BetPress'));
    }
    
    return betpress_sanitize($input);
}

function betpress_local_tz_time($unix_time, $display_format = DATE_COOKIE) {
    
    return gmdate("D M y  -  G:i " , ($unix_time + (get_option('gmt_offset') * 60 * 60))-(60*60)); //ho tolto ora legale
}

function betpress_local_tz_time_plus($unix_time) {
    
    $gmt_offset = get_option('gmt_offset');
    
    $new = (str_replace(array('.75', '.5'), array('.45', '.3'), $gmt_offset)) * 100;
    
    switch (strlen($new)) {
        
        case 1:
            $newer = '+0000';
            break;
        
        case 3:
            $newer = '+0' . $new;
            break;
        
        case 4:
            if (strpos($new, '-') === 0) {
                $newer = str_replace('-', '-0', $new);
            } else {
                $newer = '+' . $new;
            }
            break;
            
        case 5:
            $newer = $new;
            break;

        default:
            return false;
    }
    
    $result = ' ' . $newer;
    
    return ( gmdate( BETPRESS_TIME_NO_ZONE, ( $unix_time + (get_option('gmt_offset') * 60 * 60) ) ) ) . $result;
}

function betpress_floordec($zahl, $decimals = 2) {
    
     return floor($zahl*pow(10,$decimals)) / pow(10,$decimals);
}

function betpress_require($folder_name, $starting_folder = BETPRESS_DIR_PATH) {

    if (is_dir($starting_folder . $folder_name)) {

        $files = scandir($starting_folder . $folder_name);
    }

    if ((isset($files)) && (!empty($files))) {

        foreach ($files as $file) {

            if (($file === '.') || ($file === '..')) {
                continue;
            }
            
            if (is_dir($starting_folder . $folder_name . DIRECTORY_SEPARATOR . $file)) {
                
                betpress_require($file, $starting_folder . $folder_name . DIRECTORY_SEPARATOR);
                
            } else {

                if (file_exists($starting_folder . DIRECTORY_SEPARATOR . $folder_name . DIRECTORY_SEPARATOR . $file)) {

                    require_once $starting_folder . DIRECTORY_SEPARATOR . $folder_name . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
    }
}

function betpress_get_view($viewname, $folder = '', $passed_variables = NULL) {
 
    $viewname .= '.php';
    
    if (strcmp($folder, '') !== 0) {
        $folder .= DIRECTORY_SEPARATOR;
    }
    
    if (file_exists(BETPRESS_VIEWS_DIR . $folder . $viewname)) {
        
        if ($passed_variables !== NULL) {
        
            extract($passed_variables);
            
        }
        
        require BETPRESS_VIEWS_DIR . $folder . $viewname;
    }
}

function betpress_render_bet_options(array $bet_option_ids, $where = 'widget', $pass_vars = array()) {
    
    $pass = $pass_vars;

    foreach ($bet_option_ids as $bet_option_ID => $bet_option_odd) {
        
        $pass['bet_option_info'] = betpress_get_bet_option($bet_option_ID);

        switch ($where) {

            case 'widget':

                betpress_get_view('widget-bet-options', '', $pass);
                break;

            case 'page':

                $pass['odd_when_submitted'] = $bet_option_odd;
                betpress_get_view('page-bet-options', '', $pass);
                break;

            default:

                betpress_get_view('widget-bet-options', '', $pass);
                break;
        }
    }
}

function betpress_insert($table_name, $data) {

    global $wpdb;

    if (false === $wpdb->insert($wpdb->prefix . 'bp_' . $table_name, $data) ) {
        
        return false;
        
    } 
    
    return $wpdb->insert_id;
}

function bp_activity_insert($table, $data) {

    global $wpdb;
 

    if (false === $wpdb->insert($table, $data) ) {
        
        return false;
        
    } 
    
    return $wpdb->insert_id;
}

function betpress_update($table_name, $data, $where) {
    
    global $wpdb;
    
    return $wpdb->update($wpdb->prefix . 'bp_' . $table_name, $data, $where);
}

function betpress_update_wp($table_name, $data, $where) {
    
    global $wpdb;
    
    return $wpdb->update($wpdb->prefix . $table_name, $data, $where);
}

function betpress_delete($table_name, $where) {
    
    global $wpdb;
    
    return $wpdb->delete($wpdb->prefix . 'bp_' . $table_name, $where);
}

function betpress_get_bet_option($bet_option_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_options as bet_options '
                . 'INNER JOIN ' . $wpdb->prefix . 'bp_bet_events_cats as cats '
                . 'USING (bet_event_cat_id) '
                . 'INNER JOIN ' . $wpdb->prefix . 'bp_bet_events as events '
                . 'USING (bet_event_id) '
                . 'WHERE bet_options.bet_option_id = ' . $bet_option_ID . ' '
                . 'LIMIT 1', ARRAY_A
        );
}

function betpress_is_bet_option_exists($bet_option_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var(
                'SELECT bet_option_id FROM ' . $wpdb->prefix . 'bp_bet_options '
                . 'WHERE bet_option_id = ' . $bet_option_ID . ' '
                . 'LIMIT 1'
        );
}

function betpress_is_bet_option_name_exists($bet_option_name, $category_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var(
            'SELECT bet_option_id FROM ' . $wpdb->prefix . 'bp_bet_options '
            . 'WHERE bet_option_name = "' . $bet_option_name . '" '
                . 'AND bet_event_cat_id = ' . $category_ID . ' '
            . 'LIMIT 1'
        );
}

function betpress_get_category($category_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events_cats WHERE bet_event_cat_id = ' . $category_ID, ARRAY_A);
}

function betpress_get_category_by_bet_event_id($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events_cats WHERE bet_event_id = ' . $bet_event_ID, ARRAY_A);
}

function betpress_get_bet_option_cat($bet_option_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT bet_event_cat_id FROM ' . $wpdb->prefix . 'bp_bet_options WHERE bet_option_id = ' . $bet_option_ID);
}

function betpress_get_sports() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_sports ORDER BY sport_sort_order ASC', ARRAY_A);
}

function betpress_get_events() {
    
    global $wpdb;
    
    return $wpdb->get_results(
            'SELECT * FROM ' . $wpdb->prefix . 'bp_events as ev '
            . 'JOIN ' . $wpdb->prefix . 'bp_sports as sp '
            . 'USING (sport_id) '
            . 'ORDER BY sp.sport_sort_order ASC, ev.event_sort_order ASC',
            ARRAY_A
        );
}

function betpress_is_event_exists($event_name, $event_ID = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT event_name FROM ' . $wpdb->prefix . 'bp_events WHERE event_name = "' . $event_name . '"';
    
    if ($event_ID != NULL) {
        
        $sql .= ' AND event_id != ' . $event_ID;
    }
    
    $sql .= ' LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_events_max_order ($sport_id) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT MAX(event_sort_order) FROM ' . $wpdb->prefix . 'bp_events WHERE sport_id = ' . $sport_id);
}

function betpress_is_sport_exists($sport_name, $sport_ID = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT sport_name FROM ' . $wpdb->prefix . 'bp_sports WHERE sport_name = "' . $sport_name . '"';
    
    if ($sport_ID != NULL) {
        
        $sql .= ' AND sport_id != ' . $sport_ID;
    }
    
    $sql .= ' LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_sports_max_order () {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT MAX(sport_sort_order) FROM ' . $wpdb->prefix . 'bp_sports');
}

function betpress_get_event($event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_events WHERE event_id = ' . $event_ID . ' LIMIT 1', ARRAY_A);
}

function betpress_get_sport($sport_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_sports WHERE sport_id = ' . $sport_ID . ' LIMIT 1', ARRAY_A);
}

function betpress_get_sport_by_name ($sport_name) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_sports WHERE sport_name = "' . $sport_name . '" LIMIT 1', ARRAY_A);
}

function betpress_get_event_by_name ($event_name) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_events WHERE event_name = "' . $event_name . '" LIMIT 1', ARRAY_A);
}



function betpress_get_bet_events($event_ID, $front = false, $close_earlier = 0) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events '
            . 'WHERE is_featured = 0 AND event_id = ' . $event_ID . ' ';
    
    if (true === $front) {
        $sql .= '    AND UNIX_TIMESTAMP() < (deadline - ' . $close_earlier . ') ';
    }
            
    $sql .= 'ORDER BY bet_event_sort_order ASC';
    
    return $wpdb->get_results($sql, ARRAY_A); 
}

function betpress_get_all_bet_events($event_ID, $front = true, $close_earlier = 0) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events '
        . 'WHERE event_id = ' . $event_ID . ' ';
        
        if (true === $front) {
            $sql .= '    AND UNIX_TIMESTAMP() < (deadline - ' . $close_earlier . ') ';
        }
        
        $sql .= 'ORDER BY bet_event_sort_order ASC';
        
        return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_get_all_classic_bet_events($event_ID, $front = true, $close_earlier = 0) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events '
        . 'WHERE event_id = ' . $event_ID . ' AND is_featured != 1 ';
        
        if (true === $front) {
            $sql .= '    AND UNIX_TIMESTAMP() < (deadline - ' . $close_earlier . ') ';
        }
        
        $sql .= 'ORDER BY bet_event_sort_order ASC';
        
        return $wpdb->get_results($sql, ARRAY_A);
}


function betpress_get_id_sportmonks($event_name, $deadline ) {
    $link = mysqli_connect('localhost', 'fi2hnlg1_boosdb', '6y213p!S)D', 'fi2hnlg1_boosdb');
    
    
    
    if (! $link) {
        die('Couldnt connect to db.');
    }
   
    global $wpdb;
    
    $sql = 'SELECT id_sportmonks FROM ' . $wpdb->prefix . 'bp_bet_events '
        . 'WHERE bet_event_name = "' . $event_name . '" AND deadline = ' . $deadline . ' ORDER BY ilboos_bp_bet_events.id_sportmonks DESC LIMIT 1' ;
echo $sql;
        return $q = mysqli_query($link, $sql);
       // return $wpdb->get_results($sql, ARRAY_A);
}


function betpress_get_bet_events_max_order($event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT MAX(bet_event_sort_order) FROM ' . $wpdb->prefix . 'bp_bet_events WHERE event_id = ' . $event_ID);
}

function betpress_get_prom_events_max_order($event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT MAX(prom_event_sort_order) FROM ' . $wpdb->prefix . 'bp_prom_events WHERE event_id = ' . $event_ID);
}

function betpress_is_bet_event_name_exists($bet_event_name, $event_ID, $bet_event_ID = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT bet_event_name FROM ' . $wpdb->prefix . 'bp_bet_events '
            . 'WHERE bet_event_name = "' . $bet_event_name . '" AND event_id = ' . $event_ID;
    
    if ($bet_event_ID != NULL) {
        
        $sql .= ' AND bet_event_id != ' . $bet_event_ID;
    }
    
    $sql .= ' LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_bet_event ($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events WHERE bet_event_id = ' . $bet_event_ID . ' LIMIT 1', ARRAY_A);
}



function betpress_get_bet_event_by_name ($bet_event_name, $event_id) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events '
            . 'WHERE bet_event_name = "' . $bet_event_name . '" '
            . 'AND event_id = ' . $event_id . ' '
            . 'LIMIT 1', ARRAY_A);
}

function betpress_get_cat_by_name ($cat_name, $bet_event_id) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
            . 'WHERE bet_event_cat_name = "' . $cat_name . '" '
            . 'AND bet_event_id = ' . $bet_event_id . ' '
            . 'LIMIT 1', ARRAY_A);
}

function betpress_get_categories ($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results(
            'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
            . 'WHERE bet_event_cat_name = "3Way Result" AND bet_event_id = ' . $bet_event_ID . ' '
            . 'ORDER BY bet_event_cat_sort_order ASC',
            ARRAY_A
        );
}

function betpress_get_all_categories ($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results(
        'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
        . 'WHERE bet_event_cat_balance = 0 AND bet_event_id = ' . $bet_event_ID . ' '
        . 'ORDER BY bet_event_cat_sort_order ASC',
        ARRAY_A
        );
}

function betpress_get_bet_options ($cat_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results(
            'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_options '
            . 'WHERE bet_event_cat_id = ' . $cat_ID . ' '
            . 'ORDER BY bet_option_sort_order ASC',
            ARRAY_A
        );
}

function betpress_get_options ($category_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_options as options '
            . 'JOIN ' . $wpdb->prefix . 'bp_bet_events_cats as cats '
            . 'USING (bet_event_cat_id) '
            . 'WHERE bet_event_cat_id = ' . $category_ID . ' '
            . 'ORDER BY bet_option_sort_order ASC', ARRAY_A);
}

function betpress_get_bet_options_for_admin() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT be.bet_event_name, be.bet_event_id, be.event_id '
            . 'FROM ' . $wpdb->prefix . 'bp_bet_events as be '
            . 'JOIN ' . $wpdb->prefix . 'bp_bet_events_cats as cats USING (bet_event_id) '
            . 'JOIN ' . $wpdb->prefix . 'bp_bet_options as opt USING (bet_event_cat_id) '
            . 'WHERE be.deadline < UNIX_TIMESTAMP() '
                . 'AND opt.status = "' . BETPRESS_STATUS_AWAITING . '" '
            . 'GROUP BY be.bet_event_name', ARRAY_A);
}

function betpress_get_bet_option_by_all_details($sport_name, $event_name, $bet_event_name, $category_name, $bet_option_name) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_options AS bo '
                . 'INNER JOIN ' . $wpdb->prefix . 'bp_bet_events_cats AS c '
                . 'USING (bet_event_cat_id) '
                . 'INNER JOIN ' . $wpdb->prefix . 'bp_bet_events AS be '
                . 'USING (bet_event_id) '
                . 'INNER JOIN ' . $wpdb->prefix . 'bp_events AS e '
                . 'USING (event_id) '
                . 'INNER JOIN ' . $wpdb->prefix . 'bp_sports AS s '
                . 'USING (sport_id) '
                . 'WHERE bo.bet_option_name = "' . $bet_option_name . '" '
                    . 'AND c.bet_event_cat_name = "' . $category_name . '" '
                    . 'AND be.bet_event_name = "' . $bet_event_name . '" '
                    . 'AND e.event_name = "' . $event_name . '" '
                    . 'AND s.sport_name = "' . $sport_name . '" '
            , ARRAY_A
        );
}


function betpress_get_bet_creation_user_id_by_option($bet_option_id) {//SUPERCURZIO
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_options AS bo '
        . 'INNER JOIN ' . $wpdb->prefix . 'bp_bet_events_cats AS c '
        . 'USING (bet_event_cat_id) '
        . 'INNER JOIN ' . $wpdb->prefix . 'bp_bet_events AS be '
        . 'USING (bet_event_id) '
        . 'WHERE bo.bet_option_id = "' . $bet_option_id . '" '

        , ARRAY_A
        );
}


function betpress_is_category_exists($category_ID, $bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT bet_event_cat_id FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
            . 'WHERE bet_event_cat_id = ' . $category_ID . ' AND bet_event_id = ' . $bet_event_ID, ARRAY_A);
}

function betpress_is_category_name_exists($category_name, $bet_event_ID, $category_ID = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT bet_event_cat_id FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
            . 'WHERE bet_event_cat_name = "' . $category_name . '" '
            . 'AND bet_event_id = ' . $bet_event_ID . ' ';
    
    if ($category_ID != NULL) {
        
        $sql .= 'AND bet_event_cat_id != ' . $category_ID . ' ';
    }
    
    $sql .= 'LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_category_by_name($category_name, $bet_event_ID, $category_ID = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
            . 'WHERE bet_event_cat_name = "' . $category_name . '" '
            . 'AND bet_event_id = ' . $bet_event_ID . ' ';
    
    if ($category_ID != NULL) {
        
        $sql .= 'AND bet_event_cat_id != ' . $category_ID . ' ';
    }
    
    $sql .= 'LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_bet_options_max_order($category_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT MAX(bet_option_sort_order) FROM ' . $wpdb->prefix . 'bp_bet_options WHERE bet_event_cat_id = ' . $category_ID);
}

function betpress_get_cats_max_order($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT MAX(bet_event_cat_sort_order) FROM ' . $wpdb->prefix . 'bp_bet_events_cats WHERE bet_event_id = ' . $bet_event_ID);
}

function betpress_is_bet_event_exists($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT bet_event_id FROM ' . $wpdb->prefix . 'bp_bet_events WHERE bet_event_id = ' . $bet_event_ID . ' LIMIT 1', ARRAY_A);
}


function betpress_get_lower_order($current_order, $table_name, $condition_key = NULL, $condition_value = NULL, $singular = NULL) {
    
    global $wpdb;
    
    $table_name_singular = $singular === NULL ? betpress_get_singular_table_name($table_name) : $singular;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_' . $table_name . ' WHERE ' . $table_name_singular . '_sort_order = ' . --$current_order;
    
    if ( ($condition_key != NULL) && ($condition_value != NULL) ) {
        
        $sql .= ' AND ' . $condition_key . ' = ' . $condition_value;
        
    }
    
    $sql .= ' LIMIT 1';
    
    $sql = $wpdb->get_row($sql, ARRAY_A);
    
    return $sql ? $sql : betpress_get_lower_order($current_order, $table_name, $condition_key, $condition_value, $singular);
}

function betpress_get_higher_order($current_order, $table_name, $condition_key = NULL, $condition_value = NULL, $singular = NULL) {
    
    global $wpdb;
    
    $table_name_singular = $singular === NULL ? betpress_get_singular_table_name($table_name) : $singular;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_' . $table_name . ' WHERE ' . $table_name_singular . '_sort_order = ' . ++$current_order;
        
    if( ($condition_key != NULL) && ($condition_value != NULL) ) {
        
        $sql .= ' AND ' . $condition_key . ' = ' . $condition_value;
    }
    
    $sql .= ' LIMIT 1';
    
    $sql = $wpdb->get_row($sql, ARRAY_A);
    
    return $sql ? $sql : betpress_get_higher_order($current_order, $table_name, $condition_key, $condition_value, $singular);
}

function betpress_get_events_by_sport($sport_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_events '
            . 'WHERE sport_id = ' . $sport_ID . ' '
            . 'ORDER BY event_sort_order ASC', ARRAY_A);
}

function betpress_get_min_max_order ($table_name, $min_or_max = 'MIN', $condition_key = NULL, $condition_value = NULL, $singular = NULL) {
    
    global $wpdb;
    
    $table_name_singular = $singular === NULL ? betpress_get_singular_table_name($table_name) : $singular;
    
    $sql = 'SELECT ' . $min_or_max . '(' . $table_name_singular . '_sort_order) FROM ' . $wpdb->prefix . 'bp_' . $table_name;
    
    if ( ($condition_key !== NULL) && ($condition_value != NULL) ) {
        
        $sql .= ' WHERE ' . $condition_key . ' = ' . $condition_value; 
    }
    
    return $wpdb->get_var($sql);
}

function betpress_is_lb_exists($lb_name, $lb_ID = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT leaderboard_name FROM ' . $wpdb->prefix . 'bp_leaderboards WHERE leaderboard_name = "' . $lb_name . '"';
    
    if ($lb_ID != NULL) {
        
        $sql .= ' AND leaderboard_id != ' . $lb_ID;
    }
    
    $sql .= ' LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_singular_table_name($table_name) {
    
    return substr($table_name, 0, -1);
}

function betpress_is_status_exists($status, $where = 'slip') {
    
    if ( (strcmp($status, BETPRESS_STATUS_AWAITING) === 0) || 
            (strcmp($status, BETPRESS_STATUS_WINNING) === 0) || 
            (strcmp($status, BETPRESS_STATUS_LOSING) === 0) || 
            (strcmp($status, BETPRESS_STATUS_CANCELED) === 0) ) {
        
        return true;
    }
    
    if (strcmp($where, 'slip') === 0) {
        
        if ( (strcmp($status, BETPRESS_STATUS_UNSUBMITTED) === 0) || (strcmp($status, BETPRESS_STATUS_TIMED_OUT) === 0) ) {
            
            return true;
        }
    }
    
    return false;
}

function betpress_get_cat_bet_options ($category_ID, $bet_option_id = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_bet_options WHERE bet_event_cat_id = ' . $category_ID;
    
    if ($bet_option_id !== NULL) {
        
        $sql .= ' AND bet_option_id != ' . $bet_option_id;
    }
    
    return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_change_bet_option_status ($new_status, $category_ID, $bet_option_id = NULL) {
    
    global $wpdb;
    
    $sql = 'UPDATE ' . $wpdb->prefix . 'bp_bet_options '
            . 'SET status = %s '
            . 'WHERE bet_event_cat_id = %d';
      
    if ($bet_option_id !== NULL) {
        
        $sql .= ' AND bet_option_id != %d';
        $prepare = $wpdb->prepare($sql, $new_status, $category_ID, $bet_option_id);
        
    } else {
        
        $prepare = $wpdb->prepare($sql, $new_status, $category_ID);
        
    }
    
    return $wpdb->query($prepare);
}

function betpress_get_users_with_points() {
    
    global $wpdb;
    
    $sql = 'SELECT user_id, meta_value FROM ' . $wpdb->prefix . 'usermeta WHERE meta_key = "bp_points"';
    
    return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_get_current_leaderboard() {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_leaderboards WHERE leaderboard_status = "' . BETPRESS_STATUS_ACTIVE . '" LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_leaderboards() {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_leaderboards ORDER BY leaderboard_id DESC';
    
    return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_get_past_leaderboards() {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_leaderboards WHERE leaderboard_status = "' . BETPRESS_STATUS_PAST . '" ORDER BY leaderboard_id DESC';
    
    return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_get_leaderboard($leaderboard_ID) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_leaderboards WHERE leaderboard_id = ' . $leaderboard_ID . ' LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_leaderboard_by_name($leaderboard_name) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_leaderboards WHERE leaderboard_name = "' . $leaderboard_name .'" LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_leaderboard_details($leaderboard_ID, $start = 0, $limit = PHP_INT_MAX) {
    
    global $wpdb;
    
    $sql = 'SELECT position, points, nickname '
            . 'FROM ('
                . 'SELECT @position := @position + 1 as position, points, nickname '
                . 'FROM ('
                    . 'SELECT CAST(um1.meta_value as DECIMAL(10,2)) as points, um2.meta_value as nickname '
                    . 'FROM ' . $wpdb->prefix . 'usermeta as um1 '
                    . 'JOIN ' . $wpdb->prefix . 'usermeta as um2 '
                    . 'USING (user_id) '
                    . 'WHERE um1.meta_key = "bp_lb_' . $leaderboard_ID . '" '
                    . 'AND um2.meta_key = "nickname" '
                    . 'ORDER BY points DESC '
                . ') as t1, (SELECT @position := 0) as t2 '
            . ') as t3 '
            . 'LIMIT ' . $start . ', ' . $limit;
    
    return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_get_active_leaderboard_details($start = 0, $limit = PHP_INT_MAX) {
    
    global $wpdb;
    
    $sql = 'SELECT position, points, nickname '
            . 'FROM ('
                . 'SELECT @position := @position + 1 as position, points, nickname '
                . 'FROM ('
                    . 'SELECT CAST(um1.meta_value as DECIMAL(10,2)) as points, um2.meta_value as nickname '
                    . 'FROM ' . $wpdb->prefix . 'usermeta as um1 '
                    . 'JOIN ' . $wpdb->prefix . 'usermeta as um2 '
                    . 'USING (user_id) '
                    . 'WHERE um1.meta_key = "bp_points" '
                    . 'AND um2.meta_key = "nickname" '
                    . 'ORDER BY points DESC '
                . ') as t1, (SELECT @position := 0) as t2 '
            . ') as t3 '
            . 'LIMIT ' . $start . ', ' . $limit;
    
    return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_is_active_leaderboard($leaderboard_ID) {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_leaderboards '
            . 'WHERE leaderboard_id = ' . $leaderboard_ID . ' '
            . 'AND leaderboard_status = "' . BETPRESS_STATUS_ACTIVE . '" '
            . 'LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_get_active_leaderboard() {
    
    global $wpdb;
    
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'bp_leaderboards '
            . 'WHERE leaderboard_status = "' . BETPRESS_STATUS_ACTIVE . '" '
            . 'LIMIT 1';
    
    return $wpdb->get_row($sql, ARRAY_A);
}

function betpress_is_bet_option_in_bet_event($bet_option_ID, $bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT o.bet_option_id FROM ' . $wpdb->prefix . 'bp_bet_options as o '
            . 'JOIN ' . $wpdb->prefix . 'bp_bet_events_cats as c '
            . 'USING (bet_event_cat_id) '
            . 'WHERE o.bet_option_id = ' . $bet_option_ID . ' '
            . '    AND c.bet_event_id = ' . $bet_event_ID . ' '
            . 'LIMIT 1', ARRAY_A);
}

function betpress_get_sibling_categories($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events_cats WHERE bet_event_id = ' . $bet_event_ID, ARRAY_A);
}

function betpress_get_sibling_categories_except_current($bet_event_ID, $bet_option_id) {// Supercurzio trova tutte le opzioni a parte la vincente
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_options as o '
            . 'JOIN ' . $wpdb->prefix . 'bp_bet_events_cats as c '
            . 'USING (bet_event_cat_id) '
            . 'WHERE o.bet_option_id <> ' . $bet_option_id . ' '
            . '    AND c.bet_event_cat_id = ' . $bet_event_ID, ARRAY_A);
}

function betpress_get_featured_bet_events ($sort_column = 'deadline', $sort_method = 'ASC', $close_earlier = 0) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events '
            . 'WHERE is_featured = "1" AND is_active = "1" AND is_prom= "0"'
            . '    AND UNIX_TIMESTAMP() < (deadline - ' . $close_earlier . ') '
            . 'ORDER BY ' . $sort_column . ' ' . $sort_method, ARRAY_A);
}

function betpress_get_featured_bet_events_user ($sort_column = 'deadline', $sort_method = 'ASC', $close_earlier = 0) {
    
    global $wpdb;
    $u_id=get_current_user_id();
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events '
            . 'WHERE is_featured = "1" AND is_active = "1" AND is_prom= "0" AND added_by_user_id = ' . $u_id .''
            . '    AND UNIX_TIMESTAMP() < (deadline - ' . $close_earlier . ') '
            . 'ORDER BY ' . $sort_column . ' ' . $sort_method, ARRAY_A);
}

function betpress_get_featured_bet_events_bet_event ($sort_column = 'deadline', $sort_method = 'ASC', $close_earlier = 0, $id_sportmonks=0) {
    
    global $wpdb;
   
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events '
        . 'WHERE is_featured = "1" AND is_active = "1" AND is_prom= "0" AND id_sportmonks = ' . $id_sportmonks .''
        . '    AND UNIX_TIMESTAMP() < (deadline - ' . $close_earlier . ') '
        . 'ORDER BY ' . $sort_column . ' ' . $sort_method, ARRAY_A);
}


function betpress_get_sibling_event_by_url() {
    
    global $wpdb;
    $bet_id=$_GET['bet_id'];
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_bet_events WHERE bet_event_id = ' . $bet_id, ARRAY_A);
}

function betpress_get_avatar($bet_event_ID) {
    
    global $wpdb;
    
   return $wpdb->get_row('SELECT * FROM '
       . $wpdb->prefix . 'bp_bet_events WHERE bet_event_id =' . $bet_event_ID , ARRAY_A);
	
}


function betpress_get_featured_bet_options ($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results(
            'SELECT opt.bet_option_name, opt.bet_option_odd, opt.bet_option_id '
            . 'FROM ' . $wpdb->prefix . 'bp_bet_events_cats as cats '
            . 'JOIN ' . $wpdb->prefix . 'bp_bet_options as opt USING (bet_event_cat_id) '
            . 'JOIN ( '
            . '    SELECT MIN(bet_event_cat_sort_order) as min_sort_order '
            . '    FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
            . '    WHERE bet_event_cat_balance > "0" AND prom_event_id = "0" AND bet_event_id = ' . $bet_event_ID . ' '
            . ') as cats2 ON (cats2.min_sort_order = cats.bet_event_cat_sort_order) '
            . 'WHERE opt.is_active="1" AND opt.bet_option_odd > "1" AND bet_event_id = ' . $bet_event_ID,//Tolto opzioni non attive o con quota 1
            ARRAY_A
    );
}

function betpress_get_social_bet_options ($bet_event_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results(
        'SELECT opt.bet_option_name, opt.bet_option_odd, opt.bet_option_id '
        . 'FROM ' . $wpdb->prefix . 'bp_bet_events_cats as cats '
        . 'JOIN ' . $wpdb->prefix . 'bp_bet_options as opt USING (bet_event_cat_id) '
        . 'JOIN ( '
        . '    SELECT MIN(bet_event_cat_sort_order) as min_sort_order '
        . '    FROM ' . $wpdb->prefix . 'bp_bet_events_cats '
        . '    WHERE bet_event_cat_balance > "0" AND prom_event_id = "0" AND bet_event_id = ' . $bet_event_ID . ' '
        . ') as cats2 ON (cats2.min_sort_order = cats.bet_event_cat_sort_order) '
        . 'WHERE opt.is_active="1" AND bet_event_id = ' . $bet_event_ID,//Tolto opzioni non attive
        ARRAY_A
        );
}


function betpress_check_slips($slip_type = BETPRESS_STATUS_AWAITING) {
    
    switch ($slip_type) {
        
        case BETPRESS_STATUS_AWAITING:

            $slips = betpress_get_awaiting_slips();
            break;
        
        case BETPRESS_VALUE_ALL:

            $slips = betpress_get_all_slips();
            break;

        default:
            return false;
    }
    
    $db_errors = false;
    
    $active_leaderboard = betpress_get_active_leaderboard();
    
    foreach ($slips as $slip) {
               
        $slip_status = $slip['status'];
        
        //skip slips that are not submitted yet, or slips that are timed out
        if ( (strcmp($slip_status, BETPRESS_STATUS_UNSUBMITTED) === 0) || (strcmp($slip_status, BETPRESS_STATUS_TIMED_OUT) === 0) ) {
            continue;
        }
        
        $slip_lb = $slip['leaderboard_id'];
        
        //skip slips that are not part of this leaderboard
        if ($slip_lb != $active_leaderboard['leaderboard_id']) {
            continue;
        }
        
        $slip_ID = $slip['slip_id'];
        $bet_options_ids = unserialize($slip['bet_options_ids']);
        $stake = $slip['stake'];
        $winnings = $slip['winnings'];
        $user_ID = $slip['user_id'];
        $current_points = get_user_meta($user_ID, 'bp_points', true);
        
        $canceled = false;
        $awaiting = false;
        $win = false;
        $lose = false;
            
        $count_wins = 0;
        
        foreach ($bet_options_ids as $bet_option_ID => $bet_option_odd) {
            
            $bet_option = betpress_get_bet_option($bet_option_ID);
            
            if (strcmp($bet_option['status'], BETPRESS_STATUS_CANCELED) === 0) {
                               
                $canceled = true;
                break;
            }
            
            if (strcmp($bet_option['status'], BETPRESS_STATUS_AWAITING) === 0) {
                               
                $awaiting = true;
            }
            
            if (strcmp($bet_option['status'], BETPRESS_STATUS_WINNING) === 0) {
                               
                $win = true;
                $count_wins ++;
            }
            
            if (strcmp($bet_option['status'], BETPRESS_STATUS_LOSING) === 0) {
                               
                $lose = true;
            }
        }
        
        //set default values SUPERCURZIO aggiungere pagamento bookmaker
        $new_status = BETPRESS_STATUS_AWAITING;
        $updated_points = $current_points;

        if (true === $canceled) {

            switch ($slip_status) {

                case BETPRESS_STATUS_LOSING:
                case BETPRESS_STATUS_AWAITING:

                    $updated_points = $current_points + $stake;
                    
                    break;

                case BETPRESS_STATUS_WINNING:

                    $updated_points = $current_points - $winnings + $stake;

                    break;
                
                case BETPRESS_STATUS_CANCELED:
                    break;

                default:
                    return false;
            }

            $new_status = BETPRESS_STATUS_CANCELED;
            
        } else if (true === $awaiting) {
            
            if (strcmp($slip_type, BETPRESS_VALUE_ALL) === 0) {

                switch ($slip_status) {

                    case BETPRESS_STATUS_CANCELED:

                        $updated_points = $current_points - $stake;

                        break;

                    case BETPRESS_STATUS_WINNING:

                        $updated_points = $current_points - $winnings;

                        break;

                    case BETPRESS_STATUS_LOSING:

                        $updated_points = $current_points;

                        break;
                    
                    case BETPRESS_STATUS_AWAITING:
                        break;

                    default:
                        return false;
                }

                $new_status = BETPRESS_STATUS_AWAITING;
                
            }
            
        } else if (true === $lose) {
            
            switch ($slip_status) {

                case BETPRESS_STATUS_CANCELED:

                    $updated_points = $current_points - $stake;

                    break;

                case BETPRESS_STATUS_WINNING:

                    $updated_points = $current_points - $winnings;

                    break;

                case BETPRESS_STATUS_AWAITING:

                    $updated_points = $current_points;

                    break;
                
                case BETPRESS_STATUS_LOSING:
                    break;

                default:
                    return false;
            }

            $new_status = BETPRESS_STATUS_LOSING;
            
        } else if ( (true === $win) && (count($bet_options_ids) == $count_wins) ) {

            switch ($slip_status) {

                case BETPRESS_STATUS_CANCELED:

                    $updated_points = $current_points - $stake + $winnings;

                    break;

                case BETPRESS_STATUS_LOSING:
                case BETPRESS_STATUS_AWAITING:

                    $updated_points = $current_points + $winnings;

                    break;
                
                case BETPRESS_STATUS_WINNING:
                    break;

                default:
                    return false;
            }

            $new_status = BETPRESS_STATUS_WINNING;
            
        }
        
        //update only if there is a change
        if (strcmp($slip_status, $new_status) !== 0) {
            //inserire qui funzione cash back bookmaker SUPERCURZIO v
            //inizio ciclo if per pagare solo una volta il bookmaker
            //7/10/19 manca funzione cashback bookmaker se la sua scommessa il suo evento non ha avuto scommesse, da aggiungere credo in controllers/admin/leaderboards
            echo"is paid: ";echo$bet_option['is_paid'];
            if ($bet_option['is_paid'] == "0"){
        
            $opt_ammo = $bet_option['opt_ammo'];
            $opt_winnings = $bet_option['opt_winnings'];
            $cashback = $opt_ammo - $opt_winnings;
            $bet_option_id = $bet_option['bet_option_id'];
            $bet_option_cat_id = $bet_option['bet_event_cat_id'];
            $bet_creator_id = betpress_get_bet_creation_user_id_by_option( $bet_option_id);
            
            foreach ($bet_creator_id as $bet_creator_ID => $bet_option_id) {
                $user_creator_id = $bet_option_id['added_by_user_id'];
            }
            
            $current_creator_points = get_user_meta($user_creator_id, 'bp_points', true);
            $updated_creator_points = $current_creator_points + $cashback;
            
            $str_updated_creator_points = (string)$updated_creator_points;
            update_user_meta($user_creator_id, 'bp_points', $str_updated_creator_points);
            
            echo"str_updated_points";echo $str_updated_creator_points;
            
            //check if the update took effect per bookmaker 
            if (strcmp(get_user_meta($user_creator_id, 'bp_points', true), $str_updated_creator_points) !== 0) {
                $db_errors = true;
            }
            $is_paid = betpress_pay_bookmaker($bet_option_cat_id);
            // può dare problemi
            if (false === $is_paid) {
                $db_errors = true;
            }
        
            //qui devo finire ciclo if
            }else{echo"già pagata";echo $bet_option['bet_event_cat_id'];}
            
            
            $str_updated_points = (string)$updated_points;
            //update users points
            update_user_meta($user_ID, 'bp_points', $str_updated_points);
           
            //check if the update took effect
            if (strcmp(get_user_meta($user_ID, 'bp_points', true), $str_updated_points) !== 0) {
                $db_errors = true;
            }
           
            //change slip status
            if (false === betpress_change_slip_status($new_status, $slip_ID)) {
                $db_errors = true;
            }
        }
    }

    if (false === $db_errors) {
        
        return true;
        
    }
    
    return false;
}

function betpress_get_awaiting_slips() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_slips WHERE status = "' . BETPRESS_STATUS_AWAITING . '"', ARRAY_A);
}

function betpress_get_all_slips() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_slips', ARRAY_A);
}

function betpress_change_slip_status($new_status, $slip_ID) {
    
    return betpress_update(
            'slips',
            array(
                'status' => $new_status,
            ),
            array(
                'slip_id' => $slip_ID,
            )
        );
}

function betpress_pay_bookmaker($bet_event_cat_id) {
    
    return betpress_update(
        'bet_events_cats',
        array(
            'is_paid' => 1,
        ),
        array(
            'bet_event_cat_id' => $bet_event_cat_id,
        )
        );
}

function betpress_get_user_submitted_slips($user_ID) {

     

     global $wpdb;

     

     return $wpdb->get_results('SELECT * '

             . 'FROM ' . $wpdb->prefix . 'bp_slips '

             . 'WHERE user_id = ' . $user_ID . ' '

                . 'AND status != "' . BETPRESS_STATUS_UNSUBMITTED . '" '

            . 'ORDER BY date DESC', ARRAY_A);

}



function betpress_get_user_awaiting_slips($user_ID) {

    global $wpdb;

    return $wpdb->get_results('SELECT * '

            . 'FROM ' . $wpdb->prefix . 'bp_slips '

            . 'WHERE user_id = ' . $user_ID . ' '

                 . 'AND status = "' . BETPRESS_STATUS_AWAITING . '" '

             . 'ORDER BY date DESC', ARRAY_A);

 }

function betpress_get_user_winning_slips($user_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * '
            . 'FROM ' . $wpdb->prefix . 'bp_slips '
            . 'WHERE user_id = ' . $user_ID . ' '
                . 'AND status = "' . BETPRESS_STATUS_WINNING . '" '
            . 'ORDER BY date DESC', ARRAY_A);
}

function betpress_get_user_losing_slips($user_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * '
            . 'FROM ' . $wpdb->prefix . 'bp_slips '
            . 'WHERE user_id = ' . $user_ID . ' '
                . 'AND status = "' . BETPRESS_STATUS_LOSING . '" '
            . 'ORDER BY date DESC', ARRAY_A);
}

function betpress_get_user_canceled_slips($user_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * '
            . 'FROM ' . $wpdb->prefix . 'bp_slips '
            . 'WHERE user_id = ' . $user_ID . ' '
                . 'AND status = "' . BETPRESS_STATUS_CANCELED . '" '
            . 'ORDER BY date DESC', ARRAY_A);
}

function betpress_get_user_timed_out_slips($user_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * '
            . 'FROM ' . $wpdb->prefix . 'bp_slips '
            . 'WHERE user_id = ' . $user_ID . ' '
                . 'AND status = "' . BETPRESS_STATUS_TIMED_OUT . '" '
            . 'ORDER BY date DESC', ARRAY_A);
}

function betpress_get_user_unsubmitted_slip($user_ID) {
    
    global $wpdb;
    
    return $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bp_slips WHERE user_id = ' . $user_ID . ' AND status = "' . BETPRESS_STATUS_UNSUBMITTED . '" LIMIT 1', ARRAY_A);
}

function betpress_get_unsubmitted_slips() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bp_slips WHERE status = "' . BETPRESS_STATUS_UNSUBMITTED . '"', ARRAY_A);
}

function betpress_get_paypal_logs() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT u.display_name, pp.transaction_message, pp.transaction_time, pp.transaction_status, pp.user_ip, pp.points '
            . 'FROM ' . $wpdb->prefix . 'bp_paypal as pp '
            . 'JOIN ' . $wpdb->prefix . 'users as u '
            . 'ON (u.ID = pp.user_id) '
            . 'ORDER BY pp.transaction_time DESC', ARRAY_A);
}

function betpress_get_paypal_logs_by_user($user_ID) {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT u.display_name, pp.transaction_message, pp.transaction_time, pp.transaction_status, pp.user_ip, pp.points '
            . 'FROM ' . $wpdb->prefix . 'bp_paypal as pp '
            . 'JOIN ' . $wpdb->prefix . 'users as u '
            . 'ON (u.ID = pp.user_id) '
            . 'WHERE u.ID = ' . $user_ID . ' '
            . 'ORDER BY pp.transaction_time DESC', ARRAY_A);
    
}

function betpress_get_users_with_paypal_logs() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT u.display_name, pp.user_id '
            . 'FROM ' . $wpdb->prefix . 'bp_paypal as pp '
            . 'JOIN ' . $wpdb->prefix . 'users as u '
            . 'ON (u.ID = pp.user_id) '
            . 'GROUP BY pp.user_id', ARRAY_A);
}

function betpress_get_user_display_name($user_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT display_name FROM ' . $wpdb->prefix . 'users WHERE ID = ' . $user_ID . ' LIMIT 1');
}

function betpress_is_user_comment_awarded($user_ID, $comment_ID) {
    
    global $wpdb;
    
    return $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'bp_points_log '
            . 'WHERE user_id = ' . $user_ID . ' '
                . 'AND comment_id = ' . $comment_ID . ' '
                . 'AND type = "' . BETPRESS_POINTS . '"');
}

function betpress_get_points_logs($user_ID = NULL) {
    
    global $wpdb;
    
    $sql = 'SELECT u.display_name, pl.* '
            . 'FROM ' . $wpdb->prefix . 'bp_points_log AS pl '
            . 'JOIN ' . $wpdb->prefix . 'users AS u '
            . 'ON (u.ID = pl.user_id) ';
    
    if (NULL !== $user_ID) {
        $sql .= 'WHERE pl.user_id = ' . $user_ID . ' ';
    }
    
    $sql .= 'ORDER BY pl.date DESC';
    
    return $wpdb->get_results($sql, ARRAY_A);
}

function betpress_get_users_with_points_logs() {
    
    global $wpdb;
    
    return $wpdb->get_results('SELECT u.display_name, pl.user_id '
            . 'FROM ' . $wpdb->prefix . 'bp_points_log AS pl '
            . 'JOIN ' . $wpdb->prefix . 'users AS u '
            . 'ON (u.ID = pl.user_id) '
            . 'GROUP BY pl.user_id', ARRAY_A);
}

function betpress_insert_xml_data(
        $limit_sports = 0,
        $limit_events = 0,
        $limit_bet_events = 0,
        $limit_bet_events_cats = 0,
        $only_one_sport = null
    ) {
    
    $xml = simplexml_load_file(BETPRESS_XML_URL);
    
    if (false === $xml) {
        return false;
    }
    
    if (0 != $limit_sports) {
        $count_sports = 0;
    }

    foreach ($xml as $sport) {
        
        if (0 != $limit_sports) {
            if ($count_sports ++ >= $limit_sports) {
                break;
            }
        }
        
        $sport_name = (string)$sport->attributes()->name;
        
        if (null !== $only_one_sport) {
            if (strcmp($only_one_sport, $sport_name) !== 0) {
                continue;
            }
        }
        
        if ( ! betpress_is_sport_exists($sport_name) ) {
            
            $sport_max_sort_order = betpress_get_sports_max_order();
            
            $sport_id = betpress_insert(
                    'sports',
                    array(
                        'sport_name' => $sport_name,
                        'sport_sort_order' => ++ $sport_max_sort_order,
                    )
            );
            
            betpress_register_string_for_translation('sport-' . $sport_name, $sport_name);
            
        } else {
            break;
        }
        
        if (0 != $limit_events) {
            $count_events = 0;
        }

        foreach ($sport as $event) {
            
            if (0 != $limit_events) {
                if ($count_events ++ >= $limit_events) {
                    break;
                }
            }

            $event_name = (string)$event->attributes()->name;
        
            if ( ! betpress_is_event_exists($event_name) ) {
                
                $event_max_sort_order = betpress_get_events_max_order($sport_id);
                
                $event_id = betpress_insert(
                        'events',
                        array(
                            'sport_id' => $sport_id,
                            'event_name' => $event_name,
                            'event_sort_order' => ++ $event_max_sort_order,
                        )
                    );
                
                betpress_register_string_for_translation('event-' . $event_name, $event_name);
                
            } else {
                break;
            }
            
            if (0 != $limit_bet_events) {
                $count_bet_events = 0;
            }
            
            foreach ($event as $bet_event) {
                if (0 != $limit_bet_events) {
                    if ($count_bet_events ++ >= $limit_bet_events) {
                        break;
                    }
                }
                
                $bet_event_name = (string)$bet_event->attributes()->name;
                
                if ( ! betpress_is_bet_event_name_exists($bet_event_name, $event_id) ) {
                
                    $bet_event_starts = $bet_event->attributes()->starts;
                    
                    $bet_event_max_sort_order = betpress_get_bet_events_max_order($event_id);
                    
                    $bet_event_id = betpress_insert(
                            'bet_events',
                            array(
                                'bet_event_name' => $bet_event_name,
                                'event_id' => $event_id,
                                'deadline' => $bet_event_starts,
                                'bet_event_sort_order' => ++ $bet_event_max_sort_order,
                                'is_active' => 0,
                            )
                        );
                    
                    betpress_register_string_for_translation('bet-event-' . $bet_event_name, $bet_event_name);
                    
                } else {
                    break;
                }
                
                if (0 != $limit_bet_events_cats) {
                    $count_categories = 0;
                }
                
                foreach ($bet_event as $category) {
                    
                    if (0 != $limit_bet_events_cats) {
                        if ($count_categories ++ >= $limit_bet_events_cats) {
                            break;
                        }
                    }

                    $cat_name = (string)$category->attributes()->name;

                    if ( ! betpress_is_category_name_exists($cat_name, $bet_event_id) ) {

                        $max_bet_event_cat_sort_order = betpress_get_min_max_order('bet_events_cats', 'MAX', 'bet_event_id', $bet_event_id, 'bet_event_cat');

                        $cat_id = betpress_insert(
                                'bet_events_cats',
                                array(
                                    'bet_event_cat_name' => $cat_name,
                                    'bet_event_id' => $bet_event_id,
                                    'bet_event_cat_sort_order' => ++$max_bet_event_cat_sort_order,
                                )
                        );
                        
                        betpress_register_string_for_translation('cat-' . $cat_name, $cat_name);
                        
                    } else {
                        break;
                    }

                    foreach ($category as $bet_option) {

                        $bet_option_name = (string)$bet_option->attributes()->name;
                        $bet_option_odd = (float)$bet_option->attributes()->odd;
                        
                        $max_bet_option_sort_order = betpress_get_bet_options_max_order($cat_id);
                        
                        betpress_insert(
                            'bet_options',
                            array(
                                'bet_option_name' => $bet_option_name,
                                'bet_option_odd' => $bet_option_odd,
                                'bet_event_cat_id' => $cat_id,
                                'bet_option_sort_order' => ++ $max_bet_option_sort_order,
                                'status' => BETPRESS_STATUS_AWAITING,
                            )
                        );
                        
                        betpress_register_string_for_translation('bet-option-' . $bet_option_name, $bet_option_name);
                    }
                }
            }
        }
    }
}

function betpress_get_xml_data() {
    
    $xml = simplexml_load_file(BETPRESS_XML_URL);
    
    if (false === $xml) {
        return false;
    }
    
    $xml_data = array();
    
    foreach ($xml as $sport) {
         
        $sport_name = (string)$sport->attributes()->name;
        $xml_data [$sport_name] = array();
        $xml_data [$sport_name] ['id'] = (int)$sport->attributes()->id;

        foreach ($sport as $event) {
            
            $event_name = (string)$event->attributes()->name;
            $xml_data [$sport_name] [$event_name] = array();
            $xml_data [$sport_name] [$event_name] ['id'] = (int)$event->attributes()->id;
            
            foreach ($event as $bet_event) {
                
                $bet_event_name = (string)$bet_event->attributes()->name;
                $xml_data [$sport_name] [$event_name] [$bet_event_name] = array();
                $xml_data [ $sport_name] [$event_name] [$bet_event_name] ['id'] = (int)$bet_event->attributes()->id;
                
                foreach ($bet_event as $category) {
                    
                    $category_name = (string)$category->attributes()->name;
                    $xml_data [$sport_name] [$event_name] [$bet_event_name] [$category_name] = array();
                    $xml_data [$sport_name] [$event_name] [$bet_event_name] [$category_name] ['id'] = (int)$category->attributes()->id;
                    
                    foreach ($category as $bet_option) {
                        $xml_data [$sport_name] [$event_name] [$bet_event_name] [$category_name] [] = (string)$bet_option->attributes()->name;
                    }
                }
            }
        }
       
    }
    
    return $xml_data;
}

function betpress_get_structured_xml_sports() {//obsoleta

    $xml = simplexml_load_file(BETPRESS_XML_URL);

    if (false === $xml) {
        return false;
    }

    $xml_sports = array();

    foreach ($xml as $sport) {
	$name=(string)$sport->attributes()->name;
	 if ( $name == "Football" OR $name == "Tennis" OR $name == "Formula 1" OR $name == "Motorcycling"){
        $xml_sports [(int)$sport->attributes()->id] = (string)$sport->attributes()->name;
	 }
    }

    return $xml_sports;
}

function betpress_get_structured_sports() {//attuale
    
    //$xml = simplexml_load_file(BETPRESS_XML_URL);
    
//    if (false === $xml) {
  //      return false;
 //   }
    
    $xml_sports = array();
    $xml_sports ['1'] = "Football";
    //$xml_sports ['sport_name'] = "Football";
   
    
    return $xml_sports;
}

function betpress_get_structured_xml_events($sport_ID) {//obsoleta

    $xml = simplexml_load_file(BETPRESS_XML_URL);

    if (false === $xml) {
        return false;
    }

    $xml_data = array();

    foreach ($xml as $sport) {

        $xml_sport_ID = (int)$sport->attributes()->id;

        if ($xml_sport_ID != $sport_ID) {

            continue;

        }

        foreach ($sport as $event) {

            $event_ID = (int)$event->attributes()->id;
            $xml_data [$event_ID] = (string)$event->attributes()->name;

        }

    }

    return $xml_data;
}
function betpress_get_structured_events($sport_ID) {//attuale
    
    //$name="Football";
    //$sport_ID = betpress_get_sport_by_name($name);
  $xml=array();
    $xml = betpress_get_events_by_sport($sport_ID);
    
    if (false === $xml) {
        return false;
    }
    
    $xml_data = array();
    

        
        foreach ($xml as $event) {
            
            $event_ID = $event['event_id'];
            //echo $event['event_id'];
            $xml_data [$event_ID] =  $event['event_name'];
            
        }
        
    
    
    return $xml_data;
}
function betpress_get_structured_xml_bet_events($sport_ID, $event_ID) {

    $xml = simplexml_load_file(BETPRESS_XML_URL);

    if (false === $xml) {
        return false;
    }

    $xml_data = array();

    foreach ($xml as $sport) {

        $xml_sport_ID = (int)$sport->attributes()->id;

        if ($xml_sport_ID != $sport_ID) {

            continue;

        }

        foreach ($sport as $event) {

            $xml_event_ID = (int)$event->attributes()->id;

            if ($xml_event_ID != $event_ID) {

                continue;

            }

            foreach ($event as $bet_event) {

                $bet_event_ID = (int)$bet_event->attributes()->id;
                $xml_data [$bet_event_ID] ['name'] = (string)$bet_event->attributes()->name;
                $xml_data [$bet_event_ID] ['deadline'] = (string)$bet_event->attributes()->starts;

            }

        }

    }

    return $xml_data;
}

function betpress_get_structured_xml_categories($sport_ID, $event_ID, $bet_event_ID) {

    $xml = simplexml_load_file(BETPRESS_XML_URL);

    if (false === $xml) {
        return false;
    }

    $xml_data = array();

    foreach ($xml as $sport) {

        $xml_sport_ID = (int)$sport->attributes()->id;

        if ($xml_sport_ID != $sport_ID) {

            continue;

        }

        foreach ($sport as $event) {

            $xml_event_ID = (int)$event->attributes()->id;

            if ($xml_event_ID != $event_ID) {

                continue;

            }

            foreach ($event as $bet_event) {

                $xml_bet_event_ID = (int)$bet_event->attributes()->id;

                if ($xml_bet_event_ID != $bet_event_ID) {

                    continue;

                }

                foreach ($bet_event as $category) {

                    $category_ID = (int)$category->attributes()->id;

                    $xml_data [$category_ID] = (string)$category->attributes()->name;

                }

            }

        }

    }

    return $xml_data;
}

function betpress_get_structured_xml_bet_options($sport_ID, $event_ID, $bet_event_ID, $category_ID) {

    $xml = simplexml_load_file(BETPRESS_XML_URL);

    if (false === $xml) {
        return false;
    }

    $xml_data = array();

    foreach ($xml as $sport) {

        $xml_sport_ID = (int)$sport->attributes()->id;

        if ($xml_sport_ID != $sport_ID) {

            continue;

        }

        foreach ($sport as $event) {

            $xml_event_ID = (int)$event->attributes()->id;

            if ($xml_event_ID != $event_ID) {

                continue;

            }

            foreach ($event as $bet_event) {

                $xml_bet_event_ID = (int)$bet_event->attributes()->id;

                if ($xml_bet_event_ID != $bet_event_ID) {

                    continue;

                }

                foreach ($bet_event as $category) {

                    $xml_category_ID = (int)$category->attributes()->id;

                    if ($xml_category_ID != $category_ID) {

                        continue;

                    }

                    foreach ($category as $bet_option) {

                        $id = (int)$bet_option->attributes()->id;

                        $xml_data [$id] ['name'] = (string)$bet_option->attributes()->name;

                        $xml_data [$id] ['odd'] = (string)$bet_option->attributes()->odd;

                    }

                }

            }

        }

    }

    return $xml_data;
}

function betpress_insert_specific_xml_data($data, $is_active = 0) {
    
    $sports = array();
    $events = array();
    $bet_events = array();
    $cats = array();
    
    foreach ($data as $sport_id => $sport_data) {
        
        $sports [] = $sport_id;
        
        foreach ($sport_data as $event_id => $event_data) {
            
            $events [] = $event_id;
            
            foreach ($event_data as $bet_event_id => $bet_event_data) {
                
                $bet_events [] = $bet_event_id;
                
                foreach ($bet_event_data as $cat_id => $cat_data) {
                    
                    $cats [] = $cat_id;
                }
            }
        }
    }
    
    $xml = simplexml_load_file(BETPRESS_XML_URL);
    
    if (false === $xml) {
        return false;
    }
    
    foreach ($xml as $sport) {

        $sport_id = $sport->attributes()->id;

        if (in_array($sport_id, $sports)) {

            $sport_name = (string) $sport->attributes()->name;

            if ( ! betpress_is_sport_exists($sport_name) ) {

                $sport_max_sort_order = betpress_get_sports_max_order();

                $sport_id = betpress_insert(
                        'sports', 
                        array(
                            'sport_name' => $sport_name,
                            'sport_sort_order' => ++ $sport_max_sort_order,
                        )
                );
                
                betpress_register_string_for_translation('sport-' . $sport_name, $sport_name);

                if (false === $sport_id) {
                    return false;
                }
                
            } else {

                $sport_row = betpress_get_sport_by_name($sport_name);
                $sport_id = $sport_row['sport_id'];
            }

            foreach ($sport as $event) {

                $event_id = $event->attributes()->id;

                if (in_array($event_id, $events)) {

                    $event_name = (string) $event->attributes()->name;

                    if ( ! betpress_is_event_exists($event_name) ) {

                        $event_max_sort_order = betpress_get_events_max_order($sport_id);

                        $event_id = betpress_insert(
                                'events',
                                array(
                                    'sport_id' => $sport_id,
                                    'event_name' => $event_name,
                                    'event_sort_order' => ++ $event_max_sort_order,
                                )
                        );
                
                        betpress_register_string_for_translation('event-' . $event_name, $event_name);

                        if (false === $event_id) {
                            return false;
                        }
                        
                    } else {

                        $event_row = betpress_get_event_by_name($event_name);
                        $event_id = $event_row['event_id'];
                    }

                    foreach ($event as $bet_event) {

                        $bet_event_id = $bet_event->attributes()->id;

                        if (in_array($bet_event_id, $bet_events)) {

                            $bet_event_name = (string) $bet_event->attributes()->name;

                            if ( ! betpress_is_bet_event_name_exists($bet_event_name, $event_id) ) {//se non esiste, inserisci

                                $bet_event_max_sort_order = betpress_get_bet_events_max_order($event_id);

                                $bet_event_starts = (string) $bet_event->attributes()->starts;

                                $bet_event_id = betpress_insert(
                                        'bet_events',
                                        array(
                                            'bet_event_name' => $bet_event_name,
                                            'event_id' => $event_id,
                                            'deadline' => $bet_event_starts,
                                            'bet_event_sort_order' => ++ $bet_event_max_sort_order,
                                            'is_active' => $is_active,
                                        )
                                );
                                
                                betpress_register_string_for_translation('bet-event-' . $bet_event_name, $bet_event_name);
                                
                                if (false === $bet_event_id) {
                                    return false;
                                }
                                
                            } else {//SUPERCURZIO altrimenti se esite ma è social, inserisci
                                $bet_event_row = betpress_get_bet_event_by_name($bet_event_name, $event_id);
                                $bet_event_id = $bet_event_row['bet_event_id'];
                                echo$bet_event_id;//DEBUG
                                $soc_bet_event = betpress_get_bet_event( $bet_event_id);
                                
                                foreach ($soc_bet_event as $social_bet_event) {
                                    
                                    $ammo = $soc_bet_event['ammo'] ;   
                                }
                             
                                echo$ammo;//DEBUG 
                                if($ammo>0){
                                    $bet_event_max_sort_order = betpress_get_bet_events_max_order($event_id);
                                    
                                    $bet_event_starts = (string) $bet_event->attributes()->starts;
                                    
                                    $bet_event_id = betpress_insert(
                                        'bet_events',
                                        array(
                                            'bet_event_name' => $bet_event_name,
                                            'event_id' => $event_id,
                                            'deadline' => $bet_event_starts,
                                            'bet_event_sort_order' => ++ $bet_event_max_sort_order,
                                            'is_active' => $is_active,
                                        )
                                        );
                                    betpress_register_string_for_translation('bet-event-' . $bet_event_name, $bet_event_name);
                                    
                                    if (false === $bet_event_id) {
                                        return false;
                                    }
                                    
                                }else {//altrimenti lascia stare
                                echo"ciaone";//DEBUG
                                $bet_event_row = betpress_get_bet_event_by_name($bet_event_name, $event_id);
                                $bet_event_id = $bet_event_row['bet_event_id'];
                                }
                            }

                            foreach ($bet_event as $cat) {

                                $cat_id = $cat->attributes()->id;

                                if (in_array($cat_id, $cats)) {

                                    $cat_name = (string) $cat->attributes()->name;

                                    if ( ! betpress_is_category_name_exists($cat_name, $bet_event_id) ) {

                                        $cat_max_sort_order = betpress_get_cats_max_order($bet_event_id);

                                        $cat_id = betpress_insert(
                                                'bet_events_cats',
                                                array(
                                                    'bet_event_cat_name' => $cat_name,
                                                    'bet_event_id' => $bet_event_id,
                                                    'bet_event_cat_sort_order' => ++$cat_max_sort_order,
                                                )
                                        );
                                
                                        betpress_register_string_for_translation('cat-' . $cat_name, $cat_name);

                                        if (false === $cat_id) {
                                            return false;
                                        }

                                        foreach ($cat as $bet_option) {

                                            $bet_option_name = (string) $bet_option->attributes()->name;
                                            $bet_option_odd = (float) $bet_option->attributes()->odd;

                                            $max_bet_option_sort_order = betpress_get_bet_options_max_order($cat_id);

                                            $bet_option_id = betpress_insert(
                                                'bet_options',
                                                array(
                                                    'bet_option_name' => $bet_option_name,
                                                    'bet_option_odd' => $bet_option_odd,
                                                    'bet_event_cat_id' => $cat_id,
                                                    'bet_option_sort_order' => ++ $max_bet_option_sort_order,
                                                    'status' => BETPRESS_STATUS_AWAITING,
                                                )
                                            );
                                
                                            betpress_register_string_for_translation('bet-option-' . $bet_option_name, $bet_option_name);

                                            if (false === $bet_option_id) {
                                                return false;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return true;
}

function betpress_get_sport_data($sport, $sec_before_deadline) {
    
    $count_active_events = 0;
    
    $sport_data = $sport;
    $sport_data ['events'] = array();

    $events = betpress_get_events_by_sport($sport['sport_id']);
    foreach ($events as $event) {
        
        $count_active_bet_events = 0;

        $sport_data ['events'] [$event['event_id']] = $event;
        $sport_data ['events'] [$event['event_id']] ['bet_events'] = array();
        
        $bet_events = betpress_get_bet_events($event['event_id'], true, $sec_before_deadline);
        foreach ($bet_events as $bet_event) {
            
            $sport_data ['events'] [$event['event_id']] ['bet_events'] [$bet_event['bet_event_id']] = $bet_event;
            $sport_data ['events'] [$event['event_id']] ['bet_events'] [$bet_event['bet_event_id']] ['categories'] = array();
            
            $categories = betpress_get_categories($bet_event['bet_event_id']);
            foreach ($categories as $cat) {
                
                $sport_data
                        ['events']
                        [$event['event_id']]
                        ['bet_events']
                        [$bet_event['bet_event_id']]
                        ['categories']
                        [$cat['bet_event_cat_id']] = $cat;
                $sport_data 
                        ['events']
                        [$event['event_id']]
                        ['bet_events']
                        [$bet_event['bet_event_id']]
                        ['categories']
                        [$cat['bet_event_cat_id']]
                        ['bet_options'] = array();
                
                $bet_options = betpress_get_bet_options($cat['bet_event_cat_id']);

                switch (count($bet_options)) {

                    case 2:
                        $sport_data 
                            ['events']
                            [$event['event_id']]
                            ['bet_events']
                            [$bet_event['bet_event_id']]
                            ['categories']
                            [$cat['bet_event_cat_id']]
                            ['css-width'] = 45;
                        
                        $sport_data 
                            ['events']
                            [$event['event_id']]
                            ['bet_events']
                            [$bet_event['bet_event_id']]
                            ['categories']
                            [$cat['bet_event_cat_id']]
                            ['css-margin_left'] = 3.33;
                        break;

                    case 4:
                        $sport_data 
                            ['events']
                            [$event['event_id']]
                            ['bet_events']
                            [$bet_event['bet_event_id']]
                            ['categories']
                            [$cat['bet_event_cat_id']]
                            ['css-width'] = 22.5;
                        
                        $sport_data 
                            ['events']
                            [$event['event_id']]
                            ['bet_events']
                            [$bet_event['bet_event_id']]
                            ['categories']
                            [$cat['bet_event_cat_id']]
                            ['css-margin_left'] = 2;
                        break;

                    default:
                        $sport_data 
                            ['events']
                            [$event['event_id']]
                            ['bet_events']
                            [$bet_event['bet_event_id']]
                            ['categories']
                            [$cat['bet_event_cat_id']]
                            ['css-width'] = 30;
                        
                        $sport_data 
                            ['events']
                            [$event['event_id']]
                            ['bet_events']
                            [$bet_event['bet_event_id']]
                            ['categories']
                            [$cat['bet_event_cat_id']]
                            ['css-margin_left'] = 2.5;
                        break;
                }
                
                foreach ($bet_options as $bet_option) {

                    $sport_data 
                        ['events']
                        [$event['event_id']]
                        ['bet_events']
                        [$bet_event['bet_event_id']]
                        ['categories']
                        [$cat['bet_event_cat_id']]
                        ['bet_options']
                        [$bet_option['bet_option_id']] = $bet_option;
                    
                }
            }
            
            if ( ($bet_event['is_active']) && (count($sport_data ['events'] [$event['event_id']] ['bet_events'] [$bet_event['bet_event_id']] ['categories']) > 0) ) {
                $count_active_bet_events ++;
            }
        }
        
        $sport_data ['events'] [$event['event_id']] ['count_active_bet_events'] = $count_active_bet_events;
        
        if ($count_active_bet_events > 0) {
            $count_active_events ++;
        }
    }
    
    $sport_data ['count_active_events'] = $count_active_events;
    
    return $sport_data;
}

function betpress_get_desired_odd() {
    
    if (is_user_logged_in()) {

        $user_ID = get_current_user_id();
        
        $user_odd_type_db = get_user_meta($user_ID, 'bp_odd_type', true);
        
        //check if the user have prefered type saved in db
        if (strcmp($user_odd_type_db, '') !== 0) {
            
            $desired_odd = $user_odd_type_db;
            
        } else {
            
            //check if the user have prefered type saved in cookie (before he sign up or login), if not - show the default
            $desired_odd = isset($_COOKIE['betpress_odd_type']) ? betpress_sanitize($_COOKIE['betpress_odd_type']) : get_option('bp_default_odd_type');
            
        }
        
    } else {
    
        $desired_odd = isset($_COOKIE['betpress_odd_type']) ? betpress_sanitize($_COOKIE['betpress_odd_type']) : get_option('bp_default_odd_type');
        
    }
    
    return $desired_odd;
}

function betpress_register_string_for_translation($key, $value) {
    
    do_action('wpml_register_single_string', 'BetPress', $key, $value);
}

function betpress_delete_bet_options_from_unsubmitted_slips($deleted_bet_options_ids) {
    
    if ( ! empty($deleted_bet_options_ids) ) {

        $unsubmitted_slips = betpress_get_unsubmitted_slips();
        foreach ($unsubmitted_slips as $unsubmitted_slip) {

            $slip_bet_option_ids = unserialize($unsubmitted_slip['bet_options_ids']);
            foreach ($slip_bet_option_ids as $bet_option_ID => $bet_option_odd) {

                if (in_array($bet_option_ID, $deleted_bet_options_ids)) {

                    unset($slip_bet_option_ids[$bet_option_ID]);
                }
            }

            betpress_update(
                'slips',
                array('bet_options_ids' => serialize($slip_bet_option_ids)),
                array('slip_id' => $unsubmitted_slip['slip_id'])
            );
        }
    }
}

function betpress_award_user_for_approved_comment($user_ID, $comment_ID) {
    
    if ( ! betpress_is_user_comment_awarded($user_ID, $comment_ID) ) {
        
        $user_db_points = get_user_meta($user_ID, 'bp_points', true);
        
        $user_points = ('' === $user_db_points) ? get_option('bp_starting_points') : $user_db_points;
        
        $points_to_award = get_option('bp_points_per_approved_comment');
        
        $updated_points = $user_points + $points_to_award;
        
        update_user_meta($user_ID, 'bp_points', (string)$updated_points);
            
        if (strcmp(get_user_meta($user_ID, 'bp_points', true), (string)$updated_points) === 0) {
        
            betpress_insert(
                    'points_log',
                    array(
                        'user_id' => $user_ID,
                        'comment_id' => $comment_ID,
                        'admin_id' => 0,
                        'points_amount' => $points_to_award,
                        'date' => time(),
                        'type' => BETPRESS_POINTS,
                    )
                );
            
        } else {
            wp_die('Something went wrong and the user was NOT awarded with BetPress points. '
                    . 'Please unapprove and approve the comment and contact the BetPress support if you see that error again.', 'BetPress');
        }
    }
}

function betpress_inspect_name_for_image_and_video($name) {

    if (strstr($name, '[youtube]') !== false) {
        return true;
    }

    if (strstr($name, '[image]') !== false) {
        return true;
    }

    return false;
}

function betpress_transform_name_to_image_or_video($name, $url, $width = 80, $height = 80) {

    if (strstr($name, '[image]') !== false) {

        return str_replace('[image]', '<img src="' . $url . '" alt="Image" width="' . $width . '" height="' . $height . '" />', $name);

    }

    if (strstr($name, '[youtube]') !== false) {

        return str_replace(
            array('[youtube]', 'https://www.youtube.com/watch?v=', '[/youtube]'),
            array('<iframe width="560" height="315" src="', 'https://www.youtube.com/embed/', '" frameborder="0" allowfullscreen></iframe>'),
            $name
        );

    }
	
	return $name;
}
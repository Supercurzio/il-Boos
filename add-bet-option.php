<?php

//don't allow direct access via url
if ( ! defined('ABSPATH') ) {
    exit();
}

function betpress_add_bet_option() {

    //codex says i MUST do this if
    if (is_admin() === true) {

        $db_errors = false;

        $bet_option_ID = (int) betpress_sanitize($_POST['bet_option_id']);

        $bet_option_errors = array();

        $user_ID = get_current_user_id();
        
        if (0 === $user_ID) {
            
            $bet_option_errors [] = __('Please login or register.', 'BetPress');
            
        }

        if ( ! betpress_is_bet_option_exists($bet_option_ID) ) {

            $bet_option_errors [] = __('No such bet option.', 'BetPress');
        }

        $bet_option_details = betpress_get_bet_option($bet_option_ID);

        $seconds_to_close_bets_earlier = (int) get_option('bp_close_bets');

        $bet_event_close_time = $bet_option_details['deadline'] - $seconds_to_close_bets_earlier;

        if ($bet_event_close_time <= time()) {

            $bet_option_errors [] = __('This event deadline has passed.', 'BetPress');
        }

        if ( ! empty($bet_option_errors) ) {

            foreach ($bet_option_errors as $err) {

                $pass['error_message'] = $err;
                betpress_get_view('error-message', '', $pass);
            }
            
        } else {

            $unsubmitted_slip = betpress_get_user_unsubmitted_slip($user_ID);

            //if there is no unsubmitted slip for this user, we have to create one for him
            if ( ! $unsubmitted_slip ) {

                //save his first betting option in array
                $bet_option_ids = array($bet_option_ID => $bet_option_details['bet_option_odd']);

                //package it for db
                $serialized_bet_options = serialize($bet_option_ids);

                //send it to db
                $insert = betpress_insert(
                        'slips',
                        array(
                            'user_id' => $user_ID,
                            'bet_options_ids' => $serialized_bet_options,
                            'status' => BETPRESS_STATUS_UNSUBMITTED,
                            'date' => time(),
                        )
                );

                if (false === $insert) {
                    $db_errors = true;
                }
                
            } else {

                //get current slip id
                $slip_ID = $unsubmitted_slip['slip_id'];

                //get current bet options from db
                $serialized_bet_options = $unsubmitted_slip['bet_options_ids'];

                //make them an array
                $bet_option_ids = unserialize($serialized_bet_options);

                $errors = array();

                if (array_key_exists($bet_option_ID, $bet_option_ids)) {

                    $errors [] = __('You can\'t add the same option twice.', 'BetPress');
                    
                } else if (get_option('bp_one_win_per_cat') === BETPRESS_VALUE_YES) {
                    //check for bet option of the same category
                    
                    $used_categories = array();
                    foreach ($bet_option_ids as $bet_option_id => $bet_option_odd) {
                        
                        $used_categories [] = betpress_get_bet_option_cat($bet_option_id);
                    }
                    
                    if (in_array($bet_option_details['bet_event_cat_id'], $used_categories)) {
                        
                        $errors [] = __('You can\'t add two options of same category.', 'BetPress');
                    }
                }

                if (empty($errors)) {

                    //add to them the current bet option
                    $bet_option_ids [$bet_option_ID] = $bet_option_details['bet_option_odd'];

                    //package to send to db
                    $serialized_bet_options_updated = serialize($bet_option_ids);

                    //send to db
                    $update = betpress_update(
                            'slips',
                            array(
                                'bet_options_ids' => $serialized_bet_options_updated,
                            ),
                            array(
                                'slip_id' => $slip_ID,
                            )
                    );

                    if (false === $update) {
                        $db_errors = true;
                    }
                } else {

                    foreach ($errors as $error) {

                        $pass['error_message'] = $error;
                        betpress_get_view('error-message', '', $pass);
                    }
                }
            }
        }

        if (false === $db_errors) {
            
            if ( ! isset($bet_option_ids) ) {
                
                $user_ID = get_current_user_id();
                
                $unsubmitted_slip = betpress_get_user_unsubmitted_slip($user_ID);
                
                if (empty($unsubmitted_slip)) {
                    
                    $bet_option_ids = array();
                    
                } else {
                    
                    $bet_option_ids = unserialize($unsubmitted_slip['bet_options_ids']);
                    
                }
                
            }

            //show current bet options
            betpress_render_bet_options($bet_option_ids);
            
        } else {

            _e('DB error.', 'BetPress');
            wp_die();
        }
    }

    //codex says i MUST use wp_die
    wp_die();
}

add_action('wp_ajax_add_bet_option', 'betpress_add_bet_option');
add_action('wp_ajax_nopriv_add_bet_option', 'betpress_add_bet_option');

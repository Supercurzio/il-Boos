<?php



$link = mysqli_connect('localhost', 'fi2hnlg1_boosdb', '6y213p!S)D', 'fi2hnlg1_boosdb');



if (! $link) {
    die('Couldnt connect to db.');
}

mysqli_set_charset($link, 'utf8');

define('ABSPATH', '../../');
define('BETPRESS_STATUS_AWAITING', 'awaiting');
define('BETPRESS_STATUS_WINNING', 'winning');
define('BETPRESS_STATUS_LOSING', 'losing');
define('BETPRESS_STATUS_CANCELED', 'canceled');
define('BETPRESS_STATUS_TIMED_OUT', 'timed_out');
define('BETPRESS_STATUS_ACTIVE', 'active');
define('BETPRESS_STATUS_PAST', 'past');
define('BETPRESS_STATUS_FAIL', 'fail');
define('BETPRESS_STATUS_PAID', 'paid');
define('BETPRESS_POINTS', 'points');
define('BETPRESS_BOUGHT_POINTS', 'bought_points');
define('BETPRESS_VALUE_ALL', 'all');
define('BETPRESS_STATUS_UNSUBMITTED', 'unsubmitted');

function betpress_get_active_leaderboard() {
    global $link;
    //if($id == Null){$id=0;}//fede
   $q = mysqli_query($link, 'SELECT * FROM ilboos_bp_leaderboards WHERE leaderboard_status = "' . BETPRESS_STATUS_ACTIVE . '" LIMIT 1');
    return $q->fetch_array();
   // return $link->get_results('SELECT * FROM ilboos_bp_leaderboards WHERE leaderboard_status = "' . BETPRESS_STATUS_ACTIVE . '" LIMIT 1', ARRAY_A);
}

function betpress_get_awaiting_slips() {
    
    global $link;
   return $q = mysqli_query($link, 'SELECT * FROM ilboos_bp_slips WHERE status = "awaiting" ');
   // return $q->fetch_array();
    //return $link->get_results('SELECT * FROM ilboos_bp_slips WHERE status = "' . BETPRESS_STATUS_AWAITING . '"', ARRAY_A);
    }
    


function betpress_get_all_slips() {
    
    global $link;
    return $q = mysqli_query($link,'SELECT * FROM ilboos_bp_slips WHERE 1');
    //return $q->fetch_array(MYSQLI_BOTH);
}

function betpress_change_slip_status($new_status, $slip_ID) {
    
    global $link;
    $updated = mysqli_query($link, "UPDATE ilboos_bp_slips SET status = '". $new_status . "' WHERE slip_id = " . $slip_ID );
    
    if (0 === $updated) {
        return false;
    }else {
        return true;;
    } 
  
}


function get_user_meta($user_ID, $bp_points = 'bp_points', $true=true){ 
    global $link;
    $q = mysqli_query($link,'SELECT * FROM ilboos_usermeta WHERE user_id = ' . $user_ID. ' AND meta_key = "bp_points"');
    $points = $q->fetch_array();
    $user_bp_points = $points['meta_value'];
    echo "<br>user_points:";echo $user_bp_points;
    return     $user_bp_points;
}

function update_user_meta($user_ID, $bp_points, $true=true){
    global $link;
    $updated = mysqli_query($link, "UPDATE ilboos_usermeta SET meta_value = '". $bp_points . "' WHERE user_id = " . $user_ID );
    
    if (0 === $updated) {
      return false;
    }else {
        return true;;
    } 
   
}

function betpress_get_bet_option($bet_option_ID) {
    
    global $link;
  $q = mysqli_query($link,'SELECT * FROM ilboos_bp_bet_options as bet_options INNER JOIN ilboos_bp_bet_events_cats as cats USING (bet_event_cat_id) INNER JOIN ilboos_bp_bet_events as events USING (bet_event_id) WHERE bet_options.bet_option_id = '. $bet_option_ID . ' LIMIT 1'  );
    return $q->fetch_array();
}

function betpress_get_bet_creation_user_id_by_option($bet_option_id) {//SUPERCURZIO
    
    global $link;
    
    $q = mysqli_query($link,'SELECT * FROM ilboos_bp_bet_options AS bo '
        . 'INNER JOIN ilboos_bp_bet_events_cats AS c '
        . 'USING (bet_event_cat_id) '
        . 'INNER JOIN ilboos_bp_bet_events AS be '
        . 'USING (bet_event_id) '
        . 'WHERE bo.bet_option_id = "' . $bet_option_id . '" ');
}

function betpress_pay_bookmaker($bet_event_cat_id) {//da sist3mare
    
    global $link;
    $updated = mysqli_query($link, "UPDATE ilboos_bp_bet_events_cats SET is_paid = 1 WHERE bet_event_cat_id = " . $bet_event_cat_id );
    
    if (0 === $updated) {
        return false;
    }else {
        return true;
    } 
    
  
}


function check_slips($slip_type = BETPRESS_STATUS_AWAITING) {
    echo"esisto!";
  
    switch ($slip_type) {
        
        case BETPRESS_STATUS_AWAITING:
            echo"1fede";
            $slips = betpress_get_awaiting_slips();
            foreach ($slips as $slip) {
                echo $slip['slip_id'];
                echo "<br>";
            } 
            echo"sono nel get awaiting";         
            break;
            
        case BETPRESS_VALUE_ALL:
            echo"2fede";
            $slips = betpress_get_all_slips();
            echo"sono nel get all";
            break;
            
        default:
            return false;
    }
    
    $db_errors = false;
    
    $active_leaderboard = betpress_get_active_leaderboard();
    echo"active leaderboard:";echo $active_leaderboard['leaderboard_id'];
    $i=1;
    echo"ci sono";
    //echo $slips['slip_id'];
    //echo $slips['status'];
    //$slip=array();
    
   // while ($slips) {
  //      printf ("%s (%s)\n", $slips['slip_id'], $slips['status']);
 //   }
   // echo $slips['slip_id'];
    
    foreach ($slips as $slip) {
  //      foreach ($slips as $slip) {
        echo $slip['slip_id'];
        echo $slip['status'];
       
        $slip_status = $slip['status'];
        
        //skip slips that are not submitted yet, or slips that are timed out
        if ( (strcmp($slip_status, BETPRESS_STATUS_UNSUBMITTED) === 0) || (strcmp($slip_status, BETPRESS_STATUS_TIMED_OUT) === 0) ) {
            continue;
        }
        
        $slip_lb = $slip['leaderboard_id'];
       echo"leaderboard_id:";echo $slip['leaderboard_id'];
        //skip slips that are not part of this leaderboard
        if ($slip_lb != $active_leaderboard['leaderboard_id']) {
            continue;
        }
        
        $slip_ID = $slip['slip_id'];
        echo $slip_ID;
     
        $stake = $slip['stake'];
        echo"stake:";echo $stake;
        $winnings = $slip['winnings'];
        $user_ID = $slip['user_id'];
        echo "user_ID:";echo $user_ID;
        $current_points = get_user_meta($user_ID, 'bp_points', true); 
        echo"current points:"; echo $current_points;
        $canceled = false;
        $awaiting = false;
        $win = false;
        $lose = false;
        $i=$i+1;
        $count_wins = 0;
        $bet_options_ids = unserialize($slip['bet_options_ids']);
        foreach ($bet_options_ids as $bet_option_ID => $bet_option_odd) {//cerca se ci sono opzioni vincenti
            echo"for each";
            $bet_option = betpress_get_bet_option($bet_option_ID);
            echo"bet_option_status";echo $bet_option['status'];
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
            echo"bet_event_cat_balance: ";echo$bet_option['bet_event_cat_balance'];
            if ($bet_option['is_paid'] == "0" && $bet_option['bet_event_cat_balance'] > 0){
                
                $opt_ammo = $bet_option['opt_ammo'];
                echo $opt_ammo;
                $opt_winnings = $bet_option['opt_winnings'];
                $cashback = $opt_ammo - $opt_winnings;
                $bet_option_id = $bet_option['bet_option_id'];
                $bet_option_cat_id = $bet_option['bet_event_cat_id'];
                $bet_creator_id = betpress_get_bet_creation_user_id_by_option( $bet_option_id);
                echo $bet_creator_id;
                
                foreach ($bet_creator_id as $bet_creator_ID => $bet_option_id) {
                    $user_creator_id = $bet_option_id['added_by_user_id'];
                }
                
                $current_creator_points = get_user_meta($user_creator_id, 'bp_points', true);
                $updated_creator_points = $current_creator_points + $cashback;
                
                $str_updated_creator_points = (string)$updated_creator_points;
                update_user_meta($user_creator_id, $str_updated_creator_points);
                
                echo"str_updated_points";echo $str_updated_creator_points;
                
                //check if the update took effect per bookmaker
                if (strcmp(get_user_meta($user_creator_id, 'bp_points', true), $str_updated_creator_points) !== 0) {
                    $db_errors = true;
                }
                $is_paid = betpress_pay_bookmaker($bet_option_cat_id);
                // può dare problemi, risolto
                if (false === $is_paid) {
                    $db_errors = true;
                }
                
                //qui devo finire ciclo if
            }else{echo"già pagata";echo $bet_option['bet_event_cat_id'];}
            
            
            $str_updated_points = (string)$updated_points;
            //update users points
            update_user_meta($user_ID, $str_updated_points);
            
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

$vai = check_slips();
if (true === $vai) {
    echo "fico!";
}else {
    echo "non va";
} 


    if (! empty($db_errors)) {
        foreach ($db_errors as $err) {
            echo $err . '<br />';
        }
    }

mysqli_close($link);

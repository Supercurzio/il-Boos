<?php

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_logs.txt');

$link = mysqli_connect('localhost', 'fi2hnlg1_boosdb', '6y213p!S)D', 'fi2hnlg1_boosdb');

if (! $link) {
    die('Couldnt connect to db.');
}

mysqli_set_charset($link, 'utf8');

function is_sport_exists($id)
{
    global $link;
    
    $q = mysqli_query($link, 'SELECT * FROM sports WHERE id = ' . $id);
    return $q->fetch_array();
}

function is_event_exists($id)
{
    global $link;
if($id== Null){$id=1;}//fede
    $q = mysqli_query($link, 'SELECT * FROM ilboos_bp_events WHERE id_sportmonks = ' . $id);
    return $q->fetch_array();
}

function is_match_exists($id)
{
    global $link;
    if($id== Null){$id=0;}//fede
    $q = mysqli_query($link, 'SELECT * FROM ilboos_bp_bet_events WHERE id_sportmonks = ' . $id);
    return $q->fetch_array();
}

function is_bet_exists($id, $match_ID)
{
    global $link;
    if($id== Null){$id=0;}//fede
    $q = mysqli_query($link, 'SELECT * FROM ilboos_bp_bet_events_cats WHERE id_sportmonks = ' . $id .' AND id_sm_match = '. $match_ID);
    return $q->fetch_array();
}

function is_choice_exists($id)
{
    global $link;
    if($id== Null){$id=0;}//fede
    $q = mysqli_query($link, 'SELECT * FROM ilboos_bp_bet_options WHERE id_sportmonks = ' . $id );
    return $q->fetch_array();
}

function betpress_get_events_max_order ($sport_id) {
    
    global $link;
    $q = mysqli_query($link, 'SELECT MAX(event_sort_order) FROM ilboos_bp_events WHERE sport_id = ' . $sport_id );
    return $q->fetch_array();

}
function betpress_get_bet_events_max_order($event_ID) {
    
    global $link;
    $q = mysqli_query($link, 'SELECT MAX(bet_event_sort_order) FROM ilboos_bp_bet_events WHERE event_id = ' . $event_ID .' LIMIT 1');
    return $q->fetch_array();
}

function betpress_get_cats_max_order($match_ID) {
    
    global $link;
    $q = mysqli_query($link, 'SELECT MAX(bet_event_cat_sort_order) FROM ilboos_bp_bet_events_cats WHERE id_sm_match='. $match_ID );
    //return $wpdb->get_var('SELECT MAX(bet_event_cat_sort_order) FROM ' . $wpdb->prefix . 'bp_bet_events_cats WHERE bet_event_id = ' . $bet_event_ID);
    return $q->fetch_array();
}

function betpress_get_bet_options_max_order($category_ID) {
    
    global $link;
    $q = mysqli_query($link, 'SELECT MAX(bet_option_sort_order) FROM  ilboos_bp_bet_options WHERE bet_event_cat_id = ' . $category_ID );
    //return $wpdb->get_var('SELECT MAX(bet_option_sort_order) FROM ' . $wpdb->prefix . 'bp_bet_options WHERE bet_event_cat_id = ' . $category_ID);
    return $q->fetch_array();
}
//$xml = simplexml_load_file('http://xml.cdn.betclic.com/odds_en.xml');

$cURL = curl_init();
$setopt_array = array(CURLOPT_URL => "https://soccer.sportmonks.com/api/v2.0/fixtures/between/2019-10-22/2019-10-28?api_token=qrGfkDWo2F4xOVgspVvpPxKVVWwuTO4jlch7TVOsDQC7bYLe0G49cZ5vs0P0&include=localTeam,visitorTeam,league,odds",    CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array());
curl_setopt_array($cURL, $setopt_array);
$json_response_data = curl_exec($cURL);
//print_r($json_response_data);
curl_close($cURL);
$sportmonks = json_decode($json_response_data, true);
//$last_xml_update = $xml->attributes()->file_date;
//$last_xml_update_unix = strtotime($last_xml_update);
$last_xml_update_unix = time();

$last_db_update_query = mysqli_query($link, 'SELECT MAX(time) as unix_time FROM updates');
$last_db_update = $last_db_update_query->fetch_array();

if ($last_xml_update_unix != $last_db_update['unix_time']) {
    $insert_update = mysqli_query($link, 'INSERT INTO updates (time) VALUES ( ' . $last_xml_update_unix . ')');
    
    if (false === $insert_update) {
        die('Couldnt insert update time.');
    }
    
    $db_errors = [ ];
    
    $delete_old_matches = mysqli_query($link, 'DELETE FROM `matches` WHERE (UNIX_TIMESTAMP() > `start`) OR (`start` > (UNIX_TIMESTAMP() + (60*60*24*30)))');
    
    if (false === $delete_old_matches) {
        $db_errors [] = 'Couldnt delete old matches.';
    }

    foreach ($sportmonks['data'] as $sport) {


        $sport_id = 1;
        //echo "<br>questo è sport_id:!!!!!!!FEDEDEDEDEE";echo $match;//DEBUG
        //echo $sport['flatOdds']['label'];//DEBUG
        foreach ($sport['odds'] as $bet['id']) {
            $bet_id = $bet['id'];
            
            //echo $bet['id'];
        
        }
            
       // echo $sport['odds']['data']['id'];//DEBUG
        foreach ($sport['league'] as $event['data']) {
            $event_id = $event['data']['id'];
  // echo $event_id;
            if (! is_event_exists($event_id)) {
                $event_name = mysqli_real_escape_string($link, $event['data']['name']);
                $event_max_sort_order = betpress_get_events_max_order ($sport_id);
                //echo $event_max_sort_order['MAX(event_sort_order)'];
                $insert_event = mysqli_query($link, 'INSERT INTO ilboos_bp_events (sport_id, id_sportmonks, event_name, event_sort_order) '
                    . 'VALUES (' . $sport_id . ', ' . $event_id . ', "' . $event_name . '", ' . ++ $event_max_sort_order['MAX(event_sort_order)'] .')');
                
               
                
                if (false === $insert_event) {
                    $db_errors [] = 'Couldnt insert event ' . $event_name;
                }
                
                $event_inserted_id = mysqli_insert_id($link);
                //echo"<br<insertito, ok:";echo $event_inserted_id;
            } else {
              $q = mysqli_query($link, 'SELECT event_id FROM ilboos_bp_events WHERE id_sportmonks = ' . $event_id);
               $event_db = $q->fetch_array();
               $event_inserted_id = $event_db['event_id'];
               //cho"<br<non insertito:";echo $event_inserted_id;
            }
            

           /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                $match_id = $sport['id'];
                //echo"questo è il match_id:<br>";echo $match_id;echo"fine del match_id fede<br>";
                if (! is_match_exists($match_id)) {
                    $local_name=$sport['localTeam']['data']['name'];
                    $visitor_name=$sport['visitorTeam']['data']['name'];
                    $match_name =$local_name . " - " . $visitor_name;
                    $local_name_id=$sport['localTeam']['data']['id'];
                    //echo $local_name_id;
                    //echo$match_name;echo$local_name_id;
                    $visitor_name_id=$sport['visitorTeam']['data']['id'];
                    //echo $visitor_name_id;
                    $match_start_date = $sport['time']['starting_at']['timestamp']  +0100;
                    //echo $match_start_date;echo"<br>";
                    $final_result=$sport['scores']['ft_score'];
                    //echo $final_result;
                    //echo $event_inserted_id;
                    if (($match_start_date > (time() + (60*60*24*30))) || ($match_start_date < time())) {
                        continue;
                    }
                    $bet_event_max_sort_order = betpress_get_bet_events_max_order($event_inserted_id);
              
                    //echo $bet_event_max_sort_order;
                    $insert_match = mysqli_query($link, 'INSERT INTO ilboos_bp_bet_events ( id_sportmonks,event_id, bet_event_name, deadline, id_sm_local_team, id_sm_guest_team, final_result, bet_event_sort_order,is_active) VALUES ( ' . $match_id . ', ' . $event_inserted_id . ',"' . $match_name . '", ' . $match_start_date . ', ' . $local_name_id . ', ' . $visitor_name_id . ', "' . $final_result . '", ' . ++ $bet_event_max_sort_order['MAX(bet_event_sort_order)'] .',1)');
                    
                    if (false === $insert_match) {
                        $db_errors [] = 'Couldnt insert match ' . $match_name;
                    }
                    
                    $match_inserted_id = mysqli_insert_id($link);
                    //echo "match_inserted_id:<br> ";echo $match_inserted_id;echo"<br>";
                    //Inserire qui
                    
               /////////////////    
                } else {
                    $q = mysqli_query($link, 'SELECT bet_event_id FROM ilboos_bp_bet_events WHERE id_sportmonks = ' . $match_id);
                    $match_db = $q->fetch_array();
                   // $match_inserted_id = $match_db['id'];
                    //echo "match_inserted_id:<br> ";echo $match_inserted_id;echo"<br>";
                    // inserire qui
                }
           /////////////////////////////////////////////////////////////////////////////////////////////////////////////     
          ////Megacambio     
                    
                
                $match_id = $sport['id'];echo"<br>";
                $q = mysqli_query($link, 'SELECT bet_event_id FROM ilboos_bp_bet_events WHERE id_sportmonks = ' . $match_id);
                $match_db = $q->fetch_array();
                $match_inserted_id = $match_db['bet_event_id'];
                
                $q1 = mysqli_query($link, 'SELECT id_sportmonks FROM ilboos_bp_bet_events WHERE 1');
                //echo $match_inserted_id;
                //if(!$q1){$match_db = $q->fetch_array();}else{ $match_db=null;}
                $match_db1 = $q1->fetch_array();
                
                
                $match_inserted_betclic_ids['id_sportmonks'] = $match_db1['id_sportmonks'];
                
                
                
                foreach ($match_inserted_betclic_ids as $match_inserted_betclic_id) {
                    
                    echo "Match_id:"; echo $match_id; echo"<br>";
                    $match_inserted=$match_inserted_betclic_id;
                    
                    echo "Match_inserted:";  echo $match_inserted_id; echo "<br>";
                    echo "ok ciclo if"; echo"<br>";
                    
                    foreach ($sport['odds']['data'] as $bets) {
                        
                        $bet_id = $match_inserted_id;
                        
                        //echo"ecco qui: ";echo"<br>";echo $bet['id'];echo"<br>";
                        echo $bets['name'];
                        if (! is_bet_exists($bet_id,$match_id)) {
                            $bet_name = $bets['name'];
                            //echo $match_id = $sport['id'];echo"<br>";
                            
                            $bet_event_cats_max_sort_order = betpress_get_cats_max_order($match_id);
                            //echo "match_inserted_id PRIMA DI INSERIRE:<br> ";echo $match_inserted_id;echo"<br>";
                            $insert_bet = mysqli_query($link, 'INSERT INTO ilboos_bp_bet_events_cats (bet_event_cat_sort_order, id_sm_match, bet_event_id, id_sportmonks, bet_event_cat_name) VALUES (' . ++ $bet_event_cats_max_sort_order['MAX(bet_event_cat_sort_order)'] . ',' . $match_id . ', ' . $match_inserted_id . ', ' . $bet_inserted_id . ', "' . $bet_name . '")');
                            
                            if (false === $insert_bet) {
                                $db_errors [] = 'Couldnt insert bet ' . $bet_name;
                            }
                            
                            $bet_inserted_cat_id = mysqli_insert_id($link);
                            //$bet_inserted_id=$insert_bet;
                            
                            
                        } else {
                            $q = mysqli_query($link, 'SELECT bet_event_cat_id FROM ilboos_bp_bet_events_cats WHERE bet_event_id = ' . $match_inserted_id .' AND id_sm_match = '.$match_id);
                            $bet_db = $q->fetch_array();
                            $bet_inserted_cat_id = $bet_db['bet_event_cat_id'];
                            echo"else";
                        }
                        //}
                        
        
        }
    }
 
    
   /////MEGA CAMBIO 
    
    foreach ($sportmonks['data'] as $sport) {
 
                
                //$match_id = $sport['id'];echo"<br>";
                $q = mysqli_query($link, 'SELECT id_sportmonks FROM ilboos_bp_bet_events_cats WHERE id_sportmonks = ' . $bet_id .' GROUP BY id_sportmonks');
                $bet1_db = $q->fetch_array();
                $bet_inserted_ids['id_sportmonks'] = $bet1_db['id_sportmonks'];
                foreach ($bet_inserted_ids as $bet_inserted_id1) {
                    
                    foreach ($bets['bookmaker']['data'] as $choice) {
                        
                        $choice_id = $choice['id'];
                        //$match_id = $sport['id'];echo"<br>";
                        $match_inserted_id = $match_db['bet_event_id'];
                        echo "scemo";
                        echo $choice_id;//DEBUG
                        echo "<br>";
                        echo $match_id;
                        $bet_inserted_id=$bet_inserted_id1;
                        echo"bet_inserted_id:";echo $bet_inserted_id;
                    
                        if (! is_choice_exists($bet_id)) {
                            foreach ($choice['odds']['data'] as $option) {
                                
                                
                                if($choice['name']="bet365"){
                                    $choice_name = mysqli_real_escape_string($link, str_replace('%', '', $option['label']));
                                    
                                    $choice_odd = (float) mysqli_real_escape_string($link,  $option['value']);
                                    echo$choice_odd;echo"<br>";
                                    $bet_option_max_sort_order = betpress_get_bet_options_max_order($bet_inserted_cat_id);
                                    $insert_choice = mysqli_query($link, 'INSERT INTO ilboos_bp_bet_options (bet_option_sort_order, bet_event_cat_id, bet_option_name, bet_option_odd, id_sportmonks, status, opt_winnings, opt_ammo, id_sm_match ) '
                                        . 'VALUES (' . ++ $bet_option_max_sort_order['MAX(bet_option_sort_order)'] . ' ,' . $bet_inserted_cat_id . ', "' . $choice_name . '", ' . $choice_odd . ', ' . $bet_inserted_id . ', "awaiting" , "0", "0",' . $match_inserted_id .' )');
                                    
                                    if (false === $insert_choice) {
                                        $db_errors [] = 'Couldnt insert choice ' . $choice_name;
                                    }
                                }//
                            }
                            
                        }else{echo "<br>esiste choice"; }
                    }
                }}
                ////////////////////////////////////////////////////////////////////////////////////////////
                
        }
        
        
        
        
        
    }
    
    if (! empty($db_errors)) {
        foreach ($db_errors as $err) {
            echo $err . '<br />';
        }
    }
}
    
mysqli_close($link);

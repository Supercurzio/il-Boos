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
    
    $q = mysqli_query($link, 'SELECT * FROM events WHERE betclic_id = ' . $id);
    return $q->fetch_array();
}

function is_match_exists($id)
{
    global $link;
    
    $q = mysqli_query($link, 'SELECT * FROM matches WHERE betclic_id = ' . $id);
    return $q->fetch_array();
}

function is_bet_exists($id)
{
    global $link;
    
    $q = mysqli_query($link, 'SELECT * FROM bets WHERE betclic_id = ' . $id);
    return $q->fetch_array();
}

function is_choice_exists($id)
{
    global $link;
    
    $q = mysqli_query($link, 'SELECT * FROM choices WHERE betclic_id = ' . $id);
    return $q->fetch_array();
}

$xml = simplexml_load_file('http://xml.cdn.betclic.com/odds_en.xml');

$last_xml_update = $xml->attributes()->file_date;
$last_xml_update_unix = strtotime($last_xml_update);

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

    foreach ($xml as $sport) {
        $sport_id = mysqli_real_escape_string($link, $sport->attributes()->id);
        
        if (! is_sport_exists($sport_id)) {
            $sport_name = mysqli_real_escape_string($link, $sport->attributes()->name);
            
            $insert_sport = mysqli_query($link, 'INSERT INTO sports (id, name) VALUES (' . $sport_id . ', "' . $sport_name . '")');
            
            if (false === $insert_sport) {
                $db_errors [] = 'Couldnt insert sport ' . $sport_name;
            }
        }

        foreach ($sport as $event) {
            $event_id = mysqli_real_escape_string($link, $event->attributes()->id);
        
            if (! is_event_exists($event_id)) {
                $event_name = mysqli_real_escape_string($link, $event->attributes()->name);

                $insert_event = mysqli_query($link, 'INSERT INTO events (sport_id, betclic_id, name) '
                        . 'VALUES (' . $sport_id . ', ' . $event_id . ', "' . $event_name . '")');
                
                if (false === $insert_event) {
                    $db_errors [] = 'Couldnt insert event ' . $event_name;
                }
                
                $event_inserted_id = mysqli_insert_id($link);
            } else {
                $q = mysqli_query($link, 'SELECT id FROM events WHERE betclic_id = ' . $event_id);
                $event_db = $q->fetch_array();
                $event_inserted_id = $event_db['id'];
            }
            
            foreach ($event as $match) {
                $match_id = mysqli_real_escape_string($link, $match->attributes()->id);
                
                if (! is_match_exists($match_id)) {
                    $match_name = mysqli_real_escape_string($link, $match->attributes()->name);
                
                    $match_start_date = strtotime($match->attributes()->start_date . ' +0100');
                    
                    if (($match_start_date > (time() + (60*60*24*30))) || ($match_start_date < time())) {
                        continue;
                    }
                    
                    $insert_match = mysqli_query($link, 'INSERT INTO matches (event_id, betclic_id, name, start) VALUES (' . $event_inserted_id . ', ' . $match_id . ', "' . $match_name . '", ' . $match_start_date . ')');
                    
                    if (false === $insert_match) {
                        $db_errors [] = 'Couldnt insert match ' . $match_name;
                    }
                    
                    $match_inserted_id = mysqli_insert_id($link);
                } else {
                    $q = mysqli_query($link, 'SELECT id FROM matches WHERE betclic_id = ' . $match_id);
                    $match_db = $q->fetch_array();
                    $match_inserted_id = $match_db['id'];
                }
                
                foreach ($match as $bets) {
                    foreach ($bets as $bet) {
                        $bet_id = mysqli_real_escape_string($link, $bet->attributes()->id);
                        
                        if (! is_bet_exists($bet_id)) {
                            $bet_name = mysqli_real_escape_string($link, $bet->attributes()->name);

                            $insert_bet = mysqli_query($link, 'INSERT INTO bets (match_id, betclic_id, name) VALUES (' . $match_inserted_id . ', ' . $bet_id . ', "' . $bet_name . '")');
                            
                            if (false === $insert_bet) {
                                $db_errors [] = 'Couldnt insert bet ' . $bet_name;
                            }
                            
                            $bet_inserted_id = mysqli_insert_id($link);
                        } else {
                            $q = mysqli_query($link, 'SELECT id FROM bets WHERE betclic_id = ' . $bet_id);
                            $bet_db = $q->fetch_array();
                            $bet_inserted_id = $bet_db['id'];
                        }

                        foreach ($bet as $choice) {
                            $choice_id = mysqli_real_escape_string($link, $choice->attributes()->id);
                            
                            if (! is_choice_exists($choice_id)) {
                                $choice_name = mysqli_real_escape_string($link, str_replace('%', '', $choice->attributes()->name));

                                $choice_odd = (float) mysqli_real_escape_string($link, $choice->attributes()->odd);
                                
                                $insert_choice = mysqli_query($link, 'INSERT INTO choices (bet_id, betclic_id, name, odd) '
                                        . 'VALUES (' . $bet_inserted_id . ', ' . $choice_id . ', "' . $choice_name . '", ' . $choice_odd . ')');
                                
                                if (false === $insert_choice) {
                                    $db_errors [] = 'Couldnt insert choice ' . $choice_name;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    if (! empty($db_errors)) {
        foreach ($db_errors as $err) {
            echo $err . '<br />';
        }
    }
}
    
mysqli_close($link);

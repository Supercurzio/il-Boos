<?php
//$link = mysqli_connect('localhost', 'root', '', 'ilboos');
$link = mysqli_connect('localhost', 'fi2hnlg1_boosdb', '6y213p!S)D', 'fi2hnlg1_boosdb');
if (!$link)
{
	die('Couldnt connect to db.');
}
mysqli_set_charset($link, 'utf8');

const BET_EVENTS      = "ilboos_bp_bet_events";
const BET_EVENTS_CATS = "ilboos_bp_bet_events_cats";
const BET_OPTIONS     = "ilboos_bp_bet_options";
const EVENT           = "ilboos_bp_events";
const SPORTS          = "ilboos_bp_sports";


if (! $link) {
    die('Couldnt connect to db.');
}

mysqli_set_charset($link, 'utf8');

//include '../../../wp-config.php';
//include '../../../wp-settings.php';
//include '../../../wp-load.php';
//set_include_path('../../../wp-includes/');
//include '../../../wp-includes/default-constants.php';
//include '../../../wp-includes/wp-db.php';

//include "functions.php";
//include "update_slips.php";
//$wpdb = new wpdb('fi2hnlg1_boosdb', '6y213p!S)D', 'fi2hnlg1_boosdb', 'localhost' );
//$xml = simplexml_load_file('http://xml.cdn.betclic.com/odds_en.xml');

$day_from= date('Y-m-d') ;
$days = time() - (2 * 24 * 60 * 60);

$day_to= date('Y-m-d', $days) ;

// 7 days; 24 hours; 60 mins; 60 secs
echo 'From:       '. $day_from ."\n";
echo 'To: '. $day_to ."\n";
// or using strtotime():
echo 'Next Week: '. date('Y-m-d', strtotime('+1 week')) ."\n";

//$today = "https://soccer.sportmonks.com/api/v2.0/fixtures/between/" . $day_from . "/" . $day_to . "?api_token=qrGfkDWo2F4xOVgspVvpPxKVVWwuTO4jlch7TVOsDQC7bYLe0G49cZ5vs0P0&include=localTeam,visitorTeam,league,odds&filter[odds.bookmaker_id=2]";

//echo "https://soccer.sportmonks.com/api/v2.0/fixtures/date/" .  $day_to . "?api_token=qrGfkDWo2F4xOVgspVvpPxKVVWwuTO4jlch7TVOsDQC7bYLe0G49cZ5vs0P0&include=localTeam,visitorTeam,league,odds&filter[odds.bookmaker_id=2]";

$cURL = curl_init();
$setopt_array = array(CURLOPT_URL => "https://soccer.sportmonks.com/api/v2.0/fixtures/date/" . $day_to. "?api_token=iEwvjxhuE4PDpdHqXmDNJHcMu81wHm8xXHdulTnZpKTRGRIK2JR3j35C6CAF&include=league,odds&filter[odds.bookmaker_id=2]&markets=1&leagues=384,2,4&tz=Europe/Rome",    CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array());
curl_setopt_array($cURL, $setopt_array);
$json_response_data = curl_exec($cURL);
//print_r($json_response_data);
curl_close($cURL);
$sportmonks = json_decode($json_response_data, true);
//$last_xml_update = $xml->attributes()->file_date;
//$last_xml_update_unix = strtotime($last_xml_update);
//var_dump($sportmonks);

    foreach ($sportmonks['data'] as $sport) {//PER OGNI RIGA DEL FEED

        $match_id = $sport['id'];
 echo $match_id;
  //$final_result = $sport['scores']['ft_score'];
 // echo $sport['winning_odds_calculated'];
  echo $final_result = $sport['scores']['ft_score'];


  echo '<pre>';
  //print_r($sport);
  echo '</pre>';
        if(true === $sport['winning_odds_calculated']){
            echo $match_id;
            
            global $link;//qui devi mettere ciclo foreach per agggiornare sia classic che social, per ora solo classic: is_featured=0
            $q_bet = mysqli_query($link, "SELECT bet_event_id FROM ".BET_EVENTS." WHERE is_featured < 2 AND id_sportmonks = " . $match_id );

            while($row = $q_bet->fetch_array())
            {
                $rows[] = $row;
            }
            
            foreach($rows as $row)
            {
            
            $match_inserted_id = $row['bet_event_id'];

        echo"dentro if<br>";echo $final_result;echo"<br>match_inserted_id: ";
        echo $match_inserted_id;echo"<br>";

        $updated = mysqli_query($link, "UPDATE ".BET_EVENTS." SET final_result = '". $final_result . "' WHERE bet_event_id = " . $match_inserted_id . " AND id_sportmonks = " . $match_id);

            if (0 === $updated) {
                $db_errors [] = 'Couldnt update the match.';
            }else {
                echo"inserito";
            }

             foreach ($sport['odds']['data'] as $bets) {
                 $bet_id= $bets['id'];
                 echo"<br>sono fico: "; echo $bets['name'];echo $bets['id'];
                 $q1 = mysqli_query($link, "SELECT bet_event_cat_id FROM ".BET_EVENTS."_cats WHERE id_sportmonks = " . $bet_id . " AND id_sm_match = " . $match_id );
                /////// 
                 while($row1 = $q1->fetch_array())
                 {
                     $rows1[] = $row1;
                 }
                 foreach($rows1 as $row1)//per ogni cat
                 {
                 
                 
                 $cat_inserted_id = $row1['bet_event_cat_id'];

                 foreach ($bets['bookmaker']['data'] as $choice) {
                     echo"ciao pep";echo $cat_inserted_id;
                     foreach ($choice['odds']['data'] as $option) {

                         //if(true === $option['winning'] ){


                             $choice_name = mysqli_real_escape_string($link, str_replace('%', '', $option['label']));
                     //aggiorna risultati classic
                             $q2 = mysqli_query($link, "SELECT * FROM ".BET_OPTIONS." WHERE bet_event_cat_id = " . $cat_inserted_id . " AND bet_option_name = '" . $choice_name . "'");
                             while($row2 = $q2->fetch_array())
                             {
                                 $rows2[] = $row2;
                             }
                             foreach($rows2 as $row2)
                             {
                                 
                             $opt_inserted_id = $row2['bet_option_id'];
                             if(true === $option['winning'] && $option['label'] == $choice_name){
                             //$updated = mysqli_query($link, "UPDATE ".BET_OPTIONS." SET status = 'winning' WHERE bet_option_id = " . $opt_inserted_id );

                             if (0 === $updated) {
                                 $db_errors [] = 'Couldnt update the choice.';
                             }else {
                                 //echo"inserita la choice vincente";
                                }
                             
                             }else{
                                 
                                // $updated = mysqli_query($link, "UPDATE ".BET_OPTIONS." SET status = 'losing' WHERE bet_option_id = " . $opt_inserted_id );
                                 
                                 if (0 === $updated) {
                                     $db_errors [] = 'Couldnt update the choice.';
                                 }else {
                                     // echo"inserita la choice perdente";
                                 }
                         }



                             } }
                 }
             }
            }

        echo"prima di checkslips";
  //      $vai = check_slips();
            
            if (false === $vai) {
                $db_errors = true;
                $err ="fanculo";
            }
        
           
            }
            //$q_bet9->close();
        /////// NON OLTRE
        }else { echo"Non ci sono aggiornamenti";}
 }
    if (! empty($db_errors)) {
        foreach ($db_errors as $err) {
            echo $err . '<br />';
        }
    }


mysqli_close($link);

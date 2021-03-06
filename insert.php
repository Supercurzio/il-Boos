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

function is_sport_exists($id)
{
	global $link;

	$q = mysqli_query($link, 'SELECT * FROM ' . SPORTS . ' WHERE id = ' . $id);
	return $q->fetch_array();
}
function is_event_exists($id)
{
    global $link;
    //if($id== Null){$id=1;}//fede
    $q = mysqli_query($link, 'SELECT * FROM ' . EVENT . ' WHERE id_sportmonks = ' . $id);
    return $q->fetch_array();
}

function is_match_exists($id)
{
    global $link;
    if($id== Null){$id=0;}//fede
    $q = mysqli_query($link, 'SELECT * FROM ' . BET_EVENTS . ' WHERE id_sportmonks = ' . $id);
    return $q->fetch_array();
}


function is_bet_exists($id, $match_ID)
{
	global $link;
	if ($id == Null)
	{
		$id = 0;
	}
	$q  = mysqli_query($link, 'SELECT * FROM ' . BET_EVENTS_CATS . ' WHERE id_sportmonks = ' . $id . ' AND id_sm_match  = ' . $match_ID);
	if ($q)
	{
		return $q->fetch_array();
	}
	else
	{
		return '';
	}

}

function is_choice_exists($id, $match_name)
{
	global $link;
	if ($id == Null)
	{
		$id = 0;
	} //fede
	$q  = mysqli_query($link, 'SELECT * FROM ' . BET_OPTIONS . ' WHERE bet_event_cat_id = ' . $id . ' AND bet_option_name  = "' . $match_name . '"');
	return $q->fetch_array();
}

function betpress_get_events_max_order($sport_id)
{

	global $link;
	$q = mysqli_query($link, 'SELECT MAX(event_sort_order) FROM ' . EVENT . ' WHERE sport_id = ' . $sport_id);
	return $q->fetch_array();

}
function betpress_get_bet_events_max_order($event_ID)
{

	global $link;
	$q = mysqli_query($link, 'SELECT MAX(bet_event_sort_order) FROM ' . BET_EVENTS . ' WHERE event_id = ' . $event_ID . ' LIMIT 1');
	return $q->fetch_array();
}

function betpress_get_cats_max_order($bet_event_ID)
{

	global $link;
	$q = mysqli_query($link, 'SELECT MAX(bet_event_cat_sort_order) FROM ' . BET_EVENTS_CATS . ' WHERE bet_event_id =' . $bet_event_ID);
	//return $wpdb->get_var('SELECT MAX(bet_event_cat_sort_order) FROM ' . $wpdb->prefix . 'BET_EVENTS_CATS WHERE bet_event_id = ' . $bet_event_ID);
	return $q->fetch_array();
}

function betpress_get_bet_options_max_order($category_ID)
{

	global $link;
	$q = mysqli_query($link, 'SELECT MAX(bet_option_sort_order) FROM  ' . BET_OPTIONS . ' WHERE bet_event_cat_id = ' . $category_ID);
	//return $wpdb->get_var('SELECT MAX(bet_option_sort_order) FROM ' . $wpdb->prefix . 'BET_OPTIONS WHERE bet_event_cat_id = ' . $category_ID);
	return $q->fetch_array();
}

$day_from = date('Y-m-d');
$days     = time() + (20 * 24 * 60 * 60);
$day_to   = date('Y-m-d', $days);

// 7 days; 24 hours; 60 mins; 60 secs
echo 'From:       ' . $day_from . "\n";
echo 'To: ' . $day_to . "\n";
// or using strtotime():
echo 'Next Week: ' . date('Y-m-d', strtotime('+1 week')) . "\n";

$cURL         = curl_init();
$setopt_array = array(
	//CURLOPT_URL => "https://soccer.sportmonks.com/api/v2.0/fixtures/between/" . $day_from . "/" . $day_to . "?api_token=qrGfkDWo2F4xOVgspVvpPxKVVWwuTO4jlch7TVOsDQC7bYLe0G49cZ5vs0P0&include=localTeam,visitorTeam,league,odds&filter[odds.bookmaker_id=2]&markets=1&leagues=384&tz=Europe/Rome",
   CURLOPT_URL => "https://soccer.sportmonks.com/api/v2.0/fixtures/between/" . $day_from . "/" . $day_to . "?api_token=qrGfkDWo2F4xOVgspVvpPxKVVWwuTO4jlch7TVOsDQC7bYLe0G49cZ5vs0P0&include=localTeam,visitorTeam,league,odds&filter[odds.bookmaker_id=2]&markets=1&leagues=384,2,4&tz=Europe/Rome",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HTTPHEADER => array()
);
curl_setopt_array($cURL, $setopt_array);
$json_response_data = curl_exec($cURL);
//print_r($json_response_data);
curl_close($cURL);
$sportmonks = json_decode($json_response_data, true);
if ($sportmonks['data'] == null)
{
	echo "non ci sono dati";
}
foreach ($sportmonks['data'] as $sport)
{ //PER OGNI RIGA DEL FEED
	$sport_id             = 1;
	$match_id             = $sport['id'];
	$match_inserted_id    = 0; //INIZIALIZZO ID NEL DB ILBOOS
	$bet_inserted_id      = 0; //INIZIALIZZO ID NEL DB ILBOOS DA BET_EVENTS
	echo $match_id;
	echo '<pre>';
	//print_r($sport);
	echo '</pre>';

	foreach ($sport['league'] as $event['data'])
	{ //PER OGNI RIGA DEL FEED SCORRI LE LEGHE E INSERISCI

		$event_id             = $event['data']['id'];
		//echo"!!!EVENT_ID!!!!:";echo $event_id;
		if (!is_event_exists($event_id))
		{
			echo '1';
			$event_name           = mysqli_real_escape_string($link, $event['data']['name']);
			$event_max_sort_order = betpress_get_events_max_order($sport_id);
			//echo $event_max_sort_order['MAX(event_sort_order)'];
			$insert_event         = mysqli_query($link, 'INSERT INTO ' . EVENT . ' (sport_id, id_sportmonks, event_name, event_sort_order) ' . 'VALUES (' . $sport_id . ', ' . $event_id . ', "' . $event_name . '", ' . ++$event_max_sort_order['MAX(event_sort_order)'] . ')');

			if (false === $insert_event)
			{
				$db_errors[]                      = 'Couldnt insert event ' . $event_name;
			}

			$event_inserted_id    = mysqli_insert_id($link);
			//echo"<br<insertito, ok:";echo $event_inserted_id;

		}
		else
		{
			echo '2';
			$q                    = mysqli_query($link, 'SELECT event_id FROM ' . EVENT . ' WHERE id_sportmonks = ' . $event_id);
			$event_db             = $q->fetch_array();
			$event_inserted_id    = $event_db['event_id'];
			echo"<br>non insertito:";echo $event_inserted_id;

		}
	}

	if (! is_match_exists($match_id))
	{ //INSERISCE LE PARTITE
		echo '3';
		$local_name           = $sport['localTeam']['data']['name'];
		$final_result         = $sport['scores']['ft_score'];
		$visitor_name         = $sport['visitorTeam']['data']['name'];
		$match_name           = $local_name . " - " . $visitor_name;
		$local_name_id        = $sport['localTeam']['data']['id'];
		//echo $local_name_id;
		//echo$match_name;echo$local_name_id
		$visitor_name_id      = $sport['visitorTeam']['data']['id'];
		//echo $visitor_name_id;
		$match_start_date     = $sport['time']['starting_at']['timestamp'] + 0100;
		//echo $match_start_date;echo"<br>";
		if (($match_start_date > (time() + (60 * 60 * 24 * 30))) || ($match_start_date < time()))
		{
			continue;
		}
		$bet_event_max_sort_order = betpress_get_bet_events_max_order($event_inserted_id);
		//echo 'INSERT INTO ' . BET_EVENTS . ' ( id_sportmonks,event_id, bet_event_name, deadline, id_sm_local_team, id_sm_guest_team, final_result, bet_event_sort_order,is_active) VALUES ( ' . $match_id . ', ' . $event_inserted_id . ',"' . $match_name . '", ' . $match_start_date . ', ' . $local_name_id . ', ' . $visitor_name_id . ', "' . $final_result . '", ' . ++$bet_event_max_sort_order['MAX(bet_event_sort_order)'] . ',1)';
		$insert_match             = mysqli_query($link, 'INSERT INTO ' . BET_EVENTS . ' ( id_sportmonks,event_id, bet_event_name, deadline, id_sm_local_team, id_sm_guest_team, final_result, bet_event_sort_order,is_active) VALUES ( ' . $match_id . ', ' . $event_inserted_id . ',"' . $match_name . '", ' . $match_start_date . ', ' . $local_name_id . ', ' . $visitor_name_id . ', "' . $final_result . '", ' . ++$bet_event_max_sort_order['MAX(bet_event_sort_order)'] . ',1)');

		if (false === $insert_match)
		{
			$db_errors[]                          = 'Couldnt insert match ' . $match_name;
		}

		$match_inserted_id        = mysqli_insert_id($link); //ID DEL DB ILBOOS PER IL MATCH
		$bet_inserted_id          = $match_id;

	}
	else
	{
		echo '4';
		$q                        = mysqli_query($link, 'SELECT bet_event_id FROM ' . BET_EVENTS . ' WHERE id_sportmonks = ' . $match_id);
		$match_db                 = $q->fetch_array();
		$match_inserted_id        = $match_db['bet_event_id'];
		$bet_inserted_id          = $match_id;
		//echo "match_inserted_id:<br> ";echo $match_inserted_id;echo"<br>";

	}

	foreach ($sport['odds']['data'] as $bets)
	{
		echo "<br>sono fico: ";
		echo $bets['name'];

		$bets_id = $bets['id'];
		foreach ($bets as $bet_ID)
		{
			//$bet_id = $bets['id'];
			//$bet_id = $bet_ID;
			//echo "ecco qui: ";
			//echo "<br>";
			//echo $bets_id;
			//echo "<br>";

			foreach ($bets['bookmaker']['data'] as $choice)
			{

				$choice_id                     = $choice['id'];
				$bet_name                      = $bets['name'];

				if (!is_choice_exists($choice_id, $bet_name))
				{
					foreach ($choice['odds']['data'] as $option)
					{

						if (!is_bet_exists($bets_id, $match_id))
						{
							$bet_name                      = $bets['name'];
							//echo $match_id = $sport['id'];echo"<br>";
							$bet_event_cats_max_sort_order = betpress_get_cats_max_order($match_inserted_id);
							//echo "match_inserted_id PRIMA DI INSERIRE:<br> ";echo $match_inserted_id;echo"<br>";
							//echo 'INSERT INTO ' . BET_EVENTS_CATS . ' (bet_event_cat_sort_order, id_sm_match, bet_event_id, id_sportmonks, bet_event_cat_name) VALUES (' . ++$bet_event_cats_max_sort_order['MAX(bet_event_cat_sort_order)'] . ',' . $match_id . ', ' . $match_inserted_id . ', ' . $bets_id . ', "' . $bet_name . '"  )';
							$insert_bet                    = mysqli_query($link, 'INSERT INTO ' . BET_EVENTS_CATS . ' (bet_event_cat_sort_order, id_sm_match, bet_event_id, id_sportmonks, bet_event_cat_name) VALUES (' . ++$bet_event_cats_max_sort_order['MAX(bet_event_cat_sort_order)'] . ',' . $match_id . ', ' . $match_inserted_id . ', ' . $bets_id . ', "' . $bet_name . '"  )');

							if (false === $insert_bet)
							{
								$db_errors[]                               = 'Couldnt insert bet ' . $bet_name;
							}

							$bet_inserted_cat_id           = mysqli_insert_id($link);

						}
						else
						{
							$bet_name                      = $bets['name'];
							//echo" Prova di controllo: " ;echo $bet_name;
							$q                             = mysqli_query($link, 'SELECT bet_event_cat_id FROM ' . BET_EVENTS_CATS . ' WHERE bet_event_id = ' . $match_inserted_id . ' AND bet_event_cat_name = "' . $bet_name . '" ');
							$bet_db                        = $q->fetch_array();
							$bet_inserted_cat_id           = $bet_db['bet_event_cat_id'];
							//echo"else";

						}

						$choice_name                   = mysqli_real_escape_string($link, str_replace('%', '', $option['label']));
						$choice_odd                    = (float)mysqli_real_escape_string($link, $option['value']);
						$bet_name                      = $choice_name;

						if (!is_choice_exists($bet_inserted_cat_id, $choice_name))
						{

							//echo$choice_odd;echo"<br>";
							$bet_option_max_sort_order     = betpress_get_bet_options_max_order($bet_inserted_cat_id);
							//echo 'INSERT INTO ' . BET_OPTIONS . ' (bet_option_sort_order, bet_event_cat_id, bet_option_name, bet_option_odd, id_sportmonks, status, opt_winnings, opt_ammo, id_sm_match ) ' . 'VALUES (' . ++$bet_option_max_sort_order['MAX(bet_option_sort_order)'] . ' ,' . $bet_inserted_cat_id . ', "' . $choice_name . '", ' . $choice_odd . ', ' . $bet_inserted_id . ', "awaiting" , "0", "0",' . $match_id . ' )';
							$insert_choice                 = mysqli_query($link, 'INSERT INTO ' . BET_OPTIONS . ' (bet_option_sort_order, bet_event_cat_id, bet_option_name, bet_option_odd, id_sportmonks, status, opt_winnings, opt_ammo, id_sm_match ) ' . 'VALUES (' . ++$bet_option_max_sort_order['MAX(bet_option_sort_order)'] . ' ,' . $bet_inserted_cat_id . ', "' . $choice_name . '", ' . $choice_odd . ', ' . $bet_inserted_id . ', "awaiting" , "0", "0",' . $match_id . ' )');

							if (false === $insert_choice)
							{
								$db_errors[]                               = 'Couldnt insert choice ' . $choice_name;
							}
						}

					}

				}
				else
				{ //echo "<br>peppone";

				}
			}

		}
	}

}
echo"<br>INSERT FINITO, OK!<br>";
if (!empty($db_errors))
{
	foreach ($db_errors as $err)
	{
		echo $err . '<br />';
	}
}

mysqli_close($link);

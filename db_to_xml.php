<?php

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 0);
ini_set('display_errors', 1);

$link = mysqli_connect('localhost', 'fi2hnlg1_boosdb', '6y213p!S)D', 'fi2hnlg1_boosdb');

if (! $link) {
    die('DB error.');
}

mysqli_set_charset($link, 'utf8');

$xml = new DOMDocument('1.0', 'UTF-8');
$betpress = $xml->createElement('betpress');

$betpress->setAttribute('last_update', time());

$betpress = $xml->appendChild($betpress);
$sports_q = mysqli_query($link, 'SELECT * FROM sports');

while ($sp = $sports_q->fetch_assoc()) {
    $sport = $xml->createElement('sport');

    $sport->setAttribute('id', $sp['id']);

    $sport->setAttribute('name', $sp['name']);

    $betpress->appendChild($sport);

    $events_q = mysqli_query($link, 'SELECT e.id AS id, e.name AS name, e.sport_id, m.start

                                        FROM  `events` AS e

                                        JOIN  `matches` AS m

                                        ON ( e.id = m.event_id

                                            AND  m.start < ( UNIX_TIMESTAMP() + ( 60 * 60 * 24 * 30 ) ) )  

                                        GROUP BY name

                                        HAVING e.sport_id = ' . $sp['id'] . '

                                        ORDER BY id ASC');

    while ($ev = $events_q->fetch_assoc()) {
        $event = $xml->createElement('event');

        $event->setAttribute('id', $ev['id']);

        $event->setAttribute('name', $ev['name']);

        $sport->appendChild($event);
        $matches_q = mysqli_query($link, 'SELECT * FROM matches '

                . 'WHERE (event_id = ' . $ev['id'] . ') '

                    . 'AND (start < UNIX_TIMESTAMP() + (60*60*24*30))'

                    . 'AND start > UNIX_TIMESTAMP()');

        while ($ma = $matches_q->fetch_assoc()) {
            $match = $xml->createElement('match');

            $match->setAttribute('id', $ma['id']);

            $match->setAttribute('name', $ma['name']);

            $match->setAttribute('starts', $ma['start']);

            $event->appendChild($match);

            

            $bets_main_q = mysqli_query($link, 'SELECT * FROM bets '

                    . 'WHERE match_id = ' . $ma['id'] . ' '

                        . 'AND name = "3Way Result"');

            while ($be = $bets_main_q->fetch_assoc()) {
                $bet = $xml->createElement('bet');

                $bet->setAttribute('id', $be['id']);

                $bet->setAttribute('name', $be['name']);

                $match->appendChild($bet);

                

                $choices_q = mysqli_query($link, 'SELECT * FROM choices WHERE bet_id = ' . $be['id']);

                while ($ch = $choices_q->fetch_assoc()) {
                    $choice = $xml->createElement('choice');

                    $choice->setAttribute('id', $ch['id']);

                    $choice->setAttribute('name', $ch['name']);

                    $choice->setAttribute('odd', $ch['odd']);
                    
                    $choice->setAttribute('match_ref', $ch['match_ref']);

                    $bet->appendChild($choice);
                }
            }

            $bets_q = mysqli_query($link, 'SELECT * FROM bets '

                    . 'WHERE match_id = ' . $ma['id'] . ' '

                        . 'AND name != "3Way Result"');

            while ($be = $bets_q->fetch_assoc()) {
                $bet = $xml->createElement('bet');

                $bet->setAttribute('id', $be['id']);

                $bet->setAttribute('name', $be['name']);

                $match->appendChild($bet);

                $choices_q = mysqli_query($link, 'SELECT * FROM choices WHERE bet_id = ' . $be['id']);

                while ($ch = $choices_q->fetch_assoc()) {
                    $choice = $xml->createElement('choice');

                    $choice->setAttribute('id', $ch['id']);

                    $choice->setAttribute('name', $ch['name']);

                    $choice->setAttribute('odd', $ch['odd']);
                    
                    $choice->setAttribute('match_ref', $ch['match_ref']);

                    $bet->appendChild($choice);
                }
            }
        }
    }
}

$xml->formatOutput = true;

$xml->saveXML();

$result = $xml->save('/home/fi2hnlg1/public_html/wp-content/plugins/web-able-betpress-federico-674293cf0e77/betpress.xml');

if (false === $result) {
    echo 'db 2 xml failed :(';
}

mysqli_close($link);

<?php
header('Access-Control-Allow-Origin: *');
header('Content-type: text/plain; charset=utf-8');

require_once 'NotORM.php';
$pdo = new PDO('sqlite:database.sqlite');
$db = new NotORM($pdo);

$tableName = 'ranking';

if (empty($_POST['player'])) die();

// Add or update a player in the database:

$player = $_POST['player'];
$score = (!empty($_POST['score'])) ? $_POST['score'] : 0;

$playerRow = $db->$tableName('player LIKE ?', $player)->fetch();

$db->$tableName()->insert_update(
    array(
        'player' => $player
    ),
    array(
        'score' => $score,
        'high_score' => $score,
        'last_activity' => time()
    ),
    array(
        'score' => $score,
        'high_score' =>  ($score > $playerRow['high_score']) ? $score : $playerRow['high_score'],
        'last_activity' => time()
    )
);

// Delete players who last played one week ago:

$oneWeekAgo = time() - (7*24*60*60);
$db->$tableName("last_activity < $oneWeekAgo")->delete();

// Sends the response:

$onlineRanking = array('Online Players Ranking:');

foreach ($db->$tableName()->where('last_activity > ' . (time() - 30))->order('score DESC')->limit(15) as $row)
{
    array_push($onlineRanking, '[' . $row['score'] . '] ' . $row['player']);
}

$weekRanking = array('Best of The Week:');

foreach ($db->$tableName()->order('high_score DESC')->limit(15) as $row)
{
    array_push($weekRanking, $row['player'] . ' [' . $row['high_score'] . ']');
}

echo implode("\n", $onlineRanking) . '|#|' . implode("\n", $weekRanking);
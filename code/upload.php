<?php
ini_set('max_execution_time', 600);

// Функция преобразования времени в секунды
function time_to_seconds(string $time): ?int
{
  $seconds = strtotime($time) - strtotime('TODAY');
  return $seconds < 0 ? null : $seconds;
}
// Функция для формирования ответа
function response(array $response, int $code = 200)
{
	header('Content-type: text/html; charset=utf-8');
	header('Content-Type: application/json');
	http_response_code($code);
	die(json_encode($response));
}

// База данных
require_once('database.php');
$db = new DataBase;

// Redis
try {
  $redis = new \Redis();
  $redis->connect('redis', 6379);
  $stageRedis = json_decode($redis->get('stage'));
} catch(\RedisException $e) {
  exit('Connect error');
}

// Очистка предыдущих данных
$db->query("TRUNCATE TABLE `groups`");
$db->query("TRUNCATE TABLE `teams`");
$db->query("TRUNCATE TABLE `competitors`");

// Перебираем строки
if (isset($_FILES) && isset($_FILES['file']) && ($handle = fopen($_FILES['file']['tmp_name'], "r")) !== false) {
  while (($data = fgetcsv($handle, 1000, ";")) !== false) {
    foreach($data as $key => $value) {
      $data[$key] = iconv('windows-1251', 'utf-8', $value);
    }
    $number = (int) $data[9];
    if ($number) {
      $name = explode(' ', $data[6] . ' ');
      $lastname = trim($name[0]);
      $firstname = trim($name[1]);
      $rank = (int) $data[15];
      $year = (int) $data[16];
      $start_ar = explode(',', $data[5]);
      $start = time_to_seconds($start_ar[0]);
      if (!$start)
        continue;
      $start_datetime = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . date('H', $start) . ':' . date('i', $start) . ':' . date('s', $start)) - (($stageRedis->gmt ?? 0) * 3600));
      $group_name = $data[7];
      $team_name = $data[8];
      // Группа
      if (!$group = $db->selectOne("SELECT * FROM `groups` WHERE `name` = '" . $group_name . "'")) {
        $groupId = $db->insert("INSERT INTO
            `groups`
          SET
            `name` = '" . $group_name . "',
            `fullname` = '" . $group_name . "'");
        $group = $db->selectOne("SELECT * FROM `groups` WHERE `id` = '" . $groupId . "'");
      }
      // Команда
      $team_name = str_replace('г.', 'город', $team_name);
      $team_name = str_replace('обл.', 'область', $team_name);
      if (!$team = $db->selectOne("SELECT * FROM `teams` WHERE `name` = '" . $team_name . "'")) {
        $teamId = $db->insert("INSERT INTO
            `teams`
          SET
            `name` = '" . str_replace("'", "\'", $team_name) . "'");
        $team = $db->selectOne("SELECT * FROM `teams` WHERE `id` = '" . $teamId . "'");
      }
      // Участник
      if (!$competitor = $db->selectOne("SELECT * FROM `competitors` WHERE `number` = '" . $number . "'")) {
        $competitorId = $db->insert("INSERT INTO
            `competitors`
          SET
            `group_id` = '" . $group->id . "',
            `number` = '" . $number . "',
            `lastname` = '" . $lastname . "',
            `firstname` = '" . $firstname . "',
            `rank` = '" . $rank . "',
            `year` = '" . $year . "',
            `team_id` = '" . $team->id . "',
            `datetime_start` = '" . $start_datetime . "'");
        $competitor = $db->selectOne("SELECT * FROM `competitors` WHERE `id` = '" . $competitorId . "'");
      }
    }
  }
  fclose($handle);
}

// Формируем список для отправки в ответ
$return = [
  'competitors' => [],
  'groups' => [],
  'teams' => [],
];
// Группы
$sql = "SELECT
  `id`,
  `name`
FROM
  `groups`
ORDER BY
  `id` ASC";
if ($groups = $db->select($sql)) {
  foreach ($groups as $group) {
    $return['groups'][] = [
      'id'    => (int) $group->id,
      'name'  => $group->name,
    ];
  }
}
// Команды
$sql = "SELECT
  `id`,
  `name`
FROM
  `teams`
ORDER BY
  `id` ASC";
if ($teams = $db->select($sql)) {
  foreach ($teams as $team) {
    $return['teams'][] = [
      'id'    => (int) $team->id,
      'name'  => $team->name,
    ];
  }
}
// Участники
$sql = "SELECT
  `id`,
  `lastname`,
  `firstname`,
  `number`,
  `team_id`,
  `group_id`,
  `datetime_start`,
  `datetime_finish`,
  `result_points`,
  `result_time`,
  `result_place`
FROM
  `competitors`
ORDER BY
  `id` ASC";
if ($competitors = $db->select($sql)) {
  foreach ($competitors as $competitor) {
    $return['competitors'][] = [
      'id'        => (int) $competitor->id,
      'team_id'   => (int) $competitor->team_id,
      'group_id'  => (int) $competitor->group_id,
      'number'    => (int) $competitor->number,
      'fullname'  => $competitor->lastname . ' ' . $competitor->firstname,
      'stage'     => [
        'timestamp_start'   => strtotime($competitor->datetime_start),
        'timestamp_finish'  => $competitor->datetime_finish ? strtotime($competitor->datetime_finish) : null,
        'points'            => $competitor->result_points ? (int) $competitor->result_points : null,
        'result'            => $competitor->result_time ? (int) $competitor->result_time : null,
        'place'             => $competitor->result_place ? (int) $competitor->result_place : null,
      ],
    ];
  }
}
// Отправка в Redis
$redis->publish('data', json_encode($return));
$redis->close();
// Ответ
response($return, 200);

<?php
ini_set('max_execution_time', 600);

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

$json = $_GET;
unset($json['com']);

// Без данных - ошибочный запрос
if (empty($json))
  die ('empty');

// Логгируем запрос
$log = [
  'id'            => null,
  'created_at'    => time(),
  'source'        => 'WinOrient',
  'request_body'  => json_encode($json),
  'response_body' => null,
  'response_code' => null,
  'response_url'  => null,
];
$log['id'] = $db->insert("INSERT INTO
  `logs`
SET
  `created_at` = '" . date('Y-m-d H:i:s', $log['created_at']) . "',
  `source` = '" . $log['source'] . "',
  `request_body` = '" . $log['request_body'] . "'");
// Отправляем в Redis
$redis->publish('log', json_encode($log));

// Функция преобразования времени в секунды
function time_to_seconds(string $time): ?int
{
  $seconds = strtotime($time) - strtotime('TODAY');
  return $seconds < 0 ? null : $seconds;
}
function response(array $response, int $code = 200)
{
	header('Content-type: text/html; charset=utf-8');
	header('Content-Type: application/json');
	http_response_code($code);
	die(json_encode($response));
}

// Получаем данные
$number	= isset($_GET["num"])   ? intval($_GET["num"])            : false;	// Номер участника
$flag	= isset($_GET["fl"])      ? intval($_GET["fl"])             : 0;		  // Статус отметки
$result	= isset($_GET["r"])     ? time_to_seconds($_GET["r"])     : false;	// Время участника
$points	= isset($_GET["lis"])   ? intval($_GET["lis"])            : 0;		  // Количество РП (охота на лис)
$finish	= isset($_GET["tfin"])  ? time_to_seconds($_GET["tfin"])  : null;		// Время финиша
$_controls	= isset($_GET["s"])	? $_GET['s']				              : "";		  // Отметка участника

// Участник
if (!$competitor = $db->selectOne("SELECT * FROM `competitors` WHERE `number` = '" . $number . "'"))
  die ('no competitor');

// Расчёт стартового времени, если есть финишное
$datetime_start = $competitor->datetime_start;
$datetime_finish = $competitor->datetime_finish;
if ($finish) {
  $datetime_finish = date('Y-m-d H:i:s', $finish + strtotime('TODAY') - (($stageRedis->gmt ?? 0) * 3600));
  if ($result)
    $datetime_start = date('Y-m-d H:i:s', strtotime($datetime_finish) - $result);
}
$status = !$flag;

if ($stageRedis->type == 'orient') {
  $log['response_url'] = "http://orgeo.ru/online/sv?" . http_build_query($json);
  $points = 0;
}
else {
  // Группа
  if (!$group = $db->selectOne("SELECT * FROM `groups` WHERE `id` = '" . $competitor->group_id . "'"))
    die ('no group');
  // Команда
  $team = $db->selectOne("SELECT * FROM `teams` WHERE `id` = '" . $competitor->team_id . "'");
  $json = [
    'persons' => [
      [
        'ref_id'              => $competitor->number,
        'bib'                 => $competitor->number,
        'result_status'       => 'OK',
        'group_name'          => $group->name,
        'name'                => $competitor->lastname . ', ' . $competitor->firstname,
        'organization'        => $team ? $team->name : '',
        'card_number'         => null,
        'start'               => strtotime($datetime_start) - strtotime('TODAY') + (($stageRedis->gmt ?? 0) * 3600),
        'out_of_competition'  => 'false',
        'result_ms'           => $result * 100,
        'score'               => $points,
        'splits'              => [],
      ]
    ]
  ];
	// Распознование сплитов
	$_controls_count = floor(strlen($_controls) / 28);
	$_controls_array = [];
  $control_seconds_old = null;
	for ($i = 0; $i < $_controls_count; $i++) {
		$control_number = intval(base_convert(substr($_controls, 4 + $i * 28, 2), 16, 10));
		$control_seconds = intval(base_convert(substr($_controls, 15 + $i * 28, 5), 16, 10));
    $codes = [
      '31' => '1',
      '32' => '2',
      '33' => '3',
      '34' => '4',
      '35' => '5',
      '91' => '1F',
      '92' => '2F',
      '93' => '3F',
      '94' => '4F',
      '95' => '5F',
      '50' => 'B',
      '40' => 'K',
      '100' => 'S',
    ];
		// КП (начиная с 31)
		if ($control_number > 30) {
			$control_seconds = $control_seconds - (strtotime($datetime_start) - strtotime('TODAY') + (($stageRedis->gmt ?? 0) * 3600));
			$json['persons'][0]['splits'][] = [
				"time"  => $control_seconds_old ? ($control_seconds - $control_seconds_old) : $control_seconds,
				"code"  => $codes[$control_number] ?? $control_number,
			];
      $control_seconds_old = $control_seconds;
		}
	}
  $log['response_url'] = "http://orgeo.ru/online/sv?id=" . $_GET['id'] . "&sk=" . $_GET['sk'] . "&sub=" . $_GET['sub'];
}

// Ретрансляция в оргео
if (isset($_GET['id']) && isset($_GET['sk']) && isset($_GET['sub'])) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $log['response_url']);
  if ($stageRedis->type == 'orient')
    curl_setopt($ch, CURLOPT_POST, 0);
  else {
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
  }
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $log['response_body'] = curl_exec($ch);
  $log['response_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close ($ch);
  // Логгирование
  $db->query("UPDATE
      `logs`
    SET
      `response_body` = '" . str_replace("\\", "\\\\", $log['response_body']) . "',
      `response_code` = '" . $log['response_code'] . "',
      `response_url` = '" . $log['response_url'] . "'
    WHERE
      `id` = '" . $log['id'] . "'");
  // Отправляем в Redis
  $redis->publish('log', json_encode($log));
}

// Сохраняем показатели
$db->query("UPDATE
  `competitors`
SET
  `datetime_start` = '" . $datetime_start . "',
  `datetime_finish` = " . ($datetime_finish ? ("'" . $datetime_finish . "'") : 'NULL') . ",
  `result_status` = '" . ($status ? '1' : '0') . "',
  `result_points` = " . ($status ? ("'" . $points . "'") : 'NULL') . ",
  `result_time` = " . ($status ? ("'" . $result . "'") : 'NULL') . ",
  `result_place` = NULL,
  `result_scores` = NULL,
  `result_comment` = NULL
WHERE
  `id` = '" . $competitor->id . "'");

// Результат этапа
$stage = [
  'timestamp_start'   => strtotime($datetime_start),
  'timestamp_finish'  => strtotime($datetime_finish),
  'points'            => null,
  'result'            => null,
  'place'             => null,
  'status'            => null,
];
// ВЫСТАВЛЯЕМ ПОРЯДОК МЕСТ
$tmpPlace = 0;
$tmpPlaceLast = 0;
$tmpResultLast = [
  'points'	=> 0,
  'time'		=> 0,
];
$tmpUpdatesPlace = [];
// Участники
$sql = "SELECT
    `id`,
    `result_status`,
    `result_points`,
    `result_time`
  FROM
    `competitors`
  WHERE
    `group_id` = '" . $competitor->group_id . "'
    AND `result_status` IS NOT NULL
  ORDER BY
    `result_status` DESC,
    `result_points` DESC,
    `result_time` ASC";
// Результаты в группе
if ($groupResults = $db->select($sql)) {
  foreach ($groupResults as $groupResult) {
    if ($groupResult->result_status && ($stageRedis->type == 'orient' || $groupResult->result_points)) {
      // Счётчик места
      $tmpPlace++;
      // Условие сдвига места
      if ($tmpPlace == 1 || $tmpResultLast['points'] != $groupResult->result_points || $tmpResultLast['time'] != $groupResult->result_time)
        $tmpPlaceLast = $tmpPlace;
      // Изменение места
      $tmpUpdatesPlace[$groupResult->id] = $tmpPlaceLast;
      // Запоминаем результат
      $tmpResultLast = [
        'points'	=> $groupResult->result_points,
        'time'		=> $groupResult->result_time,
      ];
    }
    // Фиксация своих показателей
    if ($groupResult->id == $competitor->id && $status) {
      $stage['place'] = $tmpPlaceLast ? (int) $tmpPlaceLast : null;
      $stage['points'] = $groupResult->result_points ? (int) $groupResult->result_points : null;
      $stage['result'] = $groupResult->result_time ? (int) $groupResult->result_time : null;
      $stage['status'] = $groupResult->result_status ? (int) $groupResult->result_status : null;
    }
  }
}
// Выполняем внесение изменений
if (count($tmpUpdatesPlace)) {
  foreach ($tmpUpdatesPlace as $id => $place) {
    $db->query("UPDATE
        `competitors`
      SET
        `result_place` = '" . $place . "'
      WHERE
        `id` = '" . $id . "'");
  }
}

// Ответ
$return = [
  'status'	=> 'ok',
  'data'		=> [
    'competitor' => [
      'number'  => (int) $competitor->number,
      'stage'   => $stage,
    ],
  ],
];

// Отправляем в Redis
if (count($tmpUpdatesPlace))
  $redis->publish('updatePlace', json_encode($tmpUpdatesPlace));
$redis->publish('finished', json_encode($return['data']['competitor']));
$redis->close();

response($return, 200);

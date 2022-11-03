<?
ini_set('max_execution_time', 600);

// База данных
require_once('database.php');
$db = new DataBase;

// Подключаем к Redis
try {
  $redis = new \Redis();
  $redis->connect('redis', 6379);
  $stageRedis = json_decode($redis->get('stage'));
} catch(\RedisException $e) {
  exit('Connect error');
}

// Получаем данные
$json = file_get_contents('php://input');

// Без данных - ошибочный запрос
if (!$json)
  die ('empty');

// Логгируем запрос
$log = [
  'id'            => null,
  'created_at'    => time(),
  'source'        => 'FjwW',
  'request_body'  => $json,
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

// Ретрансляция в оргео
if (isset($_GET['id']) && isset($_GET['sk']) && isset($_GET['sub'])) {
  $log['response_url'] = "http://orgeo.ru/online/sv?id=" . $_GET['id'] . "&sk=" . $_GET['sk'] . "&sub=" . $_GET['sub'] . "&";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $log['response_url']);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
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

// Функция для форматирования ответа
function response(array $response, int $code = 200)
{
	header('Content-type: text/html; charset=utf-8');
	header('Content-Type: application/json');
	http_response_code($code);
	die(json_encode($response));
}

if ($data = json_decode($json)) {
  // Данные
  $sendFinished = true;
  $sendData = false;
  // Участники
  $persons = $data->persons;
  if (isset($data->params)) {
    $sendFinished = false;
    $db->query("TRUNCATE TABLE `groups`");
    $db->query("TRUNCATE TABLE `teams`");
    $db->query("TRUNCATE TABLE `competitors`");
  }
  if ($persons) {
    foreach ($persons as $person) {
      $number = $person->bib;
      $group_name = $person->group_name;
      $name = explode(',', $person->name);
      $lastname = trim($name[0]);
      $firstname = trim($name[1]);
      $team_name = $person->organization;
      $start = $person->start;
      // Группа
      if (!$group = $db->selectOne("SELECT * FROM `groups` WHERE `name` = '" . $group_name . "'")) {
        $groupId = $db->insert("INSERT INTO
            `groups`
          SET
            `name` = '" . $group_name . "',
            `fullname` = '" . $group_name . "'");
        $group = $db->selectOne("SELECT * FROM `groups` WHERE `id` = '" . $groupId . "'");
        $sendData = true;
      }
      // Команда
      if (!$team = $db->selectOne("SELECT * FROM `teams` WHERE `name` = '" . $team_name . "'")) {
        $teamId = $db->insert("INSERT INTO
            `teams`
          SET
            `name` = '" . str_replace("'", "\'", $team_name) . "'");
        $team = $db->selectOne("SELECT * FROM `teams` WHERE `id` = '" . $teamId . "'");
        $sendData = true;
      }
      // Расчёты
      $startTimestamp = $start;
      $datetime_start = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . date('H', $startTimestamp) . ':' . date('i', $startTimestamp) . ':' . date('s', $startTimestamp)) - (($stageRedis->gmt ?? 0) * 3600));
      // Статус
      $status = null;
      if (isset($person->result_ms))
        $status = $person->result_status == 'OK' ? 1 : 0;
      // Если статус есть - устанавливаем другие данные
      if (!is_null($status)) {
        $result = !$status ? 0 : (($person->result_ms ?? 0) / 100);
        $points = !$status ? 0 : ($person->score ?? 0);
        $datetime_finish = date('Y-m-d H:i:s', !$status ? time() : (strtotime($datetime_start) + $result));
        $timeCur = 100 * (date('H') * 3600 + date('i') * 60 + date('s'));
        $timeFin = !$status ? $timeCur : $result + $start;
        // Формируем коммент из порядка лис
        $comment = '';
        $splits = $person->splits ?? null;
        if ($splits) {
          $comment_ar = [];
          foreach($splits as $split) {
            $comment_ar[] = $split->code;
          }
          $comment = implode('-', $comment_ar);
        }
      }
      // Участник
      if (!$competitor = $db->selectOne("SELECT * FROM `competitors` WHERE `number` = '" . $number . "'")) {
        if (is_null($status))
          $competitorId = $db->insert("INSERT INTO
              `competitors`
            SET
              `group_id` = '" . $group->id . "',
              `number` = '" . $number . "',
              `lastname` = '" . $lastname . "',
              `firstname` = '" . $firstname . "',
              `team_id` = '" . $team->id . "',
              `datetime_start` = '" . $datetime_start . "'");
        else
          $competitorId = $db->insert("INSERT INTO
              `competitors`
            SET
              `group_id` = '" . $group->id . "',
              `number` = '" . $number . "',
              `lastname` = '" . $lastname . "',
              `firstname` = '" . $firstname . "',
              `team_id` = '" . $team->id . "',
              `datetime_start` = '" . $datetime_start . "',
              `datetime_finish` = '" . $datetime_finish . "',
              `result_status` = '" . $status . "',
              `result_points` = " . ($status ? ("'" . $points . "'") : 'NULL') . ",
              `result_time` = " . ($status ? ("'" . $result . "'") : 'NULL') . ",
              `result_comment` = '" . $comment . "'");
        $competitor = $db->selectOne("SELECT * FROM `competitors` WHERE `id` = '" . $competitorId . "'");
        $sendData = true;
      }
      else {
        if ($competitor->group_id != $group->id || $competitor->lastname != $lastname || $competitor->firstname != $firstname || $competitor->team_id != $team->id)
          $sendData = true;
        $competitor->group_id = $group->id;
        $competitor->lastname = $lastname;
        $competitor->firstname = $firstname;
        $competitor->team_id = $team->id;
        if (is_null($status))
          $db->query("UPDATE
              `competitors`
            SET
              `group_id` = '" . $competitor->group_id . "',
              `lastname` = '" . $competitor->lastname . "',
              `firstname` = '" . $competitor->firstname . "',
              `team_id` = " . $competitor->team_id . ",
              `datetime_start` = '" . $datetime_start . "',
              `datetime_finish` = NULL,
              `result_status` = NULL,
              `result_points` = NULL,
              `result_time` = NULL,
              `result_place` = NULL,
              `result_scores` = NULL,
              `result_comment` = '" . $comment . "'
            WHERE
              `id` = '" . $competitor->id . "'");
        else
          $db->query("UPDATE
              `competitors`
            SET
              `group_id` = '" . $competitor->group_id . "',
              `lastname` = '" . $competitor->lastname . "',
              `firstname` = '" . $competitor->firstname . "',
              `team_id` = " . $competitor->team_id . ",
              `datetime_start` = '" . $datetime_start . "',
              `datetime_finish` = '" . $datetime_finish . "',
              `result_status` = '" . $status . "',
              `result_points` = " . ($status ? ("'" . $points . "'") : 'NULL') . ",
              `result_time` = " . ($status ? ("'" . $result . "'") : 'NULL') . ",
              `result_place` = NULL,
              `result_scores` = NULL,
              `result_comment` = '" . $comment . "'
          WHERE
            `id` = '" . $competitor->id . "'");
      }
      // Одиночный спортсмен, отправка в Redis
      if ($sendFinished) {
        // Результат этапа
        $stage = [
          'timestamp_start'   => strtotime($datetime_start),
          'timestamp_finish'  => strtotime($datetime_finish),
          'points'            => null,
          'result'            => null,
          'place'             => null,
        ];
        // ВЫСТАВЛЯЕМ ПОРЯДОК МЕСТ
        $tmpPlace = 0;
        $tmpPlaceLast = 0;
        $tmpResultLast = [
          'points'	=> 0,
          'time'		=> 0,
        ];
        $tmpSQLUpdates = [];
        // Участники
        $sql = "SELECT
            `id`,
            `result_status`,
            `result_points`,
            `result_time`
          FROM
            `competitors`
          WHERE
            `group_id` = '" . $group->id . "'
            AND `result_status` IS NOT NULL
          ORDER BY
            `result_status` DESC,
            `result_points` DESC,
            `result_time` ASC";
        // Результаты в группе
        if ($groupResults = $db->select($sql)) {
          foreach ($groupResults as $groupResult) {
            if ($groupResult->result_status && $groupResult->result_points) {
              // Счётчик места
              $tmpPlace++;
              // Условие сдвига места
              if ($tmpPlace == 1 || $tmpResultLast['points'] != $groupResult->result_points || $tmpResultLast['time'] != $groupResult->result_time)
                $tmpPlaceLast = $tmpPlace;
              // Запрос в массив
              $tmpSQLUpdates[] = "UPDATE
                  `competitors`
                SET
                  `result_place` = '" . $tmpPlaceLast . "'
                WHERE
                  `id` = '" . $groupResult->id . "'";
              // Запоминаем результат
              $tmpResultLast = [
                'points'	=> $groupResult->result_points,
                'time'		=> $groupResult->result_time,
              ];
            }
            // Фиксация своих показателей
            if ($groupResult->id == $competitor->id && $status) {
              $stage['place'] = $tmpPlaceLast;
              $stage['points'] = $groupResult->result_points;
              $stage['result'] = $groupResult->result_time;
            }
          }
        }
        // Выполняем внесение изменений
        if (count($tmpSQLUpdates)) {
          foreach ($tmpSQLUpdates as $sql) {
            $db->query($sql);
          }
        }
        // Ответ
        $return = [
          'status'	=> 'ok',
          'data'		=> [
            'competitor' => [
              'number'  => $competitor->number,
              'stage'   => $stage,
            ],
          ],
        ];
        if (!$sendData) {
          $redis->publish('finished', json_encode($return['data']['competitor']));
          $redis->close();
        }
        response($return, 200);
      }
    }
  }
  // Если не отправка в Redis, значит была загрузка всего списка и требуется пересчёт всех групп
  if (!$sendFinished) {
    $sql = "SELECT
        `id`
      FROM
        `groups`";
    if ($groups = $db->select($sql)) {
      foreach ($groups as $group) {
        $tmpPlace = 0;
        $tmpPlaceLast = 0;
        $tmpResultLast = [
          'points'	=> 0,
          'time'		=> 0,
        ];
        $tmpSQLUpdates = [];
        // Участники
        $sql = "SELECT
            `id`,
            `result_status`,
            `result_points`,
            `result_time`
          FROM
            `competitors`
          WHERE
            `group_id` = '" . $group->id . "'
            AND `result_status` IS NOT NULL
          ORDER BY
            `result_status` DESC,
            `result_points` DESC,
            `result_time` ASC";
        // Результаты в группе
        if ($groupResults = $db->select($sql)) {
          foreach ($groupResults as $groupResult) {
            if ($groupResult->result_status && $groupResult->result_points) {
              // Счётчик места
              $tmpPlace++;
              // Условие сдвига места
              if ($tmpPlace == 1 || $tmpResultLast['points'] != $groupResult->result_points || $tmpResultLast['time'] != $groupResult->result_time)
                $tmpPlaceLast = $tmpPlace;
              // Запрос в массив
              $tmpSQLUpdates[] = "UPDATE
                  `competitors`
                SET
                  `result_place` = '" . $tmpPlaceLast . "'
                WHERE
                  `id` = '" . $groupResult->id . "'";
              // Запоминаем результат
              $tmpResultLast = [
                'points'	=> $groupResult->result_points,
                'time'		=> $groupResult->result_time,
              ];
            }
          }
        }
        // Выполняем внесение изменений
        if (count($tmpSQLUpdates)) {
          foreach ($tmpSQLUpdates as $sql) {
            $db->query($sql);
          }
        }
      }
    }
  }
  if ($sendData) {
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
  }
  response(['status' => 'ok'], 200);
}
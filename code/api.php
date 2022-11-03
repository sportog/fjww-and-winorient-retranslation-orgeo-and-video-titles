<?php
ini_set('display_errors', 0);

// База данных
require_once('database.php');
$db = new DataBase;

// Redis
try {
  $redis = new \Redis();
  $redis->connect('redis', 6379);
} catch(\RedisException $e) {
  exit('Connect error');
}

switch ($_GET['mode']) {
  case 'formatter':
    die('<pre>' . json_encode(json_decode($_POST['value']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</pre>');
    break;

  case 'orgeo':
    $stageRedis = json_decode($redis->get('stage'));
    $timestamp_today = strtotime('TODAY') - (($stageRedis->gmt ?? 0) * 3600);
    $orgeo_url = $_POST['url'];

    $data = [
      'params' => [
        'start_list' => true,
      ],
      'persons' => [],
    ];
    
    // Участники
    $sql = "SELECT
      `c`.`lastname`,
      `c`.`firstname`,
      `c`.`number`,
      `g`.`name` as `group_name`,
      `t`.`name` as `team_name`,
      `c`.`datetime_start`
    FROM
      `competitors` `c`
    JOIN
      `groups` `g` ON `c`.`group_id` = `g`.`id`
    LEFT JOIN
      `teams` `t` ON `c`.`team_id` = `t`.`id`
    ORDER BY
      `c`.`id` ASC";
    if ($competitors = $db->select($sql)) {
      foreach ($competitors as $competitor) {
        $start_seconds = strtotime($competitor->datetime_start) - $timestamp_today;
        $data['persons'][] = [
          "ref_id"        => $competitor->number,
          "bib"           => $competitor->number,
          "result_status" => "OK",
          "group_name"    => $competitor->group_name,
          "name"          => $competitor->lastname . ", " . $competitor->firstname,
          "organization"  => $competitor->team_name ?? '',
          "card_number"   => null,
          "start"         => $start_seconds > 0 ? $start_seconds : 0,
        ];
      }
    }
    $json = json_encode($data);
    // Логгируем запрос
    $log = [
      'id'            => null,
      'created_at'    => time(),
      'source'        => 'SportOG',
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
    $log['response_url'] = $orgeo_url;
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
    break;

  case 'truncate':
    // Очистка предыдущих данных
    $db->query("TRUNCATE TABLE `groups`");
    $db->query("TRUNCATE TABLE `teams`");
    $db->query("TRUNCATE TABLE `competitors`");
    $return = [
      'competitors' => [],
      'groups' => [],
      'teams' => [],
    ];
    // Отправка в Redis
    $redis->publish('data', json_encode($return));
    $redis->close();
    die (json_encode($return));
    break;
  
  default:
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
      `result_place`,
      `result_status`
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
            'status'            => $competitor->result_status ? (int) $competitor->result_status : null,
          ],
        ];
      }
    }
    
    // Добавляем логгирование для админ-панели
    if ($_GET['logs']) {
      $return['logs'] = [];
      $sql = "SELECT
        `id`,
        `created_at`,
        `source`,
        `request_body`,
        `response_body`,
        `response_code`,
        `response_url`
      FROM
        `logs`
      ORDER BY
        `id` DESC";
      // Результаты в группе
      if ($logs = $db->select($sql)) {
        foreach ($logs as $log) {
          $return['logs'][] = [
            'id'            => (int) $log->id,
            'created_at'    => strtotime($log->created_at),
            'source'        => $log->source,
            'request_body'  => $log->request_body,
            'response_body' => $log->response_body,
            'response_code' => $log->response_code,
            'response_url'  => $log->response_url,
          ];
        }
      }
    }
    die (json_encode($return));
}
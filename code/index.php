<?
ini_set('max_execution_time', 600);

$dir = 'logs';
$filename = time() . '.json';

// Не храним больше 25 логов
if (is_dir($dir)) {
    $files = scandir($dir . '/request', 1);
    $i = 0;
    foreach ($files as $file) {
        $i++;
        if ($i > 25) {
            @unlink($dir . '/request/' . $file);
            @unlink($dir . '/response/' . $file);
        }
    }
}

// Получаем данные
$json = file_get_contents('php://input');

if(!$json)
    die ('empty');

// Логгирование
file_put_contents($dir . '/request/' . $filename, $json);

// Ретрансляция в оргео
if (isset($_GET['id']) && isset($_GET['sk']) && isset($_GET['sub'])) {
    $url_orgeo = "http://orgeo.ru/online/sv?id=" . $_GET['id'] . "&sk=" . $_GET['sk'] . "&sub=" . $_GET['sub'] . "&";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_orgeo);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $return = curl_exec($ch);
    $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);
    // Логгирование
    file_put_contents($dir . '/response/' . $filename, json_encode(['body' => $return, 'code' => $return_code, 'url' => $url_orgeo]));
}

function response(array $response, int $code = 200)
{
	header('Content-type: text/html; charset=utf-8');
	header('Content-Type: application/json');
	http_response_code($code);
	die(json_encode($response));
}
function sectotime ($value) {
    return date('H:i:s', $value);
}
function mb_str_pad($input, $length, $padding = ' ', $padType = STR_PAD_RIGHT, $encoding = 'UTF-8')
{
	$result = $input;
	if (($paddingRequired = $length - mb_strlen($input, $encoding)) > 0) {
		switch ($padType) {
			case STR_PAD_LEFT:
				$result =
					mb_substr(str_repeat($padding, $paddingRequired), 0, $paddingRequired, $encoding) .
					$input;
				break;
			case STR_PAD_RIGHT:
				$result =
					$input .
					mb_substr(str_repeat($padding, $paddingRequired), 0, $paddingRequired, $encoding);
				break;
			case STR_PAD_BOTH:
				$rightPaddingLength = floor($paddingRequired / 2);
				$leftPaddingLength = $paddingRequired - $rightPaddingLength;
				$result =
					mb_substr(str_repeat($padding, $leftPaddingLength), 0, $leftPaddingLength, $encoding) .
					$input .
					mb_substr(str_repeat($padding, $rightPaddingLength), 0, $rightPaddingLength, $encoding);
				break;
		}
	}

	return $result;
}
function sectime($sec)
{
	$date = new DateTime();
	$date->setTime(0, 0, $sec);
	return $date->format('G:i:s');
}

try {
    $redis = new \Redis();
    $redis->connect('redis', 6379);
} catch(\RedisException $e) {
    exit('Connect error');
}

// База данных
require_once('database.php');
$db = new DataBase;

if ($data = json_decode($json)) {
    // Данные
    $sendRedis = true;

    $persons = $data->persons;
    if (isset($data->params)) {
        $sendRedis = false;
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
                $groupId = $db->insert("
                INSERT INTO
                    `groups`
                SET
                    `name` = '" . $group_name . "',
                    `fullname` = '" . $group_name . "'");
                $group = $db->selectOne("SELECT * FROM `groups` WHERE `id` = '" . $groupId . "'");
            }
            // Команда
            if (!$team = $db->selectOne("SELECT * FROM `teams` WHERE `name` = '" . $team_name . "'")) {
                $teamId = $db->insert("
                INSERT INTO
                    `teams`
                SET
                    `name` = '" . str_replace("'", "\'", $team_name) . "'");
                $team = $db->selectOne("SELECT * FROM `teams` WHERE `id` = '" . $teamId . "'");
            }

            // Расчёты
            $startTimestamp = $start;
            $datetime_start = date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' ' . date('H', $startTimestamp) . ':' . date('i', $startTimestamp) . ':' . date('s', $startTimestamp)));
            
            $status = $person->result_status == 'OK' ? 1 : 0;
            $result = !$status ? 0 : (($person->result_ms ?? 0) / 100);
            $points = !$status ? 0 : ($person->score ?? 0);

            $datetime_finish = date('Y-m-d H:i:s', !$status ? time() : (strtotime($datetime_start) + $result));

            $timeCur = 100 * (date('H') * 3600 + date('i') * 60 + date('s'));
            $timeFin = !$status ? $timeCur : $result + $start;

            $comment = '';
            $splits = $person->splits ?? null;
            if ($splits) {
                $comment_ar = [];
                foreach($splits as $split) {
                    $comment_ar[] = $split->code;
                }
                $comment = implode('-', $comment_ar);
            }

            // Участник
            if (!$competitor = $db->selectOne("SELECT `c`.* FROM `competitors` `c` JOIN `groups` `g` ON `c`.`group_id` = `g`.`id` WHERE `c`.`number` = '" . $number . "'")) {
                $competitorId = $db->insert("
                INSERT INTO
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
            }
            else {
                $competitor->group_id = $group->id;
                $competitor->lastname = $lastname;
                $competitor->firstname = $firstname;
                $competitor->team_id = $team->id;
                $db->query("
                UPDATE
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

            if ($sendRedis) {
                // Результат этапа
                $resultsStage = [
                    'start'		=> date('H:i:s', strtotime($datetime_start)),
                    'finish'	=> date('H:i:s', strtotime($datetime_finish)),
                    'status'	=> $status,
                    'place'		=> 0,
                    'result'	=> '-',
                    'running'	=> 0,
                    'outrun'	=> 0,
                    'length'	=> 0,
                ];

                // ВЫСТАВЛЯЕМ ПОРЯДОК МЕСТ
                    $tmpPlace = 0;
                    $tmpPlaceLast = 0;
                    $tmpResultLast = [
                        'points'	=> 0,
                        'time'		=> 0,
                    ];
                    $tmpSQLUpdates = [];
                    $tmpStageLiders = [];
                    $tmpMaxPoints = 0;
                    $tmpStageTitres = [];
                    $myResult = false;
                    $groupResultOld = null;
                    $tmpPlaceOld = null;
                
                    // Радиоспорт
                    $sql = "SELECT
                        `c`.`id`,
                        `c`.`lastname`,
                        `c`.`firstname`,
                        `c`.`number`,
                        `c`.`result_comment`,
                        `t`.`name` `team_name`,
                        `c`.`result_status`,
                        `c`.`result_points`,
                        `c`.`result_time`,
                        `c`.`result_place`,
                        `c`.`datetime_start`,
                        `c`.`datetime_finish`
                    FROM
                        `competitors` `c`
                    LEFT JOIN
                        `teams` `t` ON `c`.`team_id` = `t`.`id`
                    WHERE
                        `c`.`group_id` = '" . $group->id . "'
                    ORDER BY
                        `c`.`result_status` DESC,
                        `c`.`result_points` DESC,
                        `c`.`result_time` ASC";
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
                                if ($groupResult->result_place != $tmpPlaceLast)
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
                
                                // Формируем протокол
                                if ($tmpPlace < 6)
                                    $tmpStageLiders[] = mb_str_pad($tmpPlace, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25) . ' ' . mb_str_pad($groupResult->result_points . ' КП, ' . sectime($groupResult->result_time), 15, " ", STR_PAD_LEFT);
                
                                // Для титров всегда выводится первая тройка
                                if (($tmpPlace < 6 && ($myResult || !$resultsStage['status'])) || $tmpPlace < 5)
                                    $tmpStageTitres[] = [
                                        'fullname'	=> $groupResult->lastname . ' ' . $groupResult->firstname,
                                        'team_name' => $groupResult->team_name,
                                        'result'	=> $groupResult->result_points . ' КП, ' . sectime($groupResult->result_time),
                                        'place'		=> $tmpPlace,
                                        'number'	=> $groupResult->number,
                                        'comment'	=> $groupResult->result_comment,
                                    ];
                            
                            }
                
                            // Фиксация своих показателей
                            if ($groupResult->id == $competitor->id) {
                                $myResult = $tmpPlaceLast;
                                if ($resultsStage['status']) {
                                    $resultsStage['place'] = $tmpPlaceLast;
                                    $resultsStage['result'] = $groupResult->result_points . ' КП, ' . sectime($groupResult->result_time);
                
                                    // Добивка для титров если место выше 5
                                    if ($tmpPlace > 4) {
                                        if ($tmpPlace > 5) {
                                            array_pop($tmpStageTitres);
                                            $tmpStageTitres[] = [
                                                'fullname'	=> $groupResultOld->lastname . ' ' . $groupResultOld->firstname,
                                                'team_name' => $groupResultOld->team_name,
                                                'result'	=> $groupResultOld->result_points . ' КП, ' . sectime($groupResultOld->result_time),
                                                'place'		=> $tmpPlaceOld,
                                                'number'	=> $groupResultOld->number,
                                                'comment'	=> $groupResultOld->result_comment,
                                            ];
                                        }
                                        $tmpStageTitres[] = [
                                            'fullname'	=> $groupResult->lastname . ' ' . $groupResult->firstname,
                                            'team_name' => $groupResult->team_name,
                                            'result'	=> $groupResult->result_points . ' КП, ' . sectime($groupResult->result_time),
                                            'place'		=> $tmpPlace,
                                            'number'	=> $groupResult->number,
                                            'comment'	=> $groupResult->result_comment,
                                        ];
                                    }
                                } else
                                    $resultsStage['result'] = 'снят(а)';
                            }
                
                            $groupResultOld = $groupResult;
                            $tmpPlaceOld = $tmpPlace;
                        }
                    }
                    // Выполняем внесение изменений
                    if (count($tmpSQLUpdates)) {
                        foreach ($tmpSQLUpdates as $sql) {
                            $db->query($sql);
                        }
                    }
                    // Добавляем таблицу лидеров
                    if (count($tmpStageLiders)) {
                        $resultsStage['liders'] = [
                            'п/п Фамилия Имя                     Результат',
                            ...$tmpStageLiders
                        ];
                    }
                    // Добавляем титры
                    if (count($tmpStageTitres)) {
                        $resultsStage['list'] = $tmpStageTitres;
                    }
                
                // Ответ
                $return = [
                    'status'	=> 'ok',
                    'data'		=> [
                        'competitor' => [
                            'fullname'  		=> $competitor->lastname . ' ' . $competitor->firstname,
                            'lastname'  		=> $competitor->lastname,
                            'firstname' 		=> $competitor->firstname,
                            'group_fullname'	=> !empty($group->fullname) ? $group->fullname : $group->name,
                            'team_name'			=> !is_null($team) ? $team->name : '',
                            'team_id'			=> !is_null($team) ? $team->id : null,
                            'number'    		=> $competitor->number,
                            'comment'			=> $comment,
                        ],
                        'results' => [
                            'stage' => $resultsStage,
                        ],
                        'time' => date('H:i:s'),
                    ]
                ];

                $redis->publish('finish', json_encode($return['data']));
                $redis->close();

                response($return, 200);
            }
        }
    }

    if (!$sendRedis) {
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
                    $tmpStageLiders = [];
                    $tmpMaxPoints = 0;
                    $tmpStageTitres = [];
                    $myResult = false;
                    $groupResultOld = null;
                    $tmpPlaceOld = null;
                
                    // Радиоспорт
                    $sql = "SELECT
                        `c`.`id`,
                        `c`.`lastname`,
                        `c`.`firstname`,
                        `c`.`number`,
                        `c`.`result_comment`,
                        `t`.`name` `team_name`,
                        `c`.`result_status`,
                        `c`.`result_points`,
                        `c`.`result_time`,
                        `c`.`result_place`,
                        `c`.`datetime_start`,
                        `c`.`datetime_finish`
                    FROM
                        `competitors` `c`
                    LEFT JOIN
                        `teams` `t` ON `c`.`team_id` = `t`.`id`
                    WHERE
                        `c`.`group_id` = '" . $group->id . "'
                    ORDER BY
                        `c`.`result_status` DESC,
                        `c`.`result_points` DESC,
                        `c`.`result_time` ASC";
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
                                if ($groupResult->result_place != $tmpPlaceLast)
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
                
                            $groupResultOld = $groupResult;
                            $tmpPlaceOld = $tmpPlace;
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
}
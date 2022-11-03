<?php

// База данных
require_once('database.php');
$db = new DataBase;

function sectime($sec)
{
	$date = new DateTime();
	$date->setTime(0, 0, $sec);
	return $date->format('H:i:s');
}

function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = 'UTF-8')
{
    $input_length = mb_strlen($input, $encoding);
    $pad_string_length = mb_strlen($pad_string, $encoding);

    if ($pad_length <= 0 || ($pad_length - $input_length) <= 0) {
        return $input;
    }

    $num_pad_chars = $pad_length - $input_length;

    switch ($pad_type) {
        case STR_PAD_RIGHT:
            $left_pad = 0;
            $right_pad = $num_pad_chars;
            break;

        case STR_PAD_LEFT:
            $left_pad = $num_pad_chars;
            $right_pad = 0;
            break;

        case STR_PAD_BOTH:
            $left_pad = floor($num_pad_chars / 2);
            $right_pad = $num_pad_chars - $left_pad;
            break;
    }

    $result = '';
    for ($i = 0; $i < $left_pad; ++$i) {
        $result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
    }
    $result .= $input;
    for ($i = 0; $i < $right_pad; ++$i) {
        $result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
    }

    return $result;
}

// Обработка разряда
function textRank($key)
{
    $ranks = [
        'б/р',
        'Iю',
        'IIю',
        'IIIю',
        'I',
        'II',
        'III',
        'КМС',
        'МС',
        'МСМК',
        'ЗМС',
    ];
    return $ranks[$key];
}
function scoreRank($key)
{
    $ranks = [
        1.5,
        3,
        3,
        3,
        25,
        12,
        6,
        50,
        100,
        100,
        100,
    ];
    return $ranks[$key];
}

function teamReplace($team)
{
    $teams = [
        'г. Санкт-Петербург' => 'город Санкт-Петербург',
    ];
    return $teams[$team] ?? $team;
}

function RusEnding($n, $n1, $n2, $n5) {
    if($n >= 11 and $n <= 19) return $n5;
    $n = $n % 10;
    if($n == 1) return $n1;
    if($n >= 2 and $n <= 4) return $n2;
    return $n5;
  }

function exeTable($value) {
    $arr = [
        '1000' => [120,140,160,180,200,0,0,0],
        '950' => [118,138,158,178,198,0,0,0],
        '900' => [116,136,156,176,196,0,0,0],
        '850' => [114,134,154,174,194,0,0,0],
        '800' => [112,132,152,172,192,0,0,0],
        '750' => [110,130,150,170,190,210,0,0],
        '700' => [108,128,148,168,188,208,0,0],
        '650' => [106,126,146,166,186,206,0,0],
        '600' => [104,124,144,164,184,204,0,0],
        '550' => [102,122,142,162,182,202,0,0],
        '500' => [100,120,140,160,180,200,0,0],
        '475' => [0,118,138,158,178,198,0,0],
        '450' => [0,116,136,156,176,196,0,0],
        '425' => [0,114,134,154,174,194,0,0],
        '400' => [0,112,132,152,172,192,0,0],
        '375' => [0,110,130,150,170,190,210,0],
        '350' => [0,108,128,148,168,188,208,0],
        '325' => [0,106,126,146,166,186,206,0],
        '300' => [0,104,124,144,164,184,204,0],
        '275' => [0,102,122,142,162,182,202,0],
        '250' => [0,100,120,140,160,180,200,0],
        '237' => [0,0,118,138,158,178,198,0],
        '224' => [0,0,116,136,156,176,196,0],
        '211' => [0,0,114,134,154,174,194,0],
        '198' => [0,0,112,132,152,172,192,0],
        '185' => [0,0,110,130,150,170,190,210],
        '172' => [0,0,108,128,148,168,188,208],
        '159' => [0,0,106,126,146,166,186,206],
        '146' => [0,0,104,124,144,164,184,204],
        '133' => [0,0,102,122,142,162,182,202],
        '120' => [0,0,100,120,140,160,180,200],
        '114' => [0,0,0,118,138,158,178,198],
        '108' => [0,0,0,116,136,156,176,196],
        '102' => [0,0,0,114,134,154,174,194],
        '96' => [0,0,0,112,132,152,172,192],
        '90' => [0,0,0,110,130,150,170,190],
        '84' => [0,0,0,108,128,148,168,188],
        '78' => [0,0,0,106,126,146,166,186],
        '72' => [0,0,0,104,124,144,164,184],
        '66' => [0,0,0,102,122,142,162,182],
        '60' => [0,0,0,100,120,140,160,180],
        '54' => [0,0,0,0,118,138,158,178],
        '48' => [0,0,0,0,116,136,156,176],
        '42' => [0,0,0,0,114,134,154,174],
        '36' => [0,0,0,0,112,132,152,172],
        '30' => [0,0,0,0,110,130,150,170],
        '27' => [0,0,0,0,108,128,148,168],
        '24' => [0,0,0,0,106,126,146,166],
        '21' => [0,0,0,0,0,0,144,164],
        '18' => [0,0,0,0,0,0,142,162],
        '15' => [0,0,0,0,0,0,0,160],
        '12' => [0,0,0,0,0,0,0,158],
    ];

    foreach($arr as $key => $arr2) {
        if (intval($key) <= $value) {
            $data = [];
            if ($arr2[0] > 0) {
                $data[] = [
                    'rank'  => 'МС',
                    '%'     => $arr2[0],
                ];
            }
            if ($arr2[1] > 0) {
                $data[] = [
                    'rank'  => 'КМС',
                    '%'     => $arr2[1],
                ];
            }
            if ($arr2[2] > 0) {
                $data[] = [
                    'rank'  => 'I',
                    '%'     => $arr2[2],
                ];
            }
            if ($arr2[3] > 0) {
                $data[] = [
                    'rank'  => 'II',
                    '%'     => $arr2[3],
                ];
            }
            if ($arr2[4] > 0) {
                $data[] = [
                    'rank'  => 'III',
                    '%'     => $arr2[4],
                ];
            }
            if ($arr2[5] > 0) {
                $data[] = [
                    'rank'  => 'Iю',
                    '%'     => $arr2[5],
                ];
            }
            if ($arr2[6] > 0) {
                $data[] = [
                    'rank'  => 'IIю',
                    '%'     => $arr2[6],
                ];
            }
            if ($arr2[7] > 0) {
                $data[] = [
                    'rank'  => 'IIIю',
                    '%'     => $arr2[7],
                ];
            }
            return $data;
        }
    }
}

echo '<style type="text/css">
<!--
body {		margin-left:10;margin-top:10;}
A:hover {color:#FF0000;}
H1  {font-family: Arial, Helvetica, sans-serif;font-size: 12pt;font-weight: bold;color: #333366;text-align: center;}
H2  {font-family: Arial, Helvetica, sans-serif;font-size: 12pt;font-weight: bold;color: #FF0000;text-align: left;  }
p, .text  {font-family: Arial, Helvetica, sans-serif;font-size: 9pt;font-weight: regular;color: #000000;text-align: justify;}
-->
</style>';

$totals = [];
$groups = $db->select("SELECT * FROM `groups`");
foreach ($groups as $group) {
    $teamResults = [];
    echo '<h2>' . $group->name . ', ? КП</h2>';
    echo '<pre>
<u><b>№п/п Фамилия, имя              Коллектив             Квал Номер ГР   КП Результат Место  %  Вып</b></u>';
    $sql = "SELECT
        `c`.`id`,
        `c`.`lastname`,
        `c`.`firstname`,
        `t`.`name` `team_name`,
        `c`.`number`,
        `c`.`rank`,
        `c`.`year`,
        `c`.`result_status`,
        `c`.`result_points`,
        `c`.`result_time`,
        `c`.`result_place`
    FROM
        `competitors` `c`
    LEFT JOIN
        `teams` `t` ON `c`.`team_id` = `t`.`id`
    WHERE
        `c`.`group_id` = '" . $group->id . "'
    ORDER BY
        COALESCE(`c`.`result_place`,999) ASC";
        if ($groupResults = $db->select($sql)) {
            $i = 0;
            $group_rank = 0;
            $lider = [];
            $exe = !in_array($group->name, ['OPEN']);
            foreach ($groupResults as $groupResult) {
                $i++;
                if ($exe && $i < 11)
                    $group_rank += scoreRank($groupResult->rank);
            }
            $exeTableCurrent = null;
            if ($exe) {
                $exeTable = exeTable($group_rank);
                $exeTableCurrent = $exeTable[0];
            }
            $i = 0;
            foreach ($groupResults as $groupResult) {
                $i++;
                if ($i == 1)
                    $lider = [
                        'time' => $groupResult->result_time,
                        'points' => $groupResult->result_points,
                    ];
                $proc = $lider['time'] ? ceil(100*$groupResult->result_time/$lider['time']) : 0;
                if ($exe && $exeTableCurrent && $exeTableCurrent['%'] < $proc) {
                    $exeTableCurrent = null;
                    foreach($exeTable as $exeTable_value) {
                        if (is_null($exeTableCurrent) && $exeTable_value['%'] >= $proc)
                            $exeTableCurrent = $exeTable_value;
                    }
                }
                if ($exe) {
                    if (!isset($teamResults[$groupResult->team_name])) {
                        $teamResults[$groupResult->team_name] = [
                            'name'          => teamReplace($groupResult->team_name),
                            'competitors'   => [],
                            'result'        => [
                                'points'    => 0,
                                'time'      => 0,
                            ],
                        ];
                    }
                    if (count($teamResults[$groupResult->team_name]['competitors']) < 2) {
                        $teamResults[$groupResult->team_name]['result']['time'] += $groupResult->result_time;
                        $teamResults[$groupResult->team_name]['result']['points'] += $groupResult->result_points;
                    }
                    $teamResults[$groupResult->team_name]['competitors'][] = $groupResult;
                }
                echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25, " ") . ' ' . mb_str_pad(teamReplace($groupResult->team_name), 21, " ") . ' ' . mb_str_pad(textRank($groupResult->rank), 4, " ") . ' ' . mb_str_pad($groupResult->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->year, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($groupResult->result_time), 8, " ") . ' ' . mb_str_pad($groupResult->result_place, 6, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(($exe && $lider['points'] == $groupResult->result_points ? $proc : ''), 3, " ") . ' ' . ($exeTableCurrent ? $exeTableCurrent['rank'] : '');
            }
        }
    if ($exe) {
        echo '
Квалификационный уровень – ' . $group_rank . ' ' . RusEnding($group_rank, "балл", "балла", "баллов");
        foreach($exeTable as $value) {
            echo '
' . mb_str_pad($value['rank'], 4, " ") . '   - ' . $value['%'] . '%  -  ' . sectime(floor($lider['time'] * $value['%'] / 100 ));
        }
    }
    echo '</pre>';
    if (count($teamResults)) {
        $i = 0;
        $team_time = [];
        $team_points = [];
        // Получение списка столбцов
        foreach ($teamResults as $key => $row) {
            $team_time[$key]  = $row['result']['time'];
            $team_points[$key] = $row['result']['points'];
        }

        // Сортируем данные по volume по убыванию и по edition по возрастанию
        // Добавляем $data в качестве последнего параметра, для сортировки по общему ключу
        array_multisort($team_points, SORT_DESC, $team_time, SORT_ASC, $teamResults);

        echo '<pre>
<b>                          Участник                                      Коллектив</b>
<u><b>№п/п Коллектив            Номер Фамилия, имя               КП Результат КП Результат Место</b></u>';
        foreach($teamResults as $teamResult) {
            $i++;
            echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($teamResult['name'], 21, " ") . ' ' . mb_str_pad($teamResult['competitors'][0]->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($teamResult['competitors'][0]->lastname . ' ' . $teamResult['competitors'][0]->firstname, 25, " ") . ' ' . mb_str_pad($teamResult['competitors'][0]->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($teamResult['competitors'][0]->result_time), 8, " ") . ' ' . mb_str_pad($teamResult['result']['points'], 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($teamResult['result']['time']), 8, " ");
            if (count($teamResult['competitors']) > 1) {
                for($k = 1; $k < count($teamResult['competitors']); $k++) {
                    if ($k != 1)
                        echo '<span style="color: grey;">';
                    echo '
                           ' . mb_str_pad($teamResult['competitors'][$k]->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($teamResult['competitors'][$k]->lastname . ' ' . $teamResult['competitors'][$k]->firstname, 25, " ") . ' ' . mb_str_pad($teamResult['competitors'][$k]->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($teamResult['competitors'][$k]->result_time), 8, " ");
                
                    if ($k != 1)
                        echo '</span>';
                }
            }
        }
        echo '</pre>';
    }

/*
    if ($eventGroup->name == 'М21') {
        echo '<h2>М19, ? КП</h2>';
        echo '<pre>
<u><b>№п/п Фамилия, имя              Коллектив             Квал Номер ГР   КП Результат Место  %  Вып</b></u>';
        $i = 0;
        foreach ($groupResults as $groupResult) {
            if ($groupResult->year > 2002) {
                $i++;
                echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25, " ") . ' ' . mb_str_pad(teamReplace($groupResult->team_name), 21, " ") . ' ' . mb_str_pad(textRank($groupResult->rank), 4, " ") . ' ' . mb_str_pad($groupResult->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->year, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($groupResult->result_time), 8, " ") . ' ' . mb_str_pad($i, 6, " ", STR_PAD_LEFT) . ' ';
            }
        }
        echo '<h2>М40, ? КП</h2>';
        echo '<pre>
<u><b>№п/п Фамилия, имя              Коллектив             Квал Номер ГР   КП Результат Место  %  Вып</b></u>';
        $i = 0;
        foreach ($groupResults as $groupResult) {
            if ($groupResult->year < 1983) {
                $i++;
                echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25, " ") . ' ' . mb_str_pad(teamReplace($groupResult->team_name), 21, " ") . ' ' . mb_str_pad(textRank($groupResult->rank), 4, " ") . ' ' . mb_str_pad($groupResult->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->year, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($groupResult->result_time), 8, " ") . ' ' . mb_str_pad($i, 6, " ", STR_PAD_LEFT) . ' ';
            }
        }
        echo '<h2>М16, ? КП</h2>';
        echo '<pre>
<u><b>№п/п Фамилия, имя              Коллектив             Квал Номер ГР   КП Результат Место  %  Вып</b></u>';
        $i = 0;
        foreach ($groupResults as $groupResult) {
            if ($groupResult->year > 2005) {
                $i++;
                echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25, " ") . ' ' . mb_str_pad(teamReplace($groupResult->team_name), 21, " ") . ' ' . mb_str_pad(textRank($groupResult->rank), 4, " ") . ' ' . mb_str_pad($groupResult->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->year, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($groupResult->result_time), 8, " ") . ' ' . mb_str_pad($i, 6, " ", STR_PAD_LEFT) . ' ';
            }
        }
    }

    if ($eventGroup->name == 'Ж21') {
        echo '<h2>Ж19, ? КП</h2>';
        echo '<pre>
<u><b>№п/п Фамилия, имя              Коллектив             Квал Номер ГР   КП Результат Место  %  Вып</b></u>';
        $i = 0;
        foreach ($groupResults as $groupResult) {
            if ($groupResult->year > 2002) {
                $i++;
                echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25, " ") . ' ' . mb_str_pad(teamReplace($groupResult->team_name), 21, " ") . ' ' . mb_str_pad(textRank($groupResult->rank), 4, " ") . ' ' . mb_str_pad($groupResult->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->year, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($groupResult->result_time), 8, " ") . ' ' . mb_str_pad($i, 6, " ", STR_PAD_LEFT) . ' ';
            }
        }
        echo '<h2>Ж35, ? КП</h2>';
        echo '<pre>
<u><b>№п/п Фамилия, имя              Коллектив             Квал Номер ГР   КП Результат Место  %  Вып</b></u>';
        $i = 0;
        foreach ($groupResults as $groupResult) {
            if ($groupResult->year < 1988) {
                $i++;
                echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25, " ") . ' ' . mb_str_pad(teamReplace($groupResult->team_name), 21, " ") . ' ' . mb_str_pad(textRank($groupResult->rank), 4, " ") . ' ' . mb_str_pad($groupResult->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->year, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($groupResult->result_time), 8, " ") . ' ' . mb_str_pad($i, 6, " ", STR_PAD_LEFT) . ' ';
            }
        }
        echo '<h2>Ж16, ? КП</h2>';
        echo '<pre>
<u><b>№п/п Фамилия, имя              Коллектив             Квал Номер ГР   КП Результат Место  %  Вып</b></u>';
        $i = 0;
        foreach ($groupResults as $groupResult) {
            if ($groupResult->year > 2005) {
                $i++;
                echo '
' . mb_str_pad($i, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->lastname . ' ' . $groupResult->firstname, 25, " ") . ' ' . mb_str_pad(teamReplace($groupResult->team_name), 21, " ") . ' ' . mb_str_pad(textRank($groupResult->rank), 4, " ") . ' ' . mb_str_pad($groupResult->number, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->year, 4, " ", STR_PAD_LEFT) . ' ' . mb_str_pad($groupResult->result_points, 3, " ", STR_PAD_LEFT) . ' ' . mb_str_pad(sectime($groupResult->result_time), 8, " ") . ' ' . mb_str_pad($i, 6, " ", STR_PAD_LEFT) . ' ';
            }
        }
    }
*/
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AdminLTE 3 | Timeline</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
  <!-- AdminLTE css -->
  <link rel="stylesheet" href="../../dist/css/adminlte.min.css">

  <style>
@media (min-width: 768px) {
  body:not(.sidebar-mini-md):not(.sidebar-mini-xs):not(.layout-top-nav) .content-wrapper, body:not(.sidebar-mini-md):not(.sidebar-mini-xs):not(.layout-top-nav) .main-footer, body:not(.sidebar-mini-md):not(.sidebar-mini-xs):not(.layout-top-nav) .main-header {
      margin-left: 0px;
  }
}
</style>
</head>
<body class="hold-transition sidebar-mini">
<div class="content-wrapper">
  <!-- Таб-шапка - начало -->
  <ul class="nav nav-tabs" id="custom-content-below-tab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="section-log-tab" data-toggle="pill" href="#section-log" role="tab" aria-controls="section-log" aria-selected="true">Лог</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="section-competitors-tab" data-toggle="pill" href="#section-competitors" role="tab" aria-controls="section-competitors" aria-selected="false">Участники</a>
    </li>
  </ul>
  <!-- Таб-шапка - конец -->
  <!-- Таб-контент - начало -->
  <div class="tab-content" id="custom-content-below-tabContent">
    <div class="tab-pane fade show active" id="section-log" role="tabpanel" aria-labelledby="section-log-tab">
      <!-- Лог обращений - начало -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="wrapper">
            <div class="row mb-2">
              <div class="col-sm-6">
                <h1>Лог обращений</h1>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-12">
              <div class="timeline">

<?php
// База данных
require_once('database.php');
$db = new DataBase;

$date_old = null;

$sql = "SELECT
  `datetime_created`,
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
    $date_new = date('d.m.Y', strtotime($log->datetime_created));
?>
<?php if ($date_old != $date_new) { ?>
                <div class="time-label">
                  <span class="bg-red"><?= $date_new ?></span>
                </div>
<?php } ?>
                <div>
                  <i class="fas fa-envelope bg-blue"></i>
                  <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock"></i> <?= date('H:i:s', strtotime($log->datetime_created)) ?></span>
                    <h3 class="timeline-header">FjwW</h3>
                    <div class="timeline-body">
                      <div class="col-12">
                        <h5>Запрос</h5>
                      </div>
                      <div class="col-12">
                        <code><?= $log->request_body ?></code>
                        <pre><?= json_encode(json_decode($log->request_body), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?></pre>
                      </div>
                      <div class="col-12 mt-2">
                        <h5>Ответ</h5>
                      </div>
                      <div class="col-12">
                        <code><?= ( $log->response_body ? $log->response_body : '-' ) ?></code>
                        <pre><?= json_encode(json_decode($log->response_body ? $log->response_body : '[]'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?></pre>
                        <div>
                          <strong>URL:</strong> <?= ( $log->response_body ? $log->response_url : '-' ) ?>
                        </div>
                        <div>
                          <strong>Код ответа:</strong> <?= ( $log->response_body ? $log->response_code : '-' ) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
<?php
    $date_old = $date_new;
  }
}
?>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Лог обращений - конец -->
    </div>
    <div class="tab-pane fade" id="section-competitors" role="tabpanel" aria-labelledby="section-competitors-tab">
      <table id="table_competitors" class="table table-bordered table-hover table-sm">
        <thead>
          <tr>
            <th>Номер</th>
            <th>Группа</th>
            <th>Фамилия Имя</th>
            <th>Команда</th>
          </tr>
        </thead>
        <tbody>
<?php
$sql = "SELECT
  `c`.`id`,
  `c`.`lastname`,
  `c`.`firstname`,
  `c`.`number`,
  `t`.`name` `team_name`,
  `g`.`name` `group_name`
FROM
  `competitors` `c`
LEFT JOIN
  `teams` `t` ON `c`.`team_id` = `t`.`id`
LEFT JOIN
  `groups` `g` ON `c`.`group_id` = `g`.`id`
ORDER BY
  `c`.`number` ASC";
// Результаты в группе
if ($groupResults = $db->select($sql)) {
  foreach ($groupResults as $groupResult) {
?>
          <tr>
            <td><?= $groupResult->number ?></td>
            <td><?= $groupResult->group_name ?></td>
            <td><?= $groupResult->lastname . ' ' . $groupResult->firstname ?></td>
            <td><?= $groupResult->team_name ?></td>
          </tr>
<?php
  }
}
?>
        </tbody>
        <tfoot>
          <tr>
            <th>Номер</th>
            <th>Группа</th>
            <th>Фамилия Имя</th>
            <th>Команда</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <!-- Таб-контент - конец -->
</div>
<footer class="main-footer">
  <div class="float-right d-none d-sm-block">
    <b>Версия</b> 1.0.0
  </div>
  <strong>Copyright &copy; 2022 Разработка ИП Марченко А.А.
</footer>

<!-- jQuery -->
<script src="../../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../../dist/js/adminlte.min.js"></script>
</body>
</html>
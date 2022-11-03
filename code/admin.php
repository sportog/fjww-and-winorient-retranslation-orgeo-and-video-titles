<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SportOG Управление трансляцией</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
  <!-- AdminLTE css -->
  <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="../../plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">

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
    <li class="nav-item">
      <a class="nav-link" id="section-stream-tab" data-toggle="pill" href="#section-stream" role="tab" aria-controls="section-stream" aria-selected="false">Трансляция</a>
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
              <div class="timeline" date-area="logList">

<?php
// База данных
require_once('database.php');
$db = new DataBase;
?>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Лог обращений - конец -->
    </div>
    <div class="tab-pane fade" id="section-competitors" role="tabpanel" aria-labelledby="section-competitors-tab">
    <input id="fileupload" type="file" name="fileupload" /> 
    <button id="upload-button" onclick="uploadFile()">Загрузить WinOrient CSV Online version</button>
    <button type="submit" class="btn btn-danger btn-sm" data-option="dataTruncate">Очистить данные</button>

    <!-- Ajax JavaScript File Upload Logic -->
    <script>
    async function uploadFile() {
      if (typeof fileupload.files[0] === 'undefined')
        alert('Выберите файл');
      else {
        let formData = new FormData(); 
        formData.append("file", fileupload.files[0]);
        await fetch('/upload.php', {
          method: "POST", 
          body: formData,
        })
          .then((response) => response.json())
          .then((response) => alert('Загружено: групп ' + (response.groups?.length ?? 0) + ', команд ' + (response.teams?.length ?? 0) + ', участников ' + (response.competitors?.length ?? 0)));
        $('#fileupload').val(null);
      }
    }
    </script>
      <table id="table_competitors" class="table table-bordered table-hover table-sm">
        <thead>
          <tr>
            <th>Номер</th>
            <th>Группа</th>
            <th>Фамилия Имя</th>
            <th>Команда</th>
            <th>Старт</th>
          </tr>
        </thead>
        <tbody data-area="competitorList"></tbody>
        <tfoot>
          <tr>
            <th>Номер</th>
            <th>Группа</th>
            <th>Фамилия Имя</th>
            <th>Команда</th>
            <th>Старт</th>
          </tr>
        </tfoot>
      </table>
      <div class="input-group">
        <input type="text"placeholder="Оргео ссылка" class="form-control" data-area="orgeoInput">
        <span class="input-group-append">
          <button type="button" class="btn btn-primary" data-option="orgeoSync">Синхронизировать участников</button>
        </span>
      </div>
    </div>
    <div class="tab-pane fade" id="section-stream" role="tabpanel" aria-labelledby="section-stream-tab">

      <div>
      <div class="row">
        <div class="col-9">
          <div class="card m-2">
            <div class="card-header">
              <h3 class="card-title">Настройки трансляции</h3>
            </div>
            <div class="card-body table-responsive pad">
              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                      <input type="checkbox" class="custom-control-input" id="customSwitchClock" data-option="clockStatus">
                      <label class="custom-control-label" for="customSwitchClock">Вывод времени в трансляции</label>
                    </div>
                  </div>
                </div>
                <div class="col-2">
                  <div class="input-group date" id="timepicker" data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input form-control-sm" data-target="#timepicker"  data-area="clockInput"/>
                    <div class="input-group-append" data-target="#timepicker" data-toggle="datetimepicker">
                      <div class="input-group-text"><i class="far fa-clock"></i></div>
                    </div>
                  </div>
                </div>
                <div class="col-4">
                  <button type="submit" class="btn btn-primary btn-sm" data-option="clockSync">Установить</button> <small data-area="clockSync"></small>
                </div>
              </div>
              <p class="mt-3 mb-1">Режим трансляции</p>
              <div class="form-group">
                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                  <input type="radio" name="mode" value="" class="custom-control-input" id="customSwitchNull" data-option="modeStatus" checked>
                  <label class="custom-control-label" for="customSwitchNull">Ничего не выводить</label>
                </div>
              </div>
              <div class="form-group">
                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                  <input type="radio" name="mode" value="start" class="custom-control-input" id="customSwitchStart" data-option="modeStatus">
                  <label class="custom-control-label" for="customSwitchStart">Вывод стартующих</label>
                </div>
              </div>
              <div class="form-group">
                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                  <input type="radio" name="mode" value="finish" class="custom-control-input" id="customSwitchFinish" data-option="modeStatus">
                  <label class="custom-control-label" for="customSwitchFinish">Вывод финиширующих</label>
                </div>
              </div>
              <div class="form-group">
                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                  <input type="radio" name="mode" value="finishFirst" class="custom-control-input" id="customSwitchFinishFirst" data-option="modeStatus">
                  <label class="custom-control-label" for="customSwitchFinishFirst">Вывод финиширующих, как только прибежит первый</label>
                </div>
              </div>
              <p class="mt-3 mb-1">Вывод списка из группы (после завершения вывода будет активирован режим "Ничего не выводить")</p>
              <div class="row" data-area="groupList"></div>
              <div class="row">
                <div class="col-6">
                  <p class="mt-3 mb-1">Тип соревнований</p>
                  <select class="form-control form-control-sm" data-option="stageType">
                    <option value="orient">Спортивное ориентирование</option>
                    <option value="ardf">Спортивная радиопеленгация</option>
                  </select>
                </div>
                <div class="col-6">
                  <p class="mt-3 mb-1">Часовой пояс соревнований</p>
                  <select class="form-control form-control-sm" data-option="stageGMT">
                    <option value="0">GMT</option>
                    <option value="1">GMT+1</option>
                    <option value="2">GMT+2</option>
                    <option value="3">GMT+3</option>
                    <option value="4">GMT+4</option>
                    <option value="5">GMT+5</option>
                    <option value="6">GMT+6</option>
                    <option value="7">GMT+7</option>
                    <option value="8">GMT+8</option>
                    <option value="9">GMT+9</option>
                    <option value="10">GMT+10</option>
                  </select>
                </div>
              </div>
              <p class="mt-3 mb-1">&nbsp;</p>
              <button type="submit" class="btn btn-primary" data-option="syncData">Синхронизировать данные соревнований (участники, группы, команды)</button>
            </div>
          </div>
        </div>
        <div class="col-3">
          <div class="card mr-2 my-2">
            <div class="card-header">
              <h3 class="card-title">Сообщения трансляции</h3>
            </div>
            <div class="card-body table-responsive pad" style="max-height: 77vh;">
              <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control" data-area="messageInput" />
                <span class="input-group-append">
                  <button type="button" class="btn btn-info btn-flat" data-option="messageSend">Отправить</button>
                  <button type="button" class="btn btn-danger btn-flat d-none" data-option="messageClear">Отменить</button>
                </span>
              </div>
              <div class="timeline" data-area="messageList"></div>
            </div>
          </div>
        </div>
      </div>
      </div>
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

<div class="modal fade" id="modal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="overlay d-none">
        <i class="fas fa-2x fa-sync fa-spin"></i>
      </div>
      <div class="modal-header">
        <h4 class="modal-title"></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>

<script src="../../plugins/jquery/jquery.min.js"></script>
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../plugins/moment/moment.min.js"></script>
<script src="../../dist/js/adminlte.min.js"></script>
<script src="../../plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="js/socket.io.js"></script>

<script>
  let socket = io('http://localhost:3000');
  let competitors, groups, teams, logs, html, logFirstDate;
  // Сокеты
  socket.on('log', function (json) {
    logRefresh(JSON.parse(json));
  });
  socket.on('data', function (json) {
    dataSet(JSON.parse(json));
  });
  socket.on('stage', function (json) {
    if (json) {
      let data = JSON.parse(json);
      $('[data-option="stageType"] > option[value="' + data.type + '"]')[0].selected = true;
      $('[data-option="stageGMT"] > option[value="' + data.gmt + '"]')[0].selected = true;
    }
  });
  socket.on('message', function (msg) {
    if (msg) {
      let messageSend = $('[data-option="messageSend"]');
      let messageClear = $('[data-option="messageClear"]');
      let messageInput = $('[data-area="messageInput"]');
      let messageList = $('[data-area="messageList"]');
      let messageListFirst = $('[data-area="messageList"] > div:first-child > i');
      let messageTemplate = '<div class="mx-0"><i class="fas fa-bullhorn bg-success"></i><div class="timeline-item p-1 mr-0">#message#</div></div>';
      let message = msg;
      messageSend.toggleClass('d-none', true);
      messageClear.toggleClass('d-none', !true);
      messageInput.val(message).prop('disabled', true);
      if (message)
        messageList.prepend(messageTemplate.replace('#message#', message));
      }
  });
  socket.on('clock', function (is_visible) {
    $('[data-option="clockStatus"]')[0].checked = (is_visible === 'true');
  });
  socket.on('timestamp', function (value) {
    $('[data-area="clockSync"]').html('Разница времени <strong>' + value + '</strong> секунд!');
  });
  socket.on('start', function (is_visible) {
    $('[data-option="modeStatus"][value="start"]')[0].checked = (is_visible === 'true');
  });
  socket.on('finish', function (is_visible) {
    if (is_visible === 'first')
      $('[data-option="modeStatus"][value="finishFirst"]')[0].checked = true;
    else
      $('[data-option="modeStatus"][value="finish"]')[0].checked = (is_visible === 'true');
  });

  // Часы, вывод в трансляции
  function clockVisible(value) {
    socket.emit('clock', value ? 'true' : 'false');
  }
  // Часы, установка времени
  function clockSync() {
    let clockInput = $('[data-area="clockInput"]');
    let clock = clockInput.val();
    if (!clock || !moment(clock, "hh:mm:ss").isValid())
      alert("Укажите время, либо оно некорректное!");
    else {
      let diff_timestamp = moment(clock, "hh:mm:ss").unix() - moment().unix();
      socket.emit('timestamp', diff_timestamp);
      $('[data-area="clockSync"]').html('Разница времени <strong>' + diff_timestamp + '</strong> секунд!');
      clockInput.val('');
    }
  }
  // Выбор режима
  function modeSet(value) {
    switch (value) {
      case 'start':
      case 'finish':
        socket.emit(value, 'true');
        break;
      case 'finishFirst':
        socket.emit('finish', 'first');
        break;
      default:
        socket.emit('start', 'false');
        socket.emit('finish', 'false');
        break;
    }
  }
  // Сообщения
  function messageOperation(value) {
    let messageSend = $('[data-option="messageSend"]');
    let messageClear = $('[data-option="messageClear"]');
    let messageInput = $('[data-area="messageInput"]');
    let messageList = $('[data-area="messageList"]');
    let messageListFirst = $('[data-area="messageList"] > div:first-child > i');
    let messageTemplate = '<div class="mx-0"><i class="fas fa-bullhorn bg-success"></i><div class="timeline-item p-1 mr-0">#message#</div></div>';
    let message = value ? messageInput.val() : '';
    messageSend.toggleClass('d-none', value);
    messageClear.toggleClass('d-none', !value);
    messageInput.val(message).prop('disabled', value);
    socket.emit('message', message);
    if (message)
      messageList.prepend(messageTemplate.replace('#message#', message));
    else
      messageListFirst.toggleClass('bg-success', false).toggleClass('bg-grey', true);
  }
  // Вывод списка из группы
  function listVisible(value) {
    $('[data-option="modeStatus"][value=""]').trigger('click');
    setTimeout(() => {socket.emit('list', value);}, 100);
  }
  // Синхронизация данных
  function syncData() {
    socket.emit('data', JSON.stringify({competitors: competitors, groups: groups, teams: teams}));
  }
  // Отправка данных по настройкам этапа
  function stageSet() {
    let type = $('[data-option="stageType"]').val();
    let gmt = $('[data-option="stageGMT"]').val();
    socket.emit('stage', JSON.stringify({type: type, gmt: gmt}));
  }
  // Запрос синхронизации данных
  function dataSync() {
    $.getJSON( "/api.php?logs=true", (data) => {
      logs = data.logs;
      logsSetup();
      dataSet(data)
    });
    setTimeout(() => {socket.emit('operation', 'sync');}, 100);
  }
  let template = {
    log_date: '<div class="time-label">\
      <span class="bg-red">#date#</span>\
    </div>',
    log_noanswer: '<div data-id="#id#">\
      <i class="fas fa-info bg-blue"></i>\
        <div class="timeline-item">\
          <span class="time"><i class="fas fa-clock"></i> #time#</span>\
          <h3 class="timeline-header">#source#</h3>\
          <div class="timeline-body">\
            <div class="col-12">\
              <h5>Запрос <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#modal" data-title="Форматированный запрос" data-body=\'#request.body#\'>Формат</button></h5>\
            </div>\
            <div class="col-12">\
              <code>#request.body#</code>\
            </div>\
          </div>\
        </div>\
      </div>',
    log_answer: '<div data-id="#id#">\
      <i class="fas fa-info bg-blue"></i>\
        <div class="timeline-item">\
          <span class="time"><i class="fas fa-clock"></i> #time#</span>\
          <h3 class="timeline-header">#source#</h3>\
          <div class="timeline-body">\
            <div class="col-12">\
              <h5>Запрос <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#modal" data-title="Форматированный запрос" data-body=\'#request.body#\'>Формат</button></h5>\
            </div>\
            <div class="col-12">\
              <code>#request.body#</code>\
            </div>\
            <div class="col-12 mt-2">\
              <h5>Ответ <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#modal" data-title="Форматированный ответ" data-body=\'#response.body#\'>Формат</button></h5>\
            </div>\
            <div class="col-12">\
              <code>#response.body#</code>\
              <div>\
                <strong>URL:</strong> <small>#response.url#</small>\
              </div>\
              <div>\
                <strong>Код ответа:</strong> #response.code#\
              </div>\
            </div>\
          </div>\
        </div>\
      </div>',
    group_list: '<div class="col-auto"><button type="button" class="btn btn-block btn-outline-#color# btn-sm mb-1" value="#id#" data-option="listVisible">#name#</button></div>',
    competitor: '<tr data-id="#id#">\
        <td>#number#</td>\
        <td>#group.name#</td>\
        <td>#fullname#</td>\
        <td>#team.name#</td>\
        <td>#start_time#</td>\
      </tr>',
  };
  // Установка данных
  function dataSet(data) {
    competitors = data.competitors;
    groups = data.groups;
    teams = data.teams;
    groupListView();
    competitorListView();
  }
  function groupListView() {
    html = '';
    groups.forEach((group) => {
      let color = 'secondary';
      if (group.name.substr(0, 1) == 'М' || group.name.substr(0, 5) == 'Юноши' || group.name.substr(0, 6) == 'Юниоры')
        color = 'primary';
      else if (group.name.substr(0, 1) == 'Ж' || group.name.substr(0, 1) == 'Д' || group.name.substr(0, 7) == 'Юниорки')
        color = 'danger';
      html += template['group_list'].replace('#id#', group.id).replace('#name#', group.name).replace('#color#', color);
    });
    $('[data-area="groupList"]').html(html);
  }
  function competitorListView() {
    html = '';
    competitors.forEach((competitor) => {
      html += template['competitor'].replace('#id#', competitor.id).replace('#number#', competitor.number).replace('#fullname#', competitor.fullname).replace('#start_time#', moment(competitor.stage.timestamp_start * 1000).format('HH:mm:ss')).replace('#group.name#', getGroupName(competitor.group_id)).replace('#team.name#', getTeamName(competitor.team_id));
    });
    $('[data-area="competitorList"]').html(html);
  }
  // Формирование списка логгирования
  function logsSetup() {
    let _logs = logs.sort((a, b) => a.id > b.id ? -1 : 1);
    // Формируем html
    html = '';
    _logs.forEach(log => {
      if (logFirstDate != logDate(log)) {
        logFirstDate = logDate(log);
        html += template['log_date'].replace('#date#', logFirstDate);
      }
      html += logHtml(log);
    });
    // Добавляем сформированный html
    $('[date-area="logList"]').html(html);
  }
  // Обновление списка логгирования
  function logRefresh(json) {
    let log = logs.find(log => log.id === json.id);
    if (!log)
      logs.push(json);
    log = json;
    // Проверяем смену даты
    if (logFirstDate == logDate(log))
      $('[date-area="logList"] > div.time-label:first-child').remove();
    logFirstDate = logDate(log);
    // Формируем html
    html = template['log_date'].replace('#date#', logFirstDate);
    html += logHtml(log);
    // Удаляем запись логгирования, если такая раньше была
    $('[date-area="logList"] > div[data-id="' + log.id + '"]').remove();
    // Добавляем сформированный html
    $('[date-area="logList"]').prepend(html);
  }
  // Генерация строки логгирования
  function logHtml(log) {
    return template[log.response_code ? 'log_answer' : 'log_noanswer'].replace('#id#', log.id).replace('#time#', moment(log.created_at * 1000).format('HH:mm:ss')).replace('#source#', log.source).replaceAll('#request.body#', log.request_body).replaceAll('#response.body#', log.response_body).replace('#response.url#', log.response_url).replace('#response.code#', log.response_code);
  }
  // Получение даты логгирования
  function logDate(log) {
    return moment(log.created_at * 1000).format('D.MM.Y');
  }
  // Получение названия команды
  function getTeamName(id) {
    let team_name = '';
    let team = teams.find(team => team.id == id)
    if (team)
      team_name = team.name;
    return team_name;
  }
  // Получение названия группы
  function getGroupName(id) {
    let group_name = '';
    let group = groups.find(group => group.id == id)
    if (group)
      group_name = group.name;
    return group_name;
  }
  function dataTruncate() {
    if(confirm('Вы точно хотите удалить всех участников, группы и команды?')) {
      $.getJSON( "/api.php?mode=truncate");
    }
  }
  function orgeoSync() {
    let url = $('[data-area="orgeoInput"]').val();
    if (url.length === 0)
      alert ('Укажите ссылку!');
    else if (confirm('Вы точно хотите отправить список участников (фио, группа, команда) в orgeo?')) {
      $.post( "/api.php?mode=orgeo", { url: url }, (data) => {
        alert('Синхронизация завершена! В логе отображён результат');
      });
    }
  }
  $(function () {
    dataSync();
    $('#timepicker').datetimepicker({
      format: 'HH:mm:ss'
    });
    $(document).on('input', '[data-option="clockStatus"]', function() {
      clockVisible(this.checked);
    });
    $(document).on('click', '[data-option="clockSync"]', function() {
      clockSync();
    });
    $(document).on('input', '[data-option="modeStatus"]', function() {
      modeSet(this.value);
    });
    $(document).on('click', '[data-option="messageSend"]', function() {
      messageOperation(true);
    });
    $(document).on('click', '[data-option="messageClear"]', function() {
      messageOperation(false);
    });
    $(document).on('click', '[data-option="listVisible"]', function() {
      listVisible(this.value);
    });
    $(document).on('change', '[data-option="stageType"], [data-option="stageGMT"]', function() {
      stageSet();
    });
    $(document).on('click', '[data-option="syncData"]', function() {
      syncData();
    });
    $(document).on('click', '[data-option="dataTruncate"]', function() {
      dataTruncate();
    });
    $(document).on('click', '[data-option="orgeoSync"]', function() {
      orgeoSync();
    });
    $('#modal').on('show.bs.modal', function (event) {
      let button = $(event.relatedTarget);
      let title = button.attr('data-title');
      let body = button.attr('data-body');
      var modal = $(this);
      modal.find('.modal-title').text(title);
      modal.find('.modal-body').empty();
      modal.find('.overlay').toggleClass('d-none', false);
      $.post( "/api.php?mode=formatter", { value: body }, (data) => {
        modal.find('.modal-body').html(data);
        modal.find('.overlay').toggleClass('d-none', true);
      });
    })
  })
</script>
</body>
</html>
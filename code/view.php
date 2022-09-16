<?
date_default_timezone_set('Europe/Moscow');

$dir = 'logs';
$filename = time() . '.json';

echo <<<HTML
<style>
    td {
        border-top: 1px solid black;
    }
</style>
HTML;

echo '<h1>Лог обращений к Orgeo</h1>';

echo '<table style="width: 100%"><thead><tr><th style="width: 140px;">Дата и время</th><th style="width: 40%;">Запрос</th><th style="width: 50px;">Код ответа</th><th style="width: 40%;">Содержимое ответа</th></tr><tbody>';
if (is_dir($dir)) {
    $files = scandir($dir . '/request', 1);
    $i = 0;
    foreach ($files as $file) {
        $file_ar = explode('.', $file);
        $file_request = $dir . '/request/' . $file;
        $file_response = $dir . '/response/' . $file;
        if (!is_dir($file_request) && !in_array($file , ['.gitkeep', '.gitignore'])) {
            $request = file_get_contents($file_request);
            $response = null;
            if (file_exists($file_response))
                $response = json_decode(file_get_contents($file_response));
            echo '<tr><td>' . date('d.m.Y H:i:s', $file_ar[0]) . '</td><td>' . $request . '</td><td>' . ($response ? $response->code : '-' ) . '</td><td>' . ($response ? ($response->body . '<br><small>' . $response->url . '</small>' ) : '-' ) . '</td></td>';
        }
    }
}
echo '</tbody></table>';

?>

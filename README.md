# Ретрансляция финишной отметки из программы FjwW в Orgeo и в видео-титры

Для использования потребуется установка Docker
https://docs.docker.com/desktop/install/windows-install/

Также может в процессе запуска Docker потребоваться установка на x64 системах пакета обновления ядра Linux
https://docs.microsoft.com/ru-ru/windows/wsl/install-manual#step-4---download-the-linux-kernel-update-package

Для запуска использовать файл **run.bat**

**Разделы:**
- http://localhost:8080/admin.php Лог обращений с FjwW и ответов от Orgeo, а также список участников
- http://localhost:8080/stream/index.html Страница с видео-титрами для накладывания в OBS Studio
- http://localhost:8080/stream/admin.html Страница с возможностью отправлять сообщения в трансляцию и просмотром большего списка последних финишировавших спортсменов

Для использования в FjwW необходимо вместо адреса на orgeo подставлять адрес с заменой **orgeo.ru** на **localhost:8080**, т.е. если ссылка на трансляцию в Orgeo: http://orgeo.ru/online/sv?id=22527&sk=4e388&sub=6& необходимо в программе указать http://localhost:8080/online/sv?id=22527&sk=4e388&sub=6&

Если трансляция в Orgeo не нужна, достаточно указать адрес http://localhost:8080/

P.S. После перезапуска системы обнуляется база данных (список групп, команд, участников и лог)
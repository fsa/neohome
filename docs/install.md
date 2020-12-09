# Установка системы

Для установки SHCC подойдёт любой дистрибутив Linux в котором имеются необходимые версии PHP, PostgreSQL и nginx. Кроме этого, рекомендуется использовать систему инициализации systemd, но это необязательно, нужно будет обеспечить запуск демонов при старте системы и ежеминутный запуск shell-скрипта.

Рекомендуемым вариантом установки является Ubuntu 18.04 LTS, т.к. в состав ещё репозиториев включены все необходимые компоненты. Все команды для установки системы приведены для Ubuntu.

## Установка PostgreSQL

При установке системы рекомендуется выбрать локаль с кодировкой UTF-8 (например, ru_RU.UTF-8 или en_US.UTF-8), т.к. это повлияет на инициализацию кластера БД PostgreSQL.

Для установки сервер PostgreSQL выполните:
```bash
$ sudo apt install postgresql
```
После установки рекомендуется подключиться к серверу и убедиться, что кластер получил подходящие настройки Collate и Ctype:
```
# su - postgres
$ psql
psql (10.9)
Type "help" for help.

postgres=# \l
                                  List of databases
    Name    |   Owner    | Encoding |  Collate   |   Ctype    |   Access privileges   
------------+------------+----------+------------+------------+-----------------------
 postgres   | postgres   | UTF8     | ru_RU.utf8 | ru_RU.utf8 | 
 template0  | postgres   | UTF8     | ru_RU.utf8 | ru_RU.utf8 | =c/postgres          +
            |            |          |            |            | postgres=CTc/postgres
 template1  | postgres   | UTF8     | ru_RU.utf8 | ru_RU.utf8 | =c/postgres          +
            |            |          |            |            | postgres=CTc/postgres
(3 rows)
```

## Установка веб-сервера nginx и php.

Установите веб-сервер и php с необходимыми модулями с помощью команды:
```bash
$ sudo apt install nginx php-fpm php-pgsql
```

Для настройки веб-сервера nginx создайте файл конфигурации виртуального хоста. При настройках по умолчанию это можно сделать двумя способами:
1. создать файл с содержимым конфигурации в папке /etc/nginx/sites-available/ и создать на него симлинк в папке sites-enabled;
2. создать файл с расширением .conf в папаке /etc/nginx/cond.d/.

Пример файла конфигурации приведён ниже:

```
server {
    listen	80 default_server;

    server_name  shcc.example.com;
    
    charset utf-8;
    
    access_log  /var/log/nginx/shcc_access.log;
    error_log  /var/log/nginx/shcc_error.log;

    root /var/www/shcc/webroot;

    index index.php;

    location ~ \.php$ {
	fastcgi_pass php-fpm;
        fastcgi_index index.php;
	fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ^~ /alice-webhook {
	fastcgi_pass php-fpm;
	fastcgi_split_path_info ^(\/alice-webhook)(.*)$;
	fastcgi_param SCRIPT_FILENAME $document_root/alice-webhook/index.php;
	fastcgi_param PATH_INFO $fastcgi_path_info;
	include fastcgi_params;
    }

    include acme;

    # https
    listen 443 ssl http2 default_server;
    ssl_certificate /etc/letsencrypt/live/shcc.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/shcc.example.com/privkey.pem;

    add_header Strict-Transport-Security max-age=15768000;

}
```
Вместо shcc.example.com укажите свой домен, который вы планируете использовать для доступа к умному дому из сети.

Запуск php производится с помощью upstream php-fpm, который необходимо указать в секции http основного файла конфигурации nginx /etc/nginx/nginx.conf: 
```
    upstream php-fpm {
	server unix:/run/php/php7.2-fpm.sock;
    }
```
Адрес unix-сокета должен совпадать с указанным в файле конфигурации php-fpm, например, /etc/php/7.2/fpm/pool.d/www.conf для php версии 7.2 (значение listen). Вы можете использовать и другие способы взаимодействия веб-сервера и php-fpm, например, связь через TCP/IP. В любом случае, настройки nginx и php-fpm должны указывать на один и тот же объект.

Обратите внимание на часть файла после комментария https. Эти команды нужны для использования протокола https и при начальной настройке могут вызвать ошибки при запуске сервера. Чтобы этого избежать закомментируйте директивы listen 443..., ssl_..., add_header. После получения сертификатов их можно будет раскомментировать.

Ещё один требуемый файл - acme. Если вы не планируете использовать сертификаты от Let's Encrypt можете просто закомментировать include acme; в файле конфигурации виртуального хоста.

Для удобства получения бесплатных сертификатов https создаём файл /etc/nginx/acme со следующим содержимым:
```
location ~ /.well-known/acme-challenge/(.*) {
    default_type "text/plain";
    root /var/www/letsencrypt;
    allow all;
}
```
Также необходимо будет создать папку /var/www/letsencrypt и задать для неё владельца и группу веб-сервера (www-data). Эту папку нужно будет указывать в параметрах certbot при получении сертификата для своего домена.

Для настройки https также желательно добавить в секцию http файла конфигурации nginx /etc/nginx/nginx.conf параметры ssl. Пример параметров конфигурации https приведён ниже:
```
    ssl_protocols TLSv1.3 TLSv1.2;
    ssl_prefer_server_ciphers on;
    ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256';

    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;

    ssl_stapling on;
    ssl_stapling_verify on;
```

## Получение SHCC

SHCC рекомендуется размещать в папке /var/www/shcc. Имеется два способа получения текущей версии SHCC.

### Получение через git

Перейдите в папку установки (рекомендуется /var/www) и клонируйте репозиторий shcc с github:
```bash
# cd /var/www
# git clone https://github.com/fsa/shcc
```
При этом будет создана папка shcc.

### Получение через composer

Перейдите в папку установки (рекомендуется /var/www) и запустите команду создания проекта:
```bash
# cd /var/www
# composer create-project fsa/shcc
```
При этом будет создана папка shcc.

## Подготовка shcc к запуску

Создаём settings.php используя шаблон settings.sample.php.
```bash
# cp settings.sample.php settings.php
```
Отредактируйте полученный файл указав желаемые настройки. Обратите внимание на:
1. реквизиты доступа к базе данных; нужно указать имя базы данных, пользователя и пароль;
2. секцию home, где задаётся ваше местоположение и город;
3. ваш часовой пояс.

## Создание базы данных

Воспользуйтесь командной строкой, чтобы создать базу данных и импортировать в неё необходимые таблицы. Для этого переключитесь на пользователя postgres, выберите папку со скриптами инициализации базы данных и запустите psql.
```bash
root@shcc:~# su postgres
postgres@shcc:/root$ cd /var/www/shcc/src/sql
postgres@shcc:/var/www/shcc/src/sql$ psql
```

Создайте пользователя и базу данных для него. Задайте пароль для пользователя.
```
postgres=# create user shcc;
CREATE ROLE
postgres=# create database shcc owner=shcc;
CREATE DATABASE
postgres=# \password shcc
Enter new password: 
Enter it again:
```

Когда база данных создана можно подключиться к серверу с помощью вновь созданного пользователя к базе данных и инициализировать её с помощью команд импорта:
```
postgres=# \c shcc shcc 127.0.0.1
Password for user shcc:
SSL connection (protocol: TLSv1.3, cipher: TLS_AES_256_GCM_SHA384, bits: 256, compression: off)
You are now connected to database "shcc" as user "shcc" on host "127.0.0.1" at port "5432".
shcc=> \i auth.sql
...
shcc=> \i smarthome.sql
...
shcc=> \i yandex.sql
...
```
Завершающим этапом является создание пользователя для доступа к сайту умного дома.
```sql
shcc=> insert into auth_users (login, password, name) values ('user','$2y$10$3BJwk3WNeAtIxd8Gory/TONHAFe.8GkKAM2Afjxdc1njS25a.twbi', 'User');
```
Первое поле, в данном случае - это имя пользователя, которое используется для входа в систему. Второе поле - это хеш пароля, который можно получить с помощью команды password_hash:
```php
<?php
echo password_hash('password', PASSWORD_DEFAULT);
```
Для запуска скрипта можете воспользоваться сайтом http://phptester.net/ или запустить скрипт локально.

В примере был использован логин "user" с паролем "123". В целях безопасности лучше придумать свои,

## Конфигурирование модулей

После создания всех необходимых файлов конфигурации и базы данных первая часть системы готова к работе. Вы можете открыть сайт системы в браузере и произвести первоначальную настройку демонов. Для этого перейдите в пункт "Настройки", далее, в раздел "Модули" и включите необходимые вам модули. По умолчанию все демоны модулей находятся в выключенном состоянии.

Кроме этого можно произвести настройку синтеза речи в настройках соответствующих модулей.

## Запуск демонов и ежеминутного скрипта

Для нормальной работы контроллера необходимо запускать два процесса:
1. daemonctrl.php, который запускает в фоне процессы активированных демонов модулей;
2. скрипт minutely.sh, который выполняет задачи по расписанию и должен запускаться ежеминутно.

### Создание ежеминутного скрипта

В исходных кодах в папке custom имеется пример скрипта. Скопируйте его содержимое в файл minutely.php и отредактируйте полученный файл на ваше усмотрение.
```bash
# cp minutely.sample.php minutely.php
```

## Обеспечение автоматического запуска необходимых процессов на системе с systemd

Для запуска демонов и ежеминутного скрипта имеются готовые юниты для systemd. При стандартном расположении в папке /var/www/shcc необходимости настраивать сервисы нет. Создайте символические ссылки на файлы service и timer в каталог /lib/systemd/system/.
``` bash
# cd /var/www/shcc/service/systemd
# ln -s shcc.service /lib/systemd/system/
# ln -s shcc-minutely.service /lib/systemd/system/
# ln -s shcc-minutely.timer /lib/systemd/system/
```
Если вы используете иной путь расположения shcc или используете своего пользователя для запуска скриптов, то выполните настройку:
```bash
# systemctrl edit shcc.service
# systemctrt edit shcc-minutely.service
```
В открывшемся редакторе введите следующий код для shcc.service:
```
[Service]
WorkingDirectory=/home/my_user/www/shcc/service
User=my_user
Group=my_user
```
И для shcc-minutely.service:
```
[Service]
WorkingDirectory=/home/my_user/www/shcc/custom
User=my_user
Group=my_user
```
В оба файла в качестве WorkingDirectory нужно указать путь до папки service или custom, а в качестве User и Group имя пользователя и группу, от имени которого должен работаь сервис.

Теперь вы можете активировать и запустит требуемые юниты:
```bash
# systemctl enable shcc.service
Created symlink /etc/systemd/system/multi-user.target.wants/shcc.service → /var/www/shcc/service/systemd/shcc.service.
Created symlink /etc/systemd/system/shcc.service → /var/www/shcc/service/systemd/shcc.service.
# systemctl enable shcc-minutely.timer
Created symlink /etc/systemd/system/timers.target.wants/shcc-minutely.timer → /var/www/shcc/service/systemd/shcc-minutely.timer.
Created symlink /etc/systemd/system/shcc-minutely.timer → /var/www/shcc/systemd/shcc-minutely.timer.
# systemctl start shcc.service
# systemctl start shcc-minutely.timer
```

## Включение голосовых оповещений

Для работы голосовых оповещений необходимо установить проигрыватель. По умолчанию используется mpg123. Указать другой проигрыватель возможно через файл настроек settings.php.
```bash
apt install mpg123
```
После установки убедитесь, что звук воспроизводится из командной строки выполнив команду:
```bash
mpg123 sound.mp3
```
при этом указав имя существующего звукового файла.

Поскольку демоны работают от пользователя www-data по умолчанию, то нужно добавить этого пользователя в группу audio, чтобы он имел возможность воспроизводить звук:
```
usermod www-data -aG audio
```
Если для запуска демонов вы используете другого пользователя, то убедитесь, что он состоит в данной группе.

### Создание конфигурации для синтезатора речи

Зайдите в веб-интерфейсе в раздел "Настройки", и произведите настройку необходимого модуля синтеза речи. После этого созраните конфигурацию.

### Включение звука для Raspberry Pi

При установке Ubuntu на Raspberry Pi может оказаться, что звук не воспроизводится. Для его активации необходимо включить звуковой драйвер в файле конфигурации /boot/firmware/usercfg.txt (в старых версиях системы, при его отсутствии, /boot/firmware/config.txt) добавив строку
``` 
dtparam=audio=on
```
Далее выполните перезагрузку системы и звук должен появиться.
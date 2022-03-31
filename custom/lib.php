<?php

/**
 * Не редактируйте данный файл. Он может быть изменён при обновлении системы.
 */
if (!spl_autoload_functions()) {
    require_once '../vendor/autoload.php';
    set_include_path(get_include_path() . ':' . __DIR__ . '/../webroot/classes/');
    spl_autoload_extensions('.php');
    spl_autoload_register();
    openlog("shcc@cli", LOG_PID | LOG_ODELAY, LOG_USER);
}
require_once 'functions.php';

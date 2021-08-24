<?php

require_once '../../../common.php';
Session::grantAccess([]);
$tts_file='../../../../config/tts.conf';
$action=filter_input(INPUT_POST,'action');
switch ($action) {
    case null:
    case false:
        break;
    case 'setup':
        require 'save.php';
        break;
    case 'remove':
        require 'remove.php';
        break;
    default:
        httpResponse::showError('Неверное действие');
}

$key=SmartHome\Vars::get('Yandex.TTS.Key')??'';
$speaker=SmartHome\Vars::get('Yandex.TTS.Voice')??'oksana';
$emotion=SmartHome\Vars::get('Yandex.TTS.Emotion')??'neutral';
use Templates\Forms;
httpResponse::showHtmlHeader('Яндекс');
?>
<p><a href="../">Вернуться к списку модулей</a></p>
<hr>
<form method="POST" action="./">
<?php
Forms::inputString('key', $key, 'Ключ API:');
Forms::inputSelectArray('speaker', $speaker, 'Голос', ['jane'=>'Джейн (жен.)', 'oksana'=>'Оксана (жен.)', 'alyss'=>'Элис (жен.)', 'omazh'=>'Омаж (жен.)', 'zahar'=>'Захар (муж.)', 'ermil'=>'Ермил (муж.)']);
Forms::inputSelectArray('emotion', $emotion, 'Эмоциональная окраска голоса', ['good'=>'радостный, доброжелательный', 'evil'=>'раздраженный', 'neutral'=>'нейтральный']);
Forms::submitButton('Установить синтезатор TTS', 'setup');
Forms::submitButton('Отключить синтез речи', 'remove', 'btn-danger');
?>
</form>
<?php
httpResponse::showHtmlFooter();

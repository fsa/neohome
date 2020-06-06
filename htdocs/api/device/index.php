<?php

require_once '../../common.php';
//require_once '../functions.php';
httpResponse::setModeJson();
Auth\Session::grantAccess(['control']);
$device_name=filter_input(INPUT_GET, 'name');
if(!$device_name) {
    httpResponse::error(400);
}
$request=json_decode(file_get_contents('php://input'));
if(!$request) {
    httpResponse::error(400);
}
if(file_exists($device_name.'.php')) {
    chdir('../../../custom/command/');
    try {
        $result=require_once $device_name.'.php';
        httpResponse::json($result);
    } catch (AppException $ex) {
        httpResponse::showError($ex->getMessage());
    }
}
//TODO: обработка данных устройства
try {
    $device=SmartHome\Devices::get($device_name);
    $value=$request->value;
    switch ($request->action) {
        case 'power':
            $device->setPower($value);
            break;
        case 'bright':
            $device->setBrightness($value);
            break;
        case 'ct':
            $device->setCT($value);
            break;
    }
} catch (AppException $ex) {
    httpResponse::showError($ex->getMessage());
}

httpResponse::json(json_encode($device->getState()));
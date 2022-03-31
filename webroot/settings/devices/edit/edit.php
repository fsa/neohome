<?php
use FSA\Neuron\HttpResponse;
if (!isset($action)) {
    die;
}
$device=new \SmartHome\Entity\Device;
$device->uid=filter_input(INPUT_POST,'uid');
$device->hwid=filter_input(INPUT_POST,'hwid');
$device->description=filter_input(INPUT_POST,'description');
$entity=new stdClass();
$entity->classname=filter_input(INPUT_POST,'classname');
$properties=filter_input(INPUT_POST,'properties',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
if(is_array($properties)) {
    $entity->properties=$properties;
}
$device->entity=json_encode($entity);
$devices=new \SmartHome\Devices;
$devices->setDevice($device);
switch ($action) {
    case 'insert':
        $devices->insert();
        HttpResponse::storeNotification('Устройство добавлено');
        HttpResponse::redirection('../');
        break;
    case 'update':
        $devices->update(filter_input(INPUT_POST,'old_uid'));
        HttpResponse::storeNotification('Данные об устройстве обновлены');
        HttpResponse::redirection('../');
        break;
}
HttpResponse::showError('Неизвестное действие');
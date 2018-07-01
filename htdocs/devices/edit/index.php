<?php
require_once '../../common.php';
use Templates\Forms;
$id=filter_input(INPUT_GET,'id');
$module=filter_input(INPUT_GET,'module');
if($module) {
    if(!$id) {
        die;
    }
    $sh=new SmartHome\DeviceMemoryStorage;
    $memdevices=$sh->getModuleDevices($module);
    if(!isset($memdevices[$id])) {
        throw new AppException('Что-то пошло не так. Не найдено устройство в памяти.');
    }
    $memdev=$memdevices[$id];
    $init_data_list=$memdev->getInitDataList();
    $device=new SmartHome\Entity\Device;
    $device->module_id=\SmartHome\Modules::getModuleIdByName($module);
    $device->uid=$id;
    $device->unique_name=$module.'_'.$id;
    $device->name=$memdev->getDeviceName();
    $device->classname=get_class($memdev);
    $device->setInitData($memdev->getInitDataValues());
    $device->place_id=0;
} else {
    if($id) {
        $device=SmartHome\Devices::getDeviceById($id);
        $memdev=new $device->classname;
        $init_data_list=$memdev->getInitDataList();
        if(!$device) {
            throw new AppException('Устройство не найдено в базе данных');
        }
    } else {
        $device=new SmartHome\Entity\Device;
        $init_data_list=[];
    }
}
HTML::showPageHeader("Регистрация оборудования модуля '$module'");
if($module) {
?>
<p>Для работы с новым устройством его необходимо добавить в базу данных.</p>
<?php
    Templates\SmartHome\DeviceInMemory::show($memdev);
}
?>
<form method="POST" action="register/">
<?php
Forms::inputHidden('id',$device->id);
Forms::inputSelect('module_id',$device->module_id,'Модуль:',\SmartHome\Modules::getModuleList());
Forms::inputString('uid',$device->uid,'ID устройства в модуле:');
Forms::inputString('classname',$device->classname,'Класс устройства:');
Forms::inputString('unique_name',$device->unique_name,'Уникальный ID в системе (укажите удобное для вас имя):');
Forms::inputString('name',$device->name,'Наименование:');
$values=$device->getInitData();
foreach ($init_data_list as $param=>$name) {
    Forms::inputString('init['.$param.']',$values[$param],$name);    
}
Forms::inputSelect('place_id',$device->place_id,'Расположение:',\SmartHome\Places::getPlaceList());
Forms::submitButton($device->id?'Сохранить':'Создать');
?>
</form>
<?php
HTML::showPageFooter();
<?php

/**
 * SHCC 0.7.0-dev
 * 2020-11-25
 */

namespace SmartHome;

use DB,
    PDO;

class Devices {

    private $device;

    public function __construct() {
        
    }

    public static function get($uid) {
        $s=DB::prepare('SELECT p.name AS place_name, d.* FROM devices d LEFT JOIN places p ON d.place_id=p.id WHERE d.uid=?');
        $s->execute([$uid]);
        $db_dev=$s->fetch(PDO::FETCH_OBJ);
        if (!$db_dev) {
            return null;
        }
        $mem=new Device\MemoryStorage;
        $device=$mem->getDevice($db_dev->hwid);
        if (is_null($device)) {
            $entity=json_decode($db_dev->entity);
            $device=new $entity->classname;
            $device->init($db_dev->hwid, $entity->properties);
        }
        $device->place_name=$db_dev->place_name;
        return $device;
    }

    public function create() {
        $this->device=new Entity\Device;
    }

    public function fetchDeviceByUid($uid) {
        $s=DB::prepare('SELECT * FROM devices WHERE uid=?');
        $s->execute([$uid]);
        $s->setFetchMode(PDO::FETCH_CLASS, Entity\Device::class);
        $this->device=$s->fetch();
    }

    public function fetchDeviceByHwid($hwid) {
        $s=DB::prepare('SELECT * FROM devices WHERE hwid=?');
        $s->execute([$hwid]);
        $s->setFetchMode(PDO::FETCH_CLASS, Entity\Device::class);
        $this->device=$s->fetch();
    }

    public function exists($except=false) {
        if ($except) {
            if ($this->device instanceof Entity\Device) {
                return;
            }
            throw new Exception('Отсутствует устройство');
        }
        return $this->device instanceof Entity\Device;
    }

    public function getDevice() {
        return $this->device;
    }

    public function setDevice(Entity\Device $device) {
        $this->device=$device;
    }

    public function setDeviceProperties(array $data) {
        $this->exists(true);
        foreach ($data as $param=> $value) {
            $this->device->$param=$value;
        }
    }

    public function update() {
        $this->exists(true);
        return DB::update('devices', get_object_vars($this->device), 'hwid');
    }

    public function insert() {
        $this->exists(true);
        $params=get_object_vars($this->device);
        $id=DB::insert('devices', $params, 'hwid');
    }

    public static function getDevicesStmt(): \PDOStatement {
        $s=DB::query("SELECT d.uid, d.hwid, d.description, d.entity, p.name AS place_name FROM devices d LEFT JOIN places p ON d.place_id=p.id ORDER BY d.hwid");
        $s->setFetchMode(PDO::FETCH_OBJ);
        return $s;
    }

    public static function getDevicesHwids(): array {
        $s=DB::query('SELECT hwid FROM devices');
        return $s->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getDeviceByHwid($id): Entity\Device {
        $s=DB::prepare('SELECT * FROM devices WHERE hwid=?');
        $s->execute([$id]);
        $s->setFetchMode(PDO::FETCH_CLASS, Entity\Device::class);
        return $s->fetch();
    }

    public static function getUidByHwid($hwid) {
        $s=DB::prepare('SELECT uid FROM devices WHERE hwid=?');
        $s->execute([$hwid]);
        return $s->fetch(PDO::FETCH_COLUMN);
    }

    public static function refreshMemoryDevices(): void {
        $stmt=DB::prepare('SELECT hwid, entity FROM devices ORDER BY hwid');
        $stmt->execute();
        $mem=new Device\MemoryStorage();
        $mem->lockMemory();
        while ($device=$stmt->fetch(\PDO::FETCH_OBJ)) {
            $entity=json_decode($device->entity);
            if ($mem->existsDevice($device->hwid)) {
                $device_obj=$mem->getDevice($device->hwid);
                if (!($device_obj instanceof $entity->classname)) {
                    $device_obj=new $entity->classname;
                }
            } else {
                $device_obj=new $entity->classname;
            }
            $device_obj->init($device->hwid, $entity->properties);
            $mem->setDevice($device->hwid, $device_obj);
        }
        $mem->releaseMemory();
    }

}

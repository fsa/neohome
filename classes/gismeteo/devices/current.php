<?php

namespace Gismeteo\Devices;

use AppException,
    SmartHome\Devices,
    SmartHome\DeviceStorage;

class Current implements \SmartHome\DeviceInterface {

    public $weather;

    private $gismeteo;
    private $hwid;
    private $api_key;
    private $city_id;
    private $updated;

    public function __construct() {
        $this->updated=0;
    }

    public function getEventsList(): array {
        return ['temperature', 'humidity', 'pressure', 'wind_speed', 'wind_direction'];
    }

    public function getDescription(): string {
        return 'Текущая погода с сайта Gismeteo.ru';
    }

    public function getHwid(): string {
        return $this->hwid;
    }

    public function getState(): array {
        if(is_null($this->weather)) {
            return [];
        }
        return [
            'temperature'=>round($this->getTemperature(),1),
            'temp_feels_like'=>round($this->getTempFeelsLike(),1),
            'humidity'=>round($this->getHumidity()),
            'pressure'=>$this->getPressure(),
            'description'=>$this->weather->response->description->full,
            'wind_speed'=>$this->weather->response->wind->speed->m_s,
            'wind_direction'=>$this->weather->response->wind->direction->degree,
            'wind_direction_string'=>$this->getWindDirection()
                ];
    }

    public function __toString(): string {
        if (is_null($this->weather)) {
            return 'Информация о погоде отсутствует';
        }
        if ($this>$this->weather->meta->code!="299") {
            return 'Ошибка: '.$this->weather->meta->code.' '.$this->weather->meta->message;
        }
        return $this->getTemperature().'('.$this->getTempFeelsLike().')&deg;C, '.$this->getHumidity().'%, '.$this->getPressure().'&nbsp;мм.рт.ст., '.$this->weather->response->description->full.', ветер '.$this->weather->response->wind->speed->m_s.' м/с, направление '.$this->getWindDirection().' ('.$this->weather->response->wind->direction->degree.')';
    }

    public function getInitDataList(): array {
        return ['api_key'=>'Ключ API', 'city_id'=>'ID города'];
    }

    public function getInitDataValues(): array {
        return ['api_key'=>$this->api_key, 'city_id'=>$this->city_id];
    }

    public function getLastUpdate(): int {
        return $this->updated;
    }

    public function getModuleName(): string {
        return 'gismeteo';
    }

    public function init($device_id, $init_data): void {
        $this->hwid=$device_id;
        foreach ($init_data as $key=> $value) {
            $this->$key=$value;
        }
        if (is_null($this->api_key)) {
            throw new AppException('Не указан ключ API');
        }
        if (is_null($this->city_id)) {
            throw new AppException('Не указан ID города.');
        }
        $this->gismeteo=new \Gismeteo\Api($this->api_key);
        $this->gismeteo->setCityId($this->city_id);
    }

    public function update(): bool {
        $weather=$this->gismeteo->fetchCurrent();
        if (is_null($weather)) {
            return false;
        }
        if ($this->updated==$weather->response->date->unix) {
            return true;
        }
        $this->weather=$weather;
        $this->updated=$weather->response->date->unix;
        $storage=new DeviceStorage;
        $storage->set($this->hwid, $this);
        $events=['temperature'=>$weather->response->temperature->air->C, 'humidity'=>$weather->response->humidity->percent, 'pressure'=>round($weather->response->pressure->h_pa*76000/101325, 2), 'wind_speed'=>$weather->response->wind->speed->m_s, 'wind_direction'=>$weather->response->wind->direction->degree, 'weather'=>json_encode($weather, JSON_UNESCAPED_UNICODE)];
        $uid=Devices::getUidByHwid($this->hwid);
        Devices::processEvents($uid, $events, $weather->response->date->unix);
        return true;
    }

    public function getTemperature() {
        return isset($this->weather->response->temperature->air->C)?$this->weather->response->temperature->air->C:null;
    }

    public function getTempFeelsLike() {
        return isset($this->weather->response->temperature->comfort->C)?$this->weather->response->temperature->comfort->C:null;
    }

    public function getHumidity() {
        return isset($this->weather->response->humidity->percent)?$this->weather->response->humidity->percent:null;
    }

    public function getPressure() {
        return isset($this->weather->response->pressure->h_pa)?round($this->weather->response->pressure->h_pa*76000/101325, 2):null;
    }

    public function getWindSpeed() {
        if (!isset($this->weather->response->wind->speed->m_s)) {
            return '-';
        }
        return $this->weather->response->wind->speed->m_s;
    }

    public function getWindDirection() {
        if (!isset($this->weather->response->wind->direction->degree)) {
            return '-';
        }
        $deg=$this->weather->response->wind->direction->degree;
        if ($deg<22) {
            return 'C';
        } elseif ($deg<68) {
            return 'СЗ';
        } elseif ($deg<112) {
            return 'З';
        } elseif ($deg<158) {
            return 'ЮЗ';
        } elseif ($deg<202) {
            return 'Ю';
        } elseif ($deg<248) {
            return 'ЮВ';
        } elseif ($deg<292) {
            return 'В';
        } elseif ($deg<338) {
            return 'СВ';
        } else {
            return 'С';
        }
    }

}

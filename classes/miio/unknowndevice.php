<?php

namespace miIO;

class UnknownDevice implements \SmartHome\DeviceInterface {

    private $uid;
    private $location;
    private $token;
    private $timediff;
    private $connection;
    private static $message_id=1;
    private $updated;

    private $data;

    public function getDeviceDescription(): string {
        return "Неподдерживаемый тип устройства";
    }

    public function getDeviceId(): string {
        return $this->uid;
    }

    public function getDeviceStatus(): string {
        return print_r($this->data,true)??'Нет данных';
    }

    public function getInitDataList(): array {
        return ['token'=>'Токен'];
    }

    public function getInitDataValues(): array {
        return ['token'=>$this->token];
    }

    public function getLastUpdate(): int {
        return $this->updated;
    }

    public function getModuleName(): string {
        return "miio";
    }

    public function getActions() {
        return null;
    }

    public function init($device_id,$init_data): void {
        $this->uid=$device_id;
        foreach ($init_data as $key=> $value) {
            $this->$key=$value;
        }
    }
    
    public function update(MiPacket $pkt) {
        if($pkt->isHelloPacket()) {
            $this->uid=$pkt->getDeviceId();
            $this->location=$pkt->getDeviceAddr();
            $this->timediff=$pkt->getDeviceTimestamp()-time();
        } else {
            $pkt->setToken($this->token);
            $this->data.=$pkt->decryptMessage();
        }
        $this->updated=time();
    }

    public function sendCommand(string $method, array $params=[]): string {
        #TODO: проверка token и location
        $id=self::$message_id++;
        $cmd=[
            'id'=>$id,
            'method'=>$method,
            'params'=>$params
        ];
        $cmd=json_encode($cmd)."\r\n";
        $connection=$this->getConnection();
        $pkt=new MiPacket();
        $pkt->setDeviceId($this->uid);
        $pkt->setToken($this->token);
        $connection->sendTo($this->location, $pkt->buildMessage($cmd, time()+$this->timediff));
        $responce=$connection->getPacket();
        $responce->setToken($this->token);
        return $responce->decryptMessage();
    }

    public function getResponse(): MiPacket {
        $connection=$this->getConnection();
        return $connection->getPacket();
    }


    private function getConnection(): SocketServer {
        if (!is_null($this->connection)) {
            return $this->connection;
        }
        $this->connection=new SocketServer;
        $this->connection->setTimeoutSocket(2);
        return $this->connection;
    }

    public function disconnect(): void {
        $this->connection=null;
    }
}
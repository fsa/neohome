<?php

namespace Yeelight;

class Daemon {
    
    const DAEMON_NAME='yeelight';

    private $storage;
    private $socketserver;
    private $devices;
    private $process_url;
    
    public function __construct($process_url) {
        $this->process_url=$process_url;
    }
    
    public function getName() {
        return self::DAEMON_NAME;
    }

    public function prepare() {
        $this->storage=new \MemoryStorage();
        $this->devices=$this->storage->getArray(self::DAEMON_NAME);
        $this->socketserver=new SocketServer();
        $this->socketserver->run();
        $this->socketserver->sendDiscover();
    }

    public function iteration() {
        $pkt=$this->socketserver->getPacket();
        $p=$pkt->getParams();
        if (isset($p['id'])) {
            $id=$p['id'];
            if (!isset($this->devices[$id])) {
                $this->devices[$id]=new GenericDevice();
            }
            $this->devices[$id]->updateState($p);
            $this->storage->setVar(self::DAEMON_NAME,$this->devices);
            $actions=$this->devices[$id]->getActions();
            if (!is_null($actions)) {
                $data=['module'=>self::DAEMON_NAME,'uid'=>$id,'data'=>$actions];
                file_get_contents($this->process_url.'?'.http_build_query($data));
                #echo date('c').' '.$id.'=>'.$actions.PHP_EOL;
            }
        }
    }

    public function finish() {
        return;
    }

}

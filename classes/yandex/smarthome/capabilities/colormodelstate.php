<?php

namespace Yandex\SmartHome\Capabilities;

class ColorModelState extends State {

    public $type="devices.capabilities.color_setting";

    public function __construct(string $mode, int $value) {
        $this->state=[
            "instance"=>$mode,
            "value"=>$value
        ];
    }

}

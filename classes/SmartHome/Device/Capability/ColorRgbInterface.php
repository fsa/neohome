<?php

namespace SmartHome\Device\Capability;

interface ColorRgbInterface {

    function setRGB(int $value);

    function getRGB(): int;
}

<?php

namespace Widgets;

use FSA\Neuron\HttpResponse;

class Weather {

    public static function show($title, $device_name) {
        HttpResponse::showCard($title, '<span style="font-size: 1.8rem;"><span class="text-nowrap"><span device_name="'.$device_name.'" device_property="temperature">-</span> (<span device_name="'.$device_name.'" device_property="temp_feels_like">-</span>)&deg;C</span> <span device_name="'.$device_name.'" device_property="humidity">-</span>% <span class="text-nowrap"><span device_name="'.$device_name.'" device_property="wind_speed">-</span> м/с (<span device_name="'.$device_name.'" device_property="wind_direction_string">-</span>)</span></span>', '<span device_name="'.$device_name.'" device_property="last_update">');
    }

}

<?php
if(!isset($request_id)) {die;}
header('Content-Type: application/json');
include 'query.json';

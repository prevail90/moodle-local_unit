<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_created',
        'callback' => '\local_unit\observer::user_created',
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_unit\observer::user_updated',
    ],
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_unit\observer::user_loggedin',
    ],
];

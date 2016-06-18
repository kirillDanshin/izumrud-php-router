<?php

ini_set('display_errors', 1);
error_reporting(-1);

require 'src/Izumrud/Router.php';

$r = new \Izumrud\Router();

$r->AddProjects([
    'global' => [
        'not_found_handler' => function ($args) {
            return sprintf('%s not found.', $args['uri']);
        },
    ], // default project
    'example' => [
        'domains' => ['example.com'], // www.example.com will be handled too
        'not_found_handler' => function ($args) {
            return sprintf('%s not found.', $args['uri']);
        },
    ],
]);

$r->InjectLogger(function ($msg) {
    Printf("%s\n", $msg);
});

$r->Submit();

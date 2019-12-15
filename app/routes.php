<?php

$app->get('/', function() {
    require_once "entry.php";
});

$app->get('/chat(/:username)', function($username) {
    setcookie("username", $username);
    require_once "chat.php";
});

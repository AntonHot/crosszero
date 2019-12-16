<?php

$app->get('/', function() {
    require_once "entry.php";
});

$app->get('/chat/', function() {
    require_once "chat.php";
});

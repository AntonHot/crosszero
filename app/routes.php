<?php

$app->get('/', function() {
    require_once "app/mvc/views/entry.php";
});

$app->get('/chat/', function() {
    require_once "app/mvc/views/chat.php";
});

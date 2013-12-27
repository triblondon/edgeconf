<?php

require_once '../app/global';

session_start();
$_SESSION = array();
session_destroy();

$url = isset($_GET['redir']) ? $_GET['redir'] : '/';
header('Location: '.$url);

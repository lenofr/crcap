<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
session_destroy();
header('Location: /crcap/pages/login.php');
exit;

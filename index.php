<?php
require_once 'includes/config.php';
header('Location: ' . (isLoggedIn() ? BASE_URL.'/dashboard.php' : BASE_URL.'/login.php'));
exit;

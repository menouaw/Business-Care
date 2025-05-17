<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../shared/web-client/config.php';
require_once __DIR__ . '/../../shared/web-client/functions.php';
require_once __DIR__ . '/../../shared/web-client/auth.php';

session_start();

ensureCsrfToken();
verifyPostedCsrfToken();
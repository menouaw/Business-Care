<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/page_functions/employees/dashboard.php'; // <- Confirmer que ce chemin est correct

requireRole(ROLE_SALARIE);

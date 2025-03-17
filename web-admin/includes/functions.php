<?php
require_once 'config.php';
require_once 'db.php';

function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

function formatMoney($amount, $currency = '€') {
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

function getRecentActivities($limit = 10) {
    $sql = "SELECT l.*, CONCAT(p.prenom, ' ', p.nom) as user_name
            FROM logs l
            LEFT JOIN personnes p ON l.personne_id = p.id
            ORDER BY l.created_at DESC
            LIMIT ?";
    
    $stmt = executeQuery($sql, [$limit]);
    return $stmt->fetchAll();
}

function generatePageTitle($title = '') {
    if ($title) {
        return APP_NAME . ' - ' . $title;
    }
    return APP_NAME;
}

function redirectTo($url) {
    header('Location: ' . $url);
    exit;
}

function getFormData() {
    return sanitizeInput($_POST);
}

function getQueryData() {
    return sanitizeInput($_GET);
}

function flashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function displayFlashMessages() {
    $flashMessage = getFlashMessage();
    if ($flashMessage) {
        $type = $flashMessage['type'];
        $message = $flashMessage['message'];
        
        $alertClass = 'alert-info';
        switch ($type) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'danger':
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
        }
        
        $html = '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        $html .= $message;
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
        
        return $html;
    }
    
    return '';
}

function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateToken($token) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    return true;
}

function getStatusBadge($status, $context = '') {
    $badges = [
        'actif' => 'success',
        'inactif' => 'danger',
        'en_attente' => 'warning',
        'suspendu' => 'secondary',
        'expire' => 'danger',
        'resilie' => 'danger',
        'accepte' => 'success',
        'refuse' => 'danger'
    ];
    
    $class = isset($badges[$status]) ? $badges[$status] : 'primary';
    return '<span class="badge bg-' . $class . '">' . ucfirst($status) . '</span>';
}

function paginateResults($table, $page, $perPage = 20, $where = '', $orderBy = '') {
    $totalItems = countTableRows($table, $where);
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    $items = fetchAll($table, $where, $orderBy, $perPage, $offset);
    
    return [
        'items' => $items,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'perPage' => $perPage
    ];
}

function renderPagination($pagination, $urlPattern) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    $prevDisabled = $pagination['currentPage'] <= 1 ? ' disabled' : '';
    $prevUrl = str_replace('{page}', $pagination['currentPage'] - 1, $urlPattern);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
    
    // numeros de page
    $startPage = max(1, $pagination['currentPage'] - 2);
    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $pagination['currentPage'] ? ' active' : '';
        $url = str_replace('{page}', $i, $urlPattern);
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }
    
    // bouton suivant
    $nextDisabled = $pagination['currentPage'] >= $pagination['totalPages'] ? ' disabled' : '';
    $nextUrl = str_replace('{page}', $pagination['currentPage'] + 1, $urlPattern);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
} 
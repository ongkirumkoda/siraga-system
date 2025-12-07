<?php
// includes/functions.php

function cleanInput($data) {
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

function getUserRole() {
    return $_SESSION['user']['role'] ?? null;
}

function getUserName() {
    return $_SESSION['user']['name'] ?? 'Guest';
}

function getRoleName($role) {
    $roleNames = [
        'government' => 'Pemerintah',
        'nakes' => 'Tenaga Kesehatan',
        'parent' => 'Orang Tua',
        'admin' => 'Administrator'
    ];
    return $roleNames[$role] ?? 'Unknown';
}
?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role'])) {
    $servername = "localhost";
    $db_user    = "root";
    $db_pass    = "";
    $dbname     = "shop_db";

    $conn = new mysqli($servername, $db_user, $db_pass, $dbname);
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();
        $_SESSION['role'] = $row['role'] ?? 'user';
    }
}

function require_admin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("HTTP/1.1 403 Forbidden");
        header("Location: main.php");
        exit();
    }
}
?>

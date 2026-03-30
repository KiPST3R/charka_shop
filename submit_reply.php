<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit();
}

// Только авторизованные
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
    exit();
}

$admin_id   = (int) $_SESSION['user_id'];
$review_id  = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
$reply_text = isset($_POST['reply_text']) ? trim($_POST['reply_text']) : '';

if ($review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Отзыв не найден']);
    exit();
}
if ($reply_text === '') {
    echo json_encode(['success' => false, 'message' => 'Текст ответа не может быть пустым']);
    exit();
}
if (mb_strlen($reply_text) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Ответ слишком длинный (максимум 2000 символов)']);
    exit();
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "shop_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных']);
    exit();
}

// Дополнительная проверка
$role_check = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$role_check->bind_param("i", $admin_id);
$role_check->execute();
$role_result = $role_check->get_result()->fetch_assoc();
$role_check->close();

if (!$role_result || $role_result['role'] !== 'admin') {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
    exit();
}

$check = $conn->prepare("SELECT id FROM review_replies WHERE review_id = ? LIMIT 1");
$check->bind_param("i", $review_id);
$check->execute();
$existing = $check->get_result();
$check->close();

if ($existing->num_rows > 0) {
    $upd = $conn->prepare("UPDATE review_replies SET reply_text = ?, created_at = NOW() WHERE review_id = ?");
    $upd->bind_param("si", $reply_text, $review_id);
    $success = $upd->execute();
    $upd->close();
} else {
    $ins = $conn->prepare("INSERT INTO review_replies (review_id, admin_id, reply_text, created_at) VALUES (?, ?, ?, NOW())");
    $ins->bind_param("iis", $review_id, $admin_id, $reply_text);
    $success = $ins->execute();
    $ins->close();
}

$conn->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Ответ сохранён']);
} else {
    echo json_encode(['success' => false, 'message' => 'Не удалось сохранить ответ']);
}
exit();

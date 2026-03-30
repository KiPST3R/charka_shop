<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Только авторизованные пользователи могут оставлять отзывы']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$rating     = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$comment    = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Оценка должна быть от 1 до 5']);
    exit();
}

if ($comment === '') {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, напишите текст отзыва']);
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

$check_stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки запроса']);
    $conn->close();
    exit();
}

$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result && $check_result->num_rows > 0) {
    $check_stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Вы уже оставили отзыв на этот товар']);
    exit();
}

$check_stmt->close();

$order_stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
      AND o.status = 'completed'
      AND oi.product_id = ?
");
if (!$order_stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Ошибка проверки заказов']);
    exit();
}

$order_stmt->bind_param("ii", $user_id, $product_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order_row = $order_result ? $order_result->fetch_assoc() : null;
$order_stmt->close();

if (!$order_row || (int)$order_row['cnt'] === 0) {
    $conn->close();
    echo json_encode([
        'success' => false,
        'message' => 'Оставить отзыв могут только пользователи, которые оформили и получили этот товар (статус заказа completed)'
    ]);
    exit();
}

$is_verified = 1;

$insert_stmt = $conn->prepare("
    INSERT INTO reviews (user_id, product_id, rating, comment, is_verified, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
if (!$insert_stmt) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Ошибка подготовки запроса сохранения']);
    exit();
}

$insert_stmt->bind_param("iiisi", $user_id, $product_id, $rating, $comment, $is_verified);
$insert_success = $insert_stmt->execute();
$insert_stmt->close();
$conn->close();

if (!$insert_success) {
    echo json_encode(['success' => false, 'message' => 'Не удалось сохранить отзыв. Попробуйте позже.']);
    exit();
}

echo json_encode(['success' => true, 'message' => 'Спасибо! Ваш отзыв успешно опубликован.']);
exit();
<?php
session_start();
header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$comment = trim($_POST['comment'] ?? '');

if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД']);
    exit();
}

$is_logged_in = isset($_SESSION['user_id']);
$cart_items = [];
$total_price = 0;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT c.quantity, p.name, p.price, p.id as product_id
            FROM cart c 
            INNER JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $item_total = $row['price'] * $row['quantity'];
        $total_price += $item_total;
    }
    
    $stmt->close();
} else {
    if (!isset($_SESSION['guest_cart']) || empty($_SESSION['guest_cart'])) {
        echo json_encode(['success' => false, 'message' => 'Корзина пуста']);
        exit();
    }
    
    $product_ids = array_keys($_SESSION['guest_cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $sql = "SELECT id as product_id, name, price FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['product_id'];
        $quantity = $_SESSION['guest_cart'][$product_id];
        
        $cart_items[] = [
            'product_id' => $product_id,
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $quantity
        ];
        
        $item_total = $row['price'] * $quantity;
        $total_price += $item_total;
    }
    
    $stmt->close();
}

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Корзина пуста']);
    exit();
}

$order_items = [];
foreach ($cart_items as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $order_items[] = sprintf(
        "• %s x%d = %s ₽",
        $item['name'],
        $item['quantity'],
        number_format($item_total, 0, ',', ' ')
    );
}

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, comment, total_price, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssd", $user_id, $name, $phone, $comment, $total_price);
} else {
    // Для гостей user_id = NULL, используется отдельный запрос без параметра user_id
    $stmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, comment, total_price, created_at) VALUES (NULL, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssd", $name, $phone, $comment, $total_price);
}

$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

$message = "🛍 НОВЫЙ ЗАКАЗ #" . $order_id . "!\n\n";
$message .= "👤 Клиент: " . htmlspecialchars($name) . "\n";
$message .= "📱 Телефон: " . htmlspecialchars($phone) . "\n";

if ($is_logged_in) {
    $message .= "👤 Пользователь: " . htmlspecialchars($_SESSION['username']) . "\n";
} else {
    $message .= "👤 Статус: Гость\n";
}

$message .= "\n📦 Товары:\n" . implode("\n", $order_items) . "\n\n";
$message .= "💰 Итого: " . number_format($total_price, 0, ',', ' ') . " ₽\n";

if (!empty($comment)) {
    $message .= "\n💬 Комментарий: " . htmlspecialchars($comment);
}

$message .= "\n\n📅 Время заказа: " . date('d.m.Y H:i:s');

$bot_token = "TELEGRAM_BOT_TOKEN";
$chat_id = "CHAT_ID";

$telegram_api = "https://api.telegram.org/bot{$bot_token}/sendMessage";
$data = [
    'chat_id' => $chat_id,
    'text' => $message,
    'parse_mode' => 'HTML'
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$telegram_response = @file_get_contents($telegram_api, false, $context);

if ($telegram_response === false) {
    echo json_encode(['success' => false, 'message' => 'Ошибка отправки в Telegram']);
    exit();
}

$stmt_order_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");

foreach ($cart_items as $item) {
    $stmt_order_items->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
    $stmt_order_items->execute();
}

$stmt_order_items->close();

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    $_SESSION['guest_cart'] = [];
}

$conn->close();

echo json_encode([
    'success' => true, 
    'message' => 'Заказ успешно оформлен!',
    'order_id' => $order_id
]);
?>

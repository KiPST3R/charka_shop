<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД']);
    exit();
}

$action = $_POST['action'] ?? '';
$is_logged_in = isset($_SESSION['user_id']);

// Корзина для гостей
if (!$is_logged_in && !isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

switch ($action) {
    case 'add':
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($is_logged_in) {
            // Для аккаунтов - работа с БД
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $new_quantity = $row['quantity'] + $quantity;
                
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_quantity, $row['id']);
                $success = $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $success = $stmt->execute();
            }
            
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            // Для гостей - работа с сессией
            if (isset($_SESSION['guest_cart'][$product_id])) {
                $_SESSION['guest_cart'][$product_id] += $quantity;
            } else {
                $_SESSION['guest_cart'][$product_id] = $quantity;
            }
            echo json_encode(['success' => true]);
        }
        break;

    case 'update':
        $quantity = intval($_POST['quantity']);
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 99) $quantity = 99;

        if ($is_logged_in) {
            $user_id = $_SESSION['user_id'];
            $cart_id = intval($_POST['cart_id']);
            
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            $product_id = intval($_POST['product_id']);
            if (isset($_SESSION['guest_cart'][$product_id])) {
                $_SESSION['guest_cart'][$product_id] = $quantity;
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
        break;

    case 'remove':
        if ($is_logged_in) {
            $user_id = $_SESSION['user_id'];
            $cart_id = intval($_POST['cart_id']);
            
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
        } else {
            $product_id = intval($_POST['product_id']);
            if (isset($_SESSION['guest_cart'][$product_id])) {
                unset($_SESSION['guest_cart'][$product_id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
        break;

    case 'get_count':
        if ($is_logged_in) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            echo json_encode(['success' => true, 'count' => $row['total'] ?? 0]);
        } else {
            $total = 0;
            if (isset($_SESSION['guest_cart'])) {
                foreach ($_SESSION['guest_cart'] as $qty) {
                    $total += $qty;
                }
            }
            echo json_encode(['success' => true, 'count' => $total]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
        break;
}

$conn->close();
?>

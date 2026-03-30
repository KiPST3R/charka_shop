<?php
session_start();

$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "shop_db";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$action = $_POST['action'] ?? '';

if ($action == 'register') {
    $user         = trim($_POST['username']);
    $pass         = $_POST['password'];
    $pass_confirm = $_POST['password_confirm'];

    if ($pass !== $pass_confirm) {
        header("Location: login.php?error=password");
        exit();
    }

    if (strlen($user) < 3 || strlen($pass) < 6) {
        header("Location: login.php?error=short");
        exit();
    }

    // Проверка, существует ли пользователь
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: login.php?error=exists");
        exit();
    }
    $stmt->close();

    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // Роль всегда user при регистрации
    $role = 'user';

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user, $hashed_password, $role);

    if ($stmt->execute()) {
        header("Location: login.php?success=1");
    } else {
        header("Location: login.php?error=register");
    }

    $stmt->close();

} elseif ($action == 'login') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($pass, $row['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role']; // user или admin

            header("Location: main.php");
            exit();
        } else {
            header("Location: login.php?error=login");
        }
    } else {
        header("Location: login.php?error=login");
    }

    $stmt->close();
}

$conn->close();
?>

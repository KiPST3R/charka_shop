<?php
session_start();
?>

<script src="https://kit.fontawesome.com/af0a359bf8.js" crossorigin="anonymous"></script>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.4/css/all.css">
    <title><?php echo htmlspecialchars($page_title); ?></title>
</head>

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$allowed_categories = ['basic', 'glycerin', 'scrub', 'beldi'];
$category_titles = [
    'basic'    => 'Мыло',
    'glycerin' => 'Глицериновое мыло',
    'scrub'    => 'Скрабы',
    'beldi'    => 'Мыло бельди',
];

$category = isset($_GET['category']) && in_array($_GET['category'], $allowed_categories)
    ? $_GET['category']
    : null;

$page_title = $category ? ($category_titles[$category] ?? 'Каталог') : 'Каталог';

if ($category) {
    $stmt = $conn->prepare("SELECT id, name, price, image_url FROM products WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, name, price, image_url FROM products");
}
?>

<body>

    <header>
        <div class="headerdiv">
            <img src="img/logo.jpg" class="logo" alt="Логотип">
            <p class="headertitle"><?php echo htmlspecialchars($page_title); ?></p>

            <div class="nav">
                <a href="main.php"><p class="navlink">Каталог</p></a>
                <div style="position: relative;">
                    <a href="cart.php" class="navlink"><p class="navlink">Корзина</p></a>
                    <span class="cart-badge" id="cart-badge" style="display: none;">0</span>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <p class="user-welcome">Привет, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>!</p>
                        <a href="logout.php" class="logout-btn">Выйти</a>
                    </div>
                <?php else: ?>
                    <div><a href="login.php" class="navlink"> <p>Вход</p></a></div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="product-grid">
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $price = $row['price'];
                        $formatted_price = number_format($price, 0, ',', ' '); 
                ?>
                        <div class="product-card-wrapper">
                            <a href="product.php?id=<?php echo $row['id']; ?>" class="product-card-link">
                                <div class="product-card">
                                    <div class="product-image-container">
                                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                             class="product-image">
                                        <div class="product-overlay">
                                            <span class="view-details">Подробнее</span>
                                        </div>
                                    </div>
                                    
                                    <div class="product-info">
                                        <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                                        <div class="product-footer">
                                            <span class="product-price">₽ <?php echo $formatted_price; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            
                            <button class="add-to-cart-btn" onclick="addToCart(<?php echo $row['id']; ?>, event)">
                                <i class="fas fa-shopping-basket"></i>
                            </button>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p class='no-products'>Товары не найдены в базе данных</p>";
                }
                $conn->close(); 
                ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo-section">
                    <img src="img/logo.jpg" alt="Логотип" class="footer-logo" loading="lazy">
                    <div class="footer-brand">
                        <h3>Мыло ручной работы</h3>
                        <p>Делаем с любовью</p>
                    </div>
                </div>
            </div>

            <div class="footer-center">
                <div class="footer-divider"></div>
            </div>

            <div class="footer-right">
                <h4 class="footer-title">Контакты</h4>
                <div class="footer-contacts">
                    <a href="https://t.me/charka_Savon" target="_blank" class="footer-contact-item telegram">
                        <div class="contact-icon">
                            <i class="fab fa-telegram"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Наш канал</span>
                            <span class="contact-value">@charka_Savon</span>
                        </div>
                    </a>

                    <a href="mailto:charka78@yandex.ru" class="footer-contact-item email">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-info">
                            <span class="contact-label">Email для сотрудничества</span>
                            <span class="contact-value">charka78@yandex.ru</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- <div class="footer-bottom">
            <div class="footer-wave"></div>
            <p class="footer-copyright">
                <i class="fas fa-heart"></i>
                Сделано с любовью © <span id="current-year"></span>
            </p>
        </div> -->
    </footer>

<script>
    document.getElementById('current-year').textContent = new Date().getFullYear();
</script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });

        function updateCartCount() {
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('cart-badge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            });
        }

        function addToCart(productId) {
            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Товар добавлен в корзину!');
                    updateCartCount();
                } else {
                    alert('Ошибка при добавлении товара');
                }
            });
        }

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'cart-notification';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    </script>

</body>
</html>

<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$cart_items = [];
$total_price = 0;
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    // Для аккаунтов - из БД
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT c.id as cart_id, c.quantity, p.id, p.name, p.price, p.image_url, p.description 
            FROM cart c 
            INNER JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ? 
            ORDER BY c.added_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }
    
    $stmt->close();
} else {
    // Для гостей - из сессии
    if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
        $product_ids = array_keys($_SESSION['guest_cart']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $sql = "SELECT id, name, price, image_url, description FROM products WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $product_id = $row['id'];
            $quantity = $_SESSION['guest_cart'][$product_id];
            
            $cart_items[] = [
                'cart_id' => $product_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => $row['price'],
                'image_url' => $row['image_url'],
                'description' => $row['description']
            ];
            
            $total_price += $row['price'] * $quantity;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<script src="https://kit.fontawesome.com/af0a359bf8.js" crossorigin="anonymous"></script>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <title>Корзина</title>
</head>
<body>
    <header>
        <div class="headerdiv">
            <img src="img/logo.jpg" class="logo" alt="Логотип">
            <p class="headertitle">Корзина</p>
            <div class="nav">
                <a href="main.php"><p class="navlink">Каталог</p></a>
                <div style="position: relative;">
                    <a href = "cart.php" class = "navlink"><p class = "navlink">Корзина</p></a>
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
        <div class="cart-container">
            <h1 class="cart-title">
                <i class="fas fa-shopping-cart"></i> Моя корзина
            </h1>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket empty-cart-icon"></i>
                    <h2>Ваша корзина пуста</h2>
                    <p>Добавьте товары из каталога</p>
                    <a href="main.php" class="go-to-catalog-btn">
                        <i class="fas fa-arrow-left"></i> Перейти в каталог
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>" data-product-id="<?php echo $item['id']; ?>">
                                <a href="product.php?id=<?php echo $item['id']; ?>" class="cart-item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </a>
                                
                                <div class="cart-item-info">
                                    <a href="product.php?id=<?php echo $item['id']; ?>" class="cart-item-name">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                    <p class="cart-item-description">
                                        <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...
                                    </p>
                                </div>

                                <div class="cart-item-quantity">
                                    <button class="quantity-btn minus" onclick="changeQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['id']; ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="99"
                                           onchange="updateQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['id']; ?>, this.value)"
                                           readonly>
                                    <button class="quantity-btn plus" onclick="changeQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['id']; ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>

                                <div class="cart-item-price">
                                    <span class="item-total">
                                        ₽ <?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?>
                                    </span>
                                </div>

                                <button class="cart-item-remove" onclick="removeFromCart(<?php echo $item['cart_id']; ?>, <?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-summary">
                        <h2>Итого</h2>
                        <div class="summary-line">
                            <span>Товаров:</span>
                            <span id="total-items"><?php echo count($cart_items); ?></span>
                        </div>
                        <div class="summary-line total">
                            <span>Сумма:</span>
                            <span id="total-price">₽ <?php echo number_format($total_price, 0, ',', ' '); ?></span>
                        </div>
                        <button class="order-btn" onclick="openOrderModal()">
                            <i class="fas fa-check-circle"></i> Оформить заказ
                        </button>
                        <a href="main.php" class="continue-shopping">
                            <i class="fas fa-arrow-left"></i> Продолжить покупки
                        </a>
                    </div>
                </div>
            <?php endif; ?>
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

    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <span class="order-modal-close" onclick="closeOrderModal()">&times;</span>
            <h2><i class="fas fa-shopping-bag"></i> Оформление заказа</h2>
            
            <form id="orderForm" onsubmit="submitOrder(event)">
                <div class="order-form-group">
                    <label for="customer-name">
                        <i class="fas fa-user"></i> Ваше имя <span class="required">*</span>
                    </label>
                    <input type="text" id="customer-name" name="name" required placeholder="Введите ваше имя">
                </div>

                <div class="order-form-group">
                    <label for="customer-phone">
                        <i class="fas fa-phone"></i> Телефон <span class="required">*</span>
                    </label>
                    <input type="tel" id="customer-phone" name="phone" required placeholder="+7 (___) ___-__-__">
                </div>

                <div class="order-form-group">
                    <label for="customer-comment">
                        <i class="fas fa-comment"></i> Комментарий
                    </label>
                    <textarea id="customer-comment" name="comment" rows="4" placeholder="Дополнительные пожелания к заказу"></textarea>
                </div>

                <div class="order-summary-modal">
                    <div class="order-total-modal">
                        <span>Итого к оплате:</span>
                        <span class="order-price-modal">₽ <?php echo number_format($total_price, 0, ',', ' '); ?></span>
                    </div>
                </div>

                <button type="submit" class="submit-order-btn" id="submitOrderBtn">
                    <i class="fas fa-check-circle"></i> Подтвердить заказ
                </button>
            </form>
        </div>
    </div>

    <div id="successModal" class="order-modal">
        <div class="success-modal-content">
            <span class="order-modal-close" onclick="location.href='main.php'">&times;</span>
            
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h2>Заказ успешно оформлен!</h2>
            
            <div class="order-number">
                <p>Номер вашего заказа:</p>
                <span id="orderNumber">#12345</span>
            </div>
            
            <p class="success-message">
                Спасибо за ваш заказ! Мы свяжемся с вами в ближайшее время для подтверждения.
            </p>
            
            <button class="go-to-catalog-btn" onclick="location.href='main.php'">
                <i class="fas fa-arrow-left"></i> Вернуться в каталог
            </button>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            initPhoneMask();
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

        function changeQuantity(cartId, productId, delta) {
            const item = document.querySelector(`[data-cart-id="${cartId}"]`);
            const input = item.querySelector('.quantity-input');
            let newQuantity = parseInt(input.value) + delta;
            
            if (newQuantity < 1) newQuantity = 1;
            if (newQuantity > 99) newQuantity = 99;
            
            updateQuantity(cartId, productId, newQuantity);
        }

        function updateQuantity(cartId, productId, quantity) {
            quantity = parseInt(quantity);
            if (quantity < 1) quantity = 1;
            if (quantity > 99) quantity = 99;

            let body = `action=update&quantity=${quantity}`;
            if (isLoggedIn) {
                body += `&cart_id=${cartId}`;
            } else {
                body += `&product_id=${productId}`;
            }

            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка при обновлении количества');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при обновлении количества');
            });
        }

        function removeFromCart(cartId, productId) {
            if (!confirm('Удалить товар из корзины?')) return;

            let body = `action=remove`;
            if (isLoggedIn) {
                body += `&cart_id=${cartId}`;
            } else {
                body += `&product_id=${productId}`;
            }

            fetch('cart_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка при удалении товара');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при удалении товара');
            });
        }

        function openOrderModal() {
            const modal = document.getElementById('orderModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function initPhoneMask() {
            const phoneInput = document.getElementById('customer-phone');
            if (!phoneInput) return;
            
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.startsWith('8')) {
                    value = '7' + value.substring(1);
                }
                
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                let formatted = '+7';
                if (value.length > 1) {
                    formatted += ' (' + value.substring(1, 4);
                }
                if (value.length >= 5) {
                    formatted += ') ' + value.substring(4, 7);
                }
                if (value.length >= 8) {
                    formatted += '-' + value.substring(7, 9);
                }
                if (value.length >= 10) {
                    formatted += '-' + value.substring(9, 11);
                }
                
                e.target.value = formatted;
            });

            phoneInput.addEventListener('keydown', function(e) {
                if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true)) {
                    return;
                }
                
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });

            phoneInput.value = '+7 ';
        }

        function submitOrder(event) {
            event.preventDefault();
            
            const form = document.getElementById('orderForm');
            const submitBtn = document.getElementById('submitOrderBtn');
            const formData = new FormData(form);
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Оформление...';
            
            fetch('order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeOrderModal();
                    
                    document.getElementById('orderNumber').textContent = '#' + data.order_id;
                    document.getElementById('successModal').style.display = 'flex';
                    
                    updateCartCount();
                } else {
                    alert('Ошибка: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Подтвердить заказ';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при оформлении заказа');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Подтвердить заказ';
            });
        }

        window.onclick = function(event) {
            const orderModal = document.getElementById('orderModal');
            const successModal = document.getElementById('successModal');
            
            if (event.target === orderModal) {
                closeOrderModal();
            }
        }
        </script>
</body>
</html>

<?php
session_start();
?>

<script src="https://kit.fontawesome.com/af0a359bf8.js" crossorigin="anonymous"></script>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel = "stylesheet" href = "css/style.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.4/css/all.css">
    <title>Главная</title>
</head>

<body>

    <header>
        <div class = "headerdiv">
            <img src = "img/logo.jpg" class = "logo" alt = "Логотип">
            <p class = "headertitle">Категории</p>
            <div class = "nav">
                <div class = "dropdown_catalog"><a href = "main.php" class = "navlink"><p class = "navlink">Каталог</p></a></div>
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
                    <div><a href = "login.php" class = "navlink"> <p>Вход</p></a></div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="hero-section">
            <h1 class="hero-title">Выберите категорию</h1>
            <p class="hero-subtitle">Натуральное мыло ручной работы для вашей красоты и уюта</p>
        </div>

        <div class = "navbuttons">
            <a href="catalog.php?category=basic" class="navbutton-link">
                <div class="navbutton">
                    <div class="navbutton-content">
                        <div class="navbutton-icon">
                            <i class="fas fa-soap"></i>
                        </div>
                        <div class="navbutton-text">
                            <h2>Мыло</h2>
                            <p class="navbutton-description">Классическое мыло для ежедневного ухода</p>
                        </div>
                        <div class="navbutton-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            </a>

            <a href="catalog.php?category=glycerin" class="navbutton-link">
                <div class="navbutton">
                    <div class="navbutton-content">
                        <div class="navbutton-icon">
                            <i class="fas fa-droplet"></i>
                        </div>
                        <div class="navbutton-text">
                            <h2>Глицериновое мыло</h2>
                            <p class="navbutton-description">Прозрачное мыло с увлажняющим эффектом</p>
                        </div>
                        <div class="navbutton-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- <a href="catalog.php?category=scrub" class="navbutton-link"> -->
                <div class="navbutton-disabled">
                    <div class="navbutton-content">
                        <div class="navbutton-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="navbutton-text">
                            <h2>Скрабы</h2>
                            <p class="navbutton-description">Натуральные скрабы для нежной кожи</p>
                        </div>
                        <div class="navbutton-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- <a href="catalog.php?category=beldi" class="navbutton-link"> -->
                <div class="navbutton-disabled">
                    <div class="navbutton-content">
                        <div class="navbutton-icon">
                            <i class="fas fa-spa"></i>
                        </div>
                        <div class="navbutton-text">
                            <h2>Мыло бельди</h2>
                            <p class="navbutton-description">Марокканское черное мыло для глубокого очищения</p>
                        </div>
                        <div class="navbutton-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="features-section">
            <div class="feature-card">
                <i class="fas fa-leaf feature-icon"></i>
                <h3>100% натурально</h3>
                <p>Только природные ингредиенты</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-hand-sparkles feature-icon"></i>
                <h3>Ручная работа</h3>
                <p>Каждый продукт создан с любовью</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-heart feature-icon"></i>
                <h3>Без химии</h3>
                <p>Безопасно для всей семьи</p>
            </div>
        </div>
    </main>
    
    <footer>

    </footer>

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
    </script>
    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo-section">
                    <img src="img/logo.jpg" alt="Логотип" class="footer-logo">
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
                            <span class="contact-label">Telegram</span>
                            <span class="contact-value">@charka_Savon</span>
                        </div>
                    </a>

                    <a href="copy:charka78@yandex.ru" class="footer-contact-item email">
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

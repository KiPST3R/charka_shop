<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: main.html");
    exit();
}
?>

<script src="https://kit.fontawesome.com/af0a359bf8.js" crossorigin="anonymous"></script>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.4/css/all.css">
    <title>Вход / Регистрация</title>
</head>
<body>
    <header>
        <div class="headerdiv">
            <img src="img/logo.jpg" class="logo" alt="Логотип">
            <div class="nav">
                <a href="main.php"><p class="navlink">Каталог</p></a>
            </div>
        </div>
    </header>

    <main>
        <div class="auth-container">
            <div class="auth-box">
                <div class="auth-tabs">
                    <button class="auth-tab active" id="login-tab">Вход</button>
                    <button class="auth-tab" id="register-tab">Регистрация</button>
                </div>

                <form id="login-form" class="auth-form active" action="auth_process.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <h2>Войти в аккаунт</h2>
                    
                    <div class="form-group">
                        <label for="login-username">
                            <i class="fas fa-user"></i> Логин
                        </label>
                        <input type="text" id="login-username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="login-password">
                            <i class="fas fa-lock"></i> Пароль
                        </label>
                        <input type="password" id="login-password" name="password" required>
                    </div>

                    <?php if (isset($_GET['error']) && $_GET['error'] == 'login'): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> Неверный логин или пароль
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="auth-submit">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </form>

                <form id="register-form" class="auth-form" action="auth_process.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    <h2>Создать аккаунт</h2>
                    
                    <div class="form-group">
                        <label for="register-username">
                            <i class="fas fa-user"></i> Логин
                        </label>
                        <input type="text" id="register-username" name="username" required minlength="3">
                        <small>Минимум 3 символа</small>
                    </div>

                    <div class="form-group">
                        <label for="register-password">
                            <i class="fas fa-lock"></i> Пароль
                        </label>
                        <input type="password" id="register-password" name="password" required minlength="6">
                        <small>Минимум 6 символов</small>
                    </div>

                    <div class="form-group">
                        <label for="register-password-confirm">
                            <i class="fas fa-lock"></i> Подтвердите пароль
                        </label>
                        <input type="password" id="register-password-confirm" name="password_confirm" required>
                    </div>

                    <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> Пользователь с таким логином уже существует
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error']) && $_GET['error'] == 'password'): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> Пароли не совпадают
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i> Регистрация успешна! Теперь можете войти
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="auth-submit">
                        <i class="fas fa-user-plus"></i> Зарегистрироваться
                    </button>
                </form>
            </div>
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
        const loginTab = document.getElementById('login-tab');
        const registerTab = document.getElementById('register-tab');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        loginTab.addEventListener('click', function() {
            loginTab.classList.add('active');
            registerTab.classList.remove('active');
            loginForm.classList.add('active');
            registerForm.classList.remove('active');
        });

        registerTab.addEventListener('click', function() {
            registerTab.classList.add('active');
            loginTab.classList.remove('active');
            registerForm.classList.add('active');
            loginForm.classList.remove('active');
        });

        // Валидация совпадения паролей
        const registerFormElement = document.getElementById('register-form');
        registerFormElement.addEventListener('submit', function(e) {
            const password = document.getElementById('register-password').value;
            const passwordConfirm = document.getElementById('register-password-confirm').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Пароли не совпадают!');
            }
        });
    </script>
</body>
</html>

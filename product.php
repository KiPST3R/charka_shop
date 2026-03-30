<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.4/css/all.css">
    <title>Детали товара</title>
</head>
<script src="https://kit.fontawesome.com/af0a359bf8.js" crossorigin="anonymous"></script>
<body>
    <header>
        <div class="headerdiv">
            <img src="img/logo.jpg" class="logo" alt="Логотип">

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
                    <div><a href = "login.php" class = "navlink"> <p>Вход</p></a></div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <?php
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "shop_db";
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        if (isset($_GET['id'])) {
            $product_id = intval($_GET['id']);

            $stmt = $conn->prepare("SELECT id, name, price, image_url, img_detail_url1, img_detail_url2, img_detail_url3, description, compound, storage_conditions FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $formatted_price = number_format($row['price'], 0, ',', ' ');

                $is_logged_in = isset($_SESSION['user_id']);
                $current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

                $reviews = [];
                $reviews_count = 0;

                if ($reviews_stmt = $conn->prepare("
                    SELECT r.id, r.rating, r.comment, r.is_verified, r.created_at, u.username,
                           rr.reply_text, rr.created_at AS reply_date
                    FROM reviews r
                    JOIN users u ON r.user_id = u.id
                    LEFT JOIN review_replies rr ON rr.review_id = r.id
                    WHERE r.product_id = ?
                    ORDER BY r.created_at DESC
                ")) {
                    $reviews_stmt->bind_param("i", $product_id);
                    $reviews_stmt->execute();
                    $reviews_result = $reviews_stmt->get_result();
                    while ($review_row = $reviews_result->fetch_assoc()) {
                        $reviews[] = $review_row;
                    }
                    $reviews_count = count($reviews);
                    $reviews_stmt->close();
                }

                $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

                $canLeaveReview = false;
                $review_error_reason = '';

                if ($is_logged_in) {
                    // Проверка, не оставлял ли пользователь уже отзыв на этот товар
                    if ($existing_review_stmt = $conn->prepare("
                        SELECT id FROM reviews 
                        WHERE user_id = ? AND product_id = ? 
                        LIMIT 1
                    ")) {
                        $existing_review_stmt->bind_param("ii", $current_user_id, $product_id);
                        $existing_review_stmt->execute();
                        $existing_result = $existing_review_stmt->get_result();

                        if ($existing_result && $existing_result->num_rows > 0) {
                            $review_error_reason = 'Вы уже оставили отзыв на этот товар.';
                        } else {
                            // Проверка наличия завершённого заказа (status = completed) с этим товаром
                            if ($orders_stmt = $conn->prepare("
                                SELECT COUNT(*) AS cnt
                                FROM orders o
                                JOIN order_items oi ON o.id = oi.order_id
                                WHERE o.user_id = ?
                                  AND o.status = 'completed'
                                  AND oi.product_id = ?
                            ")) {
                                $orders_stmt->bind_param("ii", $current_user_id, $product_id);
                                $orders_stmt->execute();
                                $orders_result = $orders_stmt->get_result();
                                $orders_row = $orders_result ? $orders_result->fetch_assoc() : null;
                                $orders_stmt->close();

                                if ($orders_row && intval($orders_row['cnt']) > 0) {
                                    $canLeaveReview = true;
                                } else {
                                    $review_error_reason = 'Оставить отзыв можно только после покупки товара.';
                                }
                            } else {
                                $review_error_reason = 'Не удалось проверить наличие заказа с этим товаром.';
                            }
                        }

                        $existing_review_stmt->close();
                    } else {
                        $review_error_reason = 'Не удалось проверить наличие уже существующего отзыва.';
                    }
                } else {
                    $review_error_reason = 'Только зарегистрированные пользователи могут оставлять отзывы.';
                }
                ?>

                <div class="product-details">
                    <div class="details-left">
                        <div class="carousel">

                            <input type="radio" name="slider" id="img1" checked>
                            <input type="radio" name="slider" id="img2">
                            <input type="radio" name="slider" id="img3">
                            <input type="radio" name="slider" id="img4">

                            <div class="image-wrapper s1">
                                <img src="<?= htmlspecialchars($row['image_url']); ?>" class="detail-img" alt="<?= htmlspecialchars($row['name']); ?>" data-full="<?= htmlspecialchars($row['image_url']); ?>">
                                <i class="fas fa-search-plus zoom-icon"></i>
                            </div>
                            <div class="image-wrapper s2">
                                <img src="<?= htmlspecialchars($row['img_detail_url1']); ?>" class="detail-img" alt="<?= htmlspecialchars($row['name']); ?> - детали 1" data-full="<?= htmlspecialchars($row['img_detail_url1']); ?>">
                                <i class="fas fa-search-plus zoom-icon"></i>
                            </div>
                            <div class="image-wrapper s3">
                                <img src="<?= htmlspecialchars($row['img_detail_url2']); ?>" class="detail-img" alt="<?= htmlspecialchars($row['name']); ?> - детали 2" data-full="<?= htmlspecialchars($row['img_detail_url2']); ?>">
                                <i class="fas fa-search-plus zoom-icon"></i>
                            </div>
                            <div class="image-wrapper s4">
                                <img src="<?= htmlspecialchars($row['img_detail_url3']); ?>" class="detail-img" alt="<?= htmlspecialchars($row['name']); ?> - детали 3" data-full="<?= htmlspecialchars($row['img_detail_url3']); ?>">
                                <i class="fas fa-search-plus zoom-icon"></i>
                            </div>

                            <label for="img4" class="carousel-arrow left">‹</label>
                            <label for="img2" class="carousel-arrow right">›</label>

                            <div class="carousel-controls">
                                <label for="img1"></label>
                                <label for="img2"></label>
                                <label for="img3"></label>
                                <label for="img4"></label>
                            </div>

                        </div>

                        <button class="price-button" onclick="addToCart(<?php echo $row['id']; ?>)">
                            <span class="price-text">₽ <?php echo $formatted_price; ?></span>
                            <div class="cart-icon-wrapper">
                                <span class="cart-text">В корзину</span>
                                <div class="cart-icon-button">
                                    <img src="icons/shopping-basket.png" alt="Корзина">
                                </div>
                            </div>
                        </button>
                    </div>
                    <div class="details-right">
                        <h1><?php echo htmlspecialchars($row['name']); ?></h1>
                        <p><strong>Описание:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                        <p><strong>Состав:</strong> <?php echo htmlspecialchars($row['compound']); ?></p>
                        <p><strong>Условия хранения:</strong> <?php echo htmlspecialchars($row['storage_conditions']); ?></p>
                    </div>
                </div>

                <section class="reviews-section">
                    <div class="reviews-header">
                        <div>
                            <h2>Отзывы о товаре</h2>
                            <p class="reviews-subtitle">Ваши впечатления помогают другим покупателям сделать выбор</p>
                        </div>
                        <div class="reviews-header-right">
                            <span class="reviews-count">
                                <?php echo $reviews_count > 0 ? $reviews_count . ' отзыв(ов)' : 'Пока нет отзывов'; ?>
                            </span>
                            <?php if ($reviews_count > 0): ?>
                                <button class="review-toggle-btn" id="reviewsToggleBtn">
                                    <span>Показать отзывы</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($reviews_count === 0): ?>
                        <p class="reviews-empty">Отзывов пока нет — будьте первым, кто поделится своим мнением!</p>
                    <?php else: ?>
                        <div class="reviews-wrapper" id="reviewsWrapper">
                            <?php foreach ($reviews as $review): ?>
                                <article class="review-card">
                                    <header class="review-header">
                                        <div class="review-user">
                                            <div class="review-avatar">
                                                <span>
                                                    <?php
                                                    $username = $review['username'];
                                                    $firstLetter = function_exists('mb_substr')
                                                        ? mb_substr($username, 0, 1, 'UTF-8')
                                                        : substr($username, 0, 1);
                                                    echo strtoupper(htmlspecialchars($firstLetter));
                                                    ?>
                                                </span>
                                            </div>
                                            <div>
                                                <p class="review-username">
                                                    <?php echo htmlspecialchars($review['username']); ?>
                                                </p>
                                                <p class="review-date">
                                                    <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="review-meta">
                                            <div class="review-rating" aria-label="Оценка <?php echo intval($review['rating']); ?> из 5">
                                                <?php
                                                $rating = intval($review['rating']);
                                                for ($i = 1; $i <= 5; $i++):
                                                    $isFilled = $i <= $rating;
                                                    ?>
                                                    <i class="<?php echo $isFilled ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if (!empty($review['is_verified'])): ?>
                                                <span class="review-verified-badge">
                                                    <i class="fas fa-check-circle"></i>
                                                    Проверенная покупка
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </header>

                                    <p class="review-text">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </p>

                                    <?php if (!empty($review['reply_text'])): ?>
                                        <div class="admin-reply">
                                            <div class="admin-reply-avatar">
                                                <i class="fas fa-store"></i>
                                            </div>
                                            <div class="admin-reply-body">
                                                <div class="admin-reply-header">
                                                    <span class="admin-reply-name">Charka Shop</span>
                                                    <span class="admin-reply-badge"><i class="fas fa-shield-halved"></i> Магазин</span>
                                                    <span class="admin-reply-date"><?php echo date('d.m.Y', strtotime($review['reply_date'])); ?></span>
                                                </div>
                                                <p class="admin-reply-text"><?php echo nl2br(htmlspecialchars($review['reply_text'])); ?></p>
                                                <?php if ($is_admin): ?>
                                                    <button class="admin-edit-reply-btn" onclick="openReplyModal(<?php echo intval($review['id']); ?>, <?php echo json_encode($review['reply_text']); ?>)">
                                                        <i class="fas fa-pen"></i> Изменить ответ
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php elseif ($is_admin): ?>
                                        <div class="admin-reply-action">
                                            <button class="admin-reply-btn" onclick="openReplyModal(<?php echo intval($review['id']); ?>, '')">
                                                <i class="fas fa-reply"></i> Ответить
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="reviews-actions">
                        <?php if ($canLeaveReview): ?>
                            <button class="open-review-modal-btn" onclick="openReviewModal()">
                                <i class="fas fa-pen-to-square"></i>
                                Написать отзыв
                            </button>
                        <?php else: ?>
                            <p class="review-permission-hint">
                                <?php echo htmlspecialchars($review_error_reason); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ($canLeaveReview): ?>
                    <div id="reviewModal" class="review-modal">
                        <div class="review-modal-content">
                            <button type="button" class="review-modal-close" onclick="closeReviewModal()">&times;</button>
                            <h2>
                                <i class="fas fa-star-half-stroke"></i>
                                Ваш отзыв о товаре
                            </h2>
                            <p class="review-modal-subtitle">
                                Поделитесь своим честным впечатлением — это сильно помогает другим покупателям.
                            </p>

                            <form id="reviewForm">
                                <div class="review-form-group">
                                    <label>
                                        <i class="fas fa-user"></i>
                                        Ваш логин
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" disabled>
                                </div>

                                <div class="review-form-group">
                                    <label for="review-rating">
                                        <i class="fas fa-star"></i>
                                        Ваша оценка
                                    </label>
                                    <div class="review-stars-input" id="reviewStarsInput">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <button type="button" class="review-star-btn" data-value="<?php echo $i; ?>">
                                                <i class="far fa-star"></i>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="rating" id="review-rating" value="5">
                                </div>

                                <div class="review-form-group">
                                    <label for="review-text">
                                        <i class="fas fa-comment-dots"></i>
                                        Отзыв
                                    </label>
                                    <textarea id="review-text" name="comment" rows="5" required placeholder="Опишите, что вам понравилось или не понравилось в этом товаре"></textarea>
                                </div>

                                <p class="review-verified-note">
                                    <i class="fas fa-shield-alt"></i>
                                    Отзыв будет отмечен как «Проверенная покупка», так как товар реально был заказан и получен.
                                </p>

                                <button type="submit" class="submit-review-btn" id="submitReviewBtn">
                                    <i class="fas fa-paper-plane"></i>
                                    Отправить отзыв
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
            } else {
                echo "Товар не найден.";
            }
            $stmt->close();
        } else {
            echo "ID товара не указан.";
        }
        $conn->close();
        ?>
    </main>

    <?php if ($is_admin): ?>
    <div id="replyModal" class="review-modal">
        <div class="review-modal-content">
            <button type="button" class="review-modal-close" onclick="closeReplyModal()">&times;</button>
            <h2><i class="fas fa-reply"></i> Ответ от Charka Shop</h2>
            <p class="review-modal-subtitle">Ваш ответ увидят все посетители под отзывом покупателя.</p>
            <div class="review-form-group">
                <label><i class="fas fa-comment-dots"></i> Текст ответа</label>
                <textarea id="replyText" rows="5" placeholder="Напишите ответ на отзыв..." maxlength="2000"></textarea>
            </div>
            <div style="display:flex;gap:12px;margin-top:16px;">
                <button class="open-review-modal-btn" id="submitReplyBtn" onclick="submitReply()">
                    <i class="fas fa-paper-plane"></i> Опубликовать ответ
                </button>
                <button class="review-modal-close-btn" onclick="closeReplyModal()">Отмена</button>
            </div>
            <input type="hidden" id="replyReviewId" value="">
        </div>
    </div>
    <?php endif; ?>

    <div id="imageModal" class="modal">
        <span class="modal-close" id="modalCloseBtn">&times;</span>
        <button class="modal-nav modal-nav-left" id="modalPrev">&#8249;</button>
        <img class="modal-content" id="modalImage">
        <button class="modal-nav modal-nav-right" id="modalNext">&#8250;</button>
        <div class="modal-counter" id="modalCounter"></div>
    </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            const carousel = document.querySelector('.carousel');
            if (carousel) {
                const leftArrow = carousel.querySelector('.carousel-arrow.left');
                const rightArrow = carousel.querySelector('.carousel-arrow.right');
                const radioButtons = carousel.querySelectorAll('input[type="radio"]');

                function getCurrentSlide() {
                    for (let i = 0; i < radioButtons.length; i++) {
                        if (radioButtons[i].checked) {
                            return i;
                        }
                    }
                    return 0;
                }

                function updateArrows() {
                    const current = getCurrentSlide();
                    const prev = (current - 1 + radioButtons.length) % radioButtons.length;
                    const next = (current + 1) % radioButtons.length;

                    leftArrow.setAttribute('for', radioButtons[prev].id);
                    rightArrow.setAttribute('for', radioButtons[next].id);
                }

                radioButtons.forEach(radio => {
                    radio.addEventListener('change', updateArrows);
                });

                updateArrows();
            }

            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const modalClose = document.getElementById('modalCloseBtn');
            const modalPrev = document.getElementById('modalPrev');
            const modalNext = document.getElementById('modalNext');
            const modalCounter = document.getElementById('modalCounter');
            const images = document.querySelectorAll('.detail-img');
            let currentModalIndex = 0;

            function openModal(index) {
                currentModalIndex = index;
                const src = images[currentModalIndex].getAttribute('data-full') || images[currentModalIndex].src;
                modalImg.src = src;
                modalImg.style.animation = 'none';
                modalImg.offsetHeight;
                modalImg.style.animation = 'zoomIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                modalCounter.textContent = (currentModalIndex + 1) + ' / ' + images.length;
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }

            function showPrev() {
                openModal((currentModalIndex - 1 + images.length) % images.length);
            }

            function showNext() {
                openModal((currentModalIndex + 1) % images.length);
            }

            if (modal && modalImg && images.length > 0) {
                images.forEach((img, index) => {
                    img.addEventListener('click', function() {
                        openModal(index);
                    });
                });

                if (modalClose) modalClose.addEventListener('click', closeModal);
                if (modalPrev) modalPrev.addEventListener('click', function(e) { e.stopPropagation(); showPrev(); });
                if (modalNext) modalNext.addEventListener('click', function(e) { e.stopPropagation(); showNext(); });

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal();
                });

                document.addEventListener('keydown', function(e) {
                    if (!modal.classList.contains('active')) return;
                    if (e.key === 'Escape') closeModal();
                    if (e.key === 'ArrowLeft') showPrev();
                    if (e.key === 'ArrowRight') showNext();
                });

                // Свайп на мобильных
                let touchStartX = 0;
                modal.addEventListener('touchstart', function(e) {
                    touchStartX = e.changedTouches[0].clientX;
                }, { passive: true });
                modal.addEventListener('touchend', function(e) {
                    const diff = touchStartX - e.changedTouches[0].clientX;
                    if (Math.abs(diff) > 50) diff > 0 ? showNext() : showPrev();
                }, { passive: true });
            }

            initReviewsSection();
        });

        const productId = <?php echo isset($product_id) ? intval($product_id) : 'null'; ?>;
        const canLeaveReview = <?php echo isset($canLeaveReview) && $canLeaveReview ? 'true' : 'false'; ?>;

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

        // Функция добавления в корзину
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
        
        // Раздел отзывов
        function initReviewsSection() {
            const wrapper = document.getElementById('reviewsWrapper');
            const toggleBtn = document.getElementById('reviewsToggleBtn');
            const reviewModal = document.getElementById('reviewModal');
            const reviewForm = document.getElementById('reviewForm');
            const starsInput = document.getElementById('reviewStarsInput');

            if (toggleBtn && wrapper) {
                toggleBtn.addEventListener('click', function() {
                    const isOpen = wrapper.classList.toggle('open');
                    const textSpan = toggleBtn.querySelector('span');
                    const icon = toggleBtn.querySelector('i');

                    if (textSpan) {
                        textSpan.textContent = isOpen ? 'Скрыть отзывы' : 'Показать отзывы';
                    }
                    if (icon) {
                        icon.classList.toggle('rotated', isOpen);
                    }
                });
            }

            if (starsInput) {
                const starButtons = starsInput.querySelectorAll('.review-star-btn');
                const ratingInput = document.getElementById('review-rating');

                const setRating = (value) => {
                    if (!ratingInput) return;
                    ratingInput.value = value;
                    starButtons.forEach((btn) => {
                        const starValue = parseInt(btn.dataset.value, 10);
                        const icon = btn.querySelector('i');
                        if (!icon) return;
                        if (starValue <= value) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }
                    });
                };

                starButtons.forEach((btn) => {
                    btn.addEventListener('click', function() {
                        const value = parseInt(this.dataset.value, 10) || 5;
                        setRating(value);
                    });
                });

                // Начальное состояние
                setRating(parseInt(document.getElementById('review-rating').value, 10) || 5);
            }

            if (reviewForm && canLeaveReview && productId) {
                reviewForm.addEventListener('submit', function(event) {
                    event.preventDefault();

                    const submitBtn = document.getElementById('submitReviewBtn');
                    if (!submitBtn) return;

                    const formData = new FormData(reviewForm);
                    formData.append('product_id', productId);

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправляем...';

                    fetch('submit_review.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            closeReviewModal();
                            location.reload();
                        } else {
                            alert(data.message || 'Не удалось отправить отзыв. Попробуйте позже.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить отзыв';
                        }
                    })
                    .catch(() => {
                        alert('Произошла ошибка при отправке отзыва. Попробуйте позже.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить отзыв';
                    });
                });
            }

            if (reviewModal) {
                reviewModal.addEventListener('click', function(e) {
                    if (e.target === reviewModal) {
                        closeReviewModal();
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && reviewModal.classList.contains('open')) {
                        closeReviewModal();
                    }
                });
            }
        }

        function openReviewModal() {
            const reviewModal = document.getElementById('reviewModal');
            if (reviewModal) {
                reviewModal.classList.add('open');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeReviewModal() {
            const reviewModal = document.getElementById('reviewModal');
            if (reviewModal) {
                reviewModal.classList.remove('open');
                document.body.style.overflow = 'auto';
            }
        }

        function openReplyModal(reviewId, existingText) {
            const modal = document.getElementById('replyModal');
            if (!modal) return;
            document.getElementById('replyReviewId').value = reviewId;
            document.getElementById('replyText').value = existingText || '';
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeReplyModal() {
            const modal = document.getElementById('replyModal');
            if (modal) {
                modal.classList.remove('open');
                document.body.style.overflow = 'auto';
            }
        }

        function submitReply() {
            const reviewId = document.getElementById('replyReviewId').value;
            const replyText = document.getElementById('replyText').value.trim();
            const btn = document.getElementById('submitReplyBtn');

            if (!replyText) {
                alert('Введите текст ответа');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправляем...';

            const formData = new FormData();
            formData.append('review_id', reviewId);
            formData.append('reply_text', replyText);

            fetch('submit_reply.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeReplyModal();
                    location.reload();
                } else {
                    alert(data.message || 'Ошибка при сохранении ответа');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Опубликовать ответ';
                }
            })
            .catch(() => {
                alert('Ошибка соединения');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Опубликовать ответ';
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const rm = document.getElementById('replyModal');
            if (rm) {
                rm.addEventListener('click', function(e) {
                    if (e.target === rm) closeReplyModal();
                });
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && rm.classList.contains('open')) closeReplyModal();
                });
            }
        });
    </script>

</body>
</html>
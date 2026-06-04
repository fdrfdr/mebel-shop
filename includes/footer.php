<?php

$settings_query = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_UNIQUE);

$site_name = $settings_query['site_name']['value'] ?? 'FedosikShop';
$phone = $settings_query['contact_phone']['value'] ?? '8 800 000-00-00';
$email = $settings_query['contact_email']['value'] ?? 'info@shop.ru';
?>

</main>
<footer class="bg-white border-top mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand fw-bold fs-4 text-dark mb-3 d-block" href="index.php">
                    <?= htmlspecialchars($site_name) ?>
                </a>
                <p class="text-muted small lh-lg">
                    Мы создаем уют в вашем доме с 2024 года. <br>
                    Только проверенные материалы, современный дизайн и бережная доставка до вашей двери.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-dark fs-5"><i class="bi bi-telegram"></i></a>
                    <a href="#" class="text-dark fs-5"><i class="bi bi-whatsapp"></i></a>
                    <a href="#" class="text-dark fs-5"><i class="bi bi-vk"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="fw-bold mb-3">Покупателям</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="shop.php" class="text-muted text-decoration-none">Каталог товаров</a></li>
                    <li class="mb-2"><a href="about.php" class="text-muted text-decoration-none">О компании</a></li>
                    <li class="mb-2"><a href="#" class="text-muted text-decoration-none text-nowrap">Связь: <?= htmlspecialchars($email) ?></a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="fw-bold mb-3">Комнаты</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="shop.php?room=Гостиная" class="text-muted text-decoration-none">Гостиная</a></li>
                    <li class="mb-2"><a href="shop.php?room=Спальня" class="text-muted text-decoration-none">Спальня</a></li>
                    <li class="mb-2"><a href="shop.php?room=Кухня" class="text-muted text-decoration-none">Кухня</a></li>
                    <li class="mb-2"><a href="shop.php?room=Офис" class="text-muted text-decoration-none">Кабинет</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3">Будьте в курсе</h6>
                <p class="text-muted small mb-3">Подпишитесь на скидки и новинки:</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control border-end-0 rounded-start-pill ps-3" placeholder="Ваш e-mail" style="font-size: 0.9rem;">
                    <button class="btn btn-warning rounded-end-pill px-4 fw-bold" type="button">OK</button>
                </div>
                <div class="small">
                    <div class="text-muted">Горячая линия:</div>
                    <div class="fw-bold fs-5 text-nowrap">
                        <?= htmlspecialchars($phone) ?>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4 opacity-10">

        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-muted small mb-0">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($site_name) ?>. Все права защищены.
                </p>
            </div>
        </div>
    </div>
</footer>



<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

function updateMiniCart() {
    fetch('cart_fetch.php?t=' + Date.now())
    .then(res => res.text())
    .then(html => {
        const desktopCart = document.getElementById('cart-mini-list');
        const mobileCart = document.getElementById('cart-mini-list-mobile');
        if (desktopCart) desktopCart.innerHTML = html;
        if (mobileCart) mobileCart.innerHTML = html;
    });
}


function toggleWishlist(productId, element) {
    if (element) {
        const dropdown = element.closest('.dropdown-menu');
        if (dropdown) {
            dropdown.addEventListener('click', (e) => e.stopPropagation());
        }
    }

    fetch('wishlist_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const isAdded = data.action === 'added';
            
            const allBtns = document.querySelectorAll(`[onclick*="toggleWishlist(${productId}"]`);
            
            allBtns.forEach(btn => {
                const icon = btn.querySelector('i');
                if (isAdded) {
                    btn.classList.add('active');
                    if (icon) {
                        icon.classList.replace('bi-heart', 'bi-heart-fill');
                        icon.classList.add('text-danger');
                    }
                } else {
                    btn.classList.remove('active');
                    if (icon) {
                        icon.classList.replace('bi-heart-fill', 'bi-heart');
                        icon.classList.remove('text-danger');
                    }
                }
            });

            document.querySelectorAll('.wish-badge').forEach(badge => {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline-block' : 'none';
            });

            updateMiniWishlist();

        } else if (data.message === 'not_logged_in') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Внимание',
                    text: 'Войдите, чтобы сохранять товары',
                    icon: 'warning',
                    confirmButtonColor: '#ffc107'
                }).then(() => { window.location.href = 'login.php'; });
            } else {
                window.location.href = 'login.php';
            }
        }
    })
    .catch(err => console.error('Ошибка JS:', err));
}

function updateMiniWishlist() {
    fetch('includes/get_wishlist_json.php?t=' + Date.now())
    .then(r => r.json())
    .then(items => {
        const containers = [
            document.getElementById('wishlist-mini-list'),
            document.getElementById('wishlist-mini-list-mobile')
        ];
        let html = items.length === 0 
            ? '<p class="text-center text-muted small py-3">Тут пока пусто</p>'
            : items.map(item => `
                <div class="d-flex align-items-center mb-3 px-1">
                    <img src="${(item.image && item.image.indexOf('http') === 0) ? item.image : 'images/' + item.image}" class="rounded-3 me-2" style="width: 45px; height: 45px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-truncate" style="max-width: 140px;">${item.name}</div>
                        <div class="small text-warning">${parseInt(item.price).toLocaleString()} ₽</div>
                    </div>
                    <button class="btn btn-sm text-muted border-0" onclick="toggleWishlist(${item.id}, this)">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>`).join('') + '<hr><a href="profile.php?tab=wishlist" class="btn btn-dark btn-sm w-100 rounded-pill py-2 fw-bold">Смотреть всё</a>';

        document.querySelectorAll('.wish-badge').forEach(badge => {
            badge.textContent = items.length;
            badge.style.display = items.length > 0 ? 'inline-block' : 'none';
        });
        containers.forEach(c => { if(c) c.innerHTML = html; });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updateMiniWishlist();
});

window.addToCart = function(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('cart_action.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            updateBadgeAndCart(data.total_count);
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Добавлено!',
                    text: 'Товар уже в корзине',
                    icon: 'success',
                    timer: 1000,
                    showConfirmButton: false
                });
            } else {
                alert('Товар добавлен в корзину');
            }
        }
    })
    .catch(err => console.error('Ошибка добавления:', err));
};

window.removeFromCart = function(productId, currentQty) {
    const formData = new FormData();
    formData.append('product_id', productId);
    const newQty = currentQty > 1 ? currentQty - 1 : 0;
    formData.append('quantity', newQty);

    fetch('cart_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            updateBadgeAndCart(data.total_count);
        }
    })
    .catch(err => console.error('Ошибка удаления:', err));
};

window.clearCart = function() {
    Swal.fire({
        title: 'Очистить корзину?',
        text: "Все выбранные товары будут удалены",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#000',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Да, очистить',
        cancelButtonText: 'Отмена',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('clear_all', '1');

            fetch('cart_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    updateBadgeAndCart(0);
                    Swal.fire({
                        title: 'Готово!',
                        text: 'Корзина пуста',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            })
            .catch(err => console.error('Ошибка очистки:', err));
        }
    });
};

function updateBadgeAndCart(totalCount) {
    document.querySelectorAll('.cart-badge').forEach(b => {
        b.textContent = totalCount;
        b.style.display = totalCount > 0 ? 'inline-block' : 'none';
    });
    updateMiniCart();
    
    if (window.location.pathname.includes('cart.php')) {
        location.reload();
    }
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-yandex') || e.target.closest('.add-to-cart-btn');
    
    if (btn) {
        const productId = btn.getAttribute('data-id');
        if (!productId) return;

        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('cart_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.querySelectorAll('.cart-badge').forEach(b => {
                    b.textContent = data.total_count;
                    b.style.display = data.total_count > 0 ? 'inline-block' : 'none';
                });
                
                updateMiniCart();

                const oldContent = btn.innerHTML;
                const oldClass = btn.classList.contains('btn-yandex') ? 'btn-yandex' : 'btn-warning';
                
                btn.classList.remove(oldClass);
                btn.classList.add('btn-success');
                btn.innerHTML = '✓ Добавлено';
                
                setTimeout(() => {
                    btn.classList.remove('btn-success');
                    btn.classList.add(oldClass);
                    btn.innerHTML = oldContent;
                }, 1500);
            }
        })
        .catch(err => console.error('Ошибка добавления:', err));
    }
});

let searchTimeout;
function performSearch(query, resultsContainer) {
    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        if (resultsContainer.id === 'search-results') resultsContainer.style.display = 'none';
        return;
    }
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetch('search_api.php?q=' + encodeURIComponent(query))
            .then(res => res.text())
            .then(html => {
                resultsContainer.innerHTML = html;
                if (resultsContainer.id === 'search-results') {
                    resultsContainer.style.display = html ? 'block' : 'none';
                }
            });
    }, 300);
}

const desktopInput = document.getElementById('main-search');
const desktopResults = document.getElementById('search-results');
if(desktopInput) {
    desktopInput.addEventListener('input', (e) => performSearch(e.target.value, desktopResults));
}

const mobileInput = document.getElementById('mobile-search-input');
const mobileResults = document.getElementById('mobile-search-results');
if(mobileInput) {
    mobileInput.addEventListener('input', (e) => performSearch(e.target.value, mobileResults));
}

const offcanvasSearch = document.getElementById('offcanvasSearch');
if(offcanvasSearch) {
    offcanvasSearch.addEventListener('shown.bs.offcanvas', () => {
        mobileInput.focus();
    });
}
</script>
</body>
</html>
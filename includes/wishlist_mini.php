<?php

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT p.* FROM products p JOIN wishlist w ON p.id = w.product_id WHERE w.user_id = ? LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $wishes = $stmt->fetchAll();

    if ($wishes):
        foreach ($wishes as $wish): 
            $img_path = $wish['image']; 
            $src = (strpos($img_path, 'http') === 0) ? $img_path : 'images/'.$img_path;
            if (empty($img_path)) $src = 'https://dummyimage.com/100x100/f8f9fa/333.jpg&text=No+Photo';
    ?>
            <div class="d-flex align-items-center mb-3 wish-item-<?= $wish['id'] ?>">
                <img src="<?= $src ?>" class="rounded-3 me-2" style="width: 50px; height: 50px; object-fit: cover;">
                <div class="flex-grow-1">
                    <div class="small fw-bold text-truncate" style="max-width: 140px;"><?= htmlspecialchars($wish['name']) ?></div>
                    <div class="small text-warning"><?= number_format($wish['price'], 0, '.', ' ') ?> ₽</div>
                </div>
                <button class="btn btn-sm text-muted border-0" 
                        onclick="event.stopPropagation(); toggleWishlist(<?= $item['id'] ?>, this)">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        <?php endforeach; ?>
        <hr>
        <a href="profile.php?tab=wishlist" class="btn btn-dark btn-sm w-100 rounded-pill py-2 fw-bold">Смотреть всё избранное</a>
    <?php else: ?>
        <p class="text-center text-muted small py-3">В избранном пока ничего нет</p>
    <?php endif; 
} else { ?>
    <div class="text-center py-3">
        <p class="small text-muted mb-3">Войдите, чтобы сохранять товары</p>
        <a href="login.php" class="btn btn-primary btn-sm rounded-pill px-4">Войти</a>
    </div>
<?php } ?>
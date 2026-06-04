<?php
require 'includes/db.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (mb_strlen($query) < 2) {
    exit; 
}

$sql = "SELECT p.id, p.name, p.price, p.image, cat.name as cat_name 
        FROM products p 
        LEFT JOIN attr_categories cat ON p.category_id = cat.id
        WHERE p.name LIKE ? OR cat.name LIKE ? 
        LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$query%", "%$query%"]); 
$results = $stmt->fetchAll();

if ($results) {
    foreach ($results as $item) {
        $rawPath = str_replace('images/', '', $item['image']);
        $img = (strpos($item['image'], 'http') === 0) ? $item['image'] : 'images/' . $rawPath;
        
        echo '
        <a href="product.php?id='.$item['id'].'" class="search-item d-flex align-items-center p-2 text-decoration-none mb-2 rounded-3">
            <div class="position-relative">
                <img src="'.$img.'" class="rounded-3" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #eee;" onerror="this.src=\'images/no-image.png\'">
            </div>
            <div class="ms-3 flex-grow-1">
                <div class="small fw-bold text-dark lh-sm">'.htmlspecialchars($item['name']).'</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="fw-bold text-warning small">'.number_format($item['price'], 0, '.', ' ').' ₽</span>
                    <span class="text-muted" style="font-size: 10px;">• '.htmlspecialchars($item['cat_name']).'</span>
                </div>
            </div>
            <i class="bi bi-chevron-right text-muted pe-2" style="font-size: 0.8rem;"></i>
        </a>';
    }
    
    echo '<div class="px-3 pb-2 mt-2">
            <div style="height: 1px; background-color: rgba(0,0,0,0.05); margin: 8px 0;"></div>
            <a href="shop.php?search='.urlencode($query).'" 
               class="btn btn-warning w-100 py-2 fw-bold rounded-pill mt-1" 
               style="font-size: 0.75rem; letter-spacing: 0.5px; text-transform: uppercase;">
                Показать все результаты
            </a>
          </div>';
} else {
    echo '<div class="p-4 text-center">
            <div class="mb-2">😕</div>
            <div class="small text-muted">Ничего не нашли по запросу<br><strong>'.htmlspecialchars($query).'</strong></div>
          </div>';
}
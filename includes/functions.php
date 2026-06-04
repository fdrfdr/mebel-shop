<?php
function getColorHex($colorName) {
    $colors = [
        'черный' => '#212121',
        'белый' => '#ffffff',
        'серый' => '#888888',
        'дерево' => '#A47551',
        'коричневый' => '#5d4037',
        'золото' => '#ffd700',
        'прозрачный' => 'rgba(255,255,255,0.5)',
        'синий' => '#0d6efd'
    ];
    return $colors[$colorName] ?? '#ccc';
}
?>
<?php
function formatPrice($price) {
    return '<span class="price-display" data-price="' . number_format($price, 2, '.', '') . '">$' . number_format($price, 2) . '</span>';
}
?>

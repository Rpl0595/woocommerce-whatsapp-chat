<?php
/*
Plugin Name: WhatsApp Chat for WooCommerce
Description: Tambahkan tombol chat WhatsApp di halaman produk dengan rotasi admin dan data produk.
Version: 1.0
Author: SIP Studio
*/

add_action('wp_enqueue_scripts', 'wa_chat_enqueue_styles');
function wa_chat_enqueue_styles() {
    wp_register_style('wa-chat-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_style('wa-chat-style');
}

add_action('woocommerce_single_product_summary', 'wa_chat_button_on_single_product', 35);
function wa_chat_button_on_single_product() {
    global $product;
    if (!$product) return;

    $product_id = $product->get_id();
    $product_name = $product->get_name();
    $product_sku = $product->get_sku();
    $product_permalink = get_permalink($product_id);

    $message = rawurlencode("Halo Admin,
Saya tertarik dengan produk berikut:
ID: $product_id
Nama: $product_name
SKU: $product_sku
Link: $product_permalink");

    // Nomor admin
    $numbers = [
        '6281234567890',
        '6281111111111',
        '6282222222222',
        '6283333333333'
    ];

    $use_redirect = false; // true jika ingin pakai sipstudiophotography.com/redirect

    echo '<div class="wa-chat-wrapper" id="wa-chat-button" data-msg="' . $message . '" data-redirect="' . ($use_redirect ? '1' : '0') . '">';
    echo '</div>';

    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const container = document.getElementById("wa-chat-button");
        const message = container.dataset.msg;
        const useRedirect = container.dataset.redirect === "1";

        const numbers = <?php echo json_encode($numbers); ?>;
        const selected = numbers[new Date().getMinutes() % numbers.length];
        const url = useRedirect
            ? "https://sipstudiophotography.com/redirect"
            : `https://wa.me/${selected}?text=${message}`;

        const btn = document.createElement("a");
        btn.href = url;
        btn.target = "_blank";
        btn.rel = "noopener noreferrer";
        btn.className = "wa-chat-button";
        btn.innerText = "Chat via WhatsApp 📲";

        container.appendChild(btn);
    });
    </script>
    <?php
}

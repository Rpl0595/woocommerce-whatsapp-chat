<?php
/*
Plugin Name: WhatsApp Chat for WooCommerce - Fixed
Plugin URI: https://sipstudiophotography.com
Description: Tambahkan tombol chat WhatsApp di halaman produk WooCommerce dengan rotasi admin dan opsi redirect.
Version: 1.2
Author: SIP Studio
Author URI: https://sipstudiophotography.com
Text Domain: wc-whatsapp-chat
Domain Path: /languages
*/

defined('ABSPATH') || exit;

class WC_WhatsApp_Chat_Fixed {

    public function __construct() {
        // Cek jika WooCommerce aktif
        add_action('plugins_loaded', array($this, 'init_plugin'));
    }

    public function init_plugin() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Tambahkan hooks
        add_action('woocommerce_single_product_summary', array($this, 'add_whatsapp_chat_button'), 35);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function woocommerce_missing_notice() {
        echo '<div class="error notice"><p>';
        printf(
            __('WhatsApp Chat for WooCommerce membutuhkan plugin %sWooCommerce%s untuk berfungsi!', 'wc-whatsapp-chat'),
            '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">',
            '</a>'
        );
        echo '</p></div>';
    }

    public function add_admin_menu() {
        add_options_page(
            __('WhatsApp Chat Settings', 'wc-whatsapp-chat'),
            __('WhatsApp Chat', 'wc-whatsapp-chat'),
            'manage_options',
            'wc-whatsapp-chat',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_numbers');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_enable_redirect');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_redirect_url');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_button_text');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_button_color');
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WhatsApp Chat Settings', 'wc-whatsapp-chat'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_whatsapp_chat');
                do_settings_sections('wc_whatsapp_chat');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="wc_whatsapp_numbers"><?php esc_html_e('Nomor WhatsApp', 'wc-whatsapp-chat'); ?></label>
                        </th>
                        <td>
                            <textarea name="wc_whatsapp_numbers" id="wc_whatsapp_numbers" rows="4" cols="50" class="regular-text"><?php echo esc_textarea(get_option('wc_whatsapp_numbers', "6281234567890\n6281111111111\n6282222222222\n6283333333333")); ?></textarea>
                            <p class="description"><?php esc_html_e('Masukkan nomor WhatsApp untuk rotasi, satu nomor per baris', 'wc-whatsapp-chat'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wc_whatsapp_enable_redirect"><?php esc_html_e('Aktifkan Redirect', 'wc-whatsapp-chat'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="wc_whatsapp_enable_redirect" id="wc_whatsapp_enable_redirect" value="1" <?php checked(1, get_option('wc_whatsapp_enable_redirect')); ?> />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wc_whatsapp_redirect_url"><?php esc_html_e('URL Redirect', 'wc-whatsapp-chat'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="wc_whatsapp_redirect_url" id="wc_whatsapp_redirect_url" value="<?php echo esc_url(get_option('wc_whatsapp_redirect_url', 'https://sipstudiophotography.com/redirect')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wc_whatsapp_button_text"><?php esc_html_e('Teks Tombol', 'wc-whatsapp-chat'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wc_whatsapp_button_text" id="wc_whatsapp_button_text" value="<?php echo esc_attr(get_option('wc_whatsapp_button_text', __('Chat via WhatsApp', 'wc-whatsapp-chat'))); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wc_whatsapp_button_color"><?php esc_html_e('Warna Tombol', 'wc-whatsapp-chat'); ?></label>
                        </th>
                        <td>
                            <input type="color" name="wc_whatsapp_button_color" id="wc_whatsapp_button_color" value="<?php echo esc_attr(get_option('wc_whatsapp_button_color', '#25D366')); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_styles() {
        if (is_product()) {
            wp_enqueue_style(
                'wc-whatsapp-chat',
                plugins_url('assets/css/whatsapp-chat.css', __FILE__),
                array(),
                filemtime(plugin_dir_path(__FILE__) . 'assets/css/whatsapp-chat.css')
            );
        }
    }

    public function add_whatsapp_chat_button() {
        global $product;

        if (!$product) {
            return;
        }

        $product_id = $product->get_id();
        $product_name = $product->get_name();
        $product_sku = $product->get_sku();
        $product_price = $product->get_price_html();
        $product_permalink = get_permalink($product_id);

        // Format pesan
        $message = sprintf(
            __("Halo Admin,\nSaya tertarik dengan produk berikut:\n\n*%s*\n%s%sLink: %s\n\nBisa dibantu informasi lebih lanjut?", 'wc-whatsapp-chat'),
            $product_name,
            $product_sku ? "SKU: " . $product_sku . "\n" : "",
            "Harga: " . strip_tags($product_price) . "\n",
            $product_permalink
        );

        $numbers = array_filter(array_map('trim', explode("\n", get_option('wc_whatsapp_numbers', "6281234567890\n6281111111111\n6282222222222\n6283333333333"))));
        $use_redirect = get_option('wc_whatsapp_enable_redirect');
        $redirect_url = esc_url(get_option('wc_whatsapp_redirect_url', 'https://sipstudiophotography.com/redirect'));
        $button_text = esc_html(get_option('wc_whatsapp_button_text', __('Chat via WhatsApp', 'wc-whatsapp-chat')));
        $button_color = esc_attr(get_option('wc_whatsapp_button_color', '#25D366'));

        if (empty($numbers)) {
            $numbers = array('6281234567890'); // Fallback number
        }

        $random_index = array_rand($numbers);
        $selected_number = $numbers[$random_index];
        $encoded_message = rawurlencode($message);
        $chat_url = $use_redirect ? $redirect_url : "https://wa.me/{$selected_number}?text={$encoded_message}";
        ?>
        <div class="wc-whatsapp-chat-container">
            <a href="<?php echo esc_url($chat_url); ?>" 
               class="wc-whatsapp-chat-btn" 
               target="_blank" 
               rel="noopener noreferrer"
               style="background-color: <?php echo $button_color; ?>">
                <?php echo $button_text; ?> <span class="whatsapp-icon">📲</span>
            </a>
        </div>
        <?php
    }
}

// Inisialisasi plugin
function init_wc_whatsapp_chat() {
    new WC_WhatsApp_Chat_Fixed();
}
add_action('plugins_loaded', 'init_wc_whatsapp_chat');

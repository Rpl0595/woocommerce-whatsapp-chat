<?php
/*
Plugin Name: WhatsApp Chat for WooCommerce - Enhanced
Description: Tambahkan tombol chat WhatsApp di halaman produk WooCommerce dengan rotasi admin dan opsi redirect.
Version: 1.1
Author: SIP Studio
*/

if (!defined('ABSPATH')) {
    exit;
}

class WC_WhatsApp_Chat_Enhanced {

    public function __construct() {
        // Pastikan WooCommerce aktif
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Tambahkan tombol WhatsApp
        add_action('woocommerce_single_product_summary', array($this, 'add_whatsapp_chat_button'), 35);
        
        // Tambahkan menu admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Load CSS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    // Notifikasi jika WooCommerce tidak aktif
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>WhatsApp Chat</strong> membutuhkan plugin <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> untuk berfungsi!</p></div>';
    }

    // Tambahkan menu admin
    public function add_admin_menu() {
        add_options_page(
            'WhatsApp Chat Settings',
            'WhatsApp Chat',
            'manage_options',
            'wc-whatsapp-chat',
            array($this, 'settings_page')
        );
    }

    // Register settings
    public function register_settings() {
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_numbers');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_enable_redirect');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_redirect_url');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_button_text');
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_button_color');
    }

    // Halaman settings
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>WhatsApp Chat Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wc_whatsapp_chat'); ?>
                <?php do_settings_sections('wc_whatsapp_chat'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Nomor WhatsApp (pisahkan dengan koma)</th>
                        <td>
                            <textarea name="wc_whatsapp_numbers" rows="4" cols="50"><?php echo esc_textarea(get_option('wc_whatsapp_numbers', "6281234567890\n6281111111111\n6282222222222\n6283333333333")); ?></textarea>
                            <p class="description">Masukkan nomor WhatsApp untuk rotasi, satu nomor per baris</p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Aktifkan Redirect ke SIP Studio</th>
                        <td>
                            <input type="checkbox" name="wc_whatsapp_enable_redirect" value="1" <?php checked(1, get_option('wc_whatsapp_enable_redirect')); ?> />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">URL Redirect</th>
                        <td>
                            <input type="text" name="wc_whatsapp_redirect_url" value="<?php echo esc_attr(get_option('wc_whatsapp_redirect_url', 'https://sipstudiophotography.com/redirect')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Teks Tombol</th>
                        <td>
                            <input type="text" name="wc_whatsapp_button_text" value="<?php echo esc_attr(get_option('wc_whatsapp_button_text', 'Chat via WhatsApp')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Warna Tombol</th>
                        <td>
                            <input type="color" name="wc_whatsapp_button_color" value="<?php echo esc_attr(get_option('wc_whatsapp_button_color', '#25D366')); ?>" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // Load CSS
    public function enqueue_styles() {
        if (is_product()) {
            wp_enqueue_style(
                'wc-whatsapp-chat',
                plugins_url('assets/css/whatsapp-chat.css', __FILE__),
                array(),
                '1.1'
            );
        }
    }

    // Tambahkan tombol WhatsApp
    public function add_whatsapp_chat_button() {
        global $product;

        if (!$product) return;

        $product_id = $product->get_id();
        $product_name = $product->get_name();
        $product_sku = $product->get_sku();
        $product_price = $product->get_price_html();
        $product_permalink = get_permalink($product_id);

        // Format pesan
        $message = "Halo Admin,%0ASaya tertarik dengan produk berikut:%0A%0A";
        $message .= "*" . rawurlencode($product_name) . "*%0A";
        if ($product_sku) $message .= "SKU: " . rawurlencode($product_sku) . "%0A";
        $message .= "Harga: " . rawurlencode(strip_tags($product_price)) . "%0A";
        $message .= "Link: " . rawurlencode($product_permalink) . "%0A%0A";
        $message .= "Bisa dibantu informasi lebih lanjut?";

        // Dapatkan nomor WhatsApp
        $numbers = array_filter(array_map('trim', explode("\n", get_option('wc_whatsapp_numbers', "6281234567890\n6281111111111\n6282222222222\n6283333333333"))));
        $use_redirect = get_option('wc_whatsapp_enable_redirect');
        $redirect_url = esc_url(get_option('wc_whatsapp_redirect_url', 'https://sipstudiophotography.com/redirect'));
        $button_text = esc_html(get_option('wc_whatsapp_button_text', 'Chat via WhatsApp'));
        $button_color = esc_attr(get_option('wc_whatsapp_button_color', '#25D366'));

        if (empty($numbers) {
            $numbers = ['6281234567890']; // Fallback number
        }

        ?>
        <div id="wc-whatsapp-chat-btn" class="wc-whatsapp-chat-container">
            <script>
            (function() {
                const numbers = <?php echo json_encode($numbers); ?>;
                const msg = "<?php echo $message; ?>";
                const useRedirect = <?php echo $use_redirect ? 'true' : 'false'; ?>;
                const redirectUrl = "<?php echo $redirect_url; ?>";
                const btnColor = "<?php echo $button_color; ?>";
                const btnText = "<?php echo $button_text; ?>";

                // Pilih nomor secara acak
                const index = Math.floor(Math.random() * numbers.length);
                const selected = numbers[index];

                const chatUrl = useRedirect
                    ? redirectUrl
                    : `https://wa.me/${selected}?text=${msg}`;

                const btn = document.createElement('a');
                btn.href = chatUrl;
                btn.className = 'wc-whatsapp-chat-btn';
                btn.target = '_blank';
                btn.rel = 'noopener noreferrer';
                btn.innerHTML = `${btnText} <span class="whatsapp-icon">📲</span>`;
                btn.style.backgroundColor = btnColor;

                document.getElementById('wc-whatsapp-chat-btn').appendChild(btn);
            })();
            </script>
        </div>
        <?php
    }
}

new WC_WhatsApp_Chat_Enhanced();

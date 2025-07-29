<?php
/*
Plugin Name: WooCommerce WhatsApp Chat
Description: Menambahkan tombol chat WhatsApp dengan rotasi admin dan integrasi produk
Version: 1.0
Author: Adera
*/

if (!defined('ABSPATH')) {
    exit;
}

class WC_WhatsApp_Chat {

    public function __construct() {
        // Tambahkan setting admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Tambahkan tombol WhatsApp
        add_action('wp_footer', array($this, 'add_whatsapp_button'));
        
        // Tambahkan shortcode
        add_shortcode('whatsapp_chat_button', array($this, 'whatsapp_chat_shortcode'));
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
        register_setting('wc_whatsapp_chat', 'wc_whatsapp_button_position');
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
                            <textarea name="wc_whatsapp_numbers" rows="4" cols="50"><?php echo esc_textarea(get_option('wc_whatsapp_numbers')); ?></textarea>
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
                        <th scope="row">Posisi Tombol</th>
                        <td>
                            <select name="wc_whatsapp_button_position">
                                <option value="right" <?php selected('right', get_option('wc_whatsapp_button_position', 'right')); ?>>Kanan Bawah</option>
                                <option value="left" <?php selected('left', get_option('wc_whatsapp_button_position')); ?>>Kiri Bawah</option>
                            </select>
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

    // Generate nomor WhatsApp
    private function get_whatsapp_number() {
        $numbers = explode(',', get_option('wc_whatsapp_numbers'));
        $numbers = array_map('trim', $numbers);
        $numbers = array_filter($numbers);
        
        if (empty($numbers)) {
            return '';
        }
        
        // Rotasi nomor
        $count = count($numbers);
        $index = date('z') % $count; // Gunakan hari dalam tahun untuk rotasi
        
        return $numbers[$index];
    }

    // Generate pesan produk
    private function get_product_message($product_id = null) {
        if (!$product_id && is_product()) {
            $product_id = get_the_ID();
        }
        
        if (!$product_id) {
            return '';
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }
        
        $message = "Halo, saya tertarik dengan produk ini:\n\n";
        $message .= "*" . $product->get_name() . "*\n";
        
        if ($product->get_sku()) {
            $message .= "SKU: " . $product->get_sku() . "\n";
        }
        
        $message .= "Link: " . get_permalink($product_id) . "\n\n";
        $message .= "Bisa dibantu informasi lebih lanjut?";
        
        return rawurlencode($message);
    }

    // Tambahkan tombol WhatsApp
    public function add_whatsapp_button() {
        if (get_option('wc_whatsapp_enable_redirect')) {
            $redirect_url = esc_url(get_option('wc_whatsapp_redirect_url', 'https://sipstudiophotography.com/redirect'));
            $chat_url = $redirect_url;
        } else {
            $whatsapp_number = $this->get_whatsapp_number();
            if (empty($whatsapp_number)) {
                return;
            }
            
            $message = $this->get_product_message();
            $chat_url = "https://wa.me/{$whatsapp_number}?text={$message}";
        }
        
        $position = get_option('wc_whatsapp_button_position', 'right');
        $button_text = esc_html(get_option('wc_whatsapp_button_text', 'Chat via WhatsApp'));
        $button_color = esc_attr(get_option('wc_whatsapp_button_color', '#25D366'));
        
        ?>
        <style>
            .wc-whatsapp-chat-btn {
                position: fixed;
                <?php echo $position; ?>: 20px;
                bottom: 20px;
                z-index: 9999;
                background-color: <?php echo $button_color; ?>;
                color: white !important;
                border-radius: 50px;
                padding: 12px 20px;
                text-decoration: none;
                font-weight: bold;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                transition: all 0.3s ease;
            }
            
            .wc-whatsapp-chat-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.3);
            }
            
            .wc-whatsapp-chat-btn i {
                margin-right: 8px;
                font-size: 20px;
            }
            
            @media (max-width: 768px) {
                .wc-whatsapp-chat-btn {
                    <?php echo $position; ?>: 10px;
                    bottom: 10px;
                    padding: 10px 16px;
                    font-size: 14px;
                }
            }
        </style>
        
        <a href="<?php echo esc_url($chat_url); ?>" target="_blank" class="wc-whatsapp-chat-btn">
            <i class="fab fa-whatsapp"></i> <?php echo $button_text; ?>
        </a>
        
        <!-- Load Font Awesome for WhatsApp icon -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <?php
    }

    // Shortcode untuk tombol WhatsApp
    public function whataspp_chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_id' => null,
            'text' => get_option('wc_whatsapp_button_text', 'Chat via WhatsApp'),
            'color' => get_option('wc_whatsapp_button_color', '#25D366')
        ), $atts);
        
        if (get_option('wc_whatsapp_enable_redirect')) {
            $redirect_url = esc_url(get_option('wc_whatsapp_redirect_url', 'https://sipstudiophotography.com/redirect'));
            $chat_url = $redirect_url;
        } else {
            $whatsapp_number = $this->get_whatsapp_number();
            if (empty($whatsapp_number)) {
                return '';
            }
            
            $message = $this->get_product_message($atts['product_id']);
            $chat_url = "https://wa.me/{$whatsapp_number}?text={$message}";
        }
        
        ob_start();
        ?>
        <style>
            .wc-whatsapp-chat-shortcode {
                display: inline-block;
                background-color: <?php echo esc_attr($atts['color']); ?>;
                color: white !important;
                border-radius: 50px;
                padding: 12px 20px;
                text-decoration: none;
                font-weight: bold;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
                margin: 10px 0;
            }
            
            .wc-whatsapp-chat-shortcode:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.3);
            }
            
            .wc-whatsapp-chat-shortcode i {
                margin-right: 8px;
            }
        </style>
        
        <a href="<?php echo esc_url($chat_url); ?>" target="_blank" class="wc-whatsapp-chat-shortcode">
            <i class="fab fa-whatsapp"></i> <?php echo esc_html($atts['text']); ?>
        </a>
        
        <!-- Load Font Awesome for WhatsApp icon -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <?php
        return ob_get_clean();
    }
}

new WC_WhatsApp_Chat();

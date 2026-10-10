<?php
if (!defined('ABSPATH')) exit;
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    register_nav_menus(['primary' => 'Главное меню']);
});
add_action('wp_enqueue_scripts', function () { wp_enqueue_style('volt', get_stylesheet_uri(), [], '1.1.0'); });
add_filter('loop_shop_columns', function () { return 4; });
add_filter('loop_shop_per_page', function () { return 12; });
function volt_device_photo($type = 'laptop', $priority = false) {
    $labels = ['laptop'=>'Ноутбук Apple MacBook Air M2', 'phone'=>'Смартфон Apple iPhone 6', 'audio'=>'Наушники Sony MDR-10RC', 'watch'=>'Умные часы Apple Watch с зелёным ремешком'];
    if (!isset($labels[$type])) $type = 'laptop';
    $url = get_template_directory_uri().'/assets/devices/'.$type.'.jpg';
    return '<img class="product-art device-photo" src="'.esc_url($url).'" alt="'.esc_attr($labels[$type]).'" decoding="async" '.($priority ? 'fetchpriority="high"' : 'loading="lazy"').'>';
}
add_filter('woocommerce_product_get_image', function ($image, $product) {
    if ($product->get_image_id()) return $image;
    return volt_device_photo($product->get_meta('_volt_icon') ?: 'laptop');
}, 10, 2);
add_filter('woocommerce_single_product_image_thumbnail_html', function ($html) {
    global $product;
    return $product && !$product->get_image_id() ? volt_device_photo($product->get_meta('_volt_icon') ?: 'laptop') : $html;
});
add_shortcode('volt_image_credits', function () {
    $path = get_template_directory().'/assets/devices/credits.json';
    $credits = is_readable($path) ? json_decode(file_get_contents($path), true) : [];
    $html = '<p>Фотографии настоящих устройств используются как иллюстрации учебного каталога. Названия VOLT и характеристики товаров демонстрационные.</p><div class="photo-credits">';
    foreach ($credits ?: [] as $credit) {
        $html .= '<article class="news-card"><h2>'.esc_html($credit['device']).'</h2>'.volt_device_photo($credit['key']).'<p>Автор: '.esc_html($credit['author']).'. <a href="'.esc_url($credit['source']).'" target="_blank" rel="noopener noreferrer">Оригинал на Wikimedia Commons ↗</a></p><p>Лицензия: <a href="'.esc_url($credit['license_url']).'" target="_blank" rel="noopener noreferrer">'.esc_html($credit['license']).'</a>. Изображение не редактировалось; использована уменьшенная копия с Wikimedia Commons. Отображение в карточках может обрезать края фотографии.</p></article>';
    }
    return $html.'</div>';
});
add_action('init', function () {
    register_post_type('volt_message', ['label' => 'Обратная связь', 'public' => false, 'show_ui' => true, 'capability_type' => 'post', 'supports' => ['title','editor'], 'menu_icon' => 'dashicons-email']);
});
add_action('admin_post_volt_contact', 'volt_contact');
add_action('admin_post_nopriv_volt_contact', 'volt_contact');
function volt_contact() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'volt_contact')) wp_die('Обновите страницу и попробуйте ещё раз.', '', ['response' => 403]);
    $url = get_permalink((int)get_option('volt_contact_page'));
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if (!empty($_POST['website']) || !$name || !is_email($email) || !$message || empty($_POST['consent']) || strlen($message) > 10000) {
        wp_safe_redirect(add_query_arg('contact', 'error', $url)); exit;
    }
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'local');
    $key = 'volt_contact_'.md5($ip);
    if (get_transient($key)) { wp_safe_redirect(add_query_arg('contact', 'rate', $url)); exit; }
    $id = wp_insert_post(['post_type' => 'volt_message', 'post_status' => 'private', 'post_title' => $name.' — '.$email, 'post_content' => $message], true);
    if (is_wp_error($id) || !$id) { wp_safe_redirect(add_query_arg('contact', 'error', $url)); exit; }
    set_transient($key, 1, 60);
    wp_safe_redirect(add_query_arg('contact', 'saved', $url)); exit;
}
add_shortcode('volt_contact', function () {
    ob_start();
    $status = sanitize_key($_GET['contact'] ?? '');
    if ($status === 'saved') echo '<div class="notice" role="status">Сообщение сохранено. Администратор увидит его в разделе «Обратная связь».</div>';
    elseif ($status) echo '<div class="notice error" role="alert">Проверьте поля и согласие. Между отправками подождите одну минуту.</div>';
    ?>
    <form class="contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="volt_contact"><?php wp_nonce_field('volt_contact'); ?>
      <label>Ваше имя<input name="name" maxlength="100" autocomplete="name" required></label>
      <label>Электронная почта<input name="email" type="email" maxlength="254" autocomplete="email" required></label>
      <label>Сообщение<textarea name="message" rows="6" maxlength="5000" required></textarea></label>
      <div hidden aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
      <label style="display:block"><input type="checkbox" name="consent" value="1" required> Согласен с <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">политикой обработки данных</a></label>
      <button type="submit">Отправить сообщение →</button>
      <p class="demo-note">Учебная форма: обращения сохраняются в панели администратора. Отправка писем не настроена.</p>
    </form>
    <?php return ob_get_clean();
});

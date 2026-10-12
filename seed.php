<?php
// Execute with: php wp-cli.phar eval-file seed.php --path=wordpress
if (PHP_SAPI !== 'cli') exit;
if (!defined('ABSPATH') || !class_exists('WooCommerce')) throw new RuntimeException('Activate WooCommerce before seeding.');
function volt_page($slug,$title,$content='') {
    $old=get_page_by_path($slug);
    if($old) {
        $updated=wp_update_post(['ID'=>$old->ID,'post_title'=>$title,'post_content'=>$content],true);
        if(is_wp_error($updated)) throw new RuntimeException($updated->get_error_message());
        return $old->ID;
    }
    $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$content],true);
    if(is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    return $id;
}
$home=volt_page('home','Главная');
update_option('timezone_string', 'Europe/Moscow');
update_option('woocommerce_coming_soon', 'no');
$sample=get_post(1);
if($sample && in_array($sample->post_title,['Привет, мир!','Hello world!'],true) && strpos($sample->post_content,'Это ваша первая запись')!==false) wp_trash_post(1);
$news=volt_page('news','Новости');
$shop=volt_page('catalog','Каталог');
$contact=volt_page('contacts','Обратная связь','<p>Нужна помощь с выбором? Напишите нам через форму ниже.</p>[volt_contact]');
volt_page('image-credits','Источники фотографий','[volt_image_credits]');
$cart=volt_page('cart','Корзина','[woocommerce_cart]');
$checkout=volt_page('checkout','Оформление учебного заказа','<p>Это демонстрационный магазин. Реальные покупки и платежи недоступны.</p>[woocommerce_checkout]');
$account=volt_page('my-account','Личный кабинет','[woocommerce_my_account]');
$terms=volt_page('terms','Условия использования','<h2>Учебный проект</h2><p>VOLT создан для экзамена по сопровождению программного обеспечения. Товары, цены и предложения демонстрационные. Сайт не является публичной офертой, не принимает оплату и не осуществляет доставку.</p><h2>Использование сайта</h2><p>Можно просматривать каталог, добавлять товары в корзину и отправлять тестовые сообщения. Не указывайте настоящие платёжные реквизиты или чувствительные сведения.</p><h2>Социальные сети</h2><p>Ссылки в подвале ведут на главные страницы социальных платформ. Собственные аккаунты магазина не созданы.</p>');
$privacy=volt_page('privacy','Политика обработки данных','<p>При отправке формы имя, электронная почта и сообщение сохраняются в локальной базе учебного проекта и доступны администратору. Не отправляйте реальные персональные данные в демонстрационную форму.</p><p>Корзина WooCommerce использует необходимые файлы cookie для сохранения выбранных товаров.</p><p>Google Analytics 4 подключена через плагин GA Google Analytics к отдельному ресурсу VOLT. Аналитика учитывает просмотры страниц и взаимодействия с сайтом с помощью аналитических cookies. Рекламные сигналы отключены, посещения администратора не учитываются. Имена, электронная почта и тексты обращений не передаются в аналитику. При публикации сайта эту политику нужно дополнить данными владельца, сроками хранения и порядком удаления данных.</p>');
foreach(['show_on_front'=>'page','page_on_front'=>$home,'page_for_posts'=>$news,'posts_per_page'=>3,'woocommerce_shop_page_id'=>$shop,'woocommerce_cart_page_id'=>$cart,'woocommerce_checkout_page_id'=>$checkout,'woocommerce_myaccount_page_id'=>$account,'woocommerce_terms_page_id'=>$terms,'wp_page_for_privacy_policy'=>$privacy,'volt_contact_page'=>$contact,'woocommerce_currency'=>'RUB','woocommerce_default_country'=>'RU','woocommerce_price_num_decimals'=>0,'woocommerce_price_thousand_sep'=>' ','woocommerce_price_decimal_sep'=>',','woocommerce_currency_pos'=>'right_space','blogdescription'=>'Электроника для ваших идей','woocommerce_allow_tracking'=>'no'] as $key=>$value) update_option($key,$value);
$menu=wp_get_nav_menu_object('Главное меню');
$menu_id=$menu ? $menu->term_id : wp_create_nav_menu('Главное меню');
if(!wp_get_nav_menu_items($menu_id)) foreach(['Главная'=>$home,'Каталог'=>$shop,'Новости'=>$news,'Контакты'=>$contact] as $title=>$id) wp_update_nav_menu_item($menu_id,0,['menu-item-title'=>$title,'menu-item-object'=>'page','menu-item-object-id'=>$id,'menu-item-type'=>'post_type','menu-item-status'=>'publish']);
set_theme_mod('nav_menu_locations',['primary'=>$menu_id]);
$cats=[];
foreach(['laptops'=>'Ноутбуки','smartphones'=>'Смартфоны','audio'=>'Аудио','watches'=>'Умные часы'] as $slug=>$name){$term=term_exists($slug,'product_cat');if(!$term)$term=wp_insert_term($name,'product_cat',['slug'=>$slug]);if(is_wp_error($term))throw new RuntimeException($term->get_error_message());$cats[$slug]=(int)$term['term_id'];}
$items=[
 ['VOLT-001','Ноутбук VOLT Air 14',64990,'laptops','laptop','Лёгкий ноутбук для учёбы и работы.','Экран 14″ IPS, 16 ГБ памяти, SSD 512 ГБ. Демонстрационные характеристики.'],
 ['VOLT-002','Смартфон VOLT Nova',29990,'smartphones','phone','Связь, фото и любимые приложения.','Экран 6,5″, память 128 ГБ, аккумулятор 5000 мА·ч. Демонстрационные характеристики.'],
 ['VOLT-003','Наушники VOLT Studio',7990,'audio','audio','Музыка без лишнего шума.','Беспроводное подключение, мягкие амбушюры, до 30 часов работы. Демонстрационные характеристики.'],
 ['VOLT-004','Часы VOLT Pulse',9990,'watches','watch','Ваш ежедневный ритм на запястье.','Уведомления, спортивные режимы, цветной дисплей. Демонстрационные характеристики.'],
 ['VOLT-005','Ноутбук VOLT Pro 16',99990,'laptops','laptop','Больше пространства для творчества.','Экран 16″, 32 ГБ памяти, SSD 1 ТБ. Демонстрационные характеристики.'],
 ['VOLT-006','Смартфон VOLT Mini',19990,'smartphones','phone','Компактный помощник на каждый день.','Экран 6,1″, память 128 ГБ, NFC. Демонстрационные характеристики.'],
 ['VOLT-007','Наушники VOLT Focus',4990,'audio','audio','Комфортный звук для работы и прогулок.','Bluetooth, складная конструкция, встроенный микрофон. Демонстрационные характеристики.'],
 ['VOLT-008','Часы VOLT Active',6990,'watches','watch','Для прогулок и повседневных задач.','Шагомер, уведомления, сменный ремешок. Демонстрационные характеристики.'],
];
foreach($items as $r){if(wc_get_product_id_by_sku($r[0]))continue;$p=new WC_Product_Simple();$p->set_name($r[1]);$p->set_sku($r[0]);$p->set_regular_price((string)$r[2]);$p->set_category_ids([$cats[$r[3]]]);$p->set_short_description($r[5]);$p->set_description('<p>'.$r[6].'</p><p>Учебный товар. Реальная продажа не осуществляется.</p>');$p->set_status('publish');$p->set_manage_stock(true);$p->set_stock_quantity(10);$p->update_meta_data('_volt_icon',$r[4]);$p->save();}
$stories=[
 ['choose-laptop','Как выбрать ноутбук для учёбы','Для документов и браузера важны удобная клавиатура, хороший экран и автономность. При сравнении моделей учитывайте массу устройства, объём памяти и набор разъёмов. Если планируете работать с графикой, требования к производительности будут выше. Сначала определите свои задачи, затем сравните характеристики.'],
 ['headphones-guide','Наушники: комфорт важнее громкости','Проверьте посадку и удобство амбушюр. Для видеозвонков полезен хороший микрофон, а в поездках пригодится шумоподавление. Обращайте внимание на время работы и возможность подключения кабелем. Слушайте музыку на умеренной громкости.'],
 ['smartphone-memory','Сколько памяти нужно смартфону','Фотографии, видео и игры занимают больше места, чем переписка и документы. Оцените объём своих файлов и оставьте запас. Для переноса данных между устройствами используйте резервные копии.'],
 ['watch-features','Что умеют умные часы','Часы помогают видеть уведомления, отслеживать активность и пользоваться таймером. Перед выбором проверьте совместимость с телефоном, удобство ремешка и время работы. Показатели бытовых устройств не заменяют медицинские измерения.'],
 ['workspace','Пять деталей удобного рабочего места','Установите экран на комфортной высоте, позаботьтесь об освещении и оставьте место для движения. Внешняя клавиатура и мышь могут сделать длительную работу удобнее. Продумайте расположение проводов и регулярно делайте перерывы.'],
 ['battery-care','Как бережно обращаться с аккумулятором','Избегайте перегрева и используйте совместимые зарядные устройства. Следуйте инструкции производителя. Если устройство повреждено или аккумулятор вздулся, прекратите использование и обратитесь в сервис.'],
 ['store-launch','Добро пожаловать в VOLT','Мы подготовили учебный магазин электроники: восемь демонстрационных товаров, четыре категории и корзину. Здесь можно изучить характеристики и проверить работу WordPress и WooCommerce. Настоящие заказы и оплата не принимаются.'],
];
foreach($stories as $i=>$s){if(get_page_by_path($s[0],OBJECT,'post'))continue;$id=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_name'=>$s[0],'post_title'=>$s[1],'post_content'=>'<p>'.esc_html($s[2]).'</p>','post_excerpt'=>wp_trim_words($s[2],22),'post_date'=>wp_date('Y-m-d H:i:s',time()-$i*DAY_IN_SECONDS)],true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());}
update_option('permalink_structure','/%postname%/');
flush_rewrite_rules();
// Restore the configured GA4 stream during installation.
$ga_path=__DIR__.'/analytics.json';
if(is_readable($ga_path)) {
    $ga=json_decode(file_get_contents($ga_path),true);
    $ga_id=$ga['measurement_id'] ?? '';
    if(preg_match('/^G-[A-Z0-9]+$/',$ga_id) && class_exists('GA_Google_Analytics')) {
        global $GA_Google_Analytics;
        $options=get_option('gap_options',$GA_Google_Analytics->default_options());
        $options['gap_id']=$ga_id;
        $options['gap_enable']=2;
        $options['gap_location']='header';
        $options['disable_admin']=1;
        $options['admin_area']=0;
        $options['tracker_object']='{"allow_google_signals":false,"allow_ad_personalization_signals":false}';
        update_option('gap_options',$options);
    }
}

echo "Created pages, 8 products, 7 news posts and navigation.\n";

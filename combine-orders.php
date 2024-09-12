<?php
/*
Plugin Name: Combine Orders and Show on New Page
Description: This plugin allows combining orders by selecting them with checkboxes and shows the selected orders on a new page.
Version: 1.5
Author: Your Name
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit;
}

define('DANASH_COURSE_SHOP_VER', '1.0.0');
define('DANASH_COURSE_SHOP_ASSETS', plugin_dir_url(__FILE__).'assets/');
define('DANASH_COURSE_SHOP_VIEW', plugin_dir_url(__FILE__).'view/');
define('DANASH_COURSE_SHOP_CSS', DANASH_COURSE_SHOP_ASSETS .'css/');
define('DANASH_COURSE_SHOP_JS', DANASH_COURSE_SHOP_ASSETS.'js/');
define('DANASH_COURSE_SHOP_IMAGES', DANASH_COURSE_SHOP_ASSETS.'images/');


// اضافه کردن منو به پنل مدیریت
add_action('admin_menu', 'multiple_orders_menu');
// add_action('admin_enqueue_scripts', function(){
//     wp_enqueue_style(
//         'shop_style',
//         DANASH_COURSE_SHOP_CSS . 'login.css',
//         [],
//         WP_DEBUG ? time() : DANASH_COURSE_SHOP_VER
//     );
//     wp_enqueue_script(
//         'shop_script',
//         DANASH_COURSE_SHOP_JS . 'loginjs',
//         [],
//         WP_DEBUG ? time() : DANASH_COURSE_SHOP_VER
//     );
// });
// add_action('wp_enqueue_scripts', 'my_custom_enqueue_scripts');
function save_custom_order_data_to_meta($order_ids, $table_name) {
    foreach ($order_ids as $order_id) {
        // ذخیره نام جدول در متای هر سفارش
        update_post_meta($order_id, '_custom_table_name', sanitize_text_field($table_name));
    }
}
function multiple_orders_menu() {
    add_menu_page(
        'ووکامرس پیشرفته',    // عنوان صفحه
        'ووکامرس پیشرفته',        // نام منو
        'manage_options',       // سطح دسترسی
        'orders-by-ids',        // شناسه منو
        'multiple_orders_page'  // تابع نمایش صفحه
    );
    add_submenu_page(
        'orders-by-ids',      // شناسه منوی اصلی
        'ایجاد جمع بندی کالا ها',   // عنوان صفحه ساب منو
        'ایجاد  جمع بندی کالا ها',          // نام ساب منو
        'manage_options',     // سطح دسترسی
        'submenu-1',          // شناسه ساب منو
        'submenu_1_page'      // تابع نمایش صفحه ساب منو ۱
    );
    add_submenu_page(
        'orders-by-ids',      // شناسه منوی اصلی
        'ویرایش جمع بندی کالا ها',    // عنوان صفحه ساب منو
        'ویرایش جمع بندی کالا ها',         // نام ساب منو
        'manage_options',     // سطح دسترسی
        'submenu-2',     
        'submenu_2_page'    // تابع نمایش صفحه ساب منو ۱
    );
    add_submenu_page(
        'orders-by-ids',      // شناسه منوی اصلی
        'نمایش جمع بندی کالا ها',    // عنوان صفحه ساب منو
        'نمایش جمع بندی کالا ها',      // نام ساب منو
        'manage_options',     // سطح دسترسی
        'custom-order-tables-by-name',
        'display_custom_order_tables_by_name'    // تابع نمایش صفحه ساب منو ۱
    );
    // add_submenu_page(
    //     'orders-by-ids',
    //     'گزارش ماهانه فروش و خرید',
    //     'گزارش ماهانه فروش و خرید',
    //     'manage_options',
    //     'submenu-4',
    //     'submenu_4_page'
    // );

    // add_submenu_page(
    //     'orders-by-ids',
    //     'نمودار خرید و فروش',
    //     'نمودار خرید و فروش',
    //     'manage_options',
    //     'submenu-5',
    //     'submenu_5_page'
    // );
    // add_submenu_page(
    //     'orders-by-ids',
    //     'نمودار خرید هر فروشگاه',
    //     'نمودار خرید هر فروشگاه',
    //     'manage_options',
    //     'submenu-6',
    //     'submenu_6_page'
    // );
    // add_submenu_page(
    //     'orders-by-ids',
    //     'پروفایل اختصاصی هر خریدار',
    //     'پروفایل اختصاصی هر خریدار',
    //     'manage_options',
    //     'submenu-7',
    //     'submenu_7_page'
    // );
    add_submenu_page(
        'orders-by-ids',
        'حاشیه سود',
        'حاشیه سود',
        'manage_options',
        'submenu-8',
        'submenu_8_page'
    );
}



global $custom_order_table_db_version;
$custom_order_table_db_version = '1.4';

// ایجاد جدول سفارشی در دیتابیس
function custom_order_table_install() {
    global $wpdb;
    global $custom_order_table_db_version;

    $table_name = $wpdb->prefix . 'custom_order_tables'; // نام جدول
    $charset_collate = $wpdb->get_charset_collate();

    // کوئری ایجاد جدول
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        table_name varchar(255) NOT NULL,
        order_data longtext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // بررسی ایجاد جدول
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log("خطا: جدول $table_name ایجاد نشد.");
    } else {
        update_option('custom_order_table_db_version', $custom_order_table_db_version);
    }
}
register_activation_hook(__FILE__, 'custom_order_table_install');

// صفحه مدیریت پلاگین برای ذخیره سفارشات
function submenu_1_page() {
    ?>
    <div class="wrap">
        <h1 style="direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">جمع بندی کالا ها بر اساس سفارش های انتخاب شده</h1>
        <form style="direction: rtl;" method="post" action="">
            <label style="font-family: 'B Mitra', sans-serif;font-size: 26px;color: unset;"for="table_name">نام جدول را وارد کنید:</label>
            <input style="font-size: 16px;color: #333;background-color: #f9f9f9;border: 1px solid;border-radius: 5px;" type="text" name="table_name" required><br><br>

            <label style="font-family: 'B Mitra', sans-serif;font-size: 26px;color: unset;" for="order_ids">سفارشات را انتخاب کنید:</label><br>
            <?php
            // گرفتن لیست سفارشات
            $args = array(
                'limit' => -1, // نمایش تمام سفارشات
            );
            $orders = wc_get_orders($args);

            // نمایش چک‌باکس برای هر سفارش
            foreach ($orders as $order) {
                if (!is_a($order, 'WC_Order_Refund')) {
                    echo '<input type="checkbox" name="order_ids[]" value="' . $order->get_id() . '"> سفارش شماره ' . $order->get_id() . ' - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '<br>';
                }
            }
            ?>
            
            <input style="margin-top: 20px;margin-right: 20px;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;" type="submit" name="submit_orders" value="ذخیره">
        </form>
    </div>
    <?php

    if (isset($_POST['submit_orders']) && isset($_POST['order_ids']) && isset($_POST['table_name'])) {
        $table_name_input = sanitize_text_field($_POST['table_name']);
        $order_ids = $_POST['order_ids'];

        // ذخیره نام جدول در متای سفارشات
        save_custom_order_data_to_meta($order_ids, $table_name_input);

        echo '<h2 style="direction: rtl;text-align: center;border: 2px solid;padding: 10px;margin-top: 80px;">جدول با نام ' . esc_html($table_name_input) . ' ذخیره شد.</h2>';
    }
}

// تابع نمایش ساب‌منو برای حذف سفارش‌ها
function submenu_2_page() {
    global $wpdb;

    // دریافت لیست جداول سفارشی از متای سفارشات
    $query = "SELECT DISTINCT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_custom_table_name'";
    $results = $wpdb->get_results($query);

    ?>
    <div class="wrap">
        <h1 style="direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">ویرایش و مدیریت سفارش‌ها</h1>
        
        <!-- جدول نمایش جداول ذخیره شده -->
        <table style="direction: rtl;margin-right: 20px;" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="text-align: right">نام جدول</th>
                    <th style="text-align: right">مجموع مبلغ سفارشات</th>
                    <th style="text-align: right">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($results)) {
                    foreach ($results as $row) {
                        // دریافت مجموع مبلغ سفارشات مربوط به این جدول
                        $table_name = esc_attr($row->meta_value);
                        $query = $wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_custom_table_name' AND meta_value = %s", $table_name);
                        $order_results = $wpdb->get_results($query);

                        $total_sum = 0;
                        foreach ($order_results as $order_row) {
                            $order = wc_get_order($order_row->post_id);
                            if ($order) {
                                $total_sum += $order->get_total();
                            }
                        }

                        ?>
                        <tr>
                            <td><?php echo esc_html($table_name); ?></td>
                            <td><?php echo wc_price($total_sum); ?></td>
                            <td>
                                <!-- دکمه ویرایش که جدول مربوطه را باز می‌کند -->
                                <form method="post" action="">
                                    <input type="hidden" name="edit_table_name" value="<?php echo esc_attr($table_name); ?>">
                                    <input style="padding: 5px 10px;border-radius: 5px;background-color: #0073aa;color: white;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;" type="submit" name="edit_table" value="ویرایش">
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="3" style="text-align:center;">هیچ جدولی یافت نشد</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php
    // نمایش سفارشات برای ویرایش اگر کاربر دکمه ویرایش را زده باشد
    if (isset($_POST['edit_table']) && isset($_POST['edit_table_name'])) {
        $table_name = sanitize_text_field($_POST['edit_table_name']);
        
        // دریافت سفارشات مرتبط با جدول انتخاب شده
        $query = $wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_custom_table_name' AND meta_value = %s", $table_name);
        $order_results = $wpdb->get_results($query);
        
        if (!empty($order_results)) {
            ?>
            <div class="wrap">
                <h2 style="direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 30px;text-align: center;">ویرایش سفارشات برای جدول: <?php echo esc_html($table_name); ?></h2>
                <!-- فرم برای انتخاب سفارش -->
                <form method="post" action="">
                    <label style="font-family: 'B Mitra', sans-serif;">انتخاب سفارش برای ویرایش:</label>
                    <select name="order_id" required>
                        <?php
foreach ($order_results as $row) {
                            $order = wc_get_order($row->post_id);
                            if ($order) {
                                echo '<option value="' . esc_attr($order->get_id()) . '">سفارش شماره ' . esc_html($order->get_id()) . ' - ' . esc_html($order->get_billing_first_name()) . ' ' . esc_html($order->get_billing_last_name()) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <input type="hidden" name="table_name" value="<?php echo esc_attr($table_name); ?>">
                    <input style="margin-top: 20px;padding: 10px 20px;border-radius: 5px;background-color: #0073aa;color: white;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;" type="submit" name="edit_order" value="ویرایش سفارش">
                </form>
            </div>
            <?php
        }
    }

    // نمایش جزئیات سفارش و امکان ویرایش پس از انتخاب سفارش
    if (isset($_POST['edit_order']) && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if ($order) {
            ?>
            <div class="wrap">
                <h3 style="direction: rtl;font-family: 'B Mitra', sans-serif;">ویرایش سفارش شماره <?php echo esc_html($order->get_id()); ?></h3>
                <form method="post" action="">
                    <table style="direction: rtl;margin-right: 20px;" class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="text-align: right">محصول</th>
                                <th style="text-align: right">تعداد</th>
                                <th style="text-align: right">قیمت</th>
                                <th style="text-align: right">حذف</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($order->get_items() as $item_id => $item) {
                                ?>
                                <tr>
                                    <td><?php echo esc_html($item->get_name()); ?></td>
                                    <td>
                                        <input type="number" name="quantities[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($item->get_quantity()); ?>" min="1">
                                    </td>
                                    <td><?php echo wc_price($item->get_total()); ?></td>
                                    <td><input type="checkbox" name="delete_items[<?php echo esc_attr($item_id); ?>]" value="1"></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- بخش اضافه کردن محصول به سفارش -->
                    <h3 style="direction: rtl;font-family: 'B Mitra', sans-serif;">اضافه کردن محصول جدید به سفارش</h3>
                    <label style="font-family: 'B Mitra', sans-serif;">انتخاب محصول:</label>
                    <select name="new_product_id">
                        <?php
                        $products = wc_get_products(array('limit' => -1)); // گرفتن تمام محصولات
                        foreach ($products as $product) {
                            echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                        }
                        ?>
                    </select>
                    <label style="font-family: 'B Mitra', sans-serif;">تعداد:</label>
                    <input type="number" name="new_product_qty" value="1" min="1">
                    
                    <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                    <input style="margin-top: 20px;padding: 10px 20px;border-radius: 5px;background-color: #0073aa;color: whi
te;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;" type="submit" name="save_changes" value="ذخیره تغییرات">
                </form>
            </div>
            <?php
        }
    }

    // پردازش تغییرات در سفارشات
    if (isset($_POST['save_changes']) && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if ($order) {
            $quantities = $_POST['quantities'];
            $delete_items = $_POST['delete_items'] ?? array();
            $new_product_id = intval($_POST['new_product_id']);
            $new_product_qty = intval($_POST['new_product_qty']);

            foreach ($order->get_items() as $item_id => $item) {
                if (isset($delete_items[$item_id])) {
                    // حذف محصول از سفارش
                    $order->remove_item($item_id);
                } elseif (isset($quantities[$item_id])) {
                    // تغییر تعداد محصول
                    $item->set_quantity($quantities[$item_id]);
                    $item->calculate_totals();
                }
            }

            // اضافه کردن محصول جدید
            if ($new_product_id && $new_product_qty > 0) {
                $product = wc_get_product($new_product_id);
                if ($product) {
                    $order->add_product($product, $new_product_qty);
                }
            }

            $order->calculate_totals();
            $order->save();

            echo '<h2 style="direction: rtl;text-align: center;color: green;">تغییرات با موفقیت ذخیره شدند.</h2>';
        }
    }
}

// صفحه ساب منو ۴
function submenu_4_page() {
    ?>
    <div class="wrap">
        <h1 style = "direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">گزارش ماهانه فروش و خرید</h1>
        <form style = "direction: rtl;" method="post" action="">
            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="month">ماه (مثلاً 09):</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="month" id="month" placeholder="MM" required><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="year">سال (مثلاً 2024):</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="year" id="year" placeholder="YYYY" required><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="machine_cost">هزینه ماشین:</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="machine_cost" id="machine_cost" value=""><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="worker_cost">هزینه کارگر:</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="worker_cost" id="worker_cost" value=""><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="sms_cost">هزینه پنل پیامکی:</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="sms_cost" id="sms_cost" value=""><br><br>
            <input style = "direction: rtl;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;" type="submit" name="submit_report" value="محاسبه گزارش ماهانه">
        </form>
    </div>
    
    <?php

    // بررسی اینکه آیا فرم ارسال شده است
    if (isset($_POST['submit_report'])) {
        // دریافت ماه و سال از ورودی
        $month = sanitize_text_field($_POST['month']);
        $year = sanitize_text_field($_POST['year']);

        // دریافت هزینه‌های ورودی
        $machine_cost = floatval($_POST['machine_cost']);
        $worker_cost = floatval($_POST['worker_cost']);
        $sms_cost = floatval($_POST['sms_cost']);

        // تنظیم تاریخ شروع و پایان ماه
        $start_date = $year . '-' . $month . '-01 00:00:00';
        $end_date = date("Y-m-t", strtotime($start_date)) . ' 23:59:59'; // آخرین روز ماه

        // دریافت سفارش‌های مربوط به فروش در ماه انتخابی
        $sales_args = array(
            'limit' => -1, // همه سفارشات
            'date_created' => $start_date . '...' . $end_date, // بازه زمانی
            'status' => 'completed', // فقط سفارشات تکمیل شده
        );
        $sales_orders = wc_get_orders($sales_args);
        $total_sales = 0;

        // محاسبه جمع کل فروش
        foreach ($sales_orders as $order) {
            $total_sales += $order->get_total();
        }

        // فرض می‌کنیم خریدها نیز به‌صورت سفارش ثبت شده‌اند و وضعیت آن‌ها "pending" است.
        // دریافت سفارش‌های مربوط به خرید در ماه انتخابی
        $purchase_args = array(
            'limit' => -1, 
            'date_created' => $start_date . '...' . $end_date, 
            'status' => 'pending', // فقط سفارشات در انتظار
        );
        $purchase_orders = wc_get_orders($purchase_args);
        $total_purchase = 0;

        // محاسبه جمع کل خرید
        foreach ($purchase_orders as $order) {
            $total_purchase += $order->get_total();
        }

        // محاسبه سود ناخالص (فروش - خرید)
        $gross_profit = $total_sales - $total_purchase;

        // محاسبه سود خالص با کم کردن هزینه‌ها
        $net_profit = $gross_profit - ($machine_cost + $worker_cost + $sms_cost);

        // نمایش نتایج در جدول
        echo '<h2 style = "direction: rtl;text-align: center;border: 2px solid;padding: 10px;margin: 10px;border-radius: 10px;" >نتایج گزارش ماهانه</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>مقدار کل فروش</th>';
        echo '<th>مقدار کل خرید</th>';
        echo '<th>سود ناخالص</th>';
        echo '<th>هزینه ماشین</th>';
        echo '<th>هزینه کارگر</th>';
        echo '<th>هزینه پنل پیامکی</th>';
        echo '<th>سود خالص</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        echo '<tr>';
        echo '<td>' . wc_price($total_sales) . '</td>';
        echo '<td>' . wc_price($total_purchase) . '</td>';
        echo '<td>' . wc_price($gross_profit) . '</td>';
        echo '<td>' . wc_price($machine_cost) . '</td>';
        echo '<td>' . wc_price($worker_cost) . '</td>';
        echo '<td>' . wc_price($sms_cost) . '</td>';
        echo '<td>' . wc_price($net_profit) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
    }
}

// صفحه ساب منو ۵
function submenu_5_page() {
    ?>
    <div class="wrap">
        <div class="wrap">
        <h1 style = "direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">گزارش ماهانه خرید و فروش</h1>
        <form style = "direction: rtl;" method="post" action="">
            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="month">ماه (مثلاً 09):</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="month" id="month" placeholder="MM" required><br><br>
            <input style = "direction: rtl;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;" type="submit" name="submit_report" value="محاسبه گزارش ماهانه">
        </form>
    </div>
        <!-- ایجاد عنصر canvas برای نمودار -->
        <canvas id="myChart" style="width:100%;max-width:700px"></canvas>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('myChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر'], // نام ماه‌ها
                    datasets: [
                        {
                            label: 'سود خالص',
                            data: [50000, 70000, 45000, 35000], // داده‌های مربوط به سود خالص
                            backgroundColor: 'rgba(54, 162, 235, 0.5)', // رنگ میله سود خالص
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'سود ناخالص',
                            data: [200000, 300000, 250000, 150000], // داده‌های مربوط به سود ناخالص
                            backgroundColor: 'rgba(255, 206, 86, 0.5)', // رنگ میله سود ناخالص
                            borderColor: 'rgba(255, 206, 86, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'هزینه‌ها',
                            data: [300000, 400000, 350000, 300000], // داده‌های مربوط به هزینه‌ها
                            backgroundColor: 'rgba(75, 192, 192, 0.5)', // رنگ میله هزینه‌ها
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'خریدها',
                            data: [250000, 450000, 400000, 350000], // داده‌های مربوط به خریدها
                            backgroundColor: 'rgba(255, 99, 132, 0.5)', // رنگ میله خریدها
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' تومان'; // اضافه کردن واحد "تومان" به محور y
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>

    <?php
}

function submenu_6_page() {
    ?>
    <div class="wrap">
        <h1 style = "direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;" >درصد فروش کالاهای مختلف</h1>
        <form style = "direction: rtl;" method="post" action="">
            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="month">اسم دسته بندی های مورد نیاز:</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="month" id="month" placeholder="MM" required><br><br>
            <input style = "direction: rtl;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;" type="submit" name="submit_report" value="محاسبه گزارش دسته بندی محصولات">
        </form>
        <!-- ایجاد عنصر canvas برای نمودار -->
        <canvas id="myPieChart" style="width:50%;max-width:400px"></canvas>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('myPieChart').getContext('2d');
            var myPieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['نوشیدنی', 'خشکبار', 'شوینده', 'تنقلات'], // نام دسته‌های کالا
                    datasets: [{
                        label: 'درصد فروش کالاهای مختلف',
                        data: [25, 12, 30, 33], // درصد فروش فرضی
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)', // رنگ نوشیدنی
                            'rgba(255, 206, 86, 0.7)', // رنگ خشکبار
                            'rgba(75, 192, 192, 0.7)', // رنگ شوینده
                            'rgba(255, 99, 132, 0.7)', // رنگ تنقلات
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)',
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top', // نمایش نام دسته‌ها در بالا
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.label + ': ' + tooltipItem.raw + '%'; // اضافه کردن % به نمایش درصدها
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>

    <?php
}

function submenu_7_page() {
    ?>
    <div class="wrap">
        <h1 style = "direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">نمودار مقدار خرید از هر فروشگاه</h1>
        <form style = "direction: rtl;" method="post" action="">
            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="month">اسم فروشگاه های مورد نیاز:</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="month" id="month" placeholder="MM" required><br><br>
            <input style = "direction: rtl;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;" type="submit" name="submit_report" value="محاسبه گزارش فروشگاه ها">
        </form>
        <!-- ایجاد عنصر canvas برای نمودار -->
        <canvas id="myShopChart" style="width:100%;max-width:700px"></canvas>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('myShopChart').getContext('2d');
            var myShopChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['فروشگاه 1', 'فروشگاه 2', 'فروشگاه 3', 'فروشگاه 4', 'فروشگاه 5'], // نام فروشگاه‌های فرضی
                    datasets: [{
                        label: 'مجموع خرید از هر فروشگاه',
                        data: [1200000, 950000, 1700000, 1400000, 1100000], // داده‌های فرضی مربوط به خرید از هر فروشگاه (به تومان)
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)', // رنگ برای فروشگاه 1
                            'rgba(255, 99, 132, 0.7)', // رنگ برای فروشگاه 2
                            'rgba(75, 192, 192, 0.7)', // رنگ برای فروشگاه 3
                            'rgba(255, 206, 86, 0.7)', // رنگ برای فروشگاه 4
                            'rgba(153, 102, 255, 0.7)' // رنگ برای فروشگاه 5
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' تومان'; // اضافه کردن واحد "تومان" به محور y
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>

    <?php
}


function multiple_orders_page() {
    ?>
    <div class="wrap">
        <h1 style = "direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;"
        >توضیحات ووکامرس پیشرفته</h1>
        <style>
        table {
            width: 90%;
            border-collapse: collapse;
            margin: 20px auto;
            font-family: 'B Mitra', sans-serif;
            font-size: 18px;
            text-align: center;
        }

        th, td {
            border: 2px solid #000;
            padding: 10px;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <th>خروجی</th>
        <th>کار هر زیر منو </th>
        <th> زیر منو </th>
    </tr>
    <tr>
        <td>نمایش تعداد هر مصول در سفارش  هایی که درون کادر زده میشه</td>
        <td> ورودی کادر باید سفارشات از روی ووکامرس بهش داده شود</td>
        <td> جمع بندی کالا ها</td>
    </tr>
    <tr>
        <td>محاسبه سود خالص و ناخالص</td>
        <td>ابتدا شماره سفارش ها رو وارد کنید سپس کادر رو وارد کنید</td>
        <td> محاسبه حاشیه سود</td>
    </tr>
    <tr>
        <td> سود خالص و ناخالص در ماه ها</td>
        <td>ما ه ها رو وارد میکنید هزینه ها رو وارد کنید</td>
        <td> گزارش ماهانه فروش و خرید</td>
    </tr>
    <tr>
        <td>نمودار خالص ناخالص </td>
        <td>وارد کردن ماه ها</td>
        <td> نمودار خرید و فروش </td>
    </tr>
    <tr>
        <td> نمودار فروشگاه ها</td>
        <td>اسم فروشگاه ها رو وارد کن</td>
        <td> نمودار خرید و فروش هر فروشگاه</td>
    </tr>
    <tr>
        <td>محاسبه صورتحساب </td>
        <td>نام فروشگاه</td>
        <td>پروفایل اختصاصی هر خریدار </td>
    </tr>
</table>
    </div>
    <?php
}

function add_pdf_generation_script() {
    ?>
    <!-- بارگذاری کتابخانه jsPDF و jsPDF AutoTable از CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('generate_pdf').addEventListener('click', function () {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                // بارگذاری فونت فارسی از مسیر محلی
                fetch('<?php echo plugins_url('assets/fonts/Vazir.ttf', __FILE__); ?>')
                    .then(response => response.arrayBuffer())
                    .then(data => {
                        doc.addFileToVFS("Vazir.ttf", btoa(new Uint8Array(data).reduce((data, byte) => data + String.fromCharCode(byte), '')));
                        doc.addFont("Vazir.ttf", "Vazir", "normal");
                        doc.setFont("Vazir");

                        // تنظیمات عنوان
                        const title = "گزارش سفارشات انتخاب شده";
                        const pageWidth = doc.internal.pageSize.getWidth();
                        const titleX = (pageWidth - doc.getTextWidth(title)) / 2;
                        doc.setFontSize(16);
                        doc.text(title, titleX, 10);

                        // تولید جدول با استفاده از AutoTable
                        doc.autoTable({
                            html: '#orders_table',
                            styles: {
                                font: 'Vazir',
                                fontStyle: 'normal',
                                textColor: [0, 0, 0],
                            },
                            startY: 20,
                            margin: { top: 30 },
                            columnStyles: {
                                0: { cellWidth: 20, halign: 'right' },  // راست‌چین کردن ستون عکس
                                1: { cellWidth: 30, halign: 'right' },  // راست‌چین کردن ستون نام
                                2: { cellWidth: 25, halign: 'right' },  // راست‌چین کردن ستون قیمت
                                3: { cellWidth: 25, halign: 'right' },  // راست‌چین کردن ستون تخفیف
                                4: { cellWidth: 15, halign: 'right' },  // راست‌چین کردن ستون تعداد
                                5: { cellWidth: 25, halign: 'right' }   // راست‌چین کردن ستون مبلغ کل
                            }
                        });

                        // دانلود PDF
                        doc.save('orders_report.pdf');
                    });
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'add_pdf_generation_script');

function display_custom_order_tables_by_name() {
    global $wpdb;

    // دریافت لیست جداول سفارشی از متا
    $query = "SELECT DISTINCT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_custom_table_name'";
    $results = $wpdb->get_results($query);

    ?>
    <div class="wrap">
        <h1 style="direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;" >نمایش سفارشات بر اساس نام جدول</h1>
        <form style="direction:rtl;" method="post" action="">
            <label style="font-family: 'B Mitra', sans-serif;font-size: 26px;color: unset;" for="table_name">انتخاب نام جدول:</label>
            <select style="font-family: 'B Mitra', sans-serif;font-size: 18px;color: unset;" name="table_name" required>
                <?php
                if (!empty($results)) {
                    foreach ($results as $row) {
                        echo '<option value="' . esc_attr($row->meta_value) . '">' .
esc_html($row->meta_value) . '</option>';
                    }
                } else {
                    echo '<option value="">هیچ جدولی یافت نشد</option>';
                }
                ?>
            </select>
            <input style="margin-top: 20px;margin-right: 20px;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;" type="submit" name="retrieve_orders" value="نمایش سفارشات">
        </form>
    </div>
    <?php

    // بررسی اینکه آیا نام جدول انتخاب شده است
    if (isset($_POST['retrieve_orders']) && isset($_POST['table_name'])) {
        $table_name_input = sanitize_text_field($_POST['table_name']);

        // دریافت سفارشاتی که با این نام جدول ذخیره شده‌اند
        $query = $wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_custom_table_name' AND meta_value = %s", $table_name_input);
        $order_results = $wpdb->get_results($query);

        if (!empty($order_results)) {
            ?>
            <h2 style="direction: rtl;margin-top: 50px;margin-right: 40px;margin-bottom: 40px;border: 2px solid;text-align: center;padding: 10px;color: blue;">سفارشات مرتبط با جدول: <?php echo esc_html($table_name_input); ?></h2>
            <table style="direction:rtl;margin-right: 20px;" id="orders_table" class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="text-align: right">عکس محصول</th>
                        <th style="text-align: right">نام محصول</th>
                        <th style="text-align: right">قیمت</th>
                        <th style="text-align: right">مبلغ تخفیف</th>
                        <th style="text-align: right">تعداد</th>
                        <th style="text-align: right">مبلغ کل</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($order_results as $row) {
                        $order = wc_get_order($row->post_id);
                        if ($order) {
                            foreach ($order->get_items() as $item_id => $item) {
                                $product = $item->get_product();
                                $product_image = wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()), 'thumbnail')[0]; // دریافت URL عکس
                                $product_name = $item->get_name();
                                $product_price = $product->get_price();
                                $product_quantity = $item->get_quantity();

                                // محاسبه تخفیف آیتم از تخفیف کلی سفارش
                                $item_total = $item->get_total(); // مبلغ کل آیتم بدون تخفیف
                                $item_subtotal = $item->get_subtotal(); // مبلغ کل آیتم با تخفیف
                                $item_discount = $item_subtotal - $item_total; // تخفیف آیتم

                                ?>
                                <tr>
                                    <td><img src="<?php echo esc_url($product_image); ?>" width="50" height="50" /></td>
                                    <td><?php echo esc_html($product_name); ?></td>
                                    <td><?php echo wc_price($product_price); ?></td>
                                    <td><?php echo wc_price($item_discount); ?></td>
                                    <td><?php echo esc_html($product_quantity); ?></td>
                                    <td><?php echo wc_price($item_total); ?></td>
                                </tr>
                                <?php
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>

            <!-- دکمه برای تولید PDF -->
            <button style="float: right;margin-top: 10px;margin-right: 20px;" id="generate_pdf" class="button button-primary">تولید PDF از سفارشات</button>
            <?php
        } else {
            echo '<p>هیچ سفارشی برای این جدول یافت نشد.</p>';
}
    }
}

register_activation_hook(__FILE__,'dyme_install');
function dyme_install(){
    global $wpdb;
    $table_employees = $wpdb->prefix . 'dyme_employees';
    $table_collation = $wpdb->collate;
    $sql = "
    CREATE TABLE `$table_employees` (
  `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthdate` date DEFAULT NULL,
  `avatar` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` float NOT NULL,
  `mission` smallint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = $table_collation  COMMENT='this is table keep employees information'
    ";
    require_once( ABSPATH. 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('admin_menu', 'dyme_add_menus');
function dyme_add_menus() {
    // اضافه کردن منوی اصلی
    add_menu_page(
        'کارمندان',              // عنوان صفحه
        'کارمندان',              // نام منو
        'manage_options',         // سطح دسترسی
        'dyme_employees',         // شناسه منو
        'dyme_list_employees',    // تابع برای نمایش محتوای منوی اصلی
        '',                       // آیکون منو (در صورت تمایل می‌توانید آیکون تعریف کنید)
        6                         // موقعیت منو
    );

    // اضافه کردن زیرمنو
    add_submenu_page(
        'dyme_employees',         // شناسه منوی اصلی
        'ایجاد کارمندان',        // عنوان صفحه زیرمنو
        'ایجاد کارمندان',        // نام زیرمنو
        'manage_options',         // سطح دسترسی
        'dyme_create_employee',
        'dyme_create_employee'    // تابع برای نمایش محتوای زیرمنو
    );
}

// تابع برای نمایش لیست کارمندان
function dyme_list_employees() {
    $file_path = plugin_dir_path(__FILE__) . 'view/list_employees.php';
    if (file_exists($file_path)) {
        include $file_path;
    } else {
        echo '<p>فایل لیست کارمندان یافت نشد.</p>';
    }
}

// تابع برای نمایش فرم ایجاد کارمند
function dyme_create_employee() {
    $file_path = plugin_dir_path(__FILE__) . 'view/form_employees.php';
    if (file_exists($file_path)) {
        include $file_path;
    } else {
        echo '<p>فایل فرم کارمند یافت نشد.</p>';
    }
}
add_action('admin_init','dyme_form_submit');
function dyme_form_submit(){
    global $pagenow;
    if ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'dyme_create_employee'){
        if (isset($_POST['save_employee'])){
            $data = [
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'mission' => absint($_POST['mission']),
                'weight' => floatval($_POST['weight]']),
                'birthdate' => sanitize_text_field($_POST['birthdate']),
                'avatar' => esc_url_raw($_POST['first_name']),
                'created_at' => current_time('mysql'),

            ];
            global $wpdb;
            $table_employees = $wpdb->prefix . 'dyme_employees';
            $inserted = $wpdb ->insert(
                $table_employees,
                $data,
                [
                    '%s','%s','%d','%f','%s','%s','%s'
                ]
            );
            if ($inserted){
                $employee_id = $wpdb->insert_id;
                wp_redirect(
                    admin_url('admin.php?page=dyme_create_employee&employee_status=inserted&employee_id= '.$employee_id)
                ); 
                die('success');
                exit;

            }else{
                wp_redirect(
                    admin_url('admin.php?page=dyme_create_employee&employee_status=error')
                ); 
                exit;
            }
        }
    }
}

add_action( 'admin_notices','dyme_notices');
function dyme_notices(){
    $type  = ''; 
    $message = '';
    if(isset($_GET['employee_status'])){
        $status = sanitize_text_field($_GET['employee_status']);
        if ($status == 'inserted'){
            $message = 'کارمند با موفقیت ثبت شد ';
            $type = 'success';
        }elseif($status == 'inseted_error'){
            $message = 'خطا در ثبت کارمند';
            $type = 'error';
        }
        }
    if( $type && $message){
        ?>
        <div class = "notice notice-<?php echo $type;?> is-dismissible">
            <p><?php echo $message;?></p>
        </div>
        <?php
    }
    }




function submenu_8_page() {
    ?>
    <div class="wrap">
        <h1>انتخاب سفارشات ووکامرس و محاسبه سود</h1>
        <form method="post" action="">
            <?php
            // دریافت سفارشات ووکامرس
            $args = array(
                'limit' => -1, // همه سفارشات
                'status' => 'completed', // فقط سفارشات تکمیل شده
            );
            $orders = wc_get_orders($args);

            if (!empty($orders)) {
                echo '<h3>لیست سفارشات</h3>';
                echo '<ul>';

                // نمایش هر سفارش به صورت یک چک‌باکس
                foreach ($orders as $order) {
                    $order_id = $order->get_id();
                    $order_total = $order->get_total();
                    $order_date = $order->get_date_created()->date('Y-m-d');

                    echo '<li>';
                    echo '<label>';
                    echo '<input type="checkbox" name="selected_orders[]" value="' . esc_attr($order_id) . '"> ';
                    echo 'شماره سفارش: ' . esc_html($order_id) . ' | مبلغ: ' . wc_price($order_total) . ' | تاریخ: ' . esc_html($order_date);
                    echo '</label>';
                    echo '</li>';
                }

                echo '</ul>';
            } else {
                echo '<p>هیچ سفارشی پیدا نشد.</p>';
            }
            ?>
            <br>

            <label for="discount">مبلغ تخفیف کلی:</label><br>
            <input type="text" name="discount" id="discount" value=""><br><br>

            <label for="worker_cost">هزینه کارگر:</label><br>
            <input type="text" name="worker_cost" id="worker_cost" value=""><br><br>

            <label for="shipping_cost">هزینه باربری:</label><br>
            <input type="text" name="shipping_cost" id="shipping_cost" value=""><br><br>

            <input type="submit" name="process_orders" value="محاسبه سود">
        </form>
    </div>

    <?php
    if (isset($_POST['process_orders']) && !empty($_POST['selected_orders'])) {
        // دریافت سفارشات انتخاب‌شده
        $selected_orders = $_POST['selected_orders'];
        $discount = floatval($_POST['discount']); // تخفیف کلی
        $worker_cost = floatval($_POST['worker_cost']);
        $shipping_cost = floatval($_POST['shipping_cost']);
        $total_price = 0; // جمع کل مبلغ سفارشات
        $total_discount = 0; // جمع کل تخفیفات

        // پردازش سفارشات انتخاب‌شده
        foreach ($selected_orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order_total = $order->get_total();
                $order_discount = $order->get_total_discount();

                // جمع کل مبلغ و تخفیفات برای محاسبه سود
                $total_price += $order_total;
                $total_discount += $order_discount;
            }
        }

        // محاسبه سود ناخالص
        $gross_profit = $total_price - $discount;

        // محاسبه سود خالص
        $net_profit = $gross_profit - ($worker_cost + $shipping_cost);

        // نمایش نتایج محاسبات در جدول
        echo '<h2>نتایج محاسبات سود</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>جمع کل فاکتورها</th>';
        echo '<th>جمع تخفیف‌ها</th>';
        echo '<th>تخفیف کلی</th>';
        echo '<th>سود ناخالص</th>';
        echo '<th>هزینه کارگر</th>';
        echo '<th>هزینه باربری</th>';
        echo '<th>سود خالص</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        echo '<tr>';
        echo '<td>' . wc_price($total_price) . '</td>';
        echo '<td>' . wc_price($total_discount) . '</td>';
        echo '<td>' . wc_price($discount) . '</td>';
        echo '<td>' . wc_price($gross_profit) . '</td>';
        echo '<td>' . wc_price($worker_cost) . '</td>';
        echo '<td>' . wc_price($shipping_cost) . '</td>';
        echo '<td>' . wc_price($net_profit) . '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';

        // رسم نمودار با Chart.js
        ?>
        <canvas id="ordersChart"
width="400" height="200"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            var ctx = document.getElementById('ordersChart').getContext('2d');
            var ordersChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['سود ناخالص', 'سود خالص', 'هزینه کارگر', 'هزینه باربری'],
                    datasets: [{
                        label: 'مبالغ (ریال)',
                        data: [
                            <?php echo $gross_profit; ?>,
                            <?php echo $net_profit; ?>,
                            <?php echo $worker_cost; ?>,
                            <?php echo $shipping_cost; ?>
                        ],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(153, 102, 255, 0.2)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
        <?php
    } elseif (isset($_POST['process_orders'])) {
        echo '<p>هیچ سفارشی انتخاب نشده است.</p>';
    }
}















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

function save_filtered_orders_callback() {
    global $wpdb;

    $order_ids = $_POST['order_ids'];
    $table_name_input = sanitize_text_field($_POST['table_name']);

    if (!empty($order_ids) && !empty($table_name_input)) {
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);

            if ($order) {
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = $item->get_product_id();
                    $product_name = $item->get_name();
                    $quantity = $item->get_quantity();
                    $product_price = $item->get_total() / $quantity; // محاسبه قیمت واحد
                    $total_price = $item->get_total();

                    // ذخیره اطلاعات هر محصول در جدول سفارشی
                    $table_name = $wpdb->prefix . 'custom_order_items';
                    $wpdb->insert(
                        $table_name,
                        array(
                            'table_name' => $table_name_input,
                            'order_id' => $order_id,
                            'product_id' => $product_id,
                            'product_name' => $product_name,
                            'quantity' => $quantity,
                            'product_price' => $product_price,
                            'total_price' => $total_price,
                        ),
                        array(
                            '%s', // table_name
                            '%d', // order_id
                            '%d', // product_id
                            '%s', // product_name
                            '%d', // quantity
                            '%f', // product_price
                            '%f', // total_price
                        )
                    );
                }
            }
        }
        echo 'سفارشات با موفقیت ذخیره شدند.';
    } else {
        echo 'خطا: نام جدول یا سفارشات انتخاب‌شده خالی است.';
    }

    wp_die();
}
add_action('wp_ajax_save_filtered_orders', 'save_filtered_orders_callback');
function filter_orders_callback() {
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $order_status = sanitize_text_field($_POST['order_status']);

    // آرگومان‌های فیلتر سفارشات
    $args = array(
        'limit' => -1,
        'status' => $order_status ? array($order_status) : array('completed', 'processing', 'on-hold', 'cancelled'),
    );

    if (!empty($customer_name)) {
        $args['billing_first_name'] = $customer_name;
    }

    $orders = wc_get_orders($args);

    // بررسی اگر سفارشی وجود ندارد
    if (!empty($orders)) {
        echo '<ul>';
        foreach ($orders as $order) {
            echo '<li><input type="checkbox" name="order_ids[]" value="' . esc_attr($order->get_id()) . '"> سفارش شماره ' . esc_html($order->get_id()) . ' - ' . esc_html($order->get_billing_first_name()) . ' ' . esc_html($order->get_billing_last_name()) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>هیچ سفارشی برای این فیلترها یافت نشد.</p>';
    }

    wp_die();  // پایان تابع
}
add_action('wp_ajax_filter_orders', 'filter_orders_callback');
global $custom_order_table_db_version;
$custom_order_table_db_version = '1.4';

// ایجاد جدول سفارشی در دیتابیس
function custom_order_table_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_items'; // نام جدول
    $charset_collate = $wpdb->get_charset_collate();

    // کوئری ایجاد جدول
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        table_name varchar(255) NOT NULL,
        order_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        product_name varchar(255) NOT NULL,
        quantity int(11) NOT NULL,
        product_price decimal(10, 2) NOT NULL,
        total_price decimal(10, 2) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'custom_order_table_install');
function submenu_1_page() {
    ?>
    <div class="wrap">
        <h1 style="direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">ذخیره سفارشات انتخاب‌شده</h1>
        
        <!-- فرم فیلتر سفارشات -->
        <form id="filter_orders_form" style="direction: rtl; margin-bottom: 20px;">
            <label style="font-family: 'B Mitra', sans-serif;font-size: 26px;" for="customer_name">نام مشتری:</label><br>
            <input type="text" id="customer_name" name="customer_name" placeholder="نام مشتری را وارد کنید"><br><br>

            <label style="font-family: 'B Mitra', sans-serif;font-size: 26px;" for="order_status">وضعیت سفارش:</label><br>
            <select id="order_status" name="order_status">
                <option value="">همه وضعیت‌ها</option>
                <?php
                // دریافت وضعیت‌های سفارش از ووکامرس
                $statuses = wc_get_order_statuses();
                foreach ($statuses as $status_key => $status_label) {
                    echo '<option value="' . esc_attr($status_key) . '">' . esc_html($status_label) . '</option>';
                }
                ?>
            </select><br><br>

            <input type="button" id="filter_orders_button" value="فیلتر" style="padding: 10px 20px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;">
        </form>

        <!-- نمایش سفارشات فیلتر شده -->
        <div id="filtered_orders"></div>

        <!-- فرم برای ذخیره سفارشات فیلتر شده -->
        <form id="save_orders_form" style="display: none; direction: rtl;" method="post" action="">
            <label style="font-family: 'B Mitra', sans-serif;font-size: 26px;color: unset;" for="table_name">نام جدول را وارد کنید:</label><br>
            <input type="text" name="table_name" id="table_name" required><br><br>
            <div id="orders_to_save"></div>
            <input style="margin-top: 20px;margin-right: 20px;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;" type="button" id="save_orders_button" value="ذخیره">
        </form>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // عملکرد فیلتر سفارشات با AJAX
                $('#filter_orders_button').on('click', function() {
                    var customer_name = $('#customer_name').val();
                    var order_status = $('#order_status').val();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'filter_orders',
                            customer_name: customer_name,
                            order_status: order_status,
                        },
                        success: function(response) {
                            $('#filtered_orders').html(response);
                            $('#save_orders_form').show();  // نمایش فرم ذخیره سفارشات
                        }
                    });
                });

                // عملکرد ذخیره سفارشات انتخاب شده با AJAX
                $('#save_orders_button').on('click', function() {
                    var table_name = $('#table_name').val();
                    var order_ids = [];
                    $('input[name="order_ids[]"]:checked').each(function() {
                        order_ids.push($(this).val());
                    });

                    if (order_ids.length > 0 && table_name) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'save_filtered_orders',
                                order_ids: order_ids,
                                table_name: table_name,
                            },
                            success: function(response) {
alert(response);  // نمایش پیام موفقیت
                            }
                        });
                    } else {
                        alert('لطفاً جدول را وارد کنید و حداقل یک سفارش را انتخاب کنید.');
                    }
                });
            });
        </script>
    </div>
    <?php
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
    add_submenu_page(
        'orders-by-ids',
        'حاشیه سود',
        'حاشیه سود',
        'manage_options',
        'submenu-4',
        'submenu_4_page'
    );
}



function submenu_2_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_items';

    // دریافت جزئیات سفارشات از جدول سفارشی
    $orders = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1 style="direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">لیست سفارشات و ویرایش جزئیات</h1>
        
        <!-- نمایش سفارشات -->
        <table style="direction: rtl;margin-right: 20px;" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="text-align: right">نام جدول</th>
                    <th style="text-align: right">شماره سفارش</th>
                    <th style="text-align: right">شناسه محصول</th>
                    <th style="text-align: right">نام محصول</th>
                    <th style="text-align: right">تعداد</th>
                    <th style="text-align: right">قیمت واحد</th>
                    <th style="text-align: right">مبلغ کل</th>
                    <th style="text-align: right">ویرایش</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($orders)) {
                    foreach ($orders as $order) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($order->table_name); ?></td>
                            <td><?php echo esc_html($order->order_id); ?></td>
                            <td><?php echo esc_html($order->product_id); ?></td>
                            <td><?php echo esc_html($order->product_name); ?></td>
                            <td><?php echo esc_html($order->quantity); ?></td>
                            <td><?php echo wc_price($order->product_price); ?></td>
                            <td><?php echo wc_price($order->total_price); ?></td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="edit_order_id" value="<?php echo esc_attr($order->id); ?>">
                                    <input type="hidden" name="table_name" value="<?php echo esc_attr($order->table_name); ?>">
                                    <input style="padding: 5px 10px;border-radius: 5px;background-color: #0073aa;color: white;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;" type="submit" name="edit_order" value="ویرایش">
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="8" style="text-align:center;">هیچ سفارشی یافت نشد</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <!-- فرم اضافه کردن سفارش جدید -->
        <h2 style="direction: rtl;">افزودن سفارش جدید</h2>
        <form method="post" action="">
            <label style="font-family: 'B Mitra', sans-serif;">نام جدول:</label>
            <input type="text" name="table_name" required><br><br>

            <label style="font-family: 'B Mitra', sans-serif;">شماره سفارش:</label>
            <input type="number" name="order_id" required><br><br>

            <label style="font-family: 'B Mitra', sans-serif;">شناسه محصول:</label>
            <input type="number" name="product_id" required><br><br>

            <label style="font-family: 'B Mitra', sans-serif;">نام محصول:</label>
            <input type="text" name="product_name" required><br><br>

            <label style="font-family: 'B Mitra', sans-serif;">تعداد:</label>
            <input type="number" name="quantity" required><br><br>

            <label style="font-family: 'B Mitra', sans-serif;">قیمت واحد:</label>
            <input type="number" step="0.01" name="product_price" required><br><br>

            <input style="margin-top: 20px;padding:
10px 20px;border-radius: 5px;background-color: #0073aa;color: white;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;" type="submit" name="add_order" value="افزودن سفارش">
        </form>
    </div>

    <?php
    // پردازش اضافه کردن سفارش جدید
    if (isset($_POST['add_order'])) {
        $table_name_input = sanitize_text_field($_POST['table_name']);
        $order_id = intval($_POST['order_id']);
        $product_id = intval($_POST['product_id']);
        $product_name = sanitize_text_field($_POST['product_name']);
        $quantity = intval($_POST['quantity']);
        $product_price = floatval($_POST['product_price']);
        $total_price = $quantity * $product_price;

        // ذخیره سفارش جدید در جدول
        $wpdb->insert(
            $table_name,
            array(
                'table_name' => $table_name_input,
                'order_id' => $order_id,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'quantity' => $quantity,
                'product_price' => $product_price,
                'total_price' => $total_price,
            ),
            array(
                '%s', // table_name
                '%d', // order_id
                '%d', // product_id
                '%s', // product_name
                '%d', // quantity
                '%f', // product_price
                '%f', // total_price
            )
        );

        echo '<h2 style="direction: rtl;text-align: center;color: green;">سفارش جدید با موفقیت اضافه شد.</h2>';
    }

    // ویرایش سفارش
    if (isset($_POST['edit_order']) && isset($_POST['edit_order_id'])) {
        $order_id = intval($_POST['edit_order_id']);
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));

        if ($order) {
            ?>
            <div class="wrap">
                <h3 style="direction: rtl;font-family: 'B Mitra', sans-serif;">ویرایش جزئیات سفارش</h3>
                <form method="post" action="">
                    <label style="font-family: 'B Mitra', sans-serif;">نام جدول:</label>
                    <input type="text" name="table_name" value="<?php echo esc_attr($order->table_name); ?>" readonly><br><br>

                    <label style="font-family: 'B Mitra', sans-serif;">شماره سفارش:</label>
                    <input type="number" name="order_id" value="<?php echo esc_attr($order->order_id); ?>" readonly><br><br>

                    <label style="font-family: 'B Mitra', sans-serif;">شناسه محصول:</label>
                    <input type="number" name="product_id" value="<?php echo esc_attr($order->product_id); ?>" readonly><br><br>

                    <label style="font-family: 'B Mitra', sans-serif;">نام محصول:</label>
                    <input type="text" name="product_name" value="<?php echo esc_attr($order->product_name); ?>" readonly><br><br>

                    <label style="font-family: 'B Mitra', sans-serif;">تعداد:</label>
                    <input type="number" name="quantity" value="<?php echo esc_attr($order->quantity); ?>" required><br><br>

                    <label style="font-family: 'B Mitra', sans-serif;">قیمت واحد:</label>
                    <input type="number" step="0.01" name="product_price" value="<?php echo esc_attr($order->product_price); ?>" required><br><br>

                    <input type="hidden" name="edit_order_id" value="<?php echo esc_attr($order_id); ?>">
                    <input style="margin-top: 20px;padding: 10px 20px;border-radius: 5px;background-color: #0073aa;color: white;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;" type="submit" name="save_order" value="ذخیره تغییرات">
                </form>
            </div>
            <?php
        }
    }

    // ذخیره تغییرات سفارش
    if (isset($_POST['save_order']) && isset($_POST['edit_order_id'])) {
        $order_id = intval($_POST['edit_order_id']);
        $quantity = intval($_POST['quantity']);
        $product_price = floatval($_POST['product_price']);
        $total_price = $quantity * $product_price;

        // به‌روزرسانی سفارش در جدول
        $wpdb->update(
            $table_name,
            array(
                'quantity' => $quantity,
                'product_price' => $product_price,
                'total_price' => $total_price,
            ),
            array('id' => $order_id),
            array(
                '%d', // quantity
                '%f', // product_price
                '%f', // total_price
            ),
            array('%d') // id
        );

        echo '<h2 style="direction: rtl;text-align: center;color: green;">تغییرات با موفقیت ذخیره شدند.</h2>';
    }
}
// صفحه ساب منو ۴
function submenu_10_page() {
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

    // دریافت لیست جداول سفارشی از دیتابیس
    $query = "SELECT DISTINCT table_name FROM {$wpdb->prefix}custom_order_items"; // لیست جداول ذخیره شده در custom_order_items
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
                        echo '<option value="' . esc_attr($row->table_name) . '">' . esc_html($row->table_name) . '</option>';
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
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_order_items WHERE table_name = %s", $table_name_input);
        $order_results = $wpdb->get_results($query);

        if (!empty($order_results)) {
            $total_sum = 0;  // متغیر برای ذخیره مجموع قیمت سفارشات
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
                        // فرض می‌کنیم اطلاعات محصول در custom_order_items ذخیره شده است
                        $product_id = $row->product_id;
                        $product = wc_get_product($product_id);
                        $product_image = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'thumbnail')[0]; // دریافت URL عکس
                        $product_name = $row->product_name;
                        $product_price = $row->product_price;
                        $product_quantity = $row->quantity;
                        $total_price = $row->total_price;

                        // محاسبه تخفیف (فرض: مبلغ کل - قیمت نهایی)
                        $item_discount = ($product_price * $product_quantity) - $total_price;

                        // افزودن مبلغ کل به مجموع کل
                        $total_sum += $total_price;

                        ?>
                        <tr>
<td><img src="<?php echo esc_url($product_image); ?>" width="50" height="50" /></td>
                            <td><?php echo esc_html($product_name); ?></td>
                            <td><?php echo wc_price($product_price); ?></td>
                            <td><?php echo wc_price($item_discount); ?></td>
                            <td><?php echo esc_html($product_quantity); ?></td>
                            <td><?php echo wc_price($total_price); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <!-- نمایش مجموع مبلغ کل سفارشات -->
            <h2 style="direction: rtl;text-align: center;color: green;margin-top: 30px;">مجموع مبلغ کل سفارشات: <?php echo wc_price($total_sum); ?></h2>

            <!-- دکمه برای تولید PDF -->
            <button style="float: right;margin-top: 10px;margin-right: 20px;" id="generate_pdf" class="button button-primary">تولید PDF از سفارشات</button>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.14/jspdf.plugin.autotable.min.js"></script>
            <script>
                document.getElementById('generate_pdf').addEventListener('click', function () {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'a4'
                    });

                    doc.setFontSize(16);
                    doc.text("گزارش سفارشات", 10, 10);  // عنوان

                    // جدول سفارشات
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
                            0: { cellWidth: 20, halign: 'right' },  // راست‌چین کردن ستون‌ها
                            1: { cellWidth: 30, halign: 'right' },
                            2: { cellWidth: 25, halign: 'right' },
                            3: { cellWidth: 25, halign: 'right' },
                            4: { cellWidth: 90, halign: 'right' }
                        }
                    });

                    // اضافه کردن مجموع مبلغ کل به PDF
                    doc.text("مجموع مبلغ کل سفارشات: <?php echo wc_price($total_sum); ?>", 10, doc.lastAutoTable.finalY + 10);

                    doc.save('orders_report.pdf');  // دانلود PDF
                });
            </script>
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

add_action('wp_enqueue_scripts','dansh_post_script');
function dansh_post_script(){
    wp_enqueue_script(
        'dypl_script',
        DANASH_COURSE_SHOP_JS . 'post-like.js',
        ['jquery'],

    );
    wp_localize_script(
        'dypl_script',
        'dypl',
        ['ajax_url' => admin_url('admin-ajax.php')]
    );
}
add_filter('the_content','dansh_post_button');
function dansh_post_button($content){
    $like_text = __('Like','dansh-post-like');
    $post_id = get_the_ID();
    $button = "<button class = 'like-post' type = 'button' data-id='$post_id'>
    $like_text
    <span class = 'like-count'>(21)</span>
     </button>";
    return $content . $button;
}
add_action( 'wp_ajax_dypl_like','dansh_post_like_ajax_callback');
function dansh_post_like_ajax_callback(){
    if (! is_user_logged_in()){

    }
    global $wpdb;
    $post_id = absint( $_POST['post_id'] );
    $like = (bool) $_POST['like'];
    $exists_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->dypl_post_likes} WHERE post_id = %d AND user_id = %d"
            ,$post_id
            ,get_current_user_id()
        )
    );
    if ($exists_id && $like){
        //you like previously

    }
    if (! $exists_id && ! $like){
        //you did not like this post
    }
    if( $like ){
        $like_data = [
            'post_id' => $post_id,
            'user_id' => get_current_user_id(),
            'ip'  => $_SERVER['REMOTE_ADDR'],
            'liked' => 1,
            'created_at' => current_time('mysql')
        ];   
        $liked = $wpdb->insert(
            $wpdb->dypl_post_likes,
            $like_data,
            ['%d','%d','%s','%d','%s']
        );
    }else{
        $disliked = $wpdb->delete(
            $wpdb->dypl_post_likes,
            [
                'ID' => $exists_id
            ]
        );
    }
    wp_send_json_error($data);

}

register_activation_hook(__FILE__,'post_like');
function post_like(){
    global $wpdb;
    $table_post_likes = $wpdb->prefix . 'dypl_post_likes';
    $sql = "
    CREATE TABLE `wp_dypl_post_likes` (
  `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `ip` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `liked` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
    require_once( ABSPATH. 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('wp_head','dansh_post_like_style');
function dansh_post_like_style(){
    ?>
    <style>
        
button.like-post {
    background: #ffffff;
    border-radius: 4px;
    padding: 4px 15px;
    display: inline-block;
    border: 1px solid #e6cece;
}
    </style>
    <?php
}

function submenu_4_page() {
    ?>
    <div class="wrap">
        <h1 style="direction: rtl; font-family: 'B Mitra', sans-serif; font-size: 36px; font-weight: bold; color: #000000; text-align: center; border: 2px solid #000000; padding: 10px; margin: 20px; border-radius: 8px; background-color: #f0f0f0; margin-bottom: 60px;">گزارش فروش و خرید بر اساس هفته و ماه</h1>
        
        <!-- فرم برای فیلتر سفارشات بر اساس هفته و ماه -->
        <form id="filter_orders_form" style="direction: rtl;" method="post" action="">
            <!-- انتخاب هفته -->
            <label style="font-family: 'B Mitra', sans-serif; font-size: 20px;" for="week">انتخاب هفته:</label><br>
            <select id="week" name="week" style="direction: rtl; width: 300px; padding: 10px; margin: 10px 0; font-family: 'B Mitra', sans-serif; font-size: 16px; border: 2px solid #4CAF50; border-radius: 5px;">
                <option value="">انتخاب هفته</option>
                <!-- داده‌ها از طریق AJAX پر می‌شوند -->
            </select><br><br>

            <!-- انتخاب ماه -->
            <label style="font-family: 'B Mitra', sans-serif; font-size: 20px;" for="month">انتخاب ماه:</label><br>
            <select id="month" name="month" style="direction: rtl; width: 300px; padding: 10px; margin: 10px 0; font-family: 'B Mitra', sans-serif; font-size: 16px; border: 2px solid #4CAF50; border-radius: 5px;">
                <option value="">انتخاب ماه</option>
                <!-- داده‌ها از طریق AJAX پر می‌شوند -->
            </select><br><br>

            <!-- سایر فیلدهای هزینه‌ها -->
            <label style="font-family: 'B Mitra', sans-serif; font-size: 20px;" for="machine_cost">هزینه ماشین:</label><br>
            <input type="text" id="machine_cost" name="machine_cost" style="direction: rtl; width: 300px; padding: 10px; margin: 10px 0;"><br><br>

            <label style="font-family: 'B Mitra', sans-serif; font-size: 20px;" for="worker_cost">هزینه کارگر:</label><br>
            <input type="text" id="worker_cost" name="worker_cost" style="direction: rtl; width: 300px; padding: 10px; margin: 10px 0;"><br><br>

            <label style="font-family: 'B Mitra', sans-serif; font-size: 20px;" for="sms_cost">هزینه پنل پیامکی:</label><br>
            <input type="text" id="sms_cost" name="sms_cost" style="direction: rtl; width: 300px; padding: 10px; margin: 10px 0;"><br><br>

            <!-- دکمه ارسال فرم -->
            <input type="submit" name="submit_report" value="محاسبه گزارش" style="direction: rtl; padding: 10px 20px; border-radius: 5px; font-family: 'B Mitra', sans-serif; font-size: 16px; cursor: pointer;">
        </form>

        <!-- نمایش نتایج -->
        <div id="report_results"></div>

        <!-- اسکریپت AJAX برای دریافت داده‌ها -->
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // فراخوانی AJAX برای دریافت هفته‌ها
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'get_weeks' },
                    success: function(response) {
                        $('#week').html(response); // پر کردن منوی کشویی هفته‌ها
                    }
                });

                // فراخوانی AJAX برای دریافت ماه‌ها
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'get_months' },
                    success: function(response) {
                        $('#month').html(response); // پر کردن منوی کشویی ماه‌ها
                    }
                });

                // ارسال فرم برای محاسبه گزارش
                $('#filter_orders_form').on('submit', function(e) {
                    e.preventDefault(); // جلوگیری از ارسال فرم به‌صورت پیش‌فرض
                    var data = $(this).serialize(); // جمع‌آوری داده‌های فرم

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: data + '&action=calculate_report',
                        success: function(response) {
$('#report_results').html(response); // نمایش نتایج گزارش
                        }
                    });
                });
            });
        </script>
    </div>
    <?php
}
// دریافت هفته‌ها از سفارشات ووکامرس
function get_weeks_callback() {
    global $wpdb;

    // دریافت سفارشات بر اساس هفته
    $weeks = $wpdb->get_results("
        SELECT DISTINCT WEEK(post_date) as week_num, YEAR(post_date) as year 
        FROM {$wpdb->prefix}posts 
        WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing')
        ORDER BY post_date DESC
    ");

    if (!empty($weeks)) {
        foreach ($weeks as $week) {
            echo '<option value="' . esc_attr($week->year . '-' . $week->week_num) . '">هفته ' . esc_html($week->week_num) . ' - سال ' . esc_html($week->year) . '</option>';
        }
    } else {
        echo '<option value="">هیچ هفته‌ای یافت نشد</option>';
    }

    wp_die();
}
add_action('wp_ajax_get_weeks', 'get_weeks_callback');

// دریافت ماه‌ها از سفارشات ووکامرس
function get_months_callback() {
    global $wpdb;

    // دریافت سفارشات بر اساس ماه
    $months = $wpdb->get_results("
        SELECT DISTINCT MONTH(post_date) as month_num, YEAR(post_date) as year 
        FROM {$wpdb->prefix}posts 
        WHERE post_type = 'shop_order' 
        AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending') 
        ORDER BY post_date DESC
    ");

    if (!empty($months)) {
        foreach ($months as $month) {
            // نمایش ماه و سال در منوی کشویی
            echo '<option value="' . esc_attr($month->year . '-' . $month->month_num) . '">ماه ' . esc_html($month->month_num) . ' - سال ' . esc_html($month->year) . '</option>';
        }
    } else {
        echo '<option value="">هیچ ماهی یافت نشد</option>';
    }

    wp_die();
}
add_action('wp_ajax_get_months', 'get_months_callback');
// دریافت هفته‌ها از سفارشات ووکامرس
// function get_weeks_callback() {
//     global $wpdb;

//     // دریافت سفارشات بر اساس هفته
//     $weeks = $wpdb->get_results("
//         SELECT DISTINCT WEEK(post_date) as week_num, YEAR(post_date) as year 
//         FROM {$wpdb->prefix}posts 
//         WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing')
//         ORDER BY post_date DESC
//     ");

//     if (!empty($weeks)) {
//         foreach ($weeks as $week) {
//             echo '<option value="' . esc_attr($week->year . '-' . $week->week_num) . '">هفته ' . esc_html($week->week_num) . ' - سال ' . esc_html($week->year) . '</option>';
//         }
//     } else {
//         echo '<option value="">هیچ هفته‌ای یافت نشد</option>';
//     }

//     wp_die();
// }
// add_action('wp_ajax_get_weeks', 'get_weeks_callback');

// دریافت ماه‌ها از سفارشات ووکامرس
// function get_months_callback() {
//     global $wpdb;

//     // دریافت سفارشات بر اساس ماه
//     $months = $wpdb->get_results("
//         SELECT DISTINCT MONTH(post_date) as month_num, YEAR(post_date) as year 
//         FROM {$wpdb->prefix}posts 
//         WHERE post_type = 'shop_order' 
//         AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending') 
//         ORDER BY post_date DESC
//     ");

//     if (!empty($months)) {
//         foreach ($months as $month) {
//             // نمایش ماه و سال در منوی کشویی
//             echo '<option value="' . esc_attr($month->year . '-' . $month->month_num) . '">ماه ' . esc_html($month->month_num) . ' - سال ' . esc_html($month->year) . '</option>';
//         }
//     } else {
//         echo '<option value="">هیچ ماهی یافت نشد</option>';
//     }

//     wp_die();
// }
// add_action('wp_ajax_get_months', 'get_months_callback');



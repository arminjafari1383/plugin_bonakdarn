<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
        <h1 style = "direction: rtl;font-family: 'B Mitra', sans-serif;font-size: 36px;font-weight: bold;color: #000000;text-align: center;border: 2px solid #000000;padding: 10px;margin: 20px;border-radius: 8px;background-color: #f0f0f0;margin-bottom: 60px;">گزارش ماهانه فروش و خرید</h1>
        <form style = "direction: rtl;" method="post" action="">
            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="month">نام </label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="first_name" id="month" placeholder="MM" required><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="year">نام خانوادگی </label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="last_name" id="year" placeholder="YYYY" required><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="machine_cost">تعداد ماموریت</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="mission" id="machine_cost" value=""><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="worker_cost"> وزن</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="weight" id="worker_cost" value=""><br><br>

            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="sms_cost">تاریخ تواد</label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="birthdate" id="sms_cost" value=""><br><br>
            
            <label style = "direction: rtl;font-family: 'B Mitra',sans-serif; font-size: 20px;" for="sms_cost">نام پدر </label><br>
            <input style = "direction: rtl;width: 300px;padding: 10px;margin: 10px 0;font-family: 'B Mitra, sans-serif';font-size: 16px;border: 2px solid #4CAF50;border-radius: 5px;box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);transition: border-color 0.3s ease;" type="text" name="avatar" id="sms_cost" value=""><br><br>
            
        

            <input style = "direction: rtl;padding: 10px 20px;border-radius: 5px;font-family: 'B Mitra', sans-serif;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;" type="submit" name="save_employee" value="ثبت کارمند">
        </form>
    </div>
<?php
include("db.php");

$insert_message = "";
$alter_message = "";
$drop_message = "";
$status = "";

// دریافت اطلاعات مشتریان برای نمایش
$customers = [];
$sql = "CALL GetCustomers()";
$result = $conn->query($sql);
if ($result) {
    // پردازش نتایج اول
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    // از next_result برای حرکت به کوئری بعدی استفاده کنید
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}

$products = [];
$sql = "CALL GetProduct()";
$result = $conn->query($sql);
if ($result) {
    // پردازش نتایج اول
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    // از next_result برای حرکت به کوئری بعدی استفاده کنید
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}

// بررسی ارسال فرم برای افزودن سفارش
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == "add_order") {
        $date = $_POST['date'];
        $status = $_POST['status'];
        $customer_id = $_POST['customer_id'];
        $selected_products = $_POST['products'] ?? []; // محصولات انتخاب شده

        $conn->begin_transaction(); // شروع تراکنش
        try {
            // افزودن سفارش به جدول order
            $stmt = $conn->prepare("INSERT INTO `order` (Date, Status, custmerid_foren) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $date, $status, $customer_id);
            if ($stmt->execute() === TRUE) {
                $order_id = $conn->insert_id; // دریافت ID سفارش

                // افزودن محصولات به جدول include
                foreach ($selected_products as $product_id) {
                    $stmt = $conn->prepare("CALL AddProductToOrder(?, ?)");
                    $stmt->bind_param("ii", $order_id, $product_id);
                    if (!$stmt->execute()) {
                        throw new Exception("خطا در افزودن محصول به سفارش: " . $conn->error);
                    }
                }

                $insert_message = "سفارش با موفقیت ذخیره شد.";
                $status = "success";
                $conn->commit(); // تایید تراکنش
            } else {
                throw new Exception("خطا در ذخیره سفارش: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback(); // بازگردانی تراکنش در صورت خطا
            $insert_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "edit_order") {
        $id = $_POST['id'];
        $date = $_POST['date'];
        $status = $_POST['status'];
        $customer_id = $_POST['customer_id'];
        $selected_products = $_POST['products'] ?? [];

        $conn->begin_transaction(); // شروع تراکنش
        try {
            // ویرایش سفارش
            $stmt = $conn->prepare("CALL EditOrder(?, ?, ?, ?)");
            $stmt->bind_param("isss", $id, $date, $status, $customer_id,);
            if ($stmt->execute() === TRUE) {
                // حذف محصولات قبلی از جدول include
                $stmt = $conn->prepare("DELETE FROM `include` WHERE orid = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // افزودن محصولات جدید به جدول include
                foreach ($selected_products as $product_id) {
                    $stmt = $conn->prepare("CALL AddProductToInclude(?, ?)");
                    $stmt->bind_param("ii", $id, $product_id);
                    if (!$stmt->execute()) {
                        throw new Exception("خطا در افزودن محصولات به سفارش: " . $conn->error);
                    }
                }

                $alter_message = "اطلاعات سفارش با موفقیت ویرایش شد.";
                $status = "success";
                $conn->commit(); // تایید تراکنش
            } else {
                throw new Exception("خطا در ویرایش اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback(); // بازگردانی تراکنش در صورت خطا
            $alter_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "delete_order") {
        $id = intval($_POST['id']);
    
        try {
            // ابتدا رکوردهای مربوطه را از جدول include حذف کنید
            $stmt = $conn->prepare("CALL DeleteIncludeByOrderId(?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();
    
            // سپس رکورد سفارش را از جدول order حذف کنید
            $stmt = $conn->prepare("CALL DeleteOrderById(?)");
            $stmt->bind_param("i", $id);
            if ($stmt->execute() === TRUE) {
                $drop_message = "اطلاعات سفارش با موفقیت حذف شد.";
                $status = "success";
            } else {
                throw new Exception("خطا در حذف اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $drop_message = $e->getMessage();
            $status = "error";
        }
    }     
}

// اجرای پروسیجر برای دریافت سفارش‌ها
$sql = "CALL GetOrdersWithDetails()";
$result = $conn->query($sql);
$orders = [];
if ($result) {
    // پردازش نتایج اول
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    // از next_result برای حرکت به کوئری بعدی استفاده کنید
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اطلاعات سفارش</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
            direction: rtl;
        }
    
        body {
            background: conic-gradient(
                from 240deg at 50% 50%,
                #00ffc3,
                #00fad9,
                #00f4f0,
                #00eeff,
                #00e6ff,
                #00dcff,
                #00d2ff,
                #00c5ff,
                #00b8ff,
                #6da8ff,
                #9f97ff,
                #c285ff
              );
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
    
        .container {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            width: 100%;
        }
    
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    
    
        input[type="text"], input[type="number"], input[type="date"], select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        input[type="currency"],select {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-left: 25px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background: #333;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 15px;
            margin-bottom: 10px;
        }
    
        button:hover {
            background: #676768;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            text-align: center;
            padding: 8px;
        }

        th {
            background-color: #f4f4f4;
        }
    
        .footer {
            position: fixed;
            bottom: 10px;
            right: 10px;
            font-size: 12px;
            color: #777;
            font-style: italic;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #333;
        }

        .icon-btn i {
            font-size: 18px;
        }

        .icon-btn i:hover {
            color: #555;
        }

        .alert {
            margin: 10px 0;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-close {
            float: right;
            font-size: 20px;
            line-height: 20px;
            cursor: pointer;
            color: inherit;
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            border-radius: 8px;
        }

        .modal.active {
            display: block;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .modal-overlay.active {
            display: block;
        }

        .home-button {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            background-color:rgb(97, 255, 110);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .home-button i {
            color: #fff;
            font-size: 24px;
            transition: transform 0.2s ease-in-out;
        }

        .home-button:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(82, 185, 13, 0.5);
        }

        .home-button:hover i {
            transform: rotate(20deg) scale(1.1);
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <a href="http://localhost/project/main.html?open=true" class="home-button" title="Home">
        <i class="fas fa-home"></i>
    </a>


    <div class="container">
        <!-- نمایش پیام‌ها -->
        <?php if (!empty($insert_message)): ?>
            <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?>">
                <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
                <?php echo $insert_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($alter_message)): ?>
            <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?>">
                <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
                <?php echo $alter_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($drop_message)): ?>
            <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?>">
                <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
                <?php echo $drop_message; ?>
            </div>
        <?php endif; ?>

        <!-- فرم اطلاعات سفارش -->
        <h2>اطلاعات سفارش</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_order">
            <label>تاریخ:</label>
            <input type="date" name="date" required>
            <select name="status" required>
                <option value="" disabled selected>وضعیت</option>
                <option value="موفق">موفق</option>
                <option value="ناموفق">ناموفق</option>
                <option value="نامعلوم">نامعلوم</option>
            </select>
            <select name="customer_id" required>
                <option value="" disabled selected>انتخاب مشتری</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['cu_ID']; ?>">
                        <?php echo $customer['cu_ID'] . " - " . $customer['address'] . " - " . $customer['type']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
            <label>محصولات:</label>
            <div>
                <?php foreach ($products as $product): ?>
                    <label>
                        <input type="checkbox" name="products[]" value="<?php echo $product['PRO_ID']; ?>">
                        <?php echo $product['product_name'] . " (قیمت: " . $product['price'] . ")"; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">ثبت سفارش</button>
        </form>

        <!-- جدول اطلاعات سفارش‌ها -->
        <h3>داده‌های سفارش‌ها</h3>
        <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>تاریخ</th>
                        <th>وضعیت</th>
                        <th>اطلاعات مشتری</th>
                        <th>محصولات</th>
                        <th>قیمت‌ها</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['OR_ID']; ?></td>
                            <td><?php echo htmlspecialchars($order['Date']); ?></td>
                            <td><?php echo htmlspecialchars($order['Status']); ?></td>
                            <td><?php echo $order['cu_ID'] ."---".$order['address']."---".$order['type']; ?></td>
                            <td><?php echo htmlspecialchars($order['product_names']); ?></td>
                            <td><?php echo htmlspecialchars($order['product_prices']); ?></td>
                            <td style="display: flex; align-items: center; justify-content: center;">
                                <div>
                                <button class="icon-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                </div>
                                <div>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="delete_order">
                                    <input type="hidden" name="id" value="<?php echo $order['OR_ID']; ?>">
                                    <button class="icon-btn" onclick="return confirm('آیا مطمئن هستید؟')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-danger">هیچ داده‌ای موجود نیست.</div>
        <?php endif; ?>
    </div>
    
    <!-- فرم پاپ‌آپ ویرایش -->
    <div class="modal-overlay" id="modal-overlay"></div>
    <div class="modal" id="edit-modal">
        <h2>ویرایش سفارش</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit_order">
            <input type="hidden" name="id" id="edit-id">
            <label>تاریخ:</label>
            <input type="date" name="date" id="edit-date" placeholder="تاریخ" required>
            <select name="status" id="edit-status" required>
                <option value="" disabled selected>وضعیت</option>
                <option value="موفق">موفق</option>
                <option value="ناموفق">ناموفق</option>
                <option value="نامعلوم">نامعلوم</option>
            </select>
            <select name="customer_id" id="edit-customer_id" required>
                <option value="" disabled selected>انتخاب مشتری</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['cu_ID']; ?>">
                        <?php echo $customer['cu_ID'] . " - " . $customer['address'] . " - " . $customer['type']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
            <label>محصولات:</label>
            <div>
                <?php foreach ($products as $product): ?>
                    <label>
                        <input type="checkbox" class="edit-product" name="products[]" value="<?php echo $product['PRO_ID']; ?>">
                        <?php echo $product['product_name'] . " (قیمت: " . $product['price'] . ")"; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">ذخیره تغییرات</button>
            <button type="button" onclick="closeEditModal()">لغو</button>
        </form>
    </div>
    
    <div class="footer">درست شده توسط @eh3ano-0 برای درس پایگاه داده</div>

    <script>
        function openEditModal(order) {
            document.getElementById('edit-id').value = order.OR_ID;
            document.getElementById('edit-date').value = order.Date;
            document.getElementById('edit-status').value = order.Status;
            document.getElementById('edit-customer_id').value = order.custmerid_foren;

            // انتخاب محصولات مربوطه
            const selectedProducts = order.product_names.split(', ');
            document.querySelectorAll('.edit-product').forEach(input => {
                input.checked = selectedProducts.includes(input.nextSibling.nodeValue.trim());
            });

            document.getElementById('edit-modal').classList.add('active');
            document.getElementById('modal-overlay').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('edit-modal').classList.remove('active');
            document.getElementById('modal-overlay').classList.remove('active');
        }

    </script>                    

</body>
</html>
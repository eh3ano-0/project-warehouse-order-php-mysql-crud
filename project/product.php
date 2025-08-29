<?php
include("db.php");

$insert_message = "";
$alter_message = "";
$drop_message = "";
$status = "";

$categorys = [];
$sql = "CALL GetCategories()";
$result = $conn->query($sql);
if ($result) {
    // پردازش نتایج اول
    while ($row = $result->fetch_assoc()) {
        $categorys[] = $row;
    }
    // از next_result برای حرکت به کوئری بعدی استفاده کنید
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}

// دریافت اطلاعات انبارها با استفاده از پروسیجر
$warehouses = [];
$sql = "CALL GetWarehouses()";
$result = $conn->query($sql);
if ($result) {
    // پردازش نتایج اول
    while ($row = $result->fetch_assoc()) {
        $warehouses[] = $row;
    }
    // از next_result برای حرکت به کوئری بعدی استفاده کنید
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}

// بررسی ارسال فرم برای افزودن کارمند
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == "add_product") {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $warehouse_id = $_POST['warehouse_id'];

        try {
            $stmt = $conn->prepare("CALL AddProduct(?, ?, ?, ?)");
            $stmt->bind_param("sdii", $name, $price, $category_id, $warehouse_id);
            if ($stmt->execute()) {
                $insert_message = "اطلاعات محصول با موفقیت ذخیره شد.";
                $status = "success";
            } else {
                throw new Exception("خطا در ذخیره اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $insert_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "edit_product") {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $warehouse_id = $_POST['warehouse_id'];

        try {
            $stmt = $conn->prepare("CALL EditProduct(?, ?, ?, ?, ?)");
            $stmt->bind_param("isdii", $id, $name, $price, $category_id, $warehouse_id);
            if ($stmt->execute()) {
                $alter_message = "اطلاعات محصول با موفقیت ویرایش شد.";
                $status = "success";
            } else {
                throw new Exception("خطا در ویرایش اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $alter_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "delete_product") {
        $id = intval($_POST['id']);

        try {
            $stmt = $conn->prepare("CALL DeleteProduct(?)");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $drop_message = "اطلاعات محصول با موفقیت حذف شد.";
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


$conn->close();
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اطلاعات محصول</title>
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

        <!-- فرم اطلاعات محصول -->
        <h2>اطلاعات محصول</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_product">
            <input type="text" name="name" placeholder="نام محصول" required />
            <input type="number" name="price" placeholder="قیمت" required />
            <select name="category_id" required>
                <option value="" disabled selected>انتخاب دسته بندی</option>
                <?php foreach ($categorys as $category): ?>
                    <option value="<?php echo $category['CAT_ID']; ?>">
                        <?php echo $category['name'] . " - توضیحات : " . $category['comment'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="warehouse_id" required>
                <option value="" disabled selected>انتخاب انبار</option>
                <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?php echo $warehouse['WARE_ID']; ?>">
                        <?php echo $warehouse['name'] . " - مکان: " . $warehouse['location'] . " (ظرفیت: " . $warehouse['capacity'] . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">ثبت</button>
        </form>

        <!-- جدول اطلاعات محصول -->
        <h3>داده‌های محصول</h3>
        <?php if (count($products) > 0): ?>   
            <table>
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>نام محصول</th>
                        <th>قیمت</th>
                        <th>نام دسته بندی</th>
                        <th>نوع</th>
                        <th>نام انبار</th>
                        <th>ظرفیت انبار</th>
                        <th>مکان انبار</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['PRO_ID']; ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['price']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_comment']); ?></td>
                            <td><?php echo htmlspecialchars($product['warehouse_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['warehouse_capacity']); ?></td>
                            <td><?php echo htmlspecialchars($product['warehouse_location']); ?></td>
                            <td style="display: flex; align-items: center; justify-content: center;">
                                <div>
                                <button class="icon-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                </div>
                                <div>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="id" value="<?php echo $product['PRO_ID']; ?>">
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
        <h2>ویرایش محصول</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="id" id="edit-id">
            <input type="text" name="name" id="edit-name" placeholder="نام" required>
            <input type="text" name="price" id="edit-price" placeholder="قیمت" required>
            <select name="category_id" id="edit-category-id" required>
                <option value="" disabled selected>انتخاب مشتری</option>
                <?php foreach ($categorys as $category): ?>
                    <option value="<?php echo $category['CAT_ID']; ?>">
                        <?php echo $category['name'] . " - توضیحات : " . $category['comment'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="warehouse_id" id="edit-warehouse-id" required>
                <option value="" disabled selected>انتخاب انبار</option>
                <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?php echo $warehouse['WARE_ID']; ?>">
                        <?php echo $warehouse['name'] . " - مکان: " . $warehouse['location'] . " (ظرفیت: " . $warehouse['capacity'] . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">ذخیره تغییرات</button>
            <button type="button" onclick="closeEditModal()">لغو</button>
        </form>
    </div>

    <div class="footer">درست شده توسط @eh3ano-0 برای درس پایگاه داده</div>

    <script>
        function openEditModal(product) {
            document.getElementById('edit-id').value = product.PRO_ID;
            document.getElementById('edit-name').value = product.product_name;
            document.getElementById('edit-price').value = product.price;
            document.getElementById('edit-category-id').value = product.category_id;
            document.getElementById('edit-warehouse-id').value = product.warehouse_id;

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

<?php

include("db.php");

$insert_message = "";
$alter_message = "";
$drop_message = "";
$status = "";

// بررسی ارسال فرم برای افزودن مشتری
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == "add_customer") {
        $id = $_POST['id'];
        $address = $_POST['address'];
        $type = $_POST['type'];

        try {
            $stmt = $conn->prepare("CALL AddCustomer(?, ?, ?)");
            $stmt->bind_param("sss", $id, $address, $type);
            $stmt->execute();
            $insert_message = "اطلاعات مشتری با موفقیت ذخیره شد.";
            $status = "success";
        } catch (Exception $e) {
            $insert_message = "خطا در ذخیره اطلاعات: " . $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "edit_customer") {
        $id = $_POST['id'];
        $address = $_POST['address'];
        $type = $_POST['type'];

        try {
            $stmt = $conn->prepare("CALL EditCustomer(?, ?, ?)");
            $stmt->bind_param("sss", $id, $address, $type);
            $stmt->execute();
            $alter_message = "اطلاعات مشتری با موفقیت ویرایش شد.";
            $status = "success";
        } catch (Exception $e) {
            $alter_message = "خطا در ویرایش اطلاعات: " . $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "delete_customer") {
        $id = strval($_POST['id']);

        try {
            $stmt = $conn->prepare("CALL DeleteCustomerp(?)");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $drop_message = "اطلاعات مشتری با موفقیت حذف شد.";
            $status = "success";
        } catch (Exception $e) {
            $drop_message = "خطا در حذف اطلاعات: " . $e->getMessage();
            $status = "error";
        }
    }
}


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

// دریافت اطلاعات شخصی برای نمایش
$persons = [];
$sql = "CALL GetPerson()";
$result = $conn->query($sql);
if ($result) {
    // پردازش نتایج دوم
    while ($row = $result->fetch_assoc()) {
        $persons[] = $row;
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
    <title>اطلاعات مشتری</title>
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

        <!-- فرم اطلاعات مشتری -->
        <h2>اطلاعات مشتری</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_customer">
            <select name="id" required>
                <option value="" disabled selected>انتخاب شخص</option>
                <?php foreach ($persons as $person): ?>
                <option value="<?php echo $person['person_id']; ?>">
                    <?php echo "کدملی: ".$person['person_id']. " - اسم:".$person['first_name'] . " " . $person['last_name']." - نام پدر: ".$person['name_father']." - ادرس:".$person['phone'] . " - " . $person['email']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <textarea style=" resize: none" id="address" name="address" rows="4" cols="126" placeholder="  آدرس  " required></textarea>
            <select name="type" id="type"  required>
                <option value="" disabled selected>نوع مشتری </option>
                <option value="ساده">ساده</option>
                <option value="سطح برنز">سطح برنز</option>
                <option value="سطح نقره ای">سطح نقره ای</option>
                <option value="سطح طلایی">سطح طلایی</option>
                <option value="vip">vip</option>
            </select>
            <button type="submit">ثبت مشتری</button>
        </form>

        <!-- جدول اطلاعات مشتری -->
        <h3>داده‌های مشتری</h3>
        <?php if (count($customers) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>کد ملی</th>
                        <th>نام و نام خانودگی </th>
                        <th>آدرس</th>
                        <th>نوع مشتری</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo $customer['cu_ID']; ?></td>
                            <td><?php echo $customer['first_name'] . " " . $customer['last_name']. " (" . $customer['gender'] .")"; ?></td>
                            <td><?php echo htmlspecialchars($customer['address']); ?></td>
                            <td><?php echo htmlspecialchars($customer['type']); ?></td>
                            <td style="display: flex; align-items: center; justify-content: center;">
                                <div>
                                <button class="icon-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($customer)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                </div>
                                <div>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="id" value="<?php echo $customer['cu_ID']; ?>">
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
        <h2>ویرایش مشتری</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit_customer">
            <input type="text" name="id" id="edit-id" placeholder="کد ملی" required readonly>
            <textarea style=" resize: none" id="edit-address" name="address" rows="4" cols="126" placeholder="  آدرس  " required></textarea>
            <select name="type" id="edit-type"  required>
                <option value="" disabled selected>نوع مشتری </option>
                <option value="ساده">ساده</option>
                <option value="سطح برنز">سطح برنز</option>
                <option value="سطح نقره ای">سطح نقره ای</option>
                <option value="سطح طلایی">سطح طلایی</option>
                <option value="vip">vip</option>
            </select>
            <button type="submit">ذخیره تغییرات</button>
            <button type="button" onclick="closeEditModal()">لغو</button>
        </form>
    </div>

    <div class="footer">درست شده توسط @eh3ano-0 برای درس پایگاه داده</div>

    <script>
        function openEditModal(customer) {
            document.getElementById('edit-id').value = customer.cu_ID;
            document.getElementById('edit-address').value = customer.address;
            document.getElementById('edit-type').value = customer.type;

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

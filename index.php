<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - โรงเรียนสาธิตวิทยา</title>
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-card img {
            width: 80px;
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 20px;
            padding: 10px 20px;
            margin-bottom: 20px;
        }
        .btn-login {
            border-radius: 20px;
            padding: 10px 20px;
            width: 100%;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="images/favicon.png" alt="School Logo" class="rounded-circle shadow-sm" style="background:#fff; padding:5px;">
        <h3 class="mb-4">เข้าสู่ระบบ</h3>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
                ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง!
            </div>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <input type="text" name="username" class="form-control" placeholder="ชื่อผู้ใช้งาน" required>
            <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
            <button type="submit" class="btn btn-primary btn-login">เข้าสู่ระบบ</button>
        </form>
    </div>
</body>
</html>

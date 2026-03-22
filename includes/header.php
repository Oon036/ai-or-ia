<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Require login for pages that include header, except for index.php explicitly
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $current_page !== 'index.php') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริหารจัดการโรงเรียนสาธิตวิทยา</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php if ($current_page !== 'index.php') include 'sidebar.php'; ?>
        
        <div class="main-content <?php echo ($current_page == 'index.php') ? 'm-0 w-100' : ''; ?>">
            <?php if ($current_page !== 'index.php'): ?>
            <header class="top-navbar">
                <div class="d-flex align-items-center">
                    <button id="sidebarToggle" class="me-3">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0 text-dark">โรงเรียนสาธิตวิทยา</h4>
                </div>
                <div class="user-profile">
                    <?php if(isset($_SESSION['username'])): ?>
                        <span class="me-2 text-dark">สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                        <a href="logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                    <?php endif; ?>
                </div>
            </header>
            <?php endif; ?>
            
            <div class="content-wrapper mt-3">

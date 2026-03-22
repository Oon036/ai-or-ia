<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <img src="images/favicon.png" alt="Logo" width="40" height="40" class="me-2 rounded-circle bg-white p-1">
        <h5 class="logo-text mb-0 text-dark fw-bold">สาธิตวิทยา</h5>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>แดชบอร์ด</span>
            </a>
        </li>

        <?php if ($role === 'admin'): ?>
        <li class="has-submenu <?php echo in_array($current_page, ['teachers.php', 'students.php', 'subjects.php', 'classes.php', 'classrooms.php']) ? 'open' : ''; ?>">
            <a href="#">
                <i class="fas fa-database"></i>
                <span>ข้อมูลพื้นฐาน</span>
            </a>
            <ul class="submenu">
                <li class="<?php echo ($current_page == 'teachers.php') ? 'active' : ''; ?>">
                    <a href="teachers.php"><i class="fas fa-chalkboard-teacher"></i> ข้อมูลครูผู้สอน</a>
                </li>
                <li class="<?php echo ($current_page == 'students.php') ? 'active' : ''; ?>">
                    <a href="students.php"><i class="fas fa-user-graduate"></i> ข้อมูลนักเรียน</a>
                </li>
                <li class="<?php echo ($current_page == 'subjects.php') ? 'active' : ''; ?>">
                    <a href="subjects.php"><i class="fas fa-book"></i> ข้อมูลรายวิชา</a>
                </li>
                <li class="<?php echo ($current_page == 'classes.php') ? 'active' : ''; ?>">
                    <a href="classes.php"><i class="fas fa-layer-group"></i> ข้อมูลระดับชั้น</a>
                </li>
                <li class="<?php echo ($current_page == 'classrooms.php') ? 'active' : ''; ?>">
                    <a href="classrooms.php"><i class="fas fa-door-open"></i> ข้อมูลห้องเรียน</a>
                </li>
            </ul>
        </li>
        <?php endif; ?>

        <?php if ($role === 'admin' || $role === 'teacher'): ?>
        <li class="<?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>">
            <a href="schedule.php">
                <i class="fas fa-calendar-alt"></i>
                <span>จัดการตารางเรียน</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'attendance.php') ? 'active' : ''; ?>">
            <a href="attendance.php">
                <i class="fas fa-user-check"></i>
                <span>เช็คชื่อเข้าเรียน</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'grading.php') ? 'active' : ''; ?>">
            <a href="grading.php">
                <i class="fas fa-marker"></i>
                <span>บันทึกผลการเรียน</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($role === 'student'): ?>
        <li class="<?php echo ($current_page == 'my_schedule.php') ? 'active' : ''; ?>">
            <a href="my_schedule.php">
                <i class="fas fa-calendar"></i>
                <span>ตารางเรียนของฉัน</span>
            </a>
        </li>
        <li class="<?php echo ($current_page == 'my_grades.php') ? 'active' : ''; ?>">
            <a href="my_grades.php">
                <i class="fas fa-star"></i>
                <span>ผลการเรียนของฉัน</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>

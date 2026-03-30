<?php
require_once 'includes/db.php';
include 'includes/header.php';

if ($_SESSION['role'] !== 'student') {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

$student_id = $_SESSION['reference_id'];
// Get student's class_id
$stmt = $pdo->prepare("SELECT class_id FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$class_id = $stmt->fetchColumn();

// Fetch schedule for this class
$schedule_stmt = $pdo->prepare("
    SELECT s.*, sub.subject_code, sub.subject_name, t.first_name, t.last_name, c.class_name, r.room_name 
    FROM schedule s 
    LEFT JOIN subjects sub ON s.subject_id = sub.id 
    LEFT JOIN teachers t ON s.teacher_id = t.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN classrooms r ON s.room_id = r.id 
    WHERE s.class_id = ? 
    ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), s.start_time
");
$schedule_stmt->execute([$class_id]);
$schedules = $schedule_stmt->fetchAll();

$day_th = ['Monday'=>'จันทร์', 'Tuesday'=>'อังคาร', 'Wednesday'=>'พุธ', 'Thursday'=>'พฤหัสบดี', 'Friday'=>'ศุกร์'];
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold"><i class="fas fa-calendar text-primary me-2"></i>ตารางเรียนของฉัน</h1>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>วัน</th>
                            <th>เวลา</th>
                            <th>วิชา</th>
                            <th>คุณครูผู้สอน</th>
                            <th>ห้องเรียน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($schedules) > 0): ?>
                            <?php foreach($schedules as $s): ?>
                            <tr>
                                <td class="fw-bold"><?php echo isset($day_th[$s['day_of_week']]) ? $day_th[$s['day_of_week']] : $s['day_of_week']; ?></td>
                                <td><?php echo date('H:i', strtotime($s['start_time'])) . ' - ' . date('H:i', strtotime($s['end_time'])); ?></td>
                                <td><?php echo htmlspecialchars($s['subject_code'].' '.$s['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['room_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">ยังไม่มีข้อมูลตารางเรียน</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>

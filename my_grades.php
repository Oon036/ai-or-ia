<?php
require_once 'includes/db.php';
include 'includes/header.php';

if ($_SESSION['role'] !== 'student') {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

$student_id = $_SESSION['reference_id'];
// Fetch grades for this student
$grade_stmt = $pdo->prepare("
    SELECT g.*, sub.subject_code, sub.subject_name 
    FROM grades g 
    LEFT JOIN subjects sub ON g.subject_id = sub.id 
    WHERE g.student_id = ? 
    ORDER BY sub.subject_code
");
$grade_stmt->execute([$student_id]);
$grades = $grade_stmt->fetchAll();
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold"><i class="fas fa-star text-warning me-2"></i>ผลการเรียนของฉัน</h1>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-light">
                        <tr>
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th class="text-center">คะแนนดิบ</th>
                            <th class="text-center">ระดับผลการเรียน (เกรด)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($grades) > 0): ?>
                            <?php foreach($grades as $g): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($g['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($g['subject_name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($g['raw_score'] ?? '-'); ?></td>
                                <td class="text-center fw-bold text-success"><?php echo number_format($g['grade'], 1); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">ยังไม่มีข้อมูลผลการเรียน</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>

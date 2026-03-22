<?php
require_once 'includes/db.php';
include 'includes/header.php';

$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'teacher'])) {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
$err_message = '';

// Handle Grade Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_grades') {
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'];
    $raw_scores = $_POST['raw_score'] ?? []; // Associative array: student_id => raw_score

    try {
        $pdo->beginTransaction();

        foreach ($raw_scores as $student_id => $score) {
            if ($score === '' || !is_numeric($score)) continue;
            
            $score = floatval($score);
            if ($score > 100) $score = 100;
            if ($score < 0) $score = 0;

            // Calculate Grade automatically
            // 80-100=4, 70-79=3, 60-69=2, 50-59=1, 0-49=0
            if ($score >= 80) $grade = 4.0;
            elseif ($score >= 70) $grade = 3.0;
            elseif ($score >= 60) $grade = 2.0;
            elseif ($score >= 50) $grade = 1.0;
            else $grade = 0.0;

            // Check if exists
            $stmt_check = $pdo->prepare("SELECT id FROM grades WHERE subject_id = ? AND student_id = ?");
            $stmt_check->execute([$subject_id, $student_id]);
            if ($stmt_check->rowCount() > 0) {
                // Update
                $stmt_upd = $pdo->prepare("UPDATE grades SET raw_score = ?, grade = ?, teacher_id = ? WHERE subject_id = ? AND student_id = ?");
                $stmt_upd->execute([$score, $grade, $teacher_id, $subject_id, $student_id]);
            } else {
                // Insert
                $stmt_ins = $pdo->prepare("INSERT INTO grades (subject_id, student_id, raw_score, grade, teacher_id) VALUES (?, ?, ?, ?, ?)");
                $stmt_ins->execute([$subject_id, $student_id, $score, $grade, $teacher_id]);
            }
        }

        $pdo->commit();
        $message = "บันทึกข้อมูลผลการเรียนเรียบร้อยแล้วระบบได้คำนวณเกรดอัตโนมัติ";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $err_message = "เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage();
    }
}

// Fetch Schedules grouped by Subject and Class for assigning grades
// Grading usually depends on Subject and Class context
$query_groups = "
    SELECT DISTINCT sch.subject_id, sch.class_id, sch.teacher_id,
           subj.subject_code, subj.subject_name,
           c.class_name
    FROM schedule sch
    JOIN subjects subj ON sch.subject_id = subj.id
    JOIN classes c ON sch.class_id = c.id
";
if ($role == 'teacher') {
    $teacher_ref_id = $_SESSION['reference_id'];
    $query_groups .= " WHERE sch.teacher_id = " . intval($teacher_ref_id);
}
$groups = $pdo->query($query_groups)->fetchAll();

$selected_group = $_GET['group'] ?? ''; // Format: subject_id,class_id,teacher_id
$students = [];
$grades_data = [];
$selected_subject_name = "";

if ($selected_group) {
    list($sel_subj_id, $sel_class_id, $sel_teacher_id) = explode(',', $selected_group);
    
    // Fetch students
    $stmt_s = $pdo->prepare("SELECT id, student_code, prefix, first_name, last_name FROM students WHERE class_id = ? ORDER BY student_code ASC");
    $stmt_s->execute([$sel_class_id]);
    $students = $stmt_s->fetchAll();

    // Fetch existing grades
    $stmt_g = $pdo->prepare("SELECT student_id, raw_score, grade FROM grades WHERE subject_id = ?");
    $stmt_g->execute([$sel_subj_id]);
    while ($row = $stmt_g->fetch()) {
        $grades_data[$row['student_id']] = [
            'raw_score' => $row['raw_score'],
            'grade' => $row['grade']
        ];
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">บันทึกผลการเรียน</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($err_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $err_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">เลือกรายวิชาและระดับชั้น</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="grading.php" class="row">
                <div class="col-md-9 mb-3">
                    <label>กลุ่มเรียน (วิชา - ชั้นเรียน)</label>
                    <select name="group" class="form-control" required>
                        <option value="">-- เลือกกลุ่มเรียน --</option>
                        <?php foreach($groups as $g): 
                            $val = $g['subject_id'].','.$g['class_id'].','.$g['teacher_id'];
                            $sel = ($selected_group == $val) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $val; ?>" <?php echo $sel; ?>>
                                <?php echo htmlspecialchars($g['subject_code'].' '.$g['subject_name'].' - '.$g['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">ดึงข้อมูลนักเรียน</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_group): ?>
    <div class="card shadow mb-4 border-left-info">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-info">แบบบันทึกคะแนนเก็บ/สอบ</h6>
        </div>
        <div class="card-body">
            <?php if (count($students) > 0): ?>
            <form method="POST" action="grading.php?group=<?php echo urlencode($selected_group); ?>">
                <input type="hidden" name="action" value="save_grades">
                <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($sel_subj_id); ?>">
                <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($sel_teacher_id); ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead class="bg-light">
                            <tr>
                                <th width="15%">รหัสประจำตัว</th>
                                <th width="35%">ชื่อ-นามสกุล</th>
                                <th width="20%">คะแนนดิบ (เต็ม 100)</th>
                                <th width="15%">เกรดอัตโนมัติ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $st): 
                                $raw = $grades_data[$st['id']]['raw_score'] ?? '';
                                $grade = $grades_data[$st['id']]['grade'] ?? '';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($st['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($st['prefix'].$st['first_name'].' '.$st['last_name']); ?></td>
                                <td>
                                    <input type="number" step="0.1" max="100" min="0" class="form-control text-center score-input" name="raw_score[<?php echo $st['id']; ?>]" value="<?php echo htmlspecialchars($raw); ?>">
                                </td>
                                <td>
                                    <?php if ($grade !== ''): ?>
                                        <div class="fs-5 text-center fw-bold <?php echo floatval($grade) > 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo number_format($grade, 1); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted">-รอการบันทึก-</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-success px-5"><i class="fas fa-save"></i> บันทึกผลการเรียนและคำนวณเกรด</button>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-warning">ไม่มีนักเรียนในกลุ่มเรียนนี้</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

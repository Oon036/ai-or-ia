<?php
require_once 'includes/db.php';
include 'includes/header.php';

$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'teacher'])) {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

// Fetch Schedules for the user
$query_sch = "
    SELECT sch.id, sch.day_of_week, sch.start_time, sch.end_time,
           subj.subject_name, c.class_name, sch.class_id
    FROM schedule sch
    JOIN subjects subj ON sch.subject_id = subj.id
    JOIN classes c ON sch.class_id = c.id
";
if ($role == 'teacher') {
    $teacher_ref_id = $_SESSION['reference_id'];
    $query_sch .= " WHERE sch.teacher_id = " . intval($teacher_ref_id);
}
$schedules = $pdo->query($query_sch)->fetchAll();

$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_schedule = $_GET['schedule_id'] ?? '';
$students = [];
$attendance_data = [];

if ($selected_schedule && $selected_date) {
    // Get class_id from selected schedule
    $stmt_c = $pdo->prepare("SELECT class_id FROM schedule WHERE id = ?");
    $stmt_c->execute([$selected_schedule]);
    $class_id = $stmt_c->fetchColumn();

    if ($class_id) {
        // Fetch students in this class
        $stmt_s = $pdo->prepare("SELECT id, student_code, prefix, first_name, last_name FROM students WHERE class_id = ? ORDER BY student_code ASC");
        $stmt_s->execute([$class_id]);
        $students = $stmt_s->fetchAll();

        // Fetch existing attendance
        $stmt_a = $pdo->prepare("SELECT student_id, status FROM attendance WHERE schedule_id = ? AND date = ?");
        $stmt_a->execute([$selected_schedule, $selected_date]);
        while ($row = $stmt_a->fetch()) {
            $attendance_data[$row['student_id']] = $row['status'];
        }
    }
}
$days = ['Monday'=>'จันทร์', 'Tuesday'=>'อังคาร', 'Wednesday'=>'พุธ', 'Thursday'=>'พฤหัสบดี', 'Friday'=>'ศุกร์', 'Saturday'=>'เสาร์', 'Sunday'=>'อาทิตย์'];
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">เช็คชื่อเข้าเรียน</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">เลือกคาบเรียนและวันที่</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="attendance.php" class="row">
                <div class="col-md-5 mb-3">
                    <label>ตารางเรียน</label>
                    <select name="schedule_id" class="form-control" required>
                        <option value="">-- เลือกคาบเรียน --</option>
                        <?php foreach($schedules as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $selected_schedule == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['subject_name'].' ('.$s['class_name'].') - '.$days[$s['day_of_week']].' '.date('H:i',strtotime($s['start_time'])).'-'.date('H:i',strtotime($s['end_time']))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>วันที่</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">ค้นหารายชื่อ</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_schedule): ?>
    <div class="card shadow mb-4 border-left-success">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-success">รายชื่อนักเรียน (ระบบบันทึกอัตโนมัติเมื่อเลือกสถานะ)</h6>
            <div id="saveStatus" class="text-xs font-weight-bold text-primary text-uppercase mb-1"></div>
        </div>
        <div class="card-body">
            <?php if (count($students) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="10%">รหัสประจำตัว</th>
                            <th width="30%">ชื่อ-นามสกุล</th>
                            <th class="text-center">สถานะการเข้าเรียน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $st): 
                            $status = $attendance_data[$st['id']] ?? 'Present'; // default to Present if not recorded
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($st['student_code']); ?></td>
                            <td><?php echo htmlspecialchars($st['prefix'].$st['first_name'].' '.$st['last_name']); ?></td>
                            <td>
                                <div class="d-flex justify-content-center gap-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input att-radio" type="radio" name="att_<?php echo $st['id']; ?>" id="p_<?php echo $st['id']; ?>" value="Present" data-student="<?php echo $st['id']; ?>" <?php echo $status=='Present'?'checked':''; ?>>
                                        <label class="form-check-label text-success fw-bold" for="p_<?php echo $st['id']; ?>">มาเรียน</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input att-radio" type="radio" name="att_<?php echo $st['id']; ?>" id="a_<?php echo $st['id']; ?>" value="Absent" data-student="<?php echo $st['id']; ?>" <?php echo $status=='Absent'?'checked':''; ?>>
                                        <label class="form-check-label text-danger fw-bold" for="a_<?php echo $st['id']; ?>">ขาด</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input att-radio" type="radio" name="att_<?php echo $st['id']; ?>" id="l_<?php echo $st['id']; ?>" value="Late" data-student="<?php echo $st['id']; ?>" <?php echo $status=='Late'?'checked':''; ?>>
                                        <label class="form-check-label text-warning fw-bold" for="l_<?php echo $st['id']; ?>">สาย</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input att-radio" type="radio" name="att_<?php echo $st['id']; ?>" id="lv_<?php echo $st['id']; ?>" value="Leave" data-student="<?php echo $st['id']; ?>" <?php echo $status=='Leave'?'checked':''; ?>>
                                        <label class="form-check-label text-info fw-bold" for="lv_<?php echo $st['id']; ?>">ลา</label>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-warning">ไม่มีนักเรียนในชั้นเรียนนี้ หรือยังไม่ได้เพิ่มนักเรียนเข้าชั้นเรียน</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.att-radio').on('change', function() {
        var studentId = $(this).data('student');
        var status = $(this).val();
        var scheduleId = '<?php echo $selected_schedule; ?>';
        var dateVal = '<?php echo $selected_date; ?>';

        $('#saveStatus').html('<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...');
        
        $.ajax({
            url: 'ajax_attendance.php',
            type: 'POST',
            data: {
                student_id: studentId,
                schedule_id: scheduleId,
                date: dateVal,
                status: status
            },
            success: function(response) {
                var res = JSON.parse(response);
                if(res.success) {
                    $('#saveStatus').html('<span class="text-success"><i class="fas fa-check"></i> บันทึกแล้ว</span>');
                    setTimeout(function() { $('#saveStatus').html(''); }, 2000);
                } else {
                    $('#saveStatus').html('<span class="text-danger"><i class="fas fa-times"></i> ผิดพลาด!</span>');
                }
            },
            error: function() {
                $('#saveStatus').html('<span class="text-danger"><i class="fas fa-times"></i> การเชื่อมต่อล้มเหลว</span>');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

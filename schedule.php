<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Only admin and teacher can access Schedule, but usually only Admin creates it, or Admin can assign Teacher.
// Let's allow admin and teacher to view, admin to manage.
$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'teacher'])) {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
$err_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'admin') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'add') {
            $subj_id = $_POST['subject_id'];
            $teacher_id = $_POST['teacher_id'];
            $class_id = $_POST['class_id'];
            $room_id = $_POST['room_id'];
            $day = $_POST['day_of_week'];
            $s_time = $_POST['start_time'];
            $e_time = $_POST['end_time'];
            
            try {
                // Check simple time overlap logic for teacher and room (optional, but good for "สมบูรณ์")
                $overlap_stmt = $pdo->prepare("SELECT id FROM schedule WHERE (teacher_id = ? OR room_id = ? OR class_id = ?) AND day_of_week = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))");
                $overlap_stmt->execute([$teacher_id, $room_id, $class_id, $day, $e_time, $s_time, $s_time, $e_time]);
                
                if ($overlap_stmt->rowCount() > 0) {
                    $err_message = "พบการซ้อนทับของเวลาเรียนสำหรับครู, ห้องเรียน หรือชั้นเรียนในวันและเวลานี้!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO schedule (subject_id, teacher_id, class_id, room_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$subj_id, $teacher_id, $class_id, $room_id, $day, $s_time, $e_time]);
                    $message = "บันทึกตารางเรียนเรียบร้อยแล้ว";
                }
            } catch(PDOException $e) {
                $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        } elseif ($action == 'delete') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM schedule WHERE id=?");
                $stmt->execute([$id]);
                $message = "ลบตารางเรียนเรียบร้อยแล้ว";
            } catch(PDOException $e) {
                 $err_message = "ไม่สามารถลบข้อมูลได้";
            }
        }
    }
}

// Fetch master data for dropdowns
$subjects = $pdo->query("SELECT id, subject_code, subject_name FROM subjects")->fetchAll();
$teachers = $pdo->query("SELECT id, prefix, first_name, last_name FROM teachers")->fetchAll();
$classes = $pdo->query("SELECT id, class_name FROM classes")->fetchAll();
$rooms = $pdo->query("SELECT id, room_name FROM classrooms")->fetchAll();

$days = ['Monday'=>'จันทร์', 'Tuesday'=>'อังคาร', 'Wednesday'=>'พุธ', 'Thursday'=>'พฤหัสบดี', 'Friday'=>'ศุกร์', 'Saturday'=>'เสาร์', 'Sunday'=>'อาทิตย์'];

// Fetch Schedule 
$query = "
    SELECT sch.id, sch.day_of_week, sch.start_time, sch.end_time,
           subj.subject_code, subj.subject_name,
           t.prefix, t.first_name, t.last_name,
           c.class_name, r.room_name
    FROM schedule sch
    LEFT JOIN subjects subj ON sch.subject_id = subj.id
    LEFT JOIN teachers t ON sch.teacher_id = t.id
    LEFT JOIN classes c ON sch.class_id = c.id
    LEFT JOIN classrooms r ON sch.room_id = r.id
";
if ($role == 'teacher') {
    $teacher_ref_id = $_SESSION['reference_id'];
    $query .= " WHERE sch.teacher_id = " . intval($teacher_ref_id);
}
$query .= " ORDER BY FIELD(sch.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), sch.start_time ASC";

$schedules = $pdo->query($query)->fetchAll();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">จัดการตารางเรียน</h1>
        <?php if($role == 'admin'): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> เพิ่มตารางสอน
        </button>
        <?php endif; ?>
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
            <h6 class="m-0 font-weight-bold text-primary">รายการตารางสอนทั้งหมด <?php echo $role=='teacher' ? '(ของคุณ)' : ''; ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>วัน</th>
                            <th>เวลา</th>
                            <th>วิชา</th>
                            <th>ครูผู้สอน</th>
                            <th>ระดับชั้น</th>
                            <th>ห้องเรียน</th>
                            <?php if($role == 'admin'): ?><th width="10%">จัดการ</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($schedules as $sch): ?>
                        <tr>
                            <td><?php echo $days[$sch['day_of_week']] ?? $sch['day_of_week']; ?></td>
                            <td><?php echo date('H:i', strtotime($sch['start_time'])) . ' - ' . date('H:i', strtotime($sch['end_time'])); ?></td>
                            <td><?php echo htmlspecialchars($sch['subject_code'].' '.$sch['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($sch['prefix'].$sch['first_name'].' '.$sch['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($sch['class_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($sch['room_name'] ?? '-'); ?></td>
                            <?php if($role == 'admin'): ?>
                            <td>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $sch['id']; ?>"><i class="fas fa-trash"></i> ลบ</button>
                            </td>
                            <?php endif; ?>
                        </tr>

                        <?php if($role == 'admin'): ?>
                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal<?php echo $sch['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="schedule.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">ยืนยันการลบ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $sch['id']; ?>">
                                            คุณต้องการลบตารางเรียนวิชา <strong><?php echo htmlspecialchars($sch['subject_name']); ?></strong> ใช่หรือไม่?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                            <button type="submit" class="btn btn-danger">ยืนยันการลบ</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php endforeach; ?>
                        <?php if(count($schedules) == 0): ?>
                            <tr><td colspan="<?php echo $role=='admin'?7:6; ?>" class="text-center">ไม่มีข้อมูลตารางเรียน</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if($role == 'admin'): ?>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="schedule.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">จัดตารางเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>วิชา</label>
                            <select name="subject_id" class="form-control" required>
                                <option value="">-- เลือกวิชา --</option>
                                <?php foreach($subjects as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_code'].' '.$s['subject_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>ครูผู้สอน</label>
                            <select name="teacher_id" class="form-control" required>
                                <option value="">-- เลือกครูผู้สอน --</option>
                                <?php foreach($teachers as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['prefix'].$t['first_name'].' '.$t['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>ระดับชั้น</label>
                            <select name="class_id" class="form-control" required>
                                <option value="">-- เลือกระดับชั้น --</option>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>ห้องเรียน</label>
                            <select name="room_id" class="form-control" required>
                                <option value="">-- เลือกห้องเรียน --</option>
                                <?php foreach($rooms as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['room_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>วัน</label>
                            <select name="day_of_week" class="form-control" required>
                                <?php foreach($days as $en => $th): ?>
                                    <option value="<?php echo $en; ?>"><?php echo $th; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>เวลาเริ่ม</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label>เวลาสิ้นสุด</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกตารางเรียน</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

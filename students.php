<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Only admin can manage students
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
$err_message = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'add') {
            $code = trim($_POST['student_code']);
            $prefix = trim($_POST['prefix']);
            $fname = trim($_POST['first_name']);
            $lname = trim($_POST['last_name']);
            $dob = empty($_POST['dob']) ? null : $_POST['dob'];
            $class_id = empty($_POST['class_id']) ? null : $_POST['class_id'];
            
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM students WHERE student_code = ?");
                $stmt_check->execute([$code]);
                if ($stmt_check->rowCount() > 0) {
                    $err_message = "รหัสนักเรียนนี้มีอยู่ในระบบแล้ว!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO students (student_code, prefix, first_name, last_name, dob, class_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $prefix, $fname, $lname, $dob, $class_id]);
                    $new_student_id = $pdo->lastInsertId();
                    
                    // Insert User for Student login
                    $s_username = $code;
                    $s_pass = password_hash($code, PASSWORD_DEFAULT); // Default password
                    $stmt_user = $pdo->prepare("INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, 'student', ?)");
                    $stmt_user->execute([$s_username, $s_pass, $new_student_id]);

                    $message = "เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว";
                }
            } catch(PDOException $e) {
                $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $code = trim($_POST['student_code']);
            $prefix = trim($_POST['prefix']);
            $fname = trim($_POST['first_name']);
            $lname = trim($_POST['last_name']);
            $dob = empty($_POST['dob']) ? null : $_POST['dob'];
            $class_id = empty($_POST['class_id']) ? null : $_POST['class_id'];

            try {
                $stmt_check = $pdo->prepare("SELECT id FROM students WHERE student_code = ? AND id != ?");
                $stmt_check->execute([$code, $id]);
                if ($stmt_check->rowCount() > 0) {
                    $err_message = "รหัสนักเรียนนี้มีอยู่ในระบบแล้ว!";
                } else {
                    $stmt = $pdo->prepare("UPDATE students SET student_code=?, prefix=?, first_name=?, last_name=?, dob=?, class_id=? WHERE id=?");
                    $stmt->execute([$code, $prefix, $fname, $lname, $dob, $class_id, $id]);
                    
                    $stmt_user = $pdo->prepare("UPDATE users SET username=? WHERE reference_id=? AND role='student'");
                    $stmt_user->execute([$code, $id]);

                    $message = "แก้ไขข้อมูลเรียบร้อยแล้ว";
                }
            } catch(PDOException $e) {
                $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        } elseif ($action == 'delete') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM students WHERE id=?");
                $stmt->execute([$id]);
                
                $stmt_u = $pdo->prepare("DELETE FROM users WHERE reference_id=? AND role='student'");
                $stmt_u->execute([$id]);

                $message = "ลบข้อมูลเรียบร้อยแล้ว";
            } catch(PDOException $e) {
                 $err_message = "ไม่สามารถลบข้อมูลได้ เนื่องจากมีการอ้างอิงข้อมูลนี้อยู่";
            }
        }
    }
}

// Fetch classes enum
$classes_stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name DESC");
$classes = $classes_stmt->fetchAll();

// Fetch all students
$students_stmt = $pdo->query("
    SELECT s.*, c.class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY c.class_name DESC, s.student_code ASC
");
$students = $students_stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">จัดการข้อมูลนักเรียน</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> เพิ่มข้อมูลนักเรียน
        </button>
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
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>รหัสประจำตัว</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>วันเกิด</th>
                            <th>ระดับชั้น</th>
                            <th width="15%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['student_code']); ?></td>
                            <td><?php echo htmlspecialchars($s['prefix'].$s['first_name'].' '.$s['last_name']); ?></td>
                            <td><?php echo $s['dob'] ? date('d/m/Y', strtotime($s['dob'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($s['class_name'] ?? '-'); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $s['id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $s['id']; ?>"><i class="fas fa-trash"></i> Del</button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $s['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="students.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">แก้ไขข้อมูลนักเรียน</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label>รหัสประจำตัว (Username/Password)</label>
                                                <input type="text" name="student_code" class="form-control" value="<?php echo htmlspecialchars($s['student_code']); ?>" required>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label>คำนำหน้า</label>
                                                    <select name="prefix" class="form-control" required>
                                                        <option value="ด.ช." <?php echo $s['prefix']=='ด.ช.'?'selected':''; ?>>ด.ช.</option>
                                                        <option value="ด.ญ." <?php echo $s['prefix']=='ด.ญ.'?'selected':''; ?>>ด.ญ.</option>
                                                        <option value="นาย" <?php echo $s['prefix']=='นาย'?'selected':''; ?>>นาย</option>
                                                        <option value="นางสาว" <?php echo $s['prefix']=='นางสาว'?'selected':''; ?>>นางสาว</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>ชื่อ</label>
                                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($s['first_name']); ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>นามสกุล</label>
                                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($s['last_name']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label>วันเกิด</label>
                                                    <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($s['dob']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>ระดับชั้น</label>
                                                    <select name="class_id" class="form-control">
                                                        <option value="">- เลือกระดับชั้น -</option>
                                                        <?php foreach($classes as $c): ?>
                                                            <option value="<?php echo $c['id']; ?>" <?php echo $s['class_id']==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['class_name']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                            <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal<?php echo $s['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="students.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">ยืนยันการลบ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                            คุณต้องการลบข้อมูลนักเรียน <strong><?php echo htmlspecialchars($s['prefix'].$s['first_name'].' '.$s['last_name']); ?></strong> ใช่หรือไม่?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                            <button type="submit" class="btn btn-danger">ยืนยันการลบ</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(count($students) == 0): ?>
                            <tr><td colspan="5" class="text-center">ไม่มีข้อมูล</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="students.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มข้อมูลนักเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label>รหัสประจำตัว (Username/Password)</label>
                        <input type="text" name="student_code" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>คำนำหน้า</label>
                            <select name="prefix" class="form-control" required>
                                <option value="ด.ช.">ด.ช.</option>
                                <option value="ด.ญ.">ด.ญ.</option>
                                <option value="นาย">นาย</option>
                                <option value="นางสาว">นางสาว</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>ชื่อ</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label>นามสกุล</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>วันเกิด</label>
                            <input type="date" name="dob" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>ระดับชั้น</label>
                            <select name="class_id" class="form-control">
                                <option value="">- เลือกระดับชั้น -</option>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

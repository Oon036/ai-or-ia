<?php
require_once 'includes/db.php';
// Header includes session check
include 'includes/header.php';

// Only admin can manage teachers
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
$err_message = '';

// Helper function for secure upload
function uploadProfileImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes) || !in_array($ext, $allowed_exts) || $file['size'] > $max_size) {
        return false; // Error validation
    }

    $new_filename = uniqid('prof_') . '.' . $ext;
    $dest = 'images/profiles/' . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $new_filename;
    }
    return false;
}

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'add') {
            $code = trim($_POST['teacher_code']);
            $prefix = trim($_POST['prefix']);
            $fname = trim($_POST['first_name']);
            $lname = trim($_POST['last_name']);
            $phone = trim($_POST['phone']);
            $subj_group = trim($_POST['subject_group']);
            $line_id = trim($_POST['line_id']);
            
            $profile_img = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $profile_img = uploadProfileImage($_FILES['profile_image']);
                if ($profile_img === false) {
                    $err_message = "ไฟล์รูปภาพไม่ถูกต้อง หรือขนาดเกิน 2MB (รองรับ JPG, PNG, GIF เท่านั้น)";
                }
            }

            if (empty($err_message)) {
                try {
                    // Check if code exists
                    $stmt_check = $pdo->prepare("SELECT id FROM teachers WHERE teacher_code = ?");
                    $stmt_check->execute([$code]);
                    if ($stmt_check->rowCount() > 0) {
                        $err_message = "รหัสครูนี้มีอยู่ในระบบแล้ว!";
                    } else {
                        // Insert Teacher
                        $stmt = $pdo->prepare("INSERT INTO teachers (teacher_code, prefix, first_name, last_name, phone, subject_group, line_id, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$code, $prefix, $fname, $lname, $phone, $subj_group, $line_id, $profile_img]);
                        $new_teacher_id = $pdo->lastInsertId();
                        
                        // Insert User for Teacher login
                        $t_username = $code;
                        $t_pass = password_hash($code, PASSWORD_DEFAULT); // Default password is the teacher code
                        $stmt_user = $pdo->prepare("INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, 'teacher', ?)");
                        $stmt_user->execute([$t_username, $t_pass, $new_teacher_id]);

                        $message = "เพิ่มข้อมูลครูผู้สอนเรียบร้อยแล้ว";
                    }
                } catch(PDOException $e) {
                    $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
                }
            }
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $code = trim($_POST['teacher_code']);
            $prefix = trim($_POST['prefix']);
            $fname = trim($_POST['first_name']);
            $lname = trim($_POST['last_name']);
            $phone = trim($_POST['phone']);
            $subj_group = trim($_POST['subject_group']);
            $line_id = trim($_POST['line_id']);
            $old_image = $_POST['old_image'];

            $profile_img = $old_image;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded = uploadProfileImage($_FILES['profile_image']);
                if ($uploaded === false) {
                    $err_message = "ไฟล์รูปภาพไม่ถูกต้อง หรือขนาดเกิน 2MB";
                } else {
                    $profile_img = $uploaded;
                    // Delete old image if not empty
                    if (!empty($old_image) && file_exists('images/profiles/'.$old_image)) {
                        unlink('images/profiles/'.$old_image);
                    }
                }
            }

            if (empty($err_message)) {
                try {
                    // Check duplicate code for other teachers
                    $stmt_check = $pdo->prepare("SELECT id FROM teachers WHERE teacher_code = ? AND id != ?");
                    $stmt_check->execute([$code, $id]);
                    if ($stmt_check->rowCount() > 0) {
                        $err_message = "รหัสครูนี้มีอยู่ในระบบแล้ว!";
                    } else {
                        $stmt = $pdo->prepare("UPDATE teachers SET teacher_code=?, prefix=?, first_name=?, last_name=?, phone=?, subject_group=?, line_id=?, profile_image=? WHERE id=?");
                        $stmt->execute([$code, $prefix, $fname, $lname, $phone, $subj_group, $line_id, $profile_img, $id]);
                        
                        // Update User login if teacher code changed
                        $stmt_user = $pdo->prepare("UPDATE users SET username=? WHERE reference_id=? AND role='teacher'");
                        $stmt_user->execute([$code, $id]);

                        $message = "แก้ไขข้อมูลเรียบร้อยแล้ว";
                    }
                } catch(PDOException $e) {
                    $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
                }
            }
        } elseif ($action == 'delete') {
            $id = $_POST['id'];
            try {
                // Get old image
                $stmt_img = $pdo->prepare("SELECT profile_image FROM teachers WHERE id=?");
                $stmt_img->execute([$id]);
                $img = $stmt_img->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM teachers WHERE id=?");
                $stmt->execute([$id]);
                
                $stmt_u = $pdo->prepare("DELETE FROM users WHERE reference_id=? AND role='teacher'");
                $stmt_u->execute([$id]);

                if (!empty($img) && file_exists('images/profiles/'.$img)) {
                    unlink('images/profiles/'.$img);
                }
                $message = "ลบข้อมูลเรียบร้อยแล้ว";
            } catch(PDOException $e) {
                 $err_message = "ไม่สามารถลบข้อมูลได้ เนื่องจากมีการอ้างอิงข้อมูลนี้อยู่";
            }
        }
    }
}

// Fetch all teachers
$teachers_stmt = $pdo->query("SELECT * FROM teachers ORDER BY id DESC");
$teachers = $teachers_stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">จัดการข้อมูลครูผู้สอน</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> เพิ่มข้อมูลครู
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

    <!-- Teachers Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>รูปโปรไฟล์</th>
                            <th>รหัสประจำตัว</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>หมวดวิชา</th>
                            <th>เบอร์โทร</th>
                            <th>Line ID</th>
                            <th width="15%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($teachers as $t): ?>
                        <tr>
                            <td class="text-center">
                                <?php if($t['profile_image']): ?>
                                    <img src="images/profiles/<?php echo htmlspecialchars($t['profile_image']); ?>" alt="Profile" class="rounded-circle" width="50" height="50" style="object-fit:cover;">
                                <?php else: ?>
                                    <img src="images/favicon.png" alt="Profile" class="rounded-circle" width="50" height="50" style="background:#ddd; padding:5px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($t['teacher_code']); ?></td>
                            <td><?php echo htmlspecialchars($t['prefix'].$t['first_name'].' '.$t['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['subject_group']); ?></td>
                            <td><?php echo htmlspecialchars($t['phone']); ?></td>
                            <td><?php echo htmlspecialchars($t['line_id']); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $t['id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $t['id']; ?>"><i class="fas fa-trash"></i> Del</button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $t['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="teachers.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">แก้ไขข้อมูลครู</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($t['profile_image']); ?>">
                                            
                                            <div class="mb-3">
                                                <label>รหัสประจำตัว (ใช้เป็น Username / Password เริ่มต้น)</label>
                                                <input type="text" name="teacher_code" class="form-control" value="<?php echo htmlspecialchars($t['teacher_code']); ?>" required>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label>คำนำหน้า</label>
                                                    <select name="prefix" class="form-control" required>
                                                        <option value="นาย" <?php echo $t['prefix']=='นาย'?'selected':''; ?>>นาย</option>
                                                        <option value="นาง" <?php echo $t['prefix']=='นาง'?'selected':''; ?>>นาง</option>
                                                        <option value="นางสาว" <?php echo $t['prefix']=='นางสาว'?'selected':''; ?>>นางสาว</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>ชื่อ</label>
                                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($t['first_name']); ?>" required>
                                                </div>
                                                <div class="col-md-5">
                                                    <label>นามสกุล</label>
                                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($t['last_name']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label>หมวดวิชา</label>
                                                <input type="text" name="subject_group" class="form-control" value="<?php echo htmlspecialchars($t['subject_group']); ?>">
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label>เบอร์โทรศัพท์</label>
                                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($t['phone']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Line ID</label>
                                                    <input type="text" name="line_id" class="form-control" value="<?php echo htmlspecialchars($t['line_id']); ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label>รูปโปรไฟล์ (กรณีต้องการเปลี่ยน)</label>
                                                <input type="file" name="profile_image" class="form-control" accept="image/png, image/jpeg, image/gif">
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
                        <div class="modal fade" id="deleteModal<?php echo $t['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="teachers.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">ยืนยันการลบ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                            คุณต้องการลบข้อมูลครู <strong><?php echo htmlspecialchars($t['prefix'].$t['first_name'].' '.$t['last_name']); ?></strong> ใช่หรือไม่?
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
                        <?php if(count($teachers) == 0): ?>
                            <tr><td colspan="7" class="text-center">ไม่มีข้อมูล</td></tr>
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
        <form action="teachers.php" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มข้อมูลครู</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label>รหัสประจำตัว (ใช้เป็น Username / Password เริ่มต้น)</label>
                        <input type="text" name="teacher_code" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>คำนำหน้า</label>
                            <select name="prefix" class="form-control" required>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>ชื่อ</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label>นามสกุล</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>หมวดวิชา</label>
                        <input type="text" name="subject_group" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Line ID</label>
                            <input type="text" name="line_id" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>รูปโปรไฟล์ (ไม่บังคับ)</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/png, image/jpeg, image/gif">
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

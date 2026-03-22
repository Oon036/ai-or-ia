<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Only admin can manage subjects
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
            $code = trim($_POST['subject_code']);
            $name = trim($_POST['subject_name']);
            $credits = floatval($_POST['credits']);
            
            try {
                $stmt_check = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
                $stmt_check->execute([$code]);
                if ($stmt_check->rowCount() > 0) {
                    $err_message = "รหัสวิชานี้มีอยู่ในระบบแล้ว!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, credits) VALUES (?, ?, ?)");
                    $stmt->execute([$code, $name, $credits]);
                    $message = "เพิ่มข้อมูลรายวิชาเรียบร้อยแล้ว";
                }
            } catch(PDOException $e) {
                $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $code = trim($_POST['subject_code']);
            $name = trim($_POST['subject_name']);
            $credits = floatval($_POST['credits']);

            try {
                $stmt_check = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
                $stmt_check->execute([$code, $id]);
                if ($stmt_check->rowCount() > 0) {
                    $err_message = "รหัสวิชานี้มีอยู่ในระบบแล้ว!";
                } else {
                    $stmt = $pdo->prepare("UPDATE subjects SET subject_code=?, subject_name=?, credits=? WHERE id=?");
                    $stmt->execute([$code, $name, $credits, $id]);
                    $message = "แก้ไขข้อมูลเรียบร้อยแล้ว";
                }
            } catch(PDOException $e) {
                $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        } elseif ($action == 'delete') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM subjects WHERE id=?");
                $stmt->execute([$id]);
                $message = "ลบข้อมูลเรียบร้อยแล้ว";
            } catch(PDOException $e) {
                 $err_message = "ไม่สามารถลบข้อมูลได้ เนื่องจากมีการอ้างอิงข้อมูลนี้อยู่ในตารางเรียนหรือผลการเรียน";
            }
        }
    }
}

// Fetch all subjects
$subjects_stmt = $pdo->query("SELECT * FROM subjects ORDER BY id DESC");
$subjects = $subjects_stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">จัดการข้อมูลรายวิชา</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> เพิ่มข้อมูลรายวิชา
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
                            <th width="15%">รหัสวิชา</th>
                            <th>ชื่อรายวิชา</th>
                            <th width="15%">หน่วยกิต</th>
                            <th width="20%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subjects as $sub): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                            <td><?php echo number_format($sub['credits'], 1); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $sub['id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $sub['id']; ?>"><i class="fas fa-trash"></i> Del</button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $sub['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="subjects.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">แก้ไขข้อมูลรายวิชา</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label>รหัสวิชา</label>
                                                <input type="text" name="subject_code" class="form-control" value="<?php echo htmlspecialchars($sub['subject_code']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>ชื่อรายวิชา</label>
                                                <input type="text" name="subject_name" class="form-control" value="<?php echo htmlspecialchars($sub['subject_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>จำนวนหน่วยกิต</label>
                                                <input type="number" step="0.5" name="credits" class="form-control" value="<?php echo htmlspecialchars($sub['credits']); ?>" required>
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
                        <div class="modal fade" id="deleteModal<?php echo $sub['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="subjects.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">ยืนยันการลบ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                            คุณต้องการลบรายวิชา <strong><?php echo htmlspecialchars($sub['subject_code'].' '.$sub['subject_name']); ?></strong> ใช่หรือไม่?
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
                        <?php if(count($subjects) == 0): ?>
                            <tr><td colspan="4" class="text-center">ไม่มีข้อมูล</td></tr>
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
        <form action="subjects.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มข้อมูลรายวิชา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label>รหัสวิชา</label>
                        <input type="text" name="subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>ชื่อรายวิชา</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>จำนวนหน่วยกิต</label>
                        <input type="number" step="0.5" name="credits" class="form-control" required>
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

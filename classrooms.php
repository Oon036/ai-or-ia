<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Only admin can manage classrooms
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='container-fluid'><div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></div>";
    include 'includes/footer.php';
    exit();
}

$message = '';
$err_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'add') {
            $code = trim($_POST['room_code']);
            $name = trim($_POST['room_name']);

            try {
                $stmt_check = $pdo->prepare("SELECT id FROM classrooms WHERE room_code = ?");
                $stmt_check->execute([$code]);
                if ($stmt_check->rowCount() > 0) {
                    $err_message = "รหัสห้องเรียนนี้มีอยู่ในระบบแล้ว!";
                }
                else {
                    $stmt = $pdo->prepare("INSERT INTO classrooms (room_code, room_name) VALUES (?, ?)");
                    $stmt->execute([$code, $name]);
                    $message = "เพิ่มข้อมูลห้องเรียนเรียบร้อยแล้ว";
                }
            }
            catch (PDOException $e) {
                $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        }
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $code = trim($_POST['room_code']);
            $name = trim($_POST['room_name']);

            try {
                $stmt_check = $pdo->prepare("SELECT id FROM classrooms WHERE room_code = ? AND id != ?");
                $stmt_check->execute([$code, $id]);
                if ($stmt_check->rowCount() > 0) {
                    $err_message = "รหัสห้องเรียนนี้มีอยู่ในระบบแล้ว!";
                }
                else {
                    $stmt = $pdo->prepare("UPDATE classrooms SET room_code=?, room_name=? WHERE id=?");
                    $stmt->execute([$code, $name, $id]);
                    $message = "แก้ไขข้อมูลเรียบร้อยแล้ว";
                }
            }
            catch (PDOException $e) {
                $err_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        }
        elseif ($action == 'delete') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM classrooms WHERE id=?");
                $stmt->execute([$id]);
                $message = "ลบข้อมูลเรียบร้อยแล้ว";
            }
            catch (PDOException $e) {
                $err_message = "ไม่สามารถลบข้อมูลได้ เนื่องจากมีการอ้างอิงข้อมูลนี้อยู่ในตารางเรียน";
            }
        }
    }
}

$rooms_stmt = $pdo->query("SELECT * FROM classrooms ORDER BY room_code ASC");
$rooms = $rooms_stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">จัดการข้อมูลห้องเรียน</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> เพิ่มห้องเรียน
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
endif; ?>
    <?php if ($err_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $err_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th width="20%">รหัสห้องเรียน</th>
                            <th>ชื่อ/หมายเลขห้องส้วม</th>
                            <th width="20%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['room_code']); ?></td>
                            <td><?php echo htmlspecialchars($r['room_name']); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $r['id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $r['id']; ?>"><i class="fas fa-trash"></i> Del</button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="classrooms.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">แก้ไขข้อมูลห้องเรียน</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label>รหัสห้องเรียน</label>
                                                <input type="text" name="room_code" class="form-control" value="<?php echo htmlspecialchars($r['room_code']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>ชื่อ/หมายเลขห้อง (เช่น วิทยาศาสตร์ 1, ห้อง 401)</label>
                                                <input type="text" name="room_name" class="form-control" value="<?php echo htmlspecialchars($r['room_name']); ?>" required>
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
                        <div class="modal fade" id="deleteModal<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="classrooms.php" method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">ยืนยันการลบ</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            คุณต้องการลบห้องเรียน <strong><?php echo htmlspecialchars($r['room_name']); ?></strong> ใช่หรือไม่?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                            <button type="submit" class="btn btn-danger">ยืนยันการลบ</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php
endforeach; ?>
                        <?php if (count($rooms) == 0): ?>
                            <tr><td colspan="3" class="text-center">ไม่มีข้อมูล</td></tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="classrooms.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มข้อมูลห้องเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label>รหัสห้องเรียน</label>
                        <input type="text" name="room_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>ชื่อ/หมายเลขห้อง (เช่น วิทยาศาสตร์ 1, ห้อง 401)</label>
                        <input type="text" name="room_name" class="form-control" required>
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

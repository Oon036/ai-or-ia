<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'teacher'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $schedule_id = $_POST['schedule_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($student_id && $schedule_id && $date && $status) {
        try {
            // Check if record exists
            $stmt_check = $pdo->prepare("SELECT id FROM attendance WHERE schedule_id = ? AND student_id = ? AND date = ?");
            $stmt_check->execute([$schedule_id, $student_id, $date]);
            
            if ($stmt_check->rowCount() > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE schedule_id = ? AND student_id = ? AND date = ?");
                $stmt->execute([$status, $schedule_id, $student_id, $date]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO attendance (schedule_id, student_id, date, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$schedule_id, $student_id, $date, $status]);
            }
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    }
}
?>

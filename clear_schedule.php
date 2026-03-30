<?php
require 'includes/db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE attendance;");
    $pdo->exec("TRUNCATE TABLE schedule;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "ลบข้อมูลตารางเรียน (และข้อมูลการเข้าเรียนที่เกี่ยวข้อง) ทั้งหมดเรียบร้อยแล้ว";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

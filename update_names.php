<?php
require 'includes/db.php';

try {
    $students = $pdo->query("SELECT id FROM students")->fetchAll(PDO::FETCH_ASSOC);
    
    $names = [
        'สมปอง', 'ใจดี', 'สมหวัง', 'เรียนดี', 'มานะ', 'ฟ้าใหม่', 'อารี', 'รุ่งอรุณ', 'สุดา', 'ใจเย็น', 
        'วิชัย', 'สามารถ', 'ณภัทร', 'ธนพล', 'ก้องภพ', 'กวิน', 'ปภาดา', 'ภิญญาพัชญ์', 'นันท์นภัส', 'กฤติน',
        'ชลกนก', 'ปราชญ์', 'ภูมิภัทร', 'วรรธนะ', 'พิชญุตม์', 'อลิสา', 'พิมพ์ชนก', 'กัญญาพัชร', 'ชนิตา'
    ];
    $lastnames = [
        'ดีเลิศ', 'จริงใจ', 'ขยันยิ่ง', 'มุ่งมั่น', 'พากเพียร', 'สว่างไสว', 'ใจงาม', 'มีสุข', 'รุ่งเรือง',
        'วิจิตรกร', 'ทรัพย์เจริญ', 'พิทักษ์ธรรม', 'อัครประเสริฐ', 'วัฒนพาณิชย์', 'ก้องเกียรติ', 'สกุลไทย',
        'อุดมโชค', 'เลิศวัฒนา', 'จงใจหาญ', 'นพรัตน์', 'ธีระกุล', 'สิริโสภณ', 'แสงทอง', 'สว่างวงศ์'
    ];
    
    // Generate unique combinations
    $used_combinations = [];
    
    foreach ($students as $s) {
        $id = $s['id'];
        
        do {
            $fname = $names[array_rand($names)];
            $lname = $lastnames[array_rand($lastnames)];
            $combo = $fname . '|' . $lname;
        } while (isset($used_combinations[$combo]));
        
        $used_combinations[$combo] = true;
        
        $pdo->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE id = ?")->execute([$fname, $lname, $id]);
    }
    
    echo "Student names updated to be unique.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

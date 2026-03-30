<?php
require 'includes/db.php';

try {
    echo "Seeding More Master Data...\n";

    // 1. Teachers: Add for remaining subjects (Thai, Social Studies)
    // Existing: MATH, SCI, ENG. Missing: THAI, SOC
    $teachers = [
        ['T005', 'นาย', 'สมภพ', 'รักชาติ', '0811112222', 'ภาษาไทย', 'sompop_thai', ''],
        ['T006', 'นาง', 'สมหญิง', 'ใจบุญ', '0822223333', 'สังคมศึกษา', 'somying_soc', '']
    ];
    foreach ($teachers as $t) {
        if ($pdo->query("SELECT id FROM teachers WHERE teacher_code='{$t[0]}'")->rowCount() == 0) {
            $pdo->prepare("INSERT INTO teachers (teacher_code, prefix, first_name, last_name, phone, subject_group, line_id, profile_image) VALUES (?,?,?,?,?,?,?,?)")->execute($t);
            $pw = password_hash($t[0], PASSWORD_DEFAULT);
            $tid = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, 'teacher', ?)")->execute([$t[0], $pw, $tid]);
        }
    }
    echo "Additional Teachers seeded.\n";

    // 2. Classrooms: Add rooms based on subjects
    $rooms = [
        ['THAI1', 'ห้องภาษาไทย 1'], 
        ['SOC1', 'ห้องสังคมศึกษา 1'], 
        ['ENG1', 'ห้องภาษาอังกฤษ 1'],
        ['SCI2', 'ห้องวิทยาศาสตร์ 2']
    ];
    foreach($rooms as $r) {
        if ($pdo->query("SELECT id FROM classrooms WHERE room_code='{$r[0]}'")->rowCount() == 0) {
            $pdo->prepare("INSERT INTO classrooms (room_code, room_name) VALUES (?,?)")->execute($r);
        }
    }
    echo "Additional Classrooms seeded.\n";

    // 3. Students: 4 students per class. Existing classes: M1/1, M1/2, M2/1, M3/1
    $classes = $pdo->query("SELECT id, class_code FROM classes")->fetchAll(PDO::FETCH_ASSOC);
    $student_index = 1006; // Starting from 1006 since M1/1 has 1001-1005
    
    foreach ($classes as $c) {
        $class_id = $c['id'];
        $class_code = $c['class_code'];
        
        // Count existing students in this class
        $existing_count = $pdo->query("SELECT COUNT(id) FROM students WHERE class_id=$class_id")->fetchColumn();
        
        // Add students until there are 4 in the class (skip if already >= 4)
        $names = ['สมปอง', 'ใจดี', 'สมหวัง', 'เรียนดี', 'มานะ', 'ฟ้าใหม่', 'อารี', 'รุ่งอรุณ', 'สุดา', 'ใจเย็น', 'วิชัย', 'สามารถ'];
        $lastnames = ['ดีเลิศ', 'จริงใจ', 'ขยันยิ่ง', 'มุ่งมั่น', 'พากเพียร', 'สว่างไสว', 'ใจงาม', 'มีสุข', 'รุ่งเรือง'];
        $prefixes = ['ด.ช.', 'ด.ญ.'];

        while ($existing_count < 4) {
            $code = 'S' . $student_index;
            $prefix = $prefixes[array_rand($prefixes)];
            $fname = $names[array_rand($names)];
            $lname = $lastnames[array_rand($lastnames)];
            $dob = '2010-'.sprintf("%02d", rand(1, 12)).'-'.sprintf("%02d", rand(1, 28));

            if ($pdo->query("SELECT id FROM students WHERE student_code='{$code}'")->rowCount() == 0) {
                $pdo->prepare("INSERT INTO students (student_code, prefix, first_name, last_name, dob, class_id) VALUES (?,?,?,?,?,?)")
                    ->execute([$code, $prefix, $fname, $lname, $dob, $class_id]);
                $pw = password_hash($code, PASSWORD_DEFAULT);
                $sid = $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, 'student', ?)")->execute([$code, $pw, $sid]);
                $existing_count++;
            }
            $student_index++;
        }
    }
    echo "Additional Students seeded (4 per class).\n";

    echo "Additional Database sample seeded successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

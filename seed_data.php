<?php
require 'includes/db.php';

try {
    echo "Seeding Master Data...\n";

    // Teachers
    $teachers = [
        ['T002', 'นาย', 'สมชาย', 'ใจดี', '0812345678', 'คณิตศาสตร์', 'somchai_line', ''],
        ['T003', 'นาง', 'สมศรี', 'เรียนเก่ง', '0898765432', 'วิทยาศาสตร์', 'somsri_sci', ''],
        ['T004', 'นางสาว', 'มาลี', 'สวยงาม', '0845556666', 'ภาษาอังกฤษ', 'malee_eng', '']
    ];
    foreach ($teachers as $t) {
        if ($pdo->query("SELECT id FROM teachers WHERE teacher_code='{$t[0]}'")->rowCount() == 0) {
            $pdo->prepare("INSERT INTO teachers (teacher_code, prefix, first_name, last_name, phone, subject_group, line_id, profile_image) VALUES (?,?,?,?,?,?,?,?)")->execute($t);
            $pw = password_hash($t[0], PASSWORD_DEFAULT);
            $tid = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, 'teacher', ?)")->execute([$t[0], $pw, $tid]);
        }
    }
    echo "Teachers seeded.\n";

    // Subjects
    $subjects = [
        ['MATH101', 'คณิตศาสตร์พื้นฐาน', 1.5],
        ['SCI101', 'วิทยาศาสตร์พื้นฐาน', 1.5],
        ['ENG101', 'ภาษาอังกฤษเพื่อการสื่อสาร', 1.0],
        ['THAI101', 'ภาษาไทย', 1.0],
        ['SOC101', 'สังคมศึกษา', 1.0]
    ];
    foreach($subjects as $s) {
        if ($pdo->query("SELECT id FROM subjects WHERE subject_code='{$s[0]}'")->rowCount() == 0) {
            $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, credits) VALUES (?,?,?)")->execute($s);
        }
    }
    echo "Subjects seeded.\n";

    // Classes
    $classes = [
        ['M1/1', 'ม.1/1'], ['M1/2', 'ม.1/2'], ['M2/1', 'ม.2/1'], ['M3/1', 'ม.3/1']
    ];
    foreach($classes as $c) {
        if ($pdo->query("SELECT id FROM classes WHERE class_code='{$c[0]}'")->rowCount() == 0) {
            $pdo->prepare("INSERT INTO classes (class_code, class_name) VALUES (?,?)")->execute($c);
        }
    }
    echo "Classes seeded.\n";

    // Classrooms
    $rooms = [
        ['R101', 'ห้องเรียน 101'], ['R102', 'ห้องเรียน 102'], ['SLAB1', 'ห้องวิทยาศาตร์ 1'], ['COMP1', 'ห้องคอมพิวเตอร์ 1']
    ];
    foreach($rooms as $r) {
        if ($pdo->query("SELECT id FROM classrooms WHERE room_code='{$r[0]}'")->rowCount() == 0) {
            $pdo->prepare("INSERT INTO classrooms (room_code, room_name) VALUES (?,?)")->execute($r);
        }
    }
    echo "Classrooms seeded.\n";

    // Students
    $class_id_m1 = $pdo->query("SELECT id FROM classes WHERE class_code='M1/1'")->fetchColumn();
    if ($class_id_m1) {
        $students = [
            ['S1001', 'ด.ช.', 'ก้องหล้า', 'ฟ้าสดใส', '2010-05-15', $class_id_m1],
            ['S1002', 'ด.ช.', 'ตะวัน', 'ฉายแสง', '2010-08-22', $class_id_m1],
            ['S1003', 'ด.ญ.', 'จันทร์เพ็ญ', 'เด่นดวง', '2010-11-05', $class_id_m1],
            ['S1004', 'ด.ญ.', 'ดารา', 'พรายแสง', '2010-02-14', $class_id_m1],
            ['S1005', 'ด.ช.', 'วินัย', 'รักเรียน', '2010-09-30', $class_id_m1]
        ];
        foreach($students as $st) {
            if ($pdo->query("SELECT id FROM students WHERE student_code='{$st[0]}'")->rowCount() == 0) {
                $pdo->prepare("INSERT INTO students (student_code, prefix, first_name, last_name, dob, class_id) VALUES (?,?,?,?,?,?)")->execute($st);
                $pw = password_hash($st[0], PASSWORD_DEFAULT);
                $sid = $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, 'student', ?)")->execute([$st[0], $pw, $sid]);
            }
        }
        echo "Students seeded.\n";

        // Schedule
        $t_math = $pdo->query("SELECT id FROM teachers WHERE teacher_code='T002'")->fetchColumn();
        $s_math = $pdo->query("SELECT id FROM subjects WHERE subject_code='MATH101'")->fetchColumn();
        $r_101  = $pdo->query("SELECT id FROM classrooms WHERE room_code='R101'")->fetchColumn();

        if ($t_math && $s_math && $r_101) {
            if ($pdo->query("SELECT id FROM schedule WHERE subject_id=$s_math AND class_id=$class_id_m1")->rowCount() == 0) {
                $pdo->prepare("INSERT INTO schedule (subject_id, teacher_id, class_id, room_id, day_of_week, start_time, end_time) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$s_math, $t_math, $class_id_m1, $r_101, 'Monday', '08:30:00', '10:00:00']);
            }
            echo "Schedule seeded.\n";

            // Attendance (Mock)
            $schedule_id = $pdo->query("SELECT id FROM schedule WHERE subject_id=$s_math AND class_id=$class_id_m1")->fetchColumn();
            $all_m1_students = $pdo->query("SELECT id FROM students WHERE class_id=$class_id_m1")->fetchAll(PDO::FETCH_COLUMN);

            if ($schedule_id && count($all_m1_students) > 0) {
                $date = date('Y-m-d');
                $statuses = ['Present', 'Present', 'Absent', 'Late', 'Present'];
                foreach($all_m1_students as $i => $sid) {
                    $status = $statuses[$i % count($statuses)];
                    if ($pdo->query("SELECT id FROM attendance WHERE schedule_id=$schedule_id AND student_id=$sid AND date='$date'")->rowCount() == 0) {
                        $pdo->prepare("INSERT INTO attendance (schedule_id, student_id, date, status) VALUES (?,?,?,?)")->execute([$schedule_id, $sid, $date, $status]);
                    }
                }
                echo "Attendance seeded.\n";
            }

            // Grades (Mock)
            if ($s_math && count($all_m1_students) > 0) {
                $scores = [85, 76, 45, 62, 91];
                foreach($all_m1_students as $i => $sid) {
                    $score = $scores[$i % count($scores)];
                    if ($score >= 80) $grade = 4.0;
                    elseif ($score >= 70) $grade = 3.0;
                    elseif ($score >= 60) $grade = 2.0;
                    elseif ($score >= 50) $grade = 1.0;
                    else $grade = 0.0;
                    
                    if ($pdo->query("SELECT id FROM grades WHERE subject_id=$s_math AND student_id=$sid")->rowCount() == 0) {
                        $pdo->prepare("INSERT INTO grades (subject_id, student_id, raw_score, grade, teacher_id) VALUES (?,?,?,?,?)")->execute([$s_math, $sid, $score, $grade, $t_math]);
                    }
                }
                echo "Grades seeded.\n";
            }
        }
    }

    echo "Database sample seeded successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

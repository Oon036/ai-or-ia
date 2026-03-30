<?php
require 'includes/db.php';

try {
    $today = date('Y-m-d');
    $day_of_week = date('l'); // e.g. 'Monday'

    $classes = $pdo->query("SELECT id FROM classes LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    $teachers = $pdo->query("SELECT id FROM teachers LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    $subjects = $pdo->query("SELECT id FROM subjects LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    $rooms = $pdo->query("SELECT id FROM classrooms LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($classes) || empty($teachers) || empty($subjects) || empty($rooms)) {
        die("Missing basic master data.");
    }

    $schedule_ids = [];

    // Check if there's any schedule for today
    $existing = $pdo->prepare("SELECT id FROM schedule WHERE day_of_week = ?");
    $existing->execute([$day_of_week]);
    $schedule_ids = $existing->fetchAll(PDO::FETCH_COLUMN);

    // If no schedule for today, create some
    if (empty($schedule_ids)) {
        for ($i = 0; $i < 2; $i++) {
            $pdo->prepare("INSERT INTO schedule (subject_id, teacher_id, class_id, room_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$subjects[$i], $teachers[$i], $classes[$i], $rooms[$i], $day_of_week, '08:30:00', '09:30:00']);
            $schedule_ids[] = $pdo->lastInsertId();
        }
    }

    // Now seed attendance for today
    $statuses = ['Present', 'Present', 'Present', 'Absent', 'Late', 'Leave']; // biased towards Present
    
    foreach ($schedule_ids as $sch_id) {
        $class_id = $pdo->query("SELECT class_id FROM schedule WHERE id = $sch_id")->fetchColumn();
        if ($class_id) {
            $students = $pdo->query("SELECT id FROM students WHERE class_id = $class_id")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($students as $stu_id) {
                // check if already marked
                $check = $pdo->query("SELECT id FROM attendance WHERE schedule_id = $sch_id AND date = '$today' AND student_id = $stu_id")->rowCount();
                if ($check == 0) {
                    $status = $statuses[array_rand($statuses)];
                    $pdo->prepare("INSERT INTO attendance (schedule_id, student_id, date, status) VALUES (?, ?, ?, ?)")
                        ->execute([$sch_id, $stu_id, $today, $status]);
                }
            }
        }
    }

    echo "Attendance seeded for today ($today)! Charts should now render in dashboard.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

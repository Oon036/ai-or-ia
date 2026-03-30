<?php
require 'includes/db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE schedule;");
    $pdo->exec("TRUNCATE TABLE attendance;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $classes = $pdo->query("SELECT id FROM classes")->fetchAll(PDO::FETCH_COLUMN);
    $teachers = $pdo->query("SELECT id FROM teachers")->fetchAll(PDO::FETCH_COLUMN);
    $subjects = $pdo->query("SELECT id FROM subjects")->fetchAll(PDO::FETCH_COLUMN);
    $rooms = $pdo->query("SELECT id FROM classrooms")->fetchAll(PDO::FETCH_COLUMN);

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $periods = [
        ['08:30:00', '09:30:00'],
        ['09:30:00', '10:30:00'],
        ['10:30:00', '11:30:00'],
        ['12:30:00', '13:30:00'],
        ['13:30:00', '14:30:00'],
        ['14:30:00', '15:30:00']
    ];

    // Guarantee 1-to-1 mapping of subject to teacher if we have enough
    $subj_teacher = [];
    foreach ($subjects as $idx => $s_id) {
        $subj_teacher[$s_id] = $teachers[$idx % count($teachers)];
    }

    $teacher_schedule = [];
    
    // For each class, assign a schedule
    foreach ($classes as $c_idx => $class_id) {
        
        $offset = $c_idx; // Shift starting subject for each class to avoid immediate conflicts
        
        foreach ($days as $day_idx => $day) {
            foreach ($periods as $p_idx => $period) {
                $s_time = $period[0];
                $e_time = $period[1];

                $assigned = false;
                for ($attempt = 0; $attempt < count($subjects); $attempt++) {
                    $s_idx = ($offset + $attempt + $day_idx + $p_idx) % count($subjects);
                    $subj_id = $subjects[$s_idx];
                    $teacher_id = $subj_teacher[$subj_id];
                    $room_id = $rooms[($day_idx + $p_idx) % count($rooms)];

                    if (!isset($teacher_schedule[$day][$p_idx][$teacher_id])) {
                        // Assign
                        $pdo->prepare("INSERT INTO schedule (subject_id, teacher_id, class_id, room_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$subj_id, $teacher_id, $class_id, $room_id, $day, $s_time, $e_time]);
                        
                        $teacher_schedule[$day][$p_idx][$teacher_id] = true;
                        $offset = ($offset + $attempt + 1) % count($subjects);
                        $assigned = true;
                        break;
                    }
                }
                
                if (!$assigned) {
                    echo "Could not schedule for Period $p_idx on $day for class $class_id\n";
                }
            }
        }
    }

    echo "Schedule generation complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
session_start();
include '../db/db_connect.php';

header("Content-Type: application/json");

// Ensure only teachers can access this
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access!"]);
    exit();
}

$teacher_id = $_SESSION['id'];

// Fetch scheduled classes assigned to the teacher
$query = "SELECT cs.id, cs.class_title, cs.class_date, cs.start_time, cs.end_time, 
                 s.subject_name, s.course_id, c.course_name
          FROM class_schedule cs
          JOIN subjects s ON cs.subject_id = s.id
          JOIN courses c ON s.course_id = c.id  -- Fetch course_id from subjects
          WHERE cs.teacher_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = [
        "id" => $row['id'],
        "title" => "{$row['subject_name']} ({$row['course_name']})", 
        "start" => $row['class_date'] . "T" . $row['start_time'],
        "end" => $row['class_date'] . "T" . $row['end_time']
    ];
}

// Return JSON response
echo json_encode($events);

$stmt->close();
$conn->close();
?>

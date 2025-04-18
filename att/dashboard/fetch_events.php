<?php
session_start();
include '../db/db_connect.php';

header("Content-Type: application/json");

if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access!"]);
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

if ($user_role === 'student') {
    // Fetch scheduled classes for the teacher from teacher_subjects
    $query = "SELECT cs.id, s.subject_name, cs.start_time, cs.end_time 
              FROM class_schedule cs
              JOIN subjects s ON cs.subject_id = s.id
              JOIN teacher_subjects ts ON cs.subject_id = ts.subject_id 
              WHERE ts.teacher_id = ? AND cs.subject_id = ts.subject_id";
} else {
    // Fetch scheduled classes for the student from student_subjects
    $query = "SELECT cs.id, s.subject_name, cs.start_time, cs.end_time 
              FROM class_schedule cs
              JOIN subjects s ON cs.subject_id = s.id
              JOIN student_subjects ss ON cs.subject_id = ss.subject_id 
              WHERE ss.student_id = ? AND cs.subject_id = ss.subject_id";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = [
        "id" => $row['id'],
        "title" => $row['subject_name'],
        "start" => $row['start_time'],
        "end" => $row['end_time']
    ];
}

if (empty($events)) {
    echo json_encode(["status" => "error", "message" => "No scheduled classes found."]);
    exit();
}

echo json_encode($events);

$stmt->close();
$conn->close();
?>
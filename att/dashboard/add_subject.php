<?php
session_start();
include '../db/db_connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access!"]);
    exit();
}

$teacher_id = $_SESSION['id'];
$subject_name = trim($_POST['new_subject']);
$course_id = $_POST['course_id'];

if (empty($subject_name) || empty($course_id)) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit();
}

// Check if the subject already exists for the given course
$query = "SELECT id FROM subjects WHERE LOWER(subject_name) = LOWER(?) AND course_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $subject_name, $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Subject already exists for this course."]);
} else {
    // Insert new subject with the course and teacher association
    $query = "INSERT INTO subjects (subject_name, course_id, teacher_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $subject_name, $course_id, $teacher_id);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Subject added successfully!",
            "subject_id" => $stmt->insert_id,
            "subject_name" => $subject_name
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding subject."]);
    }
}

$stmt->close();
$conn->close();
?>

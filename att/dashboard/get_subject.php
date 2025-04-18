<?php
include '../db/db_connect.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']); // Ensure it's an integer

    if (!$course_id) {
        echo json_encode(["status" => "error", "message" => "Invalid course ID"]);
        exit;
    }

    $query = "SELECT id, subject_name FROM subjects WHERE course_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = [
            "id" => $row['id'], 
            "subject_name" => $row['subject_name']
        ];
    }

    // ✅ Debugging Output
    echo json_encode(["status" => "success", "subjects" => $subjects]);
    exit;
}

// Default response if invalid request
echo json_encode(["status" => "error", "message" => "Invalid request"]);
exit;
?>

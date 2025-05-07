<?php
include '../db/db_connect.php';

if (isset($_POST['subject_id']) && isset($_POST['teacher_id'])) {
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'];

    $query = "UPDATE subjects SET teacher_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $teacher_id, $subject_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }

    $stmt->close();
    $conn->close();
}
?>

<?php
include '../db/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $course_id = empty($_POST['course_id']) ? null : $_POST['course_id'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, course_id=? WHERE id=?");
    $stmt->bind_param("sssii", $name, $email, $role, $course_id, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}
?>

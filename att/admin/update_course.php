<?php
include '../db/db_connect.php';

$response = ['status' => 'error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $new_course_name = trim($_POST['course_name']);

    if ($id && !empty($new_course_name)) {
        // Get current course name from DB
        $stmt = $conn->prepare("SELECT course_name FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($current_name);
        $stmt->fetch();
        $stmt->close();

        // If only case has changed, allow the update
        if (strcasecmp($current_name, $new_course_name) === 0 && $current_name !== $new_course_name) {
            $stmt = $conn->prepare("UPDATE courses SET course_name = ? WHERE id = ?");
            $stmt->bind_param("si", $new_course_name, $id);
            if ($stmt->execute()) {
                $response['status'] = 'success';
            }
            $stmt->close();
        } else {
            // Check if new name exists in any other row (case-insensitive)
            $stmt = $conn->prepare("SELECT id FROM courses WHERE LOWER(course_name) = LOWER(?) AND id != ?");
            $stmt->bind_param("si", $new_course_name, $id);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $stmt->close();
                $stmt = $conn->prepare("UPDATE courses SET course_name = ? WHERE id = ?");
                $stmt->bind_param("si", $new_course_name, $id);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                }
                $stmt->close();
            } else {
                $response['status'] = 'duplicate';
            }
        }
    }
}

echo json_encode($response);
?>

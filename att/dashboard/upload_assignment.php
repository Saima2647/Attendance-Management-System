<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION['name']);
$user_id = $_SESSION['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Assignment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="../style/styles.css">
</head>
<body>
    <div class="sidebar">
        <h2>Welcome, <?php echo $user_name; ?></h2>
        <a href="../dashboard/student_dashboard.php">Dahboard</a>
        <a href="../dashboard/student_subject.php">Subject Notes</a>
        <a href="../dashboard/view_attendance.php">View Attendance</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
    
    <div class="content">
        <div class="container mt-5">
            <h2 class="mb-4">Welcome, <?php echo $user_name; ?>! These are your uploaded assignments:</h2>
        </div>
    </div>
</body>
</html>

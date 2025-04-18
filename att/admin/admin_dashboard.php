<?php
session_start();
include '../db/db_connect.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch total students
$total_students_query = "SELECT COUNT(*) AS total FROM users WHERE role='student'";
$total_students_result = $conn->query($total_students_query);
$total_students = $total_students_result->fetch_assoc()['total'];

// Fetch total teachers
$total_teachers_query = "SELECT COUNT(*) AS total FROM users WHERE role='teacher'";
$total_teachers_result = $conn->query($total_teachers_query);
$total_teachers = $total_teachers_result->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/admin_styles.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">  <!-- Fixed class name -->
            <h2>Admin Panel</h2>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_courses.php">Manage Courses</a>
            <a href="manage_subjects.php">Manage Subjects</a>
            <a href="view_attendance.php">View Attendance</a>
            <a href="generate_codes.php">Generate Registration Codes</a>
            <a href="logout.php">Logout</a>
        </aside>

        <main class="admin-content">  <!-- Fixed class name -->
            <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
            <div class="dashboard-stats">
                <div class="dashboard-card">
                    <h3>Total Students</h3>
                    <p><?php echo $total_students; ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Total Teachers</h3>
                    <p><?php echo $total_teachers; ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

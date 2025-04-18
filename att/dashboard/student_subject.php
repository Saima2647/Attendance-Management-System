<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION['name']);
$user_id = $_SESSION['id'];

// Fetch subjects assigned to the student
$query = "SELECT s.id, s.subject_name, c.course_name 
          FROM student_subjects ss
          JOIN subjects s ON ss.subject_id = s.id
          JOIN courses c ON s.course_id = c.id
          WHERE ss.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$subjects = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/styles.css">
</head>
<body>
<div class="sidebar">
        <h2>Welcome, <?php echo $user_name; ?></h2>
        <a href="../dashboard/student_dashboard.php">Dashboard</a>
        <a href="../dashboard/upload_assignment.php">Upload Assignment</a>
        <a href="../dashboard/view_attendance.php">View Attendance</a>
        <a href="../auth/logout.php">Logout</a>
</div>

<div class="content">
<div class="container mt-5">
    <h2 class="mb-4">Welcome, <?php echo $user_name; ?>! These are your subjects:</h2>
    <div class="row">
        <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $subject): ?>
                <div class="col-md-4 mb-4">
                    <a href="student_subject_details.php?subject_id=<?php echo $subject['id']; ?>" class="text-decoration-none text-dark">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                <p class="card-text"><strong>Course:</strong> <?php echo htmlspecialchars($subject['course_name']); ?></p>
                                <p class="text-primary">Click for more details</p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No subjects assigned.</p>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>

<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION['name']);
$teacher_id = $_SESSION['id'];

// Fetch all subjects assigned to the logged-in teacher
$subjectQuery = "SELECT s.id, s.subject_name, c.course_name 
                 FROM subjects s
                 JOIN courses c ON s.course_id = c.id
                 JOIN teacher_subjects ts ON s.id = ts.subject_id
                 WHERE ts.teacher_id = ?";
$subjectStmt = $conn->prepare($subjectQuery);
$subjectStmt->bind_param("i", $teacher_id);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();
$subjects = $subjectResult->fetch_all(MYSQLI_ASSOC);

$subjectStmt->close();
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
        <a href="../dashboard/teacher_dashboard.php">Dashboard</a>
        <a href="../dashboard/shedule_class.php">Schedule a Class</a>
        <a href="../dashboard/view_attendance.php">View Attendance</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
<div class="content">
<div class="container mt-5">
    <h2 class="mb-4">Subjects Assigned to You</h2>
    <div class="row">
        <?php foreach ($subjects as $subject): ?>
            <div class="col-md-4 mb-4">
                <a href="subject_details.php?subject_id=<?php echo $subject['id']; ?>" class="text-decoration-none text-dark">
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
    </div>
</div>
</div>
</body>
</html>

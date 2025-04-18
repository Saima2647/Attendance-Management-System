<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['id'];

if (!isset($_GET['subject_id'])) {
    echo "Subject ID is required.";
    exit();
}

$subject_id = $_GET['subject_id'];

// Fetch subject and course info
$query = "SELECT s.subject_name, c.course_name
          FROM subjects s
          JOIN courses c ON s.course_id = c.id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();
$stmt->close();

// Fetch assignments uploaded by this student for this subject
$assignmentQuery = "SELECT id, file_path, upload_date 
                    FROM assignments 
                    WHERE student_id = ? AND subject_id = ?";
$assignStmt = $conn->prepare($assignmentQuery);
$assignStmt->bind_param("ii", $student_id, $subject_id);
$assignStmt->execute();
$assignmentsResult = $assignStmt->get_result();
$assignments = $assignmentsResult->fetch_all(MYSQLI_ASSOC);
$assignStmt->close();

// Fetch attendance records for this subject
$attendanceQuery = "SELECT status, attendance_date 
                    FROM attendance 
                    WHERE student_id = ? AND subject_id = ?";
$attStmt = $conn->prepare($attendanceQuery);
$attStmt->bind_param("ii", $student_id, $subject_id);
$attStmt->execute();
$attResult = $attStmt->get_result();
$attendance = $attResult->fetch_all(MYSQLI_ASSOC);
$attStmt->close();

// Calculate percentage for this subject
$totalClasses = count($attendance);
$presentCount = 0;
foreach ($attendance as $a) {
    if (strtolower($a['status']) === 'present') $presentCount++;
}
$subjectPercentage = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 2) : 0;

// Calculate overall attendance across all subjects
$overallQuery = "SELECT status FROM attendance WHERE student_id = ?";
$overallStmt = $conn->prepare($overallQuery);
$overallStmt->bind_param("i", $student_id);
$overallStmt->execute();
$overallResult = $overallStmt->get_result();
$overall = $overallResult->fetch_all(MYSQLI_ASSOC);
$overallStmt->close();

$totalOverall = count($overall);
$presentOverall = 0;
foreach ($overall as $a) {
    if (strtolower($a['status']) === 'present') $presentOverall++;
}
$overallPercentage = $totalOverall > 0 ? round(($presentOverall / $totalOverall) * 100, 2) : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subject Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <?php if ($subject): ?>
        <h2><?php echo htmlspecialchars($subject['subject_name']); ?> - Details</h2>
        <ul class="list-group mt-4 mb-4">
            <li class="list-group-item"><strong>Course:</strong> <?php echo htmlspecialchars($subject['course_name']); ?></li>
            <li class="list-group-item text-success"><strong>Attendance Percentage for this Subject:</strong> <?php echo $subjectPercentage; ?>%</li>
            <li class="list-group-item text-primary"><strong>Overall Attendance Percentage:</strong> <?php echo $overallPercentage; ?>%</li>
        </ul>

        <!-- Assignments Section -->
        <h4 class="mt-5">Your Uploaded Assignments</h4>
        <?php if (!empty($assignments)): ?>
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Upload Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assign): ?>
                        <tr>
                            <td><a href="../<?php echo $assign['file_path']; ?>" target="_blank">View File</a></td>
                            <td><?php echo htmlspecialchars($assign['upload_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assignments uploaded yet.</p>
        <?php endif; ?>

        <!-- Attendance Section -->
        <h4 class="mt-5">Attendance Records for This Subject</h4>
        <?php if (!empty($attendance)): ?>
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $att): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($att['attendance_date']); ?></td>
                            <td><?php echo ucfirst($att['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No attendance records found.</p>
        <?php endif; ?>

        <a href="student_subject.php" class="btn btn-secondary mt-4">Back to Subjects</a>
    <?php else: ?>
        <div class="alert alert-warning">Subject not found.</div>
    <?php endif; ?>
</div>
</body>
</html>

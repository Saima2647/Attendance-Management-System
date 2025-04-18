<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['subject_id'])) {
    echo "Subject ID not provided.";
    exit();
}

$subject_id = $_GET['subject_id'];
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

// Fetch subject details
$query = "SELECT s.subject_name, c.course_name, u.name AS teacher_name
          FROM subjects s
          JOIN courses c ON s.course_id = c.id
          JOIN users u ON s.teacher_id = u.id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();
$stmt->close();

// Fetch assignments
$assignmentsQuery = "SELECT a.id, u.name AS student_name, a.file_path, a.upload_date
                     FROM assignments a
                     JOIN users u ON a.student_id = u.id
                     WHERE a.subject_id = ?";
$assignmentsStmt = $conn->prepare($assignmentsQuery);
$assignmentsStmt->bind_param("i", $subject_id);
$assignmentsStmt->execute();
$assignmentsResult = $assignmentsStmt->get_result();
$assignments = $assignmentsResult->fetch_all(MYSQLI_ASSOC);
$assignmentsStmt->close();

// Fetch attendance records with optional filter
$attendanceQuery = "SELECT a.attendance_date, a.status, u.name AS student_name
                    FROM attendance a
                    JOIN users u ON a.student_id = u.id
                    WHERE a.subject_id = ?";
if (!empty($filter_date)) {
    $attendanceQuery .= " AND a.attendance_date = ?";
}
$attendanceStmt = $conn->prepare($attendanceQuery);
if (!empty($filter_date)) {
    $attendanceStmt->bind_param("is", $subject_id, $filter_date);
} else {
    $attendanceStmt->bind_param("i", $subject_id);
}
$attendanceStmt->execute();
$attendanceResult = $attendanceStmt->get_result();
$attendance = $attendanceResult->fetch_all(MYSQLI_ASSOC);
$attendanceStmt->close();

$conn->close();

// Calculate attendance summary
$summary = ['present' => 0, 'absent' => 0, 'late' => 0];
foreach ($attendance as $record) {
    $status = strtolower($record['status']);
    if (isset($summary[$status])) {
        $summary[$status]++;
    }
}
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
            <li class="list-group-item"><strong>Assigned Teacher:</strong> <?php echo htmlspecialchars($subject['teacher_name']); ?></li>
        </ul>

        <!-- Assignments Section -->
        <h4 class="mt-5">Assignments Uploaded by Students</h4>
        <?php if (!empty($assignments)): ?>
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>File</th>
                        <th>Upload Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assign): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assign['student_name']); ?></td>
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
        <h4 class="mt-5">Attendance Records</h4>

        <!-- Filter Form -->
        <form class="row row-cols-lg-auto g-3 align-items-center mb-3" method="GET">
            <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
            <div class="col-auto">
                <label for="filter_date" class="form-label">Filter by Date:</label>
                <input type="date" class="form-control" name="filter_date" id="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="subject_details.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Attendance Summary -->
        <div class="mb-3">
            <strong>Summary:</strong>
            <span class="badge bg-secondary">Present: <?php echo $summary['present']; ?></span>
            <span class="badge bg-secondary">Absent: <?php echo $summary['absent']; ?></span>
            <span class="badge bg-secondary">Late: <?php echo $summary['late']; ?></span>
        </div>

        <?php if (!empty($attendance)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $att): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($att['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($att['attendance_date']); ?></td>
                            <td><?php echo ucfirst($att['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No attendance records found<?php if ($filter_date) echo " for selected date."; ?>.</p>
        <?php endif; ?>

        <a href="../dashboard/teachers_subject.php" class="btn btn-secondary mt-4">Back to Subjects</a>
    <?php else: ?>
        <div class="alert alert-warning">Subject not found.</div>
    <?php endif; ?>
</div>
</body>
</html>

<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['id'];

if (!isset($_GET['class_id'])) {
    echo "Invalid request!";
    exit();
}

$class_id = $_GET['class_id'];

// Get class details
$class_query = "SELECT subject_id, class_date FROM class_schedule WHERE id = ? AND teacher_id = ?";
$stmt = $conn->prepare($class_query);
$stmt->bind_param("ii", $class_id, $teacher_id);
$stmt->execute();
$class_result = $stmt->get_result();

if ($class_result->num_rows === 0) {
    echo "Class not found or unauthorized access!";
    exit();
}

$class_data = $class_result->fetch_assoc();
$subject_id = $class_data['subject_id'];
$class_date = $class_data['class_date'];

// Fetch students
$query = "SELECT ss.student_id, u.name 
          FROM student_subjects ss
          JOIN users u ON ss.student_id = u.id
          WHERE ss.subject_id = ? AND u.role = 'student'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Handle submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attendance'])) {
    foreach ($_POST['attendance'] as $student_id => $status) {
        $query = "INSERT INTO attendance (student_id, subject_id, attendance_date, status) 
                  VALUES (?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE status = VALUES(status)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $student_id, $subject_id, $class_date, $status);
        $stmt->execute();
    }

    $delete_stmt = $conn->prepare("DELETE FROM class_schedule WHERE id = ? AND teacher_id = ?");
    $delete_stmt->bind_param("ii", $class_id, $teacher_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    echo "<script>alert('Attendance marked successfully!'); window.location.href = '../dashboard/teacher_dashboard.php';</script>";
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .attendance-table th {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
        }
        .attendance-table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .btn-check + .btn {
            min-width: 80px;
        }
        .search-box {
            max-width: 400px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="fw-bold">📋 Take Attendance</h2>
        <div>
            <span class="text-muted">Class Date:</span> <strong><?= htmlspecialchars($class_date); ?></strong>
        </div>
    </div>

    <div class="mb-3 search-box">
        <input type="text" class="form-control" id="searchInput" placeholder="🔍 Search student...">
    </div>

    <form method="POST">
        <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
            <table class="table table-bordered table-hover align-middle attendance-table" id="attendanceTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Late</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach ($students as $student): ?>
                        <tr>
                            <td><?= $count++; ?></td>
                            <td class="student-name"><?= htmlspecialchars($student['name']); ?></td>
                            <td class="text-center">
                                <input type="radio" class="btn-check" name="attendance[<?= $student['student_id']; ?>]" id="present<?= $student['student_id']; ?>" value="present" required>
                                <label class="btn btn-outline-success btn-sm" for="present<?= $student['student_id']; ?>"><i class="fas fa-check-circle"></i></label>
                            </td>
                            <td class="text-center">
                                <input type="radio" class="btn-check" name="attendance[<?= $student['student_id']; ?>]" id="absent<?= $student['student_id']; ?>" value="absent">
                                <label class="btn btn-outline-danger btn-sm" for="absent<?= $student['student_id']; ?>"><i class="fas fa-times-circle"></i></label>
                            </td>
                            <td class="text-center">
                                <input type="radio" class="btn-check" name="attendance[<?= $student['student_id']; ?>]" id="late<?= $student['student_id']; ?>" value="late">
                                <label class="btn btn-outline-warning btn-sm" for="late<?= $student['student_id']; ?>"><i class="fas fa-clock"></i></label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <button type="submit" name="mark_attendance" class="btn btn-success btn-lg px-4">
                <i class="fas fa-paper-plane"></i> Submit Attendance
            </button>
        </div>
    </form>
</div>

<script>
    // Simple search filter
    document.getElementById('searchInput').addEventListener('keyup', function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#attendanceTable tbody tr');
        rows.forEach(row => {
            let name = row.querySelector('.student-name').textContent.toLowerCase();
            row.style.display = name.includes(filter) ? '' : 'none';
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

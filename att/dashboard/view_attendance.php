<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'];
$user_name = $_SESSION['name']; // make sure this is stored in session at login

if ($role === 'teacher') {
    $query = "SELECT a.id, u.name AS student_name, sub.subject_name, c.course_name, a.status, a.attendance_date
              FROM attendance a
              JOIN users u ON a.student_id = u.id
              JOIN subjects sub ON a.subject_id = sub.id
              JOIN courses c ON sub.course_id = c.id
              WHERE sub.teacher_id = ?
              ORDER BY a.attendance_date DESC";
} else if ($role === 'student') {
    $query = "SELECT a.id, sub.subject_name, c.course_name, a.status, a.attendance_date
              FROM attendance a
              JOIN subjects sub ON a.subject_id = sub.id
              JOIN courses c ON sub.course_id = c.id
              WHERE a.student_id = ?
              ORDER BY a.attendance_date DESC";
} else {
    echo "Unauthorized access!";
    exit();
}

// Calculate overall attendance across all subjects
$overallQuery = "SELECT status FROM attendance WHERE student_id = ?";
$overallStmt = $conn->prepare($overallQuery);
$overallStmt->bind_param("i", $user_id);
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


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$attendance_records = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Records</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../style/styles.css"> 
</head>

<body>

<!-- Sidebar based on role -->
<div class="sidebar">
    <h2>Welcome, <?php echo htmlspecialchars($user_name); ?></h2>

    <?php if ($role === 'student'): ?>
        <a href="../dashboard/student_dashboard.php">Dashboard</a>
        <a href="../dashboard/student_subject.php">Subject Notes</a>
        <a href="../dashboard/student_subject.php">Upload Assignment</a>
        <a href="../auth/logout.php">Logout</a>
    <?php elseif ($role === 'teacher'): ?>
        <a href="../dashboard/teacher_dashboard.php">Dashboard</a>
        <a href="../dashboard/teachers_subject.php">Subject Deatils</a>
        <a href="../dashboard/shedule_class.php">Schedule a Class</a>
        <a href="../auth/logout.php">Logout</a>
    <?php endif; ?>
</div>

<!-- Main content -->
<div class="content">
    <h2 class="mb-4">Attendance Records</h2>

    <div class="mb-3">
        <label for="dateFilter" class="form-label">Filter by Date:</label>
        <input type="text" id="dateFilter" class="form-control" placeholder="Select a date">
    </div>

    <div>
        <?php if ($role === 'student'): ?>
        <ul class="list-group mt-4 mb-4">
            <li class="list-group-item text-primary"><strong>Overall Attendance Percentage:</strong> <?php echo $overallPercentage; ?>%</li>
        </ul>
        <?php endif; ?>
    </div>


    <div class="table-responsive">
        <table id="attendanceTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <?php if ($role === 'teacher') echo "<th>Student Name</th>"; ?>
                    <th>Subject</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_records as $record): ?>
                    <tr>
                        <?php if ($role === 'teacher') echo "<td>" . htmlspecialchars($record['student_name']) . "</td>"; ?>
                        <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                        <td>
                            <?php
                            $status = htmlspecialchars($record['status']);
                            $badgeClass = match($status) {
                                'present' => 'success',
                                'absent' => 'danger',
                                'late' => 'warning',
                                default => 'secondary'
                            };
                            echo "<span class='badge bg-$badgeClass text-uppercase'>$status</span>";
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($record['attendance_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
    $(function () {
        <?php if ($role === 'teacher'): ?>
        const table = $('#attendanceTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            pageLength: 10
        });

        $("#dateFilter").datepicker({
            dateFormat: "yy-mm-dd",
            onSelect: function (dateText) {
                table.column(4).search(dateText).draw();
            }
        });
        <?php else: ?>
        const table = $('#attendanceTable').DataTable({
            pageLength: 10
        });

        $("#dateFilter").datepicker({
            dateFormat: "yy-mm-dd",
            onSelect: function (dateText) {
                table.column(3).search(dateText).draw();
            }
        });
        <?php endif; ?>
    });
</script>


</body>
</html>

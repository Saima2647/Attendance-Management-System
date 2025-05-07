<?php
session_start();
include '../db/db_connect.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch all courses
$courses_query = "SELECT id, course_name FROM courses";
$courses_result = $conn->query($courses_query);

// Fetch subjects based on selected course
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : '';

$subjects_query = "SELECT id, subject_name FROM subjects WHERE course_id='$course_id'";
$subjects_result = $conn->query($subjects_query);

// Fetch attendance records based on selected course and subject
$attendance_result = [];

if (!empty($subject_id)) {
    // Show per-subject attendance percentage
    $query = "SELECT u.id AS student_id, u.name AS student_name,
                COUNT(a.id) AS total_classes,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count
              FROM users u
              LEFT JOIN attendance a ON u.id = a.student_id AND a.subject_id = ?
              JOIN student_subjects ss ON ss.student_id = u.id
              WHERE ss.subject_id = ?
              GROUP BY u.id, u.name";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $subject_id, $subject_id);
    $stmt->execute();
    $attendance_result = $stmt->get_result();
    $mode = "subject";
} else {
    // Show overall attendance percentage (all subjects)
    $query = "SELECT u.id AS student_id, u.name AS student_name,
                COUNT(a.id) AS total_classes,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count
              FROM users u
              LEFT JOIN attendance a ON u.id = a.student_id
              JOIN student_subjects ss ON ss.student_id = u.id
              JOIN subjects s ON ss.subject_id = s.id";

    if (!empty($course_id)) {
        $query .= " WHERE s.course_id = ?";
    }

    $query .= " GROUP BY u.id, u.name";

    if (!empty($course_id)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $course_id);
    } else {
        $stmt = $conn->prepare($query);
    }

    $stmt->execute();
    $attendance_result = $stmt->get_result();
    $mode = "overall";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../style/admin_styles.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <h2>Admin Panel</h2>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_courses.php">Manage Courses</a>
            <a href="manage_subjects.php">Manage Subjects</a>
            <a href="view_attendance.php" class="active">View Attendance</a>
            <a href="generate_codes.php">Generate Registration Codes</a>
            <a href="../auth/logout.php">Logout</a>
        </aside>

        <main class="admin-content">
            <h1>View Attendance</h1>
            <form method="GET" class="mb-3">
                <label for="course" class="form-label">Select Course:</label>
                <select name="course_id" id="course" class="form-select" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php while ($course = $courses_result->fetch_assoc()) { ?>
                        <option value="<?php echo $course['id']; ?>" <?php if ($course_id == $course['id']) echo 'selected'; ?>>
                            <?php echo $course['course_name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </form>

            <form method="GET">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                <label for="subject" class="form-label">Select Subject:</label>
                <select name="subject_id" id="subject" class="form-select" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php while ($subject = $subjects_result->fetch_assoc()) { ?>
                        <option value="<?php echo $subject['id']; ?>" <?php if ($subject_id == $subject['id']) echo 'selected'; ?>>
                            <?php echo $subject['subject_name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </form>
            <br>
            
            <div class="table-responsive">
            <table  id="attendanceTable" class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Total Classes</th>
                        <th>Present</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $attendance_result->fetch_assoc()) {
                        $total = $row['total_classes'];
                        $present = $row['present_count'];
                        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
                    ?>
                        <tr>
                            <td><?php echo $row['student_id']; ?></td>
                            <td><?php echo $row['student_name']; ?></td>
                            <td><?php echo $total; ?></td>
                            <td><?php echo $present; ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        </main>
    </div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> <!-- show all the buttons -->
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>   <!-- next button design-->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script> <!-- to show the pdf button -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script> <!--space module & button design--> 
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script> <!--cvs and copy module-->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script> <!-- print module -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script> <!-- excel module -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script> <!-- pdf module  --> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script> <!-- next and previous button -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>


<script>
    $(function () {
        const table = $('#attendanceTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            pageLength: 10
        });
    });
</script>
</body>
</html>

<?php
session_start();
include '../db/db_connect.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Function to generate random codes
function generateCode($length = 8) {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
}

// Handle code generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $role = $_POST['role'];
    $count = intval($_POST['count']);
    for ($i = 0; $i < $count; $i++) {
        $code = generateCode();
        $stmt = $conn->prepare("INSERT INTO registration_codes (code, role, used) VALUES (?, ?, 0)");
        $stmt->bind_param("ss", $code, $role);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: generate_codes.php");
    exit();
}

// Handle code deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM registration_codes WHERE id = $delete_id");
    header("Location: generate_codes.php");
    exit();
}

// Handle export request
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="registration_codes.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Code', 'Role']);

    $export_query = $conn->query("SELECT id, code, role FROM registration_codes");
    while ($row = $export_query->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Fetch existing codes
$codes_query = "SELECT * FROM registration_codes ORDER BY role";
$codes_result = $conn->query($codes_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Registration Codes</title>
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
            <a href="view_attendance.php">View Attendance</a>
            <a href="generate_codes.php" class="active">Generate Registration Codes</a>
            <a href="../auth/logout.php">Logout</a>
        </aside>

        <main class="admin-content">
            <h1>Generate Registration Codes</h1>
            <form method="POST" class="mb-4">
                <label for="role">Select Role:</label>
                <select name="role" class="form-select" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
                <label for="count">Number of Codes:</label>
                <input type="number" name="count" class="form-control" min="1" required>
                <button type="submit" name="generate" class="btn btn-primary mt-2">Generate Codes</button>
            </form>
            
            <h2>Existing Registration Codes</h2>

            <div class="table-responsive">
            <table id="attendanceTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($code = $codes_result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $code['code']; ?></td>
                            <td><?php echo ucfirst($code['role']); ?></td>
                            <td>
                                <a href="generate_codes.php?delete_id=<?php echo $code['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this code?');">
                                    Delete
                                </a>
                            </td>
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

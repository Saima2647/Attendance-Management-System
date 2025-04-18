<?php
session_start();
include '../db/db_connect.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch users
$users_query = "SELECT id, name, email, role FROM users ORDER BY role";
$users_result = $conn->query($users_query);

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/admin_styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function filterUsers(role) {
            let rows = document.querySelectorAll(".user-row");
            rows.forEach(row => {
                if (role === 'all' || row.dataset.role === role) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</head>
<body>
<div class="admin-container">
    <aside class="admin-sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_users.php" class="active">Manage Users</a>
        <a href="manage_courses.php">Manage Courses</a>
        <a href="manage_subjects.php">Manage Subjects</a>
        <a href="view_attendance.php">View Attendance</a>
        <a href="generate_codes.php">Generate Registration Codes</a>
        <a href="logout.php">Logout</a>
    </aside>

    <main class="admin-content">
        <h1>Manage Users</h1>
        <div class="btn-group mb-3">
            <button class="btn btn-secondary" onclick="filterUsers('all')">All</button>
            <button class="btn btn-secondary" onclick="filterUsers('student')">Students</button>
            <button class="btn btn-secondary" onclick="filterUsers('teacher')">Teachers</button>
            <button class="btn btn-secondary" onclick="filterUsers('admin')">Admins</button>
        </div>

        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($user = $users_result->fetch_assoc()) { ?>
                <tr class="user-row" data-role="<?php echo $user['role']; ?>" data-id="<?php echo $user['id']; ?>">
                    <td><?php echo $user['id']; ?></td>
                    <td class="user-name"><?php echo $user['name']; ?></td>
                    <td class="user-email"><?php echo $user['email']; ?></td>
                    <td class="user-role"><?php echo $user['role']; ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm edit-user">Edit</button>
                        <button class="btn btn-success btn-sm save-user d-none">Save</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure?');">Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </main>
</div>

<script>
    $(document).on("click", ".edit-user", function () {
        let row = $(this).closest("tr");
        let name = row.find(".user-name").text().trim();
        let email = row.find(".user-email").text().trim();
        let role = row.find(".user-role").text().trim();

        row.find(".user-name").html(`<input type="text" class="form-control user-name-input" value="${name}">`);
        row.find(".user-email").html(`<input type="email" class="form-control user-email-input" value="${email}">`);
        row.find(".user-role").html(`
            <select class="form-control user-role-input">
                <option value="admin" ${role === 'admin' ? 'selected' : ''}>admin</option>
                <option value="teacher" ${role === 'teacher' ? 'selected' : ''}>teacher</option>
                <option value="student" ${role === 'student' ? 'selected' : ''}>student</option>
            </select>
        `);

        row.find(".edit-user").addClass("d-none");
        row.find(".save-user").removeClass("d-none");
    });

    $(document).on("click", ".save-user", function () {
        let row = $(this).closest("tr");
        let userId = row.data("id");

        let updatedData = {
            id: userId,
            name: row.find(".user-name-input").val(),
            email: row.find(".user-email-input").val(),
            role: row.find(".user-role-input").val()
        };

        $.post("update_user.php", updatedData, function (response) {
            if (response.status === "success") {
                location.reload();
            } else {
                alert("Failed to update user.");
            }
        }, "json");
    });
</script>
</body>
</html>

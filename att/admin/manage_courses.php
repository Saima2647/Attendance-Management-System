<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add Course
if (isset($_POST['add_course'])) {
    $course_name = trim($_POST['course_name']);
    if (!empty($course_name)) {
        $stmt = $conn->prepare("INSERT INTO courses (course_name) VALUES (?)");
        $stmt->bind_param("s", $course_name);
        $stmt->execute();
        $stmt->close();
    }
}

// Delete Course
if (isset($_GET['delete'])) {
    $course_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_courses.php");
    exit();
}

// Fetch Courses
$courses_query = "SELECT * FROM courses";
$courses_result = $conn->query($courses_query);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/admin_styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="admin-container">
    <aside class="admin-sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_courses.php" class="active">Manage Courses</a>
        <a href="manage_subjects.php">Manage Subjects</a>
        <a href="view_attendance.php">View Attendance</a>
        <a href="generate_codes.php">Generate Registration Codes</a>
        <a href="logout.php">Logout</a>
    </aside>

    <main class="admin-content">
        <h1>Manage Courses</h1>

        <!-- Add Course -->
        <form method="POST" class="mb-4">
            <div class="input-group">
                <input type="text" name="course_name" class="form-control" placeholder="Enter Course Name" required>
                <button type="submit" name="add_course" class="btn btn-success">Add Course</button>
            </div>
        </form>

        <!-- Courses Table -->
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Course Name</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($course = $courses_result->fetch_assoc()) { ?>
                <tr data-id="<?php echo $course['id']; ?>">
                    <td><?php echo $course['id']; ?></td>
                    <td class="course-name"><?php echo $course['course_name']; ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm edit-course">Edit</button>
                        <button class="btn btn-success btn-sm save-course d-none">Save</button>
                        <a href="manage_courses.php?delete=<?php echo $course['id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </main>
</div>

<script>
    $(document).on("click", ".edit-course", function () {
        let row = $(this).closest("tr");
        let courseName = row.find(".course-name").text().trim();

        row.find(".course-name").html(`<input type="text" class="form-control course-input" value="${courseName}">`);
        row.find(".edit-course").addClass("d-none");
        row.find(".save-course").removeClass("d-none");
    });

    $(document).on("click", ".save-course", function () {
        let row = $(this).closest("tr");
        let courseId = row.data("id");
        let updatedName = row.find(".course-input").val();

        $.post("update_course.php", {
            id: courseId,
            course_name: updatedName
        }, function (response) {
            if (response.status === "success") {
    location.reload();
} else if (response.status === "duplicate") {
    alert("Course name already exists.");
} else {
    alert("Failed to update course.");
}
        }, "json");
    });
</script>
</body>
</html>

<?php
session_start();
include '../db/db_connect.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$error_message = ""; // To store error messages

// Handle subject addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = $_POST['subject_name'];
    $course_id = $_POST['course_id'];
    $teacher_id = $_POST['teacher_id'];

    // ✅ Check if the subject already exists for the same teacher and course
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_name = ? AND course_id = ? AND teacher_id = ?");
    $stmt->bind_param("sii", $subject_name, $course_id, $teacher_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $error_message = "Subject already exists with the same teacher and course!";
    } else {
        $stmt->close();

        // Insert new subject
        $stmt = $conn->prepare("INSERT INTO subjects (subject_name, course_id, teacher_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $subject_name, $course_id, $teacher_id);
        $stmt->execute();
        $subject_id = $stmt->insert_id;
        $stmt->close();

        // Assign subject to teacher
        $stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id, course_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $teacher_id, $subject_id, $course_id);
        $stmt->execute();
        $stmt->close();

        // Assign subject to students in the course
            $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) SELECT id, ? FROM users WHERE course_id = ? AND role = 'student'");
        $stmt->bind_param("ii", $subject_id, $course_id);
        $stmt->execute();
        $stmt->close();
        }
        
    }

// Handle subject deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $subject_id = $_POST['subject_id'];
    
    // Delete from student_subjects and teacher_subjects first
    $stmt = $conn->prepare("DELETE FROM student_subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete from subjects
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all courses
$courses_query = "SELECT id, course_name FROM courses";
$courses_result = $conn->query($courses_query);

// Fetch all teachers
$teachers_query = "SELECT id, name FROM users WHERE role = 'teacher'";
$teachers_result = $conn->query($teachers_query);
$teachers = [];
while ($row = $teachers_result->fetch_assoc()) {
    $teachers[$row['id']] = $row['name'];
}

// Fetch all subjects
$subjects_query = "SELECT subjects.id, subjects.subject_name, users.id AS teacher_id, users.name AS teacher_name, courses.course_name 
                   FROM subjects 
                   JOIN users ON subjects.teacher_id = users.id 
                   JOIN courses ON subjects.course_id = courses.id";
$subjects_result = $conn->query($subjects_query);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
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
            <a href="manage_courses.php">Manage Courses</a>
            <a href="manage_subjects.php" class="active">Manage Subjects</a>
            <a href="view_attendance.php">View Attendance</a>
            <a href="generate_codes.php">Generate Registration Codes</a>
            <a href="logout.php">Logout</a>
        </aside>

        <main class="admin-content">
            <h1>Manage Subjects</h1>
            
            <!-- Sorting Buttons -->
            <button class="btn btn-secondary" id="sortByCourse">Sort by Course</button>
            <button class="btn btn-secondary" id="sortByTeacher">Sort by Teacher</button>
            
            <!-- Subject Table -->
            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject Name</th>
                        <th>Teacher</th>
                        <th>Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="subject_table">
                    <?php while ($row = $subjects_result->fetch_assoc()) { ?>
                        <tr data-course="<?php echo $row['course_name']; ?>" data-teacher="<?php echo $row['teacher_name']; ?>">
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['subject_name']; ?></td>
                            <td>
                                <span class="teacher-name"><?php echo $row['teacher_name']; ?></span>
                                <select class="form-select teacher-dropdown d-none">
                                    <?php foreach ($teachers as $id => $name) { ?>
                                        <option value="<?php echo $id; ?>" <?php echo ($id == $row['teacher_id']) ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td><?php echo $row['course_name']; ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm edit-teacher">Edit</button>
                                <button class="btn btn-success btn-sm save-teacher d-none" data-id="<?php echo $row['id']; ?>">Save</button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="subject_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_subject" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </main>
    </div>

    <main class="admin-content">
    <!-- Add Subject Form -->
    <form method="POST" class="mb-4">
                <label>Subject Name:</label>
                <input type="text" name="subject_name" class="form-control" required>
                <label>Course:</label>
                <select name="course_id" class="form-select" required>
                    <?php while ($course = $courses_result->fetch_assoc()) { ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                    <?php } ?>
                </select>
                <label>Teacher:</label>
                <select name="teacher_id" class="form-select" required>
    <option value="0" disabled <?php echo empty($row['teacher_id']) ? 'selected' : ''; ?>>Select a Teacher</option>
    <?php foreach ($teachers as $id => $name) { ?>
        <option value="<?php echo $id; ?>" <?php echo ($id == ($row['teacher_id'] ?? 0)) ? 'selected' : ''; ?>>
            <?php echo $name; ?>
        </option>
    <?php } ?>
</select>
                <button type="submit" name="add_subject" class="btn btn-primary mt-2">Add Subject</button>
            </form>
                    </main>

    <script>
        $(document).ready(function () {
            // Handle Edit Button Click
            $(document).on("click", ".edit-teacher", function () {
                let row = $(this).closest("tr");
                row.find(".teacher-name").addClass("d-none");
                row.find(".teacher-dropdown").removeClass("d-none");
                row.find(".edit-teacher").addClass("d-none");
                row.find(".save-teacher").removeClass("d-none");
            });

            // Handle Save Button Click
            $(document).on("click", ".save-teacher", function () {
                let row = $(this).closest("tr");
                let subjectId = $(this).data("id");
                let newTeacherId = row.find(".teacher-dropdown").val();
                let newTeacherName = row.find(".teacher-dropdown option:selected").text();

                $.post("update_teacher.php", { subject_id: subjectId, teacher_id: newTeacherId }, function (response) {
                    if (response.status === "success") {
                        row.find(".teacher-name").text(newTeacherName).removeClass("d-none");
                        row.find(".teacher-dropdown").addClass("d-none");
                        row.find(".edit-teacher").removeClass("d-none");
                        row.find(".save-teacher").addClass("d-none");
                    } else {
                        alert("Failed to update teacher");
                    }
                }, "json");
            });

            // Sorting by Course
            $("#sortByCourse").click(function () {
                let rows = $("tbody#subject_table tr").get();
                rows.sort(function (a, b) {
                    return $(a).data("course").localeCompare($(b).data("course"));
                });
                $("tbody#subject_table").append(rows);
            });

            // Sorting by Teacher
            $("#sortByTeacher").click(function () {
                let rows = $("tbody#subject_table tr").get();
                rows.sort(function (a, b) {
                    return $(a).data("teacher").localeCompare($(b).data("teacher"));
                });
                $("tbody#subject_table").append(rows);
            });
        });
    </script>
</body>
</html>

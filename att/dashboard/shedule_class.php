<?php
session_start();
include '../db/db_connect.php';

$user_name = htmlspecialchars($_SESSION['name']);
$teacher_id = $_SESSION['id'];

// ✅ Handle subject deletion early, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    $class = $_POST['class_id'];

    $stmt = $conn->prepare("DELETE FROM class_schedule WHERE id= ?");
    $stmt->bind_param("i", $class);
    $stmt->execute();
    $stmt->close();

    // ✅ Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Automatically delete past classes
$deleteQuery = "DELETE FROM class_schedule WHERE class_date < CURDATE() AND teacher_id = ?";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("i", $teacher_id);
$deleteStmt->execute();
$deleteStmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['class_schedule'])) {
    $class_title = htmlspecialchars($_POST['class_title']);
    $subject_id = $_POST['subject_id'];
    $class_date = $_POST['class_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Insert class schedule
    $query = "INSERT INTO class_schedule (class_title, subject_id, teacher_id, class_date, start_time, end_time) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siisss", $class_title, $subject_id, $teacher_id, $class_date, $start_time, $end_time);
    
    if ($stmt->execute()) {
        echo "<script>alert('Class scheduled successfully!'); window.location.href = window.location.href;</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch teacher's scheduled classes along with the subject name
$sql = "SELECT sc.id, sc.class_title, sc.class_date, sc.start_time, sc.end_time, s.subject_name 
        FROM class_schedule sc
        JOIN subjects s ON sc.subject_id = s.id
        WHERE sc.teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch courses dynamically
$courseQuery = "SELECT id, course_name FROM courses";
$courseResult = $conn->query($courseQuery);

// Fetch techers subject 
$subjectQuery = "SELECT s.id, s.subject_name, c.course_name 
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.id
    JOIN courses c ON ts.course_id = c.id
    WHERE ts.teacher_id = ?
";
$subjectStmt = $conn->prepare($subjectQuery);
$subjectStmt->bind_param("i", $teacher_id);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();


// Handle subject deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    $class = $_POST['class_id'];
    
    // Delete from student_subjects and teacher_subjects first
    $stmt = $conn->prepare("DELETE FROM class_schedule WHERE id= ?");
    $stmt->bind_param("i", $class);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule a Class</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="../style/styles.css">
</head>
<body>
<div class="sidebar">
<h2>Welcome, <?php echo $user_name; ?></h2>
        <a href="../dashboard/teacher_dashboard.php">Dashboard</a>
        <a href="../dashboard/teachers_subject.php">Subject Deatils</a>
        <a href="../dashboard/view_attendance.php">View Attendance</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
    <div class="shedule-container">

<div class="container mt-4">
    <h2>Welcome, <?php echo $_SESSION['name']; ?></h2>
    <h3>Schedule a Class</h3>
    
    <form method="POST">
        <div class="mb-3">
            <label>Class Title</label>
            <input type="text" name="class_title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Course</label>
            <select id="courseDropdown" class="form-control" required>
                <option value="">Select Course</option>
                <?php while ($course = $courseResult->fetch_assoc()): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
        <label for="subjectDropdown">Subject</label>
        <div class="input-group">
        <select name="subject_id" id="subjectDropdown" class="form-control" required>
            <option value="">Select Subject</option>
            <?php while ($subject = $subjectResult->fetch_assoc()): ?>
                <option value="<?php echo $subject['id']; ?>">
                    <?php echo htmlspecialchars($subject['subject_name'] . " (" . $subject['course_name'] . ")"); ?>
                </option>
            <?php endwhile; ?>
        </select>
            <!-- ✅ Plus Button to Open Add Subject Modal -->
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSubjectModal">+</button>
                </div>
        </div>

        <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">

        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="class_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Start Time</label>
            <input type="time" name="start_time" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>End Time</label>
            <input type="time" name="end_time" class="form-control" required>
        </div>
        <button type="submit" name="class_schedule" class="btn btn-primary">Schedule Class</button>
    </form>
</div>
                </div>

<div class="container mt-4">
<div class="shedule-container">
    <h3>Your Scheduled Classes</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Class Title</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Take Attendance</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['class_title']; ?></td>
                    <td><?php echo $row['subject_name']; ?></td>
                    <td><?php echo $row['class_date']; ?></td>
                    <td><?php echo $row['start_time']; ?></td>
                    <td><?php echo $row['end_time']; ?></td>
                    <td><a href="../dashboard/take_attendance.php?class_id=<?php echo $row['id']; ?>" class="btn btn-success">Take Attendance</a></td>
                    <td> <form method="POST" class="d-inline">
                                    <input type="hidden" name="class_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_class" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</button>
                                </form></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
            </div>

<!-- ✅ Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubjectLabel">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSubjectForm">
                    <div class="mb-3">
                        <label>Enter Subject Name</label>
                        <input type="text" name="new_subject" id="new_subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Select Course</label>
                        <select name="course_id" id="course_id" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php
                            include '../db/db_connect.php'; // Database connection
                            $query = "SELECT * FROM courses";  // Fetch all courses
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['course_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function () {
    // When course dropdown changes
    $('#courseDropdown').change(function () {
        var course_id = $(this).val();

        if (course_id) {
            $.ajax({
                url: 'get_subject.php', // PHP file to fetch subjects
                type: 'POST',
                data: { course_id: course_id }, // Sending course_id
                dataType: 'json',
                success: function (response) {
                    console.log("Full Response:", response); // ✅ Debugging output

                    // Clear the subject dropdown first
                    $('#subjectDropdown').html('<option value="">Select Subject</option>');

                    // ✅ Check if the response is successful and contains subjects
                    if (response.status === "success" && Array.isArray(response.subjects)) {
                        // Loop through the subjects and append them to the dropdown
                        $.each(response.subjects, function (index, subject) {
                            console.log("Subject Data:", subject); // ✅ Debugging output
                            $('#subjectDropdown').append(`<option value="${subject.id}">${subject.subject_name}</option>`);
                        });
                    } else {
                        alert("No subjects found for the selected course.");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error); // ✅ Debugging output
                    alert("Error fetching subjects. Please try again.");
                }
            });
        } else {
            // Reset the subject dropdown if no course is selected
            $('#subjectDropdown').html('<option value="">Select Subject</option>');
        }
    });
});

</script>


<script>
$(document).ready(function () {
    $("#addSubjectForm").submit(function (e) {
        e.preventDefault(); // Prevent form submission

        var subjectName = $("#new_subject").val().trim();
        var courseId = $("#course_id").val();

        // ✅ Debugging - Check if values are captured
        console.log("Subject Name:", subjectName);
        console.log("Course ID:", courseId);

        if (subjectName === "" || courseId === "") {
            alert("Please enter all fields.");
            return;
        }

        $.ajax({
            url: "add_subject.php",  // PHP script to handle insertion
            type: "POST",
            data: { new_subject: subjectName, course_id: courseId },
            dataType: "json",
            success: function (response) {
                console.log("Server Response:", response);
                if (response.status === "success") {
                    alert(response.message);

                    // ✅ Add new subject to dropdown and select it
                    $("#subjectDropdown").append(`<option value="${response.subject_id}"<selected>${response.subject_name}</option>`);


                    // ✅ Close modal and reset form
                    $("#addSubjectModal").modal("hide");
                    $("#addSubjectForm")[0].reset();
                } else {
                    alert(response.message);
                }
            },
            error: function () {
                alert("Error adding subject.");
            }
        });
    });
});
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

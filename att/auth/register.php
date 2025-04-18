<?php
session_start();
include '../db/db_connect.php'; // Database connection file

$student_id = $conn->insert_id;  // or fetched from the session


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $unique_code = mysqli_real_escape_string($conn, $_POST['unique_code']);

    // Course ID should only be set for students
    $course_id = ($role === 'student' && !empty($_POST['course_id'])) ? (int)$_POST['course_id'] : NULL;

    // ✅ Verify unique code from database
    $stmt = $conn->prepare("SELECT * FROM registration_codes WHERE code=? AND role=?");
    $stmt->bind_param("ss", $unique_code, $role);
    $stmt->execute();
    $codeCheckResult = $stmt->get_result();
    $stmt->close();

    if ($codeCheckResult->num_rows == 0) {
        $error = "Invalid or unauthorized unique code.";
    } else {
        // ✅ Prevent duplicate registrations
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        $stmt->close();

        if ($checkResult->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            // ✅ Insert new user into the database
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, course_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $email, $password, $role, $course_id);

            if ($stmt->execute()) {
            $student_id = $stmt->insert_id; // ✅ Get newly inserted student ID
    
            // ✅ Call your subject assign function here
            assignSubjectsToStudent($conn, $student_id, $course_id);
    
            echo "Student registered successfully.";

                // ✅ Remove the unique code after successful registration
                $stmt = $conn->prepare("DELETE FROM registration_codes WHERE code=?");
                $stmt->bind_param("s", $unique_code);
                $stmt->execute();
                $stmt->close();

                // ✅ Redirect to login page
                header("Location: ../index.php");
                exit();
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
    
}

function assignSubjectsToStudent($conn, $student_id, $course_id) {
    $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id)
                            SELECT ?, id FROM subjects WHERE course_id = ?");
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/styles.css">
</head>
<body>
    <div class="auth-container">
        <h3 class="text-center">Register</h3>
        <?php if (isset($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <select name="role" id="role" class="form-control" required>
                    <option value="">Select Role</option>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="mb-3" id="courseField">
                <select name="course_id" class="form-control">
                    <option value="">Select Course</option>
                    <?php
                    $result = $conn->query("SELECT id, course_name FROM courses");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . $row['course_name'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <input type="text" name="unique_code" class="form-control" placeholder="Enter Unique Code" required>
            </div>
            <button type="submit" name="register" class="btn btn-success w-100">Register</button>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="../index.php">Login here</a></p>
    </div>

    <script>
        document.getElementById("role").addEventListener("change", function() {
            let courseField = document.getElementById("courseField");
            courseField.style.display = this.value === "student" ? "block" : "none";
        });
        document.getElementById("role").dispatchEvent(new Event("change"));
    </script>
</body>
</html>

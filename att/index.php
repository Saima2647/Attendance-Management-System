<?php
session_start();
include 'db/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // Sanitize and validate input
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Check if email exists in the database
        $query = "SELECT id, name, role, password, course_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Set session variables
                $_SESSION['id'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['course_id'] = $row['course_id']; // ✅ Store course ID for students
                
                // If the user is a teacher, fetch their assigned courses
                if ($row['role'] == 'teacher') {
                    $teacher_id = $row['id'];
                    
                    $teacherQuery = "SELECT GROUP_CONCAT(course_id) AS courses FROM teacher_subjects WHERE teacher_id = ?";
                    $teacherStmt = $conn->prepare($teacherQuery);
                    $teacherStmt->bind_param("i", $teacher_id);
                    $teacherStmt->execute();
                    $teacherResult = $teacherStmt->get_result();
                    $teacherRow = $teacherResult->fetch_assoc();
                    
                    $_SESSION['teacher_courses'] = $teacherRow['courses'] ?? ''; // Store assigned courses as a comma-separated string
                }

                // Redirect based on role
                if ($row['role'] == 'student') {
                    header("Location: dashboard/student_dashboard.php");
                    exit();
                } elseif ($row['role'] == 'teacher') {
                    header("Location: dashboard/teacher_dashboard.php");
                    exit();
                } elseif ($row['role'] == 'admin') { // ✅ Admin Redirect
                    header("Location: admin/admin_dashboard.php");
                    exit();
                } else {
                    $error = "Invalid user role";
                }
            } else {
                $error = "Incorrect password";
            }
        } else {
            $error = "No user found with this email";
        }

        $stmt->close();
    } else {
        $error = "All fields are required";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/styles.css">
</head>
<body>
    <div class="auth-container">
        <h3 class="text-center">Login</h3>
        <?php if (isset($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3">Don't have an account? <a href="auth/register.php">Register here</a></p>
    </div>
</body>
</html>

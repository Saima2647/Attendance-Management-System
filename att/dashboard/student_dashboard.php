<?php
session_start();
include '../db/db_connect.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

$user_name = htmlspecialchars($_SESSION['name']);
$user_id = $_SESSION['id'];
$course_id = $_SESSION['course_id']; // ✅ Fetch course_id from session

// Fetch subjects for the student from student_subjects table
$subjects = [];
$subjectQuery = "SELECT s.subject_name 
                 FROM student_subjects ss
                 JOIN subjects s ON ss.subject_id = s.id
                 WHERE ss.student_id = ?";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row['subject_name'];
}
$stmt->close();

// Fetch scheduled classes for the student
$events = [];
$scheduleQuery = "SELECT cs.id, cs.class_title, cs.class_date, cs.start_time, cs.end_time, s.subject_name 
                  FROM class_schedule cs
                  JOIN subjects s ON cs.subject_id = s.id
                  JOIN student_subjects ss ON cs.subject_id = ss.subject_id
                  WHERE ss.student_id = ?";
$stmt = $conn->prepare($scheduleQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $events[] = [
        "id" => $row['id'],
        "title" => $row['class_title'] . " (" . $row['subject_name'] . ")",
        "start" => $row['class_date'] . "T" . $row['start_time'],
        "end" => $row['class_date'] . "T" . $row['end_time']
    ];
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../style/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
</head>
<body>
    <div class="sidebar">
        <h2>Welcome, <?php echo $user_name; ?></h2>
        <a href="../dashboard/student_subject.php">Subject Notes</a>
        <a href="../dashboard/upload_assignment.php">Upload Assignment</a>
        <a href="../dashboard/view_attendance.php">View Attendance</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
    
    <div class="content">
        <div id="calendar"></div> <!-- ✅ Fixed calendar element -->
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?php echo json_encode($events); ?>,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                height: 500, // Adjusted height for better visibility
                contentHeight: 450, 
                aspectRatio: 2,
                eventDidMount: function(info) {
                    console.log("Loaded event:", info.event.title);
                }
            });
            calendar.render();
        } else {
            console.error("Calendar element not found!");
        }
    });
    </script>
</body>
</html>

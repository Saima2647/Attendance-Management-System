<?php
session_start();
include '../db/db_connect.php';

// TEACHER DASHBOARD CODE
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit;
}

$user_name = htmlspecialchars($_SESSION['name']);
$teacher_id = $_SESSION['id'];

// Fetch teacher's scheduled classes using teacher_subjects
$sql = "SELECT cs.id,cs.class_title,cs.class_date, cs.start_time, cs.end_time, s.subject_name 
        FROM class_schedule cs
        JOIN subjects s ON cs.subject_id = s.id
        JOIN teacher_subjects ts ON cs.subject_id = ts.subject_id
        WHERE ts.teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch subjects taught by this teacher
$subjectQuery = "SELECT s.id, s.subject_name 
                 FROM subjects s
                 JOIN teacher_subjects ts ON s.id = ts.subject_id
                 WHERE ts.teacher_id = ?";
$subjectStmt = $conn->prepare($subjectQuery);
$subjectStmt->bind_param("i", $teacher_id);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../style/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="../style/calendar.js"></script>
</head>
<body>
    <div class="sidebar">
        <h2>Welcome, <?php echo $user_name; ?></h2>
        <a href="../dashboard/shedule_class.php">Schedule a Class</a>
        <a href="../dashboard/teachers_subject.php">Subject Deatils</a>
        <a href="../dashboard/view_attendance.php">View Attendance</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
    
    <div class="content">
        <div id="calendar" data-role="teacher"></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: '../dashboard/fetch_techer_events.php', // ✅ Corrected file name
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                height: 500,
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

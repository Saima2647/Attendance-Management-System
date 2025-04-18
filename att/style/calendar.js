document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var role = calendarEl.getAttribute('data-role'); // Check role (teacher/student)
        var eventsUrl = role === 'teacher' ? '../dashboard/fetch_techer_events.php' : '../dashboard/fetch_events.php';

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: {
                url: eventsUrl,
                failure: function() {
                    alert('⚠️ Failed to load class schedule. Please try again later.');
                }
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            height: "auto",
            contentHeight: "auto",
            eventDidMount: function(info) {
                console.log("Loaded event:", info.event.title);
            }
        });

        calendar.render();
    } else {
        console.error("❌ Calendar element not found!");
    }
});
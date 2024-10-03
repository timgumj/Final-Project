document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: 'load_events.php', // Backend script to load events
      dateClick: function(info) {
          // Handle date click (e.g., open booking form)
          alert('Date: ' + info.dateStr);
      },
      eventClick: function(info) {
          // Handle event click (e.g., open details or edit form)
          alert('Event: ' + info.event.title);
      }
  });

  calendar.render();
});

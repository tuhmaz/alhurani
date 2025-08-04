document.addEventListener('DOMContentLoaded', function() {
    const eventModal = document.getElementById('eventModal');
    const mainContent = document.querySelector('main, #main-content, .container, body > div.container');
  // Get CSRF token from meta tag
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  
    if (eventModal) {
      eventModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        if (button) {
          // Get event data from button attributes
          const eventTitle = button.getAttribute('data-title');
          const eventDescription = button.getAttribute('data-description');
          const eventDate = button.getAttribute('data-date');
          
          // Populate modal with event data
          document.getElementById('eventModalLabel').textContent = eventTitle || 'بدون عنوان';
          document.getElementById('eventDescription').textContent = eventDescription || 'لا يوجد وصف متاح';
          document.getElementById('eventDate').textContent = eventDate || 'غير محدد';
        }
      });
  
      eventModal.addEventListener('shown.bs.modal', function() {
        // Set inert on main content to prevent focus
        if (mainContent) {
          mainContent.setAttribute('inert', '');
          mainContent.setAttribute('aria-hidden', 'true');
        }
        
        // Focus on close button for accessibility
        const closeButton = eventModal.querySelector('.btn-close');
        if (closeButton) {
          closeButton.focus();
        }
      });
  
      eventModal.addEventListener('hidden.bs.modal', function() {
        // Remove inert from main content
        if (mainContent) {
          mainContent.removeAttribute('inert');
          mainContent.removeAttribute('aria-hidden');
        }
      });
    }
  
    // استمع لتغييرات قاعدة البيانات
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ar',
        events: {
          url: '/app-calendar-events',
          method: 'GET',
          failure: function(error) {
            console.error('Failed to fetch events:', error);
          }
        },
        eventDidMount: function(info) {
          console.log('Rendered event:', info.event);
        }
      });
      calendar.render();
    }
  });
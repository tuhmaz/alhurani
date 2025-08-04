'use strict';

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // تهيئة التقويم
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        direction: 'rtl',
        locale: 'ar',
        height: 800,
        headerToolbar: {
            start: 'prev,next today',
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        firstDay: 6, // السبت
        buttonText: {
            today: 'اليوم',
            month: 'شهر',
            week: 'أسبوع',
            day: 'يوم',
            list: 'قائمة'
        },
        events: function(info, successCallback, failureCallback) {
            const database = document.getElementById('select-database')?.value || 'mysql';

            fetch(`/dashboard/calendar-events?database=${database}&_=${new Date().getTime()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    console.log('Raw events data:', result.data);
                    successCallback(result.data);
                } else {
                    console.error('Failed to fetch events:', result.message);
                    failureCallback(new Error(result.message));
                }
            })
            .catch(error => {
                console.error('Error fetching events:', error);
                failureCallback(error);
            });
        },
        eventClick: function(info) {
          // تنسيق التاريخ بالعربية
          const eventDate = new Date(info.event.start);
          const dateOptions = {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric'
          };
          const formattedDate = new Intl.DateTimeFormat('ar-SA', dateOptions).format(eventDate);

          // تحسين استرجاع بيانات الحدث
          const title = info.event.title || 'حدث جديد';
          let description = '';
          
          // محاولة استرجاع الوصف من مصادر مختلفة
          if (info.event.extendedProps && info.event.extendedProps.description) {
              description = info.event.extendedProps.description;
          } else if (info.event.description) {
              description = info.event.description;
          } else {
              description = 'لا يوجد وصف';
          }

          // تنظيف وتحقق من الوصف
          description = description.trim() || 'لا يوجد وصف';

          // تحديث محتوى النافذة المنبثقة
          const modal = document.getElementById('eventModal');
          const modalTitle = modal.querySelector('.modal-title');
          const modalDescription = modal.querySelector('#eventDescription');
          const modalDate = modal.querySelector('#eventDate');
          const closeButton = modal.querySelector('.btn-close');
  
          // حفظ العنصر الذي كان عليه التركيز قبل فتح النافذة
          let previouslyFocusedElement = null;

          modal.addEventListener('show.bs.modal', function () {
            previouslyFocusedElement = document.activeElement;
          });

          modal.addEventListener('shown.bs.modal', function () {
            closeButton.focus();
          });

          modal.addEventListener('hidden.bs.modal', function () {
            if (previouslyFocusedElement) {
              previouslyFocusedElement.focus();
            }
          });

          modalTitle.textContent = title;
          modalDescription.textContent = description;
          modalDate.innerHTML = `<i class="ti ti-calendar me-1"></i>${formattedDate}`;

          // عرض النافذة المنبثقة
          const bootstrapModal = new bootstrap.Modal(modal);
          
          // تحسين إدارة التركيز
          modal.addEventListener('shown.bs.modal', function () {
              const closeButton = modal.querySelector('.btn-close');
              if (closeButton) {
                  closeButton.focus();
              }
          });

          modal.addEventListener('hidden.bs.modal', function () {
              // إعادة التركيز إلى العنصر الذي فتح النافذة المنبثقة
              const eventElement = info.el;
              if (eventElement) {
                  eventElement.focus();
              }
          });

          bootstrapModal.show();
      }
    });

    // تهيئة التقويم
    calendar.render();

    // استمع لتغييرات قاعدة البيانات
    document.getElementById('select-database')?.addEventListener('change', function() {
        calendar.refetchEvents();
    });

    // تحديث دوري للأحداث كل 30 ثانية
    setInterval(() => calendar.refetchEvents(), 30000);
});

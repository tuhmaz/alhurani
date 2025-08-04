/**
 * ملف الرسوم البيانية للتحليلات
 * يستخدم مكتبة ApexCharts لعرض بيانات الزوار والمستخدمين والدول
 */

"use strict";

// تهيئة الرسوم البيانية عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function () {
  // الحصول على بيانات التحليلات من الخادم
  fetch('/dashboard/analytics/visitors/data')
    .then(response => response.json())
    .then(data => {
      initVisitorsChart(data.visitor_stats);
      updateVisitorCards(data.visitor_stats);
      updateUserStats(data.user_stats);
      initCountryChart(data.country_stats);
      updateActiveVisitorsTable(data.visitor_stats.active_visitors);
    })
    .catch(error => {
      console.error('خطأ في جلب بيانات التحليلات:', error);
    });
});

/**
 * تهيئة الرسم البياني للزوار
 */
function initVisitorsChart(visitorStats) {
  if (!document.getElementById('visitorsChart')) return;

  const hours = [];
  const visitors = [];
  
  // تنسيق البيانات للرسم البياني
  visitorStats.history.forEach(item => {
    hours.push(item.hour);
    visitors.push(item.count);
  });

  const options = {
    chart: {
      height: 350,
      type: 'area',
      toolbar: {
        show: false
      },
      fontFamily: 'Cairo, sans-serif',
      dir: 'rtl'
    },
    dataLabels: {
      enabled: false
    },
    stroke: {
      curve: 'smooth',
      width: 2
    },
    series: [{
      name: 'الزوار',
      data: visitors
    }],
    xaxis: {
      categories: hours,
      labels: {
        style: {
          fontFamily: 'Cairo, sans-serif'
        }
      }
    },
    yaxis: {
      labels: {
        style: {
          fontFamily: 'Cairo, sans-serif'
        }
      }
    },
    tooltip: {
      x: {
        format: 'HH:mm'
      }
    },
    colors: ['#7367F0'],
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.7,
        opacityTo: 0.2,
        stops: [0, 90, 100]
      }
    },
    grid: {
      borderColor: '#f1f1f1',
      padding: {
        left: 10,
        right: 10
      }
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right',
      fontFamily: 'Cairo, sans-serif'
    }
  };

  const chart = new ApexCharts(document.getElementById('visitorsChart'), options);
  chart.render();
}

/**
 * تهيئة الرسم البياني للدول
 */
function initCountryChart(countryStats) {
  if (!document.getElementById('countryChart')) return;

  const countries = [];
  const counts = [];
  
  // تنسيق البيانات للرسم البياني
  countryStats.forEach(item => {
    countries.push(item.country);
    counts.push(item.count);
  });

  const options = {
    chart: {
      type: 'bar',
      height: 350,
      toolbar: {
        show: false
      },
      fontFamily: 'Cairo, sans-serif',
      dir: 'rtl'
    },
    plotOptions: {
      bar: {
        horizontal: true,
        distributed: true,
        barHeight: '70%',
        dataLabels: {
          position: 'top'
        }
      }
    },
    dataLabels: {
      enabled: true,
      offsetX: -6,
      style: {
        fontSize: '12px',
        fontWeight: 400,
        fontFamily: 'Cairo, sans-serif'
      }
    },
    series: [{
      name: 'الزوار',
      data: counts
    }],
    xaxis: {
      categories: countries,
      labels: {
        style: {
          fontFamily: 'Cairo, sans-serif'
        }
      }
    },
    yaxis: {
      labels: {
        style: {
          fontFamily: 'Cairo, sans-serif'
        }
      }
    },
    colors: ['#7367F0', '#00CFE8', '#28C76F', '#FF9F43', '#EA5455', '#4B4B4B', '#A8A8A8', '#FFCB00', '#6610F2', '#1E1E1E'],
    legend: {
      show: false
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val + ' زائر';
        }
      }
    }
  };

  const chart = new ApexCharts(document.getElementById('countryChart'), options);
  chart.render();
}

/**
 * تحديث بطاقات إحصائيات الزوار
 */
function updateVisitorCards(visitorStats) {
  // تحديث عدد الزوار الحاليين
  const currentVisitorsElement = document.getElementById('currentVisitors');
  if (currentVisitorsElement) {
    currentVisitorsElement.textContent = visitorStats.current;
  }

  // تحديث إجمالي الزوار اليوم
  const totalTodayElement = document.getElementById('totalToday');
  if (totalTodayElement) {
    totalTodayElement.textContent = visitorStats.total_today;
  }

  // تحديث نسبة التغيير
  const changeElement = document.getElementById('visitorChange');
  if (changeElement) {
    const changeValue = visitorStats.change;
    const isPositive = changeValue >= 0;
    
    changeElement.textContent = `${isPositive ? '+' : ''}${changeValue}%`;
    changeElement.classList.remove('text-success', 'text-danger');
    changeElement.classList.add(isPositive ? 'text-success' : 'text-danger');
    
    const changeIcon = document.getElementById('visitorChangeIcon');
    if (changeIcon) {
      changeIcon.classList.remove('fa-arrow-up', 'fa-arrow-down');
      changeIcon.classList.add(isPositive ? 'fa-arrow-up' : 'fa-arrow-down');
    }
  }
}

/**
 * تحديث إحصائيات المستخدمين
 */
function updateUserStats(userStats) {
  // تحديث إجمالي المستخدمين
  const totalUsersElement = document.getElementById('totalUsers');
  if (totalUsersElement) {
    totalUsersElement.textContent = userStats.total;
  }

  // تحديث المستخدمين النشطين
  const activeUsersElement = document.getElementById('activeUsers');
  if (activeUsersElement) {
    activeUsersElement.textContent = userStats.active;
  }

  // تحديث المستخدمين الجدد اليوم
  const newUsersElement = document.getElementById('newUsers');
  if (newUsersElement) {
    newUsersElement.textContent = userStats.new_today;
  }
}

/**
 * تحديث جدول الزوار النشطين
 */
function updateActiveVisitorsTable(activeVisitors) {
  const tableBody = document.getElementById('activeVisitorsTable');
  if (!tableBody) return;

  // مسح الجدول الحالي
  tableBody.innerHTML = '';

  // إضافة صفوف جديدة للجدول
  activeVisitors.forEach(visitor => {
    const row = document.createElement('tr');
    
    // إنشاء خلايا الجدول
    const ipCell = document.createElement('td');
    ipCell.className = 'visitor-ip';
    ipCell.textContent = visitor.ip;
    
    const countryCell = document.createElement('td');
    countryCell.className = 'visitor-location';
    countryCell.textContent = visitor.country ? `${visitor.country}${visitor.city ? ` - ${visitor.city}` : ''}` : 'غير معروف';
    
    const browserCell = document.createElement('td');
    browserCell.className = 'visitor-browser';
    browserCell.textContent = visitor.browser ? `${visitor.browser}${visitor.os ? ` / ${visitor.os}` : ''}` : 'غير معروف';
    
    const timeCell = document.createElement('td');
    timeCell.textContent = formatDateTime(visitor.last_active);
    
    // إضافة الخلايا إلى الصف
    row.appendChild(ipCell);
    row.appendChild(countryCell);
    row.appendChild(browserCell);
    row.appendChild(timeCell);
    
    // إضافة الصف إلى الجدول
    tableBody.appendChild(row);
  });
  
  // إذا لم يكن هناك زوار نشطين، عرض رسالة
  if (activeVisitors.length === 0) {
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 4;
    cell.className = 'text-center';
    cell.textContent = 'لا يوجد زوار نشطين حالياً';
    row.appendChild(cell);
    tableBody.appendChild(row);
  }
}

/**
 * تنسيق التاريخ والوقت
 */
function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  
  // تنسيق الوقت والتاريخ بالعربية
  const options = {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
    day: 'numeric',
    month: 'short'
  };
  
  return date.toLocaleString('ar-SA', options);
}

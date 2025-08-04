@extends('layouts.contentNavbarLayout')

@section('title', 'لوحة المراقبة')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/leaflet/leaflet.scss',
  'resources/assets/vendor/libs/swiper/swiper.scss'
])
@endsection

@section('page-style')
<style>
  /* ===== تصميم عام ===== */
  .monitoring-card {
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    overflow: hidden;
    height: 100%;
    border: none;
    box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1);
  }
  
  .monitoring-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(115, 103, 240, 0.15);
  }
  
  .monitoring-card .card-header {
    background: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
  }
  
  .monitoring-card .card-title {
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  
  .monitoring-card .card-body {
    padding: 1.5rem;
  }
  
  /* ===== الإحصائيات والأرقام ===== */
  .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    line-height: 1.2;
  }
  
  .stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: 500;
  }
  
  .stat-change {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    margin-right: 0.5rem;
    font-weight: 600;
  }
  
  .stat-change.positive {
    background-color: rgba(40, 199, 111, 0.1);
    color: #28c76f;
  }
  
  .stat-change.negative {
    background-color: rgba(234, 84, 85, 0.1);
    color: #ea5455;
  }
  
  /* ===== زر التحديث ===== */
  .refresh-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: rgba(115, 103, 240, 0.1);
    color: #7367f0;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  
  .refresh-btn:hover {
    background-color: #7367f0;
    color: white;
    transform: rotate(15deg);
  }
  
  .refresh-btn.loading i {
    animation: spin 1s linear infinite;
  }
  
  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  
  /* ===== حالة النظام ===== */
  .system-status {
    padding: 0.35rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.8rem;
    font-weight: 600;
  }
  
  .system-status.healthy {
    background-color: rgba(40, 199, 111, 0.1);
    color: #28c76f;
  }
  
  .system-status.warning {
    background-color: rgba(255, 159, 67, 0.1);
    color: #ff9f43;
  }
  
  .system-status.danger {
    background-color: rgba(234, 84, 85, 0.1);
    color: #ea5455;
  }
  
  /* ===== قائمة المستخدمين النشطين ===== */
  #active-users-list {
    max-height: 400px;
    overflow-y: auto;
  }
  
  #active-users-list::-webkit-scrollbar {
    width: 6px;
  }
  
  #active-users-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }
  
  #active-users-list::-webkit-scrollbar-thumb {
    background: #7367f0;
    border-radius: 10px;
  }
  
  /* ===== بطاقات الإحصائيات ===== */
  .stats-card {
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  }
  
  .stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
  }
  
  .avatar-stats {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 1.25rem;
  }
  
  /* ===== جدول الأخطاء ===== */
  .data-table th {
    font-weight: 600;
    white-space: nowrap;
    padding: 1rem;
  }
  
  .data-table td {
    vertical-align: middle;
    padding: 1rem;
  }
  
  .table-hover tbody tr:hover {
    background-color: rgba(115, 103, 240, 0.04);
  }
  
  /* ===== شريط التقدم ===== */
  .progress {
    overflow: hidden;
    height: 8px;
    background-color: #f5f5f5;
    border-radius: 10px;
  }
  
  .progress-bar {
    border-radius: 10px;
  }
  
  /* ===== توافق الجوال ===== */
  @media (max-width: 767.98px) {
    .monitoring-card .card-header,
    .monitoring-card .card-body {
      padding: 1rem;
    }
    
    .stat-value {
      font-size: 1.5rem;
    }
    
    .avatar-stats {
      width: 38px;
      height: 38px;
      font-size: 1rem;
    }
    
    .refresh-btn {
      width: 32px;
      height: 32px;
    }
    
    .data-table th,
    .data-table td {
      padding: 0.75rem;
    }
    
    .data-table th {
      font-size: 0.85rem;
    }
    
    .data-table td {
      font-size: 0.9rem;
    }
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/leaflet/leaflet.js',
  'resources/assets/vendor/libs/jquery/jquery.js',
  'resources/assets/vendor/libs/swiper/swiper.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages/monitoring.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- عنوان الصفحة مع الإحصائيات العامة -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <div>
          <h2 class="fw-bold mb-1">لوحة المراقبة</h2>
          <p class="text-muted mb-0">مرحباً بك في لوحة مراقبة النظام، آخر تحديث: <span id="last-update-time">{{ now()->format('Y-m-d H:i:s') }}</span></p>
        </div>
        <div class="d-flex align-items-center mt-3 mt-md-0">
          <span class="system-status healthy me-3" id="system-status">
            <i class="ti ti-check-circle me-1"></i>
            النظام يعمل بشكل طبيعي
          </span>
          <button class="btn btn-primary btn-sm" id="refresh-all-stats">
            <i class="ti ti-refresh me-1"></i>
            تحديث الكل
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- إحصائيات النظام -->
  <div class="row g-3">
    <div class="col-sm-6 col-lg-3">
      <div class="card stats-card h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-primary-subtle me-3">
              <i class="ti ti-cpu text-primary"></i>
            </div>
            <div>
              <p class="stat-label mb-1">وحدة المعالجة</p>
              <div class="d-flex align-items-center">
                <h3 class="stat-value mb-0" id="cpu-usage">0%</h3>
                <div class="progress ms-2 flex-grow-1" style="height: 8px; width: 60px;">
                  <div class="progress-bar bg-primary" id="cpu-usage-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card stats-card h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-success-subtle me-3">
              <i class="ti ti-device-laptop text-success"></i>
            </div>
            <div>
              <p class="stat-label mb-1">الذاكرة</p>
              <div class="d-flex align-items-center">
                <h3 class="stat-value mb-0" id="memory-usage">0%</h3>
                <div class="progress ms-2 flex-grow-1" style="height: 8px; width: 60px;">
                  <div class="progress-bar bg-success" id="memory-usage-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card stats-card h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-warning-subtle me-3">
              <i class="ti ti-database text-warning"></i>
            </div>
            <div>
              <p class="stat-label mb-1">القرص</p>
              <div class="d-flex align-items-center">
                <h3 class="stat-value mb-0" id="disk-usage">0%</h3>
                <div class="progress ms-2 flex-grow-1" style="height: 8px; width: 60px;">
                  <div class="progress-bar bg-warning" id="disk-usage-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card stats-card h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-info-subtle me-3">
              <i class="ti ti-clock text-info"></i>
            </div>
            <div>
              <p class="stat-label mb-1">وقت التشغيل</p>
              <h3 class="stat-value mb-0" id="uptime">0 ساعة</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- إحصائيات الزوار والمستخدمين -->
  <div class="row g-3 mt-4">
    <div class="col-md-6 col-xl-3">
      <div class="card bg-primary text-white stats-card">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-white me-3">
              <i class="ti ti-users text-primary"></i>
            </div>
            <div>
              <p class="mb-1 text-white-50">المستخدمين المتصلين</p>
              <h3 class="mb-0 text-white" id="online-users-count">0</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card bg-info text-white stats-card">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-white me-3">
              <i class="ti ti-clock text-info"></i>
            </div>
            <div>
              <p class="mb-1 text-white-50">نشطين آخر 5 دقائق</p>
              <h3 class="mb-0 text-white" id="active-users-count">0</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card bg-success text-white stats-card">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-white me-3">
              <i class="ti ti-world text-success"></i>
            </div>
            <div>
              <p class="mb-1 text-white-50">إجمالي الزوار</p>
              <h3 class="mb-0 text-white" id="total-visitors-count">0</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card bg-warning text-white stats-card">
        <div class="card-body p-3">
          <div class="d-flex align-items-center">
            <div class="avatar-stats bg-white me-3">
              <i class="ti ti-calendar text-warning"></i>
            </div>
            <div>
              <p class="mb-1 text-white-50">زيارات اليوم</p>
              <h3 class="mb-0 text-white" id="today-visits-count">0</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- إحصائيات الزوار -->
  <div class="row g-3 mt-4">
    <div class="col-12 col-xl-6">
      <div class="card monitoring-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-chart-bar me-2 text-primary"></i>
            إحصائيات الزوار
          </h5>
          <div class="refresh-btn" id="refresh-visitors">
            <i class="ti ti-refresh"></i>
          </div>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-3">
            <div>
              <p class="mb-1">الزوار الحاليون</p>
              <h3 class="stat-value mb-0" id="current-visitors">0</h3>
            </div>
            <div>
              <p class="mb-1">إجمالي الزيارات اليوم</p>
              <h3 class="stat-value mb-0" id="page-views">0</h3>
            </div>
          </div>
          <div id="visitors-chart"></div>
        </div>
      </div>
    </div>
    
    <!-- خريطة الزوار -->
    <div class="col-12 col-xl-6">
      <div class="card monitoring-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-map me-2 text-info"></i>
            خريطة الزوار
          </h5>
          <div class="refresh-btn" id="refresh-visitor-map">
            <i class="ti ti-refresh"></i>
          </div>
        </div>
        <div class="card-body">
          <div id="visitors-map" style="height: 300px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- المستخدمين النشطين حالياً وسجل الأخطاء -->
  <div class="row g-3 mt-4">
    <div class="col-12 col-xl-6">
      <div class="card monitoring-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-users me-2 text-primary"></i>
            المستخدمين النشطين حالياً
          </h5>
          <div class="refresh-btn" id="refresh-active-users">
            <i class="ti ti-refresh"></i>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="alert alert-info rounded-0 mb-0 d-flex align-items-center px-3 py-2">
            <i class="ti ti-info-circle me-2"></i>
            <small>يتم تحديث القائمة كل دقيقة تلقائياً</small>
          </div>
          <ul class="list-group list-group-flush" id="active-users-list">
            <!-- سيتم ملء هذه القائمة ديناميكيًا -->
          </ul>
        </div>
      </div>
    </div>
    
    <!-- سجل الأخطاء -->
    <div class="col-12 col-xl-6">
      <div class="card monitoring-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-alert-triangle me-2 text-danger"></i>
            سجل الأخطاء
          </h5>
          <div class="refresh-btn" id="refresh-errors">
            <i class="ti ti-refresh"></i>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="alert alert-info rounded-0 mb-0 d-flex align-items-center px-3 py-2">
            <i class="ti ti-info-circle me-2"></i>
            <small>يمكنك حذف خطأ معين بالضغط على زر الحذف بجانبه</small>
          </div>
          <div class="table-responsive">
            <table class="table table-hover data-table mb-0">
              <thead class="table-light">
                <tr>
                  <th>الوقت</th>
                  <th>المستوى</th>
                  <th>الرسالة</th>
                  <th>المسار</th>
                  <th>المستخدم</th>
                  <th>الإجراءات</th>
                </tr>
              </thead>
              <tbody id="error-logs-table">
                <!-- سيتم ملء هذا الجدول ديناميكيًا -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- الدول الأكثر زيارة ومؤشرات الأداء -->
  <div class="row g-3 mt-4">
    <!-- الدول الأكثر زيارة -->
    <div class="col-12 col-xl-6">
      <div class="card monitoring-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-map-pin me-2 text-warning"></i>
            الدول الأكثر زيارة
          </h5>
          <div class="refresh-btn" id="refresh-countries">
            <i class="ti ti-refresh"></i>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover data-table mb-0">
              <thead class="table-light">
                <tr>
                  <th>الدولة</th>
                  <th>عدد الزوار</th>
                  <th>النسبة</th>
                </tr>
              </thead>
              <tbody id="countries-table">
                <!-- سيتم ملء هذا الجدول ديناميكيًا -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    
    <!-- مؤشرات الأداء -->
    <div class="col-12 col-xl-6">
      <div class="card monitoring-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-gauge me-2 text-success"></i>
            مؤشرات الأداء
          </h5>
          <div class="refresh-btn" id="refresh-performance">
            <i class="ti ti-refresh"></i>
          </div>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-3">
            <div>
              <p class="mb-1">متوسط وقت الاستجابة</p>
              <h3 class="stat-value mb-0" id="avg-response-time">0 ثانية</h3>
            </div>
            <div>
              <p class="mb-1">الطلبات في الدقيقة</p>
              <h3 class="stat-value mb-0" id="requests-per-minute">0</h3>
            </div>
          </div>
          <div id="performance-chart"></div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- سجل الأحداث -->
  <div class="row g-3 mt-4">
    <div class="col-12">
      <div class="card monitoring-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">
            <i class="ti ti-activity me-2 text-info"></i>
            سجل الأحداث
          </h5>
          <div class="refresh-btn" id="refresh-events">
            <i class="ti ti-refresh"></i>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="alert alert-info rounded-0 mb-0 d-flex align-items-center px-3 py-2">
            <i class="ti ti-info-circle me-2"></i>
            <small>يعرض هذا القسم الأحداث الأمنية والأنشطة الأخيرة في النظام</small>
          </div>
          <div class="timeline-container p-3">
            <!-- سيتم ملء هذا القسم ديناميكيًا -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

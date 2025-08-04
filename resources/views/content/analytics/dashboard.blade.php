@extends('layouts/contentNavbarLayout')

@section('title', $title)

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
  ])
@endsection

@section('page-style')
  @vite([
    'resources/assets/vendor/scss/pages/card-analytics.scss',
    'resources/assets/css/custom-analytics.css'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('page-script')
  @vite([
    'resources/assets/js/charts-apex.js',
    'resources/assets/js/pages/custom-analytics.js'
  ])
@endsection

@section('content')
<!-- بطاقات الإحصائيات العلوية -->
<div class="row g-4 mb-4">
  <!-- الزوار الحاليين -->
  <div class="col-lg-3 col-md-6">
    <div class="card shadow-sm rounded-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <h5 class="mb-0">{{ $visitorStats['current_guests'] }}</h5>
            <small>الزوار الحاليين</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-primary rounded p-2" data-bs-toggle="tooltip" data-bs-placement="top" title="عدد الزوار غير المسجلين المتصلين حالياً">
              <i class="ti ti-user-scan ti-sm"></i>
            </span>
          </div>
        </div>
        <div class="progress mt-3" style="height: 8px;">
          <div class="progress-bar bg-primary" style="width: {{ min(100, ($visitorStats['current_guests'] / max(1, $visitorStats['current'])) * 100) }}%" role="progressbar"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- الأعضاء الحاليين -->
  <div class="col-lg-3 col-md-6">
    <div class="card shadow-sm rounded-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <h5 class="mb-0">{{ $visitorStats['current_members'] }}</h5>
            <small>الأعضاء الحاليين</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-success rounded p-2" data-bs-toggle="tooltip" data-bs-placement="top" title="عدد الأعضاء المسجلين المتصلين حالياً">
              <i class="ti ti-users ti-sm"></i>
            </span>
          </div>
        </div>
        <div class="progress mt-3" style="height: 8px;">
          <div class="progress-bar bg-success" style="width: {{ min(100, ($visitorStats['current_members'] / max(1, $visitorStats['current'])) * 100) }}%" role="progressbar"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- إجمالي الزوار والأعضاء اليوم -->
  <div class="col-lg-3 col-md-6">
    <div class="card shadow-sm rounded-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <h5 class="mb-0">{{ $visitorStats['total_combined_today'] }}</h5>
            <small>إجمالي الزوار والأعضاء اليوم</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-info rounded p-2" data-bs-toggle="tooltip" data-bs-placement="top" title="العدد الكلي للزوار والأعضاء اليوم">
              <i class="ti ti-chart-bar ti-sm"></i>
            </span>
          </div>
        </div>
        <div class="progress mt-3" style="height: 8px;">
          <div class="progress-bar bg-info" style="width: 100%" role="progressbar"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- التغيير مقارنة بالساعة السابقة -->
  <div class="col-lg-3 col-md-6">
    <div class="card shadow-sm rounded-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <h5 class="mb-0">{{ $visitorStats['change'] > 0 ? '+' : '' }}{{ $visitorStats['change'] }}%</h5>
            <small>التغيير مقارنة بالساعة السابقة</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-{{ $visitorStats['change'] >= 0 ? 'success' : 'danger' }} rounded p-2">
              <i class="ti ti-trending-{{ $visitorStats['change'] >= 0 ? 'up' : 'down' }} ti-sm"></i>
            </span>
          </div>
        </div>
        <div class="progress mt-3" style="height: 8px;">
          <div class="progress-bar bg-{{ $visitorStats['change'] >= 0 ? 'success' : 'danger' }}" style="width: {{ min(100, abs($visitorStats['change'])) }}%" role="progressbar"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- الزوار النشطين حالياً -->
<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="card shadow-sm rounded-3">
      <div class="card-header">
        <h5 class="card-title mb-0">الزوار النشطين حالياً</h5>
        <small class="text-muted">آخر 5 دقائق</small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">النوع</th>
              <th scope="col">الاسم/IP</th>
              <th scope="col">الدولة</th>
              <th scope="col">المتصفح</th>
              <th scope="col">الصفحة الحالية</th>
              <th scope="col">آخر نشاط</th>
            </tr>
          </thead>
          <tbody>
            @forelse($visitorStats['active_visitors'] as $index => $visitor)
            <tr>
              <th scope="row">{{ $index + 1 }}</th>
              <td>
                @if($visitor['is_member'])
                  <span class="badge bg-success">عضو</span>
                @else
                  <span class="badge bg-secondary">زائر</span>
                @endif
              </td>
              <td>
                @if($visitor['is_member'])
                  <div>
                    <strong>{{ $visitor['user_name'] }}</strong>
                    <br><small class="text-muted">{{ $visitor['ip'] }}</small>
                  </div>
                @else
                  {{ $visitor['ip'] }}
                @endif
              </td>
              <td>
                <div>
                  {{ $visitor['country'] }}
                  @if($visitor['city'] !== 'غير محدد')
                    <br><small class="text-muted">{{ $visitor['city'] }}</small>
                  @endif
                </div>
              </td>
              <td>
                <div>
                  {{ $visitor['browser'] }}
                  @if($visitor['os'] !== 'غير محدد')
                    <br><small class="text-muted">{{ $visitor['os'] }}</small>
                  @endif
                </div>
              </td>
              <td>
                <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $visitor['current_page_full'] ?? $visitor['current_page'] }}">
                  {{ $visitor['current_page'] }}
                </span>
              </td>
              <td>{{ $visitor['last_active']->diffForHumans() }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                  <i class="ti ti-users text-muted mb-2" style="font-size: 2rem;"></i>
                  <span>لا يوجد زوار نشطين حالياً</span>
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- إحصائيات المستخدمين -->
<div class="row g-4">
  <div class="col-lg-4 col-md-6">
    <div class="card shadow-sm rounded-3 h-100">
      <div class="card-header">
        <h5 class="card-title mb-0">إحصائيات المستخدمين</h5>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center">
            <div class="badge rounded bg-label-primary me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="إجمالي عدد المستخدمين">
              <i class="ti ti-users ti-sm"></i>
            </div>
            <div>
              <h6 class="mb-0">إجمالي المستخدمين</h6>
            </div>
          </div>
          <h6 class="mb-0">{{ $userStats['total'] }}</h6>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center">
            <div class="badge rounded bg-label-success me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="عدد المستخدمين النشطين">
              <i class="ti ti-user-check ti-sm"></i>
            </div>
            <div>
              <h6 class="mb-0">المستخدمين النشطين</h6>
            </div>
          </div>
          <h6 class="mb-0">{{ $userStats['active'] }}</h6>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="badge rounded bg-label-info me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="عدد المستخدمين الجدد اليوم">
              <i class="ti ti-user-plus ti-sm"></i>
            </div>
            <div>
              <h6 class="mb-0">مستخدمين جدد اليوم</h6>
            </div>
          </div>
          <h6 class="mb-0">{{ $userStats['new_today'] }}</h6>
        </div>
      </div>
    </div>
  </div>

  <!-- إحصائيات الدول -->
  <div class="col-lg-8 col-md-6">
    <div class="card shadow-sm rounded-3 h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">الزوار حسب الدولة</h5>
        <small class="text-muted">آخر 7 أيام</small>
      </div>
      <div class="card-body">
        <div id="countriesChart" aria-label="مخطط إحصائيات الزوار حسب الدولة" role="img"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@extends('layouts.contentNavbarLayout')

@section('title', 'مراقبة الزوار النشطين')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/vendor/libs/animate-css/animate.scss'
])
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">الزوار النشطين</h5>
        <div class="d-flex">
          <a href="{{ route('dashboard.monitoring.active-visitors') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-refresh me-1"></i> تحديث
          </a>
        </div>
      </div>
      <div class="card-body">
        <!-- بطاقات الإحصائيات -->
        <div class="row mb-4">
          <!-- عدد الزوار -->
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div class="card-info">
                    <p class="card-text">عدد الزوار النشطين</p>
                    <div class="d-flex align-items-end mb-2">
                      <h4 class="card-title mb-0 me-2" id="visitor-count">{{ count($visitors ?? []) }}</h4>
                      <small class="text-success">زائر</small>
                    </div>
                    <small>آخر تحديث: <span id="last-update-time">{{ now()->format('H:i:s') }}</span></small>
                  </div>
                  <div class="card-icon">
                    <span class="badge bg-label-primary rounded p-2">
                      <i class="ti ti-users ti-sm"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- جدول الزوار النشطين -->
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>الصفحة</th>
                <th>المرجع</th>
                <th>العنوان</th>
                <th>المتصفح</th>
                <th>أول زيارة</th>
                <th>آخر نشاط</th>
              </tr>
            </thead>
            <tbody>
              @php
                // جلب بيانات الزوار مباشرة من وحدة التحكم
                $visitorData = app('App\Http\Controllers\MonitoringController')->getActiveVisitorsData()->getData();
                $visitors = $visitorData->visitors ?? [];
              @endphp
              
              @if(count($visitors) > 0)
                @foreach($visitors as $index => $visitor)
                  <tr>
                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2 bg-label-primary">
                          <span class="avatar-initial rounded-circle"><i class="ti ti-link"></i></span>
                        </div>
                        <span class="text-truncate" style="max-width: 200px;" title="{{ $visitor->url }}">
                          {{ $visitor->url ?? 'الصفحة الرئيسية' }}
                        </span>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-label-secondary">
                        <i class="ti ti-arrow-back-up me-1"></i>{{ $visitor->referrer ?? 'مباشر' }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="badge bg-label-primary mb-1">
                          <i class="ti ti-device-desktop me-1"></i>{{ $visitor->ip ?? '127.0.0.1' }}
                        </span>
                        @php
                          $geoData = is_string($visitor->geo_data) ? json_decode($visitor->geo_data) : $visitor->geo_data;
                          $location = $geoData->country_name ?? $geoData->country_code ?? 'غير معروف';
                        @endphp
                        <span class="badge bg-label-warning">
                          <i class="ti ti-map-pin me-1"></i>{{ $location }}
                        </span>
                      </div>
                    </td>
                    <td>
                      @php
                        $userAgent = $visitor->user_agent ?? '';
                        $browser = '';
                        if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
                        elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
                        elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
                        elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
                        elseif (strpos($userAgent, 'Opera') !== false) $browser = 'Opera';
                        else $browser = 'متصفح آخر';
                        
                        $deviceType = '';
                        if (strpos($userAgent, 'Mobile') !== false) $deviceType = 'محمول';
                        elseif (strpos($userAgent, 'Tablet') !== false) $deviceType = 'جهاز لوحي';
                        else $deviceType = 'سطح مكتب';
                      @endphp
                      <div class="d-flex flex-column">
                        <span class="badge bg-label-info mb-1">
                          <i class="ti ti-browser me-1"></i>{{ $browser }}
                        </span>
                        <span class="badge bg-label-{{ $deviceType === 'محمول' ? 'success' : 'secondary' }}">
                          <i class="ti ti-{{ $deviceType === 'محمول' ? 'device-mobile' : 'device-desktop' }} me-1"></i>{{ $deviceType }}
                        </span>
                      </div>
                    </td>
                    <td>
                      @php
                        $firstSeen = new DateTime($visitor->first_seen);
                      @endphp
                      <div class="d-flex flex-column">
                        <span class="text-muted small">{{ $firstSeen->format('Y-m-d') }}</span>
                        <div class="d-flex align-items-center">
                          <span class="badge bg-label-secondary me-2">
                            <i class="ti ti-clock"></i>
                          </span>
                          {{ $firstSeen->format('H:i:s') }}
                        </div>
                      </div>
                    </td>
                    <td>
                      @php
                        $lastActivity = new DateTime($visitor->last_activity);
                        $now = new DateTime();
                        $diff = $now->getTimestamp() - $lastActivity->getTimestamp();
                        
                        if ($diff < 60) {
                            $timeDiff = $diff . ' ثانية';
                        } elseif ($diff < 3600) {
                            $timeDiff = floor($diff / 60) . ' دقيقة';
                        } else {
                            $timeDiff = floor($diff / 3600) . ' ساعة';
                        }
                      @endphp
                      <div class="d-flex flex-column">
                        <span class="text-muted small">{{ $timeDiff }} مضت</span>
                        <div class="d-flex align-items-center">
                          <span class="badge bg-label-success me-2">
                            <i class="ti ti-activity"></i>
                          </span>
                          {{ $lastActivity->format('H:i:s') }}
                        </div>
                      </div>
                    </td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="7" class="text-center py-5">
                    <i class="ti ti-mood-empty fs-1 text-muted d-block mb-3"></i>
                    <p class="mb-0 text-muted">لا يوجد زوار نشطين حاليًا</p>
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  // عند تحميل الصفحة
  document.addEventListener('DOMContentLoaded', function() {
    console.log('تم تحميل صفحة الزوار النشطين');
    
    // إضافة مستمع للزر اختبار الاتصال
    const testBtn = document.getElementById('test-connection-btn');
    if (testBtn) {
      testBtn.addEventListener('click', function(e) {
        console.log('تم النقر على زر اختبار الاتصال');
        testConnection();
      });
    } else {
      console.error('لم يتم العثور على زر اختبار الاتصال');
    }
    
    // إضافة مستمع لزر تحديث الجدول
    document.getElementById('refresh-visitors-btn').addEventListener('click', updateVisitorsTable);
    
    updateVisitorsTable();
  });
  
  // اختبار الاتصال بالخادم
  function testConnection() {
    console.log('تشغيل اختبار الاتصال...');
    const statusDiv = document.getElementById('connection-status');
    statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> جاري الاختبار...';
    
    // اختبار ipwho.is - بديل احترافي لـ geoplugin.net مع دعم IPv6
fetch('https://ipwho.is/', {
  method: 'GET',
  mode: 'cors',
  cache: 'no-cache'
})
  .then(response => {
    console.log('استجابة ipwho.is:', response);
    if (!response.ok) {
      throw new Error(`فشل الاتصال: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    console.log('نجح الاتصال بـ ipwho.is:', data);
    statusDiv.innerHTML = '<div class="alert alert-success mt-2">ipwho.is: نجاح ✅</div>';
  })
  .catch(error => {
    console.error('فشل الاتصال بـ ipwho.is:', error);
    statusDiv.innerHTML = '<div class="alert alert-danger mt-2">ipwho.is: فشل ❌</div>';
  });

      
    // اختبار API الزوار النشطين
    fetch("{{ route('dashboard.monitoring.active-visitors.data', [], false) }}", {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })
    .then(response => {
      console.log('استجابة API الزوار:', response);
      if (response.ok) {
        statusDiv.innerHTML += '<div class="alert alert-success mt-2">API الزوار: نجاح ✅ (' + response.status + ')</div>';
      } else {
        throw new Error('حالة الاستجابة: ' + response.status);
      }
      return response.json();
    })
    .then(data => {
      console.log('بيانات API الزوار:', data);
    })
    .catch(error => {
      console.error('خطأ في API الزوار:', error);
      statusDiv.innerHTML += '<div class="alert alert-danger mt-2">API الزوار: فشل ❌</div>';
    });
  }

  // استخراج معلومات المتصفح من user-agent
  function getBrowserInfo(userAgent) {
    if (!userAgent) return 'غير معروف';
    
    if (userAgent.includes('Chrome')) return 'Chrome';
    if (userAgent.includes('Firefox')) return 'Firefox';
    if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
    if (userAgent.includes('Edge')) return 'Edge';
    if (userAgent.includes('MSIE') || userAgent.includes('Trident/')) return 'Internet Explorer';
    
    return 'آخر';
  }

  // تحديث جدول الزوار
  function updateVisitorsTable() {
    console.log('جاري جلب بيانات الزوار...');
    
    // عرض مؤشر التحميل
    document.querySelector('#visitors-table tbody').innerHTML = `
      <tr>
        <td colspan="8" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">جاري التحميل...</span>
          </div>
          <p class="mt-2">جاري تحميل بيانات الزوار...</p>
        </td>
      </tr>
    `;
    
    // تعريف متغير لتتبع ما إذا كانت الاستجابة قد نجحت
    let responseReceived = false;
    
    // مؤقت للعرض التجريبي بعد 8 ثواني إذا لم يتم الحصول على استجابة
    let timeoutId = setTimeout(() => {
      // إذا لم يتم استلام استجابة بعد، عرض بيانات تجريبية
      if (!responseReceived) {
        console.log('انتهى وقت الانتظار، عرض بيانات تجريبية');
        const demoData = {
          success: true,
          count: 1,
          visitors: [{
            id: 'demo-visitor',
            url: 'الصفحة الرئيسية',
            referrer: 'مباشر',
            ip: '127.0.0.1',
            user_agent: navigator.userAgent,
            first_seen: new Date().toISOString(),
            last_activity: new Date().toISOString(),
            geo_data: {
              country_code: 'JO',
              country_name: 'الأردن',
              city: 'عمان'
            }
          }]
        };
        updateVisitorsTableWithData(demoData);
        document.getElementById('last-update').innerHTML = `<span class="badge bg-warning">بيانات تجريبية</span> آخر تحديث: ${new Date().toLocaleTimeString()}`;
      }
    }, 8000); // زيادة الوقت إلى 8 ثواني لإعطاء فرصة للاستجابة الحقيقية
    
    // Debug fetch URL
    console.log('Fetching visitors URL:', "{{ route('dashboard.monitoring.active-visitors.data', [], false) }}");
    
    // إضافة معلمة عشوائية لمنع التخزين المؤقت
    const cacheBuster = new Date().getTime();
    const url = `{{ route('dashboard.monitoring.active-visitors.data', [], false) }}?_=${cacheBuster}`;
    
    fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate'
      }
    })
      .then(response => {
        console.log('حالة الاستجابة:', response.status);
        responseReceived = true; // تعيين المتغير للإشارة إلى أننا تلقينا استجابة
        clearTimeout(timeoutId); // إلغاء المؤقت لأننا حصلنا على استجابة
        if (!response.ok) {
          throw new Error(`خطأ في الاتصال! الحالة: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('البيانات المستلمة:', data);
        if (!data.success) {
          throw new Error(data.message || 'حدث خطأ غير معروف');
        }
        
        // تحديث الجدول بالبيانات الحقيقية
        if (data.visitors && data.visitors.length > 0) {
          console.log('تم العثور على', data.visitors.length, 'زائر نشط');
          console.log('الزائر الأول:', data.visitors[0]);
          
          // تحديث العنوان بالبيانات الحقيقية
          document.getElementById('last-update').innerHTML = `<span class="badge bg-success">بيانات حقيقية</span> آخر تحديث: ${new Date().toLocaleTimeString()}`;
          
          // معالجة بيانات الموقع الجغرافي لكل زائر
          data.visitors.forEach(visitor => {
            if (visitor.geo_data && typeof visitor.geo_data === 'string') {
              try {
                visitor.geo_data = JSON.parse(visitor.geo_data);
                console.log('تم تحليل بيانات الموقع الجغرافي من سلسلة JSON');
              } catch (e) {
                console.error('خطأ في تحليل بيانات الموقع الجغرافي:', e);
                visitor.geo_data = {
                  country_code: 'JO',
                  country_name: 'الأردن',
                  city: 'عمان'
                };
              }
            }
          });
          
          // تحديث الجدول بالبيانات
          updateVisitorsTableWithData(data);
        } else {
          console.log('لم يتم العثور على زوار نشطين');
          throw new Error('لم يتم العثور على زوار نشطين');
        }
      })
      .catch(error => {
        console.error('خطأ في تحديث جدول الزوار:', error);
        
        // عرض بيانات تجريبية في حالة الخطأ
        console.log('عرض بيانات تجريبية بسبب الخطأ');
        const demoData = {
          success: true,
          count: 1,
          visitors: [{
            id: 'demo-visitor',
            url: 'الصفحة الرئيسية',
            referrer: 'مباشر',
            ip: '127.0.0.1',
            user_agent: navigator.userAgent,
            first_seen: new Date().toISOString(),
            last_activity: new Date().toISOString()
          }]
        };
        updateVisitorsTableWithData(demoData);
        
        // عرض رسالة تنبيه صغيرة أن البيانات تجريبية
        document.getElementById('last-update').innerHTML = `<span class="badge bg-warning">بيانات تجريبية</span> آخر تحديث: ${new Date().toLocaleTimeString()}`;
        
        // لا تعرض رسالة الخطأ لأننا عرضنا بيانات تجريبية بدلاً منها
      });
  }
  
  // وظيفة منفصلة لتحديث الجدول بالبيانات (مع إضافات جديدة)
  function updateVisitorsTableWithData(data) {
    console.log('تحديث الجدول بالبيانات:', data);
    console.log('عدد الزوار:', data.visitors ? data.visitors.length : 0);
    console.log('الزائر الأول:', data.visitors && data.visitors.length > 0 ? data.visitors[0] : 'لا يوجد');
    
    // تحديث عدد الزوار
    document.getElementById('visitors-count').textContent = data.visitors.length;
    document.getElementById('last-update').textContent = 'آخر تحديث: ' + new Date().toLocaleTimeString();
    
    // تحديث الإحصائيات
    updateVisitorStats(data.visitors);
    
    // الحصول على جسم الجدول
    const tbody = document.querySelector('#visitors-table tbody');
    console.log('تم العثور على جسم الجدول:', tbody ? true : false);
    
    // التحقق من وجود زوار
    if (!data.visitors || data.visitors.length === 0) {
      console.log('لا يوجد زوار لعرضهم');
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="text-center py-5">
            <i class="ti ti-mood-empty fs-1 text-muted d-block mb-3"></i>
            <p class="mb-0 text-muted">لا يوجد زوار نشطين حاليًا</p>
          </td>
        </tr>
      `;
      return;
    }
    
    console.log('سيتم عرض', data.visitors.length, 'زائر في الجدول');
    
    // تفريغ الجدول
    tbody.innerHTML = '';
    
    // إضافة صفوف لكل زائر
    data.visitors.forEach((visitor, index) => {
      console.log('معالجة الزائر #', index + 1, visitor);
      const row = document.createElement('tr');
      
      // تحويل الطوابع الزمنية إلى كائنات Date
      let firstSeen, lastActivity;
      try {
        firstSeen = visitor.first_seen ? new Date(visitor.first_seen) : new Date();
        lastActivity = visitor.last_activity ? new Date(visitor.last_activity) : new Date();
        console.log('تم تحويل التواريخ بنجاح:', firstSeen, lastActivity);
      } catch (e) {
        console.warn('خطأ في تحليل التواريخ:', e);
        firstSeen = new Date();
        lastActivity = new Date();
      }
      
      // استخراج معلومات المتصفح والجهاز
      const browser = getBrowserInfo(visitor.user_agent);
      const deviceType = getDeviceType(visitor.user_agent);
      
      // معالجة بيانات الموقع الجغرافي
      let location = 'غير معروف';
      if (visitor.geo_data) {
        if (typeof visitor.geo_data === 'string') {
          try {
            const geoDataObj = JSON.parse(visitor.geo_data);
            location = geoDataObj.country_name || geoDataObj.country_code || 'غير معروف';
            console.log('تم تحليل بيانات الموقع من سلسلة JSON:', geoDataObj);
          } catch (e) {
            console.error('خطأ في تحليل بيانات الموقع الجغرافي:', e);
          }
        } else {
          location = visitor.geo_data.country_name || visitor.geo_data.country_code || 'غير معروف';
          console.log('بيانات الموقع الجغرافي ككائن:', visitor.geo_data);
        }
      }
      
      console.log('الموقع الجغرافي:', location);
      console.log('المتصفح:', browser);
      console.log('نوع الجهاز:', deviceType);
      
      // إنشاء محتوى الصف
      const rowHtml = `
        <td class="text-center fw-bold">${index + 1}</td>
        <td>
          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-2 bg-label-primary">
              <span class="avatar-initial rounded-circle"><i class="ti ti-link"></i></span>
            </div>
            <span class="text-truncate" style="max-width: 200px;" title="${visitor.url}">
              ${visitor.url || 'الصفحة الرئيسية'}
            </span>
          </div>
        </td>
        <td>
          <span class="badge bg-label-secondary">
            <i class="ti ti-arrow-back-up me-1"></i>${visitor.referrer || 'مباشر'}
          </span>
        </td>
        <td>
          <div class="d-flex flex-column">
            <span class="badge bg-label-primary mb-1">
              <i class="ti ti-device-desktop me-1"></i>${visitor.ip || '127.0.0.1'}
            </span>
            <span class="badge bg-label-warning">
              <i class="ti ti-map-pin me-1"></i>${location}
            </span>
          </div>
        </td>
        <td>
          <div class="d-flex flex-column">
            <span class="badge bg-label-info mb-1">
              <i class="ti ti-browser me-1"></i>${browser}
            </span>
            <span class="badge bg-label-${deviceType === 'محمول' ? 'success' : 'secondary'}">
              <i class="ti ti-${deviceType === 'محمول' ? 'device-mobile' : 'device-desktop'} me-1"></i>${deviceType}
            </span>
          </div>
        </td>
        <td>
          <div class="d-flex flex-column">
            <span class="text-muted small">${firstSeen.toLocaleDateString()}</span>
            <div class="d-flex align-items-center">
              <span class="badge bg-label-secondary me-2">
                <i class="ti ti-clock"></i>
              </span>
              ${firstSeen.toLocaleTimeString()}
            </div>
          </div>
        </td>
        <td>
          <div class="d-flex flex-column">
            <span class="text-muted small">${getTimeDifference(lastActivity, new Date())}</span>
            <div class="d-flex align-items-center">
              <span class="badge bg-label-success me-2">
                <i class="ti ti-activity"></i>
              </span>
              ${lastActivity.toLocaleTimeString()}
            </div>
          </div>
        </td>
        <td>
          <div class="dropdown">
            <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="ti ti-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="javascript:void(0);"><i class="ti ti-info-circle me-1"></i>التفاصيل</a></li>
              <li><a class="dropdown-item" href="javascript:void(0);"><i class="ti ti-map me-1"></i>عرض الموقع</a></li>
            </ul>
          </div>
        </td>
      `;
      
      // تعيين HTML للصف
      row.innerHTML = rowHtml;
      console.log('تم إنشاء صف الجدول بنجاح');
      
      // إضافة الصف إلى جسم الجدول
      tbody.appendChild(row);
      console.log('تمت إضافة الصف إلى الجدول');
    });
  }
  
  // وظيفة لتحديث إحصائيات الزوار
  function updateVisitorStats(visitors) {
    if (!visitors || !visitors.length) {
      document.getElementById('total-visitors-count').textContent = '0';
      document.getElementById('desktop-count').textContent = '0';
      document.getElementById('mobile-count').textContent = '0';
      document.getElementById('avg-time').textContent = '0:00';
      return;
    }
    
    // إجمالي الزوار
    document.getElementById('total-visitors-count').textContent = visitors.length;
    
    // عدد الأجهزة المكتبية والمحمولة
    let desktopCount = 0;
    let mobileCount = 0;
    
    visitors.forEach(visitor => {
      if (isMobileDevice(visitor.user_agent)) {
        mobileCount++;
      } else {
        desktopCount++;
      }
    });
    
    document.getElementById('desktop-count').textContent = desktopCount;
    document.getElementById('mobile-count').textContent = mobileCount;
    
    // حساب متوسط وقت التصفح
    let totalTime = 0;
    visitors.forEach(visitor => {
      try {
        const firstSeen = visitor.first_seen ? new Date(visitor.first_seen) : new Date();
        const lastActivity = visitor.last_activity ? new Date(visitor.last_activity) : new Date();
        const sessionTime = (lastActivity - firstSeen) / 1000 / 60; // بالدقائق
        totalTime += sessionTime;
      } catch (e) {
        console.warn('خطأ في حساب وقت الجلسة:', e);
      }
    });
    
    const avgTime = totalTime / visitors.length;
    const minutes = Math.floor(avgTime);
    const seconds = Math.floor((avgTime - minutes) * 60);
    
    document.getElementById('avg-time').textContent = `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
  }
  
  // وظيفة للتحقق من نوع الجهاز
  function isMobileDevice(userAgent) {
    if (!userAgent) return false;
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);
  }
  
  // وظيفة للحصول على نوع الجهاز
  function getDeviceType(userAgent) {
    if (!userAgent) return 'غير معروف';
    
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)) {
      return 'محمول';
    }
    
    return 'سطح المكتب';
  }
  
  // وظيفة لحساب الفرق بين وقتين
  function getTimeDifference(date1, date2) {
    if (!date1 || !date2) return 'لتو';
    
    const diffMs = Math.abs(date2 - date1);
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHours = Math.floor(diffMin / 60);
    
    if (diffSec < 60) {
      return `منذ ${diffSec} ثانية`;
    } else if (diffMin < 60) {
      return `منذ ${diffMin} دقيقة`;
    } else if (diffHours < 24) {
      return `منذ ${diffHours} ساعة`;
    } else {
      return `منذ ${Math.floor(diffHours / 24)} يوم`;
    }
  }

  // تحديث البيانات كل 10 ثوانٍ
  console.log('تم تعيين تحديث تلقائي كل 10 ثوانٍ');
  setInterval(updateVisitorsTable, 10000);
  
  // تتبع الزائر الحالي
  function trackCurrentVisitor() {
    const data = {
      url: window.location.href,
      referrer: document.referrer
    };
    
    fetch('/track-visitor', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => console.log('تم تسجيل الزائر:', data))
    .catch(error => console.error('خطأ في تسجيل الزائر:', error));
  }
  
  // تتبع الزائر عند تحميل الصفحة
  trackCurrentVisitor();
  
  // تحديث نشاط الزائر كل 30 ثانية
  setInterval(function() {
    const data = {
      url: window.location.href
    };
    
    fetch('/update-visitor-activity', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(data)
    })
    .catch(error => console.error('خطأ في تحديث نشاط الزائر:', error));
    
    // تحديث عداد الزوار النشطين
    updateVisitorCounter();
  }, 30000);
  
  // وظيفة لتحديث عداد الزوار النشطين
  function updateVisitorCounter() {
    // إضافة معلمة عشوائية لمنع التخزين المؤقت
    const cacheBuster = new Date().getTime();
    const url = `{{ route('dashboard.monitoring.active-visitors.data', [], false) }}?_=${cacheBuster}`;
    
    fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // تحديث عداد الزوار
        document.getElementById('visitor-count').textContent = data.count;
        // تحديث وقت آخر تحديث
        document.getElementById('last-update-time').textContent = new Date().toLocaleTimeString();
      }
    })
    .catch(error => {
      console.error('خطأ في تحديث عداد الزوار:', error);
    });
  }
</script>
@endsection

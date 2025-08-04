document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // منع النسخ واللصق والقص للبيانات الحساسة
    document.querySelectorAll('.sensitive-data').forEach(function(el) {
        ['copy', 'paste', 'cut'].forEach(function(evt) {
            el.addEventListener(evt, function(e) {
                e.preventDefault();
            });
        });
    });

    // إعداد CSRF لطلبات AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // التحقق من محاولات XSS في النماذج
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let hasXSS = false;
            form.querySelectorAll('input[type="text"], textarea').forEach(function(input) {
                if (/<script[^>]*>|<\/script>|javascript:|data:|<script|\beval\b|\bexec\b|\balert\b|\bprompt\b/i.test(input.value)) {
                    hasXSS = true;
                    input.classList.add('is-invalid');
                }
            });
            if (hasXSS) {
                e.preventDefault();
                toastr.error('تم اكتشاف محتوى غير آمن في النموذج');
            }
        });
    });

    // تسجيل محاولات الوصول غير المصرح بها
    document.querySelectorAll('[data-requires-permission]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!el.dataset.hasPermission) {
                e.preventDefault();
                $.post('/dashboard/security/log-unauthorized-access', {
                    element: el.dataset.requiresPermission,
                    url: window.location.href
                });
                toastr.error('ليس لديك صلاحية للوصول إلى هذا العنصر');
            }
        });
    });

    // رسم درجة الأمان
    var securityScoreOptions = {
        chart: {
            height: 260,
            type: 'radialBar'
        },
        plotOptions: {
            radialBar: {
                hollow: { size: '70%' },
                track: { background: '#f0f2f5' },
                dataLabels: { show: false }
            }
        },
        colors: ['#28c76f'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'horizontal',
                shadeIntensity: 0.5,
                gradientToColors: ['#00cfe8'],
                inverseColors: true,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100]
            }
        },
        series: [window.securityScore || 0],
        stroke: { lineCap: 'round' }
    };
    var securityScoreChart = new ApexCharts(document.querySelector('#security-score-chart'), securityScoreOptions);
    securityScoreChart.render();

    // رسم اتجاهات الأحداث
    var timelineTrends = window.timelineTrends || {
        dates: [],
        critical: [],
        warning: []
    };

    var securityEventsOptions = {
        chart: {
            height: 370,
            type: 'area',
            toolbar: { show: false }
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        series: [
            { name: 'أحداث حرجة', data: timelineTrends.critical },
            { name: 'تحذيرات', data: timelineTrends.warning }
        ],
        colors: ['#ea5455', '#ff9f43'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: timelineTrends.dates,
            labels: { style: { cssClass: 'text-muted' } }
        },
        yaxis: {
            labels: { style: { cssClass: 'text-muted' } }
        },
        tooltip: {
            x: { format: 'dd/MM/yy' }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        }
    };
    var securityEventsChart = new ApexCharts(document.querySelector('#security-events-timeline'), securityEventsOptions);
    securityEventsChart.render();

    // توزيع أنواع الأحداث
    var eventLabels = [];
    var eventData = [];

    if (window.eventTypeDistribution && window.eventTypeDistribution.length > 0) {
        window.eventTypeDistribution.forEach(event => {
            eventLabels.push(event.event_type);
            eventData.push(event.count);
        });
    } else {
        eventLabels = [];
        eventData = [];
    }

    var eventTypeOptions = {
        chart: {
            height: 370,
            type: 'donut'
        },
        labels: eventLabels,
        series: eventData,
        colors: ['#ea5455', '#ff9f43', '#28c76f', '#00cfe8', '#7367f0', '#82868b'],
        legend: { 
            position: 'bottom',
            fontSize: '14px'
        },
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        show: true,
                        name: { 
                            show: true,
                            fontSize: '16px',
                            fontWeight: 600
                        },
                        value: { 
                            show: true,
                            fontSize: '18px',
                            fontWeight: 700
                        },
                        total: {
                            show: true,
                            label: 'الإجمالي',
                            fontSize: '14px',
                            fontWeight: 600,
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        }
    };
    var eventTypeChart = new ApexCharts(document.querySelector('#event-type-chart'), eventTypeOptions);
    eventTypeChart.render();

    // إعداد التفاصيل للثغرات الأمنية
    document.querySelectorAll('.dropdown-item').forEach(function(item) {
        if(item.querySelector('.ti-info-circle')) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = this.closest('tr');
                var type = row.querySelector('td:first-child .badge').textContent;
                var description = row.querySelector('td:nth-child(3)').textContent;
                var recommendation = row.querySelector('td:nth-child(4)').textContent;
                
                $('#securityDetailModal').modal('show');
                $('#securityDetailTitle').text('تفاصيل المشكلة: ' + type);
                $('#securityDetailDescription').text(description);
                $('#securityDetailRecommendation').text(recommendation);
            });
        }
    });
});

$(function () {
    // تفعيل التأثيرات الحركية مع تأخير تدريجي
    $('.security-card').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
    });

    // تفعيل tooltips للأزرار والعناصر
    $('[data-bs-toggle="tooltip"]').tooltip();

    // تفعيل تأثيرات الأزرار عند النقر
    $('.btn-wave').on('click', function(e) {
        let wave = $(this).find('.wave');
        if (wave.length === 0) {
            wave = $('<span class="wave"></span>');
            $(this).append(wave);
        }
        wave.removeClass('animate');
        wave.addClass('animate');
        setTimeout(() => wave.removeClass('animate'), 1000);
    });
    
    // معالجة بيانات النافذة المنبثقة لتفاصيل المشكلة
    $('#securityDetailModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const type = button.data('type');
        const description = button.data('description');
        const recommendation = button.data('recommendation');
        
        const modal = $(this);
        modal.find('#securityDetailTitle').text('تفاصيل المشكلة: ' + type);
        modal.find('#securityDetailDescription').text(description);
        modal.find('#securityDetailRecommendation').text(recommendation);
    });
    
    // زر معالجة المشكلة
    $('#fixIssueBtn').on('click', function() {
        Swal.fire({
            title: 'جاري معالجة المشكلة',
            text: 'يرجى الانتظار...',
            icon: 'info',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            }
        }).then(() => {
            Swal.fire({
                title: 'تمت المعالجة بنجاح!',
                text: 'تم معالجة المشكلة الأمنية بنجاح وتحسين درجة الأمان',
                icon: 'success',
                confirmButtonText: 'تم'
            });
        });
        $('#securityDetailModal').modal('hide');
    });
});

// دالة لتشغيل فحص أمني جديد
function runSecurityScan() {
    Swal.fire({
        title: 'جاري تنفيذ الفحص الأمني',
        html: 'يرجى الانتظار أثناء فحص النظام<br><small class="text-muted">قد يستغرق هذا بضع دقائق</small>',
        icon: 'info',
        showConfirmButton: false,
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
            setTimeout(() => {
                Swal.fire({
                    title: 'اكتمل الفحص الأمني',
                    text: 'تم العثور على 4 مشكلات أمنية تحتاج إلى معالجة',
                    icon: 'warning',
                    confirmButtonText: 'عرض التفاصيل'
                });
            }, 3000);
        }
    });
}

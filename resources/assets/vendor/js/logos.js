'use strict';

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

$(function () {
    // التحقق من وجود جدول السجلات
    const securityTable = $('#security-logs-table');
    if (!securityTable.length) return;

    const dt = securityTable.DataTable({
        dom: '<"card-header"<"head-label text-center"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        buttons: [
            {
                extend: 'collection',
                className: 'btn btn-label-secondary dropdown-toggle mx-3',
                text: '<i class="ti ti-screen-share me-1 ti-xs"></i>تصدير',
                buttons: [
                    {
                        extend: 'print',
                        text: '<i class="ti ti-printer me-2" ></i>طباعة',
                        className: 'dropdown-item',
                        exportOptions: { columns: [0, 1, 2, 3, 4] }
                    },
                    {
                        extend: 'csv',
                        text: '<i class="ti ti-file-text me-2" ></i>CSV',
                        className: 'dropdown-item',
                        exportOptions: { columns: [0, 1, 2, 3, 4] }
                    },
                    {
                        extend: 'excel',
                        text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
                        className: 'dropdown-item',
                        exportOptions: { columns: [0, 1, 2, 3, 4] }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="ti ti-file-code-2 me-2"></i>PDF',
                        className: 'dropdown-item',
                        exportOptions: { columns: [0, 1, 2, 3, 4] }
                    }
                ]
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'الكل']],
        language: {
            search: 'بحث:',
            searchPlaceholder: 'بحث في السجلات...',
            lengthMenu: 'عرض _MENU_ سجلات',
            info: 'عرض _START_ إلى _END_ من _TOTAL_ سجل',
            paginate: {
                first: 'الأول',
                last: 'الأخير',
                next: 'التالي',
                previous: 'السابق'
            }
        }
    });

    // تحديث الفلاتر عند التغيير
    $('#security-filters').on('submit', function(e) {
        e.preventDefault();
        dt.draw();
    });

    // تفعيل التواريخ
    $('.flatpickr').flatpickr({
        dateFormat: 'Y-m-d',
        locale: 'ar'
    });

    // تفعيل tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // معالجة حذف السجلات
    $(document).on('click', '.delete-log', function(e) {
        e.preventDefault();
        var btn = $(this);
        var url = btn.closest('form').attr('action');

        // منع أي تحويل أو إعادة تحميل
        if (confirm('هل أنت متأكد من حذف هذا السجل؟')) {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    _method: 'DELETE'
                },
                beforeSend: function() {
                    btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i>');
                },
                success: function(response) {
                    if (response.success) {
                        // تحديث الجدول بدون إعادة تحميل الصفحة
                        if (typeof dt !== 'undefined') {
                            dt.draw(false);
                        }
                        // عرض رسالة نجاح
                        toastr.success(response.message || 'تم حذف السجل بنجاح');
                    } else {
                        toastr.error(response.message || 'حدث خطأ أثناء حذف السجل');
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON ? xhr.responseJSON.message : 'حدث خطأ أثناء حذف السجل';
                    toastr.error(message);
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="ti ti-trash"></i>');
                }
            });
        }
    });

    // معالجة زر المشاهدة
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        
        $.ajax({
            url: `/dashboard/security/logs/${id}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // إنشاء نافذة مودال للعرض
                    var modalHtml = `
                        <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">تفاصيل السجل</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>نوع الحدث:</strong> ${response.data.event_type}</p>
                                                <p><strong>الوقت:</strong> ${response.data.created_at}</p>
                                                <p><strong>عنوان IP:</strong> ${response.data.ip_address}</p>
                                                <p><strong>متصفح المستخدم:</strong> ${response.data.user_agent}</p>
                                                <p><strong>المسار:</strong> ${response.data.route}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>الوصف:</strong> ${response.data.description}</p>
                                                <p><strong>مستوى الخطورة:</strong> ${response.data.severity}</p>
                                                <p><strong>بيانات الطلب:</strong> <pre>${JSON.stringify(JSON.parse(response.data.request_data || '{}'), null, 2)}</pre></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // إضافة المودال إلى الصفحة
                    $('body').append(modalHtml);
                    
                    // عرض المودال
                    var modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
                    modal.show();
                    
                    // تنظيف المودال عند إغلاقه
                    document.getElementById('logDetailsModal').addEventListener('hidden.bs.modal', function () {
                        $(this).remove();
                    });
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON ? xhr.responseJSON.message : 'حدث خطأ أثناء عرض التفاصيل';
                toastr.error(message);
            }
        });
    });
    
    // إعداد التفاصيل للثغرات الأمنية
    document.querySelectorAll('.dropdown-item').forEach(function(item) {
        if(item.querySelector('.ti-info-circle')) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var row = this.closest('tr');
                var type = row.querySelector('td:first-child .badge').textContent;
                var description = row.querySelector('td:nth-child(3)').textContent;
                var recommendation = row.querySelector('td:nth-child(4)') ? 
                                     row.querySelector('td:nth-child(4)').textContent : 
                                     'لا توجد توصيات';
                
                $('#securityDetailModal').modal('show');
                $('#securityDetailTitle').text('تفاصيل المشكلة: ' + type);
                $('#securityDetailDescription').text(description);
                $('#securityDetailRecommendation').text(recommendation);
            });
        }
    });
});
 
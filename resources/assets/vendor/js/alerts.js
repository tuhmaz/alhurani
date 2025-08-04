'use strict';

    $(function () {
        'use strict';

        // تهيئة منتقي التاريخ
        $('.flatpickr-basic').flatpickr();

        // تهيئة التلميحات
        $('[data-bs-toggle="tooltip"]').tooltip();

        // تحديد التنبيه كمحلول
        $('.resolve-alert').on('click', function() {
            var alertId = $(this).data('id');
            $('#alert_id').val(alertId);
            $('#is_resolved').val(1);
            $('#resolveAlertModal').modal('show');
        });

        // تحديد التنبيه كغير محلول
        $('.unresolve-alert').on('click', function() {
            var alertId = $(this).data('id');
            $('#alert_id').val(alertId);
            $('#is_resolved').val(0);
            $('#resolution_notes').val('');
            $('#resolveAlertModal').modal('show');
        });

        // إرسال نموذج تحديد التنبيه
        $('#submitResolveAlert').on('click', function() {
            var alertId = $('#alert_id').val();
            var isResolved = $('#is_resolved').val();
            var resolutionNotes = $('#resolution_notes').val();

            $.ajax({
                url: '/dashboard/security/alerts/' + alertId,
                type: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    is_resolved: isResolved,
                    resolution_notes: resolutionNotes
                },
                success: function(response) {
                    $('#resolveAlertModal').modal('hide');
                    // عرض رسالة نجاح
                    toastr.success(response.message);
                    // إعادة تحميل الصفحة بعد ثانيتين
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                },
                error: function(xhr) {
                    // عرض رسالة خطأ
                    toastr.error(xhr.responseJSON.message || 'حدث خطأ أثناء معالجة الطلب');
                }
            });
        });

        // حظر عنوان IP
        $('.block-ip').on('click', function() {
            var ip = $(this).data('ip');
            $('#ip_address').val(ip);
            $('#blockIpModal').modal('show');
        });

        // إرسال نموذج حظر عنوان IP
        $('#submitBlockIp').on('click', function() {
            var ipAddress = $('#ip_address').val();
            var blockReason = $('#block_reason').val();
            var blockDuration = $('#block_duration').val();

            $.ajax({
                url: '/dashboard/security/block-ip',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ip_address: ipAddress,
                    reason: blockReason,
                    duration: blockDuration
                },
                success: function(response) {
                    $('#blockIpModal').modal('hide');
                    // عرض رسالة نجاح
                    toastr.success(response.message);
                    // إعادة تحميل الصفحة بعد ثانيتين
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                },
                error: function(xhr) {
                    // عرض رسالة خطأ
                    toastr.error(xhr.responseJSON.message || 'حدث خطأ أثناء معالجة الطلب');
                }
            });
        });
    });

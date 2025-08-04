$(function () {
    'use strict';

    // تحديد التنبيه كمحلول
    $('.resolve-alert').on('click', function() {
        $('#is_resolved').val(1);
        $('#resolveAlertModal').modal('show');
    });

    // تحديد التنبيه كغير محلول
    $('.unresolve-alert').on('click', function() {
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
                _token: $('meta[name="csrf-token"]').attr('content'),
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
                _token: $('meta[name="csrf-token"]').attr('content'),
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
    
    // تحديد مشكلة فردية كمحلولة
    $('.mark-issue-resolved').on('click', function() {
        var issueId = $(this).data('issue-id');
        var alertId = $('#alert_id').val();
        
        // عرض رسالة تأكيد
        if (confirm('هل أنت متأكد من تحديد هذه المشكلة كمحلولة؟')) {
            // هنا يمكن إرسال طلب AJAX لتحديث حالة المشكلة الفردية
            toastr.success('تم تحديد المشكلة #' + issueId + ' كمحلولة');
            
            // تحديث واجهة المستخدم
            $(this).closest('.issue-item').find('.badge').removeClass('bg-danger bg-warning bg-info').addClass('bg-success').text('محلول');
            $(this).remove();
        }
    });
});

@component('mail::message')
# تقرير الفحص الأمني

تم إجراء فحص أمني شامل للتطبيق بتاريخ {{ $scanDate }}.

## درجة الأمان الإجمالية: {{ $score }}/100

@if($score < 70)
**تنبيه: تم اكتشاف مشكلات أمنية حرجة تتطلب اهتمامك الفوري.**
@elseif($score < 90)
**ملاحظة: تم اكتشاف بعض المشكلات الأمنية التي تتطلب المراجعة.**
@else
تهانينا! التطبيق يتمتع بمستوى أمان جيد.
@endif

@if($totalIssues > 0)
## الثغرات الأمنية المكتشفة ({{ $totalIssues }})

@component('mail::table')
| النوع | الخطورة | الوصف |
| :--- | :------ | :---- |
@foreach($vulnerabilities as $issue)
| {{ $issue['type'] }} | {{ $issue['severity'] }} | {{ $issue['description'] }} |
@endforeach
@endcomponent

@component('mail::panel')
للحصول على تفاصيل كاملة حول التوصيات لإصلاح هذه المشكلات، يرجى زيارة لوحة تحكم الأمان.
@endcomponent
@endif

@if($criticalUpdates)
## تحديثات أمنية حرجة

تم اكتشاف تحديثات أمنية حرجة للمكونات التالية:

@if($updates['laravel_version']['is_critical'])
- **Laravel**: الإصدار الحالي {{ $updates['laravel_version']['current_version'] }}، الإصدار المتاح {{ $updates['laravel_version']['latest_version'] }}
@endif

@if($updates['php_version']['is_critical'])
- **PHP**: الإصدار الحالي {{ $updates['php_version']['current_version'] }}، الإصدار المتاح {{ $updates['php_version']['latest_version'] }}
@endif

@if(count($updates['packages']['security_updates']) > 0)
- **الحزم والمكتبات**:
@foreach($updates['packages']['security_updates'] as $package)
  - {{ $package['name'] }}: الإصدار الحالي {{ $package['current_version'] }}، الإصدار المتاح {{ $package['latest_version'] }}
@endforeach
@endif

@component('mail::panel')
يوصى بشدة بتحديث هذه المكونات في أقرب وقت ممكن لتجنب المخاطر الأمنية المحتملة.
@endcomponent
@endif

## إحصائيات سجلات الأمان

- إجمالي السجلات: {{ $logs['total_logs'] }}
- السجلات الحرجة: {{ $logs['critical_logs'] }}
- المشكلات غير المحلولة: {{ $logs['unresolved_issues'] }}
- النشاط المشبوه الأخير (24 ساعة): {{ $logs['recent_suspicious_activity'] }}

@component('mail::button', ['url' => route('dashboard.security.monitor')])
عرض لوحة مراقبة الأمان
@endcomponent

شكرًا لك،<br>
{{ config('app.name') }}
@endcomponent

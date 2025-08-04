'use strict';

// تعريف جميع الدوال المطلوبة كـ window functions
window.refreshData = function() {
    location.reload();
};

window.optimizeCache = function() {
    if (confirm('Are you sure you want to optimize the cache system?')) {
        fetch('/dashboard/redis/optimize', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache optimization completed successfully');
                location.reload();
            } else {
                alert('Error occurred during optimization');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error occurred during optimization');
        });
    }
};

window.loadReport = function(hours) {
    location.href = `/dashboard/redis/monitoring?hours=${hours}`;
};

window.analyzeKey = function(key) {
    fetch('/dashboard/redis/analyze-key', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ key: key })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('key-analysis-content').innerHTML = `
            <h6>Key: <code>${key}</code></h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Operations:</strong> ${data.operations || 0}</p>
                    <p><strong>Average Response Time:</strong> ${data.avg_response_time || 0} ms</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Memory Usage:</strong> ${data.memory_usage || 0} KB</p>
                    <p><strong>TTL:</strong> ${data.ttl || 0} seconds</p>
                </div>
            </div>
        `;
        new bootstrap.Modal(document.getElementById('keyAnalysisModal')).show();
    });
};

window.applyRecommendation = function(action) {
    if (confirm('Are you sure you want to apply this optimization?')) {
        fetch('/dashboard/redis/apply-recommendation', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Optimization applied successfully');
                location.reload();
            } else {
                alert('Error occurred during optimization');
            }
        });
    }
};

// الرسوم البيانية وتحديث الإحصائيات
document.addEventListener('DOMContentLoaded', function() {
    // رسم بياني للأداء
    const performanceData = window.chartData ? window.chartData.performance : [];
    const hoursData = window.chartData ? window.chartData.hours : [];
    
    const performanceOptions = {
        series: [{
            name: 'Response Time',
            data: performanceData
        }],
        chart: {
            type: 'line',
            height: 350
        },
        xaxis: {
            categories: hoursData
        },
        yaxis: {
            title: {
                text: 'Response Time (ms)'
            }
        }
    };
    
    if (document.getElementById('performanceChart')) {
        const performanceChart = new ApexCharts(document.querySelector("#performanceChart"), performanceOptions);
        performanceChart.render();
    }

    // رسم بياني للذاكرة
    const memorySeries = window.chartData ? window.chartData.memory_series : [];
    const memoryLabels = window.chartData ? window.chartData.memory_labels : [];
    
    const memoryOptions = {
        series: memorySeries,
        chart: {
            type: 'donut',
            height: 350
        },
        labels: memoryLabels,
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };
    
    if (document.getElementById('memoryChart')) {
        const memoryChart = new ApexCharts(document.querySelector("#memoryChart"), memoryOptions);
        memoryChart.render();
    }
});

// تحديث تلقائي كل 30 ثانية للإحصائيات
setInterval(function() {
    fetch('/dashboard/redis/stats')
        .then(response => response.json())
        .then(data => {
            if (document.getElementById('memory-usage')) {
                document.getElementById('memory-usage').textContent = data.memory_usage;
            }
            if (document.getElementById('total-keys')) {
                document.getElementById('total-keys').textContent = data.keys_count;
            }
            if (document.getElementById('hit-ratio')) {
                document.getElementById('hit-ratio').textContent = data.hit_ratio + '%';
            }
            if (document.getElementById('avg-ttl')) {
                document.getElementById('avg-ttl').textContent = data.avg_ttl + 's';
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}, 30000);

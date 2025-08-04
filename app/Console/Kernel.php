<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Limpiar actividades antiguas cada día a la medianoche
        $schedule->command('activities:clean-old')->daily();
        
        // تنفيذ فحص أمني شامل أسبوعيًا
        $schedule->command('security:scan --notify')->weekly()->sundays()->at('02:00');
        
        // تنظيف سجلات الأمان القديمة شهريًا
        $schedule->command('security:clean-logs')->monthly()->at('03:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

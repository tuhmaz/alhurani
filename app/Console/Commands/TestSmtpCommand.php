<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SmtpTestService;
use App\Models\Setting;

class TestSmtpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smtp:test 
                            {--host= : SMTP host}
                            {--port= : SMTP port}
                            {--username= : SMTP username}
                            {--password= : SMTP password}
                            {--encryption= : SMTP encryption (ssl/tls)}
                            {--email= : Send test email to this address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMTP connection and optionally send test email';

    /**
     * Execute the console command.
     */
    public function handle(SmtpTestService $smtpTestService)
    {
        $this->info('🔧 Testing SMTP Connection...');
        $this->newLine();

        try {
            // Get configuration from options or database
            $config = $this->getSmtpConfig();
            
            $this->displayConfig($config);
            $this->newLine();

            // Test connection
            $result = $smtpTestService->testConnection($config);

            if ($result['success']) {
                $this->info('✅ SMTP Connection Successful!');
                $this->info('Message: ' . $result['message']);
                
                if (isset($result['details'])) {
                    $this->displayDetails($result['details']);
                }

                // Send test email if requested
                if ($this->option('email')) {
                    $this->newLine();
                    $this->sendTestEmail($config);
                }

            } else {
                $this->error('❌ SMTP Connection Failed!');
                $this->error('Error: ' . $result['message']);
                
                if (!empty($result['suggestions'])) {
                    $this->newLine();
                    $this->warn('💡 Suggestions:');
                    foreach ($result['suggestions'] as $suggestion) {
                        $this->line('  • ' . $suggestion);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Command failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Get SMTP configuration from options or database
     */
    private function getSmtpConfig()
    {
        return [
            'host' => $this->option('host') ?: Setting::get('mail_host', config('mail.mailers.smtp.host')),
            'port' => (int) ($this->option('port') ?: Setting::get('mail_port', config('mail.mailers.smtp.port'))),
            'username' => $this->option('username') ?: Setting::get('mail_username', config('mail.mailers.smtp.username')),
            'password' => $this->option('password') ?: Setting::get('mail_password', config('mail.mailers.smtp.password')),
            'encryption' => $this->option('encryption') ?: Setting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
            'timeout' => 30,
            'auth_mode' => 'login'
        ];
    }

    /**
     * Display current configuration
     */
    private function displayConfig($config)
    {
        $this->info('📋 Current SMTP Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Host', $config['host'] ?: 'Not set'],
                ['Port', $config['port'] ?: 'Not set'],
                ['Username', $config['username'] ? '***' . substr($config['username'], -10) : 'Not set'],
                ['Password', $config['password'] ? str_repeat('*', strlen($config['password'])) : 'Not set'],
                ['Encryption', $config['encryption'] ?: 'None'],
                ['Timeout', $config['timeout'] . ' seconds']
            ]
        );
    }

    /**
     * Display connection details
     */
    private function displayDetails($details)
    {
        $this->newLine();
        $this->info('📊 Connection Details:');
        foreach ($details as $key => $value) {
            $this->line("  {$key}: " . ($value === true ? 'Yes' : ($value === false ? 'No' : $value)));
        }
    }

    /**
     * Send test email
     */
    private function sendTestEmail($config)
    {
        $email = $this->option('email');
        
        $this->info("📧 Sending test email to: {$email}");
        
        try {
            // Configure mail settings
            config(['mail.mailers.smtp' => array_merge(config('mail.mailers.smtp'), [
                'host' => $config['host'],
                'port' => $config['port'],
                'username' => $config['username'],
                'password' => $config['password'],
                'encryption' => $config['encryption'],
            ])]);

            \Mail::raw('This is a test email sent from the SMTP test command. If you received this email, your SMTP configuration is working correctly.', function($message) use ($email) {
                $message->to($email)
                        ->subject('SMTP Test Email - ' . config('app.name'));
            });

            $this->info('✅ Test email sent successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email: ' . $e->getMessage());
        }
    }
}

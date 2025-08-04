<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class HttpsProtocol
{
    /**
     * توجيه جميع الطلبات إلى HTTPS في بيئة الإنتاج
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // تطبيق HTTPS فقط في بيئة الإنتاج وعندما يكون force_https مفعل
        if (!$request->secure() && App::environment('production') && Config::get('secure-connections.force_https', true)) {
            // إعادة توجيه الطلب إلى HTTPS
            return redirect()->secure($request->getRequestUri());
        }

        // تعيين رؤوس HTTP الأمنية
        $response = $next($request);
        
        // تعيين رأس HTTPS فقط - باقي رؤوس الأمان ستتم معالجتها بواسطة SecurityHeaders middleware
        
        // تعيين رأس Strict-Transport-Security فقط في بيئة الإنتاج وعند استخدام HTTPS
        if (App::environment('production') && $request->secure()) {
            $hstsMaxAge = Config::get('secure-connections.hsts_max_age', 31536000);
            $hstsIncludeSubdomains = Config::get('secure-connections.hsts_include_subdomains', true);
            $hstsHeader = "max-age={$hstsMaxAge}";
            if ($hstsIncludeSubdomains) {
                $hstsHeader .= '; includeSubDomains; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hstsHeader);
        }
        
        return $response;
    }
}

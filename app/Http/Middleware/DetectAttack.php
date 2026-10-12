<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class DetectAttack
{
    protected array $sqlInjectionPatterns = [
        '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
        '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',
        '/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i',
        '/((\%27)|(\'))union/i',
        '/exec(\s|\+)+(s|x)p\w+/i',
        '/union(\s+)select/i',
        '/insert(\s+)into/i',
        '/delete(\s+)from/i',
        '/drop(\s+)table/i',
        '/update(\s+)\w+(\s+)set/i',
        '/select(\s+)[\w\*\,\s]+(\s+)from/i',
        '/\bor\b\s+\d+\s*=\s*\d+/i',
        '/\band\b\s+\d+\s*=\s*\d+/i',
        '/\bor\b\s+[\'\"]?\w+[\'\"]?\s*=\s*[\'\"]?\w+[\'\"]?/i',
        '/benchmark\s*\(/i',
        '/sleep\s*\(/i',
        '/load_file\s*\(/i',
        '/into\s+outfile/i',
        '/into\s+dumpfile/i',
    ];

    protected array $xssPatterns = [
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript\s*:/i',
        '/on\w+\s*=\s*["\'][^"\']*["\']/i',
        '/<iframe[^>]*>/i',
        '/<object[^>]*>/i',
        '/<embed[^>]*>/i',
        '/<link[^>]*>/i',
        '/<meta[^>]*>/i',
        '/expression\s*\(/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
    ];

    protected array $pathTraversalPatterns = [
        '/\.\.\//i',
        '/\.\.\\\/i',
        '/%2e%2e%2f/i',
        '/%2e%2e\//i',
        '/\.%2e\//i',
        '/%2e\.\//i',
        '/\.\.%5c/i',
        '/%252e%252e%255c/i',
        '/etc\/passwd/i',
        '/etc\/shadow/i',
        '/proc\/self/i',
        '/windows\/system32/i',
    ];

    protected array $commandInjectionPatterns = [
        '/;\s*(ls|cat|rm|mv|cp|chmod|chown|wget|curl|nc|bash|sh|python|perl|php|ruby)\b/i',
        '/\|\s*(ls|cat|rm|mv|cp|chmod|chown|wget|curl|nc|bash|sh|python|perl|php|ruby)\b/i',
        '/`[^`]*`/',
        '/\$\([^)]*\)/',
        '/\$\{[^}]*\}/',
        '/&&\s*(ls|cat|rm|mv|cp|chmod|chown|wget|curl|nc|bash|sh)\b/i',
        '/\|\|\s*(ls|cat|rm|mv|cp|chmod|chown|wget|curl|nc|bash|sh)\b/i',
    ];

    protected array $ldapInjectionPatterns = [
        '/[)(|*\\\\]/',
        '/\x00/',
    ];

    protected array $xmlInjectionPatterns = [
        '/<!ENTITY/i',
        '/<!DOCTYPE[^>]*\[/i',
        '/SYSTEM\s+["\']/i',
        '/PUBLIC\s+["\']/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip if tables don't exist
        if (! Schema::hasTable('security_logs')) {
            return $next($request);
        }

        try {
            $attacks = $this->detectAttacks($request);

            if (! empty($attacks)) {
                $this->logAttacks($request, $attacks);

                // Auto-block IP if critical attack detected
                if ($this->shouldBlockIp($request->ip(), $attacks)) {
                    $this->blockAttacker($request->ip());
                }
            }
        } catch (\Exception $e) {
            // Don't break the application if detection fails
            // Just log error silently and continue
            \Log::error('Attack detection error: '.$e->getMessage());
        }

        // Always continue - detection only, no blocking of requests
        return $next($request);
    }

    protected function detectAttacks(Request $request): array
    {
        $attacks = [];
        $inputs = $this->getAllInputs($request);

        foreach ($inputs as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            // SQL Injection
            if ($this->matchesPatterns($value, $this->sqlInjectionPatterns)) {
                $attacks[] = [
                    'type' => 'sql_injection',
                    'severity' => 'critical',
                    'field' => $key,
                    'value' => $this->truncateValue($value),
                ];
            }

            // XSS
            if ($this->matchesPatterns($value, $this->xssPatterns)) {
                $attacks[] = [
                    'type' => 'xss',
                    'severity' => 'critical',
                    'field' => $key,
                    'value' => $this->truncateValue($value),
                ];
            }

            // Path Traversal
            if ($this->matchesPatterns($value, $this->pathTraversalPatterns)) {
                $attacks[] = [
                    'type' => 'path_traversal',
                    'severity' => 'critical',
                    'field' => $key,
                    'value' => $this->truncateValue($value),
                ];
            }

            // Command Injection
            if ($this->matchesPatterns($value, $this->commandInjectionPatterns)) {
                $attacks[] = [
                    'type' => 'command_injection',
                    'severity' => 'critical',
                    'field' => $key,
                    'value' => $this->truncateValue($value),
                ];
            }

            // LDAP Injection
            if ($this->matchesPatterns($value, $this->ldapInjectionPatterns)) {
                $attacks[] = [
                    'type' => 'ldap_injection',
                    'severity' => 'warning',
                    'field' => $key,
                    'value' => $this->truncateValue($value),
                ];
            }

            // XML/XXE Injection
            if ($this->matchesPatterns($value, $this->xmlInjectionPatterns)) {
                $attacks[] = [
                    'type' => 'xxe_injection',
                    'severity' => 'critical',
                    'field' => $key,
                    'value' => $this->truncateValue($value),
                ];
            }
        }

        // Check URL path for attacks
        $path = $request->path();
        if ($this->matchesPatterns($path, $this->pathTraversalPatterns)) {
            $attacks[] = [
                'type' => 'path_traversal',
                'severity' => 'critical',
                'field' => 'url_path',
                'value' => $this->truncateValue($path),
            ];
        }

        // Check for suspicious user agents
        $userAgent = $request->userAgent() ?? '';
        if ($this->isSuspiciousUserAgent($userAgent)) {
            $attacks[] = [
                'type' => 'suspicious_user_agent',
                'severity' => 'info',
                'field' => 'user_agent',
                'value' => $this->truncateValue($userAgent),
            ];
        }

        return $attacks;
    }

    protected function getAllInputs(Request $request): array
    {
        $inputs = [];

        // Query parameters
        foreach ($request->query() as $key => $value) {
            $inputs["query.{$key}"] = $value;
        }

        // POST data
        foreach ($request->post() as $key => $value) {
            $inputs["post.{$key}"] = $value;
        }

        // Headers (selected ones)
        $checkHeaders = ['referer', 'x-forwarded-for', 'x-custom-header'];
        foreach ($checkHeaders as $header) {
            if ($request->hasHeader($header)) {
                $inputs["header.{$header}"] = $request->header($header);
            }
        }

        // Cookies
        foreach ($request->cookies->all() as $key => $value) {
            if (is_string($value)) {
                $inputs["cookie.{$key}"] = $value;
            }
        }

        return $inputs;
    }

    protected function matchesPatterns(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    protected function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousAgents = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zgrab',
            'gobuster',
            'dirbuster',
            'wfuzz',
            'hydra',
            'burpsuite',
            'havij',
            'acunetix',
            'nessus',
            'openvas',
            'w3af',
            'skipfish',
            'whatweb',
        ];

        $lowerAgent = strtolower($userAgent);
        foreach ($suspiciousAgents as $agent) {
            if (str_contains($lowerAgent, $agent)) {
                return true;
            }
        }

        return false;
    }

    protected function truncateValue(string $value): string
    {
        return mb_substr($value, 0, 500);
    }

    protected function logAttacks(Request $request, array $attacks): void
    {
        foreach ($attacks as $attack) {
            SecurityLog::log(
                $attack['type'],
                $attack['severity'],
                "Detected {$attack['type']} attack in field [{$attack['field']}]: {$attack['value']}",
                null,
                $request
            );
        }
    }

    protected function shouldBlockIp(string $ip, array $attacks): bool
    {
        // Check if any critical attack detected
        $hasCritical = collect($attacks)->contains('severity', 'critical');

        if (! $hasCritical) {
            return false;
        }

        // Check attack count in last hour
        $recentAttacks = SecurityLog::where('ip_address', $ip)
            ->where('severity', 'critical')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        // Block if 5+ critical attacks in the last hour
        return $recentAttacks >= 5;
    }

    protected function blockAttacker(string $ip): void
    {
        if (! Schema::hasTable('blocked_ips')) {
            return;
        }

        // Don't block if already blocked
        if (BlockedIp::isBlocked($ip)) {
            return;
        }

        // Block for 24 hours (auto-block, not permanent)
        BlockedIp::block(
            $ip,
            'Auto-blocked: Multiple critical attack attempts detected',
            null, // System block
            1440 // 24 hours in minutes
        );

        SecurityLog::log(
            'auto_ip_block',
            'warning',
            "IP {$ip} has been automatically blocked for 24 hours due to multiple attack attempts",
            null,
            request()
        );
    }
}

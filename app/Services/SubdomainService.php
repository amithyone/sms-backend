<?php

namespace App\Services;

use App\Models\ResellerPanel;
use Illuminate\Support\Facades\Log;

class SubdomainService
{
    /**
     * Create subdomain configuration for a reseller panel
     */
    public function createSubdomain(ResellerPanel $panel): array
    {
        try {
            $subdomain = $panel->subdomain;
            $fullDomain = "{$subdomain}.fadsms.com";
            
            // Create Nginx configuration
            $nginxConfig = $this->generateNginxConfig($subdomain, $fullDomain);
            $configPath = "/etc/nginx/sites-available/{$fullDomain}";
            
            // Write config file
            file_put_contents($configPath, $nginxConfig);
            
            // Create symlink to sites-enabled
            $symlinkPath = "/etc/nginx/sites-enabled/{$fullDomain}";
            if (!file_exists($symlinkPath)) {
                symlink($configPath, $symlinkPath);
            }
            
            // Test Nginx configuration
            $testResult = shell_exec('sudo nginx -t 2>&1');
            
            if (strpos($testResult, 'syntax is ok') !== false) {
                // Reload Nginx
                shell_exec('sudo systemctl reload nginx 2>&1');
                
                // Try to get SSL certificate
                $this->obtainSSLCertificate($fullDomain);
                
                return [
                    'success' => true,
                    'message' => "Subdomain {$fullDomain} created successfully",
                    'url' => "https://{$fullDomain}"
                ];
            } else {
                // Rollback
                @unlink($configPath);
                @unlink($symlinkPath);
                
                return [
                    'success' => false,
                    'message' => "Nginx configuration test failed: {$testResult}"
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to create subdomain', [
                'panel_id' => $panel->id,
                'subdomain' => $panel->subdomain,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => "Failed to create subdomain: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate Nginx configuration for subdomain
     */
    private function generateNginxConfig(string $subdomain, string $fullDomain): string
    {
        return <<<NGINX
# Reseller Panel: {$subdomain}
server {
    server_name {$fullDomain};

    root /var/www/fadsms.com/dist;
    index index.html;

    # Set reseller panel identifier
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    listen 80;
    listen [::]:80;
}
NGINX;
    }
    
    /**
     * Obtain SSL certificate using Certbot
     */
    private function obtainSSLCertificate(string $domain): bool
    {
        try {
            // Run certbot in non-interactive mode
            $command = "sudo certbot --nginx -d {$domain} --non-interactive --agree-tos --email admin@fadsms.com --redirect 2>&1";
            $output = shell_exec($command);
            
            Log::info('SSL Certificate request', [
                'domain' => $domain,
                'output' => $output
            ]);
            
            return strpos($output, 'Successfully') !== false;
        } catch (\Exception $e) {
            Log::error('Failed to obtain SSL certificate', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Remove subdomain configuration
     */
    public function removeSubdomain(ResellerPanel $panel): array
    {
        try {
            $fullDomain = "{$panel->subdomain}.fadsms.com";
            $configPath = "/etc/nginx/sites-available/{$fullDomain}";
            $symlinkPath = "/etc/nginx/sites-enabled/{$fullDomain}";
            
            // Remove symlink and config
            @unlink($symlinkPath);
            @unlink($configPath);
            
            // Reload Nginx
            shell_exec('sudo systemctl reload nginx 2>&1');
            
            return [
                'success' => true,
                'message' => "Subdomain removed successfully"
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to remove subdomain', [
                'panel_id' => $panel->id,
                'subdomain' => $panel->subdomain,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => "Failed to remove subdomain: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Configure custom domain for reseller panel
     */
    public function configureCustomDomain(ResellerPanel $panel): array
    {
        if (!$panel->custom_domain) {
            return [
                'success' => false,
                'message' => 'No custom domain specified'
            ];
        }
        
        try {
            $customDomain = $panel->custom_domain;
            
            // Create Nginx configuration
            $nginxConfig = $this->generateNginxConfig('custom-' . $panel->id, $customDomain);
            $configPath = "/etc/nginx/sites-available/{$customDomain}";
            
            // Write config file
            file_put_contents($configPath, $nginxConfig);
            
            // Create symlink
            $symlinkPath = "/etc/nginx/sites-enabled/{$customDomain}";
            if (!file_exists($symlinkPath)) {
                symlink($configPath, $symlinkPath);
            }
            
            // Test Nginx
            $testResult = shell_exec('sudo nginx -t 2>&1');
            
            if (strpos($testResult, 'syntax is ok') !== false) {
                shell_exec('sudo systemctl reload nginx 2>&1');
                
                // Return DNS instructions
                return [
                    'success' => true,
                    'message' => 'Custom domain configured successfully',
                    'dns_instructions' => $this->getDNSInstructions($panel)
                ];
            } else {
                @unlink($configPath);
                @unlink($symlinkPath);
                
                return [
                    'success' => false,
                    'message' => "Nginx configuration test failed: {$testResult}"
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to configure custom domain', [
                'panel_id' => $panel->id,
                'custom_domain' => $panel->custom_domain,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => "Failed to configure custom domain: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get DNS setup instructions for custom domain
     */
    public function getDNSInstructions(ResellerPanel $panel): array
    {
        // Get server IP
        $serverIP = gethostbyname('fadsms.com');
        
        return [
            'custom_domain' => $panel->custom_domain,
            'instructions' => [
                [
                    'type' => 'A Record',
                    'name' => '@',
                    'value' => $serverIP,
                    'description' => 'Point your domain to our server'
                ],
                [
                    'type' => 'A Record',
                    'name' => 'www',
                    'value' => $serverIP,
                    'description' => 'Point www subdomain to our server'
                ],
                [
                    'type' => 'CNAME',
                    'name' => $panel->custom_domain,
                    'value' => 'fadsms.com',
                    'description' => 'Alternative: Use CNAME instead of A record'
                ]
            ],
            'notes' => [
                'DNS changes can take up to 48 hours to propagate',
                'After DNS is configured, we will automatically obtain SSL certificate',
                'Contact support if you need help with DNS configuration'
            ]
        ];
    }
}


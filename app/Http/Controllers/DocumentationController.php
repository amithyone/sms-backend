<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocumentationController extends Controller
{
    /**
     * Serve API Documentation
     */
    public function apiDocumentation()
    {
        return $this->serveMarkdownFile('API_DOCUMENTATION.md');
    }

    /**
     * Serve FAQ/Help Center
     */
    public function helpResources()
    {
        return $this->serveMarkdownFile('HELP_RESOURCES.md');
    }

    /**
     * Serve Terms of Use
     */
    public function termsOfUse()
    {
        return $this->serveMarkdownFile('TERMS_OF_USE.md');
    }

    /**
     * Serve Privacy Policy
     */
    public function privacyPolicy()
    {
        return $this->serveMarkdownFile('PRIVACY_POLICY.md');
    }

    /**
     * Helper method to serve markdown files
     */
    private function serveMarkdownFile(string $filename)
    {
        $path = base_path($filename);

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation file not found',
                'file' => $filename
            ], 404);
        }

        $content = file_get_contents($path);

        // Return as plain text with markdown content type
        return response($content, 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * Get list of available documentation
     */
    public function index(): JsonResponse
    {
        $docs = [
            [
                'title' => 'API Documentation',
                'description' => 'Complete API reference for FaddedSMS Reseller API',
                'url' => url('/API_DOCUMENTATION.md'),
                'icon' => '📖'
            ],
            [
                'title' => 'FAQs & Help Center',
                'description' => 'Frequently asked questions and help resources',
                'url' => url('/HELP_RESOURCES.md'),
                'icon' => '❓'
            ],
            [
                'title' => 'Terms of Use',
                'description' => 'Terms and conditions for using FaddedSMS services',
                'url' => url('/TERMS_OF_USE.md'),
                'icon' => '📜'
            ],
            [
                'title' => 'Privacy Policy',
                'description' => 'Our privacy policy and data protection measures',
                'url' => url('/PRIVACY_POLICY.md'),
                'icon' => '🔒'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $docs,
            'message' => 'Documentation list retrieved successfully'
        ]);
    }
}


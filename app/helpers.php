<?php

if (!function_exists('versioned_asset')) {
    /**
     * Generate an asset path with dynamic cache-busting version query string.
     */
    function versioned_asset(string $path): string
    {
        $cleanPath = ltrim($path, '/\\');
        $fullPath = base_path('public/' . $cleanPath);
        $version = file_exists($fullPath) ? filemtime($fullPath) : time();
        return asset($cleanPath) . '?v=' . $version;
    }
}

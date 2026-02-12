<?php
/**
 * Cache Buster Module
 * 
 * Aggressive cache-busting headers to force content updates
 * Bypasses server-side caches (LiteSpeed, Cloudflare) and browser caches
 * 
 * This file MUST be included at the very top of header.php before ANY output
 * to ensure headers are sent before HTML content.
 */

// Prevent direct access
if (!isset($_SESSION)) {
    session_start();
}

// Aggressive Cache-Control headers
// - no-store: Prevents caching entirely
// - no-cache: Forces revalidation with server
// - must-revalidate: Forces fresh content on every request
// - max-age=0: Immediately stale
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// HTTP/1.0 compatibility
header("Pragma: no-cache");

// Set Expires to past date to prevent caching
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// LiteSpeed-specific cache bypass
header("X-LiteSpeed-Cache-Control: no-cache");

// Cloudflare cache bypass
// Note: CF-Cache-Status is typically set by Cloudflare, but we can suggest bypass
header("CDN-Cache-Control: no-cache");

// Dynamic Last-Modified header (current time)
// This ensures content is always considered fresh
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// Dynamic ETag based on current timestamp
// This prevents serving stale content based on old ETags
header("ETag: \"" . md5(microtime(true)) . "\"");

// Security headers
// Prevent clickjacking attacks
header("X-Frame-Options: SAMEORIGIN");

// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Enable XSS protection in older browsers
header("X-XSS-Protection: 1; mode=block");

// Note: We don't set CF-Cache-Status directly as it's a Cloudflare response header
// Instead, we use Cache-Control which Cloudflare respects

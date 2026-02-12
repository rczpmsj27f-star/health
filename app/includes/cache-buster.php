<?php
/**
 * Cache Buster Module
 * 
 * Aggressive cache-busting headers to force content updates
 * Bypasses server-side caches (LiteSpeed, Cloudflare) and browser caches
 * 
 * This file MUST be included at the very top of header.php before ANY output
 * to ensure headers are sent before HTML content.
 * 
 * Note: This module assumes session_start() has already been called by the including page.
 */

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

// Security headers
// Prevent clickjacking attacks
header("X-Frame-Options: SAMEORIGIN");

// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Note: We don't set CF-Cache-Status directly as it's a Cloudflare response header
// Instead, we use Cache-Control which Cloudflare respects

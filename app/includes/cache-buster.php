<?php
/**
 * Cache Buster Module
 * 
 * Aggressive cache-busting headers to force content updates
 * Bypasses server-side caches (LiteSpeed, Cloudflare) and browser caches
 * 
 * This file MUST be included as the FIRST LINE of every PHP entry point page
 * BEFORE any other code, output, or includes to ensure headers are sent properly.
 * 
 * This module handles session_start() internally if not already started.
 */

// Start session if not already started (must be before any output)
if (session_status() === PHP_SESSION_NONE) {
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

// Security headers
// Prevent clickjacking attacks
header("X-Frame-Options: SAMEORIGIN");

// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Note: We don't set CF-Cache-Status directly as it's a Cloudflare response header
// Instead, we use Cache-Control which Cloudflare respects

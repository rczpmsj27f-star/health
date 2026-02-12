<?php
/**
 * Cache Buster Module
 * 
 * AGGRESSIVE CACHE BUSTING FOR GET REQUESTS ONLY
 * Allows POST/redirects to work normally
 * GET requests force refresh of content
 * POST requests are allowed through for forms/redirects
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

// Only cache-bust GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // FORCE DISABLE SERVER CACHING
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    
    // SPECIFIC HOSTINGER/LITESPEED BYPASS
    header("X-LiteSpeed-Cache-Control: no-cache");
    
    // CLOUDFLARE BYPASS
    header("CF-Cache-Status: BYPASS");
    
    // Dynamic cache validation
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("ETag: " . md5(time()));
}

// Always allow redirects and security headers (applies to all request types)
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

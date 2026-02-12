<?php
/**
 * Cache Buster Module - AGGRESSIVE MULTI-LAYER CACHE PREVENTION
 * 
 * Sends multiple redundant cache-prevention headers to ensure
 * no caching layer (browser, proxy, CDN, or Service Worker) 
 * can cache the response.
 * 
 * This file MUST be included as the FIRST LINE of every PHP entry point page
 * BEFORE any other code, output, or includes to ensure headers are sent properly.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only cache-bust GET requests (allow POST/redirects)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ✅ LAYER 1: Standard HTTP cache headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // ✅ LAYER 2: Unique identifier every request (defeats ETags)
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("ETag: \"" . md5(uniqid(mt_rand(), true)) . "\"");
    
    // ✅ LAYER 3: Service Worker specific
    header("X-SW-Precache-Control: no-cache");
    
    // ✅ LAYER 4: Specific server implementations
    header("X-LiteSpeed-Cache-Control: no-cache");
    header("CF-Cache-Status: BYPASS");
    
    // ✅ LAYER 5: Vary headers (cache key varies by these)
    header("Vary: Accept-Encoding, Cookie, Authorization");
}

// Always send security headers (applies to all request types)
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");


<?php

/**
 * File Upload Helper Functions
 * Provides utilities for handling file uploads securely
 */

/**
 * Validate uploaded file
 * 
 * @param array $file The $_FILES array element
 * @param array $allowed_types Array of allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validate_upload($file, $allowed_types = [], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $max_mb = round($max_size / 1048576, 2);
        return ['valid' => false, 'error' => "File size exceeds {$max_mb}MB limit"];
    }
    
    // Check file type if allowed types specified
    if (!empty($allowed_types)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Generate a safe filename
 * 
 * @param string $filename Original filename
 * @return string Safe filename
 */
function sanitize_filename($filename) {
    $info = pathinfo($filename);
    $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
    $basename = basename($filename, $extension);
    
    // Remove special characters and spaces
    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
    
    // Add timestamp to ensure uniqueness
    return $safe_name . '_' . time() . $extension;
}

/**
 * Save uploaded file to destination
 * 
 * @param array $file The $_FILES array element
 * @param string $destination Destination directory
 * @param string|null $filename Optional custom filename
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function save_upload($file, $destination, $filename = null) {
    // Ensure destination directory exists
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Generate filename if not provided
    if ($filename === null) {
        $filename = sanitize_filename($file['name']);
    }
    
    $filepath = rtrim($destination, '/') . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'error' => null];
    }
    
    return ['success' => false, 'filename' => null, 'error' => 'Failed to save file'];
}

/**
 * Delete file securely
 * 
 * @param string $filepath Path to file
 * @return bool True if deleted, false otherwise
 */
function delete_file($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

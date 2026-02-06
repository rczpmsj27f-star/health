<?php
/**
 * Medication Icon Helper
 * PHP functions for rendering medication icons inline
 */

/**
 * Get SVG icon for medication type
 * @param string $iconType Icon type
 * @return array Array with 'svg' and 'supportsTwoColor' keys
 */
function getMedicationIconSVG($iconType = 'pill') {
    $icons = [
        'pill' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.22 11.29l7.07-7.07c2.68-2.68 7.02-2.68 9.7 0 2.68 2.68 2.68 7.02 0 9.7l-7.07 7.07c-2.68 2.68-7.02 2.68-9.7 0-2.68-2.68-2.68-7.02 0-9.7zM13 9c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1zm-2 2c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1zm-2 2c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z"/></svg>',
            'supportsTwoColors' => false
        ],
        'pill-small' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 11.29l5-5c1.8-1.8 4.7-1.8 6.5 0 1.8 1.8 1.8 4.7 0 6.5l-5 5c-1.8 1.8-4.7 1.8-6.5 0-1.8-1.8-1.8-4.7 0-6.5zM12 11c-.4 0-.7.3-.7.7s.3.7.7.7.7-.3.7-.7-.3-.7-.7-.7z"/></svg>',
            'supportsTwoColors' => false
        ],
        'pill-large' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 11.29l8.5-8.5c3-3 7.87-3 10.87 0 3 3 3 7.87 0 10.87l-8.5 8.5c-3 3-7.87 3-10.87 0-3-3-3-7.87 0-10.87zM14 9c-.6 0-1.1.5-1.1 1.1s.5 1.1 1.1 1.1 1.1-.5 1.1-1.1-.5-1.1-1.1-1.1zm-2.5 2.5c-.6 0-1.1.5-1.1 1.1s.5 1.1 1.1 1.1 1.1-.5 1.1-1.1-.5-1.1-1.1-1.1zm-2.5 2.5c-.6 0-1.1.5-1.1 1.1s.5 1.1 1.1 1.1 1.1-.5 1.1-1.1-.5-1.1-1.1-1.1z"/></svg>',
            'supportsTwoColors' => false
        ],
        'pill-round' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/></svg>',
            'supportsTwoColors' => false
        ],
        'pill-oval' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><ellipse cx="12" cy="12" rx="8" ry="6"/></svg>',
            'supportsTwoColors' => false
        ],
        'pill-oblong' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="8" width="12" height="8" rx="4" ry="4"/></svg>',
            'supportsTwoColors' => false
        ],
        'pill-rectangular' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="7" y="8" width="10" height="8" rx="1" ry="1"/></svg>',
            'supportsTwoColors' => false
        ],
        'pill-scored' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/><line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="1" opacity="0.6"/></svg>',
            'supportsTwoColors' => false
        ],

        'pill-half' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/><path class="secondary-color" d="M12 4 A8 8 0 0 1 12 20 Z" opacity="0.85"/></svg>',
            'supportsTwoColors' => true
        ],
        'capsule-half' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="0.5"><path d="M 4 12 A 4 4 0 0 1 8 8 L 12 8 L 12 16 L 8 16 A 4 4 0 0 1 4 12 Z" fill="currentColor"/><path class="secondary-color" d="M 12 8 L 16 8 A 4 4 0 0 1 20 12 A 4 4 0 0 1 16 16 L 12 16 Z" fill="currentColor"/></svg>',
            'supportsTwoColors' => true
        ],

        'capsule-small' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 11.29l5-5c1.8-1.8 4.7-1.8 6.5 0 1.8 1.8 1.8 4.7 0 6.5l-5 5c-1.8 1.8-4.7 1.8-6.5 0-1.8-1.8-1.8-4.7 0-6.5z"/></svg>',
            'supportsTwoColors' => false
        ],
        'capsule-large' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 11.29l8.5-8.5c3-3 7.87-3 10.87 0 3 3 3 7.87 0 10.87l-8.5 8.5c-3 3-7.87 3-10.87 0-3-3-3-7.87 0-10.87zM14 10L10 14l-1.41-1.41L12.59 8.59z"/></svg>',
            'supportsTwoColors' => false
        ],

        'liquid' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 2h6v6h-1V3H7v5H6V2zm11 4h1v2h-1V6zM7 20c-1.66 0-3-1.34-3-3l2-9h8l2 9c0 1.66-1.34 3-3 3H7zm10-6h-1v-3h-1v3h-1V9h3v5zm1 0h1v-1h-1v1zm0-2h1v-1h-1v1z"/></svg>',
            'supportsTwoColors' => false
        ],
        'injection' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 3l-3-3-1 1 1 1-4 4-3-3-2 2 3 3-8 8c-1.1 1.1-1.1 2.9 0 4s2.9 1.1 4 0l8-8 3 3 2-2-3-3 4-4 1 1 1-1z"/></svg>',
            'supportsTwoColors' => false
        ],
        'inhaler' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15 2h2v6h-2V2zm4 0h2v6h-2V2zM8 10h10v2H8v-2zm0 4h10v2H8v-2zm0 4h10v2H8v-2zm-3-8h2v10H5V10z"/></svg>',
            'supportsTwoColors' => false
        ],
        'drops' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c-1.1 0-2 .9-2 2 0 .74.4 1.38 1 1.72V8h2V5.72c.6-.34 1-.98 1-1.72 0-1.1-.9-2-2-2zm0 8c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/></svg>',
            'supportsTwoColors' => false
        ],
        'cream' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M7 6h10c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H7c-1.1 0-2-.9-2-2V8c0-1.1.9-2 2-2z"/><path d="M9 10h6v8H9z" fill="white" opacity="0.3"/></svg>',
            'supportsTwoColors' => false
        ],
        'patch' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>',
            'supportsTwoColors' => false
        ],
        'spray' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6v8h-2V4h-2v6H9V2zm3 10c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0 8c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z"/></svg>',
            'supportsTwoColors' => false
        ],
        'suppository' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C9.24 2 7 4.24 7 7v10c0 2.76 2.24 5 5 5s5-2.24 5-5V7c0-2.76-2.24-5-5-5zm0 18c-1.65 0-3-1.35-3-3V7c0-1.65 1.35-3 3-3s3 1.35 3 3v10c0 1.65-1.35 3-3 3z"/></svg>',
            'supportsTwoColors' => false
        ],
        'powder' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 8c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zM6 10c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 8c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 0c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zM6 18c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2z"/></svg>',
            'supportsTwoColors' => false
        ]
    ];
    
    return $icons[$iconType] ?? $icons['pill'];
}

/**
 * Render medication icon with color
 * @param string $iconType Icon type
 * @param string $color Hex color code
 * @param string $size CSS size (default: 20px)
 * @param string $secondaryColor Optional secondary color for two-tone icons
 * @return string HTML string
 */
function renderMedicationIcon($iconType = 'pill', $color = '#5b21b6', $size = '20px', $secondaryColor = null) {
    $iconData = getMedicationIconSVG($iconType);
    $svg = $iconData['svg'];
    
    // Replace currentColor with selected color
    $svg = str_replace('currentColor', $color, $svg);
    
    // Handle secondary color
    if (!empty($secondaryColor) && $iconData['supportsTwoColors']) {
        $svg = str_replace('class="secondary-color" fill="currentColor"', sprintf('fill="%s"', htmlspecialchars($secondaryColor)), $svg);
        $svg = str_replace('class="secondary-color"', sprintf('fill="%s"', htmlspecialchars($secondaryColor)), $svg);
    } else {
        $svg = preg_replace('/<path class="secondary-color"[^>]*\/?>/', '', $svg);
    }
    
    // Add black stroke to the entire SVG if not already present
    // This applies to all paths that don't override
    if (strpos($svg, 'stroke=') === false) {
        $svg = str_replace('<svg ', '<svg stroke="#000" stroke-width="0.5" ', $svg);
    }
    
    return sprintf(
        '<span class="med-icon-inline" style="width: %s; height: %s; display: inline-block; vertical-align: middle;">%s</span>',
        htmlspecialchars($size),
        htmlspecialchars($size),
        $svg
    );
}

/**
 * Format medication dose for display
 * @param float $doseAmount Dose amount
 * @param string $doseUnit Dose unit
 * @return string Formatted dose string
 */
function formatMedicationDose($doseAmount, $doseUnit) {
    // Remove trailing zeros and decimal point if not needed
    $formatted = rtrim(rtrim(number_format($doseAmount, 2, '.', ''), '0'), '.');
    return htmlspecialchars($formatted . ' ' . $doseUnit);
}


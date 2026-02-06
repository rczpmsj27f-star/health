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
            'supportsTwoColor' => false
        ],
        'capsule' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.22 11.29l7.07-7.07c2.68-2.68 7.02-2.68 9.7 0 2.68 2.68 2.68 7.02 0 9.7l-7.07 7.07c-2.68 2.68-7.02 2.68-9.7 0-2.68-2.68-2.68-7.02 0-9.7zM13.5 9.5L9.5 13.5l-1.41-1.41L12.09 8.09z"/></svg>',
            'supportsTwoColor' => true
        ],
        'round_pill' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/></svg>',
            'supportsTwoColor' => false
        ],
        'oval_pill' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><ellipse cx="12" cy="12" rx="8" ry="6"/></svg>',
            'supportsTwoColor' => false
        ],
        'oblong_pill' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="8" width="12" height="8" rx="4" ry="4"/></svg>',
            'supportsTwoColor' => false
        ],
        'rectangular_tablet' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="7" y="8" width="10" height="8" rx="1" ry="1"/></svg>',
            'supportsTwoColor' => false
        ],
        'scored_tablet' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/><line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="1" opacity="0.6"/></svg>',
            'supportsTwoColor' => false
        ],
        'small_round_pill' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="5"/></svg>',
            'supportsTwoColor' => false
        ],
        'large_capsule' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 11.29l8.5-8.5c3-3 7.87-3 10.87 0 3 3 3 7.87 0 10.87l-8.5 8.5c-3 3-7.87 3-10.87 0-3-3-3-7.87 0-10.87zM14 10L10 14l-1.41-1.41L12.59 8.59z"/></svg>',
            'supportsTwoColor' => true
        ],
        'two_tone_capsule' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.22 11.29l7.07-7.07c2.68-2.68 7.02-2.68 9.7 0 2.68 2.68 2.68 7.02 0 9.7l-7.07 7.07c-2.68 2.68-7.02 2.68-9.7 0-2.68-2.68-2.68-7.02 0-9.7z"/><path class="secondary-color" d="M11.29 4.22l-7.07 7.07c-2.68 2.68-2.68 7.02 0 9.7l7.07-7.07z" opacity="0.7"/></svg>',
            'supportsTwoColor' => true
        ],
        'liquid' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 2h6v6h-1V3H7v5H6V2zm11 4h1v2h-1V6zM7 20c-1.66 0-3-1.34-3-3l2-9h8l2 9c0 1.66-1.34 3-3 3H7zm10-6h-1v-3h-1v3h-1V9h3v5zm1 0h1v-1h-1v1zm0-2h1v-1h-1v1z"/></svg>',
            'supportsTwoColor' => false
        ],
        'injection' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 3l1 1-1 1-1.5-1.5L17 5l-3-3 1.5-1.5L14 0l1-1 1 1zm-9 5l-2 2-2-2-6 6 2 2-3 3 2 2 3-3 2 2 6-6-2-2 2-2-2-2z"/></svg>',
            'supportsTwoColor' => false
        ],
        'inhaler' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15 2h2v6h-2V2zm4 0h2v6h-2V2zM8 10h10v2H8v-2zm0 4h10v2H8v-2zm0 4h10v2H8v-2zm-3-8h2v10H5V10z"/></svg>',
            'supportsTwoColor' => false
        ],
        'drops' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c-1.1 0-2 .9-2 2 0 .74.4 1.38 1 1.72V8h2V5.72c.6-.34 1-.98 1-1.72 0-1.1-.9-2-2-2zm0 8c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/></svg>',
            'supportsTwoColor' => false
        ],
        'cream' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 20c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm6-5h2v2h-2v-2zm4 0h2v2h-2v-2zm-8 0h2v2h-2v-2zm4-3c-3.31 0-6 2.69-6 6h12c0-3.31-2.69-6-6-6zm-8-8h16v8H7V4z"/></svg>',
            'supportsTwoColor' => false
        ],
        'patch' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>',
            'supportsTwoColor' => false
        ],
        'spray' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6v8h-2V4h-2v6H9V2zm3 10c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0 8c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z"/></svg>',
            'supportsTwoColor' => false
        ],
        'suppository' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C9.24 2 7 4.24 7 7v10c0 2.76 2.24 5 5 5s5-2.24 5-5V7c0-2.76-2.24-5-5-5zm0 18c-1.65 0-3-1.35-3-3V7c0-1.65 1.35-3 3-3s3 1.35 3 3v10c0 1.65-1.35 3-3 3z"/></svg>',
            'supportsTwoColor' => false
        ],
        'powder' => [
            'svg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 8c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zM6 10c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 8c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 0c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zM6 18c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2z"/></svg>',
            'supportsTwoColor' => false
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
    $svg = str_replace('currentColor', $color, $svg);
    
    // If icon supports two colors and secondary color is provided
    if (!empty($secondaryColor) && $iconData['supportsTwoColor']) {
        $svg = str_replace('class="secondary-color"', 'fill="' . htmlspecialchars($secondaryColor) . '"', $svg);
    }
    
    return sprintf(
        '<span class="med-icon-inline" style="width: %s; height: %s; display: inline-block; vertical-align: middle;">%s</span>',
        htmlspecialchars($size),
        htmlspecialchars($size),
        $svg
    );
}

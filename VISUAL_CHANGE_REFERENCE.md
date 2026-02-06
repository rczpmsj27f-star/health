# Visual Change Reference

## Capsule-Half Icon Change

### Before (WRONG - Disconnected Shapes)
```svg
<svg viewBox="0 0 24 24" fill="none">
  <g stroke="#000" stroke-width="0.5">
    <path d="M6 12 C6 9.79 7.79 8 10 8 L12 8 L12 16 L10 16 C7.79 16 6 14.21 6 12 Z" fill="currentColor"/>
    <path class="secondary-color" d="M12 8 L14 8 C16.21 8 18 9.79 18 12 C18 14.21 16.21 16 14 16 L12 16 L12 8 Z"/>
  </g>
</svg>
```
Problem: Used cubic bezier curves (C) that created squarish shapes, not a proper capsule

### After (CORRECT - Proper Capsule with Arcs)
```svg
<svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="0.5">
  <path d="M 4 12 A 4 4 0 0 1 8 8 L 12 8 L 12 16 L 8 16 A 4 4 0 0 1 4 12 Z" fill="currentColor"/>
  <path class="secondary-color" d="M 12 8 L 16 8 A 4 4 0 0 1 20 12 A 4 4 0 0 1 16 16 L 12 16 Z" fill="currentColor"/>
</svg>
```
Solution: Uses arc commands (A 4 4 0 0 1) for proper rounded capsule ends

Key differences:
- Arc commands create smooth rounded ends
- Horizontal orientation (as required)
- Vertical split down the middle at x=12
- Both halves connect seamlessly
- Stroke moved from <g> tag to <svg> tag for better compatibility

---

## Injection Icon Change

### Before (Old Design)
```svg
<svg viewBox="0 0 24 24" fill="currentColor">
  <path d="M20 3l1 1-1 1-1.5-1.5L17 5l-3-3 1.5-1.5L14 0l1-1 1 1zm-9 5l-2 2-2-2-6 6 2 2-3 3 2 2 3-3 2 2 6-6-2-2 2-2-2-2z"/>
</svg>
```

### After (New Design)
```svg
<svg viewBox="0 0 24 24" fill="currentColor">
  <path d="M21 3l-3-3-1 1 1 1-4 4-3-3-2 2 3 3-8 8c-1.1 1.1-1.1 2.9 0 4s2.9 1.1 4 0l8-8 3 3 2-2-3-3 4-4 1 1 1-1z"/>
</svg>
```

Key differences:
- More recognizable syringe/needle shape
- Better diagonal orientation
- Clearer plunger representation

---

## renderMedicationIcon Function Change

### Before (Complex Regex Approach)
```php
// Add black stroke for visibility (avoid elements that already have stroke and secondary-color paths)
$svg = preg_replace('/<path(?![^>]*stroke)(?![^>]*class="secondary-color")/', '<path stroke="black" stroke-width="1.5"', $svg);
$svg = preg_replace('/<circle(?![^>]*stroke)/', '<circle stroke="black" stroke-width="1.5"', $svg);
$svg = preg_replace('/<rect(?![^>]*stroke)/', '<rect stroke="black" stroke-width="1.5"', $svg);
$svg = preg_replace('/<ellipse(?![^>]*stroke)/', '<ellipse stroke="black" stroke-width="1.5"', $svg);

// Handle secondary color for two-tone icons
if (!empty($secondaryColor) && $iconData['supportsTwoColors']) {
    $svg = str_replace('class="secondary-color"', 'fill="' . htmlspecialchars($secondaryColor) . '" stroke="black" stroke-width="1.5"', $svg);
}
```

Problems:
- Multiple regex operations (slow)
- Complex negative lookahead patterns
- Could cause duplicate stroke attributes
- Secondary color handling separate from stroke

### After (Simple SVG-Level Approach)
```php
// Handle secondary color
if (!empty($secondaryColor) && $iconData['supportsTwoColors']) {
    $svg = str_replace('class="secondary-color" fill="currentColor"', sprintf('fill="%s"', htmlspecialchars($secondaryColor)), $svg);
    $svg = str_replace('class="secondary-color"', sprintf('fill="%s"', htmlspecialchars($secondaryColor)), $svg);
} else {
    $svg = preg_replace('/<path class="secondary-color"[^>]*\/?>/', '', $svg);
}

// Add black stroke to the entire SVG if not already present
if (strpos($svg, 'stroke=') === false) {
    $svg = str_replace('<svg ', '<svg stroke="#000" stroke-width="0.5" ', $svg);
}
```

Benefits:
- Single check for stroke existence
- Single string replacement (fast)
- No complex regex patterns
- Applies to all child elements automatically
- Prevents duplicate attributes
- More maintainable code

---

## Theme CSS Change

### Before
```css
/* Dark Mode - Force Dark Theme */
body.theme-dark { /* dark variables */ }

/* Light Mode - Force Light Theme (uses default variables, no override needed) */
body.theme-light {
    /* Uses default light theme variables defined in :root */
}

/* Device Mode - Follow System Preference */
@media (prefers-color-scheme: dark) {
    body.theme-device { /* dark variables */ }
}
```

Problems:
- Light mode had no explicit variables
- Device mode used specific class
- Not following the :not() pattern

### After
```css
/* Light mode - explicit */
body.theme-light {
    --color-text: #333;
    --color-text-primary: #333;
    /* ... all light variables ... */
}

/* Dark mode - explicit */
body.theme-dark {
    --color-text: #e0e0e0;
    /* ... all dark variables ... */
}

/* Device mode - follows system preference */
@media (prefers-color-scheme: dark) {
    body:not(.theme-light):not(.theme-dark) {
        /* dark variables */
    }
}
```

Benefits:
- Light mode has explicit variables
- Device mode uses :not() selector (no class needed)
- More consistent pattern
- Works with or without JavaScript

---

## Preferences PHP Body Class Change

### Before
```php
<body class="<?php 
$themeMode = $preferences['theme_mode'] ?? 'device';
if ($themeMode === 'light') echo 'theme-light';
elseif ($themeMode === 'dark') echo 'theme-dark';
elseif ($themeMode === 'device') echo 'theme-device';
?>">
```

### After
```php
<body class="<?php 
$themeMode = $preferences['theme_mode'] ?? 'device';
if ($themeMode === 'light') echo 'theme-light';
elseif ($themeMode === 'dark') echo 'theme-dark';
// theme_mode === 'device' gets no class (uses media query)
?>">
```

Key difference:
- Device mode no longer adds a class
- Relies on CSS :not() selector and media query
- Cleaner, standards-compliant approach

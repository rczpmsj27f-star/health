# Medication Icon Enhancement - Quick Reference

## New Icon Shapes (8 Total)

1. **pill** - Standard pill/tablet (existing)
2. **capsule** - Standard capsule (existing, now supports two-tone)
3. **round_pill** - Simple circular pill
4. **oval_pill** - Elliptical/oval shaped pill
5. **oblong_pill** - Elongated pill with rounded ends
6. **rectangular_tablet** - Square/rectangular tablet
7. **scored_tablet** - Round pill with dividing line
8. **small_round_pill** - Smaller circular pill
9. **large_capsule** - Larger capsule (supports two-tone)
10. **two_tone_capsule** - Special capsule with gradient (supports two-tone)

Plus existing shapes: liquid, injection, inhaler, drops, cream, patch, spray, suppository, powder

## Color Palette (21 Colors)

### Light Colors (with borders for visibility)
- White (#FFFFFF)
- Off-White/Beige (#F5F5DC)
- Light Yellow (#FFFFE0)

### Primary Medication Colors
- Yellow (#FFD700)
- Pink (#FFB6C1)
- Light Pink (#FFC0CB)
- Blue (#4169E1)
- Light Blue (#ADD8E6)
- Green (#32CD32)
- Light Green (#90EE90)
- Red (#DC143C)
- Orange (#FF8C00)
- Purple (#9370DB)
- Brown (#A0522D)
- Gray (#808080)
- Light Gray (#D3D3D3)

### Dark Colors
- Dark Purple (#5b21b6) - Default
- Dark Blue (#2563eb)
- Dark Green (#16a34a)
- Teal (#0d9488)
- Indigo (#4f46e5)

## Usage Examples

### JavaScript
```javascript
// Single color icon
MedicationIcons.render('pill', '#FFD700', '24px');

// Two-color icon
MedicationIcons.render('two_tone_capsule', '#4169E1', '24px', '#FFB6C1');
```

### PHP
```php
// Single color icon
renderMedicationIcon('pill', '#FFD700', '20px');

// Two-color icon
renderMedicationIcon('two_tone_capsule', '#4169E1', '20px', '#FFB6C1');

// Using database values with null-coalescing
renderMedicationIcon(
    $med['icon'] ?? 'pill',
    $med['color'] ?? '#5b21b6',
    '20px',
    $med['secondary_color'] ?? null
);
```

## Form Behavior

### Icon Selection
- Grid of all available icons
- Click to select
- Selected icon highlighted with border
- Icon name shown below each icon

### Primary Color Selection
- Grid of color swatches
- Click to select
- Selected color shows checkmark
- No custom color picker

### Secondary Color Selection
- Only shown for icons that support two-tone (capsule, large_capsule, two_tone_capsule)
- Automatically appears/disappears when icon changes
- Same grid interface as primary color
- Optional (can be left unselected)

### Live Preview
- Shows selected icon with chosen colors
- Updates in real-time as selections change
- Displays at 48px size for visibility

## Database Schema

```sql
CREATE TABLE medications (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    icon VARCHAR(50) DEFAULT 'pill',
    color VARCHAR(7) DEFAULT '#5b21b6',
    secondary_color VARCHAR(7) DEFAULT NULL,  -- NEW FIELD
    -- ... other fields
);
```

## Migration Command

```bash
# Run from project root
php run_migration.php database/migrations/migration_add_secondary_color.sql
```

Or manually execute:
```sql
ALTER TABLE medications 
ADD COLUMN IF NOT EXISTS secondary_color VARCHAR(7) DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_medications_secondary_color 
ON medications(secondary_color);
```

## Views Updated

All medication display views now properly show custom icons:

1. **Dashboard** (`/modules/medications/dashboard.php`)
   - Today's medications
   - PRN medications
   - All instances use custom icons

2. **My Medications** (`/modules/medications/list.php`)
   - Scheduled medications
   - PRN medications
   - Archived medications

3. **Compliance Reports** (`/modules/medications/compliance.php`)
   - Daily view
   - Weekly view
   - Monthly view
   - Annual view
   - PRN tracking

## What Changed in Forms

### Before (Old Color Picker)
```html
<input type="color" value="#5b21b6" title="Custom Color">
```
- Technical color picker interface
- RGB values and pipette tool
- Not user-friendly for medication context

### After (Color Grid)
```html
<div class="color-grid">
    <div class="color-option" style="background-color: #FFFFFF;">
    <div class="color-option" style="background-color: #FFD700;">
    <!-- ... 21 total colors ... -->
</div>
```
- Visual grid of medication colors
- Click to select
- Checkmark shows selection
- Much more intuitive

## Browser Compatibility

- All modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive
- Touch-friendly on tablets/phones
- Progressive enhancement (falls back gracefully)

## Performance

- Icon rendering is lightweight (inline SVG)
- Color selection is instant (no server calls)
- CSS grid for efficient layout
- Minimal JavaScript for interactivity

## Accessibility

- High contrast colors
- Keyboard navigation support
- Screen reader friendly labels
- Touch targets meet WCAG guidelines (40px minimum)

## Future Enhancements (Not in Scope)

- Custom icon upload
- Pattern/texture support
- Icon rotation/orientation
- Animation effects
- Icon size variations in views

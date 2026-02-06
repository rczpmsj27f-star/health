/**
 * Medication Icon Library
 * SVG icons for different medication types
 */

const MedicationIcons = {
    // Available icon types
    icons: {
        'pill': {
            name: 'Pill/Tablet',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.22 11.29l7.07-7.07c2.68-2.68 7.02-2.68 9.7 0 2.68 2.68 2.68 7.02 0 9.7l-7.07 7.07c-2.68 2.68-7.02 2.68-9.7 0-2.68-2.68-2.68-7.02 0-9.7zM13 9c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1zm-2 2c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1zm-2 2c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z"/></svg>',
            supportsTwoColors: false
        },
        'pill-small': {
            name: 'Small Pill',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 11.29l5-5c1.8-1.8 4.7-1.8 6.5 0 1.8 1.8 1.8 4.7 0 6.5l-5 5c-1.8 1.8-4.7 1.8-6.5 0-1.8-1.8-1.8-4.7 0-6.5zM12 11c-.4 0-.7.3-.7.7s.3.7.7.7.7-.3.7-.7-.3-.7-.7-.7z"/></svg>',
            supportsTwoColors: false
        },
        'pill-large': {
            name: 'Large Pill',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 11.29l8.5-8.5c3-3 7.87-3 10.87 0 3 3 3 7.87 0 10.87l-8.5 8.5c-3 3-7.87 3-10.87 0-3-3-3-7.87 0-10.87zM14 9c-.6 0-1.1.5-1.1 1.1s.5 1.1 1.1 1.1 1.1-.5 1.1-1.1-.5-1.1-1.1-1.1zm-2.5 2.5c-.6 0-1.1.5-1.1 1.1s.5 1.1 1.1 1.1 1.1-.5 1.1-1.1-.5-1.1-1.1-1.1zm-2.5 2.5c-.6 0-1.1.5-1.1 1.1s.5 1.1 1.1 1.1 1.1-.5 1.1-1.1-.5-1.1-1.1-1.1z"/></svg>',
            supportsTwoColors: false
        },
        'pill-round': {
            name: 'Round Pill',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/></svg>',
            supportsTwoColors: false
        },
        'pill-oval': {
            name: 'Oval Pill',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><ellipse cx="12" cy="12" rx="8" ry="6"/></svg>',
            supportsTwoColors: false
        },
        'pill-oblong': {
            name: 'Oblong Tablet',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="8" width="12" height="8" rx="4" ry="4"/></svg>',
            supportsTwoColors: false
        },
        'pill-rectangular': {
            name: 'Rectangular Tablet',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="7" y="8" width="10" height="8" rx="1" ry="1"/></svg>',
            supportsTwoColors: false
        },
        'pill-scored': {
            name: 'Scored Tablet',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/><line x1="12" y1="4" x2="12" y2="20" stroke="white" stroke-width="1" opacity="0.6"/></svg>',
            supportsTwoColors: false
        },
        'pill-two-tone': {
            name: 'Two-Tone Pill',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.22 11.29l7.07-7.07c2.68-2.68 7.02-2.68 9.7 0 2.68 2.68 2.68 7.02 0 9.7l-7.07 7.07c-2.68 2.68-7.02 2.68-9.7 0-2.68-2.68-2.68-7.02 0-9.7z"/><path class="secondary-color" d="M11.29 4.22l-7.07 7.07 7.07 7.07z" opacity="0.85"/></svg>',
            supportsTwoColors: true
        },
        'pill-half': {
            name: 'Half-and-Half Pill',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/><path class="secondary-color" d="M12 4 A8 8 0 0 1 12 20 Z" opacity="0.85"/></svg>',
            supportsTwoColors: true
        },
        'capsule': {
            name: 'Capsule',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.22 11.29l7.07-7.07c2.68-2.68 7.02-2.68 9.7 0 2.68 2.68 2.68 7.02 0 9.7l-7.07 7.07c-2.68 2.68-7.02 2.68-9.7 0-2.68-2.68-2.68-7.02 0-9.7z"/><path class="secondary-color" d="M11.29 4.22l-7.07 7.07 7.07 7.07z" opacity="0.85"/></svg>',
            supportsTwoColors: true
        },
        'capsule-small': {
            name: 'Small Capsule',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 11.29l5-5c1.8-1.8 4.7-1.8 6.5 0 1.8 1.8 1.8 4.7 0 6.5l-5 5c-1.8 1.8-4.7 1.8-6.5 0-1.8-1.8-1.8-4.7 0-6.5z"/></svg>',
            supportsTwoColors: false
        },
        'capsule-large': {
            name: 'Large Capsule',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 11.29l8.5-8.5c3-3 7.87-3 10.87 0 3 3 3 7.87 0 10.87l-8.5 8.5c-3 3-7.87 3-10.87 0-3-3-3-7.87 0-10.87zM14 10L10 14l-1.41-1.41L12.59 8.59z"/></svg>',
            supportsTwoColors: false
        },
        'capsule-two-tone': {
            name: 'Two-Tone Capsule',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.22 11.29l7.07-7.07c2.68-2.68 7.02-2.68 9.7 0 2.68 2.68 2.68 7.02 0 9.7l-7.07 7.07c-2.68 2.68-7.02 2.68-9.7 0-2.68-2.68-2.68-7.02 0-9.7z"/><path class="secondary-color" d="M11.29 4.22l-7.07 7.07c-2.68 2.68-2.68 7.02 0 9.7l7.07-7.07z" opacity="0.85"/></svg>',
            supportsTwoColors: true
        },
        'liquid': {
            name: 'Liquid/Syrup',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 2h6v6h-1V3H7v5H6V2zm11 4h1v2h-1V6zM7 20c-1.66 0-3-1.34-3-3l2-9h8l2 9c0 1.66-1.34 3-3 3H7zm10-6h-1v-3h-1v3h-1V9h3v5zm1 0h1v-1h-1v1zm0-2h1v-1h-1v1z"/></svg>',
            supportsTwoColors: false
        },
        'injection': {
            name: 'Injection/Syringe',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 3l1 1-1 1-1.5-1.5L17 5l-3-3 1.5-1.5L14 0l1-1 1 1zm-9 5l-2 2-2-2-6 6 2 2-3 3 2 2 3-3 2 2 6-6-2-2 2-2-2-2z"/></svg>',
            supportsTwoColors: false
        },
        'inhaler': {
            name: 'Inhaler',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15 2h2v6h-2V2zm4 0h2v6h-2V2zM8 10h10v2H8v-2zm0 4h10v2H8v-2zm0 4h10v2H8v-2zm-3-8h2v10H5V10z"/></svg>',
            supportsTwoColors: false
        },
        'drops': {
            name: 'Eye/Ear Drops',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c-1.1 0-2 .9-2 2 0 .74.4 1.38 1 1.72V8h2V5.72c.6-.34 1-.98 1-1.72 0-1.1-.9-2-2-2zm0 8c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/></svg>',
            supportsTwoColors: false
        },
        'cream': {
            name: 'Cream/Ointment',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 20c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm6-5h2v2h-2v-2zm4 0h2v2h-2v-2zm-8 0h2v2h-2v-2zm4-3c-3.31 0-6 2.69-6 6h12c0-3.31-2.69-6-6-6zm-8-8h16v8H7V4z"/></svg>',
            supportsTwoColors: false
        },
        'patch': {
            name: 'Patch',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>',
            supportsTwoColors: false
        },
        'spray': {
            name: 'Nasal Spray',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6v8h-2V4h-2v6H9V2zm3 10c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0 8c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z"/></svg>',
            supportsTwoColors: false
        },
        'suppository': {
            name: 'Suppository',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C9.24 2 7 4.24 7 7v10c0 2.76 2.24 5 5 5s5-2.24 5-5V7c0-2.76-2.24-5-5-5zm0 18c-1.65 0-3-1.35-3-3V7c0-1.65 1.35-3 3-3s3 1.35 3 3v10c0 1.65-1.35 3-3 3z"/></svg>',
            supportsTwoColors: false
        },
        'powder': {
            name: 'Powder/Granules',
            svg: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 8c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zM6 10c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 8c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 0c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zM6 18c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2z"/></svg>',
            supportsTwoColors: false
        }
    },

    // Color palette - Common medication colors (~25 colors)
    colors: [
        // Light/Neutral Colors
        { name: 'White', value: '#FFFFFF' },
        { name: 'Off-White/Beige', value: '#F5F5DC' },
        { name: 'Cream', value: '#FFFDD0' },
        { name: 'Light Gray', value: '#D3D3D3' },
        { name: 'Gray', value: '#808080' },
        // Pastel/Light Variants
        { name: 'Light Pink', value: '#FFB6C1' },
        { name: 'Light Blue', value: '#ADD8E6' },
        { name: 'Light Yellow', value: '#FFFACD' },
        { name: 'Light Green', value: '#90EE90' },
        { name: 'Peach', value: '#FFDAB9' },
        // Vibrant Colors
        { name: 'Pink', value: '#FF69B4' },
        { name: 'Red', value: '#DC2626' },
        { name: 'Orange', value: '#FF8C00' },
        { name: 'Yellow', value: '#FFD700' },
        { name: 'Green', value: '#16A34A' },
        { name: 'Blue', value: '#2563EB' },
        { name: 'Purple', value: '#9370DB' },
        { name: 'Teal', value: '#0D9488' },
        { name: 'Indigo', value: '#4F46E5' },
        // Dark Colors
        { name: 'Brown', value: '#8B4513' },
        { name: 'Dark Gray', value: '#696969' },
        { name: 'Black', value: '#000000' }
    ],

    /**
     * Render icon with color
     * @param {string} iconType Icon type key
     * @param {string} color Hex color code
     * @param {string} size CSS size (default: 24px)
     * @param {string} secondaryColor Optional secondary color for two-tone icons
     * @returns {string} HTML string
     */
    render: function(iconType, color = '#5b21b6', size = '24px', secondaryColor = null) {
        const icon = this.icons[iconType] || this.icons.pill;
        let svg = icon.svg.replace('currentColor', color);
        
        // If icon supports two colors and secondary color is provided
        if (secondaryColor && icon.supportsTwoColors) {
            svg = svg.replace('class="secondary-color"', `fill="${secondaryColor}"`);
        }
        
        return `<span class="med-icon" style="width: ${size}; height: ${size}; display: inline-block;">${svg}</span>`;
    },

    /**
     * Create icon selector HTML
     * @param {string} selectedIcon Currently selected icon
     * @param {string} selectedColor Currently selected color
     * @param {string} selectedSecondaryColor Currently selected secondary color
     * @returns {string} HTML string
     */
    createSelector: function(selectedIcon = 'pill', selectedColor = '#5b21b6', selectedSecondaryColor = '') {
        let html = '<div class="icon-selector">';
        html += '<label>Medication Icon</label>';
        html += '<div class="icon-grid">';
        
        Object.keys(this.icons).forEach(key => {
            const icon = this.icons[key];
            const isSelected = key === selectedIcon ? 'selected' : '';
            html += `
                <div class="icon-option ${isSelected}" data-icon="${key}" title="${icon.name}">
                    ${icon.svg}
                    <span class="icon-name">${icon.name}</span>
                </div>
            `;
        });
        
        html += '</div>';
        html += `<input type="hidden" name="medication_icon" id="medication_icon" value="${selectedIcon}">`;
        html += '</div>';
        
        html += '<div class="color-selector">';
        html += '<label>Primary Color</label>';
        html += '<div class="color-grid">';
        
        this.colors.forEach(color => {
            const isSelected = color.value === selectedColor ? 'selected' : '';
            html += `
                <div class="color-option ${isSelected}" 
                     data-color="${color.value}" 
                     title="${color.name}"
                     style="background-color: ${color.value}; ${color.value === '#FFFFFF' || color.value === '#FFFFE0' || color.value === '#F5F5DC' ? 'border: 1px solid #ccc;' : ''}">
                </div>
            `;
        });
        
        html += '</div>';
        html += `<input type="hidden" name="medication_color" id="medication_color" value="${selectedColor}">`;
        html += '</div>';
        
        html += '<div class="color-selector" id="secondary-color-selector" style="display: none;">';
        html += '<label>Secondary Color (for two-tone medications)</label>';
        html += '<div class="color-grid" id="secondary-color-grid">';
        
        this.colors.forEach(color => {
            const isSelected = color.value === selectedSecondaryColor ? 'selected' : '';
            html += `
                <div class="secondary-color-option ${isSelected}" 
                     data-color="${color.value}" 
                     title="${color.name}"
                     style="background-color: ${color.value}; ${color.value === '#FFFFFF' || color.value === '#FFFFE0' || color.value === '#F5F5DC' ? 'border: 1px solid #ccc;' : ''}">
                </div>
            `;
        });
        
        html += '</div>';
        html += `<input type="hidden" name="secondary_color" id="secondary_color" value="${selectedSecondaryColor}">`;
        html += '</div>';
        
        return html;
    }
};

// Initialize icon/color selector functionality
function initMedicationIconSelector() {
    // Icon selection
    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            const iconKey = this.dataset.icon;
            document.getElementById('medication_icon').value = iconKey;
            
            // Show/hide secondary color selector based on icon type
            const icon = MedicationIcons.icons[iconKey];
            const secondaryColorSelector = document.getElementById('secondary-color-selector');
            if (secondaryColorSelector) {
                if (icon && icon.supportsTwoColors) {
                    secondaryColorSelector.style.display = 'block';
                } else {
                    secondaryColorSelector.style.display = 'none';
                    document.getElementById('secondary_color').value = '';
                }
            }
            
            updateIconPreview();
        });
    });

    // Color selection
    document.querySelectorAll('.color-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('medication_color').value = this.dataset.color;
            updateIconPreview();
        });
    });

    // Secondary color selection
    document.querySelectorAll('.secondary-color-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.secondary-color-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('secondary_color').value = this.dataset.color;
            updateIconPreview();
        });
    });
}

function updateIconPreview() {
    const iconType = document.getElementById('medication_icon')?.value || 'pill';
    const color = document.getElementById('medication_color')?.value || '#5b21b6';
    const secondaryColor = document.getElementById('secondary_color')?.value || null;
    const preview = document.getElementById('icon_preview');
    
    if (preview) {
        preview.innerHTML = MedicationIcons.render(iconType, color, '48px', secondaryColor);
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MedicationIcons;
}

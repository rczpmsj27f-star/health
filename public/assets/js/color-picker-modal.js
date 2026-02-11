/**
 * Color Picker Modal Component
 * Provides a large, mobile-friendly color picker interface
 */

const ColorPickerModal = {
    // Extended color palette organized by hue (60+ colors)
    colorPalette: [
        // Reds & Pinks (Row 1)
        { name: 'Light Pink', value: '#FFB6C1' },
        { name: 'Pink', value: '#FF69B4' },
        { name: 'Hot Pink', value: '#FF1493' },
        { name: 'Deep Pink', value: '#FF0080' },
        { name: 'Crimson', value: '#DC143C' },
        { name: 'Red', value: '#DC2626' },
        { name: 'Dark Red', value: '#8B0000' },
        { name: 'Maroon', value: '#800000' },
        
        // Oranges & Peaches (Row 2)
        { name: 'Peach', value: '#FFDAB9' },
        { name: 'Light Coral', value: '#F08080' },
        { name: 'Coral', value: '#FF7F50' },
        { name: 'Tomato', value: '#FF6347' },
        { name: 'Orange Red', value: '#FF4500' },
        { name: 'Orange', value: '#FF8C00' },
        { name: 'Dark Orange', value: '#FF8800' },
        { name: 'Burnt Orange', value: '#CC5500' },
        
        // Yellows & Golds (Row 3)
        { name: 'Cream', value: '#FFFDD0' },
        { name: 'Light Yellow', value: '#FFFACD' },
        { name: 'Lemon', value: '#FFF44F' },
        { name: 'Yellow', value: '#FFFF00' },
        { name: 'Gold', value: '#FFD700' },
        { name: 'Goldenrod', value: '#DAA520' },
        { name: 'Dark Goldenrod', value: '#B8860B' },
        { name: 'Olive', value: '#808000' },
        
        // Greens (Row 4)
        { name: 'Light Green', value: '#90EE90' },
        { name: 'Lime Green', value: '#32CD32' },
        { name: 'Lime', value: '#00FF00' },
        { name: 'Green', value: '#16A34A' },
        { name: 'Forest Green', value: '#228B22' },
        { name: 'Dark Green', value: '#006400' },
        { name: 'Olive Drab', value: '#6B8E23' },
        { name: 'Dark Olive', value: '#556B2F' },
        
        // Teals & Cyans (Row 5)
        { name: 'Aquamarine', value: '#7FFFD4' },
        { name: 'Turquoise', value: '#40E0D0' },
        { name: 'Cyan', value: '#00FFFF' },
        { name: 'Teal', value: '#0D9488' },
        { name: 'Dark Cyan', value: '#008B8B' },
        { name: 'Deep Teal', value: '#014D4E' },
        { name: 'Sea Green', value: '#2E8B57' },
        { name: 'Dark Sea Green', value: '#8FBC8F' },
        
        // Blues (Row 6)
        { name: 'Light Blue', value: '#ADD8E6' },
        { name: 'Sky Blue', value: '#87CEEB' },
        { name: 'Deep Sky Blue', value: '#00BFFF' },
        { name: 'Dodger Blue', value: '#1E90FF' },
        { name: 'Blue', value: '#2563EB' },
        { name: 'Royal Blue', value: '#4169E1' },
        { name: 'Dark Blue', value: '#00008B' },
        { name: 'Navy', value: '#000080' },
        
        // Purples & Violets (Row 7)
        { name: 'Lavender', value: '#E6E6FA' },
        { name: 'Plum', value: '#DDA0DD' },
        { name: 'Violet', value: '#EE82EE' },
        { name: 'Purple', value: '#800080' },
        { name: 'Medium Purple', value: '#9370DB' },
        { name: 'Dark Purple', value: '#5b21b6' },
        { name: 'Indigo', value: '#4F46E5' },
        { name: 'Dark Indigo', value: '#4B0082' },
        
        // Browns & Tans (Row 8)
        { name: 'Off-White/Beige', value: '#F5F5DC' },
        { name: 'Tan', value: '#D2B48C' },
        { name: 'Peru', value: '#CD853F' },
        { name: 'Sienna', value: '#A0522D' },
        { name: 'Brown', value: '#A52A2A' },
        { name: 'Saddle Brown', value: '#8B4513' },
        { name: 'Chocolate', value: '#D2691E' },
        { name: 'Dark Brown', value: '#654321' },
        
        // Grays & Neutrals (Row 9)
        { name: 'White', value: '#FFFFFF' },
        { name: 'Gainsboro', value: '#DCDCDC' },
        { name: 'Light Gray', value: '#D3D3D3' },
        { name: 'Silver', value: '#C0C0C0' },
        { name: 'Gray', value: '#808080' },
        { name: 'Dark Gray', value: '#A9A9A9' },
        { name: 'Dim Gray', value: '#696969' },
        { name: 'Black', value: '#000000' }
    ],

    currentCallback: null,
    currentValue: '#5b21b6',

    /**
     * Initialize the color picker modal
     */
    init: function() {
        // Create modal HTML if it doesn't exist
        if (!document.getElementById('color-picker-modal')) {
            const modalHTML = `
                <div id="color-picker-modal" class="color-modal" style="display: none;">
                    <div class="color-modal-overlay" onclick="ColorPickerModal.close()"></div>
                    <div class="color-modal-content">
                        <div class="color-modal-header">
                            <h3>Choose Color</h3>
                            <button type="button" class="color-modal-close" onclick="ColorPickerModal.close()">&times;</button>
                        </div>
                        <div class="color-modal-body">
                            <div class="color-palette-grid" id="color-palette-grid"></div>
                            <div class="color-hex-input-group">
                                <label for="color-hex-input">Custom Hex Color:</label>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="text" id="color-hex-input" placeholder="#000000" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                    <div id="color-hex-preview" style="width: 40px; height: 40px; border: 2px solid #ccc; border-radius: 4px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="color-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="ColorPickerModal.close()">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="ColorPickerModal.apply()">Apply Color</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Populate color grid
            this.populateColorGrid();
            
            // Setup hex input handler
            const hexInput = document.getElementById('color-hex-input');
            const hexPreview = document.getElementById('color-hex-preview');
            hexInput.addEventListener('input', function(e) {
                let value = e.target.value;
                // Auto-add # if missing
                if (value && !value.startsWith('#')) {
                    value = '#' + value;
                    e.target.value = value;
                }
                // Update preview if valid
                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    hexPreview.style.backgroundColor = value;
                    ColorPickerModal.currentValue = value;
                }
            });
        }
    },

    /**
     * Populate the color grid with all colors
     */
    populateColorGrid: function() {
        const grid = document.getElementById('color-palette-grid');
        grid.innerHTML = '';
        
        this.colorPalette.forEach(color => {
            const colorOption = document.createElement('div');
            colorOption.className = 'color-palette-option';
            colorOption.title = color.name;
            colorOption.style.backgroundColor = color.value;
            colorOption.dataset.color = color.value;
            
            // Add border for light colors
            if (color.value === '#FFFFFF' || color.value === '#FFFDD0' || color.value === '#F5F5DC') {
                colorOption.style.border = '2px solid #ccc';
            }
            
            colorOption.addEventListener('click', function() {
                // Remove selection from all
                document.querySelectorAll('.color-palette-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                // Select this one
                this.classList.add('selected');
                ColorPickerModal.currentValue = this.dataset.color;
                // Update hex input
                document.getElementById('color-hex-input').value = this.dataset.color;
                document.getElementById('color-hex-preview').style.backgroundColor = this.dataset.color;
            });
            
            grid.appendChild(colorOption);
        });
    },

    /**
     * Open the modal
     * @param {string} currentColor - The currently selected color
     * @param {function} callback - Function to call when color is applied
     */
    open: function(currentColor, callback) {
        this.init();
        this.currentValue = currentColor || '#5b21b6';
        this.currentCallback = callback;
        
        // Update hex input and preview
        document.getElementById('color-hex-input').value = this.currentValue;
        document.getElementById('color-hex-preview').style.backgroundColor = this.currentValue;
        
        // Select the current color in grid if it exists
        document.querySelectorAll('.color-palette-option').forEach(opt => {
            opt.classList.remove('selected');
            if (opt.dataset.color.toLowerCase() === this.currentValue.toLowerCase()) {
                opt.classList.add('selected');
            }
        });
        
        // Show modal
        const modal = document.getElementById('color-picker-modal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent body scroll
    },

    /**
     * Close the modal
     */
    close: function() {
        const modal = document.getElementById('color-picker-modal');
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restore body scroll
    },

    /**
     * Apply the selected color
     */
    apply: function() {
        if (this.currentCallback && typeof this.currentCallback === 'function') {
            this.currentCallback(this.currentValue);
        }
        this.close();
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ColorPickerModal;
}

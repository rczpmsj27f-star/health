<?php 
session_start();
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$isAdmin = Auth::isAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medication</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .unified-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 80px 16px 40px 16px;
        }
        
        .form-section {
            margin-bottom: 32px;
            padding: 24px;
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
        }
        
        .form-section-title {
            background: var(--color-primary);
            color: var(--color-bg-white);
            padding: 12px 20px;
            margin: -24px -24px 20px -24px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            font-weight: 600;
            font-size: 18px;
        }
        
        .number-stepper {
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 200px;
        }
        
        .number-stepper input {
            flex: 1;
            text-align: center;
            background: var(--color-bg-gray);
        }
        
        .stepper-btn {
            width: 40px;
            height: 40px;
            border: 2px solid var(--color-primary);
            background: var(--color-bg-white);
            color: var(--color-primary);
            font-size: 24px;
            font-weight: bold;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        
        .stepper-btn:hover {
            background: var(--color-primary);
            color: var(--color-bg-white);
        }
        
        .stepper-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div class="unified-form-container">
        <div style="text-align: center; margin-bottom: 32px;">
            <h2 style="color: var(--color-primary); font-size: 32px; margin: 0 0 8px 0;">💊 Add New Medication</h2>
            <p style="color: var(--color-text-secondary); margin: 0;">Complete all fields to add your medication</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #f5c6cb;">
                <strong>Error:</strong> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="/modules/medications/add_unified_handler.php" id="medForm">
            <!-- Section 1: Medication Search -->
            <div class="form-section">
                <div class="form-section-title">1. Medication Information</div>
                
                <div class="form-group">
                    <label>Medication Name *</label>
                    <input type="text" name="med_name" id="med_name" autocomplete="off" placeholder="Enter medication name" required>
                </div>

                <input type="hidden" name="nhs_med_id" id="selected_med_id">

                <!-- Icon and Color Selection -->
                <div id="icon-color-section">
                    <!-- Icon Selector - Collapsible -->
                    <div class="icon-selector">
                        <label style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Medication Icon</span>
                            <button type="button" class="btn-toggle-icons" onclick="toggleIconGrid()" id="toggle-icon-btn">
                                Choose Icon ▼
                            </button>
                        </label>
                        <div class="icon-grid" id="icon-grid" style="display: none; margin-top: 12px;"></div>
                        <input type="hidden" name="medication_icon" id="medication_icon" value="pill">
                        
                        <!-- Selected icon preview -->
                        <div id="selected-icon-display" style="margin-top: 12px; padding: 12px; background: var(--color-bg-light); border-radius: var(--radius-sm); text-align: center;">
                            <strong>Selected:</strong> <span id="selected-icon-name">Pill/Tablet</span>
                        </div>
                    </div>

                    <!-- Primary Color Selector -->
                    <div class="color-selector">
                        <label style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Primary Color</span>
                            <button type="button" class="btn-toggle-icons" onclick="togglePrimaryColorGrid()" id="toggle-primary-color-btn">
                                Choose Color ▼
                            </button>
                        </label>
                        <div class="color-grid" id="color-grid" style="display: none; margin-top: 12px;"></div>
                        <input type="hidden" name="medication_color" id="medication_color" value="#5b21b6">
                        <button type="button" class="custom-color-btn" onclick="openPrimaryColorModal()" style="display: none;" id="custom-primary-color-btn">
                            Custom Color...
                        </button>
                    </div>

                    <!-- Secondary Color Selector (conditional) -->
                    <div class="color-selector" id="secondary-color-selector" style="display: none;">
                        <label style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Secondary Color (for two-tone effect)</span>
                            <button type="button" class="btn-toggle-icons" onclick="toggleSecondaryColorGrid()" id="toggle-secondary-color-btn">
                                Choose Color ▼
                            </button>
                        </label>
                        <div class="color-grid" id="secondary-color-grid" style="display: none; margin-top: 12px;"></div>
                        <input type="hidden" name="secondary_color" id="secondary_color" value="">
                        <button type="button" class="custom-color-btn" onclick="openSecondaryColorModal()" style="display: none;" id="custom-secondary-color-btn">
                            Custom Color...
                        </button>
                    </div>

                    <!-- Preview Section -->
                    <div id="icon_preview" style="margin-top: 20px;">
                        <div style="text-align: center; margin-bottom: 8px;">
                            <strong>Preview:</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Dosage -->
            <div class="form-section">
                <div class="form-section-title">2. Dosage Information</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Dose Amount *</label>
                        <input type="number" step="0.01" name="dose_amount" placeholder="e.g., 500" required>
                    </div>

                    <div class="form-group">
                        <label>Unit *</label>
                        <select name="dose_unit" required>
                            <option value="">Select unit...</option>
                            <option value="mg">mg (milligrams)</option>
                            <option value="ml">ml (milliliters)</option>
                            <option value="tablet">tablet(s)</option>
                            <option value="capsule">capsule(s)</option>
                            <option value="g">g (grams)</option>
                            <option value="mcg">mcg (micrograms)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Schedule -->
            <div class="form-section">
                <div class="form-section-title">3. Medication Schedule</div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_prn" id="is_prn" value="1" onchange="togglePRN()" style="width: auto; margin: 0;">
                        <span>💊 As and when needed (PRN)</span>
                    </label>
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                        Check this if you take this medication only when needed, not on a regular schedule
                    </small>
                </div>

                <div id="prn-schedule" style="display: none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tablets per dose (initial)</label>
                            <input type="number" name="initial_dose" id="initial_dose" min="1" max="10" value="1" placeholder="e.g., 2">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Recommended number of tablets for the first dose (e.g., 2 paracetamol tablets). This will be the default when logging, but you can adjust it each time.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Tablets per dose (subsequent)</label>
                            <input type="number" name="subsequent_dose" id="subsequent_dose" min="1" max="10" value="1" placeholder="e.g., 2">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Recommended number of tablets for follow-up doses (e.g., 2 paracetamol tablets). This will be the default when logging, but you can adjust it each time.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Maximum doses per day</label>
                            <input type="number" name="max_doses_per_day" id="max_doses_per_day" min="1" max="24" placeholder="e.g., 4">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Maximum number of times you can take this per day
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Minimum hours between doses</label>
                            <input type="number" step="0.5" name="min_hours_between_doses" id="min_hours_between_doses" min="0.5" max="24" placeholder="e.g., 4">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Minimum time required between doses (e.g., 4 or 4.5)
                            </small>
                        </div>
                    </div>
                </div>

                <div id="regular-schedule">
                    <div class="form-group">
                        <label>Frequency *</label>
                        <select name="frequency_type" id="freq" onchange="updateScheduleUI()" required>
                            <option value="per_day">Times per day</option>
                            <option value="per_week">Times per week</option>
                        </select>
                    </div>

                    <div id="daily">
                    <div class="form-group">
                        <label>Times per day *</label>
                        <div class="number-stepper">
                            <button type="button" class="stepper-btn" onclick="decrementTimesPerDay()">−</button>
                            <input type="number" name="times_per_day" id="times_per_day" min="1" max="6" value="1" readonly onchange="updateTimeInputs()">
                            <button type="button" class="stepper-btn" onclick="incrementTimesPerDay()">+</button>
                        </div>
                    </div>
                    
                    <div id="time_inputs_container">
                        <!-- Time inputs will be dynamically generated here -->
                    </div>
                </div>

                <div id="weekly" style="display:none;">
                    <div class="form-group">
                        <label>Times per week *</label>
                        <input type="number" name="times_per_week" min="1" max="7" value="1">
                    </div>

                    <div class="form-group">
                        <label>Days of Week</label>
                        <div class="day-toggle-container">
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Mon">
                                <span class="day-toggle-btn">Mon</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Tue">
                                <span class="day-toggle-btn">Tue</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Wed">
                                <span class="day-toggle-btn">Wed</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Thu">
                                <span class="day-toggle-btn">Thu</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Fri">
                                <span class="day-toggle-btn">Fri</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Sat">
                                <span class="day-toggle-btn">Sat</span>
                            </label>
                            <label class="day-toggle">
                                <input type="checkbox" name="days_of_week[]" value="Sun">
                                <span class="day-toggle-btn">Sun</span>
                            </label>
                        </div>
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            Select the days when this medication should be taken
                        </small>
                    </div>
                </div>
                </div>
            </div>

            <!-- Section 4: Stock & Expiry -->
            <div class="form-section">
                <div class="form-section-title">4. Stock & Expiry Information</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Current Stock (optional)</label>
                        <input type="number" name="current_stock" min="0" placeholder="e.g., 30 tablets">
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            How many tablets/doses do you currently have?
                        </small>
                    </div>

                    <div class="form-group">
                        <label>End Date (optional)</label>
                        <input type="date" name="end_date">
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            When will you stop taking this medication?
                        </small>
                    </div>
                </div>
            </div>

            <!-- Section 5: Instructions -->
            <div class="form-section">
                <div class="form-section-title">5. Special Instructions</div>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take with water">
                        💧 Take with water
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take on empty stomach">
                        🍽️ Take on empty stomach
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take with food">
                        🍴 Take with food
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Do not crush or chew">
                        💊 Do not crush or chew
                    </label>
                </div>

                <div class="form-group">
                    <label>Other Instructions (optional)</label>
                    <textarea name="other_instruction" rows="3" placeholder="Enter any additional instructions..."></textarea>
                </div>
            </div>

            <!-- Section 6: Condition -->
            <div class="form-section" style="display: none;">
                <div class="form-section-title">6. Condition Being Treated</div>
                
                <div class="form-group">
                    <label>Condition Name *</label>
                    <input type="text" name="condition_name" id="condition_name" autocomplete="off" placeholder="e.g., High blood pressure">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" type="submit">✅ Add Medication</button>
                <a class="btn btn-secondary" href="/modules/medications/list.php">❌ Cancel</a>
            </div>
        </form>
    </div>

    <script src="/assets/js/medication-icons.js"></script>
    <script src="/assets/js/color-picker-modal.js"></script>
    <script src="/assets/js/form-protection.js"></script>
    <script>
        // Toggle icon grid visibility
        function toggleIconGrid() {
            const iconGrid = document.getElementById('icon-grid');
            const toggleBtn = document.getElementById('toggle-icon-btn');
            
            if (iconGrid.style.display === 'none' || iconGrid.style.display === '') {
                iconGrid.style.display = 'grid';
                toggleBtn.textContent = 'Hide Icons ▲';
            } else {
                iconGrid.style.display = 'none';
                toggleBtn.textContent = 'Choose Icon ▼';
            }
        }

        // Toggle primary color grid visibility
        function togglePrimaryColorGrid() {
            const colorGrid = document.getElementById('color-grid');
            const toggleBtn = document.getElementById('toggle-primary-color-btn');
            const customBtn = document.getElementById('custom-primary-color-btn');
            
            if (colorGrid.style.display === 'none' || colorGrid.style.display === '') {
                colorGrid.style.display = 'flex';
                customBtn.style.display = 'block';
                toggleBtn.textContent = 'Hide Colors ▲';
            } else {
                colorGrid.style.display = 'none';
                customBtn.style.display = 'none';
                toggleBtn.textContent = 'Choose Color ▼';
            }
        }

        // Toggle secondary color grid visibility
        function toggleSecondaryColorGrid() {
            const colorGrid = document.getElementById('secondary-color-grid');
            const toggleBtn = document.getElementById('toggle-secondary-color-btn');
            const customBtn = document.getElementById('custom-secondary-color-btn');
            
            if (colorGrid.style.display === 'none' || colorGrid.style.display === '') {
                colorGrid.style.display = 'flex';
                customBtn.style.display = 'block';
                toggleBtn.textContent = 'Hide Colors ▲';
            } else {
                colorGrid.style.display = 'none';
                customBtn.style.display = 'none';
                toggleBtn.textContent = 'Choose Color ▼';
            }
        }

        // Open color modal for primary color
        function openPrimaryColorModal() {
            const currentColor = document.getElementById('medication_color').value;
            ColorPickerModal.open(currentColor, function(selectedColor) {
                document.getElementById('medication_color').value = selectedColor;
                // Remove selection from grid colors
                document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
                updateIconPreview();
            });
        }

        // Open color modal for secondary color
        function openSecondaryColorModal() {
            const currentColor = document.getElementById('secondary_color').value || '#5b21b6';
            ColorPickerModal.open(currentColor, function(selectedColor) {
                document.getElementById('secondary_color').value = selectedColor;
                // Remove selection from grid colors
                document.querySelectorAll('.secondary-color-option').forEach(o => o.classList.remove('selected'));
                updateIconPreview();
            });
        }

        // Initialize medication icon selector
        document.addEventListener('DOMContentLoaded', function() {
            // Populate icon grid
            const iconGrid = document.getElementById('icon-grid');
            Object.keys(MedicationIcons.icons).forEach(key => {
                const icon = MedicationIcons.icons[key];
                const div = document.createElement('div');
                div.className = 'icon-option' + (key === 'pill' ? ' selected' : '');
                div.dataset.icon = key;
                div.title = icon.name;
                div.innerHTML = icon.svg + '<span class="icon-name">' + icon.name + '</span>';
                div.addEventListener('click', function() {
                    document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    const iconKey = this.dataset.icon;
                    document.getElementById('medication_icon').value = iconKey;
                    
                    // Update selected icon display
                    const iconName = MedicationIcons.icons[iconKey].name;
                    document.getElementById('selected-icon-name').textContent = iconName;
                    
                    // Show/hide secondary color selector based on icon type
                    const iconData = MedicationIcons.icons[iconKey];
                    const secondaryColorSelector = document.getElementById('secondary-color-selector');
                    if (iconData && iconData.supportsTwoColors) {
                        secondaryColorSelector.style.display = 'block';
                    } else {
                        secondaryColorSelector.style.display = 'none';
                        document.getElementById('secondary_color').value = '';
                    }
                    
                    updateIconPreview();
                });
                iconGrid.appendChild(div);
            });

            // Populate primary color grid
            const colorGrid = document.getElementById('color-grid');
            MedicationIcons.colors.forEach((color, index) => {
                const div = document.createElement('div');
                div.className = 'color-option' + (index === 16 ? ' selected' : ''); // Default to Dark Purple
                div.dataset.color = color.value;
                div.title = color.name;
                div.style.backgroundColor = color.value;
                if (color.value === '#FFFFFF' || color.value === '#FFFFE0' || color.value === '#F5F5DC') {
                    div.style.border = '1px solid #ccc';
                }
                div.addEventListener('click', function() {
                    document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('medication_color').value = this.dataset.color;
                    updateIconPreview();
                });
                colorGrid.appendChild(div);
            });

            // Populate secondary color grid
            const secondaryColorGrid = document.getElementById('secondary-color-grid');
            MedicationIcons.colors.forEach(color => {
                const div = document.createElement('div');
                div.className = 'secondary-color-option';
                div.dataset.color = color.value;
                div.title = color.name;
                div.style.backgroundColor = color.value;
                if (color.value === '#FFFFFF' || color.value === '#FFFFE0' || color.value === '#F5F5DC') {
                    div.style.border = '1px solid #ccc';
                }
                div.addEventListener('click', function() {
                    document.querySelectorAll('.secondary-color-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('secondary_color').value = this.dataset.color;
                    updateIconPreview();
                });
                secondaryColorGrid.appendChild(div);
            });

            // Initial preview
            updateIconPreview();
        });

        function updateIconPreview() {
            const iconType = document.getElementById('medication_icon')?.value || 'pill';
            const color = document.getElementById('medication_color')?.value || '#5b21b6';
            const secondaryColor = document.getElementById('secondary_color')?.value || null;
            const preview = document.getElementById('icon_preview');
            
            if (preview) {
                preview.innerHTML = MedicationIcons.render(iconType, color, '48px', secondaryColor);
            }
        }
    // Increment/Decrement times per day
    function incrementTimesPerDay() {
        let input = document.getElementById("times_per_day");
        let current = parseInt(input.value) || 1;
        if (current < 6) {
            input.value = current + 1;
            updateTimeInputs();
        }
    }
    
    function decrementTimesPerDay() {
        let input = document.getElementById("times_per_day");
        let current = parseInt(input.value) || 1;
        if (current > 1) {
            input.value = current - 1;
            updateTimeInputs();
        }
    }
    
    // Update time inputs based on times per day
    function updateTimeInputs() {
        let timesPerDay = parseInt(document.getElementById("times_per_day").value) || 1;
        let container = document.getElementById("time_inputs_container");
        
        if (timesPerDay > 1) {
            let html = '<div style="margin-top:10px;"><strong>Dose Times:</strong></div>';
            for (let i = 1; i <= timesPerDay; i++) {
                html += `<label>Time ${i}:</label>`;
                html += `<input type="time" name="dose_time_${i}" id="dose_time_${i}">`;
            }
            
            // Add evenly split button
            html += '<button type="button" class="btn btn-secondary" onclick="evenlySplitTimes()" style="margin-top: 12px;">⏰ Evenly split (7am - 10pm)</button>';
            
            container.innerHTML = html;
        } else {
            container.innerHTML = '';
        }
    }
    
    // Evenly split times throughout the day (7am - 10pm)
    function evenlySplitTimes() {
        let timesPerDay = parseInt(document.getElementById("times_per_day").value) || 1;
        
        if (timesPerDay < 2) return;
        
        // Start at 7:00 (420 minutes from midnight)
        // End at 22:00 (1320 minutes from midnight)
        const startMinutes = 7 * 60; // 7:00 AM = 420 minutes
        const endMinutes = 22 * 60;  // 10:00 PM = 1320 minutes
        
        // Calculate interval
        let interval;
        if (timesPerDay === 2) {
            interval = endMinutes - startMinutes;
        } else {
            interval = (endMinutes - startMinutes) / (timesPerDay - 1);
        }
        
        // Set each time input
        for (let i = 1; i <= timesPerDay; i++) {
            let totalMinutes = startMinutes + (interval * (i - 1));
            let hours = Math.floor(totalMinutes / 60);
            let minutes = Math.round(totalMinutes % 60);
            
            // Format as HH:MM
            let timeString = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
            
            let input = document.getElementById(`dose_time_${i}`);
            if (input) {
                input.value = timeString;
            }
        }
    }

    // Schedule UI toggle
    function updateScheduleUI() {
        let f = document.getElementById("freq").value;
        
        if (f === "per_day") {
            document.querySelector('[name="times_per_week"]').value = "";
            // Uncheck all days_of_week checkboxes
            document.querySelectorAll('[name="days_of_week[]"]').forEach(cb => cb.checked = false);
            document.getElementById("daily").style.display = "block";
            document.getElementById("weekly").style.display = "none";
            updateTimeInputs();
        } else {
            document.querySelector('[name="times_per_day"]').value = "";
            document.getElementById("daily").style.display = "none";
            document.getElementById("weekly").style.display = "block";
        }
    }
    
    // PRN toggle - show/hide regular schedule
    function togglePRN() {
        let isPrn = document.getElementById("is_prn").checked;
        let regularSchedule = document.getElementById("regular-schedule");
        let prnSchedule = document.getElementById("prn-schedule");
        let freqSelect = document.getElementById("freq");
        
        if (isPrn) {
            regularSchedule.style.display = "none";
            prnSchedule.style.display = "block";
            // Remove required attribute from schedule fields
            freqSelect.removeAttribute("required");
        } else {
            regularSchedule.style.display = "block";
            prnSchedule.style.display = "none";
            // Add back required attribute
            freqSelect.setAttribute("required", "required");
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateTimeInputs();
    });
    </script>
</body>
</html>

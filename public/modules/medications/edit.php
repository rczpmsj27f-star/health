<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$medId) {
    header("Location: /modules/medications/list.php");
    exit;
}

$isAdmin = Auth::isAdmin();

// Get medication details
$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ? AND user_id = ?");
$stmt->execute([$medId, $_SESSION['user_id']]);
$med = $stmt->fetch();

if (!$med) {
    header("Location: /modules/medications/list.php");
    exit;
}

// Get dose information
$stmt = $pdo->prepare("SELECT * FROM medication_doses WHERE medication_id = ?");
$stmt->execute([$medId]);
$dose = $stmt->fetch();

// Get schedule information
$stmt = $pdo->prepare("SELECT * FROM medication_schedules WHERE medication_id = ?");
$stmt->execute([$medId]);
$schedule = $stmt->fetch();

// Get instructions
$stmt = $pdo->prepare("SELECT * FROM medication_instructions WHERE medication_id = ?");
$stmt->execute([$medId]);
$instructions = $stmt->fetchAll();

// Get condition
$stmt = $pdo->prepare("SELECT * FROM medication_conditions WHERE medication_id = ?");
$stmt->execute([$medId]);
$condition = $stmt->fetch();

// Get existing dose times
$existingDoseTimes = [];
$doseTimesArray = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM medication_dose_times WHERE medication_id = ? ORDER BY dose_number");
    $stmt->execute([$medId]);
    $existingDoseTimes = $stmt->fetchAll();
    foreach ($existingDoseTimes as $dt) {
        $doseTimesArray[$dt['dose_number']] = $dt['dose_time'];
    }
} catch (PDOException $e) {
    // Table doesn't exist yet - continue with empty arrays
}

// Prepare instruction checkboxes
$hasWater = false;
$hasEmptyStomach = false;
$hasFood = false;
$hasNoCrush = false;
$otherInstructions = [];

foreach ($instructions as $i) {
    switch ($i['instruction_text']) {
        case 'Take with water':
            $hasWater = true;
            break;
        case 'Take on empty stomach':
            $hasEmptyStomach = true;
            break;
        case 'Take with food':
            $hasFood = true;
            break;
        case 'Do not crush or chew':
            $hasNoCrush = true;
            break;
        default:
            $otherInstructions[] = $i['instruction_text'];
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medication - <?= htmlspecialchars($med['name']) ?></title>
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
            <h2 style="color: var(--color-primary); font-size: 32px; margin: 0 0 8px 0;">✏️ Edit Medication</h2>
            <p style="color: var(--color-text-secondary); margin: 0;">Update medication information</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #f5c6cb;">
                <strong>Error:</strong> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="/modules/medications/edit_handler.php" id="medForm">
            <input type="hidden" name="med_id" value="<?= $medId ?>">
            
            <!-- Section 1: Medication Information -->
            <div class="form-section">
                <div class="form-section-title">1. Medication Information</div>
                
                <div class="form-group">
                    <label>Medication Name *</label>
                    <input type="text" name="med_name" id="med_name" autocomplete="off" value="<?= htmlspecialchars($med['name']) ?>" required>
                </div>

                <!-- Icon and Color Selection -->
                <div id="icon-color-section">
                    <div class="icon-selector">
                        <label>Medication Icon</label>
                        <div class="icon-grid" id="icon-grid"></div>
                        <input type="hidden" name="medication_icon" id="medication_icon" value="<?= htmlspecialchars($med['icon'] ?? 'pill') ?>">
                    </div>

                    <div class="color-selector">
                        <label>Medication Color</label>
                        <div class="color-grid" id="color-grid">
                            <input type="color" id="custom_color" value="<?= htmlspecialchars($med['color'] ?? '#5b21b6') ?>" title="Custom Color">
                        </div>
                        <input type="hidden" name="medication_color" id="medication_color" value="<?= htmlspecialchars($med['color'] ?? '#5b21b6') ?>">
                    </div>

                    <div id="icon_preview"></div>
                </div>
            </div>

            <!-- Section 2: Dosage -->
            <div class="form-section">
                <div class="form-section-title">2. Dosage Information</div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Dose Amount *</label>
                        <input type="number" step="0.01" name="dose_amount" value="<?= htmlspecialchars($dose['dose_amount']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Unit *</label>
                        <select name="dose_unit" required>
                            <option value="">Select unit...</option>
                            <option value="mg" <?= $dose['dose_unit'] === 'mg' ? 'selected' : '' ?>>mg (milligrams)</option>
                            <option value="ml" <?= $dose['dose_unit'] === 'ml' ? 'selected' : '' ?>>ml (milliliters)</option>
                            <option value="tablet" <?= $dose['dose_unit'] === 'tablet' ? 'selected' : '' ?>>tablet(s)</option>
                            <option value="capsule" <?= $dose['dose_unit'] === 'capsule' ? 'selected' : '' ?>>capsule(s)</option>
                            <option value="g" <?= $dose['dose_unit'] === 'g' ? 'selected' : '' ?>>g (grams)</option>
                            <option value="mcg" <?= $dose['dose_unit'] === 'mcg' ? 'selected' : '' ?>>mcg (micrograms)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Schedule -->
            <div class="form-section">
                <div class="form-section-title">3. Medication Schedule</div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_prn" id="is_prn" value="1" <?= !empty($schedule['is_prn']) ? 'checked' : '' ?> onchange="togglePRN()" style="width: auto; margin: 0;">
                        <span>💊 As and when needed (PRN)</span>
                    </label>
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                        Check this if you take this medication only when needed, not on a regular schedule
                    </small>
                </div>

                <div id="prn-limits" style="<?= !empty($schedule['is_prn']) ? '' : 'display:none;' ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="initial_dose">Tablets per dose (initial)</label>
                            <input type="number" name="initial_dose" id="initial_dose" min="1" max="10" value="<?= htmlspecialchars($schedule['initial_dose'] ?? '1') ?>">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Recommended number of tablets for the first dose (e.g., 2 paracetamol tablets). This will be the default when logging, but you can adjust it each time.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="subsequent_dose">Tablets per dose (subsequent)</label>
                            <input type="number" name="subsequent_dose" id="subsequent_dose" min="1" max="10" value="<?= htmlspecialchars($schedule['subsequent_dose'] ?? '1') ?>">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Recommended number of tablets for follow-up doses (e.g., 2 paracetamol tablets). This will be the default when logging, but you can adjust it each time.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_doses_per_day">Max doses per day (optional)</label>
                            <input type="number" name="max_doses_per_day" id="max_doses_per_day" min="1" value="<?= htmlspecialchars($schedule['max_doses_per_day'] ?? '') ?>">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Maximum number of times you can take this per day
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="min_hours_between_doses">Min hours between doses (optional)</label>
                            <input type="number" step="0.5" name="min_hours_between_doses" id="min_hours_between_doses" min="0" value="<?= htmlspecialchars($schedule['min_hours_between_doses'] ?? '') ?>">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Minimum time required between doses
                            </small>
                        </div>
                    </div>
                </div>

                <div id="regular-schedule" style="<?= !empty($schedule['is_prn']) ? 'display:none;' : '' ?>">
                    <div class="form-group">
                        <label>Frequency *</label>
                        <select name="frequency_type" id="freq" onchange="updateScheduleUI()" <?= !empty($schedule['is_prn']) ? '' : 'required' ?>>
                            <option value="per_day" <?= $schedule['frequency_type'] === 'per_day' ? 'selected' : '' ?>>Times per day</option>
                            <option value="per_week" <?= $schedule['frequency_type'] === 'per_week' ? 'selected' : '' ?>>Times per week</option>
                        </select>
                    </div>

                    <div id="daily" style="<?= $schedule['frequency_type'] === 'per_day' ? '' : 'display:none;' ?>">
                        <div class="form-group">
                            <label>Times per day *</label>
                            <div class="number-stepper">
                                <button type="button" class="stepper-btn" onclick="decrementTimesPerDay()">−</button>
                                <input type="number" name="times_per_day" id="times_per_day" min="1" max="6" value="<?= htmlspecialchars($schedule['times_per_day'] ?: 1) ?>" readonly>
                                <button type="button" class="stepper-btn" onclick="incrementTimesPerDay()">+</button>
                            </div>
                        </div>
                        
                        <div id="time_inputs_container">
                            <!-- Time inputs will be dynamically generated here -->
                        </div>
                    </div>

                    <div id="weekly" style="<?= $schedule['frequency_type'] === 'per_week' ? '' : 'display:none;' ?>">
                        <div class="form-group">
                            <label>Times per week *</label>
                            <input type="number" name="times_per_week" min="1" max="7" value="<?= htmlspecialchars($schedule['times_per_week'] ?: 1) ?>">
                        </div>

                        <div class="form-group">
                            <label>Days of Week</label>
                            <div class="day-toggle-container">
                                <?php
                                $selectedDays = [];
                                if (!empty($schedule['days_of_week'])) {
                                    $selectedDays = array_map('trim', explode(',', $schedule['days_of_week']));
                                }
                                $allDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                foreach ($allDays as $day):
                                    $checked = in_array($day, $selectedDays) ? 'checked' : '';
                                ?>
                                <label class="day-toggle">
                                    <input type="checkbox" name="days_of_week[]" value="<?= $day ?>" <?= $checked ?>>
                                    <span class="day-toggle-btn"><?= $day ?></span>
                                </label>
                                <?php endforeach; ?>
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
                        <input type="number" name="current_stock" min="0" value="<?= htmlspecialchars($med['current_stock'] ?? '') ?>">
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            How many tablets/doses do you currently have?
                        </small>
                    </div>

                    <div class="form-group">
                        <label>End Date (optional)</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($med['end_date'] ?? '') ?>">
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
                        <input type="checkbox" name="instructions[]" value="Take with water" <?= $hasWater ? 'checked' : '' ?>>
                        💧 Take with water
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take on empty stomach" <?= $hasEmptyStomach ? 'checked' : '' ?>>
                        🍽️ Take on empty stomach
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Take with food" <?= $hasFood ? 'checked' : '' ?>>
                        🍴 Take with food
                    </label>
                    <label>
                        <input type="checkbox" name="instructions[]" value="Do not crush or chew" <?= $hasNoCrush ? 'checked' : '' ?>>
                        💊 Do not crush or chew
                    </label>
                </div>

                <div class="form-group">
                    <label>Other Instructions (optional)</label>
                    <textarea name="other_instructions" rows="3"><?= htmlspecialchars(implode("\n", $otherInstructions)) ?></textarea>
                </div>
            </div>

            <!-- Section 6: Condition -->
            <div class="form-section" style="display: none;">
                <div class="form-section-title">6. Condition Being Treated</div>
                
                <div class="form-group">
                    <label>Condition Name *</label>
                    <input type="text" name="condition_name" id="condition_name" autocomplete="off" value="<?= htmlspecialchars($condition['condition_name'] ?? '') ?>">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" type="submit">✅ Save Changes</button>
                <a class="btn btn-secondary" href="/modules/medications/view.php?id=<?= $medId ?>">❌ Cancel</a>
            </div>
        </form>
    </div>

    <script src="/assets/js/medication-icons.js"></script>
    <script src="/assets/js/form-protection.js"></script>
    <script>
        const selectedIcon = '<?= htmlspecialchars($med['icon'] ?? 'pill') ?>';
        const selectedColor = '<?= htmlspecialchars($med['color'] ?? '#5b21b6') ?>';

        // Initialize medication icon selector
        document.addEventListener('DOMContentLoaded', function() {
            // Populate icon grid
            const iconGrid = document.getElementById('icon-grid');
            Object.keys(MedicationIcons.icons).forEach(key => {
                const icon = MedicationIcons.icons[key];
                const div = document.createElement('div');
                div.className = 'icon-option' + (key === selectedIcon ? ' selected' : '');
                div.dataset.icon = key;
                div.title = icon.name;
                div.innerHTML = icon.svg + '<span class="icon-name">' + icon.name + '</span>';
                div.addEventListener('click', function() {
                    document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('medication_icon').value = this.dataset.icon;
                    updateIconPreview();
                });
                iconGrid.appendChild(div);
            });

            // Populate color grid
            const colorGrid = document.getElementById('color-grid');
            const customColor = document.getElementById('custom_color');
            
            MedicationIcons.colors.forEach(color => {
                const div = document.createElement('div');
                div.className = 'color-option' + (color.value === selectedColor ? ' selected' : '');
                div.dataset.color = color.value;
                div.title = color.name;
                div.style.backgroundColor = color.value;
                div.addEventListener('click', function() {
                    document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('medication_color').value = this.dataset.color;
                    customColor.value = this.dataset.color;
                    updateIconPreview();
                });
                colorGrid.insertBefore(div, customColor);
            });

            customColor.addEventListener('change', function() {
                document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
                document.getElementById('medication_color').value = this.value;
                updateIconPreview();
            });

            // Initial preview
            updateIconPreview();
        });

        function updateIconPreview() {
            const iconType = document.getElementById('medication_icon')?.value || 'pill';
            const color = document.getElementById('medication_color')?.value || '#5b21b6';
            const preview = document.getElementById('icon_preview');
            
            if (preview) {
                preview.innerHTML = MedicationIcons.render(iconType, color, '48px');
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
        
        // Get existing dose times from PHP
        let existingTimes = <?= json_encode($doseTimesArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        
        if (timesPerDay >= 1) {
            let html = '<div style="margin-top:10px;"><strong>Dose Times:</strong></div>';
            for (let i = 1; i <= timesPerDay; i++) {
                html += `<label>Time ${i}:</label>`;
                let timeValue = existingTimes[i] || '';
                html += `<input type="time" name="dose_time_${i}" id="dose_time_${i}" value="${timeValue}">`;
            }
            
            // Add evenly split button only if more than 1 dose per day
            if (timesPerDay > 1) {
                html += '<button type="button" class="btn btn-secondary" onclick="evenlySplitTimes()" style="margin-top: 12px;">⏰ Evenly split (7am - 10pm)</button>';
            }
            
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
            let timesPerWeekInput = document.querySelector('[name="times_per_week"]');
            if (timesPerWeekInput) {
                timesPerWeekInput.value = "";
            }
            // Uncheck all days_of_week checkboxes
            document.querySelectorAll('[name="days_of_week[]"]').forEach(cb => cb.checked = false);
            document.getElementById("daily").style.display = "block";
            document.getElementById("weekly").style.display = "none";
            updateTimeInputs();
        } else {
            let timesPerDayInput = document.querySelector('[name="times_per_day"]');
            if (timesPerDayInput) {
                timesPerDayInput.value = "";
            }
            document.getElementById("daily").style.display = "none";
            document.getElementById("weekly").style.display = "block";
        }
    }
    
    // PRN toggle - show/hide regular schedule
    function togglePRN() {
        let isPrn = document.getElementById("is_prn").checked;
        let regularSchedule = document.getElementById("regular-schedule");
        let prnLimits = document.getElementById("prn-limits");
        let freqSelect = document.getElementById("freq");
        
        if (isPrn) {
            regularSchedule.style.display = "none";
            prnLimits.style.display = "block";
            // Remove required attribute from schedule fields
            freqSelect.removeAttribute("required");
        } else {
            regularSchedule.style.display = "block";
            prnLimits.style.display = "none";
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

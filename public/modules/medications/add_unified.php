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
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        
        <div class="menu-parent">
            <a href="/modules/medications/dashboard.php" class="menu-parent-link">💊 Medications</a>
            <div class="menu-children">
                <a href="/modules/medications/list.php">My Medications</a>
                <a href="/modules/medications/stock.php">Medication Stock</a>
                <a href="/modules/medications/compliance.php">Compliance</a>
                <a href="/modules/medications/log_prn.php">Log PRN</a>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

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
                            <label>Maximum doses per day</label>
                            <input type="number" name="max_doses_per_day" id="max_doses_per_day" min="1" max="24" placeholder="e.g., 4">
                            <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                                Maximum number of doses allowed in 24 hours
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
            <div class="form-section">
                <div class="form-section-title">6. Condition Being Treated</div>
                
                <div class="form-group">
                    <label>Condition Name *</label>
                    <input type="text" name="condition_name" id="condition_name" autocomplete="off" placeholder="e.g., High blood pressure" required>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" type="submit">✅ Add Medication</button>
                <a class="btn btn-secondary" href="/modules/medications/list.php">❌ Cancel</a>
            </div>
        </form>
    </div>

    <script>
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

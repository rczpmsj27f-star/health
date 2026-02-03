<?php 
session_start();
require_once "../../../app/core/Auth.php";

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
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/menu.js" defer></script>
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
        <a href="/modules/medications/list.php">💊 Medications</a>
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

        <form method="POST" action="/modules/medications/add_unified_handler.php" id="medForm">
            <!-- Section 1: Medication Search -->
            <div class="form-section">
                <div class="form-section-title">1. Medication Information</div>
                
                <div class="form-group">
                    <label>Medication Name *</label>
                    <input type="text" name="med_name" id="med_name" onkeyup="searchMed()" autocomplete="off" placeholder="Start typing to search..." required>
                    <div id="results" class="autocomplete-results" style="display: none;"></div>
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
                    <label>Frequency *</label>
                    <select name="frequency_type" id="freq" onchange="updateScheduleUI()" required>
                        <option value="per_day">Times per day</option>
                        <option value="per_week">Times per week</option>
                    </select>
                </div>

                <div id="daily">
                    <div class="form-group">
                        <label>Times per day *</label>
                        <input type="number" name="times_per_day" min="1" max="6" value="1">
                    </div>
                </div>

                <div id="weekly" style="display:none;">
                    <div class="form-group">
                        <label>Times per week *</label>
                        <input type="number" name="times_per_week" min="1" max="7" value="1">
                    </div>

                    <div class="form-group">
                        <label>Days of Week</label>
                        <input type="text" name="days_of_week" placeholder="e.g., Mon, Wed, Fri">
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            Enter days separated by commas
                        </small>
                    </div>
                </div>
            </div>

            <!-- Section 4: Instructions -->
            <div class="form-section">
                <div class="form-section-title">4. Special Instructions</div>
                
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

            <!-- Section 5: Condition -->
            <div class="form-section">
                <div class="form-section-title">5. Condition Being Treated</div>
                
                <div class="form-group">
                    <label>Condition Name *</label>
                    <input type="text" name="condition_name" id="condition_name" onkeyup="searchCondition()" autocomplete="off" placeholder="e.g., High blood pressure" required>
                    <div id="condition_results" class="autocomplete-results" style="display: none;"></div>
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
    // Medication search
    function searchMed() {
        let q = document.getElementById("med_name").value;
        let resultsDiv = document.getElementById("results");
        
        if (q.length < 2) {
            resultsDiv.style.display = "none";
            return;
        }

        fetch("/modules/medications/search.php?q=" + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                let html = "";
                if (data.length === 0) {
                    html = '<div class="autocomplete-item" style="color: #999;">No results found</div>';
                } else {
                    data.forEach(item => {
                        // Fix: Properly handle the name to avoid "Unknown"
                        const itemName = item.name && item.name !== "Unknown" ? item.name : 'Custom medication';
                        const itemId = item.id || '';
                        html += `<div class="autocomplete-item" onclick="selectMed('${itemId}', '${itemName.replace(/'/g, "&apos;")}')">${itemName}</div>`;
                    });
                }
                resultsDiv.innerHTML = html;
                resultsDiv.style.display = "block";
            })
            .catch(err => {
                console.error('Search error:', err);
                resultsDiv.innerHTML = '<div class="autocomplete-item" style="color: #dc3545;">Error searching medications</div>';
                resultsDiv.style.display = "block";
            });
    }

    function selectMed(id, name) {
        document.getElementById("med_name").value = name;
        document.getElementById("selected_med_id").value = id;
        document.getElementById("results").style.display = "none";
    }

    // Condition search
    function searchCondition() {
        let q = document.getElementById("condition_name").value;
        let resultsDiv = document.getElementById("condition_results");
        
        if (q.length < 2) {
            resultsDiv.style.display = "none";
            return;
        }

        // Mock condition search - in production this would call an API
        // For now, just show the entered value as a suggestion
        let html = `<div class="autocomplete-item" onclick="selectCondition('${q.replace(/'/g, "&apos;")}')">${q}</div>`;
        resultsDiv.innerHTML = html;
        resultsDiv.style.display = "block";
    }

    function selectCondition(name) {
        document.getElementById("condition_name").value = name;
        document.getElementById("condition_results").style.display = "none";
    }

    // Schedule UI toggle
    function updateScheduleUI() {
        let f = document.getElementById("freq").value;
        
        if (f === "per_day") {
            document.querySelector('[name="times_per_week"]').value = "";
            document.querySelector('[name="days_of_week"]').value = "";
            document.getElementById("daily").style.display = "block";
            document.getElementById("weekly").style.display = "none";
        } else {
            document.querySelector('[name="times_per_day"]').value = "";
            document.getElementById("daily").style.display = "none";
            document.getElementById("weekly").style.display = "block";
        }
    }

    // Close autocomplete when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.form-group')) {
            document.getElementById('results').style.display = 'none';
            document.getElementById('condition_results').style.display = 'none';
        }
    });
    </script>
</body>
</html>

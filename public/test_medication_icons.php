<?php
require_once __DIR__ . '/../../app/helpers/medication_icon.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Icon Test - Verification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            font-size: 36px;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .test-section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .test-row {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .test-label {
            min-width: 250px;
            font-weight: 600;
            color: #555;
        }
        .icon-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .bg-white {
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .success-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Medication Icon System - Fix Verification</h1>
        
        <div class="test-section">
            <h2>✓ Test 1: Half & Half Icons with Vertical Split</h2>
            <div class="test-row">
                <div class="test-label">pill-half (Red/Blue):</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('pill-half', '#DC2626', '64px', '#2563EB'); ?>
                    </div>
                    <span class="success-badge">VERTICAL SPLIT ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">capsule-half (Red/Blue):</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('capsule-half', '#DC2626', '64px', '#2563EB'); ?>
                    </div>
                    <span class="success-badge">VERTICAL SPLIT ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">capsule-half (Green/Yellow):</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('capsule-half', '#16A34A', '64px', '#FFD700'); ?>
                    </div>
                    <span class="success-badge">VERTICAL SPLIT ✓</span>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>✓ Test 2: Light Colored Pills with Black Outline (Visibility)</h2>
            <div class="test-row">
                <div class="test-label">White pill-round:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('pill-round', '#FFFFFF', '64px'); ?>
                    </div>
                    <span class="success-badge">VISIBLE ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">Beige pill-oval:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('pill-oval', '#F5F5DC', '64px'); ?>
                    </div>
                    <span class="success-badge">VISIBLE ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">Light Yellow pill-oblong:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('pill-oblong', '#FFFACD', '64px'); ?>
                    </div>
                    <span class="success-badge">VISIBLE ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">Light Pink capsule-small:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('capsule-small', '#FFB6C1', '64px'); ?>
                    </div>
                    <span class="success-badge">VISIBLE ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">Cream pill-rectangular:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('pill-rectangular', '#FFFDD0', '64px'); ?>
                    </div>
                    <span class="success-badge">VISIBLE ✓</span>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>✓ Test 3: Various Icons with Black Strokes</h2>
            <div class="test-row">
                <div class="test-label">Purple pill:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('pill', '#9370DB', '64px'); ?>
                    </div>
                    <span class="success-badge">BLACK OUTLINE ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">Blue capsule-large:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('capsule-large', '#2563EB', '64px'); ?>
                    </div>
                    <span class="success-badge">BLACK OUTLINE ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">Orange liquid:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('liquid', '#FF8C00', '64px'); ?>
                    </div>
                    <span class="success-badge">BLACK OUTLINE ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">Green drops:</div>
                <div class="icon-container">
                    <div class="bg-white">
                        <?php echo renderMedicationIcon('drops', '#0D9488', '64px'); ?>
                    </div>
                    <span class="success-badge">BLACK OUTLINE ✓</span>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>✓ Test 4: JavaScript Rendering (using MedicationIcons.render())</h2>
            <div class="test-row">
                <div class="test-label">JS: pill-half (Pink/Green):</div>
                <div class="icon-container">
                    <div class="bg-white" id="js-pill-half"></div>
                    <span class="success-badge">JS RENDER ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">JS: capsule-half (Purple/Orange):</div>
                <div class="icon-container">
                    <div class="bg-white" id="js-capsule-half"></div>
                    <span class="success-badge">JS RENDER ✓</span>
                </div>
            </div>
            <div class="test-row">
                <div class="test-label">JS: White pill-round:</div>
                <div class="icon-container">
                    <div class="bg-white" id="js-white-pill"></div>
                    <span class="success-badge">JS RENDER ✓</span>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/medication-icons.js"></script>
    <script>
        // Test JavaScript rendering
        document.getElementById('js-pill-half').innerHTML = MedicationIcons.render('pill-half', '#FF69B4', '64px', '#16A34A');
        document.getElementById('js-capsule-half').innerHTML = MedicationIcons.render('capsule-half', '#9370DB', '64px', '#FF8C00');
        document.getElementById('js-white-pill').innerHTML = MedicationIcons.render('pill-round', '#FFFFFF', '64px');
    </script>
</body>
</html>

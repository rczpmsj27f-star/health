<?php
require_once __DIR__ . '/app/helpers/medication_icon.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Icon Test - Capsule Half & Injection</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .icon-test {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            margin: 10px;
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #5b21b6;
            padding-bottom: 10px;
        }
        .label {
            font-weight: 600;
            min-width: 150px;
        }
    </style>
</head>
<body>
    <h1>Medication Icon Updates Test</h1>
    
    <div class="test-section">
        <h2>Issue 1: Capsule-Half Icon (Proper Horizontal Capsule with Vertical Split)</h2>
        <p>Should show a proper capsule shape (rounded rectangle) split vertically with black outline.</p>
        
        <div class="icon-test">
            <span class="label">Purple/Orange:</span>
            <?= renderMedicationIcon('capsule-half', '#5b21b6', '40px', '#ff6b35') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Blue/Yellow:</span>
            <?= renderMedicationIcon('capsule-half', '#3b82f6', '40px', '#fbbf24') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Green/Red:</span>
            <?= renderMedicationIcon('capsule-half', '#10b981', '40px', '#ef4444') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Large size:</span>
            <?= renderMedicationIcon('capsule-half', '#8b5cf6', '60px', '#ec4899') ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Issue 3: Injection Icon (Proper Needle/Syringe)</h2>
        <p>Should show a proper syringe/needle icon with black outline.</p>
        
        <div class="icon-test">
            <span class="label">Purple:</span>
            <?= renderMedicationIcon('injection', '#5b21b6', '40px') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Blue:</span>
            <?= renderMedicationIcon('injection', '#3b82f6', '40px') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Green:</span>
            <?= renderMedicationIcon('injection', '#10b981', '40px') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Large size:</span>
            <?= renderMedicationIcon('injection', '#f59e0b', '60px') ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Issue 4: Black Outline on All Icons</h2>
        <p>All icons should have visible black outlines (stroke="#000" stroke-width="0.5").</p>
        
        <div class="icon-test">
            <span class="label">Pill:</span>
            <?= renderMedicationIcon('pill', '#5b21b6', '40px') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Pill-Half:</span>
            <?= renderMedicationIcon('pill-half', '#5b21b6', '40px', '#ff6b35') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Capsule:</span>
            <?= renderMedicationIcon('capsule', '#5b21b6', '40px') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Capsule-Small:</span>
            <?= renderMedicationIcon('capsule-small', '#3b82f6', '40px') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Liquid:</span>
            <?= renderMedicationIcon('liquid', '#10b981', '40px') ?>
        </div>
        
        <div class="icon-test">
            <span class="label">Inhaler:</span>
            <?= renderMedicationIcon('inhaler', '#8b5cf6', '40px') ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>SVG Source Code Check</h2>
        <p>Capsule-Half raw SVG (before color replacement):</p>
        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;"><?php 
            $data = getMedicationIconSVG('capsule-half');
            echo htmlspecialchars($data['svg']);
        ?></pre>
        
        <p>Injection raw SVG (before color replacement):</p>
        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;"><?php 
            $data = getMedicationIconSVG('injection');
            echo htmlspecialchars($data['svg']);
        ?></pre>
    </div>
</body>
</html>

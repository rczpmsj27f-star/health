<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medication</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="centered-page">
    <div class="page-card">
        <div class="page-header">
            <h2>Add Medication</h2>
            <p>Search and select your medication</p>
        </div>

        <form method="POST" action="/modules/medications/add_handler.php">
            <div class="form-group">
                <label>Medication Name</label>
                <input type="text" name="med_name" id="med_name" onkeyup="searchMed()" autocomplete="off" placeholder="Start typing to search..." required>
                
                <div id="results" class="autocomplete-results" style="display: none;"></div>
            </div>

            <input type="hidden" name="nhs_med_id" id="selected_med_id">

            <button class="btn btn-accept" type="submit">Continue to Dose</button>
        </form>

        <div class="page-footer">
            <p><a href="/modules/medications/list.php">Back to Medications</a></p>
        </div>
    </div>

    <script>
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
                        html += `<div class="autocomplete-item" onclick="selectMed('${item.id}', '${item.name.replace(/'/g, "&apos;")}')">${item.name}</div>`;
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
    </script>
</body>
</html>

<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Medication</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Add Medication</h2>

    <form method="POST" action="/modules/medications/add_handler.php">
        <label>Medication Name</label>
        <input type="text" name="med_name" id="med_name" onkeyup="searchMed()" autocomplete="off" required>

        <div id="results" style="background:#fff; border:1px solid #ccc; margin-top:5px;"></div>

        <input type="hidden" name="nhs_med_id" id="selected_med_id">

        <button class="btn btn-accept" type="submit">Continue</button>
    </form>
</div>

<script>
function searchMed() {
    let q = document.getElementById("med_name").value;
    if (q.length < 2) return;

    fetch("/modules/medications/search.php?q=" + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            let html = "";
            data.forEach(item => {
                html += `<div style="padding:8px; border-bottom:1px solid #eee; cursor:pointer;"
                            onclick="selectMed('${item.id}', '${item.name}')">
                            ${item.name}
                         </div>`;
            });
            document.getElementById("results").innerHTML = html;
        });
}

function selectMed(id, name) {
    document.getElementById("med_name").value = name;
    document.getElementById("selected_med_id").value = id;
    document.getElementById("results").innerHTML = "";
}
</script>

</body>
</html>

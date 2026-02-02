<form method="POST" action="/modules/medications/add_handler.php">
    <label>Medication Name</label>
    <input type="text" name="med_name" id="med_name" onkeyup="searchMed()" autocomplete="off">

    <div id="results"></div>

    <button class="btn btn-accept">Continue</button>
</form>

<script>
function searchMed() {
    let q = document.getElementById("med_name").value;
    if (q.length < 2) return;

    fetch("/modules/medications/search.php?q=" + q)
        .then(r => r.json())
        .then(data => {
            let html = "";
            data.forEach(item => {
                html += `<div onclick="selectMed('${item.id}', '${item.name}')">${item.name}</div>`;
            });
            document.getElementById("results").innerHTML = html;
        });
}

function selectMed(id, name) {
    document.getElementById("med_name").value = name;
    document.getElementById("results").innerHTML = "";
    document.getElementById("selected_med_id").value = id;
}
</script>

<input type="hidden" name="nhs_med_id" id="selected_med_id">

<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Picture</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div style="padding:16px;">
    <h2>Update Profile Picture</h2>

    <form method="POST" action="/modules/profile/update_picture_handler.php" enctype="multipart/form-data">
        <label>Select Image</label>
        <input type="file" name="profile_picture" accept="image/*" required>

        <button class="btn btn-accept" type="submit">Upload</button>
    </form>
</div>

</body>
</html>

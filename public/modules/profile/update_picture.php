<?php 
session_start();
require_once "../../../app/core/auth.php";
$isAdmin = Auth::isAdmin();
$err = $_SESSION['error'] ?? null;
$ok  = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Picture</title>
    
    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Health Tracker">
    <link rel="apple-touch-icon" href="/assets/images/icon-192x192.png">
    <meta name="theme-color" content="#4F46E5">
    
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    
    <style>
        .crop-container {
            max-width: 100%;
            margin: 20px 0;
            display: none;
        }
        
        #cropImage {
            max-width: 100%;
            display: block;
        }
        
        .crop-controls {
            display: none;
            margin-top: 16px;
            gap: 12px;
        }
        
        .crop-controls.active {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .crop-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: opacity 0.2s;
        }
        
        .crop-btn:hover {
            opacity: 0.8;
        }
        
        .crop-btn-rotate {
            background: #6c757d;
            color: white;
        }
        
        .crop-btn-flip {
            background: #17a2b8;
            color: white;
        }
        
        .crop-btn-reset {
            background: #ffc107;
            color: #000;
        }
        
        #fileInputLabel {
            display: inline-block;
            padding: 12px 24px;
            background: #4F46E5;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        #fileInputLabel:hover {
            opacity: 0.9;
        }
        
        #profilePictureInput {
            display: none;
        }
        
        .upload-section {
            display: none;
        }
        
        .upload-section.active {
            display: block;
        }
    </style>
</head>
<body class="centered-page">
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div id="main-content">
    <div style="max-width: 700px; margin: 0 auto; padding: 16px 16px 40px 16px;">
    <div class="page-card">
        <div class="page-header">
            <h2>Update Profile Picture</h2>
            <p>Upload a new profile picture</p>
        </div>

        <?php if ($err): ?>
            <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="fileInputLabel">Select Image</label>
            <label id="fileInputLabel">
                📁 Choose Photo
                <input type="file" id="profilePictureInput" accept="image/*">
            </label>
            <small style="display: block; margin-top: 8px; color: #666;">
                Allowed: JPG, PNG, GIF, WebP. Maximum size: 5MB
            </small>
        </div>

        <div class="crop-container" id="cropContainer">
            <img id="cropImage" alt="Crop preview">
        </div>

        <div class="crop-controls" id="cropControls">
            <button type="button" class="crop-btn crop-btn-rotate" onclick="rotateCropper(-90)">↶ Rotate Left</button>
            <button type="button" class="crop-btn crop-btn-rotate" onclick="rotateCropper(90)">↷ Rotate Right</button>
            <button type="button" class="crop-btn crop-btn-flip" onclick="flipCropper('horizontal')">↔ Flip H</button>
            <button type="button" class="crop-btn crop-btn-flip" onclick="flipCropper('vertical')">↕ Flip V</button>
            <button type="button" class="crop-btn crop-btn-reset" onclick="resetCropper()">🔄 Reset</button>
        </div>

        <form method="POST" action="/modules/profile/update_picture_handler.php" enctype="multipart/form-data" id="uploadForm" class="upload-section">
            <input type="hidden" name="cropped_image" id="croppedImageData">
            
            <div id="finalPreview" style="display: none; text-align: center; margin: 20px 0;">
                <p style="font-weight: 600; margin-bottom: 10px;">Final Preview:</p>
                <img id="finalPreviewImg" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Final preview">
            </div>

            <button class="btn btn-accept" type="submit">✅ Upload Picture</button>
            <button class="btn btn-secondary" type="button" onclick="cancelCrop()" style="margin-top: 10px;">Cancel</button>
        </form>

        <div class="page-footer">
            <p><a href="/profile">Cancel</a></p>
        </div>
    </div>
    
    <!-- Cropper.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    
    <script>
    let cropper = null;
    let originalFile = null;
    
    document.getElementById('profilePictureInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Invalid file type. Please select a JPG, PNG, GIF, or WebP image.');
            this.value = '';
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File too large. Maximum size is 5MB.');
            this.value = '';
            return;
        }
        
        originalFile = file;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            // Show crop container
            document.getElementById('cropContainer').style.display = 'block';
            document.getElementById('cropControls').classList.add('active');
            
            // Set image source
            const cropImage = document.getElementById('cropImage');
            cropImage.src = e.target.result;
            
            // Destroy previous cropper if exists
            if (cropper) {
                cropper.destroy();
            }
            
            // Initialize cropper
            cropper = new Cropper(cropImage, {
                aspectRatio: 1, // Square crop
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                ready: function() {
                    // Show upload form when cropper is ready
                    document.getElementById('uploadForm').classList.add('active');
                    updateFinalPreview();
                },
                crop: function() {
                    updateFinalPreview();
                }
            });
        };
        reader.readAsDataURL(file);
    });
    
    function updateFinalPreview() {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: 300,
            height: 300,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        if (canvas) {
            const preview = document.getElementById('finalPreviewImg');
            preview.src = canvas.toDataURL('image/jpeg', 0.9);
            document.getElementById('finalPreview').style.display = 'block';
        }
    }
    
    function rotateCropper(degree) {
        if (cropper) {
            cropper.rotate(degree);
        }
    }
    
    function flipCropper(direction) {
        if (!cropper) return;
        
        if (direction === 'horizontal') {
            const scaleX = cropper.getData().scaleX || 1;
            cropper.scaleX(-scaleX);
        } else {
            const scaleY = cropper.getData().scaleY || 1;
            cropper.scaleY(-scaleY);
        }
    }
    
    function resetCropper() {
        if (cropper) {
            cropper.reset();
        }
    }
    
    function cancelCrop() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        document.getElementById('cropContainer').style.display = 'none';
        document.getElementById('cropControls').classList.remove('active');
        document.getElementById('uploadForm').classList.remove('active');
        document.getElementById('profilePictureInput').value = '';
        originalFile = null;
    }
    
    // Handle form submission
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!cropper) {
            alert('Please select an image first.');
            return;
        }
        
        // Get cropped canvas
        const canvas = cropper.getCroppedCanvas({
            width: 500,
            height: 500,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        if (!canvas) {
            alert('Failed to process image. Please try again.');
            return;
        }
        
        // Convert canvas to blob
        canvas.toBlob(function(blob) {
            if (!blob) {
                alert('Failed to process image. Please try again.');
                return;
            }
            
            // Create form data
            const formData = new FormData();
            
            // Add cropped image as file
            const filename = originalFile.name || 'profile.jpg';
            formData.append('profile_picture', blob, filename);
            
            // Submit via fetch
            fetch('/modules/profile/update_picture_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text().then(text => {
                        throw new Error('Upload failed: ' + text);
                    });
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Failed to upload image. Please try again.');
            });
        }, 'image/jpeg', 0.9);
    });
    </script>
    
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker registered'))
            .catch(err => console.error('Service Worker registration failed:', err));
    }
    </script>
    </div> <!-- wrapper div -->
    </div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>

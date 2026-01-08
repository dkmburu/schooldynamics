<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capture Document - <?= e($schoolName ?? 'School') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #206bc4 0%, #4299e1 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .capture-container {
            max-width: 600px;
            margin: 0 auto;
        }
        #camera-view {
            width: 100%;
            max-width: 100%;
            border-radius: 10px;
            background: #000;
        }
        #captured-image {
            width: 100%;
            max-width: 100%;
            border-radius: 10px;
        }
        .btn-capture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            font-size: 2rem;
        }
        .card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .staff-info {
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="capture-container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="ti ti-camera me-2"></i>
                    Capture <?= e($documentLabel ?? 'Document') ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <?= e($error) ?>
                    </div>
                <?php else: ?>
                    <!-- Staff Info -->
                    <?php if (!empty($staffName)): ?>
                    <div class="alert alert-info">
                        <small>Uploading document for: <strong><?= e($staffName) ?></strong></small>
                    </div>
                    <?php endif; ?>

                    <!-- Camera View -->
                    <div id="camera-section">
                        <div class="text-center mb-3">
                            <p class="text-muted">Position the document within the frame</p>
                        </div>
                        <video id="camera-view" autoplay playsinline></video>
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-primary btn-capture" id="capture-btn">
                                <i class="ti ti-camera"></i>
                            </button>
                            <p class="mt-2 text-muted">Tap to capture</p>
                        </div>
                    </div>

                    <!-- Preview Section (Hidden Initially) -->
                    <div id="preview-section" style="display: none;">
                        <div class="text-center mb-3">
                            <p class="text-muted">Review your photo</p>
                        </div>
                        <img id="captured-image" src="" alt="Captured Document">
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-secondary mr-2" id="retake-btn">
                                <i class="ti ti-refresh me-2"></i>Retake
                            </button>
                            <button type="button" class="btn btn-success" id="upload-btn">
                                <i class="ti ti-check me-2"></i>Use This Photo
                            </button>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div id="upload-section" style="display: none;">
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="sr-only">Uploading...</span>
                            </div>
                            <p>Uploading document...</p>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <div id="success-section" style="display: none;">
                        <div class="alert alert-success text-center">
                            <i class="ti ti-circle-check" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Document Uploaded Successfully!</h5>
                            <p class="mb-0">You can now close this window</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card mt-3 bg-light">
            <div class="card-body text-center">
                <small class="text-muted">
                    <i class="ti ti-lock me-1"></i>
                    Secure upload - This link will expire after use
                </small>
            </div>
        </div>
    </div>

    <canvas id="canvas" style="display: none;"></canvas>

    <script>
    let stream = null;
    let capturedBlob = null;

    const video = document.getElementById('camera-view');
    const canvas = document.getElementById('canvas');
    const capturedImage = document.getElementById('captured-image');

    const cameraSection = document.getElementById('camera-section');
    const previewSection = document.getElementById('preview-section');
    const uploadSection = document.getElementById('upload-section');
    const successSection = document.getElementById('success-section');

    const captureBtn = document.getElementById('capture-btn');
    const retakeBtn = document.getElementById('retake-btn');
    const uploadBtn = document.getElementById('upload-btn');

    // Start camera
    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment', // Use back camera on mobile
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            });
            video.srcObject = stream;
        } catch (error) {
            console.error('Camera error:', error);
            alert('Unable to access camera. Please ensure you have granted camera permissions.');
        }
    }

    // Capture photo
    if (captureBtn) {
        captureBtn.addEventListener('click', function() {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0);

            // Convert to blob
            canvas.toBlob(function(blob) {
                capturedBlob = blob;
                capturedImage.src = URL.createObjectURL(blob);

                // Stop camera
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                // Show preview
                cameraSection.style.display = 'none';
                previewSection.style.display = 'block';
            }, 'image/jpeg', 0.92);
        });
    }

    // Retake photo
    if (retakeBtn) {
        retakeBtn.addEventListener('click', function() {
            previewSection.style.display = 'none';
            cameraSection.style.display = 'block';
            startCamera();
        });
    }

    // Upload photo
    if (uploadBtn) {
        uploadBtn.addEventListener('click', async function() {
            if (!capturedBlob) return;

            previewSection.style.display = 'none';
            uploadSection.style.display = 'block';

            const formData = new FormData();
            formData.append('document_file', capturedBlob, 'document.jpg');
            formData.append('upload_token', '<?= e($uploadToken ?? '') ?>');

            try {
                const response = await fetch('/hr-payroll/documents/upload-from-phone', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    uploadSection.style.display = 'none';
                    successSection.style.display = 'block';

                    // Auto-close after 3 seconds
                    setTimeout(() => {
                        window.close();
                    }, 3000);
                } else {
                    alert('Upload failed: ' + (data.message || 'Unknown error'));
                    uploadSection.style.display = 'none';
                    previewSection.style.display = 'block';
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed. Please try again.');
                uploadSection.style.display = 'none';
                previewSection.style.display = 'block';
            }
        });
    }

    // Start camera when page loads
    <?php if (!isset($error)): ?>
    startCamera();
    <?php endif; ?>
    </script>
</body>
</html>

<?php
/**
 * Document Upload Modals
 */
?>

<!-- Upload Method Selection Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="uploadModalLabel">
                    <i class="ti ti-upload me-2"></i>
                    Upload <span id="upload_doc_type_label"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="upload_applicant_id" value="">
                <input type="hidden" id="upload_doc_type" value="">

                <!-- Step 1: Choose Upload Method -->
                <div id="step_choose_method">
                    <h5 class="mb-4">How would you like to upload the document?</h5>

                    <div class="row">
                        <!-- Option 1: Upload from Computer -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-primary cursor-pointer" onclick="selectUploadMethod('computer')" style="cursor: pointer;">
                                <div class="card-body text-center">
                                    <i class="fas fa-laptop fa-4x text-primary mb-3"></i>
                                    <h5 class="card-title">Upload from Computer</h5>
                                    <p class="card-text text-muted">
                                        Select a file from your computer or drag and drop
                                    </p>
                                    <button type="button" class="btn btn-primary" onclick="selectUploadMethod('computer')">
                                        <i class="fas fa-folder-open mr-2"></i>Choose File
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Option 2: Capture with Phone Camera -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-success cursor-pointer" onclick="selectUploadMethod('phone')" style="cursor: pointer;">
                                <div class="card-body text-center">
                                    <i class="fas fa-mobile-alt fa-4x text-success mb-3"></i>
                                    <h5 class="card-title">Capture with Phone Camera</h5>
                                    <p class="card-text text-muted">
                                        Scan QR code with your phone to take a photo
                                    </p>
                                    <button type="button" class="btn btn-success" onclick="selectUploadMethod('phone')">
                                        <i class="fas fa-qrcode mr-2"></i>Generate QR Code
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2a: Computer Upload Form -->
                <div id="step_computer_upload" style="display: none;">
                    <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="backToMethodSelection()">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </button>

                    <form id="computerUploadForm" method="POST" action="/applicants/documents/upload" enctype="multipart/form-data" data-no-ajax="true">
                        <input type="hidden" name="applicant_id" id="computer_applicant_id">
                        <input type="hidden" name="document_type" id="computer_doc_type">
                        <input type="hidden" name="upload_method" value="computer">

                        <div class="form-group">
                            <label for="document_file">Select File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="document_file" name="document_file" accept="image/*,.pdf" required>
                                <label class="custom-file-label" for="document_file">Choose file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Accepted formats: JPG, PNG, PDF. Maximum file size: 5MB
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="document_notes">Notes (Optional)</label>
                            <textarea class="form-control" id="document_notes" name="notes" rows="2" placeholder="Add any additional notes about this document..."></textarea>
                        </div>

                        <!-- File Preview -->
                        <div id="file_preview" class="mb-3" style="display: none;">
                            <h6>Preview:</h6>
                            <img id="preview_image" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload me-2"></i>Upload Document
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2b: Phone Camera QR Code -->
                <div id="step_phone_qr" style="display: none;">
                    <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="backToMethodSelection()">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </button>

                    <div class="text-center">
                        <h5 class="mb-3">Scan QR Code with Your Phone</h5>
                        <p class="text-muted mb-4">
                            Open your phone's camera app and point it at this QR code to start capturing the document
                        </p>

                        <!-- QR Code Display -->
                        <div id="qr_code_container" class="mb-4">
                            <div id="qr_code_spinner" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Generating QR code...</span>
                                </div>
                                <p class="mt-2">Generating secure link...</p>
                            </div>
                            <div id="qr_code_display" style="display: none;">
                                <div id="qr_code" style="display: inline-block;"></div>
                            </div>
                        </div>

                        <!-- Upload Status -->
                        <div id="upload_status" class="alert alert-info" style="display: none;">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span id="upload_status_text">Waiting for upload from phone...</span>
                        </div>

                        <!-- Session Info -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">
                                    <i class="fas fa-lock mr-1"></i>
                                    This is a secure, one-time link that expires in <strong>10 minutes</strong>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
let uploadToken = null;
let uploadCheckInterval = null;

function showUploadModal(docType, docLabel, applicantId) {
    // Reset modal
    document.getElementById('upload_doc_type').value = docType;
    document.getElementById('upload_doc_type_label').textContent = docLabel;
    document.getElementById('upload_applicant_id').value = applicantId;

    // Show method selection
    document.getElementById('step_choose_method').style.display = 'block';
    document.getElementById('step_computer_upload').style.display = 'none';
    document.getElementById('step_phone_qr').style.display = 'none';

    // Clear any existing intervals
    if (uploadCheckInterval) {
        clearInterval(uploadCheckInterval);
        uploadCheckInterval = null;
    }

    // Show modal using Bootstrap's JavaScript API or jQuery
    const uploadModal = document.getElementById('uploadModal');
    if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
        jQuery('#uploadModal').modal('show');
    } else if (typeof bootstrap !== 'undefined') {
        new bootstrap.Modal(uploadModal).show();
    } else {
        uploadModal.classList.add('show');
        uploadModal.style.display = 'block';
    }
}

function selectUploadMethod(method) {
    const docType = document.getElementById('upload_doc_type').value;
    const applicantId = document.getElementById('upload_applicant_id').value;

    if (method === 'computer') {
        // Show computer upload form
        document.getElementById('step_choose_method').style.display = 'none';
        document.getElementById('step_computer_upload').style.display = 'block';

        document.getElementById('computer_applicant_id').value = applicantId;
        document.getElementById('computer_doc_type').value = docType;

    } else if (method === 'phone') {
        // Show QR code generation
        document.getElementById('step_choose_method').style.display = 'none';
        document.getElementById('step_phone_qr').style.display = 'block';

        generateQRCode(applicantId, docType);
    }
}

function backToMethodSelection() {
    document.getElementById('step_choose_method').style.display = 'block';
    document.getElementById('step_computer_upload').style.display = 'none';
    document.getElementById('step_phone_qr').style.display = 'none';

    // Clear interval if exists
    if (uploadCheckInterval) {
        clearInterval(uploadCheckInterval);
        uploadCheckInterval = null;
    }
}

async function generateQRCode(applicantId, docType) {
    try {
        // Show spinner
        document.getElementById('qr_code_spinner').style.display = 'block';
        document.getElementById('qr_code_display').style.display = 'none';

        // Request upload token from server
        const response = await fetch('/applicants/documents/generate-upload-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                applicant_id: applicantId,
                document_type: docType
            })
        });

        const data = await response.json();

        if (data.success) {
            uploadToken = data.token;
            const uploadUrl = window.location.origin + '/upload-document/' + uploadToken;

            // Clear previous QR code if any
            const qrContainer = document.getElementById('qr_code');
            qrContainer.innerHTML = '';

            // Generate QR code
            try {
                new QRCode(qrContainer, {
                    text: uploadUrl,
                    width: 300,
                    height: 300,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });

                // Hide spinner, show QR
                document.getElementById('qr_code_spinner').style.display = 'none';
                document.getElementById('qr_code_display').style.display = 'block';

                // Start checking for upload completion
                startUploadCheck(uploadToken, applicantId);
            } catch (error) {
                console.error('QR generation error:', error);
                alert('Failed to generate QR code');
            }
        } else {
            alert('Failed to generate upload link: ' + (data.message || 'Unknown error'));
        }

    } catch (error) {
        console.error('Error generating QR code:', error);
        alert('Failed to generate QR code. Please try again.');
    }
}

function startUploadCheck(token, applicantId) {
    document.getElementById('upload_status').style.display = 'block';
    document.getElementById('upload_status_text').textContent = 'Waiting for upload from phone...';

    uploadCheckInterval = setInterval(async () => {
        try {
            const response = await fetch('/applicants/documents/check-upload-status/' + token, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.status === 'completed') {
                clearInterval(uploadCheckInterval);
                document.getElementById('upload_status_text').innerHTML = '<i class="fas fa-check-circle mr-2"></i>Document uploaded successfully!';
                document.getElementById('upload_status').classList.remove('alert-info');
                document.getElementById('upload_status').classList.add('alert-success');

                setTimeout(() => {
                    // Hide modal
                    const uploadModal = document.getElementById('uploadModal');
                    if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
                        jQuery('#uploadModal').modal('hide');
                    } else if (typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getInstance(uploadModal).hide();
                    } else {
                        uploadModal.classList.remove('show');
                        uploadModal.style.display = 'none';
                    }
                    window.location.reload(); // Reload to show new document
                }, 2000);

            } else if (data.status === 'expired') {
                clearInterval(uploadCheckInterval);
                document.getElementById('upload_status_text').innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Upload link expired. Please generate a new one.';
                document.getElementById('upload_status').classList.remove('alert-info');
                document.getElementById('upload_status').classList.add('alert-warning');
            }

        } catch (error) {
            console.error('Error checking upload status:', error);
        }
    }, 3000); // Check every 3 seconds
}

// File preview for computer upload
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('document_file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Update label
                const label = fileInput.nextElementSibling;
                label.textContent = file.name;

                // Show preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('preview_image').src = e.target.result;
                        document.getElementById('file_preview').style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('file_preview').style.display = 'none';
                }
            }
        });
    }
});

// Clean up interval when modal closes
document.addEventListener('DOMContentLoaded', function() {
    const uploadModal = document.getElementById('uploadModal');
    if (uploadModal) {
        uploadModal.addEventListener('hidden.bs.modal', function () {
            if (uploadCheckInterval) {
                clearInterval(uploadCheckInterval);
                uploadCheckInterval = null;
            }
        });
    }
});
</script>

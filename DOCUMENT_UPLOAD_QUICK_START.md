# Document Upload Feature - Quick Start Guide

## Installation (One-time Setup)

### 1. Create Database Table
Run this SQL in your database:
```sql
USE sims_demo;

CREATE TABLE IF NOT EXISTS `document_upload_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('pending','completed','expired') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `applicant_id` (`applicant_id`),
  KEY `status` (`status`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Note**: Modify `applicant_id` to match your entity (e.g., `student_id`, `staff_id`)

### 2. Ensure Storage Directory Exists
```bash
mkdir -p storage/documents
chmod 755 storage/documents
```

## Usage Examples

### Using the Service Class (Recommended)

```php
// Include the service
require_once __DIR__ . '/app/Services/DocumentUploadService.php';

// Initialize for applicants
$documentService = new DocumentUploadService('applicants', 15);

// Generate upload token for QR code
$result = $documentService->generateToken('birth_certificate');
if ($result['success']) {
    $token = $result['token'];
    // Use $token to generate QR code
}

// Upload from computer
$result = $documentService->uploadFromComputer(
    $_FILES['document_file'],
    'birth_certificate',
    'Optional notes here'
);

// Upload from phone
$result = $documentService->uploadFromPhone(
    $token,
    $_FILES['document_file']
);

// Delete document
$result = $documentService->deleteDocument(123);

// Get all documents
$documents = $documentService->getDocuments();
```

### Using Direct Controller Methods

```php
// In your controller
public function generateUploadToken() {
    $service = new DocumentUploadService('applicants', Request::get('applicant_id'));
    $result = $service->generateToken(Request::get('document_type'));

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
```

## Frontend Integration

### 1. Include Modal in Your View
```php
<!-- At the bottom of your page -->
<?php require __DIR__ . '/_document_upload_modals.php'; ?>
```

### 2. Add Upload Button
```php
<button class="btn btn-primary"
        onclick="showUploadModal('birth_certificate', 'Birth Certificate', <?= $applicant['id'] ?>)">
    <i class="fas fa-upload mr-1"></i> Upload
</button>
```

### 3. Display Documents List
```php
<?php require __DIR__ . '/_show_tab_documents.php'; ?>
```

## Routes Setup

Add these routes to your `routes_tenant.php`:

```php
// Document Management
Router::post('/applicants/documents/generate-upload-token', 'ApplicantsController@generateUploadToken');
Router::get('/applicants/documents/check-upload-status/:token', 'ApplicantsController@checkUploadStatus');
Router::get('/upload-document/:token', 'ApplicantsController@showPhoneCapture');
Router::post('/applicants/documents/upload-from-phone', 'ApplicantsController@uploadFromPhone');
Router::post('/applicants/documents/upload', 'ApplicantsController@uploadDocument');
Router::post('/applicants/documents/delete', 'ApplicantsController@deleteDocument');
```

## Adapting for Different Entities

### For Students Module

1. **Update table names**:
   - `applicant_documents` → `student_documents`
   - `applicant_id` → `student_id`

2. **Update routes**:
   ```php
   Router::post('/students/documents/generate-upload-token', 'StudentsController@generateUploadToken');
   // ... etc
   ```

3. **Initialize service**:
   ```php
   $service = new DocumentUploadService('students', $studentId);
   ```

4. **Update views** (search and replace):
   - `applicant` → `student`
   - `/applicants/` → `/students/`

### For Staff Module

Same process as Students, but use:
- `staff_documents` table
- `staff_id` column
- `'staff'` entity type

## Document Types Configuration

Define your document types in the view or config:

```php
$requiredDocs = [
    'birth_certificate' => [
        'label' => 'Birth Certificate',
        'icon' => 'fa-file-alt',
        'required' => false
    ],
    'passport_photo' => [
        'label' => 'Passport Photo',
        'icon' => 'fa-camera',
        'required' => false
    ],
    // Add more as needed
];
```

## Testing Checklist

- [ ] Database table created
- [ ] Storage directory exists and is writable
- [ ] Routes configured
- [ ] Upload from computer works
- [ ] QR code generates successfully
- [ ] Mobile camera capture works
- [ ] Upload status polling works
- [ ] Documents display in list
- [ ] Delete works correctly
- [ ] Audit logs are created

## Common Issues

### QR Code Not Loading
- Check CDN: `https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js`
- Check browser console for JavaScript errors

### Upload Fails
- Check PHP settings: `upload_max_filesize` and `post_max_size`
- Verify storage directory permissions: `chmod 755 storage/documents`
- Check file size (max 5MB) and type (JPG, PNG, PDF)

### Token Expired
- Default expiry: 10 minutes
- Generate new QR code if expired

## Support & Documentation

- Full documentation: See `DOCUMENT_UPLOAD_FEATURE.md`
- Service class: `app/Services/DocumentUploadService.php`
- Example implementation: Applicants module

## Credits

- Developed by: Claude Code Assistant
- Date: November 2025
- License: Part of SchoolDynamics SIMS

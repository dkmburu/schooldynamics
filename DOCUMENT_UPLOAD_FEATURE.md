# Document Upload Feature - Reusable Component

## Overview
The Document Upload feature provides a flexible, secure way for users to upload documents either from their computer or using their mobile phone's camera via QR code scanning.

## Features
- **Dual Upload Methods**: Computer file upload or phone camera capture
- **QR Code Integration**: Generate secure, one-time QR codes for mobile uploads
- **Secure Tokens**: Time-limited (10 minutes), single-use tokens
- **Real-time Status**: Polls for upload completion
- **Campus Isolation**: Respects multi-tenant architecture
- **Audit Logging**: All uploads tracked in audit logs
- **File Validation**: Type and size restrictions
- **Transaction Safety**: Database transactions with rollback

## Database Requirements

### Table: `document_upload_tokens`
```sql
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
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `document_upload_tokens_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Note**: For reuse with other entities (students, staff, etc.), create additional tables or modify to use polymorphic relationships.

### Table: `[entity]_documents`
Example structure (modify entity name as needed):
```sql
CREATE TABLE IF NOT EXISTS `applicant_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `verification_status` enum('pending','uploaded','verified','rejected') DEFAULT 'uploaded',
  `upload_method` enum('computer','phone') DEFAULT 'computer',
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `document_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## File Structure

### Views
```
app/Views/
  ├── applicants/
  │   ├── _document_upload_modals.php    # Main upload modal
  │   ├── _show_tab_documents.php        # Documents tab UI
  ├── documents/
  │   └── phone_capture.php              # Mobile camera capture page
```

### Controllers
```
app/Controllers/
  └── ApplicantsController.php
      ├── generateUploadToken()          # Generate QR token
      ├── checkUploadStatus()            # Poll upload status
      ├── showPhoneCapture()             # Display mobile capture page
      ├── uploadFromPhone()              # Handle mobile uploads
      ├── uploadDocument()               # Handle computer uploads
      └── deleteDocument()               # Delete documents
```

### Storage
```
storage/
  └── documents/
      └── [entity_id]/                   # Documents organized by entity
          ├── birth_certificate_xxxxx.jpg
          ├── passport_photo_xxxxx.jpg
          └── ...
```

## Routes Configuration

```php
// Document Management
Router::post('/applicants/documents/generate-upload-token', 'ApplicantsController@generateUploadToken');
Router::get('/applicants/documents/check-upload-status/:token', 'ApplicantsController@checkUploadStatus');
Router::get('/upload-document/:token', 'ApplicantsController@showPhoneCapture');
Router::post('/applicants/documents/upload-from-phone', 'ApplicantsController@uploadFromPhone');
Router::post('/applicants/documents/upload', 'ApplicantsController@uploadDocument');
Router::post('/applicants/documents/delete', 'ApplicantsController@deleteDocument');
```

## Usage Instructions

### 1. Include Modal in Your View

```php
<!-- Include Document Upload Modals -->
<?php require __DIR__ . '/_document_upload_modals.php'; ?>
```

### 2. Add Upload Button in Your UI

```php
<button class="btn btn-primary" onclick="showUploadModal('birth_certificate', 'Birth Certificate', <?= $applicant['id'] ?>)">
    <i class="fas fa-upload mr-1"></i> Upload
</button>
```

### 3. Display Documents

```php
<?php require __DIR__ . '/_show_tab_documents.php'; ?>
```

## Reusability Guide

### For Students Module

1. **Create student_documents table** (similar structure to applicant_documents)
2. **Create StudentsDocumentController** or add methods to StudentsController
3. **Copy and modify views**:
   - Rename `applicant_id` to `student_id`
   - Update routes from `/applicants/` to `/students/`
   - Update JavaScript function calls

4. **Update routes**:
```php
Router::post('/students/documents/generate-upload-token', 'StudentsController@generateUploadToken');
Router::get('/students/documents/check-upload-status/:token', 'StudentsController@checkUploadStatus');
// ... etc
```

### For Staff Module

1. **Create staff_documents table**
2. **Create StaffDocumentController**
3. **Modify document types** to suit staff requirements:
```php
$requiredDocs = [
    'cv_resume' => ['label' => 'CV/Resume', 'icon' => 'fa-file-alt', 'required' => true],
    'id_copy' => ['label' => 'ID Copy', 'icon' => 'fa-id-card', 'required' => true],
    'certificates' => ['label' => 'Certificates', 'icon' => 'fa-certificate', 'required' => false],
    // ... add more as needed
];
```

### For General Purpose

Consider creating a **DocumentUploadService** class:

```php
// app/Services/DocumentUploadService.php
class DocumentUploadService {
    private $entityType;    // 'applicant', 'student', 'staff', etc.
    private $entityId;

    public function generateToken($entityId, $documentType) { ... }
    public function uploadFromComputer($file, $entityId, $documentType) { ... }
    public function uploadFromPhone($token, $file) { ... }
    public function deleteDocument($documentId) { ... }
}
```

## Configuration

### Document Types Configuration
Create a config file for document types:

```php
// config/document_types.php
return [
    'applicants' => [
        'birth_certificate' => ['label' => 'Birth Certificate', 'required' => false],
        'previous_report' => ['label' => 'Previous School Report', 'required' => false],
        // ...
    ],
    'students' => [
        'birth_certificate' => ['label' => 'Birth Certificate', 'required' => true],
        'medical_records' => ['label' => 'Medical Records', 'required' => false],
        // ...
    ],
    'staff' => [
        'cv_resume' => ['label' => 'CV/Resume', 'required' => true],
        'certificates' => ['label' => 'Certificates', 'required' => false],
        // ...
    ]
];
```

### Upload Settings

```php
// config/uploads.php
return [
    'max_file_size' => 5 * 1024 * 1024,  // 5MB
    'allowed_types' => [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/pdf'
    ],
    'token_expiry_minutes' => 10,
    'storage_path' => 'storage/documents/'
];
```

## Security Considerations

1. **Token Security**:
   - Tokens expire after 10 minutes
   - Single-use tokens (status changes to 'completed' after use)
   - 64-character random hex tokens (bin2hex(random_bytes(32)))

2. **Campus Isolation**:
   - All operations verify entity belongs to current campus
   - Cross-campus access is prevented

3. **File Validation**:
   - Type checking (MIME type validation)
   - Size limits enforced
   - Sanitized filenames (no user input in filename)

4. **Permissions**:
   - Requires `Students.write` permission or ADMIN role
   - Authentication required for all operations

## JavaScript API

### Functions Available

```javascript
// Show upload modal
showUploadModal(docType, docLabel, entityId)

// Select upload method
selectUploadMethod(method)  // 'computer' or 'phone'

// Generate QR code
generateQRCode(entityId, docType)

// Start upload status checking
startUploadCheck(token, entityId)

// Delete document
deleteDocument(documentId, entityId)

// Show/hide document files
showDocumentFiles(type)
```

## CDN Dependencies

```html
<!-- QR Code Generator -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
```

## Mobile Camera Capture Flow

1. User clicks "Capture with Phone Camera"
2. System generates secure token and stores in database
3. QR code is generated with URL: `/upload-document/{token}`
4. User scans QR code with mobile phone
5. Mobile browser opens camera capture page
6. User captures photo using device camera
7. Photo is uploaded via AJAX
8. Token is marked as 'completed'
9. Desktop browser polls status and detects completion
10. Page reloads to show new document

## Troubleshooting

### QR Code Not Generating
- Check that qrcodejs library is loaded
- Verify `document_upload_tokens` table exists
- Check browser console for errors

### Upload Fails
- Verify file size is under 5MB
- Check file type is allowed (JPG, PNG, PDF)
- Ensure storage directory has write permissions
- Check PHP `upload_max_filesize` and `post_max_size` settings

### Token Expired
- Default expiry is 10 minutes
- Generate a new QR code if expired
- Check server time is synchronized

## Future Enhancements

1. **Document Versioning**: Keep history of replaced documents
2. **Bulk Upload**: Upload multiple documents at once
3. **Image Compression**: Automatically compress large images
4. **OCR Integration**: Extract text from scanned documents
5. **Document Templates**: Predefined document requirements per entity type
6. **Email Notifications**: Notify users when documents are verified/rejected
7. **Document Expiry**: Track document expiration dates
8. **Digital Signatures**: Support for digitally signed documents

## License & Credits

- QRCode.js: MIT License
- Part of SchoolDynamics SIMS
- Developer: Claude Code Assistant
- Date: November 2025

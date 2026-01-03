<?php
/**
 * Tenant Routes
 * Routes for individual school tenants
 */

// Public routes (no authentication required)
Router::get('/', function() {
    if (isAuthenticated()) {
        Response::redirect('/dashboard');
    } else {
        Response::view('tenant.login');
    }
});

Router::get('/login', function() {
    if (isAuthenticated()) {
        Response::redirect('/dashboard');
    }
    Response::view('tenant.login');
});

Router::post('/login', 'AuthController@login');

Router::get('/logout', 'AuthController@logout');

Router::get('/forgot-password', function() {
    Response::view('tenant.forgot-password');
});

Router::post('/forgot-password', 'AuthController@forgotPassword');

// Public Authorization Approval (no login required)
Router::get('/authorize/:token', 'AuthorizationController@showApprovalPage');
Router::post('/authorize/:token', 'AuthorizationController@processApproval');

// Protected routes (require authentication - to be enforced by middleware)
Router::get('/dashboard', 'DashboardController@index');

// Campus Switcher
Router::get('/switch-campus', function() {
    if (!isAuthenticated()) {
        Response::redirect('/login');
    }

    $campusId = Request::get('campus_id');

    if ($campusId === 'all') {
        // Allow admins to view all campuses
        if (Gate::hasRole('ADMIN')) {
            $_SESSION['current_campus_id'] = 'all';
            flash('success', 'Viewing all campuses');
        } else {
            flash('error', 'You do not have permission to view all campuses');
        }
    } elseif (!empty($campusId) && is_numeric($campusId)) {
        // Verify campus exists
        try {
            $pdo = Database::getTenantConnection();
            $stmt = $pdo->prepare("SELECT * FROM campuses WHERE id = ? AND is_active = 1");
            $stmt->execute([$campusId]);
            $campus = $stmt->fetch();

            if ($campus) {
                $_SESSION['current_campus_id'] = $campusId;
                flash('success', 'Switched to ' . $campus['campus_name']);
            } else {
                flash('error', 'Campus not found');
            }
        } catch (Exception $e) {
            flash('error', 'Failed to switch campus');
            logMessage("Campus switch error: " . $e->getMessage(), 'error');
        }
    }

    Response::redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
});

// Applicants
Router::get('/applicants', 'ApplicantsController@index');
Router::get('/applicants/create', 'ApplicantsController@create');
Router::post('/applicants/create', 'ApplicantsController@store');
Router::get('/applicants/screening', 'ApplicantsController@screening');
Router::post('/applicants/decision', 'ApplicantsController@decision');
Router::post('/applicants/schedule-interview', 'ApplicantsController@scheduleInterview');
Router::post('/applicants/interview-outcome', 'ApplicantsController@interviewOutcome');
Router::post('/applicants/schedule-exam', 'ApplicantsController@scheduleExam');
Router::post('/applicants/exam-score', 'ApplicantsController@examScore');
Router::post('/applicants/stage-transition', 'ApplicantsController@stageTransition');
Router::post('/applicants/update', 'ApplicantsController@update');

// Guardian Management
Router::post('/applicants/guardians/store', 'ApplicantsController@storeGuardian');
Router::post('/applicants/guardians/update', 'ApplicantsController@updateGuardian');
Router::post('/applicants/guardians/set-primary', 'ApplicantsController@setPrimaryGuardian');
Router::post('/applicants/guardians/delete', 'ApplicantsController@deleteGuardian');

// Document Management
Router::post('/applicants/documents/generate-upload-token', 'ApplicantsController@generateUploadToken');
Router::get('/applicants/documents/check-upload-status/:token', 'ApplicantsController@checkUploadStatus');
Router::get('/upload-document/:token', 'ApplicantsController@showPhoneCapture');
Router::post('/applicants/documents/upload-from-phone', 'ApplicantsController@uploadFromPhone');
Router::post('/applicants/documents/upload', 'ApplicantsController@uploadDocument');
Router::get('/applicants/documents/download-logs', 'ApplicantsController@downloadLogs');
Router::post('/applicants/documents/delete', 'ApplicantsController@deleteDocument');

// Sibling/Family Management
Router::get('/applicants/siblings/search', 'ApplicantsController@searchFamilyMembers');
Router::post('/applicants/siblings/store', 'ApplicantsController@storeSibling');
Router::post('/applicants/siblings/delete', 'ApplicantsController@deleteSibling');

// Authorization/Consent Management
Router::post('/applicants/authorization/send', 'ApplicantsController@sendAuthorizationRequest');
Router::post('/applicants/authorization/approve-by-code', 'ApplicantsController@approveAuthorizationByCode');
Router::get('/applicants/:id/authorization-history', 'ApplicantsController@getAuthorizationHistory');

// Reusable Document Download (works with any entity type)
Router::get('/download/:entityType/:id', 'DocumentDownloadController@download');

// Admission Process
Router::post('/applicants/initiate-pre-admission', 'ApplicantsController@initiatePreAdmission');
Router::post('/applicants/record-admission-payment', 'ApplicantsController@recordAdmissionPayment');
Router::get('/applicants/:id/finances-tab', 'ApplicantsController@financesTab');

Router::get('/applicants/:id', 'ApplicantsController@show');
Router::get('/applicants/:id/edit', 'ApplicantsController@edit');
Router::post('/applicants/:id/edit', 'ApplicantsController@update');

// Students
Router::get('/students', 'StudentsController@index');
Router::get('/students/:id', 'StudentsController@show');
Router::get('/students/:id/edit', 'StudentsController@edit');
Router::post('/students/:id/edit', 'StudentsController@update');

// Student sub-actions (AJAX)
Router::post('/students/:id/medical', 'StudentsController@updateMedical');
Router::post('/students/:id/education-history', 'StudentsController@addEducationHistory');
Router::post('/students/:id/education-history/:historyId/delete', 'StudentsController@deleteEducationHistory');
Router::post('/students/:id/change-class', 'StudentsController@changeClass');
Router::post('/students/:id/transport', 'StudentsController@assignTransport');
Router::post('/students/:id/transport/remove', 'StudentsController@removeTransport');
Router::post('/students/:id/documents', 'StudentsController@uploadDocument');
Router::post('/students/:id/documents/:documentId/delete', 'StudentsController@deleteDocument');
Router::post('/students/:id/guardians', 'StudentsController@addGuardian');
Router::post('/students/:id/guardians/:guardianId/update', 'StudentsController@updateGuardian');
Router::post('/students/:id/guardians/:guardianId/toggle-primary', 'StudentsController@togglePrimaryGuardian');
Router::post('/students/:id/guardians/:guardianId/delete', 'StudentsController@removeGuardian');

// =========================================================================
// FINANCE MODULE
// =========================================================================

// Dashboard
Router::get('/finance', 'FinanceController@index');

// Chart of Accounts
Router::get('/finance/chart-of-accounts', 'FinanceController@chartOfAccounts');
Router::post('/finance/chart-of-accounts/store', 'FinanceController@storeAccount');
Router::post('/finance/chart-of-accounts/update', 'FinanceController@updateAccount');

// Fee Categories
Router::get('/finance/fee-categories', 'FinanceController@feeCategories');
Router::post('/finance/fee-categories/store', 'FinanceController@storeFeeCategory');
Router::post('/finance/fee-categories/update', 'FinanceController@updateFeeCategory');

// Fee Items
Router::get('/finance/fee-items', 'FinanceController@feeItems');
Router::post('/finance/fee-items/store', 'FinanceController@storeFeeItem');
Router::post('/finance/fee-items/update', 'FinanceController@updateFeeItem');

// Fee Structures
Router::get('/finance/fee-structures', 'FinanceController@feeStructures');
Router::get('/finance/fee-structures/create', 'FinanceController@editFeeStructure');
Router::get('/finance/fee-structures/:id', 'FinanceController@editFeeStructure');
Router::post('/finance/fee-structures/save', 'FinanceController@saveFeeStructure');

// Transport Tariffs
Router::get('/finance/transport-tariffs', 'FinanceController@transportTariffs');
Router::post('/finance/transport-tariffs/store', 'FinanceController@storeTransportTariff');
Router::post('/finance/transport-tariffs/update', 'FinanceController@updateTransportTariff');
Router::post('/finance/transport-zones/store', 'FinanceController@storeTransportZone');
Router::post('/finance/transport-zones/update', 'FinanceController@updateTransportZone');

// Invoicing
Router::get('/finance/invoices', 'FinanceController@invoices');
Router::get('/finance/invoices/generate', 'FinanceController@generateInvoices');
Router::post('/finance/invoices/generate', 'FinanceController@processInvoiceGeneration');
Router::get('/finance/invoices/:id', 'FinanceController@viewInvoice');

// Credit Notes
Router::get('/finance/credit-notes', 'FinanceController@creditNotes');
Router::post('/finance/credit-notes/store', 'FinanceController@storeCreditNote');
Router::post('/finance/credit-notes/:id/approve', 'FinanceController@approveCreditNote');
Router::post('/finance/credit-notes/:id/apply', 'FinanceController@applyCreditNote');

// Payments
Router::get('/finance/payments', 'FinanceController@payments');
Router::get('/finance/payments/record', 'FinanceController@recordPayment');
Router::post('/finance/payments/record', 'FinanceController@storePayment');
Router::post('/finance/payments/store', 'FinanceController@storePayment');
Router::get('/finance/payments/:id', 'FinanceController@viewPayment');
Router::post('/finance/payments/:id/send-sms', 'FinanceController@sendReceiptSMS');
Router::post('/finance/payments/:id/send-email', 'FinanceController@sendReceiptEmail');

// Student & Family Accounts
Router::get('/finance/student-accounts', 'FinanceController@studentAccounts');
Router::get('/finance/student-accounts/:id', 'FinanceController@viewStudentAccount');
Router::get('/finance/family-accounts', 'FinanceController@familyAccounts');
Router::post('/finance/family-accounts/create', 'FinanceController@createFamilyAccount');
Router::get('/finance/family-accounts/:id', 'FinanceController@showFamilyAccount');

// Finance API
Router::get('/finance/api/search-accounts', 'FinanceController@searchAccounts');

// Reports
Router::get('/finance/reports/collection', 'FinanceController@collectionReport');
Router::get('/finance/reports/outstanding', 'FinanceController@outstandingReport');
Router::get('/finance/reports/income', 'FinanceController@incomeStatement');

// Academics
Router::get('/academics/classes', 'ClassesController@index');
Router::get('/academics/subjects', 'SubjectsController@index');
Router::get('/academics/attendance', 'AttendanceController@index');

// Assessment
Router::get('/assessment/exams', 'ExamsController@index');
Router::get('/assessment/grades', 'GradesController@index');

// Communication
Router::get('/communication/messages', 'MessagesController@index');
Router::get('/communication/templates', 'MessageTemplatesController@index');

// Reports
Router::get('/reports', 'ReportsController@index');

// Settings
Router::get('/settings', 'SettingsController@index');
Router::post('/settings', 'SettingsController@update');

// User Profile
Router::get('/profile', 'ProfileController@index');
Router::post('/profile', 'ProfileController@update');

// =========================================================================
// WORKFLOW / TASKS MODULE
// =========================================================================

// Task Inbox (My Tasks)
Router::get('/tasks', 'WorkflowController@inbox');
Router::get('/tasks/escalations', 'WorkflowController@escalations');
Router::get('/tasks/:id', 'WorkflowController@viewTask');

// Task Actions
Router::post('/tasks/claim', 'WorkflowController@claimTask');
Router::post('/tasks/release', 'WorkflowController@releaseTask');
Router::post('/tasks/complete', 'WorkflowController@completeTask');
Router::post('/tasks/reassign', 'WorkflowController@reassignTask');

// Escalations
Router::post('/tasks/escalations/acknowledge', 'WorkflowController@acknowledgeEscalation');

// Workflow Tickets
Router::get('/workflow/tickets/:id', 'WorkflowController@viewTicket');
Router::get('/workflow/entity/:entityType/:entityId', 'WorkflowController@entityTickets');
Router::post('/workflow/start', 'WorkflowController@startWorkflow');
Router::post('/workflow/cancel', 'WorkflowController@cancelTicket');
Router::post('/workflow/pause', 'WorkflowController@pauseTicket');
Router::post('/workflow/resume', 'WorkflowController@resumeTicket');
Router::post('/workflow/comment', 'WorkflowController@addComment');

// Workflow Admin (Designer)
Router::get('/workflow/admin', 'WorkflowController@listWorkflows');
Router::post('/workflow/admin/create', 'WorkflowController@createWorkflow');
Router::post('/workflow/admin/clone', 'WorkflowController@cloneWorkflow');
Router::post('/workflow/admin/delete', 'WorkflowController@deleteWorkflow');
Router::post('/workflow/admin/create-from-template', 'WorkflowController@createFromTemplate');
Router::get('/workflow/admin/:id', 'WorkflowController@editWorkflow');
Router::get('/workflow/admin/:id/json', 'WorkflowController@getWorkflowJson');
Router::post('/workflow/admin/save', 'WorkflowController@saveWorkflow');

// Workflow API Endpoints
Router::get('/api/workflow/available', 'WorkflowController@getAvailableWorkflows');
Router::get('/api/workflow/task-counts', 'WorkflowController@getTaskCounts');

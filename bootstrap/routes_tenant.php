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
Router::get('/applicants/guardians/search', 'ApplicantsController@searchGuardian');
Router::get('/applicants/guardians/get/:id', 'ApplicantsController@getGuardian');
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
Router::post('/finance/transport-tariffs/store', 'FinanceController@storeTariff');
Router::post('/finance/transport-tariffs/update', 'FinanceController@updateTariff');
Router::post('/finance/transport-zones/store', 'FinanceController@storeZone');
Router::post('/finance/transport-zones/update', 'FinanceController@updateZone');

// Invoicing
Router::get('/finance/invoices', 'FinanceController@invoices');
Router::get('/finance/invoices/generate', 'FinanceController@generateInvoices');
Router::post('/finance/invoices/generate', 'FinanceController@processGenerateInvoices');
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

// Expenses (Suppliers, Purchase Orders, GRN, Invoices, Payments)
Router::get('/finance/expenses', 'ExpensesController@index');
Router::get('/finance/expenses/:tab', 'ExpensesController@index');

// Suppliers API
Router::get('/finance/expenses/api/suppliers', 'ExpensesController@getSuppliers');
Router::post('/finance/expenses/api/suppliers', 'ExpensesController@storeSupplier');
Router::get('/finance/expenses/api/suppliers/:id', 'ExpensesController@getSupplier');
Router::post('/finance/expenses/api/suppliers/:id', 'ExpensesController@updateSupplier');
Router::post('/finance/expenses/api/suppliers/:id/delete', 'ExpensesController@deleteSupplier');

// Purchase Orders API
Router::get('/finance/expenses/api/purchase-orders', 'ExpensesController@getPurchaseOrders');
Router::post('/finance/expenses/api/purchase-orders', 'ExpensesController@storePurchaseOrder');
Router::get('/finance/expenses/api/purchase-orders/:id', 'ExpensesController@getPurchaseOrder');
Router::post('/finance/expenses/api/purchase-orders/:id', 'ExpensesController@updatePurchaseOrder');
Router::post('/finance/expenses/api/purchase-orders/:id/approve', 'ExpensesController@approvePurchaseOrder');
Router::post('/finance/expenses/api/purchase-orders/:id/cancel', 'ExpensesController@cancelPurchaseOrder');

// GRN API
Router::get('/finance/expenses/api/grn', 'ExpensesController@getGRNs');
Router::post('/finance/expenses/api/grn', 'ExpensesController@storeGRN');
Router::get('/finance/expenses/api/grn/:id', 'ExpensesController@getGRN');
Router::post('/finance/expenses/api/grn/:id/confirm', 'ExpensesController@confirmGRN');

// Supplier Invoices API
Router::get('/finance/expenses/api/invoices', 'ExpensesController@getSupplierInvoices');
Router::post('/finance/expenses/api/invoices', 'ExpensesController@storeSupplierInvoice');
Router::get('/finance/expenses/api/invoices/:id', 'ExpensesController@getSupplierInvoice');
Router::post('/finance/expenses/api/invoices/:id', 'ExpensesController@updateSupplierInvoice');
Router::post('/finance/expenses/api/invoices/:id/approve', 'ExpensesController@approveSupplierInvoice');

// Supplier Payments API
Router::get('/finance/expenses/api/payments', 'ExpensesController@getSupplierPayments');
Router::post('/finance/expenses/api/payments', 'ExpensesController@storeSupplierPayment');
Router::get('/finance/expenses/api/payments/:id', 'ExpensesController@getSupplierPayment');
Router::post('/finance/expenses/api/payments/:id/approve', 'ExpensesController@approveSupplierPayment');

// Budgets
Router::get('/finance/budgets', 'BudgetsController@index');
Router::get('/finance/budgets/:tab', 'BudgetsController@index');

// Budget API
Router::get('/finance/budgets/api/budgets/:id', 'BudgetsController@getBudget');
Router::post('/finance/budgets/api/budgets', 'BudgetsController@storeBudget');
Router::post('/finance/budgets/api/budgets/:id', 'BudgetsController@updateBudget');
Router::post('/finance/budgets/api/budgets/:id/delete', 'BudgetsController@deleteBudget');
Router::post('/finance/budgets/api/budgets/:id/submit', 'BudgetsController@submitForApproval');
Router::post('/finance/budgets/api/budgets/:id/approve', 'BudgetsController@approveBudget');
Router::post('/finance/budgets/api/budgets/:id/reject', 'BudgetsController@rejectBudget');
Router::post('/finance/budgets/api/budgets/:id/allocations', 'BudgetsController@updateAllocations');
Router::post('/finance/budgets/api/budgets/:id/replicate', 'BudgetsController@replicateAllocations');

// Budget Overruns API
Router::post('/finance/budgets/api/overruns/:id/approve', 'BudgetsController@approveOverrun');
Router::post('/finance/budgets/api/overruns/:id/reject', 'BudgetsController@rejectOverrun');
Router::get('/finance/budgets/api/check-availability', 'BudgetsController@checkBudgetAvailability');
Router::post('/finance/budgets/api/request-overrun', 'BudgetsController@requestOverrunApproval');

// Budget Periods API
Router::post('/finance/budgets/api/periods', 'BudgetsController@storePeriod');

// =========================================================================
// HR & PAYROLL MODULE
// =========================================================================

// Dashboard
Router::get('/hr-payroll', 'HRPayrollController@index');

// Staff Management
Router::get('/hr-payroll/staff', 'HRPayrollController@staff');
Router::get('/hr-payroll/staff/create', 'HRPayrollController@createStaff');
Router::post('/hr-payroll/staff', 'HRPayrollController@storeStaff');
Router::get('/hr-payroll/staff/:id', 'HRPayrollController@showStaff');
Router::get('/hr-payroll/staff/:id/edit', 'HRPayrollController@editStaff');
Router::post('/hr-payroll/staff/:id', 'HRPayrollController@updateStaff');

// Payroll Processing
Router::get('/hr-payroll/payroll', 'HRPayrollController@payroll');

// Payslips
Router::get('/hr-payroll/payslips', 'HRPayrollController@payslips');

// Salary Structures
Router::get('/hr-payroll/salary-structures', 'HRPayrollController@salaryStructures');

// Allowances & Deductions (Pay Components)
Router::get('/hr-payroll/components', 'HRPayrollController@components');

// Loans & Advances
Router::get('/hr-payroll/loans', 'HRPayrollController@loans');

// Statutory Deductions
Router::get('/hr-payroll/statutory', 'HRPayrollController@statutory');

// Payroll Reports
Router::get('/hr-payroll/reports', 'HRPayrollController@reports');

// Staff Document Uploads
Router::post('/hr-payroll/documents/generate-upload-token', 'HRPayrollController@generateDocumentUploadToken');
Router::get('/hr-payroll/documents/check-upload-status/:token', 'HRPayrollController@checkDocumentUploadStatus');
Router::get('/hr-payroll/documents/capture/:token', 'HRPayrollController@showPhoneCapture');
Router::post('/hr-payroll/documents/upload-from-phone', 'HRPayrollController@uploadDocumentFromPhone');
Router::post('/hr-payroll/documents/upload', 'HRPayrollController@uploadDocument');
Router::post('/hr-payroll/documents/delete', 'HRPayrollController@deleteDocument');
Router::get('/hr-payroll/documents/download/:id', 'HRPayrollController@downloadDocument');
Router::post('/hr-payroll/staff/upload-photo', 'HRPayrollController@uploadStaffPhotoAjax');

// Academics
Router::get('/academics/classes', 'ClassesController@index');
Router::get('/academics/subjects', 'SubjectsController@index');
Router::get('/academics/attendance', 'AttendanceController@index');

// Assessment
Router::get('/assessment/exams', 'ExamsController@index');
Router::get('/assessment/grades', 'GradesController@index');

// Communication - Message Queue
Router::get('/communication/messages', 'MessagesController@index');
Router::get('/communication/messages/export', 'MessagesController@export');
Router::get('/communication/messages/:id', 'MessagesController@show');
Router::post('/communication/messages/:id/retry', 'MessagesController@retry');
Router::post('/communication/messages/:id/cancel', 'MessagesController@cancel');
Router::post('/communication/messages/bulk-retry', 'MessagesController@bulkRetry');

// Communication - Templates
Router::get('/communication/templates', 'MessageTemplatesController@index');
Router::get('/communication/templates/create', 'MessageTemplatesController@create');
Router::post('/communication/templates', 'MessageTemplatesController@store');
Router::get('/communication/templates/:id/edit', 'MessageTemplatesController@edit');
Router::post('/communication/templates/:id', 'MessageTemplatesController@update');
Router::post('/communication/templates/:id/delete', 'MessageTemplatesController@delete');
Router::post('/communication/templates/:id/toggle', 'MessageTemplatesController@toggleStatus');
Router::get('/communication/templates/:id/preview', 'MessageTemplatesController@preview');

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

// Field Audit Trail API Endpoints
Router::get('/api/audit/check', 'AuditController@checkFieldHistory');
Router::get('/api/audit/history', 'AuditController@getFieldHistory');
Router::get('/api/audit/entity/:entityType/:entityId', 'AuditController@getEntityHistory');

// =========================================================================
// SETTINGS - USERS & ROLES MANAGEMENT (RBAC)
// =========================================================================

// Users Management
Router::get('/settings/users', 'UsersController@index');
Router::get('/settings/users/create', 'UsersController@create');
Router::post('/settings/users', 'UsersController@store');
Router::get('/settings/users/:id/edit', 'UsersController@edit');
Router::post('/settings/users/:id', 'UsersController@update');
Router::post('/settings/users/:id/delete', 'UsersController@destroy');
Router::post('/settings/users/:id/toggle-status', 'UsersController@toggleStatus');

// Roles Management
Router::get('/settings/roles/create', 'RolesController@create');
Router::post('/settings/roles', 'RolesController@store');
Router::get('/settings/roles/:id/edit', 'RolesController@edit');
Router::post('/settings/roles/:id', 'RolesController@update');
Router::post('/settings/roles/:id/delete', 'RolesController@destroy');
Router::post('/settings/roles/:id/clone', 'RolesController@clone');

// =========================================================================
// PARENT PORTAL (External Users - SMS OTP Authentication)
// =========================================================================

// Public Parent Routes (no parent auth required)
Router::get('/parent', function() {
    if (isset($_SESSION['parent_logged_in']) && $_SESSION['parent_logged_in'] === true) {
        Response::redirect('/parent/dashboard');
    } else {
        Response::redirect('/parent/login');
    }
});

// Login Flow (SMS OTP)
Router::get('/parent/login', 'ParentAuthController@showLogin');
Router::post('/parent/login', 'ParentAuthController@login');
Router::get('/parent/verify-otp', 'ParentAuthController@showVerifyOtp');
Router::post('/parent/verify-otp', 'ParentAuthController@verifyOtp');
Router::post('/parent/resend-otp', 'ParentAuthController@resendOtp');
Router::get('/parent/logout', 'ParentAuthController@logout');

// Registration Flow (SMS Magic Link)
Router::get('/parent/register', 'ParentAuthController@showRegister');
Router::post('/parent/register', 'ParentAuthController@register');
Router::get('/parent/registration-sent', 'ParentAuthController@showRegistrationSent');
Router::get('/parent/activate/:token', 'ParentAuthController@verifyMagicLink');

// Protected Parent Routes (require parent auth)
Router::get('/parent/dashboard', 'ParentDashboardController@index');
Router::get('/parent/profile', 'ParentDashboardController@profile');

// Notifications
Router::get('/parent/notifications', 'ParentNotificationsController@index');
Router::get('/parent/notifications/:id', 'ParentNotificationsController@show');
Router::post('/parent/notifications/:id/mark-read', 'ParentNotificationsController@markAsRead');
Router::get('/parent/notifications/mark-all-read', 'ParentNotificationsController@markAllAsRead');
Router::post('/parent/notifications/:id/dismiss', 'ParentNotificationsController@dismiss');

// Contacts
Router::get('/parent/contacts', 'ParentContactsController@index');

// Student Linking
Router::get('/parent/link-student', 'ParentDashboardController@showLinkStudent');
Router::post('/parent/link-student', 'ParentDashboardController@linkStudent');
Router::get('/parent/link-requests', 'ParentDashboardController@linkRequests');

// Child-specific views (parent must have access to the child)
Router::get('/parent/child/:studentId/profile', 'ParentDashboardController@childProfile');
Router::get('/parent/child/:studentId/fees', 'ParentDashboardController@fees');
Router::get('/parent/child/:studentId/attendance', 'ParentDashboardController@attendance');

// =========================================================================
// PORTAL MANAGEMENT (Admin interface for external portals)
// =========================================================================

// Parent Portal Management
Router::get('/portals/parents', 'PortalManagementController@parentAccounts');
Router::get('/portals/parents/pending', 'PortalManagementController@parentPending');
Router::get('/portals/parents/notifications', 'PortalManagementController@parentNotifications');
Router::post('/portals/parents/notifications/send', 'PortalManagementController@sendParentNotification');
Router::get('/portals/parents/settings', 'PortalManagementController@parentSettings');
Router::post('/portals/parents/settings', 'PortalManagementController@updateParentSettings');
Router::get('/portals/parents/:id', 'PortalManagementController@viewParent');
Router::post('/portals/parents/:id/approve', 'PortalManagementController@approveParent');
Router::post('/portals/parents/:id/reject', 'PortalManagementController@rejectParent');
Router::post('/portals/parents/:id/suspend', 'PortalManagementController@suspendParent');
Router::post('/portals/parents/:id/activate', 'PortalManagementController@activateParent');
Router::post('/portals/parents/:id/resend-activation', 'PortalManagementController@resendActivation');
Router::post('/portals/parents/:id/delete', 'PortalManagementController@deleteParent');
Router::post('/portals/parents/approve-all', 'PortalManagementController@approveAllPending');

// Student Linkage Request Management
Router::get('/portals/parents/linkage-requests', 'PortalManagementController@linkageRequests');
Router::get('/portals/parents/linkage-requests/:id', 'PortalManagementController@viewLinkageRequest');
Router::post('/portals/parents/linkage-requests/:id/approve', 'PortalManagementController@approveLinkageRequest');
Router::post('/portals/parents/linkage-requests/:id/reject', 'PortalManagementController@rejectLinkageRequest');
Router::post('/portals/parents/linkage-requests/approve-bulk', 'PortalManagementController@approveBulkLinkageRequests');

// Supplier Portal Management (Future)
// Router::get('/portals/suppliers', 'PortalManagementController@supplierAccounts');

// Driver Portal Management (Future)
// Router::get('/portals/drivers', 'PortalManagementController@driverAccounts');

// Alumni Portal Management (Future)
// Router::get('/portals/alumni', 'PortalManagementController@alumniAccounts');

// =========================================================================
// CALENDAR MODULE
// =========================================================================

// Calendar Dashboard
Router::get('/calendar', 'CalendarController@index');

// Academic Terms
Router::get('/calendar/terms', 'CalendarController@terms');
Router::get('/calendar/terms/create', 'CalendarController@createTerm');
Router::post('/calendar/terms/create', 'CalendarController@storeTerm');
Router::get('/calendar/terms/:id', 'CalendarController@showTerm');
Router::get('/calendar/terms/:id/edit', 'CalendarController@editTerm');
Router::post('/calendar/terms/:id/edit', 'CalendarController@updateTerm');
Router::post('/calendar/terms/:id/delete', 'CalendarController@deleteTerm');

// Important Dates
Router::post('/calendar/terms/:id/dates', 'CalendarController@addImportantDate');
Router::post('/calendar/dates/:id/update', 'CalendarController@updateImportantDate');
Router::post('/calendar/dates/:id/delete', 'CalendarController@deleteImportantDate');

// National Holidays
Router::get('/calendar/holidays', 'CalendarController@holidays');

// Events Management
Router::get('/calendar/events', 'EventsController@index');
Router::get('/calendar/events/create', 'EventsController@create');
Router::post('/calendar/events/create', 'EventsController@store');
Router::get('/calendar/events/:id', 'EventsController@show');
Router::get('/calendar/events/:id/edit', 'EventsController@edit');
Router::post('/calendar/events/:id/edit', 'EventsController@update');
Router::post('/calendar/events/:id/delete', 'EventsController@delete');

// Communication Module - Broadcasts
Router::get('/communication/broadcasts', 'BroadcastsController@index');
Router::get('/communication/broadcasts/create', 'BroadcastsController@create');
Router::post('/communication/broadcasts/create', 'BroadcastsController@store');
Router::get('/communication/broadcasts/:id', 'BroadcastsController@show');
Router::get('/communication/broadcasts/:id/edit', 'BroadcastsController@edit');
Router::post('/communication/broadcasts/:id/edit', 'BroadcastsController@update');
Router::post('/communication/broadcasts/:id/delete', 'BroadcastsController@delete');
Router::post('/communication/broadcasts/:id/cancel', 'BroadcastsController@cancel');

// Communication Credits
Router::get('/communication/credits', 'CreditsController@index');
Router::post('/communication/credits/purchase', 'CreditsController@purchase');

// Broadcast Approvals (placeholder for future implementation)
Router::get('/communication/approvals', function() {
    flash('info', 'Broadcast Approvals module coming soon');
    Response::redirect('/communication/broadcasts');
});

// Broadcast History (placeholder for future implementation)
Router::get('/communication/history', function() {
    flash('info', 'Broadcast History module coming soon');
    Response::redirect('/communication/broadcasts');
});

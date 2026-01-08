<?php
/**
 * Message Templates Controller
 * Manage communication templates for SMS, Email, and WhatsApp
 */

class MessageTemplatesController
{
    /**
     * List all templates
     */
    public function index()
    {
        try {
            $pdo = Database::getTenantConnection();

            // Get all templates with stats
            $stmt = $pdo->query("
                SELECT
                    ct.*,
                    (SELECT COUNT(*) FROM message_queue mq
                     WHERE mq.message_type LIKE CONCAT('%', ct.template_code, '%')) as usage_count
                FROM communication_templates ct
                ORDER BY ct.category, ct.template_name
            ");
            $templates = $stmt->fetchAll();

            // Group by category
            $grouped = [];
            foreach ($templates as $template) {
                $category = $template['category'] ?? 'general';
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $template;
            }

            // Get categories for filter
            $categories = array_keys($grouped);

            Response::view('communication.templates', [
                'pageTitle' => 'Message Templates',
                'templates' => $templates,
                'groupedTemplates' => $grouped,
                'categories' => $categories
            ]);

        } catch (Exception $e) {
            logMessage("Error loading templates: " . $e->getMessage(), 'error');
            setFlash('error', 'Failed to load templates');
            Response::redirect('/dashboard');
        }
    }

    /**
     * Show create template form
     */
    public function create()
    {
        Response::view('communication.template-form', [
            'pageTitle' => 'Create Template',
            'template' => null,
            'categories' => $this->getCategories(),
            'variablePresets' => $this->getVariablePresets()
        ]);
    }

    /**
     * Store new template
     */
    public function store()
    {
        try {
            $pdo = Database::getTenantConnection();

            // Validate required fields
            $category = trim(Request::get('category', ''));
            $templateCode = trim(Request::get('template_code', ''));
            $templateName = trim(Request::get('template_name', ''));
            $description = trim(Request::get('description', ''));

            if (empty($templateCode) || empty($templateName)) {
                setFlash('error', 'Template code and name are required');
                Response::redirect('/communication/templates/create');
                return;
            }

            // Check for duplicate code
            $stmt = $pdo->prepare("SELECT id FROM communication_templates WHERE template_code = ?");
            $stmt->execute([$templateCode]);
            if ($stmt->fetch()) {
                setFlash('error', 'Template code already exists');
                Response::redirect('/communication/templates/create');
                return;
            }

            // Get channels
            $channels = Request::get('channels', []);
            if (!is_array($channels)) {
                $channels = [$channels];
            }

            // Get variables
            $variables = Request::get('variables', '');
            $variablesArray = array_filter(array_map('trim', explode(',', $variables)));

            // Insert template
            $stmt = $pdo->prepare("
                INSERT INTO communication_templates (
                    category, template_code, template_name, description,
                    channels, subject, sms_body, email_body, whatsapp_body,
                    variables, requires_authorization, authorization_type,
                    validity_days, is_system, is_active, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $category ?: 'general',
                $templateCode,
                $templateName,
                $description,
                json_encode($channels),
                Request::get('subject', ''),
                Request::get('sms_body', ''),
                Request::get('email_body', ''),
                Request::get('whatsapp_body', ''),
                json_encode($variablesArray),
                Request::get('requires_authorization') ? 1 : 0,
                Request::get('authorization_type', null),
                Request::get('validity_days', 30),
                0, // Not system template
                Request::get('is_active') ? 1 : 0,
                $_SESSION['user_id'] ?? null
            ]);

            setFlash('success', 'Template created successfully');
            Response::redirect('/communication/templates');

        } catch (Exception $e) {
            logMessage("Error creating template: " . $e->getMessage(), 'error');
            setFlash('error', 'Failed to create template');
            Response::redirect('/communication/templates/create');
        }
    }

    /**
     * Show edit template form
     */
    public function edit($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("SELECT * FROM communication_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();

            if (!$template) {
                setFlash('error', 'Template not found');
                Response::redirect('/communication/templates');
                return;
            }

            Response::view('communication.template-form', [
                'pageTitle' => 'Edit Template',
                'template' => $template,
                'categories' => $this->getCategories(),
                'variablePresets' => $this->getVariablePresets()
            ]);

        } catch (Exception $e) {
            logMessage("Error loading template: " . $e->getMessage(), 'error');
            setFlash('error', 'Failed to load template');
            Response::redirect('/communication/templates');
        }
    }

    /**
     * Update template
     */
    public function update($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Check template exists
            $stmt = $pdo->prepare("SELECT * FROM communication_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();

            if (!$template) {
                setFlash('error', 'Template not found');
                Response::redirect('/communication/templates');
                return;
            }

            // Validate required fields
            $templateName = trim(Request::get('template_name', ''));
            if (empty($templateName)) {
                setFlash('error', 'Template name is required');
                Response::redirect("/communication/templates/{$id}/edit");
                return;
            }

            // Get channels
            $channels = Request::get('channels', []);
            if (!is_array($channels)) {
                $channels = [$channels];
            }

            // Get variables
            $variables = Request::get('variables', '');
            $variablesArray = array_filter(array_map('trim', explode(',', $variables)));

            // Update template
            $stmt = $pdo->prepare("
                UPDATE communication_templates SET
                    category = ?,
                    template_name = ?,
                    description = ?,
                    channels = ?,
                    subject = ?,
                    sms_body = ?,
                    email_body = ?,
                    whatsapp_body = ?,
                    variables = ?,
                    requires_authorization = ?,
                    authorization_type = ?,
                    validity_days = ?,
                    is_active = ?,
                    updated_by = ?
                WHERE id = ?
            ");

            $stmt->execute([
                Request::get('category', 'general'),
                $templateName,
                Request::get('description', ''),
                json_encode($channels),
                Request::get('subject', ''),
                Request::get('sms_body', ''),
                Request::get('email_body', ''),
                Request::get('whatsapp_body', ''),
                json_encode($variablesArray),
                Request::get('requires_authorization') ? 1 : 0,
                Request::get('authorization_type', null),
                Request::get('validity_days', 30),
                Request::get('is_active') ? 1 : 0,
                $_SESSION['user_id'] ?? null,
                $id
            ]);

            setFlash('success', 'Template updated successfully');
            Response::redirect('/communication/templates');

        } catch (Exception $e) {
            logMessage("Error updating template: " . $e->getMessage(), 'error');
            setFlash('error', 'Failed to update template');
            Response::redirect("/communication/templates/{$id}/edit");
        }
    }

    /**
     * Delete template
     */
    public function delete($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Check if system template
            $stmt = $pdo->prepare("SELECT is_system FROM communication_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();

            if (!$template) {
                return Response::json(['success' => false, 'message' => 'Template not found']);
            }

            if ($template['is_system']) {
                return Response::json(['success' => false, 'message' => 'Cannot delete system templates']);
            }

            // Delete template
            $stmt = $pdo->prepare("DELETE FROM communication_templates WHERE id = ?");
            $stmt->execute([$id]);

            return Response::json(['success' => true, 'message' => 'Template deleted']);

        } catch (Exception $e) {
            logMessage("Error deleting template: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to delete template']);
        }
    }

    /**
     * Toggle template active status
     */
    public function toggleStatus($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                UPDATE communication_templates
                SET is_active = NOT is_active, updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'] ?? null, $id]);

            return Response::json(['success' => true]);

        } catch (Exception $e) {
            logMessage("Error toggling template status: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to toggle status']);
        }
    }

    /**
     * Preview template with sample data
     */
    public function preview($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("SELECT * FROM communication_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();

            if (!$template) {
                return Response::json(['success' => false, 'message' => 'Template not found']);
            }

            // Generate sample data for preview
            $sampleData = $this->getSampleVariables();

            // Substitute variables
            $smsPreview = $this->substituteVariables($template['sms_body'] ?? '', $sampleData);
            $emailPreview = $this->substituteVariables($template['email_body'] ?? '', $sampleData);
            $whatsappPreview = $this->substituteVariables($template['whatsapp_body'] ?? '', $sampleData);
            $subjectPreview = $this->substituteVariables($template['subject'] ?? '', $sampleData);

            return Response::json([
                'success' => true,
                'preview' => [
                    'subject' => $subjectPreview,
                    'sms' => $smsPreview,
                    'email' => $emailPreview,
                    'whatsapp' => $whatsappPreview,
                    'sms_length' => strlen($smsPreview),
                    'sms_parts' => ceil(strlen($smsPreview) / 160)
                ]
            ]);

        } catch (Exception $e) {
            logMessage("Error previewing template: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to preview template']);
        }
    }

    /**
     * Get available categories
     */
    private function getCategories(): array
    {
        return [
            'authorization' => 'Authorization Requests',
            'parent_portal' => 'Parent Portal',
            'admissions' => 'Admissions',
            'finance' => 'Finance & Fees',
            'attendance' => 'Attendance',
            'academic' => 'Academic',
            'transport' => 'Transport',
            'general' => 'General Communication'
        ];
    }

    /**
     * Get variable presets for quick insertion
     */
    private function getVariablePresets(): array
    {
        return [
            'Common' => [
                '{{school_name}}' => 'School name',
                '{{school_phone}}' => 'School phone number',
                '{{school_email}}' => 'School email'
            ],
            'Guardian/Parent' => [
                '{{guardian_name}}' => 'Guardian full name',
                '{{guardian_phone}}' => 'Guardian phone number',
                '{{guardian_email}}' => 'Guardian email'
            ],
            'Student' => [
                '{{student_name}}' => 'Student full name',
                '{{student_first_name}}' => 'Student first name',
                '{{admission_number}}' => 'Admission number',
                '{{grade_name}}' => 'Grade/Class name',
                '{{stream_name}}' => 'Stream name'
            ],
            'Authorization' => [
                '{{code}}' => 'Verification code',
                '{{link}}' => 'Authorization link',
                '{{validity_days}}' => 'Validity period in days'
            ],
            'Finance' => [
                '{{amount}}' => 'Amount',
                '{{balance}}' => 'Balance due',
                '{{due_date}}' => 'Due date',
                '{{receipt_number}}' => 'Receipt number'
            ],
            'Dates' => [
                '{{date}}' => 'Current date',
                '{{time}}' => 'Current time',
                '{{term}}' => 'Current term',
                '{{year}}' => 'Current year'
            ]
        ];
    }

    /**
     * Get sample data for preview
     */
    private function getSampleVariables(): array
    {
        return [
            'school_name' => $_SESSION['tenant_name'] ?? 'Demo School',
            'school_phone' => '+254 700 123456',
            'school_email' => 'info@school.co.ke',
            'guardian_name' => 'John Doe',
            'guardian_phone' => '+254 722 345678',
            'guardian_email' => 'john.doe@email.com',
            'student_name' => 'Jane Doe',
            'student_first_name' => 'Jane',
            'admission_number' => 'ADM2024001',
            'grade_name' => 'Grade 8',
            'stream_name' => 'East',
            'code' => '123456',
            'link' => 'https://school.example.com/authorize/sample-token',
            'validity_days' => '30',
            'amount' => 'KES 25,000',
            'balance' => 'KES 15,000',
            'due_date' => date('d M Y', strtotime('+7 days')),
            'receipt_number' => 'RCP2024001',
            'date' => date('d M Y'),
            'time' => date('h:i A'),
            'term' => 'Term 1',
            'year' => date('Y')
        ];
    }

    /**
     * Substitute variables in template
     */
    private function substituteVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }
        return $template;
    }
}

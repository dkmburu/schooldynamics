<?php
/**
 * Authorization Controller
 * Handles public-facing authorization approval
 */

class AuthorizationController
{
    /**
     * Show authorization approval page (public, no login required)
     */
    public function showApprovalPage($token)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Find authorization request by token
            $stmt = $pdo->prepare("
                SELECT ar.*, ct.template_name, ct.authorization_type, c.campus_name, sp.school_name
                FROM authorization_requests ar
                LEFT JOIN communication_templates ct ON ar.message_template = ct.template_code
                LEFT JOIN campuses c ON ar.campus_id = c.id
                LEFT JOIN school_profile sp ON sp.id = 1
                WHERE ar.token = ?
            ");
            $stmt->execute([$token]);
            $request = $stmt->fetch();

            if (!$request) {
                Response::view('authorization.approve', [
                    'error' => 'Invalid authorization link. Please check the link or contact the school.'
                ]);
                return;
            }

            // Check if already approved
            if ($request['status'] === 'approved') {
                Response::view('authorization.approve', [
                    'already_approved' => true,
                    'request' => $request
                ]);
                return;
            }

            // Check if expired
            if (strtotime($request['expires_at']) < time()) {
                Response::view('authorization.approve', [
                    'expired' => true,
                    'request' => $request
                ]);
                return;
            }

            // Check if rejected
            if ($request['status'] === 'rejected') {
                Response::view('authorization.approve', [
                    'rejected' => true,
                    'request' => $request
                ]);
                return;
            }

            // Show approval form
            Response::view('authorization.approve', [
                'request' => $request
            ]);

        } catch (Exception $e) {
            logMessage("Show approval page error: " . $e->getMessage(), 'error');
            Response::view('authorization.approve', [
                'error' => 'An error occurred. Please try again or contact the school.'
            ]);
        }
    }

    /**
     * Process authorization approval (public, no login required)
     */
    public function processApproval($token)
    {
        try {
            $action = $_POST['action'] ?? null;

            if ($action === 'approve') {
                // Get IP and user agent for audit
                $metadata = [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ];

                $result = AuthHelper::approveByLink($token, $metadata);

                if ($result['success']) {
                    Response::view('authorization.approve', [
                        'success' => true,
                        'request' => $result['request']
                    ]);
                } else {
                    Response::view('authorization.approve', [
                        'error' => $result['message']
                    ]);
                }
            } elseif ($action === 'reject') {
                // TODO: Implement rejection
                Response::view('authorization.approve', [
                    'error' => 'Rejection feature coming soon. Please contact the school to decline.'
                ]);
            } else {
                Response::view('authorization.approve', [
                    'error' => 'Invalid action'
                ]);
            }

        } catch (Exception $e) {
            logMessage("Process approval error: " . $e->getMessage(), 'error');
            Response::view('authorization.approve', [
                'error' => 'An error occurred. Please try again or contact the school.'
            ]);
        }
    }
}

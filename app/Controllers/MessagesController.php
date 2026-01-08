<?php
/**
 * Messages Controller
 * View and manage message queue
 */

class MessagesController
{
    /**
     * List messages in queue
     */
    public function index()
    {
        try {
            $pdo = Database::getTenantConnection();

            // Get filters
            $status = Request::get('status', '');
            $channel = Request::get('channel', '');
            $search = Request::get('search', '');
            $dateFrom = Request::get('date_from', '');
            $dateTo = Request::get('date_to', '');
            $page = max(1, (int)Request::get('page', 1));
            $perPage = 50;

            // Build query
            $where = [];
            $params = [];

            if ($status) {
                $where[] = "mq.status = ?";
                $params[] = $status;
            }

            if ($channel) {
                $where[] = "mq.channel = ?";
                $params[] = $channel;
            }

            if ($search) {
                $where[] = "(mq.recipient_name LIKE ? OR mq.recipient_phone LIKE ? OR mq.recipient_email LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            if ($dateFrom) {
                $where[] = "DATE(mq.created_at) >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo) {
                $where[] = "DATE(mq.created_at) <= ?";
                $params[] = $dateTo;
            }

            $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

            // Get total count
            $countSql = "SELECT COUNT(*) FROM message_queue mq {$whereClause}";
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Get messages with pagination
            $offset = ($page - 1) * $perPage;
            $sql = "
                SELECT
                    mq.*,
                    u.full_name as created_by_name
                FROM message_queue mq
                LEFT JOIN users u ON mq.created_by = u.id
                {$whereClause}
                ORDER BY mq.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll();

            // Get stats
            $stats = $this->getMessageStats($pdo);

            Response::view('communication.messages', [
                'pageTitle' => 'Message Queue',
                'messages' => $messages,
                'stats' => $stats,
                'filters' => [
                    'status' => $status,
                    'channel' => $channel,
                    'search' => $search,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ],
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'perPage' => $perPage,
                    'totalPages' => ceil($total / $perPage)
                ]
            ]);

        } catch (Exception $e) {
            logMessage("Error loading messages: " . $e->getMessage(), 'error');
            setFlash('error', 'Failed to load messages');
            Response::redirect('/dashboard');
        }
    }

    /**
     * View single message details
     */
    public function show($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT mq.*, u.full_name as created_by_name
                FROM message_queue mq
                LEFT JOIN users u ON mq.created_by = u.id
                WHERE mq.id = ?
            ");
            $stmt->execute([$id]);
            $message = $stmt->fetch();

            if (!$message) {
                return Response::json(['success' => false, 'message' => 'Message not found']);
            }

            return Response::json(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            logMessage("Error loading message: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to load message']);
        }
    }

    /**
     * Retry failed message
     */
    public function retry($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Check message exists and is failed
            $stmt = $pdo->prepare("SELECT * FROM message_queue WHERE id = ? AND status = 'failed'");
            $stmt->execute([$id]);
            $message = $stmt->fetch();

            if (!$message) {
                return Response::json(['success' => false, 'message' => 'Message not found or not in failed status']);
            }

            // Reset to queued
            $stmt = $pdo->prepare("
                UPDATE message_queue
                SET status = 'queued',
                    attempts = 0,
                    error_message = NULL,
                    error_code = NULL
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            return Response::json(['success' => true, 'message' => 'Message queued for retry']);

        } catch (Exception $e) {
            logMessage("Error retrying message: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to retry message']);
        }
    }

    /**
     * Cancel queued message
     */
    public function cancel($id)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Check message exists and is queued
            $stmt = $pdo->prepare("SELECT * FROM message_queue WHERE id = ? AND status = 'queued'");
            $stmt->execute([$id]);
            $message = $stmt->fetch();

            if (!$message) {
                return Response::json(['success' => false, 'message' => 'Message not found or already processed']);
            }

            // Cancel message
            $stmt = $pdo->prepare("UPDATE message_queue SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);

            return Response::json(['success' => true, 'message' => 'Message cancelled']);

        } catch (Exception $e) {
            logMessage("Error cancelling message: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to cancel message']);
        }
    }

    /**
     * Bulk retry failed messages
     */
    public function bulkRetry()
    {
        try {
            $pdo = Database::getTenantConnection();

            $ids = Request::get('ids', []);
            if (empty($ids)) {
                return Response::json(['success' => false, 'message' => 'No messages selected']);
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("
                UPDATE message_queue
                SET status = 'queued',
                    attempts = 0,
                    error_message = NULL,
                    error_code = NULL
                WHERE id IN ({$placeholders}) AND status = 'failed'
            ");
            $stmt->execute($ids);

            $count = $stmt->rowCount();
            return Response::json(['success' => true, 'message' => "{$count} message(s) queued for retry"]);

        } catch (Exception $e) {
            logMessage("Error bulk retrying: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to retry messages']);
        }
    }

    /**
     * Get message statistics
     */
    private function getMessageStats(PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as queued,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN channel = 'sms' THEN 1 ELSE 0 END) as sms_count,
                SUM(CASE WHEN channel = 'email' THEN 1 ELSE 0 END) as email_count,
                SUM(CASE WHEN channel = 'whatsapp' THEN 1 ELSE 0 END) as whatsapp_count
            FROM message_queue
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        return $stmt->fetch();
    }

    /**
     * Export messages to CSV
     */
    public function export()
    {
        try {
            $pdo = Database::getTenantConnection();

            $status = Request::get('status', '');
            $channel = Request::get('channel', '');
            $dateFrom = Request::get('date_from', date('Y-m-d', strtotime('-30 days')));
            $dateTo = Request::get('date_to', date('Y-m-d'));

            $where = ["DATE(created_at) BETWEEN ? AND ?"];
            $params = [$dateFrom, $dateTo];

            if ($status) {
                $where[] = "status = ?";
                $params[] = $status;
            }

            if ($channel) {
                $where[] = "channel = ?";
                $params[] = $channel;
            }

            $stmt = $pdo->prepare("
                SELECT
                    id, channel, message_type, recipient_name, recipient_phone, recipient_email,
                    subject, status, sent_at, failed_at, error_message, created_at
                FROM message_queue
                WHERE " . implode(' AND ', $where) . "
                ORDER BY created_at DESC
            ");
            $stmt->execute($params);
            $messages = $stmt->fetchAll();

            // Generate CSV
            $filename = "messages_export_" . date('Y-m-d_His') . ".csv";

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Channel', 'Type', 'Recipient', 'Phone', 'Email', 'Subject', 'Status', 'Sent At', 'Failed At', 'Error', 'Created At']);

            foreach ($messages as $msg) {
                fputcsv($output, [
                    $msg['id'],
                    $msg['channel'],
                    $msg['message_type'],
                    $msg['recipient_name'],
                    $msg['recipient_phone'],
                    $msg['recipient_email'],
                    $msg['subject'],
                    $msg['status'],
                    $msg['sent_at'],
                    $msg['failed_at'],
                    $msg['error_message'],
                    $msg['created_at']
                ]);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            logMessage("Error exporting messages: " . $e->getMessage(), 'error');
            setFlash('error', 'Failed to export messages');
            Response::redirect('/communication/messages');
        }
    }
}

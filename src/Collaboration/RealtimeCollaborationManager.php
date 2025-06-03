<?php

declare(strict_types=1);

namespace Rector\ThinkPHP\Collaboration;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * Real-time collaboration manager for multi-user upgrade sessions
 */
final class RealtimeCollaborationManager implements MessageComponentInterface
{
    /**
     * @var \SplObjectStorage<ConnectionInterface, array<string, mixed>>
     */
    private \SplObjectStorage $clients;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $sessions = [];

    /**
     * @var array<string, array<ConnectionInterface>>
     */
    private array $sessionClients = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $userProfiles = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn, [
            'id' => uniqid(),
            'connected_at' => time(),
            'session_id' => null,
            'user_id' => null,
            'permissions' => [],
        ]);

        $this->sendMessage($conn, [
            'type' => 'connection_established',
            'client_id' => $this->clients[$conn]['id'],
            'timestamp' => time(),
        ]);

        echo "New connection: {$this->clients[$conn]['id']}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $data = json_decode($msg, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->sendError($from, 'Invalid JSON message');
                return;
            }

            $this->handleMessage($from, $data);

        } catch (\Exception $e) {
            $this->sendError($from, 'Message processing error: ' . $e->getMessage());
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $clientData = $this->clients[$conn];
        
        // Remove from session if connected
        if ($clientData['session_id']) {
            $this->leaveSession($conn, $clientData['session_id']);
        }

        $this->clients->detach($conn);
        
        echo "Connection closed: {$clientData['id']}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleMessage(ConnectionInterface $from, array $data): void
    {
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'authenticate':
                $this->handleAuthentication($from, $data);
                break;

            case 'join_session':
                $this->handleJoinSession($from, $data);
                break;

            case 'leave_session':
                $this->handleLeaveSession($from, $data);
                break;

            case 'create_session':
                $this->handleCreateSession($from, $data);
                break;

            case 'upgrade_action':
                $this->handleUpgradeAction($from, $data);
                break;

            case 'chat_message':
                $this->handleChatMessage($from, $data);
                break;

            case 'cursor_position':
                $this->handleCursorPosition($from, $data);
                break;

            case 'file_lock':
                $this->handleFileLock($from, $data);
                break;

            case 'file_unlock':
                $this->handleFileUnlock($from, $data);
                break;

            case 'status_update':
                $this->handleStatusUpdate($from, $data);
                break;

            default:
                $this->sendError($from, "Unknown message type: {$type}");
        }
    }

    private function handleAuthentication(ConnectionInterface $from, array $data): void
    {
        $token = $data['token'] ?? '';
        $userId = $this->validateAuthToken($token);

        if (!$userId) {
            $this->sendError($from, 'Invalid authentication token');
            return;
        }

        $clientData = $this->clients[$from];
        $clientData['user_id'] = $userId;
        $clientData['permissions'] = $this->getUserPermissions($userId);
        $this->clients[$from] = $clientData;

        $this->sendMessage($from, [
            'type' => 'authenticated',
            'user_id' => $userId,
            'permissions' => $clientData['permissions'],
            'timestamp' => time(),
        ]);
    }

    private function handleCreateSession(ConnectionInterface $from, array $data): void
    {
        $clientData = $this->clients[$from];
        
        if (!$clientData['user_id']) {
            $this->sendError($from, 'Authentication required');
            return;
        }

        $sessionId = uniqid('session_');
        $projectPath = $data['project_path'] ?? '';
        $sessionName = $data['session_name'] ?? 'Upgrade Session';

        $this->sessions[$sessionId] = [
            'id' => $sessionId,
            'name' => $sessionName,
            'project_path' => $projectPath,
            'owner_id' => $clientData['user_id'],
            'created_at' => time(),
            'status' => 'active',
            'participants' => [],
            'file_locks' => [],
            'chat_history' => [],
            'upgrade_progress' => [
                'status' => 'pending',
                'current_step' => null,
                'progress' => 0,
                'files_processed' => 0,
                'total_files' => 0,
            ],
        ];

        $this->sessionClients[$sessionId] = [];

        // Auto-join creator to session
        $this->joinSession($from, $sessionId);

        $this->sendMessage($from, [
            'type' => 'session_created',
            'session_id' => $sessionId,
            'session' => $this->sessions[$sessionId],
            'timestamp' => time(),
        ]);
    }

    private function handleJoinSession(ConnectionInterface $from, array $data): void
    {
        $sessionId = $data['session_id'] ?? '';
        
        if (!isset($this->sessions[$sessionId])) {
            $this->sendError($from, 'Session not found');
            return;
        }

        $this->joinSession($from, $sessionId);
    }

    private function handleLeaveSession(ConnectionInterface $from, array $data): void
    {
        $sessionId = $data['session_id'] ?? '';
        $this->leaveSession($from, $sessionId);
    }

    private function handleUpgradeAction(ConnectionInterface $from, array $data): void
    {
        $sessionId = $this->clients[$from]['session_id'] ?? '';
        
        if (!$sessionId || !isset($this->sessions[$sessionId])) {
            $this->sendError($from, 'Not in a valid session');
            return;
        }

        $action = $data['action'] ?? '';
        $userId = $this->clients[$from]['user_id'];

        // Check permissions
        if (!$this->hasPermission($from, 'upgrade_control')) {
            $this->sendError($from, 'Insufficient permissions');
            return;
        }

        switch ($action) {
            case 'start_upgrade':
                $this->startCollaborativeUpgrade($sessionId, $data, $userId);
                break;

            case 'pause_upgrade':
                $this->pauseCollaborativeUpgrade($sessionId, $userId);
                break;

            case 'resume_upgrade':
                $this->resumeCollaborativeUpgrade($sessionId, $userId);
                break;

            case 'cancel_upgrade':
                $this->cancelCollaborativeUpgrade($sessionId, $userId);
                break;

            default:
                $this->sendError($from, "Unknown upgrade action: {$action}");
        }
    }

    private function handleChatMessage(ConnectionInterface $from, array $data): void
    {
        $sessionId = $this->clients[$from]['session_id'] ?? '';
        
        if (!$sessionId) {
            $this->sendError($from, 'Not in a session');
            return;
        }

        $message = $data['message'] ?? '';
        $userId = $this->clients[$from]['user_id'];

        $chatMessage = [
            'id' => uniqid(),
            'user_id' => $userId,
            'message' => $message,
            'timestamp' => time(),
            'type' => 'chat',
        ];

        $this->sessions[$sessionId]['chat_history'][] = $chatMessage;

        // Broadcast to all session participants
        $this->broadcastToSession($sessionId, [
            'type' => 'chat_message',
            'message' => $chatMessage,
        ], $from);
    }

    private function handleCursorPosition(ConnectionInterface $from, array $data): void
    {
        $sessionId = $this->clients[$from]['session_id'] ?? '';
        
        if (!$sessionId) {
            return;
        }

        $userId = $this->clients[$from]['user_id'];
        $file = $data['file'] ?? '';
        $line = $data['line'] ?? 0;
        $column = $data['column'] ?? 0;

        // Broadcast cursor position to other participants
        $this->broadcastToSession($sessionId, [
            'type' => 'cursor_position',
            'user_id' => $userId,
            'file' => $file,
            'line' => $line,
            'column' => $column,
            'timestamp' => time(),
        ], $from);
    }

    private function handleFileLock(ConnectionInterface $from, array $data): void
    {
        $sessionId = $this->clients[$from]['session_id'] ?? '';
        $file = $data['file'] ?? '';
        
        if (!$sessionId || !$file) {
            return;
        }

        $userId = $this->clients[$from]['user_id'];

        // Check if file is already locked
        if (isset($this->sessions[$sessionId]['file_locks'][$file])) {
            $this->sendError($from, "File is already locked by another user");
            return;
        }

        $this->sessions[$sessionId]['file_locks'][$file] = [
            'user_id' => $userId,
            'locked_at' => time(),
        ];

        // Broadcast file lock to session
        $this->broadcastToSession($sessionId, [
            'type' => 'file_locked',
            'file' => $file,
            'user_id' => $userId,
            'timestamp' => time(),
        ]);
    }

    private function handleFileUnlock(ConnectionInterface $from, array $data): void
    {
        $sessionId = $this->clients[$from]['session_id'] ?? '';
        $file = $data['file'] ?? '';
        
        if (!$sessionId || !$file) {
            return;
        }

        $userId = $this->clients[$from]['user_id'];

        // Check if user owns the lock
        if (!isset($this->sessions[$sessionId]['file_locks'][$file]) ||
            $this->sessions[$sessionId]['file_locks'][$file]['user_id'] !== $userId) {
            $this->sendError($from, "You don't own the lock for this file");
            return;
        }

        unset($this->sessions[$sessionId]['file_locks'][$file]);

        // Broadcast file unlock to session
        $this->broadcastToSession($sessionId, [
            'type' => 'file_unlocked',
            'file' => $file,
            'user_id' => $userId,
            'timestamp' => time(),
        ]);
    }

    private function handleStatusUpdate(ConnectionInterface $from, array $data): void
    {
        $sessionId = $this->clients[$from]['session_id'] ?? '';
        
        if (!$sessionId) {
            return;
        }

        $userId = $this->clients[$from]['user_id'];
        $status = $data['status'] ?? 'online';

        // Broadcast status update to session
        $this->broadcastToSession($sessionId, [
            'type' => 'user_status_update',
            'user_id' => $userId,
            'status' => $status,
            'timestamp' => time(),
        ], $from);
    }

    private function joinSession(ConnectionInterface $conn, string $sessionId): void
    {
        $clientData = $this->clients[$conn];
        $userId = $clientData['user_id'];

        // Leave current session if any
        if ($clientData['session_id']) {
            $this->leaveSession($conn, $clientData['session_id']);
        }

        // Join new session
        $clientData['session_id'] = $sessionId;
        $this->clients[$conn] = $clientData;

        if (!isset($this->sessionClients[$sessionId])) {
            $this->sessionClients[$sessionId] = [];
        }

        $this->sessionClients[$sessionId][] = $conn;

        // Add to session participants
        $this->sessions[$sessionId]['participants'][$userId] = [
            'user_id' => $userId,
            'joined_at' => time(),
            'status' => 'online',
        ];

        // Send session info to client
        $this->sendMessage($conn, [
            'type' => 'session_joined',
            'session_id' => $sessionId,
            'session' => $this->sessions[$sessionId],
            'timestamp' => time(),
        ]);

        // Notify other participants
        $this->broadcastToSession($sessionId, [
            'type' => 'user_joined',
            'user_id' => $userId,
            'timestamp' => time(),
        ], $conn);
    }

    private function leaveSession(ConnectionInterface $conn, string $sessionId): void
    {
        $clientData = $this->clients[$conn];
        $userId = $clientData['user_id'];

        // Remove from session clients
        if (isset($this->sessionClients[$sessionId])) {
            $this->sessionClients[$sessionId] = array_filter(
                $this->sessionClients[$sessionId],
                fn($client) => $client !== $conn
            );
        }

        // Remove from session participants
        if (isset($this->sessions[$sessionId]['participants'][$userId])) {
            unset($this->sessions[$sessionId]['participants'][$userId]);
        }

        // Release any file locks
        foreach ($this->sessions[$sessionId]['file_locks'] as $file => $lock) {
            if ($lock['user_id'] === $userId) {
                unset($this->sessions[$sessionId]['file_locks'][$file]);
                
                $this->broadcastToSession($sessionId, [
                    'type' => 'file_unlocked',
                    'file' => $file,
                    'user_id' => $userId,
                    'reason' => 'user_left',
                    'timestamp' => time(),
                ]);
            }
        }

        // Update client data
        $clientData['session_id'] = null;
        $this->clients[$conn] = $clientData;

        // Notify other participants
        $this->broadcastToSession($sessionId, [
            'type' => 'user_left',
            'user_id' => $userId,
            'timestamp' => time(),
        ]);

        // Clean up empty session
        if (empty($this->sessions[$sessionId]['participants'])) {
            unset($this->sessions[$sessionId]);
            unset($this->sessionClients[$sessionId]);
        }
    }

    private function broadcastToSession(string $sessionId, array $message, ConnectionInterface $exclude = null): void
    {
        if (!isset($this->sessionClients[$sessionId])) {
            return;
        }

        foreach ($this->sessionClients[$sessionId] as $client) {
            if ($client !== $exclude) {
                $this->sendMessage($client, $message);
            }
        }
    }

    private function sendMessage(ConnectionInterface $conn, array $message): void
    {
        $conn->send(json_encode($message));
    }

    private function sendError(ConnectionInterface $conn, string $error): void
    {
        $this->sendMessage($conn, [
            'type' => 'error',
            'message' => $error,
            'timestamp' => time(),
        ]);
    }

    private function startCollaborativeUpgrade(string $sessionId, array $data, string $userId): void
    {
        $this->sessions[$sessionId]['upgrade_progress'] = [
            'status' => 'running',
            'started_by' => $userId,
            'started_at' => time(),
            'current_step' => 'Initializing upgrade...',
            'progress' => 0,
            'files_processed' => 0,
            'total_files' => $data['total_files'] ?? 0,
        ];

        $this->broadcastToSession($sessionId, [
            'type' => 'upgrade_started',
            'started_by' => $userId,
            'progress' => $this->sessions[$sessionId]['upgrade_progress'],
            'timestamp' => time(),
        ]);
    }

    private function pauseCollaborativeUpgrade(string $sessionId, string $userId): void
    {
        $this->sessions[$sessionId]['upgrade_progress']['status'] = 'paused';
        
        $this->broadcastToSession($sessionId, [
            'type' => 'upgrade_paused',
            'paused_by' => $userId,
            'timestamp' => time(),
        ]);
    }

    private function resumeCollaborativeUpgrade(string $sessionId, string $userId): void
    {
        $this->sessions[$sessionId]['upgrade_progress']['status'] = 'running';
        
        $this->broadcastToSession($sessionId, [
            'type' => 'upgrade_resumed',
            'resumed_by' => $userId,
            'timestamp' => time(),
        ]);
    }

    private function cancelCollaborativeUpgrade(string $sessionId, string $userId): void
    {
        $this->sessions[$sessionId]['upgrade_progress']['status'] = 'cancelled';
        
        $this->broadcastToSession($sessionId, [
            'type' => 'upgrade_cancelled',
            'cancelled_by' => $userId,
            'timestamp' => time(),
        ]);
    }

    private function validateAuthToken(string $token): ?string
    {
        // Simplified token validation - in real implementation, verify JWT or session token
        if (strlen($token) > 10) {
            return 'user_' . substr($token, 0, 8);
        }
        
        return null;
    }

    private function getUserPermissions(string $userId): array
    {
        // Simplified permissions - in real implementation, load from database
        return [
            'upgrade_control',
            'file_edit',
            'chat',
            'view_session',
        ];
    }

    private function hasPermission(ConnectionInterface $conn, string $permission): bool
    {
        $clientData = $this->clients[$conn];
        return in_array($permission, $clientData['permissions'] ?? [], true);
    }

    /**
     * Update upgrade progress for a session
     */
    public function updateUpgradeProgress(string $sessionId, array $progress): void
    {
        if (!isset($this->sessions[$sessionId])) {
            return;
        }

        $this->sessions[$sessionId]['upgrade_progress'] = array_merge(
            $this->sessions[$sessionId]['upgrade_progress'],
            $progress
        );

        $this->broadcastToSession($sessionId, [
            'type' => 'upgrade_progress_update',
            'progress' => $this->sessions[$sessionId]['upgrade_progress'],
            'timestamp' => time(),
        ]);
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions(): array
    {
        return array_values($this->sessions);
    }

    /**
     * Get session by ID
     */
    public function getSession(string $sessionId): ?array
    {
        return $this->sessions[$sessionId] ?? null;
    }
}

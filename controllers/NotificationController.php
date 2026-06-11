<?php
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db   = getDB();
$user = currentUser();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'read':
        $id = (int)($_GET['id'] ?? 0);
        $notif = $db->query("SELECT * FROM notifications WHERE id = $id AND user_id = {$user['id']}")->fetch_assoc();
        $db->query("UPDATE notifications SET is_read = 1 WHERE id = $id AND user_id = {$user['id']}");
        redirect(BASE_URL . '/views/public/notifications.php');
        break;
    case 'read_all':
        $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = {$user['id']}");
        redirect(BASE_URL . '/views/public/notifications.php');
        break;
    case 'poll':
        header('Content-Type: application/json');
        $last_id = (int)($_GET['last_id'] ?? 0);
        $unread = $db->query("SELECT COUNT(*) as c FROM notifications WHERE user_id = {$user['id']} AND is_read = 0")->fetch_assoc()['c'] ?? 0;
        
        $new_notifs = [];
        $res = $db->query("SELECT * FROM notifications WHERE user_id = {$user['id']} AND id > $last_id ORDER BY id DESC");
        while ($n = $res->fetch_assoc()) {
            $n['time_ago'] = timeAgo($n['created_at']);
            $n['title'] = sanitize($n['title']);
            $n['message'] = sanitize($n['message']);
            $new_notifs[] = $n;
        }
        
        echo json_encode([
            'success' => true,
            'unread' => (int)$unread,
            'new_notifications' => $new_notifs
        ]);
        exit;
    default:
        redirect(BASE_URL . '/views/public/notifications.php');
}

<?php
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db     = getDB();
$user   = currentUser();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'read':
        $id    = (int)($_GET['id'] ?? 0);
        $stmt  = $db->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user['id']]);
        $upd   = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $upd->execute([$id, $user['id']]);
        redirect(BASE_URL . '/views/public/notifications.php');
        break;
    case 'read_all':
        $upd = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        $upd->execute([$user['id']]);
        redirect(BASE_URL . '/views/public/notifications.php');
        break;
    case 'poll':
        header('Content-Type: application/json');
        $last_id = (int)($_GET['last_id'] ?? 0);

        $su = $db->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $su->execute([$user['id']]);
        $unread = (int)($su->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $new_notifs = [];
        $sn = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND id > ? ORDER BY id DESC");
        $sn->execute([$user['id'], $last_id]);
        while ($n = $sn->fetch(PDO::FETCH_ASSOC)) {
            $n['time_ago'] = timeAgo($n['created_at']);
            $n['title']    = sanitize($n['title']);
            $n['message']  = sanitize($n['message']);
            $new_notifs[]  = $n;
        }

        echo json_encode([
            'success'          => true,
            'unread'           => $unread,
            'new_notifications'=> $new_notifs
        ]);
        exit;
    default:
        redirect(BASE_URL . '/views/public/notifications.php');
}

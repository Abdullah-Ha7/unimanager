<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$user = current_user();
if (!$user || $user['role_id'] != 2) {
    header("Location: ?page=login");
    exit;
}

$review_id = $_GET['id'] ?? null;
if (!$review_id) {
    header("Location: ?page=home");
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.id, r.event_id, e.organizer_id
    FROM reviews r
    JOIN events e ON r.event_id = e.id
    WHERE r.id = ?
");
$stmt->execute([$review_id]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$review || $review['organizer_id'] != $user['id']) {
    header("Location: ?page=home");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply = trim($_POST['reply'] ?? '');
    if ($reply) {
        $stmt = $pdo->prepare("UPDATE reviews SET reply = ?, replied_at = NOW() WHERE id = ?");
        $stmt->execute([$reply, $review_id]);
    }
    header("Location: ?page=event_detail&id=" . $review['event_id']);
    exit;
}
?>

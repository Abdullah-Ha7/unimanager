<?php
// Pre-processing for event_detail page: fetch event, handle booking & organizer reply BEFORE any output.
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$user = current_user();
$event_id = intval($_GET['id'] ?? 0);
if ($event_id <= 0) {
    $_SESSION['error_message'] = lang('no_event_selected');
    header('Location: ?page=events');
    exit;
}

$stmt = $pdo->prepare("SELECT e.*, u.name AS organizer_name,(SELECT COUNT(id) FROM bookings WHERE event_id = e.id) AS booked_count FROM events e JOIN users u ON e.organizer_id = u.id WHERE e.id = ? LIMIT 1");
$stmt->execute([$event_id]);
$event = $stmt->fetch();
if (!$event) {
    $_SESSION['error_message'] = lang('event_not_found');
    header('Location: ?page=events');
    exit;
}

// Approval/status check (support both field names)
$approval = $event['approval_status'] ?? ($event['status'] ?? 'pending');
if (!in_array($approval, ['approved','published'])) {
    $_SESSION['error_message'] = lang('event_not_found');
    header('Location: ?page=events');
    exit;
}

$user_id    = $user['id'] ?? null;
$user_role  = $user['role_id'] ?? null;
$is_organizer = $user_role == 2 && $user_id == $event['organizer_id'];
$is_student   = $user_role == 3;
$is_finished  = (new DateTime($event['end_at'] ?? $event['date'] ?? 'now')) < new DateTime();

$is_booked = false;
$has_reviewed = false;
if ($user_id) {
    $stmtBook = $pdo->prepare('SELECT id FROM bookings WHERE event_id = ? AND user_id = ? LIMIT 1');
    $stmtBook->execute([$event_id, $user_id]);
    $is_booked = (bool)$stmtBook->fetch();

    $stmtRev = $pdo->prepare('SELECT id FROM reviews WHERE event_id = ? AND user_id = ? LIMIT 1');
    $stmtRev->execute([$event_id, $user_id]);
    $has_reviewed = (bool)$stmtRev->fetch();
}

// Booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_event']) && $is_student) {
    if (!in_array($approval, ['approved','published'])) {
        $_SESSION['error_message'] = lang('booking_fail');
        header('Location: ?page=events');
        exit;
    }
    if (!$is_booked) {
        $capacity = $event['capacity'] ?? $event['seats'] ?? null; // support legacy field names
        if ($capacity !== null && $capacity !== '' && $event['booked_count'] >= $capacity) {
            $_SESSION['error_message'] = lang('event_is_full');
        } else {
            $stmtIns = $pdo->prepare('INSERT INTO bookings (event_id, user_id) VALUES (?, ?)');
            if ($stmtIns->execute([$event_id, $user_id])) {
                $_SESSION['success_message'] = lang('booking_success');
            } else {
                $_SESSION['error_message'] = lang('booking_fail');
            }
        }
    } else {
        $_SESSION['error_message'] = lang('already_booked');
    }
    header('Location: ?page=event_detail&id=' . $event_id);
    exit;
}

// Organizer reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply']) && $is_organizer) {
    $target_review_id = intval($_POST['review_id'] ?? 0);
    $reply_text = trim($_POST['reply_text'] ?? '');
    if ($target_review_id > 0 && $reply_text !== '') {
        $stmtCheck = $pdo->prepare("SELECT e.organizer_id, EXISTS(SELECT 1 FROM review_replies rr WHERE rr.review_id = r.id) AS has_reply FROM reviews r JOIN events e ON r.event_id = e.id WHERE r.id = ? AND r.event_id = ?");
        $stmtCheck->execute([$target_review_id, $event_id]);
        $target_review = $stmtCheck->fetch();
        if ($target_review && $target_review['organizer_id'] == $user_id && !$target_review['has_reply']) {
            $stmtReply = $pdo->prepare('INSERT INTO review_replies (review_id, organizer_id, reply_text, created_at) VALUES (?, ?, ?, NOW())');
            if ($stmtReply->execute([$target_review_id, $user_id, $reply_text])) {
                $_SESSION['success_message'] = lang('reply_submitted_success');
            } else {
                $_SESSION['error_message'] = lang('reply_submission_failed');
            }
        } else {
            $_SESSION['error_message'] = lang('access_denied_or_already_replied');
        }
    } else {
        $_SESSION['error_message'] = lang('reply_cannot_be_empty');
    }
    header('Location: ?page=event_detail&id=' . $event_id);
    exit;
}

// Review stats
$stmtAvg = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(id) AS total_reviews FROM reviews WHERE event_id = ?');
$stmtAvg->execute([$event_id]);
$review_stats = $stmtAvg->fetch();
$avg_rating    = round($review_stats['avg_rating'] ?? 0, 1);
$total_reviews = $review_stats['total_reviews'] ?? 0;

// Reviews + replies
$stmtReviews = $pdo->prepare("SELECT r.*, u.name AS reviewer_name, u.role_id AS reviewer_role,(SELECT rr.reply_text FROM review_replies rr WHERE rr.review_id = r.id ORDER BY rr.created_at DESC LIMIT 1) AS reply_text,(SELECT rr.created_at FROM review_replies rr WHERE rr.review_id = r.id ORDER BY rr.created_at DESC LIMIT 1) AS reply_at FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.event_id = ? ORDER BY r.created_at DESC");
$stmtReviews->execute([$event_id]);
$reviews = $stmtReviews->fetchAll();
?>
<?php
// Pre-processing for reviews page: validate access, fetch event, handle submission.
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$user = current_user();
$error = '';
$event = null;

// Only students can review
if (!$user || ($user['role_id'] ?? null) != 3) {
    $_SESSION['error_message'] = lang('access_denied');
    header('Location: ?page=login');
    exit;
}

$event_id = intval($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
if ($event_id <= 0) {
    $_SESSION['error_message'] = lang('no_event_selected');
    header('Location: ?page=events');
    exit;
}

// Fetch event
$stmt = $pdo->prepare('SELECT e.* FROM events e WHERE e.id = ? LIMIT 1');
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['error_message'] = lang('event_not_found');
    header('Location: ?page=events');
    exit;
}

// Check approval/published status (support both field names)
$approval = $event['approval_status'] ?? ($event['status'] ?? 'pending');
if (!in_array($approval, ['approved','published'])) {
    $_SESSION['error_message'] = lang('event_not_found');
    header('Location: ?page=events');
    exit;
}

// Ensure user booked this event
$stmtBook = $pdo->prepare('SELECT id FROM bookings WHERE event_id = ? AND user_id = ? LIMIT 1');
$stmtBook->execute([$event_id, $user['id']]);
$hasBooking = (bool)$stmtBook->fetch();
if (!$hasBooking) {
    $error = lang('must_book_before_review') ?? 'You must book this event before reviewing.';
}

// Check if already reviewed
$stmtRev = $pdo->prepare('SELECT id FROM reviews WHERE event_id = ? AND user_id = ? LIMIT 1');
$stmtRev->execute([$event_id, $user['id']]);
$alreadyReviewed = (bool)$stmtRev->fetch();
if ($alreadyReviewed) {
    $error = lang('already_reviewed');
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && empty($error)) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = lang('invalid_rating') ?? 'Invalid rating.';
    } else {
        // Prevent duplicate review at insertion time
        $stmtCheck = $pdo->prepare('SELECT id FROM reviews WHERE event_id = ? AND user_id = ? LIMIT 1');
        $stmtCheck->execute([$event_id, $user['id']]);
        if ($stmtCheck->fetch()) {
            $error = lang('already_reviewed');
        } else {
            $stmtIns = $pdo->prepare('INSERT INTO reviews (event_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');
            if ($stmtIns->execute([$event_id, $user['id'], $rating, $comment])) {
                $_SESSION['success_message'] = lang('review_submitted_success') ?? 'Review submitted successfully.';
                header('Location: ?page=event_detail&id=' . $event_id);
                exit;
            } else {
                $error = lang('review_submission_failed') ?? 'Failed to submit review.';
            }
        }
    }
}

// Variables $event and $error now available to reviews.php
?>
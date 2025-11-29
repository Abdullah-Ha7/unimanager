<?php
// Load PHPMailer directly (avoid Composer autoloader and platform checks)
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Function to send email
function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // 🔧 SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ksu19577@gmail.com';
        $mail->Password   = 'shzdjhztzzigabna';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // 📧 Sender and recipient
        $mail->setFrom('ksu19577@gmail.com', 'King Saud University Events');
        $mail->addAddress($to);

        // 📝 Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // ✅ Send it
        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ✅ Function to send booking confirmation
function sendBookingConfirmation($user_email, $user_name, $event_title, $event_date, $event_end_at, $event_location, $language = 'en', $booking_id = null, $role_id = null) {
    // Only generate QR for students (role_id === 3); cast to int to avoid type mismatch
    $includeQR = ((int)$role_id === 3);
    $verifyUrl = '';
    $qrUrl = '';
    if ($includeQR) {
        $sig = hash('sha256', (string)$booking_id . '|' . $user_email . '|' . SECRET_KEY);
        $verifyUrl = BASE_URL . '/?page=verify_booking&booking_id=' . urlencode((string)$booking_id) . '&sig=' . urlencode($sig);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($verifyUrl);
    }

    // Build email HTML with QR image URL
    if ($language == 'ar') {
        $subject = 'تأكيد حجز الفعالية';
        $qrSection = '';
        if ($includeQR) {
            $qrSection = "<p>أظهر رمز الاستجابة (QR) عند الدخول:</p>
                <p style='text-align:center'><img src='" . htmlspecialchars($qrUrl) . "' alt='QR Code' style='max-width:220px; height:auto;'/></p>
                <p style='text-align:center'><a href='" . htmlspecialchars($verifyUrl) . "' target='_blank' style='color:#0d6efd;'>رابط التحقق المباشر</a></p>";
        } elseif ($verifyUrl) {
            // Fallback: show direct link even if image blocked
            $qrSection = "<p style='text-align:center'><a href='" . htmlspecialchars($verifyUrl) . "' target='_blank' style='color:#0d6efd;'>رابط التحقق المباشر</a></p>";
        }
        $bodyInner = "
            <div style='font-family: Arial, sans-serif; direction: rtl; text-align: right;'>
                <h2 style='color: #2c3e50;'>تأكيد الحجز</h2>
                <p>مرحباً <strong>" . htmlspecialchars($user_name) . "</strong>،</p>
                <p>تم تأكيد حجزك في الفعالية بنجاح.</p>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='color: #27ae60; margin-top: 0;'>تفاصيل الفعالية:</h3>
                    <p><strong>رقم الحجز:</strong> " . htmlspecialchars((string)$booking_id) . "</p>
                    <p><strong>اسم الفعالية:</strong> " . htmlspecialchars($event_title) . "</p>
                    <p><strong>تاريخ البدء:</strong> " . htmlspecialchars($event_date) . "</p>
                    <p><strong>تاريخ الانتهاء:</strong> " . htmlspecialchars($event_end_at) . "</p>
                    <p><strong>المكان:</strong> " . htmlspecialchars($event_location) . "</p>
                </div>
                " . $qrSection . "
                <hr>
                <p style='color: #7f8c8d; font-size: 12px;'>هذا البريد الإلكتروني مرسل تلقائياً، يرجى عدم الرد عليه.</p>
            </div>
        ";
    } else {
        $subject = 'Event Booking Confirmation';
        $qrSection = '';
        if ($includeQR) {
            $qrSection = "<p>Please show the QR code at entry:</p>
                <p style='text-align:center'><img src='" . htmlspecialchars($qrUrl) . "' alt='QR Code' style='max-width:220px; height:auto;'/></p>
                <p style='text-align:center'><a href='" . htmlspecialchars($verifyUrl) . "' target='_blank' style='color:#0d6efd;'>Direct verify link</a></p>";
        } elseif ($verifyUrl) {
            // Fallback if QR not included but link exists
            $qrSection = "<p style='text-align:center'><a href='" . htmlspecialchars($verifyUrl) . "' target='_blank' style='color:#0d6efd;'>Direct verify link</a></p>";
        }
        $bodyInner = "
            <div style='font-family: Arial, sans-serif;'>
                <h2 style='color: #2c3e50;'>Booking Confirmation</h2>
                <p>Hello <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                <p>Your event booking has been confirmed successfully.</p>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <h3 style='color: #27ae60; margin-top: 0;'>Event Details:</h3>
                    <p><strong>Booking ID:</strong> " . htmlspecialchars((string)$booking_id) . "</p>
                    <p><strong>Event Title:</strong> " . htmlspecialchars($event_title) . "</p>
                    <p><strong>Start:</strong> " . htmlspecialchars($event_date) . "</p>
                    <p><strong>End:</strong> " . htmlspecialchars($event_end_at) . "</p>
                    <p><strong>Location:</strong> " . htmlspecialchars($event_location) . "</p>
                </div>
                " . $qrSection . "
                <hr>
                <p style='color: #7f8c8d; font-size: 12px;'>This is an automated email, please do not reply.</p>
            </div>
        ";
    }
    // Send email (no embedded CID required)
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ksu19577@gmail.com';
        $mail->Password   = 'shzdjhztzzigabna';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('ksu19577@gmail.com', 'King Saud University Events');
        $mail->addAddress($user_email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyInner;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Booking mail failed: ' . $mail->ErrorInfo);
        return false;
    }
}


function sendResetPassword($to, $user_name, $reset_link, $language) { // ✅ الترتيب الصحيح
    
    // 1. تعريف القيم الافتراضية (اللغة الإنجليزية) أولاً لمنع تحذيرات Undefined variable
    $subject = '🔑 Password Reset Request';
    $body = "
        <div style='font-family: Arial, sans-serif;'>
            <h2 style='color: #2c3e50;'>Hello {$user_name},</h2>
            <p>You requested to reset your password.</p>
            <p><strong>Click the link below to set a new password:</strong></p>
            <p style='margin: 20px 0;'>
                <a href='{$reset_link}' target='_blank' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    Reset Password
                </a>
            </p>
            <p>This link will expire in 1 hour.</p>
        </div>
    ";

    // 2. التجاوز (Override) للغة العربية إذا كانت مطلوبة
    if ($language == 'ar') {
        $subject = 'طلب إعادة تعيين كلمة المرور 🔑';
        $body = "
            <div style='font-family: Arial, sans-serif; text-align: right;' dir='rtl'>
                <h2 style='color: #2c3e50;'>مرحباً {$user_name},</h2>
                <p>لقد طلبت إعادة تعيين كلمة مرورك.</p>
                <p><strong>انقر على الرابط أدناه لتعيين كلمة مرور جديدة:</strong></p>
                <p style='margin: 20px 0;'>
                    <a href='{$reset_link}' target='_blank' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        إعادة تعيين كلمة المرور
                    </a>
                </p>
                <p>يرجى ملاحظة أن هذا الرابط سينتهي مفعوله خلال ساعة واحدة.</p>
            </div>
        ";
    }

    // 3. استدعاء الدالة الرئيسية (السطر 106)
    return sendMail($to, $subject, $body); 
}
?>
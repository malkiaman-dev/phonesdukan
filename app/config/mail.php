<?php
require_once dirname(__DIR__, 2) . '/app/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__, 2) . '/app/PHPMailer/SMTP.php';
require_once dirname(__DIR__, 2) . '/app/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@phonesdukan.com';
    $mail->Password   = 'Phones&Dukan12!';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->SMTPDebug  = 0;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom('info@phonesdukan.com', 'Phones Dukan');
    $mail->isHTML(true);
    return $mail;
}

// Wraps an email body string (one or more <tr> blocks) in the full branded shell.
function emailShell(string $body): string {
    $year = date('Y');

    $h  = '<!DOCTYPE html><html lang="en"><head>';
    $h .= '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    $h .= '<meta http-equiv="X-UA-Compatible" content="IE=edge"></head>';
    $h .= '<body style="margin:0;padding:0;background:#f0f0f0;font-family:Arial,Helvetica,sans-serif;">';
    $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f0f0;">';
    $h .= '<tr><td align="center" style="padding:30px 15px;">';
    $h .= '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" ';
    $h .= 'style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.15);">';

    // ── Brand Header ──────────────────────────────────────────────────────────
    $h .= '<tr><td align="center" style="background:#111111;padding:28px 40px;border-bottom:4px solid #f7d117;">';
    $h .= '<div style="font-size:24px;font-weight:700;letter-spacing:2px;font-family:Arial,Helvetica,sans-serif;margin-bottom:6px;">';
    $h .= '<span style="color:#f7d117;">PHONES</span><span style="color:#ffffff;">&nbsp;DUKAN</span></div>';
    $h .= '<div style="font-size:10px;color:#888888;letter-spacing:4px;text-transform:uppercase;font-family:Arial,Helvetica,sans-serif;">Your Trusted Mobile Store</div>';
    $h .= '</td></tr>';

    // ── Injected body rows ────────────────────────────────────────────────────
    $h .= $body;

    // ── Brand Footer ──────────────────────────────────────────────────────────
    $h .= '<tr><td style="background:#1a1a1a;padding:28px 40px;text-align:center;">';
    $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
    $h .= '<tr><td align="center" style="padding-bottom:10px;font-family:Arial,sans-serif;font-size:13px;">';
    $h .= '<a href="https://phonesdukan.com" style="color:#f7d117;text-decoration:none;font-weight:600;">phonesdukan.com</a>';
    $h .= '&nbsp;&nbsp;&bull;&nbsp;&nbsp;';
    $h .= '<a href="https://wa.me/+923116600031" style="color:#f7d117;text-decoration:none;">WhatsApp</a>';
    $h .= '&nbsp;&nbsp;&bull;&nbsp;&nbsp;';
    $h .= '<a href="https://phonesdukan.com/contact-us" style="color:#f7d117;text-decoration:none;">Contact Us</a>';
    $h .= '</td></tr>';
    $h .= "<tr><td align=\"center\"><p style=\"margin:0;color:#555555;font-size:11px;font-family:Arial,Helvetica,sans-serif;\">";
    $h .= "&copy; {$year} Phones Dukan &bull; Islamabad, Pakistan &bull; All Rights Reserved</p></td></tr>";
    $h .= '</table></td></tr>';

    $h .= '</table></td></tr></table></body></html>';
    return $h;
}

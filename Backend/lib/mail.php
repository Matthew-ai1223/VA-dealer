<?php
/**
 * Email helper — uses PHPMailer (SMTP) when available, falls back to PHP mail()
 *
 * Credentials are loaded from Backend/config/mail.local.php (never committed).
 * See mail.local.php for Gmail App Password setup instructions.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Load SMTP credentials from mail.local.php
 */
function _mailConfig(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $localFile = __DIR__ . '/../config/mail.local.php';
    if (is_file($localFile)) {
        $cfg = require $localFile;
    } else {
        $cfg = [];
    }
    return $cfg;
}

/**
 * Send an email via SMTP (PHPMailer) or fallback to PHP mail()
 *
 * @param string $to      Recipient email address
 * @param string $subject Email subject
 * @param string $body    Plain-text body
 * @param string $htmlBody Optional HTML body (if empty, plain text is used)
 * @return bool
 */
function sendMail(string $to, string $subject, string $body, string $htmlBody = ''): bool
{
    $mailCfg  = _mailConfig();
    $appCfg   = appConfig();

    $fromEmail = $mailCfg['from_email'] ?? ($appCfg['mail_from'] ?? 'noreply@localhost');
    $fromName  = $mailCfg['from_name']  ?? ($appCfg['site_name'] ?? 'VA Auto Sales');

    // ── PHPMailer path ────────────────────────────────────────────────────────
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!empty($mailCfg['username']) && !empty($mailCfg['password']) && is_file($autoload)) {
        require_once $autoload;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host        = $mailCfg['host']       ?? 'smtp.gmail.com';
            $mail->Port        = (int) ($mailCfg['port'] ?? 587);
            $mail->SMTPAuth    = true;
            $mail->Username    = $mailCfg['username'];
            $mail->Password    = $mailCfg['password'];
            $mail->SMTPSecure  = ($mailCfg['encryption'] ?? 'tls') === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;

            if ($htmlBody !== '') {
                $mail->isHTML(true);
                $mail->Body    = $htmlBody;
                $mail->AltBody = $body;
            } else {
                $mail->isHTML(false);
                $mail->Body = $body;
            }

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('[VA Mail] PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }

    // ── Fallback: native PHP mail() ───────────────────────────────────────────
    $headers = implode("\r\n", [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
    ]);
    return @mail($to, $subject, $body, $headers);
}

// ── Convenience wrappers ─────────────────────────────────────────────────────

/**
 * Notify admin of a new lead submission
 */
function sendLeadNotification(array $lead): bool
{
    $config = appConfig();
    $to     = trim($config['admin_email'] ?? '');
    if ($to === '') {
        return false;
    }

    $subject = sprintf('[VA Auto Sales] New lead: %s — %s',
        $lead['full_name'] ?? 'Unknown',
        $lead['interested_vehicle'] ?? 'Unknown vehicle'
    );

    $detailUrl = fullUrl('Backend/admin/lead-detail.php?id=' . (int) ($lead['id'] ?? 0));

    $plain = implode("\n", [
        'A new lead has been submitted on VA Auto Sales.',
        '',
        'Name:     ' . ($lead['full_name']           ?? ''),
        'Phone:    ' . ($lead['phone_number']         ?? ''),
        'Email:    ' . ($lead['email']                ?? 'Not provided'),
        'Vehicle:  ' . ($lead['interested_vehicle']   ?? ''),
        'Source:   ' . ucfirst(str_replace('_', ' ', $lead['source']       ?? 'website')),
        'Type:     ' . ucfirst(str_replace('_', ' ', $lead['inquiry_type'] ?? 'request_info')),
        'Status:   ' . ucfirst(str_replace('_', ' ', $lead['status']       ?? 'new')),
        '',
        'View in admin: ' . $detailUrl,
    ]);

    $html = '
<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
  <div style="background:#1e40af;padding:20px 28px;">
    <h2 style="color:#fff;margin:0;font-size:18px;">🚗 New Lead — VA Auto Sales</h2>
  </div>
  <div style="padding:24px 28px;background:#f8fafc;">
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
      <tr><td style="padding:7px 0;color:#64748b;width:90px;">Name</td><td style="padding:7px 0;font-weight:600;">' . htmlspecialchars($lead['full_name'] ?? '', ENT_QUOTES) . '</td></tr>
      <tr><td style="padding:7px 0;color:#64748b;">Phone</td><td style="padding:7px 0;font-weight:600;">' . htmlspecialchars($lead['phone_number'] ?? '', ENT_QUOTES) . '</td></tr>
      <tr><td style="padding:7px 0;color:#64748b;">Email</td><td style="padding:7px 0;">' . htmlspecialchars($lead['email'] ?? 'Not provided', ENT_QUOTES) . '</td></tr>
      <tr><td style="padding:7px 0;color:#64748b;">Vehicle</td><td style="padding:7px 0;">' . htmlspecialchars($lead['interested_vehicle'] ?? '', ENT_QUOTES) . '</td></tr>
      <tr><td style="padding:7px 0;color:#64748b;">Source</td><td style="padding:7px 0;">' . ucfirst(str_replace('_', ' ', $lead['source'] ?? 'website')) . '</td></tr>
      <tr><td style="padding:7px 0;color:#64748b;">Type</td><td style="padding:7px 0;">' . ucfirst(str_replace('_', ' ', $lead['inquiry_type'] ?? 'request_info')) . '</td></tr>
    </table>
  </div>
  <div style="padding:16px 28px;background:#fff;text-align:center;border-top:1px solid #e2e8f0;">
    <a href="' . $detailUrl . '" style="display:inline-block;background:#1e40af;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">View Lead in Admin →</a>
  </div>
</div>';

    return sendMail($to, $subject, $plain, $html);
}

/**
 * Send a nurturing / follow-up email to a lead
 */
function sendNurtureEmail(string $to, string $name, string $subject, string $body): bool
{
    if (trim($to) === '') {
        return false;
    }

    $html = '
<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
  <div style="background:#1e40af;padding:20px 28px;">
    <h2 style="color:#fff;margin:0;font-size:18px;">VA Auto Sales</h2>
  </div>
  <div style="padding:28px;background:#f8fafc;font-size:15px;line-height:1.7;color:#0f172a;">
    <p>Hi ' . htmlspecialchars($name, ENT_QUOTES) . ',</p>
    ' . nl2br(htmlspecialchars($body, ENT_QUOTES)) . '
  </div>
  <div style="padding:16px 28px;background:#fff;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;text-align:center;">
    VA Auto Sales · Nigeria<br>
    <a href="' . fullUrl('Frontend/listings.php') . '" style="color:#1e40af;">Browse our listings</a>
  </div>
</div>';

    return sendMail($to, $subject, $body, $html);
}

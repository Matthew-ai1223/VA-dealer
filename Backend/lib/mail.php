<?php
/**
 * Simple email notifications for new leads
 */

function sendLeadNotification(array $lead): bool
{
    $config = appConfig();
    $to = trim($config['admin_email'] ?? '');

    if ($to === '') {
        return false;
    }

    $subject = sprintf('[VA Auto Sales] New lead: %s — %s', $lead['full_name'], $lead['interested_vehicle']);

    $body = implode("\n", [
        'A new lead has been submitted on VA Auto Sales.',
        '',
        'Name:     ' . ($lead['full_name'] ?? ''),
        'Phone:    ' . ($lead['phone_number'] ?? ''),
        'Email:    ' . ($lead['email'] ?? 'Not provided'),
        'Vehicle:  ' . ($lead['interested_vehicle'] ?? ''),
        'Source:   ' . ucfirst(str_replace('_', ' ', $lead['source'] ?? 'website')),
        'Type:     ' . ucfirst(str_replace('_', ' ', $lead['inquiry_type'] ?? 'request_info')),
        'Status:   ' . ucfirst(str_replace('_', ' ', $lead['status'] ?? 'new')),
        '',
        'View in admin: ' . fullUrl('Backend/admin/lead-detail.php?id=' . (int) ($lead['id'] ?? 0)),
    ]);

    $from = $config['mail_from'] ?? 'noreply@localhost';
    $headers = implode("\r\n", [
        'From: ' . $from,
        'Reply-To: ' . ($lead['email'] ?: $from),
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
    ]);

    return @mail($to, $subject, $body, $headers);
}

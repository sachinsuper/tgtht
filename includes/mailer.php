<?php
/**
 * Lead notification email. Uses PHP mail() by default; switch to SMTP in
 * config.php if your host's mail() is unreliable (PHPMailer required).
 */

function send_lead_email(array $lead, array $page): bool
{
    $recipients = [];

    foreach (preg_split('/[,;\s]+/', (string) ($page['notify_emails'] ?? '')) as $r) {
        if (filter_var($r, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $r;
        }
    }
    foreach (preg_split('/[,;\s]+/', setting('notify_emails', '')) as $r) {
        if (filter_var($r, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $r;
        }
    }
    $recipients = array_values(array_unique($recipients));

    if (!$recipients) {
        return false;
    }

    $subject = sprintf('New lead: %s — %s', $lead['phone'] ?: 'no phone', $page['name']);

    $rows = [
        'Phone'    => $lead['phone'],
        'Name'     => $lead['name'],
        'Email'    => $lead['email'],
        'Message'  => $lead['message'],
        'Page'     => $page['name'] . ' (' . $page['slug'] . ')',
        'Source'   => trim(($lead['utm_source'] ?? '') . ' / ' . ($lead['utm_medium'] ?? '') . ' / ' . ($lead['utm_campaign'] ?? ''), ' /'),
        'Received' => $lead['created_at'],
        'IP'       => $lead['ip'],
    ];

    $html = '<div style="font:15px/1.6 -apple-system,Segoe UI,Roboto,sans-serif;color:#111">'
          . '<h2 style="margin:0 0 4px;font-size:19px">New call-back request</h2>'
          . '<p style="margin:0 0 16px;color:#666">from ' . e($page['name']) . '</p>'
          . '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px">';
    foreach ($rows as $k => $v) {
        if (trim((string) $v) === '') {
            continue;
        }
        $html .= '<tr>'
               . '<td style="border:1px solid #e3e3e3;background:#fafafa;font-weight:600;white-space:nowrap">' . e($k) . '</td>'
               . '<td style="border:1px solid #e3e3e3">' . nl2br(e($v)) . '</td>'
               . '</tr>';
    }
    $html .= '</table>';
    if ($lead['phone']) {
        $tel = preg_replace('/\D/', '', $lead['phone']);
        $html .= '<p style="margin:18px 0 0">'
               . '<a href="tel:+91' . e($tel) . '" style="background:#FFD100;color:#111;padding:10px 18px;border-radius:4px;font-weight:700;text-decoration:none">Call now</a> &nbsp; '
               . '<a href="https://wa.me/91' . e($tel) . '" style="background:#25D366;color:#fff;padding:10px 18px;border-radius:4px;font-weight:700;text-decoration:none">WhatsApp</a>'
               . '</p>';
    }
    $html .= '</div>';

    if (SMTP_ENABLED && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return send_via_smtp($recipients, $subject, $html, $lead);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
    ];
    if (filter_var($lead['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $lead['email'];
    }

    $ok = true;
    foreach ($recipients as $to) {
        $ok = @mail($to, $subject, $html, implode("\r\n", $headers)) && $ok;
    }
    return $ok;
}

function send_via_smtp(array $recipients, string $subject, string $html, array $lead): bool
{
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        foreach ($recipients as $to) {
            $mail->addAddress($to);
        }
        if (filter_var($lead['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($lead['email']);
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags(str_replace(['</tr>', '</td>'], ["\n", ': '], $html));
        return $mail->send();
    } catch (Throwable $e) {
        error_log('SMTP lead mail failed: ' . $e->getMessage());
        return false;
    }
}

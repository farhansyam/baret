<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Pengiriman email sederhana.
 * MAIL_DRIVER=log  -> ditulis ke storage/logs/mail.log (untuk pengembangan)
 * MAIL_DRIVER=mail -> fungsi mail() PHP (butuh MTA/SMTP relay di server)
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $from = (string) config('mail.from');
        $fromName = (string) config('mail.from_name');
        if (config('mail.driver') === 'mail') {
            $headers = [
                'From' => sprintf('%s <%s>', mb_encode_mimeheader($fromName), $from),
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Mailer' => 'BARET',
            ];
            return mail($to, mb_encode_mimeheader($subject), $body, $headers);
        }
        $entry = sprintf("[%s] To: %s\nSubject: %s\n\n%s\n%s\n", now(), $to, $subject, $body, str_repeat('-', 60));
        return file_put_contents(STORAGE_PATH . '/logs/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}

<?php
/**
 * services/EmailTemplate.php
 *
 * Shared builder for branded HR/Legal Compliance emails.
 */

namespace App\Services;

class EmailTemplate
{
    public const LOGO_CID = 'bestlink-logo';
    public const SIGNATORY_CID = 'signatory-image';

    public static function signatoryUrl(): string
    {
        $localPath = __DIR__ . '/../../assets/images.png';
        if (is_file($localPath)) {
            return 'assets/images.png';
        }
        return '';
    }

    public static function logoUrl(): string
    {
        return 'https://s3.ap-southeast-1.amazonaws.com/buckets.epicareer.com/employer/logo/20240919150720-2590899-bestlink-college-of-the-philippines.png';
    }

    public static function logoDataUri(): string
    {
        return self::logoUrl();
    }

    public static function logoImageTag(bool $forMailer = false): string
    {
        if ($forMailer) {
            $src = 'cid:' . self::LOGO_CID;
        } else {
            $src = self::logoUrl();
        }

        if ($src === '') {
            return '<div style="width:48px;height:48px;border-radius:10px;background:#0d6efd;"></div>';
        }

        return '<img src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" alt="Bestlink College of the Philippines" '
            . 'width="180" height="56" '
            . 'style="display:block;max-height:56px;max-width:180px;width:auto;height:auto;">';
    }

    public static function embedLogo(object $mail): bool
    {
        $logoUrl = self::logoUrl();
        $data = false;

        if ($logoUrl !== '') {
            if (preg_match('#^https?://#i', $logoUrl)) {
                $data = @file_get_contents($logoUrl);
            } else {
                $absPath = realpath(__DIR__ . '/../' . ltrim($logoUrl, '/'));
                if ($absPath !== false && is_file($absPath)) {
                    $data = @file_get_contents($absPath);
                }
            }
        }

        if ($data === false || $data === '') {
            $local = __DIR__ . '/../../assets/bcp_logo.png';
            if (is_file($local)) {
                $data = @file_get_contents($local);
            }
        }

        if ($data === false || $data === '') {
            $local = __DIR__ . '/../../assets/bcp-logo.png';
            if (is_file($local)) {
                $data = @file_get_contents($local);
            }
        }

        if (empty($data)) {
            return false;
        }

        $type = 'image/png';
        if (substr($data, 0, 3) === "\xff\xd8\xff") {
            $type = 'image/jpeg';
        } elseif (substr($data, 0, 4) === 'GIF8') {
            $type = 'image/gif';
        }

        return (bool) $mail->addStringEmbeddedImage(
            $data,
            self::LOGO_CID,
            'bestlink-logo.png',
            'base64',
            $type
        );
    }

    public static function embedSignatory(object $mail): bool
    {
        $localPath = __DIR__ . '/../../assets/images.png';
        if (!is_file($localPath)) {
            return false;
        }

        $data = @file_get_contents($localPath);
        if (empty($data)) {
            return false;
        }

        $type = 'image/png';
        if (substr($data, 0, 3) === "\xff\xd8\xff") {
            $type = 'image/jpeg';
        } elseif (substr($data, 0, 4) === 'GIF8') {
            $type = 'image/gif';
        }

        return (bool) $mail->addStringEmbeddedImage(
            $data,
            self::SIGNATORY_CID,
            'signatory.png',
            'base64',
            $type
        );
    }

    public static function generateReferenceId(): string
    {
        $date = date('Ymd');
        $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        return 'LCO-' . $date . '-' . $suffix;
    }

    public static function companyConfig(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $configPath = __DIR__ . '/../../lib/config/email_config.php';
        $config = is_file($configPath) ? require $configPath : [];

        $cached = [
            'name' => (string) ($config['company_name'] ?? 'Bestlink College of the Philippines'),
            'email' => (string) ($config['company_email'] ?? 'hr@bestlink.edu.ph'),
            'website' => (string) ($config['company_website'] ?? 'www.bestlinkcollege.edu.ph'),
            'address' => (string) ($config['company_address'] ?? 'Quirino Highway, Brgy. Minuyan Proper, City of San Jose del Monte, Bulacan'),
        ];

        return $cached;
    }

    public static function buildHtml(
        string $subject,
        string $bodyText,
        string $recipientName = '',
        string $company = '',
        string $portalUrl = '',
        string $referenceId = '',
        bool $forMailer = false,
        string $signatoryName = ''
    ): string {
        if ($referenceId === '') {
            $referenceId = self::generateReferenceId();
        }
        $logoTag = self::logoImageTag($forMailer);
        $signatorySrc = $forMailer
            ? 'cid:' . self::SIGNATORY_CID
            : 'assets/img/images.png';

        $cfg = self::companyConfig();
        if ($company === '') {
            $company = $cfg['name'];
        }

        $name  = $recipientName !== '' ? htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'Employee';
        $subj  = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $comp  = htmlspecialchars($company, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $ref   = htmlspecialchars($referenceId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $year  = (int) date('Y');
        $date  = htmlspecialchars(date('F j, Y'), ENT_QUOTES, 'UTF-8');
        $compEmail   = htmlspecialchars($cfg['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $compWebsite = htmlspecialchars($cfg['website'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $compAddress = htmlspecialchars($cfg['address'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $signName = $signatoryName !== '' ? htmlspecialchars($signatoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'HR Director';
        $message = nl2br(htmlspecialchars($bodyText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $message = self::emphasiseImportant($message);

        if ($portalUrl === '') {
            $portalUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
                . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . '/hrms-capstone';
        }
        $portal = htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8');

        $timeStr = date('g:i A');
        $dateTime = $date . ' &bull; ' . $timeStr;

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$subj}</title>
</head>
<body style="margin:0;padding:0;background:#eef1f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2733;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#eef1f6" style="background:#eef1f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e3e8ef;">

          <tr>
            <td bgcolor="#0e1c33" style="background:#0e1c33;padding:22px 28px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="52" style="width:52px;vertical-align:middle;">{$logoTag}</td>
                  <td style="vertical-align:middle;padding-left:14px;">
                    <div style="font-size:16px;font-weight:800;color:#ffffff;line-height:1.2;letter-spacing:0.03em;">BESTLINK COLLEGE OF THE PHILIPPINES</div>
                    <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.7);margin-top:2px;">Bulacan Campus</div>
                    <div style="font-size:12px;font-weight:500;color:#e3c479;margin-top:1px;">Human Resources &amp; Legal Compliance Office</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td bgcolor="#f8f9fb" style="background:#f8f9fb;padding:18px 28px 14px;border-bottom:1px solid #e4e8ee;">
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#8b93a1;margin-bottom:12px;">&#9993; Email Information</div>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="padding:3px 0;font-size:13px;line-height:1.5;color:#1b2430;"><span style="display:inline-block;width:70px;font-weight:600;color:#5b6472;font-size:12px;">From</span> Human Resources &amp; Legal Compliance Office</td></tr>
                <tr><td style="padding:3px 0;font-size:13px;line-height:1.5;color:#1b2430;"><span style="display:inline-block;width:70px;font-weight:600;color:#5b6472;font-size:12px;">To</span> {$name}</td></tr>
                <tr><td style="padding:3px 0;font-size:13px;line-height:1.5;color:#1b2430;"><span style="display:inline-block;width:70px;font-weight:600;color:#5b6472;font-size:12px;">CC</span> Department Head (Optional)</td></tr>
                <tr><td style="padding:3px 0;font-size:13px;line-height:1.5;color:#1b2430;"><span style="display:inline-block;width:70px;font-weight:600;color:#5b6472;font-size:12px;">Subject</span> <strong>{$subj}</strong></td></tr>
                <tr><td style="padding:3px 0;font-size:13px;line-height:1.5;color:#1b2430;"><span style="display:inline-block;width:70px;font-weight:600;color:#5b6472;font-size:12px;">Date</span> {$dateTime}</td></tr>
                <tr><td style="padding:3px 0;font-size:13px;line-height:1.5;color:#1b2430;"><span style="display:inline-block;width:70px;font-weight:600;color:#5b6472;font-size:12px;">Priority</span> <span style="display:inline-block;padding:1px 8px;border-radius:999px;font-size:11px;font-weight:600;background:rgba(47,158,110,0.10);color:#1f7a52;">Normal</span></td></tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 28px 20px;">
              <p style="font-size:15px;line-height:1.7;color:#3a4555;margin:0 0 16px;">Dear <strong style="color:#1f2733;">{$name}</strong>,</p>
              <div style="font-size:15px;line-height:1.75;color:#3a4555;margin:0 0 24px;">{$message}</div>

              <div style="margin-top:24px;padding-top:16px;border-top:1px solid #e4e8ee;">
                <p style="margin:0 0 2px;font-size:14px;line-height:1.4;color:#3a4555;">Sincerely,</p>
                <img src="{$signatorySrc}" alt="Signatory" style="display:block;max-width:140px;max-height:56px;width:auto;height:auto;margin-top:6px;margin-bottom:4px;">
                <p style="margin:0;font-size:15px;font-weight:600;color:#0e1c33;line-height:1.4;">{$signName}</p>
                <p style="margin:0;font-size:13px;color:#5b6472;line-height:1.4;">HR Director</p>
                <p style="margin:0;font-size:12px;color:#8b93a1;line-height:1.4;">Human Resources &amp; Legal Compliance Office</p>
                <p style="margin:0;font-size:12px;color:#8b93a1;line-height:1.4;">Bestlink College of the Philippines</p>
              </div>
            </td>
          </tr>

          <tr>
            <td bgcolor="#0e1c33" style="background:#0e1c33;padding:20px 28px;text-align:center;">
               <div style="font-size:14px;font-weight:700;color:#c9a24a;line-height:1.3;">{$comp}</div>
               <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.75);line-height:1.4;margin-top:2px;">Human Resources &amp; Legal Compliance Office</div>
               <div style="font-size:11px;color:rgba(255,255,255,0.55);line-height:1.4;margin-top:2px;">{$compAddress}</div>
               <div style="font-size:12px;color:#e3c479;line-height:1.4;margin-top:4px;">&#9993; {$compEmail} &nbsp;|&nbsp; &#127760; {$compWebsite} &nbsp;|&nbsp; &#9742; 09077915906</div>
            </td>
          </tr>

          <tr>
            <td bgcolor="#fff8e6" style="background:#fff8e6;padding:14px 28px;border-left:3px solid #c9a24a;">
              <div style="font-size:11px;font-weight:700;color:#a4802e;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">&#128737; Confidentiality Notice</div>
              <div style="font-size:11px;line-height:1.45;color:#6e5a2a;">This email and any attachments may contain confidential and privileged information intended only for the designated recipient(s). Unauthorized access, disclosure, copying, distribution, or use of this information is prohibited.</div>
            </td>
          </tr>

          <tr>
            <td bgcolor="#f5f5f5" style="background:#f5f5f5;padding:14px 28px;border-top:1px solid #e4e8ee;">
              <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">&#128214; Academic / Thesis Disclaimer</div>
              <div style="font-size:10px;line-height:1.45;color:#8b93a1;margin-bottom:4px;">This email is generated solely for academic and research purposes as part of the development of a Human Resource Management System (HRMS) thesis project.</div>
              <div style="font-size:10px;line-height:1.45;color:#8b93a1;margin-bottom:4px;">The Bestlink College logo, branding, employee names, email addresses, documents, and other information displayed are used only for demonstration and system testing. They do not represent actual institutional communications or official records of Bestlink College of the Philippines.</div>
              <div style="font-size:9px;color:#9ca3af;font-style:italic;">&copy; {$year} Human Resource Management System (HRMS) Thesis Project &mdash; Bestlink College of the Philippines</div>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function emphasiseImportant(string $html): string
    {
        $bold = static function (string $h, string $pattern): string {
            return preg_replace_callback(
                $pattern,
                static fn($m) => '<strong>' . $m[0] . '</strong>',
                $h
            ) ?? $h;
        };

        $html = $bold($html, '/Legal Compliance Office/');
        $html = $bold($html, '/Employee Number\s+[A-Z0-9\-]+/');
        $html = $bold($html, '/Reference ID:\s*[A-Z0-9\-]+/');
        $html = $bold($html, '/[A-Za-z0-9_\-]+\.pdf/');
        $html = $bold($html, '/Failure to acknowledge or act upon this notice[^.]*\./');
        $html = $bold($html, '/Kindly confirm receipt, complete the required action[^.]*\./');

        return $html;
    }

    public static function buildText(
        string $subject,
        string $bodyText,
        string $recipientName = '',
        string $company = 'Bestlink College of the Philippines',
        string $referenceId = ''
    ): string {
        $name = $recipientName !== '' ? $recipientName : 'Employee';
        $text = "Dear {$name},\n\n"
            . strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyText));

        if ($referenceId === '') {
            if (preg_match('/LCO-\d{8}-[A-Z0-9]{6}/', $text, $m)) {
                $referenceId = $m[0];
            } else {
                $referenceId = self::generateReferenceId();
            }
        }
        if (stripos($text, $referenceId) === false) {
            $text .= "\n\nReference: {$referenceId}";
        }
        if (stripos($text, $company) !== false) {
            return $text;
        }
        return $text . "\n\n{$company} — BCP Bulacan HR Legal &amp; Compliance Office";
    }
}

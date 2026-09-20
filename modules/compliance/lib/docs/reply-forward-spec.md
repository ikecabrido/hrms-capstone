# Notification Reply / Forward — Technical Specification

## 1. Overview

This document defines the behavior for **Reply** and **Forward** actions in the HRMS Compliance notification compose flow. It specifies how recipient fields, subject lines, and message bodies are derived from the original `lc_notifications` row.

## 2. Data Model

`lc_notifications` relevant columns:

| Column | Purpose |
|---|---|
| `id` | Primary key |
| `employee_id` | Sender's employee ID (for system-generated notifications) |
| `email` | Fallback sender or recipient email |
| `sender_email` | Explicit sender email (preferred for reply) |
| `title` | Original subject line |
| `message` | Original message body |
| `type` | Notification category / key |
| `module` | Source module |
| `is_read` | Read flag |
| `created_at` | Timestamp |

## 3. Reply Mode

### 3.1 Recipient Resolution

The reply recipient is the **original sender** of the notification.

Priority:
1. `sender_email` column (if valid email)
2. `email` column (if valid email)
3. If neither is available, reply is **disabled** with a warning banner.

```php
$replyRecipient = '';
if (filter_var($row['sender_email'], FILTER_VALIDATE_EMAIL)) {
    $replyRecipient = $row['sender_email'];
} elseif (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
    $replyRecipient = $row['email'];
}
```

### 3.2 Subject Line

Prepend `Re: ` to the original `title` **only if** it does not already start with `Re: ` (case-insensitive).

```js
function buildReplySubject(originalSubject) {
    var s = (originalSubject || '').trim();
    if (s === '') return 'Re: ';
    if (/^re:\s*/i.test(s)) return s;
    return 'Re: ' + s;
}
```

### 3.3 Body

Prepend a quoted block containing the original message:

```
On <original_date>, <sender_name> wrote:

> <original_message>
```

### 3.4 UI Behavior

- Recipient field is **pre-filled** with the resolved sender email as a non-removable chip (or clearly marked as the reply-to address).
- User may add additional recipients.
- Send button is enabled only when at least one recipient is present.

## 4. Forward Mode

### 4.1 Recipient Resolution

Forward has **no implicit recipient**. The recipient field starts empty. The user must manually select at least one recipient.

### 4.2 Subject Line

Prepend `Fwd: ` to the original `title` **only if** it does not already start with `Fwd: ` (case-insensitive).

```js
function buildForwardSubject(originalSubject) {
    var s = (originalSubject || '').trim();
    if (s === '') return 'Fwd: ';
    if (/^fwd:\s*/i.test(s)) return s;
    return 'Fwd: ' + s;
}
```

### 4.3 Body

Prepend a quoted block containing the original message:

```
---------- Forwarded message ---------
From: <sender_email>
Date: <original_date>
Subject: <original_subject>

> <original_message>
```

### 4.4 UI Behavior

- Recipient field is **empty**.
- User must add at least one recipient before sending.
- Attachments from the original notification (if any) are preserved.

## 5. Subject Prefix Deduplication

To avoid stacking prefixes (`Re: Re: ...` or `Fwd: Fwd: ...`):

| Existing prefix | Action |
|---|---|
| `Re: ...` | Do not prepend another `Re: ` |
| `Fwd: ...` | Do not prepend another `Fwd: ` |
| None | Prepend the appropriate prefix |

## 6. Server-Side Validation (`notification-send.php`)

Regardless of client-side behavior, the server must:

1. Validate all recipients are valid emails.
2. Reject empty recipient lists.
3. Apply the same subject prefix logic if the client failed to do so (defense in depth).
4. Log the original `notification_id` and `mode` in the sent record for audit.

## 7. Security Considerations

- Never trust client-side `to_recipient_email` blindly; validate on server.
- Do not expose internal employee IDs in email addresses.
- Sanitize all quoted original message content before rendering in HTML preview.

## 8. Implementation Files

| File | Responsibility |
|---|---|
| `modules/compliance/pages/notification-compose.php` | Load original notification, resolve reply recipient, pass config to JS |
| `modules/compliance/js/pages/notification-compose.js` | UI state, subject prefixing, chip management, preview rendering |
| `modules/compliance/lib/api/notification-send.php` | Persist notification, dispatch email via PHPMailer |

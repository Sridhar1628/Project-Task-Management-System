<?php
require 'config.php';          // DB connection
require 'email_config.php';    // SMTP settings

// Include PHPMailer manually
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set MySQL session time zone to IST
$conn->query("SET time_zone = '+05:30'");

// Fetch projects with deadlines in 1, 3, or 5 days
$sql = "
SELECT
    p.id AS project_id,
    p.project_name,
    p.deadline,
    ap.team_id,
    DATEDIFF(p.deadline, CURDATE()) AS days_left,
    GROUP_CONCAT(DISTINCT tm.member_email)   AS member_emails,
    GROUP_CONCAT(DISTINCT tl.leader_email)   AS leader_emails
FROM projects p
JOIN assigned_projects ap ON ap.project_id = p.id
LEFT JOIN team_members tm   ON tm.team_id = ap.team_id
LEFT JOIN team_leaders tl   ON tl.team_id = ap.team_id
WHERE p.status <> 'Completed'
  AND DATEDIFF(p.deadline, CURDATE()) IN (1,3,5)
GROUP BY p.id, ap.team_id, p.project_name, p.deadline
ORDER BY p.deadline ASC
";

$res = $conn->query($sql);
if (!$res) {
    error_log('Query failed: ' . $conn->error);
    exit;
}

// Prepared statements for log checks / inserts
$chk = $conn->prepare("
    SELECT 1
    FROM deadline_reminder_logs
    WHERE project_id = ? AND team_id = ? AND days_left = ? AND sent_on = CURDATE()
");

$ins = $conn->prepare("
    INSERT INTO deadline_reminder_logs (project_id, team_id, days_left, sent_on)
    VALUES (?, ?, ?, CURDATE())
");

// Convert CSV emails to array
function listToArray($csv) {
    if (!$csv) return [];
    $parts = array_map('trim', explode(',', $csv));
    $parts = array_filter($parts, function($e){ return $e !== ''; });
    return array_values(array_unique($parts));
}

// Get all admins (fallback)
function getAllAdmins($conn) {
    $out = [];
    $q = $conn->query("SELECT email FROM admins");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            if (!empty($r['email'])) $out[] = trim($r['email']);
        }
    }
    return array_values(array_unique($out));
}

$countSent = 0;

while ($row = $res->fetch_assoc()) {
    $projectId   = (int)$row['project_id'];
    $teamId      = (int)$row['team_id'];
    $daysLeft    = (int)$row['days_left'];
    $deadlineYmd = $row['deadline'];
    $projectName = $row['project_name'];

    // Skip if already sent today
    $chk->bind_param('iii', $projectId, $teamId, $daysLeft);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) continue;

    $leaderEmails = listToArray($row['leader_emails']);
    $memberEmails = listToArray($row['member_emails']);
    $adminEmails  = getAllAdmins($conn); // send to all admins

    if (empty($adminEmails) && empty($leaderEmails) && empty($memberEmails)) continue;

    // Build email
    $dueDateFmt = date('d M Y', strtotime($deadlineYmd));
    $plural = ($daysLeft === 1) ? '' : 's';
    $subject = "Reminder: \"{$projectName}\" deadline in {$daysLeft} day{$plural} (due {$dueDateFmt})";

    $projectUrl = "https://your-domain.com/admin/view_project.php?id=" . urlencode($projectId);

    $html = "
    <div style='font-family:Arial,Helvetica,sans-serif; font-size:14px; line-height:1.6'>
        <p>Hello Team,</p>
        <p>This is a reminder that the project <b>{$projectName}</b> is due on <b>{$dueDateFmt}</b>.</p>
        <ul>
            <li><b>Days left:</b> {$daysLeft}</li>
            <li><b>Project ID:</b> {$projectId}</li>
            <li><b>Team ID:</b> {$teamId}</li>
        </ul>
        <p>Please ensure tasks are updated and any blockers are communicated promptly.</p>
        <p><a href='{$projectUrl}' style='display:inline-block;padding:8px 14px;text-decoration:none;border:1px solid #444;border-radius:6px'>Open Project</a></p>
        <hr>
        <p style='color:#666'>This is an automated message from Project Task Management System.</p>
    </div>";

    $plain = "Hello Team,\n\n"
           . "Reminder: \"{$projectName}\" is due on {$dueDateFmt}.\n"
           . "Days left: {$daysLeft}\n"
           . "Project ID: {$projectId}\n"
           . "Team ID: {$teamId}\n\n"
           . "Open Project: {$projectUrl}\n\n"
           . "- Project Task Management System";

    // Send email via PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_REPLY_TO);

        foreach ($adminEmails as $em) $mail->addAddress($em);
        foreach ($leaderEmails as $em) $mail->addCC($em);
        foreach ($memberEmails as $em) $mail->addBCC($em);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $plain;

        if ($mail->send()) {
            $ins->bind_param('iii', $projectId, $teamId, $daysLeft);
            $ins->execute();
            $countSent++;
        }
    } catch (Exception $e) {
        error_log("Mailer error for project {$projectId}/team {$teamId}/T-{$daysLeft}: " . $mail->ErrorInfo);
    }
}

echo "Reminders sent: {$countSent}\n";

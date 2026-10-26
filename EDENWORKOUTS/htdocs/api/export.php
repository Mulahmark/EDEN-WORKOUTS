<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_login();
$type = get('type', 'members');

/** CSV cell with spreadsheet formula-injection protection. */
function csv_cell($v): string {
    $s = (string) ($v ?? '');
    if (preg_match('/^[=+\-@\t\r]/', $s)) $s = "'" . $s;
    if (preg_match('/[",\n]/', $s)) $s = '"' . str_replace('"', '""', $s) . '"';
    return $s;
}
function csv_out(string $name, array $headers, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $r) fputcsv($out, array_map('csv_cell', $r));
    fclose($out);
    exit;
}
$financial = ['payments', 'expenses', 'subscriptions'];
if (in_array($type, $financial, true) && !in_array($u['role'], ['admin', 'receptionist'], true)) { http_response_code(403); exit('Forbidden'); }
if ($type === 'enquiries' && $u['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

$from = get('from') ?: '2000-01-01';
$to = get('to') ?: date('Y-m-d');

switch ($type) {
    case 'members':
        $rows = db_all("SELECT member_number, first_name, last_name, email, phone, status, joined_at FROM members ORDER BY member_number");
        csv_out('eden-members.csv', ['Member number', 'First name', 'Last name', 'Email', 'Phone', 'Status', 'Joined'],
            array_map(fn($r) => [$r['member_number'], $r['first_name'], $r['last_name'], $r['email'], $r['phone'], $r['status'], fmt_datetime($r['joined_at'])], $rows));
    case 'subscriptions':
        $rows = db_all("SELECT m.member_number, CONCAT(m.first_name,' ',m.last_name) name, p.name plan, ms.start_date, ms.end_date, ms.total_due, ms.paid_total, ms.status
                        FROM memberships ms JOIN members m ON m.id=ms.member_id JOIN membership_plans p ON p.id=ms.plan_id ORDER BY ms.end_date DESC");
        csv_out('eden-subscriptions.csv', ['Member no', 'Member', 'Plan', 'Start', 'Expiry', 'Total due', 'Paid', 'Balance', 'Status'],
            array_map(fn($r) => [$r['member_number'], $r['name'], $r['plan'], $r['start_date'], $r['end_date'], $r['total_due'], $r['paid_total'], balance_of($r['total_due'], $r['paid_total']), $r['status']], $rows));
    case 'payments':
        $rows = db_all("SELECT p.reference, m.member_number, CONCAT(m.first_name,' ',m.last_name) name, p.amount, p.method, p.tx_reference, p.paid_at, p.status
                        FROM payments p JOIN members m ON m.id=p.member_id WHERE DATE(p.paid_at) BETWEEN ? AND ? ORDER BY p.paid_at DESC", [$from, $to]);
        csv_out('eden-payments.csv', ['Reference', 'Member no', 'Member', 'Amount', 'Method', 'Transaction ref', 'Paid at', 'Status'],
            array_map(fn($r) => [$r['reference'], $r['member_number'], $r['name'], $r['amount'], $r['method'], $r['tx_reference'], fmt_datetime($r['paid_at']), $r['status']], $rows));
    case 'attendance':
        $rows = db_all("SELECT a.attend_date, m.member_number, CONCAT(m.first_name,' ',m.last_name) name, a.check_in_at, a.check_out_at, a.is_override, a.override_reason
                        FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.attend_date BETWEEN ? AND ? ORDER BY a.check_in_at DESC", [$from, $to]);
        csv_out('eden-attendance.csv', ['Date', 'Member no', 'Member', 'Check-in', 'Check-out', 'Override', 'Override reason'],
            array_map(fn($r) => [$r['attend_date'], $r['member_number'], $r['name'], fmt_datetime($r['check_in_at']), $r['check_out_at'] ? fmt_datetime($r['check_out_at']) : '', (int) $r['is_override'] ? 'yes' : 'no', $r['override_reason']], $rows));
    case 'expenses':
        $rows = db_all("SELECT e.reference, c.name category, e.description, e.amount, e.expense_date, e.method FROM expenses e JOIN expense_categories c ON c.id=e.category_id
                        WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC", [$from, $to]);
        csv_out('eden-expenses.csv', ['Reference', 'Category', 'Description', 'Amount', 'Date', 'Method'],
            array_map(fn($r) => [$r['reference'], $r['category'], $r['description'], $r['amount'], $r['expense_date'], $r['method']], $rows));
    case 'enquiries':
        $rows = db_all("SELECT created_at, name, email, phone, subject, status, message FROM contact_enquiries ORDER BY created_at DESC");
        csv_out('eden-enquiries.csv', ['Received', 'Name', 'Email', 'Phone', 'Subject', 'Status', 'Message'],
            array_map(fn($r) => [fmt_datetime($r['created_at']), $r['name'], $r['email'], $r['phone'], $r['subject'], $r['status'], $r['message']], $rows));
    case 'trainers':
        $rows = db_all("SELECT t.full_name trainer, t.email, t.phone, t.employment_status, m.member_number, CONCAT(m.first_name,' ',m.last_name) client, a.assigned_at, a.status
                        FROM trainer_assignments a JOIN trainers t ON t.id=a.trainer_id JOIN members m ON m.id=a.member_id ORDER BY t.full_name");
        csv_out('eden-trainer-assignments.csv', ['Trainer', 'Email', 'Phone', 'Employment', 'Client no', 'Client', 'Assigned', 'Assignment'],
            array_map(fn($r) => [$r['trainer'], $r['email'], $r['phone'], $r['employment_status'], $r['member_number'], $r['client'], fmt_datetime($r['assigned_at']), $r['status']], $rows));
    case 'workouts':
        $rows = db_all("SELECT w.name, w.status, m.member_number, CONCAT(m.first_name,' ',m.last_name) member, COALESCE(t.full_name,'—') trainer, w.created_at
                        FROM workout_plans w JOIN members m ON m.id=w.member_id LEFT JOIN trainers t ON t.id=w.trainer_id ORDER BY w.created_at DESC");
        csv_out('eden-workout-plans.csv', ['Plan', 'Status', 'Member no', 'Member', 'Trainer', 'Created'],
            array_map(fn($r) => [$r['name'], $r['status'], $r['member_number'], $r['member'], $r['trainer'], fmt_datetime($r['created_at'])], $rows));
    default:
        http_response_code(400); exit('Unknown export type.');
}

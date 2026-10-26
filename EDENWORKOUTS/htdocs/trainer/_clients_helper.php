<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
function assigned_clients(int $trainerId): array
{
    return db_all("SELECT m.id, m.first_name, m.last_name, m.member_number, a.assigned_at, m.status
                   FROM trainer_assignments a JOIN members m ON m.id = a.member_id
                   WHERE a.trainer_id = ? AND a.status = 'active' ORDER BY m.first_name", [$trainerId]);
}
function guard_client(int $memberId, array $user): void
{
    if ($user['role'] === 'trainer') {
        if (!db_val("SELECT 1 FROM trainer_assignments WHERE trainer_id = ? AND member_id = ? AND status='active'", [(int) $user['trainer_id'], $memberId])) {
            http_response_code(403); exit('That client is not assigned to you.');
        }
    }
}

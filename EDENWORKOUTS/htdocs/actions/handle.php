<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$do = get('do');
$back = $_POST['back'] ?? '';
$safeBack = preg_match('#^[a-z0-9/_-]+\.php(\?[\w=&%.-]*)?$#i', $back) ? $back : 'index.php';

function fail(string $message, string $back): void { err($message); redirect($back); }
function done(string $message, string $back): void { ok($message); redirect($back); }

try {
    // ---------------------------------------------------------------- public
    if ($do === 'contact.submit') {
        csrf_verify();
        if (post('website') !== '') done('Thank you — your message has been received.', 'contact.php');
        $name = post('name'); $email = strtolower(post('email')); $subject = post('subject'); $message = post('message');
        if ($name === '' || $subject === '' || mb_strlen($message) < 10) throw new InvalidArgumentException('Provide your name, a subject and a message of at least 10 characters.');
        if (!valid_email($email)) throw new InvalidArgumentException('Enter a valid email address.');
        $phone = post('phone') !== '' ? normalise_phone(post('phone')) : null;
        $recent = (int) db_val("SELECT COUNT(*) FROM contact_enquiries WHERE ip_address = ? AND created_at > (NOW() - INTERVAL 1 HOUR)", [client_ip()]);
        if ($recent >= 5) throw new InvalidArgumentException('You have sent several messages recently. Please try again later.');
        db_insert("INSERT INTO contact_enquiries (name,email,phone,subject,message,status,ip_address) VALUES (?,?,?,?,?,'new',?)",
            [$name, $email, $phone, $subject, $message, client_ip()]);
        audit('enquiry.created', 'contact_enquiry', null, ['email' => $email]);
        done('Thank you — the EDEN WORKOUTS team will get back to you shortly.', 'contact.php');
    }

    if ($do === 'register.submit') {
        csrf_verify();
        $first = post('first_name'); $last = post('last_name'); $email = strtolower(post('email'));
        $phone = post('phone'); $pass = (string) ($_POST['password'] ?? ''); $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($first === '' || $last === '') throw new InvalidArgumentException('Enter your first and last name.');
        if (!valid_email($email)) throw new InvalidArgumentException('Enter a valid email address.');
        if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) throw new InvalidArgumentException('Password must be at least 8 characters with letters and numbers.');
        if ($pass !== $confirm) throw new InvalidArgumentException('Passwords do not match.');
        $phoneN = normalise_phone($phone);
        if (db_val("SELECT 1 FROM users WHERE email = ?", [$email])) throw new InvalidArgumentException('An account with that email already exists. Please sign in.');
        tx(function () use ($first, $last, $email, $phoneN, $pass) {
            $uid = db_insert("INSERT INTO users (email,password_hash,full_name,phone,role,status) VALUES (?,?,?,?,'member','pending')",
                [$email, password_hash($pass, PASSWORD_DEFAULT), trim($first . ' ' . $last), $phoneN]);
            $num = 'EDN-' . strtoupper(bin2hex(random_bytes(3)));
            db_insert("INSERT INTO members (user_id,member_number,first_name,last_name,email,phone,status) VALUES (?,?,?,?,?,?,'pending')",
                [$uid, $num, $first, $last, $email, $phoneN]);
            audit('member.self_registered', 'user', $uid, ['email' => $email], $uid, $email);
        });
        done('Registration received. Your account is pending activation — the gym will confirm it shortly.', 'login.php');
    }

    // ---------------------------------------------------------------- account
    if ($do === 'password.change') {
        csrf_verify(); $u = require_login();
        $cur = (string) ($_POST['current_password'] ?? ''); $new = (string) ($_POST['password'] ?? ''); $conf = (string) ($_POST['confirm_password'] ?? '');
        $row = db_one("SELECT password_hash FROM users WHERE id = ?", [$u['id']]);
        if (!$row || !password_verify($cur, $row['password_hash'])) fail('Your current password is incorrect.', 'change-password.php');
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) fail('New password must be 8+ characters with letters and numbers.', 'change-password.php');
        if ($new !== $conf) fail('New passwords do not match.', 'change-password.php');
        db_run("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        audit('password.changed', 'user', (int) $u['id'], null, (int) $u['id']);
        done('Password updated.', 'change-password.php');
    }

    if ($do === 'profile.update') {
        csrf_verify(); $u = require_role(['member']); $mid = (int) ($u['member_id'] ?? 0);
        guard_member($mid, $u);
        $phone = normalise_phone(post('phone'));
        db_run("UPDATE members SET phone = ?, date_of_birth = ?, gender = ?, emergency_contact_name = ?, emergency_contact_phone = ? WHERE id = ?",
            [$phone, post('date_of_birth') ?: null, post('gender') ?: null, post('emergency_contact_name') ?: null,
             post('emergency_contact_phone') !== '' ? normalise_phone(post('emergency_contact_phone')) : null, $mid]);
        audit('member.self_updated', 'member', $mid);
        done('Profile updated.', 'member/profile.php');
    }

    if ($do === 'renewal.request') {
        csrf_verify(); $u = require_role(['member']);
        $staff = db_all("SELECT id FROM users WHERE status='active' AND role IN ('admin','receptionist')");
        foreach ($staff as $s) notify((int) $s['id'], 'Membership renewal requested', $u['full_name'] . ' has requested a renewal. Please contact them.', 'general');
        audit('membership.renewal_requested', 'member', (int) $u['member_id']);
        done('Renewal request sent to reception.', 'member/membership.php');
    }

    // ----------------------------------------------------------- admin: members
    if ($do === 'member.create') {
        csrf_verify(); $u = require_role(['admin', 'receptionist']);
        $first = post('first_name'); $last = post('last_name'); $email = strtolower(post('email'));
        if ($first === '' || $last === '' || !valid_email($email)) throw new InvalidArgumentException('Provide a first name, last name and valid email.');
        $phone = normalise_phone(post('phone'));
        if (db_val("SELECT 1 FROM members WHERE email = ?", [$email])) throw new InvalidArgumentException('A member with that email already exists.');
        $pass = (string) ($_POST['password'] ?? '');
        $dest = $u['role'] === 'admin' ? 'admin/members.php' : 'reception/members.php';
        $newId = tx(function () use ($first, $last, $email, $phone, $pass, $u) {
            $uid = null;
            if ($pass !== '') {
                if (strlen($pass) < 8) throw new InvalidArgumentException('Login password must be 8+ characters.');
                if (db_val("SELECT 1 FROM users WHERE email = ?", [$email])) throw new InvalidArgumentException('A user account already uses that email.');
                $uid = db_insert("INSERT INTO users (email,password_hash,full_name,phone,role,status) VALUES (?,?,?,?,'member','active')",
                    [$email, password_hash($pass, PASSWORD_DEFAULT), trim($first . ' ' . $last), $phone]);
            }
            $mid = db_insert("INSERT INTO members (user_id,member_number,first_name,last_name,email,phone,date_of_birth,gender,emergency_contact_name,emergency_contact_phone,status,notes)
                              VALUES (?,?,?,?,?,?,?,?,?,?,'active',?)",
                [$uid, 'EDN-' . strtoupper(bin2hex(random_bytes(3))), $first, $last, $email, $phone,
                 post('date_of_birth') ?: null, post('gender') ?: null, post('emergency_contact_name') ?: null,
                 post('emergency_contact_phone') !== '' ? normalise_phone(post('emergency_contact_phone')) : null, post('notes') ?: null]);
            audit('member.created', 'member', $mid, ['email' => $email, 'login' => (bool) $uid]);
            return $mid;
        });
        done('Member created.', $dest . '?view=' . $newId);
    }

    if ($do === 'member.update') {
        csrf_verify(); $u = require_role(['admin', 'receptionist']);
        $mid = (int) post('member_id'); guard_member($mid, $u);
        $email = strtolower(post('email'));
        if (!valid_email($email)) throw new InvalidArgumentException('Enter a valid email address.');
        if (db_val("SELECT 1 FROM members WHERE email = ? AND id <> ?", [$email, $mid])) throw new InvalidArgumentException('Another member already uses that email.');
        db_run("UPDATE members SET first_name=?,last_name=?,email=?,phone=?,date_of_birth=?,gender=?,emergency_contact_name=?,emergency_contact_phone=?,notes=? WHERE id=?",
            [post('first_name'), post('last_name'), $email, normalise_phone(post('phone')), post('date_of_birth') ?: null, post('gender') ?: null,
             post('emergency_contact_name') ?: null, post('emergency_contact_phone') !== '' ? normalise_phone(post('emergency_contact_phone')) : null,
             post('notes') ?: null, $mid]);
        audit('member.updated', 'member', $mid);
        done('Member updated.', 'admin/member.php?id=' . $mid);
    }

    if ($do === 'member.status') {
        csrf_verify(); $u = require_role(['admin']);
        $mid = (int) post('member_id'); $status = post('status');
        if (!in_array($status, ['active', 'pending', 'inactive', 'suspended'], true)) throw new InvalidArgumentException('Unknown status.');
        $m = db_one("SELECT user_id, status FROM members WHERE id = ?", [$mid]);
        if (!$m) throw new InvalidArgumentException('Member not found.');
        db_run("UPDATE members SET status = ? WHERE id = ?", [$status, $mid]);
        if ($m['user_id']) {
            $acc = $status === 'active' ? 'active' : ($status === 'pending' ? 'pending' : ($status === 'suspended' ? 'suspended' : 'inactive'));
            db_run("UPDATE users SET status = ? WHERE id = ?", [$acc, $m['user_id']]);
            if ($status !== 'active') db_run("UPDATE attendance SET check_out_at = NOW() WHERE member_id = ? AND check_out_at IS NULL", [$mid]);
        }
        audit('member.status_changed', 'member', $mid, ['from' => $m['status'], 'to' => $status]);
        done('Member marked ' . $status . '.', 'admin/member.php?id=' . $mid);
    }

    // --------------------------------------------------------------- plans
    if ($do === 'plan.save') {
        csrf_verify(); $u = require_role(['admin']);
        $id = (int) post('plan_id');
        $unit = post('duration_unit');
        if (!in_array($unit, ['day', 'week', 'month', 'quarter', 'year'], true)) throw new InvalidArgumentException('Invalid duration unit.');
        $vals = [post('name'), post('description') ?: null, max(1, (int) post('duration_value')), $unit,
                 number_format(money_input(post('price')), 2, '.', ''), number_format((float) str_replace(',', '', post('joining_fee') ?: '0'), 2, '.', ''),
                 post('benefits') ?: null, post('access_notes') ?: null];
        if ($vals[0] === '') throw new InvalidArgumentException('Plan name is required.');
        if ($id) {
            $vals[] = $id;
            db_run("UPDATE membership_plans SET name=?,description=?,duration_value=?,duration_unit=?,price=?,joining_fee=?,benefits=?,access_notes=? WHERE id=?", $vals);
            audit('plan.updated', 'membership_plan', $id);
            done('Plan updated. Existing subscriptions keep their agreed price.', 'admin/plans.php');
        }
        $new = db_insert("INSERT INTO membership_plans (name,description,duration_value,duration_unit,price,joining_fee,benefits,access_notes) VALUES (?,?,?,?,?,?,?,?)", $vals);
        audit('plan.created', 'membership_plan', $new);
        done('Plan created and published to the website.', 'admin/plans.php');
    }

    if ($do === 'plan.toggle') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('plan_id');
        db_run("UPDATE membership_plans SET is_active = 1 - is_active WHERE id = ?", [$id]);
        audit('plan.toggled', 'membership_plan', $id);
        done('Plan visibility updated.', 'admin/plans.php');
    }

    // ------------------------------------------------------- subscriptions
    if ($do === 'subscription.create' || $do === 'subscription.renew') {
        csrf_verify(); $u = require_role(['admin', 'receptionist']);
        $input = [
            'member_id' => (int) post('member_id'),
            'plan_id' => (int) post('plan_id'),
            'start_date' => post('start_date') ?: today(),
            'discount' => (float) str_replace(',', '', post('discount') ?: '0'),
            'notes' => post('notes') ?: null,
            'created_by' => (int) $u['id'],
            'allow_duplicate' => post('allow_duplicate') === '1',
        ];
        if ($do === 'subscription.renew') { $input['renewed_from_id'] = (int) post('membership_id'); $input['allow_duplicate'] = true; }
        $sub = subscription_create($input);
        audit($do === 'subscription.renew' ? 'membership.renewed' : 'membership.created', 'membership', (int) $sub['id'], ['member_id' => $input['member_id']]);
        notify_member($input['member_id'], 'Membership activated', 'Valid until ' . fmt_date($sub['end_date']) . '.', 'general', 'membership', (int) $sub['id']);
        done('Subscription saved — expires ' . fmt_date($sub['end_date']) . '.', $u['role'] === 'admin' ? 'admin/memberships.php' : 'reception/memberships.php');
    }

    if ($do === 'subscription.status') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('membership_id'); $status = post('status');
        if (!in_array($status, ['pending', 'active', 'suspended', 'cancelled'], true)) throw new InvalidArgumentException('Unknown status.');
        db_run("UPDATE memberships SET status = ? WHERE id = ?", [$status, $id]);
        audit('membership.status_changed', 'membership', $id, ['to' => $status]);
        done('Subscription marked ' . $status . '.', 'admin/memberships.php');
    }

    // ------------------------------------------------------------ payments
    if ($do === 'payment.record') {
        csrf_verify(); $u = require_role(['admin', 'receptionist']);
        $mid = (int) post('membership_id');
        $p = payment_record([
            'member_id' => (int) post('member_id'),
            'membership_id' => $mid > 0 ? $mid : null,
            'amount' => money_input(post('amount')),
            'method' => post('method') ?: 'cash',
            'tx_reference' => post('tx_reference') ?: null,
            'paid_at' => post('paid_at') ? str_replace('T', ' ', post('paid_at')) . ':00' : date('Y-m-d H:i:s'),
            'notes' => post('notes') ?: null,
            'received_by' => (int) $u['id'],
        ]);
        audit('payment.recorded', 'payment', (int) $p['id'], ['reference' => $p['reference'], 'amount' => $p['amount'], 'channel' => 'manual']);
        notify_member((int) $p['member_id'], 'Payment received', 'Payment ' . $p['reference'] . ' of ' . money($p['amount']) . ' recorded.', 'payment_recorded', 'payment', (int) $p['id']);
        done('Payment ' . $p['reference'] . ' recorded. Receipt ready.', $u['role'] === 'admin' ? 'admin/payments.php' : 'reception/payments.php');
    }

    if ($do === 'payment.reverse') {
        csrf_verify(); $u = require_role(['admin']);
        $p = payment_reverse((int) post('payment_id'), post('reason'), post('type') ?: 'reversal', (int) $u['id']);
        audit('payment.reversed', 'payment', (int) $p['id'], ['reference' => $p['reference'], 'reason' => post('reason')]);
        done('Payment reversed and balance recalculated.', 'admin/payments.php');
    }

    // ---------------------------------------------------------- attendance
    if ($do === 'attendance.checkin') {
        csrf_verify(); $u = require_role(['admin', 'receptionist']);
        $reason = $u['role'] === 'admin' ? (post('override_reason') ?: null) : null;
        $r = attendance_checkin((int) post('member_id'), (int) $u['id'], $reason);
        audit('attendance.checkin', 'member', (int) post('member_id'), ['override' => (bool) $r['attendance']['is_override'], 'reason' => $reason]);
        done($r['member']['first_name'] . ' checked in.', $u['role'] === 'admin' ? 'admin/attendance.php' : 'reception/check-in.php');
    }

    if ($do === 'attendance.checkout') {
        csrf_verify(); $u = require_role(['admin', 'receptionist']);
        if (!attendance_checkout((int) post('member_id'))) throw new InvalidArgumentException('That member has no open visit.');
        audit('attendance.checkout', 'member', (int) post('member_id'));
        done('Check-out recorded.', $u['role'] === 'admin' ? 'admin/attendance.php' : 'reception/check-in.php');
    }

    // ------------------------------------------------------------- trainers
    if ($do === 'trainer.save') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('trainer_id');
        $fields = [post('full_name'), strtolower(post('email')), normalise_phone(post('phone')), post('bio') ?: null, post('specializations') ?: null,
                   post('certifications') ?: null, (int) (post('experience_years') ?: 0), post('availability_notes') ?: null,
                   in_array(post('employment_status'), ['active', 'on_leave', 'inactive'], true) ? post('employment_status') : 'active',
                   post('is_public') === '1' ? 1 : 0];
        if ($fields[0] === '' || !valid_email($fields[1])) throw new InvalidArgumentException('Provide the trainer name and a valid email.');
        if ($id) {
            db_run("UPDATE trainers SET full_name=?,email=?,phone=?,bio=?,specializations=?,certifications=?,experience_years=?,availability_notes=?,employment_status=?,is_public=? WHERE id=?", array_merge($fields, [$id]));
            audit('trainer.updated', 'trainer', $id);
            done('Trainer profile updated.', 'admin/trainers.php');
        }
        $pass = (string) ($_POST['password'] ?? '');
        if (strlen($pass) < 8) throw new InvalidArgumentException('Set an initial password of at least 8 characters.');
        if (db_val("SELECT 1 FROM users WHERE email = ?", [$fields[1]])) throw new InvalidArgumentException('A user account already uses that email.');
        $newId = tx(function () use ($fields, $pass) {
            $uid = db_insert("INSERT INTO users (email,password_hash,full_name,phone,role,status,must_change_password) VALUES (?,?,?,?,'trainer','active',1)",
                [$fields[1], password_hash($pass, PASSWORD_DEFAULT), $fields[0], $fields[2]]);
            $tid = db_insert("INSERT INTO trainers (user_id,full_name,email,phone,bio,specializations,certifications,experience_years,availability_notes,employment_status,is_public) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                array_merge([$uid], $fields));
            audit('trainer.created', 'trainer', $tid, ['email' => $fields[1]]);
            return $tid;
        });
        done('Trainer created with a sign-in account.', 'admin/trainers.php?id=' . $newId);
    }

    if ($do === 'trainer.status') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('trainer_id'); $status = post('employment_status');
        if (!in_array($status, ['active', 'on_leave', 'inactive'], true)) throw new InvalidArgumentException('Unknown status.');
        $t = db_one("SELECT user_id FROM trainers WHERE id = ?", [$id]);
        db_run("UPDATE trainers SET employment_status = ? WHERE id = ?", [$status, $id]);
        if ($t && $t['user_id']) db_run("UPDATE users SET status = ? WHERE id = ?", [$status === 'active' ? 'active' : 'suspended', $t['user_id']]);
        audit('trainer.status_changed', 'trainer', $id, ['to' => $status]);
        done('Trainer marked ' . $status . '.', 'admin/trainers.php');
    }

    if ($do === 'assignment.create') {
        csrf_verify(); require_role(['admin']);
        $tid = (int) post('trainer_id'); $mid = (int) post('member_id');
        if (db_val("SELECT 1 FROM trainer_assignments WHERE trainer_id=? AND member_id=? AND status='active'", [$tid, $mid])) throw new InvalidArgumentException('That member is already assigned to this trainer.');
        tx(function () use ($tid, $mid) {
            db_run("UPDATE trainer_assignments SET status='ended', ended_at=NOW() WHERE member_id=? AND status='active'", [$mid]);
            db_insert("INSERT INTO trainer_assignments (trainer_id,member_id,assigned_by,notes) VALUES (?,?,?,?)", [$tid, $mid, (int) current_user()['id'], post('notes') ?: null]);
        });
        audit('assignment.created', 'member', $mid, ['trainer_id' => $tid]);
        done('Client assigned (previous assignments kept in history).', 'admin/trainers.php');
    }

    if ($do === 'assignment.end') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('assignment_id');
        db_run("UPDATE trainer_assignments SET status='ended', ended_at=NOW() WHERE id = ?", [$id]);
        audit('assignment.ended', 'trainer_assignment', $id);
        done('Assignment ended.', 'admin/trainers.php');
    }

    if ($do === 'availability.save') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $tid = $u['role'] === 'trainer' ? (int) $u['trainer_id'] : (int) post('trainer_id');
        $start = post('start_time'); $end = post('end_time');
        if ($start === '' || $end === '' || $end <= $start) throw new InvalidArgumentException('End time must be after start time.');
        db_insert("INSERT INTO trainer_availability (trainer_id,day_of_week,start_time,end_time,note) VALUES (?,?,?,?,?)",
            [$tid, (int) post('day_of_week'), $start, $end, post('note') ?: null]);
        audit('availability.created', 'trainer', $tid);
        done('Availability slot added.', $u['role'] === 'trainer' ? 'trainer/appointments.php' : 'admin/trainers.php');
    }

    // ------------------------------------------------------------ exercises
    if ($do === 'exercise.save') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $id = (int) post('exercise_id');
        $vals = [post('name'), post('description') ?: null, post('muscle_group') ?: null, post('category') ?: null, post('equipment') ?: null,
                 in_array(post('difficulty'), ['beginner', 'intermediate', 'advanced'], true) ? post('difficulty') : 'beginner',
                 post('instructions') ?: null, post('media_url') ?: null];
        if ($vals[0] === '') throw new InvalidArgumentException('Exercise name is required.');
        $dest = $u['role'] === 'admin' ? 'admin/exercises.php' : 'trainer/workouts.php';
        if ($id) {
            db_run("UPDATE exercises SET name=?,description=?,muscle_group=?,category=?,equipment=?,difficulty=?,instructions=?,media_url=? WHERE id=?", array_merge($vals, [$id]));
            audit('exercise.updated', 'exercise', $id);
            done('Exercise updated.', $dest);
        }
        $new = db_insert("INSERT INTO exercises (name,description,muscle_group,category,equipment,difficulty,instructions,media_url) VALUES (?,?,?,?,?,?,?,?)", $vals);
        audit('exercise.created', 'exercise', $new);
        done('Exercise added to the library.', $dest);
    }

    if ($do === 'exercise.toggle') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('exercise_id');
        db_run("UPDATE exercises SET is_active = 1 - is_active WHERE id = ?", [$id]);
        done('Exercise visibility updated.', 'admin/exercises.php');
    }

    // -------------------------------------------------------- workout plans
    if ($do === 'workout.save') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $id = (int) post('plan_id'); $mid = (int) post('member_id');
        guard_member($mid, $u);
        $tid = $u['role'] === 'trainer' ? (int) $u['trainer_id'] : ((int) post('trainer_id') ?: null);
        $status = in_array(post('status'), ['draft', 'active', 'completed', 'archived'], true) ? post('status') : 'draft';
        $vals = [post('name'), post('description') ?: null, $mid, $tid, post('start_date') ?: null, post('end_date') ?: null,
                 post('weekly_schedule') ?: null, post('goals') ?: null, $status, post('notes') ?: null];
        if ($vals[0] === '') throw new InvalidArgumentException('Plan name is required.');
        if ($id) {
            db_run("UPDATE workout_plans SET name=?,description=?,member_id=?,trainer_id=?,start_date=?,end_date=?,weekly_schedule=?,goals=?,status=?,notes=? WHERE id=?", array_merge($vals, [$id]));
            audit('workout_plan.updated', 'workout_plan', $id);
            done('Workout plan updated.', 'admin/workout.php?id=' . $id);
        }
        $new = db_insert("INSERT INTO workout_plans (name,description,member_id,trainer_id,start_date,end_date,weekly_schedule,goals,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            array_merge($vals, [(int) $u['id']]));
        audit('workout_plan.created', 'workout_plan', $new, ['member_id' => $mid]);
        if ($status === 'active') notify_member($mid, 'New workout plan assigned', 'Your coach published "' . $vals[0] . '".', 'workout_assigned', 'workout_plan', $new);
        done('Workout plan created. Add exercises below.', 'admin/workout.php?id=' . $new);
    }

    if ($do === 'workout.exercise_add') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $pid = (int) post('plan_id');
        $plan = db_one("SELECT member_id FROM workout_plans WHERE id = ?", [$pid]);
        if (!$plan) throw new InvalidArgumentException('Plan not found.');
        guard_member((int) $plan['member_id'], $u);
        $order = (int) db_val("SELECT COALESCE(MAX(order_index),0) FROM workout_plan_exercises WHERE plan_id = ?", [$pid]) + 1;
        db_insert("INSERT INTO workout_plan_exercises (plan_id,exercise_id,training_day,sets,reps,target_weight,rest_seconds,order_index,instructions) VALUES (?,?,?,?,?,?,?,?,?)",
            [$pid, (int) post('exercise_id'), post('training_day') ?: 'Day 1', max(1, (int) post('sets')), post('reps') ?: '10',
             post('target_weight') !== '' ? (float) post('target_weight') : null, max(0, (int) post('rest_seconds')), $order, post('instructions') ?: null]);
        audit('workout_plan.exercise_added', 'workout_plan', $pid);
        done('Exercise added to the plan.', 'admin/workout.php?id=' . $pid);
    }

    if ($do === 'workout.exercise_remove') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $pid = (int) post('plan_id'); $line = (int) post('line_id');
        db_run("DELETE FROM workout_plan_exercises WHERE id = ? AND plan_id = ?", [$line, $pid]);
        done('Exercise removed.', 'admin/workout.php?id=' . $pid);
    }

    if ($do === 'workout.status') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $pid = (int) post('plan_id'); $status = post('status');
        if (!in_array($status, ['draft', 'active', 'completed', 'archived'], true)) throw new InvalidArgumentException('Unknown status.');
        $plan = db_one("SELECT member_id, name FROM workout_plans WHERE id = ?", [$pid]);
        guard_member((int) $plan['member_id'], $u);
        db_run("UPDATE workout_plans SET status = ? WHERE id = ?", [$status, $pid]);
        if ($status === 'active') notify_member((int) $plan['member_id'], 'Workout plan assigned', 'Your coach published "' . $plan['name'] . '".', 'workout_assigned', 'workout_plan', $pid);
        audit('workout_plan.status_changed', 'workout_plan', $pid, ['to' => $status]);
        done('Plan marked ' . $status . '.', ($u['role'] === 'admin' ? 'admin/workout.php?id=' : 'trainer/workout.php?id=') . $pid);
    }

    if ($do === 'workout.duplicate') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $pid = (int) post('plan_id');
        $p = db_one("SELECT * FROM workout_plans WHERE id = ?", [$pid]);
        if (!$p) throw new InvalidArgumentException('Plan not found.');
        guard_member((int) $p['member_id'], $u);
        $new = tx(function () use ($p, $pid, $u) {
            $nid = db_insert("INSERT INTO workout_plans (name,description,member_id,trainer_id,start_date,end_date,weekly_schedule,goals,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,'draft',?,?)",
                [$p['name'] . ' (copy)', $p['description'], $p['member_id'], $p['trainer_id'], $p['start_date'], $p['end_date'], $p['weekly_schedule'], $p['goals'], $p['notes'], (int) $u['id']]);
            $lines = db_all("SELECT * FROM workout_plan_exercises WHERE plan_id = ?", [$pid]);
            foreach ($lines as $l) {
                db_insert("INSERT INTO workout_plan_exercises (plan_id,exercise_id,training_day,sets,reps,target_weight,rest_seconds,order_index,instructions) VALUES (?,?,?,?,?,?,?,?,?)",
                    [$nid, $l['exercise_id'], $l['training_day'], $l['sets'], $l['reps'], $l['target_weight'], $l['rest_seconds'], $l['order_index'], $l['instructions']]);
            }
            return $nid;
        });
        audit('workout_plan.duplicated', 'workout_plan', $new);
        done('Plan duplicated as a draft.', 'admin/workout.php?id=' . $new);
    }

    if ($do === 'session.log') {
        csrf_verify(); $u = require_role(['admin', 'trainer', 'member']);
        $mid = $u['role'] === 'member' ? (int) $u['member_id'] : (int) post('member_id');
        guard_member($mid, $u);
        $ids = $_POST['exercise_id'] ?? [];
        if (!is_array($ids) || count($ids) === 0) throw new InvalidArgumentException('Add at least one exercise.');
        $sid = tx(function () use ($mid, $u, $ids) {
            $id = db_insert("INSERT INTO workout_sessions (member_id,plan_id,trainer_id,performed_at,duration_minutes,completion,notes) VALUES (?,?,?,?,?,?,?)",
                [$mid, (int) post('plan_id') ?: null, $u['role'] === 'trainer' ? (int) $u['trainer_id'] : null,
                 post('performed_at') ? str_replace('T', ' ', post('performed_at')) . ':00' : date('Y-m-d H:i:s'),
                 (int) post('duration_minutes') ?: null, in_array(post('completion'), ['completed', 'partial', 'missed'], true) ? post('completion') : 'completed',
                 post('notes') ?: null]);
            foreach ($ids as $i => $eid) {
                if (!$eid) continue;
                db_insert("INSERT INTO workout_session_exercises (session_id,exercise_id,sets_completed,reps_completed,weight_used,notes) VALUES (?,?,?,?,?,?)",
                    [$id, (int) $eid, max(0, (int) ($_POST['sets'][$i] ?? 0)), max(0, (int) ($_POST['reps'][$i] ?? 0)),
                     ($_POST['weight'][$i] ?? '') !== '' ? (float) $_POST['weight'][$i] : null, ($_POST['line_notes'][$i] ?? '') ?: null]);
            }
            return $id;
        });
        audit('workout_session.logged', 'workout_session', $sid, ['member_id' => $mid]);
        done('Workout session logged.', $u['role'] === 'member' ? 'member/sessions.php' : ($u['role'] === 'trainer' ? 'trainer/sessions.php' : 'admin/progress.php'));
    }

    // -------------------------------------------------------------- fitness
    if ($do === 'measurement.save') {
        csrf_verify(); $u = require_role(['admin', 'trainer', 'member']);
        $mid = $u['role'] === 'member' ? (int) $u['member_id'] : (int) post('member_id');
        guard_member($mid, $u);
        $date = post('measured_at') ?: today();
        if ($date > today()) throw new InvalidArgumentException('Measurement date cannot be in the future.');
        $num = fn(string $k) => post($k) !== '' ? round((float) post($k), 2) : null;
        db_insert("INSERT INTO fitness_measurements (member_id,measured_at,weight_kg,height_cm,body_fat_pct,waist_cm,chest_cm,arms_cm,thighs_cm,notes,recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$mid, $date, $num('weight_kg'), $num('height_cm'), $num('body_fat_pct'), $num('waist_cm'), $num('chest_cm'), $num('arms_cm'), $num('thighs_cm'), post('notes') ?: null, (int) $u['id']]);
        audit('measurement.recorded', 'member', $mid, ['date' => $date]);
        done('Measurement recorded.', $u['role'] === 'member' ? 'member/progress.php' : ($u['role'] === 'trainer' ? 'trainer/progress.php?member_id=' . $mid : 'admin/progress.php?member_id=' . $mid));
    }

    if ($do === 'goal.save') {
        csrf_verify(); $u = require_role(['admin', 'trainer', 'member']);
        $mid = $u['role'] === 'member' ? (int) $u['member_id'] : (int) post('member_id');
        guard_member($mid, $u);
        $id = (int) post('goal_id');
        $num = fn(string $k) => post($k) !== '' ? round((float) post($k), 2) : null;
        $vals = [post('title'), post('description') ?: null, post('goal_type') ?: 'general', post('start_date') ?: null, post('target_date') ?: null,
                 $num('start_value'), $num('target_value'), $num('current_value'), post('unit') ?: null,
                 in_array(post('status'), ['active', 'completed', 'paused', 'abandoned'], true) ? post('status') : 'active', post('progress_notes') ?: null];
        if ($vals[0] === '') throw new InvalidArgumentException('Give the goal a title.');
        if ($id) {
            db_run("UPDATE fitness_goals SET title=?,description=?,goal_type=?,start_date=?,target_date=?,start_value=?,target_value=?,current_value=?,unit=?,status=?,progress_notes=? WHERE id=?", array_merge($vals, [$id]));
            audit('goal.updated', 'fitness_goal', $id);
            done('Goal updated.', 'member/goals.php');
        }
        $new = db_insert("INSERT INTO fitness_goals (title,description,goal_type,start_date,target_date,start_value,target_value,current_value,unit,status,progress_notes,member_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            array_merge($vals, [$mid, (int) $u['id']]));
        audit('goal.created', 'fitness_goal', $new);
        done('Goal created.', $u['role'] === 'member' ? 'member/goals.php' : ($u['role'] === 'trainer' ? 'trainer/progress.php?member_id=' . $mid : 'admin/progress.php?member_id=' . $mid));
    }

    if ($do === 'goal.progress') {
        csrf_verify(); $u = require_role(['admin', 'trainer', 'member']);
        $id = (int) post('goal_id');
        $g = db_one("SELECT member_id, status FROM fitness_goals WHERE id = ?", [$id]);
        if (!$g) throw new InvalidArgumentException('Goal not found.');
        guard_member((int) $g['member_id'], $u);
        $status = in_array(post('status'), ['active', 'completed', 'paused', 'abandoned'], true) ? post('status') : $g['status'];
        db_run("UPDATE fitness_goals SET current_value = ?, status = ?, progress_notes = ? WHERE id = ?",
            [post('current_value') !== '' ? (float) post('current_value') : null, $status, post('progress_notes') ?: null, $id]);
        if ($status === 'completed' && $g['status'] !== 'completed') audit('goal.completed', 'fitness_goal', $id);
        done('Goal progress saved.', 'member/goals.php');
    }

    // ---------------------------------------------------------- appointments
    if ($do === 'appointment.save') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $tid = $u['role'] === 'trainer' ? (int) $u['trainer_id'] : (int) post('trainer_id');
        $when = post('scheduled_at');
        if ($when === '') throw new InvalidArgumentException('Choose a date and time.');
        $whenSql = str_replace('T', ' ', $when) . ':00';
        $dur = min(240, max(15, (int) post('duration_minutes')));
        $mid = (int) post('member_id') ?: null;
        if ($mid) guard_member($mid, $u);
        $clash = db_val("SELECT 1 FROM training_appointments WHERE trainer_id = ? AND status <> 'cancelled'
                         AND scheduled_at < DATE_ADD(?, INTERVAL ? MINUTE) AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?
                         LIMIT 1", [$tid, $whenSql, $dur, $whenSql]);
        if ($clash) throw new InvalidArgumentException('That trainer already has an appointment overlapping this slot.');
        $id = db_insert("INSERT INTO training_appointments (trainer_id,member_id,scheduled_at,duration_minutes,status,notes,created_by) VALUES (?,?,?,?,?,?,?)",
            [$tid, $mid, $whenSql, $dur, in_array(post('status'), ['scheduled', 'completed', 'cancelled', 'no_show'], true) ? post('status') : 'scheduled', post('notes') ?: null, (int) $u['id']]);
        if ($mid) notify_member($mid, 'Training appointment booked', 'Session on ' . fmt_datetime($whenSql) . ' (' . $dur . ' min).', 'appointment_reminder', 'appointment', $id);
        audit('appointment.created', 'appointment', $id, ['trainer_id' => $tid]);
        done('Appointment scheduled.', $u['role'] === 'admin' ? 'admin/appointments.php' : 'trainer/appointments.php');
    }

    if ($do === 'appointment.status') {
        csrf_verify(); $u = require_role(['admin', 'trainer']);
        $id = (int) post('appointment_id'); $status = post('status');
        if (!in_array($status, ['scheduled', 'completed', 'cancelled', 'no_show'], true)) throw new InvalidArgumentException('Unknown status.');
        $a = db_one("SELECT member_id FROM training_appointments WHERE id = ?", [$id]);
        db_run("UPDATE training_appointments SET status = ? WHERE id = ?", [$status, $id]);
        if ($a && $a['member_id']) notify_member((int) $a['member_id'], 'Appointment ' . str_replace('_', ' ', $status), 'Your training appointment was marked ' . str_replace('_', ' ', $status) . '.', 'appointment_changed', 'appointment', $id);
        audit('appointment.status_changed', 'appointment', $id, ['to' => $status]);
        done('Appointment marked ' . str_replace('_', ' ', $status) . '.', $u['role'] === 'admin' ? 'admin/appointments.php' : 'trainer/appointments.php');
    }

    // -------------------------------------------------------------- expenses
    if ($do === 'expense.save') {
        csrf_verify(); require_role(['admin']);
        $id = db_insert("INSERT INTO expenses (reference,category_id,description,amount,expense_date,method,recorded_by,notes) VALUES (?,?,?,?,?,?,?,?)",
            [ref_code('EXP'), (int) post('category_id'), post('description'), number_format(money_input(post('amount')), 2, '.', ''),
             post('expense_date') ?: today(), in_array(post('method'), ['cash', 'mpesa', 'bank', 'card', 'other'], true) ? post('method') : 'cash',
             (int) current_user()['id'], post('notes') ?: null]);
        audit('expense.recorded', 'expense', $id, ['amount' => post('amount')]);
        done('Expense recorded.', 'admin/expenses.php');
    }

    if ($do === 'expense.delete') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('expense_id');
        $x = db_one("SELECT reference, amount FROM expenses WHERE id = ?", [$id]);
        db_run("DELETE FROM expenses WHERE id = ?", [$id]);
        audit('expense.deleted', 'expense', $id, ['reference' => $x['reference'] ?? null]);
        done('Expense removed (audit entry kept).', 'admin/expenses.php');
    }

    if ($do === 'category.save') {
        csrf_verify(); require_role(['admin']);
        $name = post('name');
        if ($name === '') throw new InvalidArgumentException('Category name is required.');
        if (db_val("SELECT 1 FROM expense_categories WHERE name = ?", [$name])) throw new InvalidArgumentException('That category already exists.');
        db_insert("INSERT INTO expense_categories (name) VALUES (?)", [$name]);
        done('Category added.', 'admin/expenses.php');
    }

    // ----------------------------------------------------------------- staff
    if ($do === 'staff.create') {
        csrf_verify(); $u = require_role(['admin']);
        $email = strtolower(post('email')); $role = post('role'); $pass = (string) ($_POST['password'] ?? '');
        if (!valid_email($email)) throw new InvalidArgumentException('Enter a valid email address.');
        if (!in_array($role, ['admin', 'receptionist', 'trainer'], true)) throw new InvalidArgumentException('Choose a valid role.');
        if (strlen($pass) < 8) throw new InvalidArgumentException('Password must be at least 8 characters.');
        if (db_val("SELECT 1 FROM users WHERE email = ?", [$email])) throw new InvalidArgumentException('A user account already uses that email.');
        $newId = db_insert("INSERT INTO users (email,password_hash,full_name,phone,role,status,must_change_password) VALUES (?,?,?,?,?,'active',1)",
            [$email, password_hash($pass, PASSWORD_DEFAULT), post('full_name'), post('phone') ? normalise_phone(post('phone')) : null, $role]);
        audit('staff.created', 'user', $newId, ['role' => $role]);
        done('Staff account created.', 'admin/staff.php?reset=' . $newId);
    }

    if ($do === 'staff.status' || $do === 'staff.role') {
        csrf_verify(); $u = require_role(['admin']);
        $id = (int) post('user_id');
        $target = db_one("SELECT role, status FROM users WHERE id = ?", [$id]);
        if (!$target) throw new InvalidArgumentException('User not found.');
        if ($do === 'staff.status') {
            $status = post('status');
            if (!in_array($status, ['active', 'suspended', 'inactive'], true)) throw new InvalidArgumentException('Unknown status.');
            if ($id === (int) $u['id']) throw new InvalidArgumentException('You cannot change your own status here.');
            if ($target['role'] === 'admin' && $status !== 'active') {
                $others = (int) db_val("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id <> ?", [$id]);
                if ($others === 0) throw new InvalidArgumentException('At least one active administrator must remain.');
            }
            db_run("UPDATE users SET status = ? WHERE id = ?", [$status, $id]);
            audit('staff.status_changed', 'user', $id, ['to' => $status]);
            done('Account marked ' . $status . '.', 'admin/staff.php');
        }
        $role = post('role');
        if (!in_array($role, ['admin', 'receptionist', 'trainer', 'member'], true)) throw new InvalidArgumentException('Unknown role.');
        if ($target['role'] === 'admin' && $role !== 'admin') {
            $others = (int) db_val("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id <> ?", [$id]);
            if ($others === 0) throw new InvalidArgumentException('At least one active administrator must remain.');
        }
        db_run("UPDATE users SET role = ? WHERE id = ?", [$role, $id]);
        audit('staff.role_changed', 'user', $id, ['from' => $target['role'], 'to' => $role]);
        done('Role updated to ' . $role . '.', 'admin/staff.php');
    }

    if ($do === 'staff.reset') {
        csrf_verify(); $u = require_role(['admin']);
        $id = (int) post('user_id'); $pass = (string) ($_POST['password'] ?? '');
        if (strlen($pass) < 8) throw new InvalidArgumentException('Password must be at least 8 characters.');
        db_run("UPDATE users SET password_hash = ?, must_change_password = 1, failed_login_count = 0, locked_until = NULL WHERE id = ?",
            [password_hash($pass, PASSWORD_DEFAULT), $id]);
        audit('staff.password_reset', 'user', $id);
        done('Password reset. Ask the user to change it after signing in.', 'admin/staff.php');
    }

    // -------------------------------------------------------------- settings
    if ($do === 'settings.save') {
        csrf_verify(); $u = require_role(['admin']);
        db_run("UPDATE gym_settings SET gym_name=?,tagline=?,address=?,phone=?,email=?,currency=?,timezone=?,reminder_days_7=?,reminder_days_30=?,receipt_footer=?,tax_label=?,tax_percent=? WHERE id=1",
            [post('gym_name') ?: 'EDEN WORKOUTS', post('tagline'), post('address') ?: null, post('phone') ?: null, post('email') ?: null,
             post('currency') ?: 'KES', post('timezone') ?: 'Africa/Nairobi', max(1, (int) post('reminder_days_7')), max(1, (int) post('reminder_days_30')),
             post('receipt_footer') ?: null, post('tax_label') ?: null, round((float) (post('tax_percent') ?: 0), 2)]);
        audit('settings.updated', 'gym_settings', 1);
        done('Gym settings saved.', 'admin/settings.php');
    }

    if ($do === 'website.save') {
        csrf_verify(); $u = require_role(['admin']);
        db_run("UPDATE gym_settings SET about_text=?,mission_text=?,facebook_url=?,instagram_url=?,x_url=?,tiktok_url=?,whatsapp_number=? WHERE id=1",
            [post('about_text') ?: null, post('mission_text') ?: null, post('facebook_url') ?: null, post('instagram_url') ?: null,
             post('x_url') ?: null, post('tiktok_url') ?: null, post('whatsapp_number') ?: null]);
        audit('website_content.updated', 'gym_settings', 1);
        done('Public website content updated.', 'admin/website.php');
    }

    if ($do === 'announcement.send') {
        csrf_verify(); $u = require_role(['admin']);
        $title = post('title'); $message = post('message'); $aud = post('audience') ?: 'all';
        if ($title === '' || mb_strlen($message) < 5) throw new InvalidArgumentException('Provide a title and message.');
        $sql = "SELECT id FROM users WHERE status='active'";
        if ($aud === 'members') $sql .= " AND role='member'";
        if ($aud === 'staff') $sql .= " AND role IN ('admin','receptionist','trainer')";
        $rows = db_all($sql);
        foreach ($rows as $r) notify((int) $r['id'], $title, $message, 'announcement', 'announcement', null, 'ann-' . md5($title . $message . $r['id']));
        audit('announcement.sent', null, null, ['audience' => $aud, 'recipients' => count($rows)]);
        done('Announcement delivered to ' . count($rows) . ' in-app inbox(es). No email or SMS is sent.', 'admin/notifications.php');
    }

    if ($do === 'notifications.readall') {
        csrf_verify(); $u = require_login();
        db_run("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$u['id']]);
        done('All notifications marked as read.', $safeBack);
    }

    if ($do === 'enquiry.status') {
        csrf_verify(); require_role(['admin']);
        $id = (int) post('enquiry_id'); $status = post('status');
        if (!in_array($status, ['new', 'in_progress', 'resolved', 'spam'], true)) throw new InvalidArgumentException('Unknown status.');
        db_run("UPDATE contact_enquiries SET status = ?, handled_by = ?, handled_at = NOW() WHERE id = ?", [$status, (int) current_user()['id'], $id]);
        audit('enquiry.status_changed', 'contact_enquiry', $id, ['to' => $status]);
        done('Enquiry marked ' . str_replace('_', ' ', $status) . '.', 'admin/enquiries.php');
    }

    throw new RuntimeException('Unknown action.');
} catch (InvalidArgumentException | RuntimeException $e) {
    err($e->getMessage());
    redirect($safeBack);
} catch (Throwable $e) {
    error_log('[EDEN] action ' . $do . ' failed: ' . $e->getMessage());
    err('Something went wrong. The technical details were written to the server log.');
    redirect($safeBack);
}

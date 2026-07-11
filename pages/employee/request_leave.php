<?php
requireLogin();
$pageTitle = 'Request Leave | HRMS Core';
$currentPage = 'request_leave';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$emp = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
$emp->execute([$userId]);
$emp = $emp->fetch();
$empId = $emp['id'] ?? 0;

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $type = $_POST['type'] ?? '';
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (!$type || !$start || !$end) {
        $error = 'Please fill in all required fields.';
    } elseif ($start > $end) {
        $error = 'End date must be after start date.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO leaves (employee_id, type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$empId, $type, $start, $end, $reason]);
        $msg = 'Leave request submitted successfully!';
    }
}

// leave used this year
$leaveUsed = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE employee_id = ? AND status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())");
$leaveUsed->execute([$empId]);
$leaveUsed = $leaveUsed->fetchColumn();
$leaveRemaining = max(0, 15 - $leaveUsed);

// pending leaves
$pending = $pdo->prepare("SELECT * FROM leaves WHERE employee_id = ? AND status = 'pending' ORDER BY created_at DESC");
$pending->execute([$empId]);
$pendingLeaves = $pending->fetchAll();
?>
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <a href="<?= BASE_URL ?>/employee/dashboard" class="text-sm text-primary font-bold hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Back to Dashboard
        </a>
        <h2 class="font-headline-lg text-headline-lg text-text-heading mt-2">Request Leave</h2>
        <p class="text-body-md text-text-body">Submit a leave request for approval.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <?php if ($msg): ?>
                <div class="p-4 bg-green-100 text-green-700 rounded-lg font-semibold mb-6"><?= h($msg) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="p-4 bg-red-100 text-red-700 rounded-lg font-semibold mb-6"><?= h($error) ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl border border-border-subtle soft-elevated p-6">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-on-surface mb-2">Leave Type *</label>
                            <select name="type" required
                                class="w-full px-4 py-2.5 rounded-lg border border-border-subtle bg-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                <option value="">Select type</option>
                                <option value="annual">Annual</option>
                                <option value="sick">Sick</option>
                                <option value="personal">Personal</option>
                                <option value="maternity">Maternity</option>
                                <option value="paternity">Paternity</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface mb-2">Remaining Leave</label>
                            <div class="w-full px-4 py-2.5 rounded-lg bg-surface-muted border border-border-subtle text-sm flex items-center gap-2">
                                <span class="text-primary font-bold text-lg"><?= $leaveRemaining ?></span>
                                <span class="text-secondary">of 15 days</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface mb-2">Start Date *</label>
                            <input type="date" name="start_date" required
                                class="w-full px-4 py-2.5 rounded-lg border border-border-subtle bg-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-on-surface mb-2">End Date *</label>
                            <input type="date" name="end_date" required
                                class="w-full px-4 py-2.5 rounded-lg border border-border-subtle bg-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-on-surface mb-2">Reason</label>
                        <textarea name="reason" rows="4"
                            class="w-full px-4 py-2.5 rounded-lg border border-border-subtle bg-white text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none resize-none"
                            placeholder="Tell us why you need this leave..."></textarea>
                    </div>
                    <button type="submit" name="submit"
                        class="w-full px-6 py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all">
                        Submit Leave Request
                    </button>
                </form>
            </div>
        </div>
        <div>
            <div class="bg-white rounded-2xl border border-border-subtle soft-elevated p-6">
                <h4 class="font-bold text-on-surface mb-4">Your Leave Balance</h4>
                <div class="text-center mb-4">
                    <span class="text-display-lg font-bold text-primary"><?= $leaveRemaining ?></span>
                    <p class="text-sm text-secondary">Days Remaining</p>
                </div>
                <div class="w-full bg-surface-muted h-3 rounded-full overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: <?= min(100, ($leaveUsed / 15) * 100) ?>%"></div>
                </div>
                <p class="text-xs text-secondary text-center mt-2"><?= $leaveUsed ?> of 15 days used</p>
            </div>

            <?php if (!empty($pendingLeaves)): ?>
            <div class="bg-white rounded-2xl border border-border-subtle soft-elevated p-6 mt-6">
                <h4 class="font-bold text-on-surface mb-4">Pending Requests</h4>
                <div class="space-y-3">
                    <?php foreach ($pendingLeaves as $pl): ?>
                    <div class="flex items-center justify-between p-3 bg-surface-muted rounded-lg">
                        <div>
                            <p class="text-sm font-bold text-on-surface capitalize"><?= h($pl['type']) ?></p>
                            <p class="text-xs text-secondary"><?= h($pl['start_date']) ?> → <?= h($pl['end_date']) ?></p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-yellow-100 text-yellow-700">Pending</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $pageScripts = '
<style>
main {
    background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url("' . BASE_URL . '/public/background/dashboard.jpeg") center/cover no-repeat fixed;
    min-height: 100vh;
}
</style>
<script>
document.querySelectorAll(".soft-elevated").forEach(card => {
    card.addEventListener("mouseenter", () => { card.style.transform = "translateY(-2px)"; card.style.transition = "transform 0.2s ease-out, box-shadow 0.2s ease-out"; card.classList.add("shadow-lg"); });
    card.addEventListener("mouseleave", () => { card.style.transform = "translateY(0px)"; card.classList.remove("shadow-lg"); });
});
</script>';
require_once __DIR__ . '/../../includes/footer.php'; ?>

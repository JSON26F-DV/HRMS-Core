<?php
requireLogin();
requireAdmin();
$pageTitle = 'Leave Management | HRMS Core';
$currentPage = 'leave_management';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/pagination.php';

$perPage = 10;
$currentPageNum = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($currentPageNum - 1) * $perPage;

$totalCount = $pdo->query("SELECT COUNT(*) FROM leaves")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT l.*, e.first_name, e.last_name, e.employee_id, u.email as emp_email, e.phone as emp_phone, d.name as department_name
    FROM leaves l
    JOIN employees e ON l.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN users u ON e.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$leaves = $stmt->fetchAll();

$pagination = paginate($currentPageNum, $totalCount, $perPage, $_SERVER['REQUEST_URI'], 'page');
$pagination['base_url'] = preg_replace('/[?&]page=\d+/', '', $pagination['base_url']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['leave_id'])) {
    $newStatus = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE leaves SET status = ?, approved_by = ? WHERE id = ?");
    $stmt->execute([$newStatus, $_SESSION['user_id'], $_POST['leave_id']]);
    header('Location: ' . BASE_URL . '/admin/leave-management');
    exit;
}
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img
                    src="<?= BASE_URL ?>/public/emojis/Title%20emojis/leave.png" class="w-8 h-8" alt=""> Leave
                Management</h2>
            <p class="text-text-body font-body-md">Review and manage employee leave requests.</p>
        </div>
    </div>

    <div
        class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden pb-10 px-4">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Employee</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Type</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Dates</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Status</th>
                        <th
                            class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">
                            Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaves)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-secondary">No leave requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaves as $l): ?>
                            <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle cursor-pointer"
                                onclick="openLeaveModal(<?= htmlspecialchars(json_encode($l)) ?>)"
                                data-leave='<?= htmlspecialchars(json_encode($l)) ?>'>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold text-on-primary-container">
                                            <?= strtoupper(substr($l['first_name'], 0, 1) . substr($l['last_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-on-surface text-body-sm">
                                                <?= h($l['first_name'] . ' ' . $l['last_name']) ?></p>
                                            <p class="text-xs text-secondary"><?= h($l['employee_id']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="capitalize"><?= h($l['type']) ?></span></td>
                                <td class="px-6 py-4 text-body-sm"><?= h($l['start_date']) ?> → <?= h($l['end_date']) ?></td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest inline-flex items-center gap-1
                                <?= $l['status'] === 'approved' ? 'bg-green-100 text-green-700' : ($l['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                        <?= ucfirst($l['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-body-sm text-secondary">
                                    <?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPaginationCompact($pagination) ?>
    </div>
</div>

<!-- Leave Details Modal -->
<div id="leave-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40"
    onclick="closeLeaveModal(event)">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-border-subtle flex items-center justify-between">
            <h3 class="font-headline-md text-headline-md">Leave Request Details</h3>
            <button onclick="closeLeaveModal()"
                class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-surface-muted">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <!-- Employee Info -->
            <div class="flex items-center gap-4 pb-4 border-b border-border-subtle">
                <div id="modal-avatar"
                    class="w-14 h-14 rounded-full bg-primary-container flex items-center justify-center text-lg font-bold text-on-primary-container">
                </div>
                <div>
                    <p id="modal-employee-name" class="font-semibold text-on-surface text-lg"></p>
                    <p id="modal-employee-id" class="text-sm text-secondary"></p>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wider font-bold mb-1">Leave Type</p>
                    <p id="modal-type" class="font-medium capitalize"></p>
                </div>
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wider font-bold mb-1">Status</p>
                    <p id="modal-status"></p>
                </div>
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wider font-bold mb-1">Start Date</p>
                    <p id="modal-start-date" class="font-medium"></p>
                </div>
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wider font-bold mb-1">End Date</p>
                    <p id="modal-end-date" class="font-medium"></p>
                </div>
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wider font-bold mb-1">Department</p>
                    <p id="modal-department" class="font-medium"></p>
                </div>
                <div>
                    <p class="text-xs text-secondary uppercase tracking-wider font-bold mb-1">Applied On</p>
                    <p id="modal-created" class="font-medium"></p>
                </div>
            </div>

            <!-- Reason -->
            <div>
                <p class="text-xs text-secondary uppercase tracking-wider font-bold mb-1">Reason</p>
                <p id="modal-reason" class="text-body-sm bg-surface-muted rounded-lg p-3"></p>
            </div>

            <!-- Action Buttons -->
            <div id="modal-actions" class="flex gap-3 pt-4 border-t border-border-subtle">
                <!-- Buttons will be inserted here by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50"
    onclick="closeConfirmModal(event)">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 text-center">
            <div id="confirm-icon" class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center"></div>
            <h3 id="confirm-title" class="font-headline-md text-headline-md mb-2"></h3>
            <p id="confirm-message" class="text-body-sm text-secondary mb-6"></p>
            <div class="flex gap-3">
                <button onclick="closeConfirmModal()"
                    class="flex-1 px-4 py-2.5 border border-border-subtle rounded-lg text-body-sm font-medium hover:bg-surface-muted">Cancel</button>
                <form id="confirm-form" method="POST" class="flex-1">
                    <input type="hidden" name="leave_id" id="confirm-leave-id">
                    <input type="hidden" name="action" id="confirm-action">
                    <button type="submit" id="confirm-btn"
                        class="w-full px-4 py-2.5 rounded-lg text-body-sm font-bold text-white"></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openLeaveModal(data) {
        document.getElementById('modal-avatar').textContent = data.first_name.charAt(0).toUpperCase() + data.last_name.charAt(0).toUpperCase();
        document.getElementById('modal-employee-name').textContent = data.first_name + ' ' + data.last_name;
        document.getElementById('modal-employee-id').textContent = data.employee_id;
        document.getElementById('modal-type').textContent = data.type;
        document.getElementById('modal-start-date').textContent = data.start_date;
        document.getElementById('modal-end-date').textContent = data.end_date;
        document.getElementById('modal-department').textContent = data.department_name || 'N/A';
        document.getElementById('modal-created').textContent = new Date(data.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        document.getElementById('modal-reason').textContent = data.reason || 'No reason provided';

        // Status badge
        const statusEl = document.getElementById('modal-status');
        let statusClass = '';
        if (data.status === 'approved') statusClass = 'bg-green-100 text-green-700';
        else if (data.status === 'rejected') statusClass = 'bg-red-100 text-red-700';
        else statusClass = 'bg-yellow-100 text-yellow-700';
        statusEl.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase ' + statusClass + '">' + data.status.charAt(0).toUpperCase() + data.status.slice(1) + '</span>';

        // Action buttons
        const actionsEl = document.getElementById('modal-actions');
        if (data.status === 'pending') {
            actionsEl.innerHTML = `
            <button onclick="showConfirm('${data.id}', 'approve', 'approve')" class="flex-1 px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">check</span> Approve
            </button>
            <button onclick="showConfirm('${data.id}', 'reject', 'reject')" class="flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">close</span> Reject
            </button>
        `;
        } else {
            actionsEl.innerHTML = '<p class="text-center text-secondary text-sm w-full">This request has already been processed.</p>';
        }

        document.getElementById('leave-modal').classList.remove('hidden');
        document.getElementById('leave-modal').classList.add('flex');
    }

    function closeLeaveModal(event) {
        if (!event || event.target === event.currentTarget) {
            document.getElementById('leave-modal').classList.add('hidden');
            document.getElementById('leave-modal').classList.remove('flex');
        }
    }

    function showConfirm(leaveId, action, type) {
        document.getElementById('confirm-leave-id').value = leaveId;
        document.getElementById('confirm-action').value = action;

        const icon = document.getElementById('confirm-icon');
        const title = document.getElementById('confirm-title');
        const message = document.getElementById('confirm-message');
        const btn = document.getElementById('confirm-btn');

        if (type === 'approve') {
            icon.className = 'w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-green-100';
            icon.innerHTML = '<span class="material-symbols-outlined text-3xl text-green-600">check_circle</span>';
            title.textContent = 'Approve Leave Request?';
            message.textContent = 'This employee will be notified that their leave request has been approved.';
            btn.className = 'flex-1 px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold text-sm';
            btn.textContent = 'Yes, Approve';
        } else {
            icon.className = 'w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-red-100';
            icon.innerHTML = '<span class="material-symbols-outlined text-3xl text-red-600">cancel</span>';
            title.textContent = 'Reject Leave Request?';
            message.textContent = 'This employee will be notified that their leave request has been rejected.';
            btn.className = 'flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-sm';
            btn.textContent = 'Yes, Reject';
        }

        document.getElementById('confirm-modal').classList.remove('hidden');
        document.getElementById('confirm-modal').classList.add('flex');
    }

    function closeConfirmModal(event) {
        if (!event || event.target === event.currentTarget) {
            document.getElementById('confirm-modal').classList.add('hidden');
            document.getElementById('confirm-modal').classList.remove('flex');
        }
    }
</script>
<style>
    main {
        background: linear-gradient(rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.92)), url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;
        min-height: 100vh
    }
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
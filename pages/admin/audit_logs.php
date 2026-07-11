<?php
requireLogin();
requireAdmin();
$pageTitle = 'Audit Logs | HRMS Core';
$currentPage = 'audit_logs';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/pagination.php';

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_logs'])) {
    $logIds = $_POST['selected_logs'] ?? [];
    if (!empty($logIds)) {
        $placeholders = implode(',', array_fill(0, count($logIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE id IN ($placeholders)");
        $stmt->execute($logIds);
        $deleted = $stmt->rowCount();
        $msg = "$deleted log(s) deleted successfully.";
    } else {
        $error = "No logs selected for deletion.";
    }
}

$perPage = 15;
$currentPageNum = max(1, (int)($_GET['page'] ?? 1));
$offset = ($currentPageNum - 1) * $perPage;

$totalCount = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT al.*, u.email as user_email
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$pagination = paginate($currentPageNum, $totalCount, $perPage, $_SERVER['REQUEST_URI'], 'page');
$pagination['base_url'] = preg_replace('/[?&]page=\d+/', '', $pagination['base_url']);
?>
<div class="max-w-7xl mx-auto space-y-8">
    <?php if ($msg): ?><div class="p-4 bg-green-100 text-green-700 rounded-lg font-semibold"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="p-4 bg-red-100 text-red-700 rounded-lg font-semibold"><?= h($error) ?></div><?php endif; ?>
    
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/audit.png" class="w-8 h-8" alt=""> Audit Logs</h2>
            <p class="text-text-body font-body-md">Track all system activities and changes.</p>
        </div>
        <button onclick="openDeleteModal()" id="delete-btn" class="hidden items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-sm">
            <span class="material-symbols-outlined text-lg">delete</span>
            Delete Selected (<span id="selected-count">0</span>)
        </button>
    </div>

    <form method="POST" id="delete-form">
        <input type="hidden" name="delete_logs" value="1">
        <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-surface-muted border-b border-border-subtle">
                            <th class="text-left px-4 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold w-12">
                                <input type="checkbox" id="select-all" onchange="toggleAll(this)" class="w-5 h-5 rounded border-border-subtle cursor-pointer">
                            </th>
                            <th class="text-left px-4 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Timestamp</th>
                            <th class="text-left px-4 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">User</th>
                            <th class="text-left px-4 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Action</th>
                            <th class="text-left px-4 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Entity</th>
                            <th class="text-left px-4 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="px-6 py-12 text-center text-secondary">No audit logs found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                            <td class="px-4 py-4">
                                <input type="checkbox" name="selected_logs[]" value="<?= $log['id'] ?>" class="log-checkbox w-5 h-5 rounded border-border-subtle cursor-pointer" onchange="updateSelectedCount()">
                            </td>
                            <td class="px-4 py-4 text-body-sm whitespace-nowrap"><?= h($log['created_at']) ?></td>
                            <td class="px-4 py-4 text-body-sm"><?= h($log['user_email'] ?? 'System') ?></td>
                            <td class="px-4 py-4">
                                <span class="px-2.5 py-1 bg-surface-muted rounded-lg text-xs font-bold"><?= h($log['action']) ?></span>
                            </td>
                            <td class="px-4 py-4 text-body-sm"><?= h($log['entity_type'] ?? '--') ?> #<?= h($log['entity_id'] ?? '--') ?></td>
                            <td class="px-4 py-4 text-body-sm text-secondary max-w-xs truncate" title="<?= h($log['details'] ?? '') ?>"><?= h($log['details'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= renderPaginationWithInfo($pagination) ?>
        </div>
    </form>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40" onclick="closeDeleteModal(event)">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 text-center">
            <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center bg-red-100">
                <span class="material-symbols-outlined text-3xl text-red-600">warning</span>
            </div>
            <h3 class="font-headline-md text-headline-md mb-2">Delete Audit Logs?</h3>
            <p class="text-body-sm text-secondary mb-6">Are you sure you want to delete <span id="delete-count">0</span> selected log(s)? This action cannot be undone.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 border border-border-subtle rounded-lg text-body-sm font-medium hover:bg-surface-muted">Cancel</button>
                <button onclick="submitDelete()" class="flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-body-sm font-bold">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.log-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.log-checkbox:checked');
    const count = checkboxes.length;
    const deleteBtn = document.getElementById('delete-btn');
    const countSpan = document.getElementById('selected-count');
    
    countSpan.textContent = count;
    
    if (count > 0) {
        deleteBtn.classList.remove('hidden');
        deleteBtn.classList.add('flex');
    } else {
        deleteBtn.classList.add('hidden');
        deleteBtn.classList.remove('flex');
    }
}

function openDeleteModal() {
    const count = document.querySelectorAll('.log-checkbox:checked').length;
    document.getElementById('delete-count').textContent = count;
    document.getElementById('delete-modal').classList.remove('hidden');
    document.getElementById('delete-modal').classList.add('flex');
}

function closeDeleteModal(event) {
    if (!event || event.target === event.currentTarget) {
        document.getElementById('delete-modal').classList.add('hidden');
        document.getElementById('delete-modal').classList.remove('flex');
    }
}

function submitDelete() {
    document.getElementById('delete-form').submit();
}
</script>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

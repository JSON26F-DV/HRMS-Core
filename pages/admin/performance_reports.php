<?php
requireLogin();
requireAdmin();
$pageTitle = 'Performance & Reports | HRMS Core';
$currentPage = 'performance_reports';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/pagination.php';

$msg = $error = '';
$employees = $pdo->query("SELECT id, first_name, last_name, employee_id FROM employees WHERE status = 'active' ORDER BY last_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $rating = (int)($_POST['rating'] ?? 0);
        $comments = $_POST['comments'] ?? '';
        $review_date = $_POST['review_date'] ?? date('Y-m-d');

        if (!$employee_id || !$type || !$rating || !$review_date) {
            $error = 'Please fill all required fields.';
        } elseif ($rating < 1 || $rating > 5) {
            $error = 'Rating must be between 1 and 5.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO performance_reviews (employee_id, reviewer_id, review_date, rating, comments, type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$employee_id, $_SESSION['user_id'], $review_date, $rating, $comments, $type]);
            $msg = 'Performance review created.';
        }
    }

    if (isset($_POST['update'])) {
        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $rating = (int)($_POST['rating'] ?? 0);
        $comments = $_POST['comments'] ?? '';
        $review_date = $_POST['review_date'] ?? date('Y-m-d');

        if ($rating < 1 || $rating > 5) {
            $error = 'Rating must be between 1 and 5.';
        } else {
            $stmt = $pdo->prepare("UPDATE performance_reviews SET type=?, rating=?, comments=?, review_date=? WHERE id=?");
            $stmt->execute([$type, $rating, $comments, $review_date, $id]);
            $msg = 'Performance review updated.';
        }
    }

    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM performance_reviews WHERE id = ?")->execute([$id]);
        $msg = 'Performance review deleted.';
    }
}

$perPage = 10;
$currentPageNum = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($currentPageNum - 1) * $perPage;

$totalCount = $pdo->query("SELECT COUNT(*) FROM performance_reviews")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT pr.*, e.first_name, e.last_name, e.employee_id, d.name as department_name
    FROM performance_reviews pr
    JOIN employees e ON pr.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    ORDER BY pr.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

$pagination = paginate($currentPageNum, $totalCount, $perPage, $_SERVER['REQUEST_URI'], 'page');
$pagination['base_url'] = preg_replace('/[?&]page=\d+/', '', $pagination['base_url']);

$avgRating = $pdo->query("SELECT AVG(rating) FROM performance_reviews")->fetchColumn();
$reviewsMonth = $pdo->query("SELECT COUNT(*) FROM performance_reviews WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$highRatings = $pdo->query("SELECT COUNT(*) FROM performance_reviews WHERE rating >= 4")->fetchColumn();
$empReviewed = $pdo->query("SELECT COUNT(DISTINCT employee_id) FROM performance_reviews")->fetchColumn();
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/performance.png" class="w-8 h-8" alt=""> Performance & Reports</h2>
            <p class="text-text-body font-body-md">Review employee performance and generate reports.</p>
        </div>
        <button onclick="openCreateModal()" class="bg-green-400 hover:bg-green-500 text-white rounded-lg d-inline-flex align-items-center gap-2 px-4 py-2">
            <span class="material-symbols-outlined text-lg">add</span> New Review
        </button>
    </div>

    <?php if ($msg): ?><div class="p-4 bg-green-100 text-green-700 rounded-lg font-semibold"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="p-4 bg-red-100 text-red-700 rounded-lg font-semibold"><?= h($error) ?></div><?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/performance/total.png" class="w-6 h-6" alt="">
                </div>
                <span class="text-label-sm text-primary font-bold bg-primary-container/20 px-2 py-0.5 rounded"><?= $totalCount ?></span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Total Reviews</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($totalCount) ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/performance/star_2b50.png" class="w-6 h-6" alt="">
                </div>
                <span class="text-label-sm text-yellow-600 font-bold bg-yellow-100 px-2 py-0.5 rounded"><?= $avgRating ? number_format($avgRating, 1) : '--' ?></span>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Avg Rating</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $avgRating ? number_format($avgRating, 1) : '--' ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/performance/calendar_1f4c5.png" class="w-6 h-6" alt="">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">This Month</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($reviewsMonth) ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/performance/chart-increasing-with-yen_1f4b9.png" class="w-6 h-6" alt="">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">High Ratings (&ge;4)</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($highRatings) ?></h3>
            </div>
        </div>
        <div class="stats-card bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center">
                    <img src="<?= BASE_URL ?>/public/emojis/performance/busts-in-silhouette_1f465.png" class="w-6 h-6" alt="">
                </div>
            </div>
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Employees Reviewed</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= number_format($empReviewed) ?></h3>
            </div>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden pb-10">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Employee</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Department</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Type</th>
                        <th class="text-center px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Rating</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Date</th>
                        <th class="text-center px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="6" class="px-6 py-12 text-center text-secondary">No performance reviews found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-xs font-bold text-on-primary-container">
                                            <?= strtoupper(substr($r['first_name'], 0, 1) . substr($r['last_name'], 0, 1)) ?>
                                        </div>
                                        <span class="font-semibold text-body-sm"><?= h($r['first_name'] . ' ' . $r['last_name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-body-sm"><?= h($r['department_name'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4"><span class="capitalize"><?= h($r['type']) ?></span></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">
                                        <span class="material-symbols-outlined text-[14px]">star</span>
                                        <?= h($r['rating']) ?>/5
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-body-sm"><?= h($r['review_date']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='openEditModal(<?= json_encode($r) ?>)'
                                            class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-blue-100 transition-all" title="Edit">
                                            <span class="material-symbols-outlined text-lg">edit</span>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this review?')">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" name="delete"
                                                class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-red-100 transition-all" title="Delete">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPaginationCompact($pagination) ?>
    </div>
</div>

<!-- Create Modal -->
<div id="create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-border-subtle flex items-center justify-between">
            <h3 class="font-headline-md text-headline-md">New Performance Review</h3>
            <button onclick="closeModal('create-modal')" class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-surface-muted">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Employee *</label>
                <select name="employee_id" required class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
                    <option value="">Select employee</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= h($e['first_name'] . ' ' . $e['last_name']) ?> (<?= h($e['employee_id']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Type *</label>
                    <select name="type" required class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
                        <option value="quarterly">Quarterly</option>
                        <option value="annual">Annual</option>
                        <option value="probation">Probation</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Rating (1-5) *</label>
                    <input type="number" name="rating" min="1" max="5" required class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Review Date *</label>
                <input type="date" name="review_date" value="<?= date('Y-m-d') ?>" required class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Comments</label>
                <textarea name="comments" rows="4" class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm resize-none"></textarea>
            </div>
            <button type="submit" name="create" class="w-full py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all">Create Review</button>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-border-subtle flex items-center justify-between">
            <h3 class="font-headline-md text-headline-md">Edit Performance Review</h3>
            <button onclick="closeModal('edit-modal')" class="w-8 h-8 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-surface-muted">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id" id="edit-id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Type *</label>
                    <select name="type" id="edit-type" required class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
                        <option value="quarterly">Quarterly</option>
                        <option value="annual">Annual</option>
                        <option value="probation">Probation</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Rating (1-5) *</label>
                    <input type="number" name="rating" id="edit-rating" min="1" max="5" required class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Review Date *</label>
                <input type="date" name="review_date" id="edit-date" required class="w-full h-11 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm">
            </div>
            <div>
                <label class="text-xs text-secondary uppercase tracking-wider font-bold mb-1 block">Comments</label>
                <textarea name="comments" id="edit-comments" rows="4" class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary text-sm resize-none"></textarea>
            </div>
            <button type="submit" name="update" class="w-full py-3 bg-primary text-white font-bold rounded-lg hover:brightness-95 transition-all">Update Review</button>
        </form>
    </div>
</div>

<style>
main { background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed; min-height: 100vh; }
.stats-card { position: relative; overflow: hidden; }
.stats-card::before { content: ""; position: absolute; top: var(--mouse-y, 0); left: var(--mouse-x, 0); width: 0; height: 0; background: rgba(47, 242, 158, 0.15); border-radius: 50%; transform: translate(-50%, -50%); transition: width 0.4s ease, height 0.4s ease; pointer-events: none; z-index: 0; }
.stats-card:hover::before { width: 300px; height: 300px; }
.stats-card > * { position: relative; z-index: 1; }
</style>
<script>
function openCreateModal() {
    document.getElementById('create-modal').classList.remove('hidden');
    document.getElementById('create-modal').classList.add('flex');
}
function openEditModal(data) {
    document.getElementById('edit-id').value = data.id;
    document.getElementById('edit-type').value = data.type;
    document.getElementById('edit-rating').value = data.rating;
    document.getElementById('edit-date').value = data.review_date;
    document.getElementById('edit-comments').value = data.comments || '';
    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-modal').classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
document.querySelectorAll('.fixed.inset-0').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
});
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".stats-card").forEach(card => {
        card.addEventListener("mousemove", (e) => {
            const rect = card.getBoundingClientRect();
            card.style.setProperty("--mouse-x", `${e.clientX - rect.left}px`);
            card.style.setProperty("--mouse-y", `${e.clientY - rect.top}px`);
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

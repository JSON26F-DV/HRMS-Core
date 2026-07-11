<?php
requireLogin();
requireAdmin();
$pageTitle = 'Performance & Reports | HRMS Core';
$currentPage = 'performance_reports';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/pagination.php';

$perPage = 10;
$currentPageNum = max(1, (int)($_GET['page'] ?? 1));
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
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/Title%20emojis/performance.png" class="w-8 h-8" alt=""> Performance & Reports</h2>
            <p class="text-text-body font-body-md">Review employee performance and generate reports.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-border-subtle">
            <p class="text-label-md text-secondary font-medium">Total Reviews</p>
            <p class="text-headline-lg font-headline-lg text-on-surface mt-1"><?= count($reviews) ?></p>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-border-subtle">
            <p class="text-label-md text-secondary font-medium">Avg Rating</p>
            <p class="text-headline-lg font-headline-lg text-on-surface mt-1">
                <?php
                $avg = $pdo->query("SELECT AVG(rating) FROM performance_reviews")->fetchColumn();
                echo $avg ? number_format($avg, 1) : '--';
                ?>
            </p>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Employee</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Department</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Type</th>
                        <th class="text-center px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Rating</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-secondary">No performance reviews found.</td></tr>
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
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPaginationCompact($pagination) ?>
    </div>
</div>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

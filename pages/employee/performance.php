<?php
requireLogin();
$pageTitle = 'My Performance | HRMS Core';
$currentPage = 'employee_performance';
require_once __DIR__ . '/../../includes/header.php';

$userId = $_SESSION['user_id'];
$emp = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
$emp->execute([$userId]);
$emp = $emp->fetch();
$empId = $emp['id'] ?? 0;

$reviews = [];
$avgRating = 0;
if ($empId) {
    $reviews = $pdo->prepare("
        SELECT pr.*, u.email as reviewer_email
        FROM performance_reviews pr
        LEFT JOIN users u ON pr.reviewer_id = u.id
        WHERE pr.employee_id = ?
        ORDER BY pr.created_at DESC
    ");
    $reviews->execute([$empId]);
    $reviews = $reviews->fetchAll();

    $avg = $pdo->prepare("SELECT AVG(rating) FROM performance_reviews WHERE employee_id = ?");
    $avg->execute([$empId]);
    $avgRating = $avg->fetchColumn();
}
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div>
        <h2 class="font-headline-lg text-headline-lg text-on-surface">My Performance Reviews</h2>
        <p class="text-text-body font-body-md">View your performance evaluation history.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Total Reviews</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= count($reviews) ?></h3>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Average Rating</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= $avgRating ? number_format($avgRating, 1) : '--' ?></h3>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Latest Review</p>
                <h3 class="font-headline-lg text-headline-lg mt-1"><?= !empty($reviews) ? h($reviews[0]['review_date']) : '--' ?></h3>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-border-subtle flex flex-col justify-between">
            <div>
                <p class="text-label-md text-secondary uppercase tracking-wider font-bold">Best Rating</p>
                <h3 class="font-headline-lg text-headline-lg mt-1">
                    <?php
                    $best = array_reduce($reviews, function($c, $r) { return max($c, $r['rating']); }, 0);
                    echo $best ?: '--';
                    ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-surface-muted border-b border-border-subtle">
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Type</th>
                        <th class="text-center px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Rating</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Comments</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Review Date</th>
                        <th class="text-left px-6 py-4 text-label-sm text-secondary uppercase tracking-widest font-bold">Reviewed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-secondary">No performance reviews yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                    <tr class="hover:bg-surface-muted transition-colors border-b border-border-subtle">
                        <td class="px-6 py-4"><span class="capitalize font-semibold"><?= h($r['type']) ?></span></td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">
                                <span class="material-symbols-outlined text-[14px]">star</span>
                                <?= h($r['rating']) ?>/5
                            </span>
                        </td>
                        <td class="px-6 py-4 text-body-sm text-secondary max-w-xs truncate"><?= h($r['comments'] ?? '--') ?></td>
                        <td class="px-6 py-4 text-body-sm"><?= h($r['review_date']) ?></td>
                        <td class="px-6 py-4 text-body-sm"><?= h($r['reviewer_email'] ?? 'Admin') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $pageScripts = '
<style>
main {
    background: linear-gradient(rgba(255,255,255,0.92), rgba(255,255,255,0.92)), url("' . BASE_URL . '/public/background/dashboard.jpeg") center/cover no-repeat fixed;
    min-height: 100vh;
}
</style>';
require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
requireLogin();
requireHrOrAdmin();
$pageTitle = 'Events | HRMS Core';
$currentPage = 'events';
require_once __DIR__ . '/../../includes/header.php';

$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add']) || isset($_POST['update'])) {
        $title = trim($_POST['title'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $type = $_POST['type'] ?? 'event';
        $description = trim($_POST['description'] ?? '');

        if (!$title || !$eventDate) {
            $error = 'Title and date are required.';
        } elseif (isset($_POST['update'])) {
            $id = (int) $_POST['id'];
            $pdo->prepare("UPDATE events SET title=?, event_date=?, type=?, description=? WHERE id=?")->execute([$title, $eventDate, $type, $description, $id]);
            logAudit('update', 'event', $id, 'Updated event: '.$title);
            $msg = 'Event updated.';
        } else {
            $pdo->prepare("INSERT INTO events (title, event_date, type, description) VALUES (?, ?, ?, ?)")->execute([$title, $eventDate, $type, $description]);
            logAudit('create', 'event', $pdo->lastInsertId(), 'Created event: '.$title);
            $msg = 'Event created.';
        }
    }
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare("SELECT title FROM events WHERE id=?");
        $stmt->execute([$id]);
        $ev = $stmt->fetch();
        $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
        logAudit('delete', 'event', $id, 'Deleted event: '.($ev['title'] ?? ''));
        $msg = 'Event deleted.';
    }
}

$editEvent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $stmt->execute([(int) $_GET['edit']]);
    $editEvent = $stmt->fetch();
}

$upcoming = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC")->fetchAll();
$past = $pdo->query("SELECT * FROM events WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 10")->fetchAll();
?>
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface flex items-center gap-2"><img src="<?= BASE_URL ?>/public/emojis/tear-off-calendar_1f4c6.png" class="w-8 h-8" alt=""> Events</h2>
            <p class="text-text-body font-body-md">Manage company events, holidays, and meetings.</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="p-4 bg-primary-container/20 text-on-primary-container rounded-lg font-semibold"><?= h($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 bg-error-container text-on-error-container rounded-lg font-semibold"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div>
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle p-6">
                <h3 class="font-headline-md text-headline-md mb-4"><?= $editEvent ? 'Edit Event' : 'New Event' ?></h3>
                <form method="POST" class="space-y-4">
                    <?php if ($editEvent): ?>
                        <input type="hidden" name="id" value="<?= $editEvent['id'] ?>">
                    <?php endif; ?>
                    <div class="space-y-1.5">
                        <label class="font-label-md text-label-md text-on-surface-variant">Title</label>
                        <input name="title" value="<?= h($editEvent['title'] ?? '') ?>" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-label-md text-label-md text-on-surface-variant">Date</label>
                        <input name="event_date" type="date" value="<?= h($editEvent['event_date'] ?? '') ?>" required class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-label-md text-label-md text-on-surface-variant">Type</label>
                        <select name="type" class="w-full h-12 px-4 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container">
                            <option value="event" <?= ($editEvent['type'] ?? '') === 'event' ? 'selected' : '' ?>>Event</option>
                            <option value="holiday" <?= ($editEvent['type'] ?? '') === 'holiday' ? 'selected' : '' ?>>Holiday</option>
                            <option value="meeting" <?= ($editEvent['type'] ?? '') === 'meeting' ? 'selected' : '' ?>>Meeting</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="font-label-md text-label-md text-on-surface-variant">Description</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-3 bg-surface-muted border border-border-subtle rounded-lg focus:outline-none focus:border-primary-container"><?= h($editEvent['description'] ?? '') ?></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" name="<?= $editEvent ? 'update' : 'add' ?>" class="px-6 py-3 bg-primary-container text-on-primary-container font-bold rounded-lg hover:brightness-95 transition-all shadow-sm">
                            <?= $editEvent ? 'Update' : 'Create' ?>
                        </button>
                        <?php if ($editEvent): ?>
                            <a href="<?= BASE_URL ?>/admin/events" class="px-6 py-3 border border-border-subtle rounded-lg font-bold text-secondary hover:bg-surface-muted transition-all">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-8">
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <div class="px-6 py-4 border-b border-border-subtle">
                    <h3 class="font-headline-md text-headline-md flex items-center gap-2"><span class="material-symbols-outlined text-primary">upcoming</span> Upcoming</h3>
                </div>
                <?php if (empty($upcoming)): ?>
                    <div class="px-6 py-12 text-center text-secondary">No upcoming events.</div>
                <?php else: ?>
                    <div class="divide-y divide-border-subtle">
                        <?php foreach ($upcoming as $ev):
                            $typeColors = ['holiday' => 'bg-red-100 text-red-700', 'event' => 'bg-blue-100 text-blue-700', 'meeting' => 'bg-purple-100 text-purple-700'];
                            $tc = $typeColors[$ev['type']] ?? 'bg-surface-muted text-secondary';
                        ?>
                        <div class="px-6 py-4 flex items-center gap-4 hover:bg-surface-muted transition-colors">
                            <div class="w-14 h-14 rounded-xl bg-primary-container/20 flex flex-col items-center justify-center text-center shrink-0">
                                <p class="text-[10px] font-bold uppercase text-secondary"><?= date('M', strtotime($ev['event_date'])) ?></p>
                                <p class="text-lg font-bold leading-tight text-on-surface"><?= date('j', strtotime($ev['event_date'])) ?></p>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-on-surface truncate"><?= h($ev['title']) ?></p>
                                <?php if ($ev['description']): ?>
                                    <p class="text-body-sm text-secondary truncate"><?= h($ev['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest <?= $tc ?>"><?= h($ev['type']) ?></span>
                                <a href="?edit=<?= $ev['id'] ?>" class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container hover:text-on-primary-container transition-all" title="Edit">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this event?')">
                                    <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                    <button type="submit" name="delete" class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-error-container hover:text-error transition-all" title="Delete">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($past)): ?>
            <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-border-subtle overflow-hidden">
                <div class="px-6 py-4 border-b border-border-subtle">
                    <h3 class="font-headline-md text-headline-md flex items-center gap-2 text-secondary"><span class="material-symbols-outlined">history</span> Past Events</h3>
                </div>
                <div class="divide-y divide-border-subtle">
                    <?php foreach ($past as $ev):
                        $typeColors = ['holiday' => 'bg-red-100 text-red-700', 'event' => 'bg-blue-100 text-blue-700', 'meeting' => 'bg-purple-100 text-purple-700'];
                        $tc = $typeColors[$ev['type']] ?? 'bg-surface-muted text-secondary';
                    ?>
                    <div class="px-6 py-4 flex items-center gap-4 hover:bg-surface-muted transition-colors opacity-60">
                        <div class="w-14 h-14 rounded-xl bg-surface-muted flex flex-col items-center justify-center text-center shrink-0">
                            <p class="text-[10px] font-bold uppercase text-secondary"><?= date('M', strtotime($ev['event_date'])) ?></p>
                            <p class="text-lg font-bold leading-tight text-secondary"><?= date('j', strtotime($ev['event_date'])) ?></p>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-on-surface truncate"><?= h($ev['title']) ?></p>
                            <?php if ($ev['description']): ?>
                                <p class="text-body-sm text-secondary truncate"><?= h($ev['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest <?= $tc ?>"><?= h($ev['type']) ?></span>
                            <a href="?edit=<?= $ev['id'] ?>" class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-primary-container hover:text-on-primary-container transition-all" title="Edit">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this event?')">
                                <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                <button type="submit" name="delete" class="w-9 h-9 rounded-lg border border-border-subtle flex items-center justify-center text-secondary hover:bg-error-container hover:text-error transition-all" title="Delete">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>main{background:linear-gradient(rgba(255,255,255,0.92),rgba(255,255,255,0.92)),url('<?= BASE_URL ?>/public/background/dashboard.jpeg') center/cover no-repeat fixed;min-height:100vh}</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

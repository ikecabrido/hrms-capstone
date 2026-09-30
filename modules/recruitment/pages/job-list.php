<?php
include_once __DIR__ . '/../../../database/db.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT 
        j.id,
        COALESCE(MAX(position_record.position_name), j.title) AS title,
        j.category,
        j.location,
        j.max_applicants,
        COUNT(a.id) AS total_applicants
    FROM rao_jobs j
    LEFT JOIN em_positions position_record
        ON LOWER(TRIM(position_record.position_name)) LIKE CONCAT('%', LOWER(TRIM(j.title)), '%')
    LEFT JOIN rao_applications a 
        ON j.id = a.job_id
    GROUP BY 
        j.id,
        j.title,
        j.category,
        j.location,
        j.max_applicants
    ORDER BY j.id DESC
");

$stmt->execute();

$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="module-header">
    <h1>Open Positions (<?= count($jobs) ?>)</h1>
    <p>Manage your current openings and track applicant volume.</p>

</div>

<div class="module-content">

    <?php if (count($jobs) > 0): ?>
        <div class="job-grid">
            <?php foreach ($jobs as $job):
                $max = $job['max_applicants'];
                $current = $job['total_applicants'];
                $isFull = ($max != 0 && $current >= $max);
            ?>

                <div class="job-card <?= $isFull ? 'job-full' : '' ?>">
                    <div class="job-card-body">
                        <div class="job-main-info">
                            <span class="badge badge-category"><?= htmlspecialchars($job['category']) ?></span>
                            <h3 style="<?= $isFull ? 'color: #94a3b8;' : '' ?>"><?= htmlspecialchars($job['title']) ?></h3>

                            <div class="job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                <span><i class="fas fa-clock"></i> Full Time</span>
                            </div>

                            <div class="job-slots">
                                <span class="<?= $isFull ? 'text-danger fw-bold' : 'text-success' ?>">
                                    <?php if ($isFull): ?>
                                        <i class="fas fa-ban"></i> Application Closed (Quota Met)
                                    <?php else: ?>
                                        <i class="fas fa-users"></i> <?= $current ?> / <?= $max == 0 ? '∞' : $max ?> Applied
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div class="job-card-action">
                            <?php if ($isFull): ?>
                                <button class="btn-view-job disabled" style="background: #cbd5e1; cursor: not-allowed;">
                                    Positions Filled
                                </button>
                            <?php else: ?>
                                <a href="index.php?page=job-posting&id=<?= (int) $job['id'] ?>" class="btn-view-job">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>We don't have any openings right now. Check back later!</p>
        </div>
    <?php endif; ?>
</div>
<?php
/**
 * Grade-book summary card. Expects $gradebook from profile-data.php.
 * Optionally set $gradebookLink = ['href' => '?page=...', 'page' => '...', 'label' => '...'].
 */
$gradedCount = (int) $gradebook['graded_count'];
$mastered = $gradebook['mastered'];
$gaps = $gradebook['gaps'];
$totalSkills = count($mastered) + count($gaps);
$gradebookLink = $gradebookLink ?? null;
?>
<div class="mode-card">
    <div class="profile-gb-head">
        <h3><i class="fas fa-book-bookmark" style="color:var(--primary); margin-right:0.5rem;" aria-hidden="true"></i> Grade Book</h3>
        <?php if (!empty($gradebookLink)): ?>
            <a class="profile-gb-link" href="<?= htmlspecialchars($gradebookLink['href'], ENT_QUOTES, 'UTF-8') ?>"<?= !empty($gradebookLink['page']) ? ' data-page="' . htmlspecialchars($gradebookLink['page'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                <?= htmlspecialchars($gradebookLink['label'], ENT_QUOTES, 'UTF-8') ?> <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="profile-gb-stats">
        <div class="profile-gb-stat">
            <div class="profile-gb-stat-label">Average Score</div>
            <div class="profile-gb-stat-value"><?= number_format((float) $gradebook['avg_score'], 1) ?>%</div>
            <div class="profile-gb-stat-sub"><?= $gradedCount > 0 ? $gradedCount . ' graded item' . ($gradedCount !== 1 ? 's' : '') : 'No graded items yet' ?></div>
            <div class="profile-gb-bar" aria-hidden="true"><span style="width:<?= (float) $gradebook['score_pct'] ?>%;"></span></div>
        </div>
        <div class="profile-gb-stat">
            <div class="profile-gb-stat-label">Skills Mastered</div>
            <div class="profile-gb-stat-value is-green"><?= count($mastered) ?></div>
            <div class="profile-gb-stat-sub">of <?= $totalSkills ?> tracked skills</div>
        </div>
        <div class="profile-gb-stat">
            <div class="profile-gb-stat-label">Skill Gaps</div>
            <div class="profile-gb-stat-value is-amber"><?= count($gaps) ?></div>
            <div class="profile-gb-stat-sub">to grow into</div>
        </div>
    </div>

    <div class="profile-gb-panels">
        <section class="profile-gb-panel profile-gb-panel--mastered">
            <div class="profile-gb-panel-head">
                <i class="fas fa-check-circle" aria-hidden="true"></i> Skills Mastered
                <span class="profile-gb-panel-count"><?= count($mastered) ?></span>
            </div>
            <?php if (empty($mastered)): ?>
                <p class="profile-gb-empty">Complete a course to master your first skill.</p>
            <?php else: ?>
                <div class="profile-gb-chips">
                    <?php foreach ($mastered as $skillName): ?>
                        <span class="profile-gb-chip"><?= htmlspecialchars($skillName, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="profile-gb-panel profile-gb-panel--gaps">
            <div class="profile-gb-panel-head">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> Skill Gaps
                <span class="profile-gb-panel-count"><?= count($gaps) ?></span>
            </div>
            <?php if (empty($gaps)): ?>
                <p class="profile-gb-empty">No gaps — every listed skill is mastered.</p>
            <?php else: ?>
                <div class="profile-gb-chips">
                    <?php foreach ($gaps as $skillName): ?>
                        <span class="profile-gb-chip"><?= htmlspecialchars($skillName, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

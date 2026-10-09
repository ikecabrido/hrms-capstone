<?php
$learnerToolItems = [
    ['label' => 'My Learning Path', 'page' => 'learner/my-learning-path', 'icon' => 'fa-route'],
    ['label' => 'Skill Gap', 'page' => 'learner/skill-gap', 'icon' => 'fa-chart-line'],
    ['label' => 'My Skills', 'page' => 'learner/study-subpage/skill', 'icon' => 'fa-star'],
    ['label' => 'Knowledge Transfer', 'page' => 'learner/knowledge-transfer', 'icon' => 'fa-right-left'],
];
?>
<aside class="learner-tools-sidebar" aria-label="Tools">
    <h2>Tools</h2>
    <nav>
        <?php foreach ($learnerToolItems as $item): ?>
            <?php $isActive = $page === $item['page']; ?>
            <a href="?page=<?= urlencode($item['page']) ?>" data-page="<?= htmlspecialchars($item['page'], ENT_QUOTES, 'UTF-8') ?>" class="learner-tools-link<?= $isActive ? ' active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                <i class="fas <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<?php
// Shared action alert partial for Exit module
// Accepts either canonical names ($count, $viewAction) or page aliases ($alertCount, $alertViewAction).
$count = $count ?? $alertCount ?? 0;
$icon = $icon ?? $alertIcon ?? 'fas fa-exclamation-circle';
$message = $message ?? $alertMessage ?? 'Action items need attention';
$viewAction = $viewAction ?? $alertViewAction ?? null;
$id = $id ?? $alertId ?? 'exit-action-alert';
?>
<div id="<?php echo htmlspecialchars($id, ENT_QUOTES); ?>" class="action-alert-wrapper" style="margin-bottom:12px;">
    <?php if ($count > 0): ?>
    <div class="alert alert-warning d-flex align-items-center justify-content-between" role="alert" style="font-weight:600;">
        <div class="d-flex align-items-center gap-2">
            <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES); ?>" style="font-size:18px;margin-right:8px;"></i>
            <span class="action-alert-message"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></span>
            <span class="badge badge-pill badge-primary ml-2" style="background:#0d6efd;color:#fff;margin-left:8px;"><?php echo (int)$count; ?></span>
        </div>
        <div>
            <?php if ($viewAction): ?>
                <a href="#" class="btn btn-sm btn-outline-primary action-alert-view-btn" data-action="<?php echo htmlspecialchars($viewAction, ENT_QUOTES); ?>">View</a>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-light action-alert-dismiss" aria-label="Dismiss">Dismiss</button>
        </div>
    </div>
    <?php else: ?>
    <div style="display:none;"></div>
    <?php endif; ?>
</div>

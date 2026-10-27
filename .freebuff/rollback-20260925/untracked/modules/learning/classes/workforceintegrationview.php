<?php
/**
 * WorkforceIntegrationView — the tabbed presentation of the L&D ↔ Workforce
 * Analytics handoff, rendered for both audiences from one implementation:
 *
 *   pages/admin/workforce-integration.php        all four datasets, status editable,
 *                                                follow-up requests can be raised
 *   pages/instructor/workforce-integration.php   the three department-aware datasets
 *                                                for the viewer's own department, plus
 *                                                the requests aimed at it (read-only)
 *
 * The view only renders: pages load the rows, totals and summary through
 * WorkforceIntegration and hand them over in $spec (see page()). Writable/modal
 * markup is emitted from the same $spec flags, so a role cannot get an action
 * column it should not have.
 *
 * Everything is returned as a string; the caller echoes it. Inline styles are used
 * on purpose — they are what the rest of this page already ships, and moving them
 * to a stylesheet would change the rendered page for the admin view too.
 */
require_once __DIR__ . '/workforceintegration.php';

class WorkforceIntegrationView
{
    private const TH = 'padding:0.7rem 0.85rem;text-align:left;font-size:0.68rem;font-weight:800;text-transform:uppercase;'
        . 'letter-spacing:0.06em;color:var(--primary);white-space:nowrap;';

    private const TD = 'padding:0.7rem 0.85rem;font-size:0.82rem;color:var(--text);vertical-align:top;';

    private const DASH = '<span style="color:var(--muted);">—</span>';

    // ─────────────────────────────────────────────────────────────
    // Page
    // ─────────────────────────────────────────────────────────────

    /**
     * Render a complete page body.
     *
     * $spec:
     *   title, subtitle, hint            strings (hint = the "where this comes from" line)
     *   tabs                             ordered dataset key => ['label', 'icon']
     *   activeTab                        the tab to open
     *   rows, totals                     dataset => rows / dataset => matching row count
     *   summary                          dataset key => count, dataset key + '_open' => open count
     *   snapshot, atRisk                 turnover-risk panel data (null omits the panel)
     *   requests, requestsTotal          optional 'requests' tab (instructor view)
     *   filters                          ['q' => string, 'dept' => string, 'status' => string]
     *   departments                      department filter options; [] hides the select
     *   scopeLabel                       fixed scope shown when there is no department select
     *   page, perPage                    pagination state
     *   baseQuery                        query params every link keeps (page included)
     *   writable                         render the status <select> column
     *   canRequest                       render "raise training request" actions + modal
     *   history                          dataset => record id => triage events (adds the
     *                                    per-record "who moved it, when" rows)
     *   cron, canRunCron                 scheduled-job panel (LearningCron::getStatus());
     *                                    canRunCron renders "Run now" and lets the page run
     *                                    the job itself when it is due
     *   highlight                        record id a notification deep-linked to; that row
     *                                    is marked and scrolled to on load
     *   pinned                           dataset => record the notification named but the
     *                                    list itself did not contain; rendered first
     *   notice                           HTML line shown to explain a deep link that could
     *                                    not be honoured (e.g. outside a scoped department)
     */
    public static function page(array $spec): string
    {
        $tabs        = $spec['tabs'] ?? [];
        $summary     = $spec['summary'] ?? [];
        $filters     = $spec['filters'] ?? ['q' => '', 'dept' => '', 'status' => ''];
        $activeTab   = (string) ($spec['activeTab'] ?? array_key_first($tabs) ?? '');
        $requests    = $spec['requests'] ?? null;
        $writable    = !empty($spec['writable']);
        $canRequest  = !empty($spec['canRequest']);
        $cron        = $spec['cron'] ?? null;
        $canRunCron  = !empty($spec['canRunCron']);
        $highlight   = (int) ($spec['highlight'] ?? 0);
        $hasFilters  = trim((string) $filters['q']) !== '' || trim((string) $filters['dept']) !== '' || trim((string) $filters['status']) !== '';

        $html = '<div class="module-header">'
            . '<h1 class="module-header-title">' . self::esc($spec['title'] ?? 'Workforce Integration') . '</h1>'
            . (!empty($spec['subtitle']) ? '<p class="module-header-subtitle">' . self::esc($spec['subtitle']) . '</p>' : '')
            . '</div>';

        $html .= '<div class="module-content">';
        $html .= self::statCards($tabs, $summary, $requests !== null ? (int) ($spec['requestsTotal'] ?? count($requests)) : null);

        if (!empty($spec['hint'])) {
            $html .= '<p style="margin:0 0 1rem;font-size:0.85rem;color:var(--muted);">'
                . '<i class="fas fa-arrow-right-long" style="margin-right:0.35rem;"></i>' . $spec['hint'] . '</p>';
        }

        // A deep link the page could not honour — said plainly, rather than opening an
        // empty tab and leaving the reader to guess why.
        if (!empty($spec['notice'])) {
            $html .= '<div class="mode-card" style="display:flex;gap:0.6rem;align-items:flex-start;margin-bottom:1rem;border-left:3px solid #d97706;">'
                . '<i class="fas fa-circle-info" style="color:#d97706;margin-top:0.15rem;"></i>'
                . '<p style="margin:0;font-size:0.82rem;color:var(--text);">' . $spec['notice'] . '</p></div>';
        }

        if (is_array($cron) && $cron) {
            $html .= self::cronPanel($cron, $canRunCron);
        }

        $html .= '<div class="tab-container"><div class="tab-list">';
        foreach ($tabs as $key => $meta) {
            $html .= '<button type="button" class="tab-item' . ($key === $activeTab ? ' active' : '') . '" data-tab="wfi-' . self::esc($key) . '">'
                . '<i class="fas ' . self::esc($meta['icon'] ?? 'fa-table') . '" style="margin-right:0.35rem;"></i>' . self::esc($meta['label']) . '</button>';
        }
        if ($requests !== null) {
            $html .= '<button type="button" class="tab-item' . ('requests' === $activeTab ? ' active' : '') . '" data-tab="wfi-requests">'
                . '<i class="fas fa-hand-paper" style="margin-right:0.35rem;"></i>Follow-up requests</button>';
        }
        $html .= '</div>';

        foreach ($tabs as $dataset => $meta) {
            $html .= self::datasetSection($dataset, $meta, $spec, $hasFilters);
        }

        if ($requests !== null) {
            $html .= self::requestsSection($requests, $spec);
        }

        $html .= '</div></div>'; // .tab-container, .module-content

        $html .= '<div id="wfi-toast" style="display:none;position:fixed;right:1.25rem;bottom:1.25rem;z-index:10050;padding:0.85rem 1.1rem;border-radius:12px;font-size:0.82rem;font-weight:600;box-shadow:0 12px 32px rgba(0,0,0,0.18);background:#fff;color:var(--text);" role="status" aria-live="polite"></div>';

        if ($canRequest) {
            $html .= self::requestModal();
        }

        $html .= self::scripts($writable, $canRequest, $highlight, $activeTab);

        return $html;
    }

    // ─────────────────────────────────────────────────────────────
    // Sections
    // ─────────────────────────────────────────────────────────────

    private static function datasetSection(string $dataset, array $meta, array $spec, bool $hasFilters): string
    {
        $rows        = $spec['rows'][$dataset] ?? [];
        $total       = (int) ($spec['totals'][$dataset] ?? 0);
        $perPage     = max(1, (int) ($spec['perPage'] ?? 15));
        $page        = max(1, (int) ($spec['page'] ?? 1));
        $pages       = max(1, (int) ceil($total / $perPage));
        $offset      = ($page - 1) * $perPage;
        $activeTab   = (string) ($spec['activeTab'] ?? '');
        $writable    = !empty($spec['writable']);
        $canRequest  = !empty($spec['canRequest']);

        $html = '<div class="tab-content' . ($dataset === $activeTab ? ' active' : '') . '" data-tab="wfi-' . self::esc($dataset) . '">';
        $html .= self::filterForm($dataset, $meta, $spec, $hasFilters);

        if ($dataset === 'turnover_risk' && !empty($spec['snapshot'])) {
            $html .= self::riskPanel($spec['snapshot'], $spec['atRisk'] ?? [], $spec['scopeLabel'] ?? null);
        }

        $html .= '<div class="mode-card">';
        $html .= '<div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:0.75rem;">'
            . '<h3 style="margin:0;font-size:0.95rem;font-weight:800;color:var(--text);">'
            . '<i class="fas ' . self::esc($meta['icon'] ?? 'fa-table') . '" style="margin-right:0.4rem;color:var(--primary);"></i>'
            . self::esc($meta['label']) . '</h3>'
            . '<span style="font-size:0.75rem;color:var(--muted);">'
            . ($total === 0 ? 'No records' : 'Showing ' . ($offset + 1) . '–' . ($offset + count($rows)) . ' of ' . $total)
            . '</span></div>';

        $html .= self::datasetTable($dataset, $rows, $writable, $canRequest, $spec, $meta, $hasFilters);

        if ($pages > 1) {
            $html .= self::pagination($dataset, $page, $pages, $spec);
        }

        $html .= '</div></div>';

        return $html;
    }

    private static function filterForm(string $dataset, array $meta, array $spec, bool $hasFilters): string
    {
        $filters     = $spec['filters'] ?? [];
        $departments = $spec['departments'] ?? [];
        $baseQuery   = $spec['baseQuery'] ?? [];

        $html = '<form method="get" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;margin-bottom:1rem;">';

        // The filter inputs below cover q/dept/status themselves; only the keys this
        // form does not render are carried as hidden fields.
        foreach (self::hiddenFields($baseQuery, ['page', 'tab', 'q', 'dept', 'status', 'p']) as $name => $value) {
            $html .= '<input type="hidden" name="' . self::esc($name) . '" value="' . self::esc($value) . '">';
        }
        $html .= '<input type="hidden" name="tab" value="' . self::esc($dataset) . '">';

        $html .= '<label style="display:flex;flex-direction:column;gap:0.25rem;font-size:0.75rem;font-weight:600;color:var(--primary);">'
            . 'Search<input type="search" name="q" value="' . self::esc($filters['q'] ?? '') . '" placeholder="Search records…"'
            . ' style="padding:0.5rem 0.75rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;font-size:0.82rem;min-width:200px;"></label>';

        // A department column exists on three of the four datasets.
        if (!in_array($dataset, ['reporting'], true)) {
            if ($departments) {
                $html .= '<label style="display:flex;flex-direction:column;gap:0.25rem;font-size:0.75rem;font-weight:600;color:var(--primary);">'
                    . 'Department<select name="dept" style="padding:0.5rem 0.75rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;font-size:0.82rem;background:#fff;">'
                    . '<option value="">All departments</option>';
                foreach ($departments as $department) {
                    $html .= '<option value="' . self::esc($department) . '"'
                        . ((string) ($filters['dept'] ?? '') === (string) $department ? ' selected' : '') . '>'
                        . self::esc($department) . '</option>';
                }
                $html .= '</select></label>';
            } elseif (!empty($spec['scopeLabel'])) {
                $html .= '<span style="display:flex;flex-direction:column;gap:0.25rem;font-size:0.75rem;font-weight:600;color:var(--primary);">'
                    . 'Department<span style="padding:0.5rem 0.75rem;border-radius:8px;background:rgba(32,0,130,0.05);font-size:0.82rem;font-weight:700;">'
                    . self::esc($spec['scopeLabel']) . '</span></span>';
            }
        }

        $html .= '<label style="display:flex;flex-direction:column;gap:0.25rem;font-size:0.75rem;font-weight:600;color:var(--primary);">'
            . 'Status<select name="status" style="padding:0.5rem 0.75rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;font-size:0.82rem;background:#fff;">'
            . '<option value="">All statuses</option>';
        foreach (WorkforceIntegration::STATUSES[$dataset] ?? [] as $value => $label) {
            $html .= '<option value="' . self::esc($value) . '"'
                . ((string) ($filters['status'] ?? '') === (string) $value ? ' selected' : '') . '>' . self::esc($label) . '</option>';
        }
        $html .= '</select></label>';

        $html .= '<button type="submit" style="padding:0.55rem 1rem;border:0;border-radius:8px;background:var(--primary);color:#fff;font-size:0.8rem;font-weight:700;cursor:pointer;">Apply</button>';

        if ($hasFilters) {
            $withoutFilters = array_diff_key($baseQuery, array_flip(['q', 'dept', 'status', 'p']));
            $html .= '<a href="' . self::esc(self::link($withoutFilters, ['tab' => $dataset])) . '" style="font-size:0.78rem;color:var(--primary);font-weight:600;align-self:center;">Clear</a>';
        }

        return $html . '</form>';
    }

    private static function datasetTable(string $dataset, array $rows, bool $writable, bool $canRequest, array $spec, array $meta, bool $hasFilters): string
    {
        // The record a notification named, when the list below does not carry it.
        $pinnedRow  = $spec['pinned'][$dataset] ?? null;
        $highlight  = (int) ($spec['highlight'] ?? 0);

        if (!$rows && !$pinnedRow) {
            return '<div style="text-align:center;padding:2.5rem;color:var(--muted);">'
                . '<i class="fas ' . self::esc($meta['icon'] ?? 'fa-table') . '" style="font-size:1.6rem;display:block;margin-bottom:0.6rem;opacity:0.4;"></i>'
                . '<p style="margin:0;font-weight:600;font-size:0.9rem;">No ' . self::esc(strtolower((string) ($meta['label'] ?? 'records'))) . ' records'
                . ($hasFilters ? ' match these filters' : ' yet') . '</p>'
                . '<p style="margin:0.35rem 0 0;font-size:0.8rem;opacity:0.8;">'
                . ($hasFilters ? 'Clear the filters to see everything received.' : 'They appear here as soon as the Workforce Analytics module sends them.')
                . '</p></div>';
        }

        $th = self::TH;
        $td = self::TD;
        $head = '';

        // Column sets mirror the four reception tables.
        if ($dataset === 'engagement') {
            $head = '<th style="' . $th . '">Learner / team</th><th style="' . $th . '">Department</th><th style="' . $th . '">Activity</th>'
                . '<th style="' . $th . '">Engagement</th><th style="' . $th . '">Engagement data</th><th style="' . $th . '">Recommended action</th>'
                . '<th style="' . $th . '">Status</th>' . self::actionHeader($writable, $canRequest) . '<th style="' . $th . '">Received</th>';
        } elseif ($dataset === 'turnover_risk') {
            $head = '<th style="' . $th . '">Employee</th><th style="' . $th . '">Department</th><th style="' . $th . '">Risk level</th>'
                . '<th style="' . $th . '">Score</th><th style="' . $th . '">Factors</th><th style="' . $th . '">Recommended action</th>'
                . '<th style="' . $th . '">Status</th>' . self::actionHeader($writable, $canRequest) . '<th style="' . $th . '">Assessed</th>';
        } elseif ($dataset === 'competency') {
            $head = '<th style="' . $th . '">Skill / competency</th><th style="' . $th . '">Department</th><th style="' . $th . '">Priority</th>'
                . '<th style="' . $th . '">Need training</th><th style="' . $th . '">Target proficiency</th><th style="' . $th . '">Expected learning output</th>'
                . '<th style="' . $th . '">Status</th>' . self::actionHeader($writable, $canRequest) . '<th style="' . $th . '">Received</th>';
        } else {
            $head = '<th style="' . $th . '">Reporting target</th><th style="' . $th . '">Frequency</th><th style="' . $th . '">Period</th>'
                . '<th style="' . $th . '">Goal</th><th style="' . $th . '">Expected output</th>'
                . '<th style="' . $th . '">Status</th>' . self::actionHeader($writable, $canRequest) . '<th style="' . $th . '">Received</th>';
        }

        // The history row below a record spans whatever the head above actually has.
        $columns = substr_count($head, '<th');

        $body = '';
        if ($pinnedRow) {
            $body .= '<tr class="wfi-pinned-note"><td colspan="' . max(1, $columns) . '" style="padding:0.5rem 0.85rem;'
                . 'font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;color:var(--primary);background:rgba(32,0,130,0.05);">'
                . '<i class="fas fa-bell" style="margin-right:0.35rem;"></i>From your notification</td></tr>';
            $body .= self::recordRow($dataset, $pinnedRow, $writable, $canRequest, $spec, $columns, true, $highlight);
        }
        foreach ($rows as $row) {
            $body .= self::recordRow($dataset, $row, $writable, $canRequest, $spec, $columns, false, $highlight);
        }

        return '<div style="overflow-x:auto;"><table class="wa-table" style="width:100%;border-collapse:collapse;">'
            . '<thead style="background:rgba(32,0,130,0.03);"><tr>' . $head . '</tr></thead>'
            . '<tbody>' . $body . '</tbody></table></div>';
    }

    /**
     * One list row plus the hidden history row under it.
     *
     * $pinned marks the record a notification deep-linked to when the list itself did not
     * contain it, so following an alert always lands on the row it was about; $highlight
     * is that record's id, matched here to mark the row and (in scripts()) scroll to it,
     * including when the list did already contain it on its own page.
     */
    private static function recordRow(string $dataset, array $row, bool $writable, bool $canRequest, array $spec, int $columns, bool $pinned, int $highlight): string
    {
        $id = (int) ($row['id'] ?? 0);
        $isHighlighted = $id > 0 && $id === $highlight;
        $id = $id > 0 ? ' id="wfi-' . self::esc($dataset) . '-' . $id . '"' : '';
        $marker = $isHighlighted ? ' data-highlight="1"' : '';

        $style = 'border-top:1px solid rgba(32,0,130,0.06);';
        if ($isHighlighted) {
            $style .= 'background:rgba(217,119,6,0.1);box-shadow:inset 3px 0 0 #d97706;';
        } elseif ($pinned) {
            $style .= 'background:rgba(32,0,130,0.04);box-shadow:inset 3px 0 0 var(--primary);';
        }

        return '<tr class="wfi-row"' . $id . $marker . ' style="' . $style . '">'
            . self::datasetRow($dataset, $row, $writable, $canRequest, $spec) . '</tr>'
            . self::historyRow($dataset, (int) ($row['id'] ?? 0), $spec['history'][$dataset][(int) ($row['id'] ?? 0)] ?? [], $columns);
    }

    /** The status column: a pill everyone sees, a select only editors get. */
    private static function actionHeader(bool $writable, bool $canRequest): string
    {
        $th = self::TH;
        $html = '';
        if ($writable) {
            $html .= '<th style="' . $th . '">Set</th>';
        }
        if ($canRequest) {
            $html .= '<th style="' . $th . '">Follow-up</th>';
        }

        return $html;
    }

    private static function datasetRow(string $dataset, array $row, bool $writable, bool $canRequest, array $spec): string
    {
        $td = self::TD;
        $id = (int) ($row['id'] ?? 0);
        $sourceColumn = '';

        if ($dataset === 'engagement') {
            $sourceColumn = '<td style="' . $td . '"><strong>' . self::esc($row['learner_name'] ?: ucwords(str_replace('-', ' ', (string) $row['learner_team_type']))) . '</strong>'
                . (!empty($row['learner_id']) ? '<div style="font-size:0.72rem;color:var(--muted);">Employee #' . (int) $row['learner_id'] . '</div>' : '')
                . '</td>'
                . '<td style="' . $td . '">' . self::esc($row['department']) . '</td>'
                . '<td style="' . $td . '">' . self::esc(ucwords(str_replace('-', ' ', (string) $row['training_activity']))) . '</td>'
                . '<td style="' . $td . '">' . self::levelPill((string) $row['engagement_status']) . '</td>'
                . '<td style="' . $td . '">' . self::clip((string) $row['engagement_data'], 90) . '</td>'
                . '<td style="' . $td . '">' . self::clip((string) $row['recommended_action'], 90) . '</td>';
        } elseif ($dataset === 'turnover_risk') {
            $sourceColumn = '<td style="' . $td . '"><strong>' . self::esc($row['employee_name'] ?: 'Employee #' . (int) $row['employee_id']) . '</strong>'
                . (!empty($row['position_name']) ? '<div style="font-size:0.72rem;color:var(--muted);">' . self::esc($row['position_name']) . '</div>' : '')
                . '</td>'
                . '<td style="' . $td . '">' . self::esc($row['department'] ?: '—') . '</td>'
                . '<td style="' . $td . '">' . self::levelPill((string) $row['risk_level']) . '</td>'
                . '<td style="' . $td . '">' . ($row['risk_score'] === null ? self::DASH : self::esc($row['risk_score'])) . '</td>'
                . '<td style="' . $td . '">' . self::clip((string) ($row['risk_factors'] ?? ''), 90) . '</td>'
                . '<td style="' . $td . '">' . self::clip((string) ($row['recommended_action'] ?? ''), 90) . '</td>';
        } elseif ($dataset === 'competency') {
            $sourceColumn = '<td style="' . $td . '"><strong>' . self::esc($row['skill_name']) . '</strong>'
                . (!empty($row['notes']) ? '<div style="font-size:0.72rem;color:var(--muted);">' . self::clip((string) $row['notes'], 70) . '</div>' : '')
                . '</td>'
                . '<td style="' . $td . '">' . self::esc($row['target_department']) . '</td>'
                . '<td style="' . $td . '">' . self::levelPill((string) $row['priority_level']) . '</td>'
                . '<td style="' . $td . '"><strong>' . (int) $row['employees_needing_training'] . '</strong></td>'
                . '<td style="' . $td . '">' . self::esc($row['target_proficiency']) . '</td>'
                . '<td style="' . $td . '">' . self::clip((string) $row['expected_learning_output'], 90) . '</td>';
        } else {
            $sourceColumn = '<td style="' . $td . '"><strong>' . self::esc(ucwords((string) $row['target_name'])) . '</strong>'
                . (!empty($row['notes']) ? '<div style="font-size:0.72rem;color:var(--muted);">' . self::clip((string) $row['notes'], 70) . '</div>' : '')
                . '</td>'
                . '<td style="' . $td . '">' . self::esc(ucfirst((string) $row['reporting_frequency'])) . '</td>'
                . '<td style="' . $td . '">' . self::date((string) ($row['reporting_period_start'] ?? '')) . ' – ' . self::date((string) ($row['reporting_period_end'] ?? '')) . '</td>'
                . '<td style="' . $td . '"><strong>' . self::esc($row['completion_target']) . '%</strong></td>'
                . '<td style="' . $td . '">' . self::clip((string) $row['expected_output'], 90) . '</td>';
        }

        $html = $sourceColumn
            . '<td style="' . $td . '">' . self::statusPill($dataset, (string) $row['status'])
            . self::historyToggle($dataset, $id, $spec['history'][$dataset][$id] ?? []) . '</td>';

        if ($writable) {
            $html .= '<td style="' . $td . '">' . self::statusSelect($dataset, $id, (string) $row['status']) . '</td>';
        }
        if ($canRequest) {
            $html .= '<td style="' . $td . '">' . self::requestButton($dataset, $row, $spec) . '</td>';
        }

        $html .= '<td style="' . $td . '">' . self::date((string) ($row['assessment_date'] ?? $row['created_at'] ?? '')) . '</td>';

        return $html;
    }

    /**
     * "3 triage steps" under the status pill — the reception tables only hold the current
     * value, so the audit trail is the only place the history exists. Clicking it opens
     * the hidden row historyRow() emitted right below.
     */
    private static function historyToggle(string $dataset, int $id, array $events): string
    {
        if (!$events) {
            return '<div style="margin-top:0.3rem;font-size:0.7rem;color:var(--muted);">Not triaged yet</div>';
        }

        $count = count($events);

        return '<div style="margin-top:0.3rem;"><button type="button" class="wfi-history"'
            . ' data-target="wfi-history-' . self::esc($dataset) . '-' . $id . '"'
            . ' style="border:0;background:none;padding:0;font-size:0.7rem;font-weight:700;color:var(--primary);'
            . 'cursor:pointer;text-decoration:underline dotted;" title="Show who moved this record and when">'
            . '<i class="fas fa-clock-rotate-left" style="margin-right:0.25rem;"></i>'
            . $count . ' triage step' . ($count === 1 ? '' : 's') . '</button></div>';
    }

    /**
     * The hidden row behind historyToggle(): one line per audited decision, newest first.
     * Emitted for every record, empty or not, so the toggle never has a missing target.
     */
    private static function historyRow(string $dataset, int $id, array $events, int $columns): string
    {
        if ($id <= 0) {
            return '';
        }

        $html = '<tr class="wfi-history-row" id="wfi-history-' . self::esc($dataset) . '-' . $id . '"'
            . ' style="display:none;border-top:1px solid rgba(32,0,130,0.06);background:rgba(32,0,130,0.02);">'
            . '<td colspan="' . max(1, $columns) . '" style="padding:0.85rem 1.1rem;">';

        if (!$events) {
            return $html . '<span style="font-size:0.78rem;color:var(--muted);">'
                . 'This record has not been triaged yet — no status move and no follow-up request.</span></td></tr>';
        }

        $html .= '<div style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;'
            . 'color:var(--primary);margin-bottom:0.4rem;">Triage history</div><ol style="margin:0;padding-left:1.1rem;">';

        foreach ($events as $event) {
            $html .= '<li style="font-size:0.78rem;color:var(--text);margin-bottom:0.25rem;">'
                . '<span style="color:var(--muted);">' . self::stamp($event['created_at'] ?? null) . '</span> · '
                . '<strong>' . self::esc($event['actor'] ?? 'Admin') . '</strong> ';

            if (($event['kind'] ?? '') === 'request') {
                $html .= 'raised follow-up request #' . (int) ($event['request_id'] ?? 0)
                    . ($event['title'] !== null && $event['title'] !== '' ? ' "' . self::esc($event['title']) . '"' : '')
                    . ($event['priority'] !== null && $event['priority'] !== ''
                        ? ' (' . self::esc($event['priority']) . ' priority)' : '')
                    . ($event['department'] !== null && $event['department'] !== ''
                        ? ' for ' . self::esc($event['department']) : '');
            } else {
                $html .= 'moved the status ' . self::esc(WorkforceIntegration::statusLabel($dataset, $event['from'] ?? ''))
                    . ' <i class="fas fa-arrow-right-long" style="font-size:0.7rem;"></i> '
                    . '<strong>' . self::esc(WorkforceIntegration::statusLabel($dataset, $event['to'] ?? '')) . '</strong>';
            }

            $html .= '</li>';
        }

        return $html . '</ol></td></tr>';
    }

    /**
     * "Raise training request" — only for the two datasets where follow-up makes
     * sense, and only for the admin view.
     */
    private static function requestButton(string $dataset, array $row, array $spec): string
    {
        if (!isset(WorkforceIntegration::REQUEST_SOURCES[$dataset])) {
            return '<span style="font-size:0.72rem;color:var(--muted);">—</span>';
        }

        $draft = $spec['drafts'][$dataset][(int) ($row['id'] ?? 0)] ?? null;
        if (!is_array($draft)) {
            return '<span style="font-size:0.72rem;color:var(--muted);">—</span>';
        }

        return '<button type="button" class="wfi-request"'
            . ' data-dataset="' . self::esc($dataset) . '"'
            . ' data-id="' . (int) $row['id'] . '"'
            . ' data-title="' . self::esc($draft['title']) . '"'
            . ' data-department="' . self::esc($draft['department']) . '"'
            . ' data-priority="' . self::esc($draft['priority']) . '"'
            . ' data-message="' . self::esc($draft['message']) . '"'
            . ' style="padding:0.4rem 0.7rem;border:1px solid rgba(32,0,130,0.15);border-radius:8px;background:rgba(32,0,130,0.04);'
            . 'color:var(--primary);font-size:0.74rem;font-weight:700;cursor:pointer;white-space:nowrap;">'
            . '<i class="fas fa-plus" style="margin-right:0.25rem;"></i>Request</button>';
    }

    private static function requestsSection(array $requests, array $spec): string
    {
        $th = self::TH;
        $td = self::TD;
        $activeTab = (string) ($spec['activeTab'] ?? '');
        $html = '<div class="tab-content' . ('requests' === $activeTab ? ' active' : '') . '" data-tab="wfi-requests">';
        $html .= '<div class="mode-card">';
        $html .= '<div style="margin-bottom:0.75rem;"><h3 style="margin:0 0 0.25rem;font-size:0.95rem;font-weight:800;color:var(--text);">'
            . '<i class="fas fa-hand-paper" style="margin-right:0.4rem;color:var(--primary);"></i>Follow-up requests</h3>'
            . '<p style="margin:0;font-size:0.78rem;color:var(--muted);">Training requests raised from Workforce Analytics records and aimed at your department. '
            . 'Respond to them from <strong>Training Requests</strong>.</p></div>';

        if (!$requests) {
            $html .= '<div style="text-align:center;padding:2.5rem;color:var(--muted);">'
                . '<i class="fas fa-hand-paper" style="font-size:1.6rem;display:block;margin-bottom:0.6rem;opacity:0.4;"></i>'
                . '<p style="margin:0;font-weight:600;font-size:0.9rem;">No follow-up requests for this department yet</p>'
                . '</div>';
            return $html . '</div></div>';
        }

        $html .= '<div style="overflow-x:auto;"><table class="wa-table" style="width:100%;border-collapse:collapse;">'
            . '<thead style="background:rgba(32,0,130,0.03);"><tr>'
            . '<th style="' . $th . '">Request</th><th style="' . $th . '">Department</th><th style="' . $th . '">Learner</th>'
            . '<th style="' . $th . '">Priority</th><th style="' . $th . '">Status</th><th style="' . $th . '">Raised</th><th style="' . $th . '">Source</th>'
            . '</tr></thead><tbody>';

        foreach ($requests as $request) {
            $html .= '<tr style="border-top:1px solid rgba(32,0,130,0.06);">'
                . '<td style="' . $td . '"><strong>' . self::esc($request['skill_name']) . '</strong>'
                . (!empty($request['message']) ? '<div style="font-size:0.72rem;color:var(--muted);">' . self::clip((string) $request['message'], 80) . '</div>' : '')
                . '</td>'
                . '<td style="' . $td . '">' . self::esc($request['target_department'] ?: '—') . '</td>'
                . '<td style="' . $td . '">' . self::esc($request['learner_name'] ?: 'Department-wide') . '</td>'
                . '<td style="' . $td . '">' . self::levelPill((string) $request['priority_level']) . '</td>'
                . '<td style="' . $td . '">' . self::requestStatusPill((string) $request['status']) . '</td>'
                . '<td style="' . $td . '">' . self::date((string) $request['created_at']) . '</td>'
                . '<td style="' . $td . '"><code style="font-size:0.72rem;color:var(--muted);">' . self::esc((string) ($request['external_reference_id'] ?? '—')) . '</code></td>'
                . '</tr>';
        }

        return $html . '</tbody></table></div></div></div>';
    }

    /**
     * The scheduled-job panel: what runs, when it is next due, and how the last run went
     * (one row per run in ld_cron_run, so the answer survives a page reload).
     *
     * When $canRun is set the page also carries the job's due flag and posts to
     * pages/admin/ajax/workforce-integration-run-cron.php — both from the "Run now"
     * button and automatically on load when the slot has passed unrun. That last part is
     * what keeps the schedule on a deployment with no OS scheduler configured.
     */
    private static function cronPanel(array $cron, bool $canRun): string
    {
        $job = (string) ($cron['job'] ?? '');
        $last = $cron['last'] ?? null;
        $due = !empty($cron['due']);
        $running = !empty($cron['running']);
        $available = ($cron['available'] ?? true) !== false;
        $lastStatus = is_array($last) ? (string) $last['status'] : 'never';
        $trigger = is_array($last) ? (string) $last['trigger_type'] : '';

        $html = '<div class="mode-card" id="wfi-cron" data-cron-job="' . self::esc($job) . '"'
            . ' data-cron-due="' . ($canRun && $due && !$running && $available ? '1' : '0') . '"'
            . ' data-cron-can-run="' . ($canRun ? '1' : '0') . '"'
            . ' style="margin-bottom:1.5rem;">';

        $html .= '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h3 style="margin:0 0 0.25rem;font-size:0.95rem;font-weight:800;color:var(--text);">'
            . '<i class="fas fa-clock-rotate-left" style="margin-right:0.4rem;color:var(--primary);"></i>'
            . self::esc((string) ($cron['label'] ?? 'Scheduled job'))
            . ' <span style="font-weight:600;font-size:0.75rem;color:var(--muted);">'
            . self::esc((string) ($cron['schedule'] ?? '')) . '</span></h3>'
            . '<p style="margin:0;font-size:0.78rem;color:var(--muted);max-width:70ch;">'
            . self::esc((string) ($cron['description'] ?? ''))
            . (!empty($cron['script']) ? ' Runs itself — scheduled via <code>' . self::esc((string) $cron['script']) . '</code>, '
                . 'or point an OS scheduler at <code>cron/run-due.php</code>.' : '')
            . '</p></div>'
            . '<div style="display:flex;align-items:center;gap:0.5rem;">'
            . '<span id="wfi-cron-pill" style="display:inline-flex;align-items:center;">'
            . self::runStatusPill($lastStatus) . '</span>';

        if ($canRun) {
            $html .= '<button type="button" id="wfi-cron-run" style="padding:0.45rem 0.9rem;border:1px solid rgba(32,0,130,0.15);'
                . 'border-radius:8px;background:rgba(32,0,130,0.04);color:var(--primary);font-size:0.76rem;font-weight:700;'
                . 'cursor:pointer;white-space:nowrap;"><i class="fas fa-play" style="margin-right:0.3rem;"></i>Run now</button>';
        }

        $html .= '</div></div>';

        if (!$available) {
            return $html . '<p style="margin:0.9rem 0 0;font-size:0.82rem;color:#b45309;">'
                . '<i class="fas fa-triangle-exclamation" style="margin-right:0.35rem;"></i>'
                . self::esc((string) ($cron['error'] ?? 'The run history could not be read.'))
                . ' Run <code>sql/workforce_integration.sql</code> to create it.</p>'
                . self::cronMessageSlot() . '</div>';
        }

        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:0.75rem;margin-top:0.9rem;">'
            . self::band('Last run', is_array($last) ? self::stamp((string) $last['started_at']) : 'Never',
                'rgba(32,0,130,0.04)', 'var(--primary)',
                is_array($last) ? $trigger . ' run' : 'nothing booked yet')
            . self::band('Result', $running ? 'Running' : ucfirst($lastStatus === 'never' ? 'never run' : $lastStatus),
                $lastStatus === 'success' ? 'rgba(5,150,105,0.06)' : ($running ? 'rgba(217,119,6,0.06)' : 'rgba(220,53,69,0.06)'),
                $lastStatus === 'success' ? '#047857' : ($running ? '#b45309' : '#b91c1c'),
                is_array($last) && $last['duration_ms'] !== null ? $last['duration_ms'] . ' ms' : '')
            . self::band('Records', is_array($last) && $last['records_processed'] !== null ? (string) $last['records_processed'] : '—',
                'rgba(32,0,130,0.04)', 'var(--primary)', 'learners scored')
            . self::band('Next run', $due ? 'Due now' : (!empty($cron['next_due']) ? self::stamp((string) $cron['next_due']) : 'Not scheduled'),
                $due ? 'rgba(217,119,6,0.06)' : 'rgba(5,150,105,0.06)', $due ? '#b45309' : '#047857',
                !empty($cron['last_success']) ? 'last success ' . self::stamp((string) $cron['last_success']['started_at']) : 'no successful run yet')
            . '</div>';

        if (is_array($last) && $last['message'] !== null && $lastStatus !== 'success') {
            $html .= '<p style="margin:0.75rem 0 0;font-size:0.78rem;color:#b91c1c;">'
                . '<i class="fas fa-circle-exclamation" style="margin-right:0.35rem;"></i>'
                . self::esc((string) $last['message']) . '</p>';
        }

        $recent = is_array($cron['recent'] ?? null) ? $cron['recent'] : [];
        if (count($recent) > 1) {
            $html .= '<div style="margin-top:0.9rem;font-size:0.74rem;color:var(--muted);">'
                . '<strong style="color:var(--primary);font-size:0.68rem;text-transform:uppercase;letter-spacing:0.06em;">Earlier runs</strong>'
                . '<ul style="margin:0.35rem 0 0;padding-left:1.1rem;">';
            foreach (array_slice($recent, 1) as $run) {
                $html .= '<li>' . self::stamp((string) $run['started_at']) . ' · ' . self::esc((string) $run['trigger_type'])
                    . ' · <span style="color:' . ($run['status'] === 'success' ? '#047857' : '#b91c1c') . ';font-weight:700;">'
                    . self::esc((string) $run['status']) . '</span>'
                    . ($run['records_processed'] !== null ? ' · ' . (int) $run['records_processed'] . ' records' : '')
                    . '</li>';
            }
            $html .= '</ul></div>';
        }

        return $html . self::cronMessageSlot() . '</div>';
    }

    /** The line the panel's script writes into after a run. */
    private static function cronMessageSlot(): string
    {
        return '<p id="wfi-cron-message" style="display:none;margin:0.75rem 0 0;font-size:0.8rem;font-weight:600;"></p>';
    }

    /** "Success" / "Failed" / "Running" / "Never run" as a coloured pill. */
    private static function runStatusPill(string $status): string
    {
        [$background, $colour, $label] = match (strtolower($status)) {
            'success' => ['rgba(5,150,105,0.12)', '#047857', 'Success'],
            'failed'  => ['rgba(220,53,69,0.12)', '#b91c1c', 'Failed'],
            'running' => ['rgba(217,119,6,0.12)', '#b45309', 'Running'],
            default   => ['rgba(100,116,139,0.12)', '#475569', 'Never run'],
        };

        return '<span style="display:inline-block;padding:0.25rem 0.75rem;border-radius:999px;font-size:0.72rem;font-weight:700;'
            . 'background:' . $background . ';color:' . $colour . ';">' . self::esc($label) . '</span>';
    }

    private static function riskPanel(array $snapshot, array $atRisk, ?string $scopeLabel): string
    {
        $td = self::TD;
        $scopeNote = $scopeLabel !== null && $scopeLabel !== '' ? ' in ' . self::esc($scopeLabel) : '';

        $html = '<div class="mode-card" style="margin-bottom:1rem;">'
            . '<h3 style="margin:0 0 0.75rem;font-size:0.95rem;font-weight:800;color:var(--text);">'
            . '<i class="fas fa-gauge-high" style="margin-right:0.4rem;color:var(--primary);"></i>'
            . 'Learning engagement risk snapshot'
            . ($snapshot['date'] !== null ? ' <span style="font-weight:600;font-size:0.75rem;color:var(--muted);">('
                . self::esc(date('M d, Y', strtotime((string) $snapshot['date']))) . ')</span>' : '')
            . '</h3>';

        if (($snapshot['date'] ?? null) === null) {
            return $html . '<p style="margin:0;font-size:0.82rem;color:var(--muted);">'
                . 'No engagement snapshot yet — cron/materialize-engagement-snapshots.php builds one per learner each day.</p></div>';
        }

        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:0.75rem;">'
            . self::band('High risk (70+)' . $scopeNote, (int) $snapshot['bands']['high'], 'rgba(220,53,69,0.06)', '#b91c1c')
            . self::band('Watch (40-69)' . $scopeNote, (int) $snapshot['bands']['medium'], 'rgba(217,119,6,0.06)', '#b45309')
            . self::band('Healthy' . $scopeNote, (int) $snapshot['bands']['low'], 'rgba(5,150,105,0.06)', '#047857')
            . self::band('Learners tracked' . $scopeNote, (int) $snapshot['total'], 'rgba(32,0,130,0.04)', 'var(--primary)',
                'avg risk score ' . $snapshot['avg_risk'])
            . '</div>';

        if ($atRisk) {
            $th = self::TH;
            $html .= '<div style="overflow-x:auto;margin-top:1rem;"><table style="width:100%;border-collapse:collapse;">'
                . '<thead style="background:rgba(32,0,130,0.03);"><tr>'
                . '<th style="' . $th . '">Learner</th><th style="' . $th . '">Department</th><th style="' . $th . '">Risk score</th>'
                . '<th style="' . $th . '">Factors</th><th style="' . $th . '">Inactive</th></tr></thead><tbody>';

            foreach ($atRisk as $learner) {
                $html .= '<tr style="border-top:1px solid rgba(32,0,130,0.06);">'
                    . '<td style="' . $td . '">' . self::esc($learner['learner_name'] ?: 'Learner #' . (int) $learner['learner_id']) . '</td>'
                    . '<td style="' . $td . '">' . self::esc((string) $learner['department_name']) . '</td>'
                    . '<td style="' . $td . '"><strong style="color:#b91c1c;">' . self::esc($learner['risk_score']) . '</strong></td>'
                    . '<td style="' . $td . '">' . self::clip(str_replace(['[', ']', '"'], '', (string) $learner['risk_factors']), 90) . '</td>'
                    . '<td style="' . $td . '">' . ($learner['days_since_last_activity'] === null
                        ? '<span style="color:var(--muted);">No activity</span>'
                        : (int) $learner['days_since_last_activity'] . ' days') . '</td>'
                    . '</tr>';
            }

            $html .= '</tbody></table></div>';
        } else {
            $html .= '<p style="margin:0.9rem 0 0;font-size:0.82rem;color:var(--muted);">'
                . 'No learner is above the watch threshold on the latest snapshot' . ($scopeNote !== '' ? ' in ' . self::esc($scopeLabel) : '') . '.</p>';
        }

        return $html . '</div>';
    }

    private static function band(string $label, string|int $value, string $background, string $colour, string $sub = ''): string
    {
        return '<div style="padding:0.85rem 1rem;background:' . $background . ';border-radius:10px;">'
            . '<span style="display:block;font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:' . $colour . ';">'
            . self::esc($label) . '</span>'
            . '<strong style="font-size:1.35rem;color:' . $colour . ';">' . self::esc((string) $value) . '</strong>'
            . ($sub !== '' ? '<div style="font-size:0.72rem;color:var(--muted);">' . self::esc($sub) . '</div>' : '')
            . '</div>';
    }

    private static function statCards(array $tabs, array $summary, ?int $requestsTotal): string
    {
        $html = '<div class="analytics-cards" style="margin-bottom:1.5rem;">';

        foreach ($tabs as $dataset => $meta) {
            $total = (int) ($summary[$dataset] ?? 0);
            $open  = (int) ($summary[$dataset . '_open'] ?? 0);

            $html .= '<button type="button" class="analytics-card wfi-jump" data-tab="' . self::esc($dataset) . '" style="text-align:left;cursor:pointer;border:0;">'
                . '<h2><i class="fas ' . self::esc($meta['icon'] ?? 'fa-table') . '" style="margin-right:0.4rem;opacity:0.6;"></i> '
                . self::esc($meta['label']) . '</h2>'
                . '<p class="analytics-value">' . $total . '</p>'
                . '<div style="font-size:0.8rem;color:#999;">' . self::openLabel($dataset, $open) . '</div>'
                . '</button>';
        }

        if ($requestsTotal !== null) {
            $html .= '<button type="button" class="analytics-card wfi-jump" data-tab="requests" style="text-align:left;cursor:pointer;border:0;">'
                . '<h2><i class="fas fa-hand-paper" style="margin-right:0.4rem;opacity:0.6;"></i> Follow-up requests</h2>'
                . '<p class="analytics-value">' . $requestsTotal . '</p>'
                . '<div style="font-size:0.8rem;color:#999;">raised from these records</div>'
                . '</button>';
        }

        return $html . '</div>';
    }

    private static function openLabel(string $dataset, int $open): string
    {
        return match ($dataset) {
            'engagement'    => $open . ' awaiting follow-up',
            'turnover_risk' => $open . ' not yet mitigated',
            'competency'    => $open . ' open requirement' . ($open === 1 ? '' : 's'),
            default         => $open . ' still in progress',
        };
    }

    private static function pagination(string $dataset, int $page, int $pages, array $spec): string
    {
        $baseQuery = $spec['baseQuery'] ?? [];

        return '<div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1rem;">'
            . ($page > 1
                ? '<a href="' . self::esc(self::link($baseQuery, ['tab' => $dataset, 'p' => $page - 1])) . '" style="font-size:0.8rem;font-weight:700;color:var(--primary);">← Previous</a>'
                : '<span></span>')
            . '<span style="font-size:0.75rem;color:var(--muted);">Page ' . $page . ' of ' . $pages . '</span>'
            . ($page < $pages
                ? '<a href="' . self::esc(self::link($baseQuery, ['tab' => $dataset, 'p' => $page + 1])) . '" style="font-size:0.8rem;font-weight:700;color:var(--primary);">Next →</a>'
                : '<span></span>')
            . '</div>';
    }

    /**
     * The schedule panel on its own. The run endpoint returns this so a finished run can
     * repaint its own panel without reloading the page.
     */
    public static function cronPanelHtml(array $status, bool $canRun): string
    {
        return self::cronPanel($status, $canRun);
    }

    // ─────────────────────────────────────────────────────────────
    // Small pieces
    // ─────────────────────────────────────────────────────────────

    public static function statusPill(string $dataset, ?string $status): string
    {
        $status = (string) $status;
        [$background, $colour] = self::$statusStyles[$status] ?? self::$statusStyles['received'];

        return '<span class="wfi-status-pill" data-status="' . self::esc($status) . '" style="display:inline-block;'
            . 'padding:0.2rem 0.7rem;border-radius:999px;font-size:0.72rem;font-weight:700;white-space:nowrap;'
            . 'background:' . $background . ';color:' . $colour . ';">'
            . self::esc(WorkforceIntegration::statusLabel($dataset, $status)) . '</span>';
    }

    private static function requestStatusPill(string $status): string
    {
        $map = [
            'pending'    => ['rgba(217,119,6,0.12)', '#b45309', 'Pending'],
            'accepted'   => ['rgba(5,150,105,0.12)', '#047857', 'Accepted'],
            'completed'  => ['rgba(37,99,235,0.12)', '#1d4ed8', 'Completed'],
            'rejected'   => ['rgba(220,53,69,0.12)', '#b91c1c', 'Rejected'],
        ];
        [$background, $colour, $label] = $map[strtolower($status)] ?? ['rgba(100,116,139,0.12)', '#475569', ucfirst($status)];

        return '<span style="display:inline-block;padding:0.2rem 0.7rem;border-radius:999px;font-size:0.72rem;font-weight:700;'
            . 'background:' . $background . ';color:' . $colour . ';">' . self::esc($label) . '</span>';
    }

    private static function statusSelect(string $dataset, int $id, ?string $status): string
    {
        $html = '<select class="wfi-status-select" data-dataset="' . self::esc($dataset) . '" data-id="' . $id . '"'
            . ' aria-label="Update status" style="padding:0.3rem 0.5rem;border:1.5px solid rgba(32,0,130,0.12);'
            . 'border-radius:8px;background:#fff;font-size:0.75rem;font-weight:600;cursor:pointer;">';

        foreach (WorkforceIntegration::STATUSES[$dataset] ?? [] as $value => $label) {
            $html .= '<option value="' . self::esc($value) . '"' . ($value === $status ? ' selected' : '') . '>'
                . self::esc($label) . '</option>';
        }

        return $html . '</select>';
    }

    /** Level / priority / engagement-status pill. */
    public static function levelPill(string $level): string
    {
        $map = [
            'critical'    => ['rgba(220,53,69,0.12)', '#b91c1c'],
            'high'        => ['rgba(234,88,12,0.12)', '#c2410c'],
            'medium'      => ['rgba(217,119,6,0.12)', '#b45309'],
            'low'         => ['rgba(100,116,139,0.12)', '#475569'],
            'active'      => ['rgba(5,150,105,0.12)', '#047857'],
            'in-progress' => ['rgba(37,99,235,0.12)', '#1d4ed8'],
            'inactive'    => ['rgba(220,53,69,0.12)', '#b91c1c'],
        ];
        [$background, $colour] = $map[strtolower($level)] ?? $map['low'];

        return '<span style="display:inline-block;padding:0.2rem 0.7rem;border-radius:999px;font-size:0.72rem;font-weight:700;'
            . 'white-space:nowrap;background:' . $background . ';color:' . $colour . ';">'
            . self::esc(ucwords(str_replace('-', ' ', $level))) . '</span>';
    }

    public static function clip(?string $text, int $length = 120): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return self::DASH;
        }
        $short = mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;

        return '<span title="' . self::esc($text) . '">' . self::esc($short) . '</span>';
    }

    public static function date(?string $date): string
    {
        if (!$date || $date === '0000-00-00') {
            return self::DASH;
        }
        $timestamp = strtotime($date);

        return $timestamp ? self::esc(date('M d, Y', $timestamp)) : self::DASH;
    }

    /** Same as date(), with the wall-clock time — for run and triage timestamps. */
    public static function stamp(?string $value): string
    {
        if (!$value || $value === '0000-00-00 00:00:00') {
            return self::DASH;
        }
        $timestamp = strtotime($value);

        return $timestamp ? self::esc(date('M d, Y H:i', $timestamp)) : self::DASH;
    }

    // ─────────────────────────────────────────────────────────────
    // Modal + behaviour
    // ─────────────────────────────────────────────────────────────

    private static function requestModal(): string
    {
        $field = 'width:100%;padding:0.6rem 0.7rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:9px;font-size:0.85rem;font-family:inherit;box-sizing:border-box;';
        $label = 'display:flex;flex-direction:column;gap:0.25rem;font-size:0.72rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.05em;';

        $html = '<div id="wfi-request-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:10060;align-items:center;justify-content:center;padding:1rem;">'
            . '<div style="background:#fff;border-radius:16px;max-width:520px;width:100%;padding:1.5rem;box-shadow:0 24px 64px rgba(0,0,0,0.25);max-height:90vh;overflow-y:auto;">'
            . '<h3 style="margin:0 0 0.35rem;font-size:1.05rem;color:var(--text);"><i class="fas fa-hand-paper" style="color:var(--primary);margin-right:0.4rem;"></i>Raise training request</h3>'
            . '<p style="margin:0 0 1rem;font-size:0.82rem;color:var(--muted);" id="wfi-request-source"></p>'
            . '<div style="display:flex;flex-direction:column;gap:0.85rem;">'
            . '<label style="' . $label . '">Request title<input type="text" id="wfi-request-title" style="' . $field . '" maxlength="150"></label>'
            . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.85rem;">'
            . '<label style="' . $label . '">Department<input type="text" id="wfi-request-department" style="' . $field . '" maxlength="150"></label>'
            . '<label style="' . $label . '">Priority<select id="wfi-request-priority" style="' . $field . 'background:#fff;">'
            . '<option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option>'
            . '</select></label></div>'
            . '<label style="' . $label . '">Details<textarea id="wfi-request-message" rows="4" style="' . $field . 'resize:vertical;"></textarea></label>'
            . '</div>'
            . '<div style="display:flex;justify-content:flex-end;gap:0.6rem;margin-top:1.25rem;">'
            . '<button type="button" id="wfi-request-cancel" style="padding:0.55rem 1.1rem;border:0;border-radius:9px;background:rgba(32,0,130,0.07);color:var(--text);font-size:0.85rem;font-weight:700;cursor:pointer;">Cancel</button>'
            . '<button type="button" id="wfi-request-submit" style="padding:0.55rem 1.1rem;border:0;border-radius:9px;background:var(--primary);color:#fff;font-size:0.85rem;font-weight:700;cursor:pointer;">Raise request</button>'
            . '</div></div></div>';

        return $html;
    }

    /**
     * Page behaviour. Handlers are bound to the elements this page rendered, never
     * to document: the sidebar swaps page content through innerHTML, so a
     * document-level listener would add one more copy on every navigation.
     */
    private static function scripts(bool $writable, bool $canRequest, int $highlight = 0, string $highlightDataset = ''): string
    {
        $html = '<script>(function () {'
            . 'var toast = document.getElementById("wfi-toast");'
            . 'function showToast(message, ok) {'
            . 'if (!toast) { return; }'
            . 'toast.textContent = message;'
            . 'toast.style.borderLeft = "4px solid " + (ok ? "#059669" : "#dc3545");'
            . 'toast.style.display = "block";'
            . 'clearTimeout(toast._timer);'
            . 'toast._timer = setTimeout(function () { toast.style.display = "none"; }, 4500);'
            . '}'
            . 'function token() { var meta = document.querySelector(\'meta[name="csrf-token"]\');'
            . 'return window.CSRF_TOKEN || (meta ? meta.getAttribute("content") : ""); }';

        if ($writable) {
            $html .= 'document.querySelectorAll(".wfi-status-select").forEach(function (select) {'
                . 'select.addEventListener("change", function () {'
                . 'var status = select.value;'
                . 'select.disabled = true;'
                . 'fetch("pages/admin/ajax/workforce-integration-update-status.php", {'
                . 'method: "POST", credentials: "same-origin",'
                . 'headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest", "X-CSRF-Token": token() },'
                . 'body: JSON.stringify({ dataset: select.getAttribute("data-dataset"), id: select.getAttribute("data-id"), status: status })'
                . '}).then(function (response) { return response.json(); })'
                . '.then(function (data) {'
                . 'select.disabled = false;'
                . 'if (!data || !data.success) { showToast((data && (data.error || data.message)) || "The status could not be saved.", false); return; }'
                . 'var row = select.closest("tr");'
                . 'var pill = row ? row.querySelector(".wfi-status-pill") : null;'
                . 'if (pill) { pill.textContent = data.label; pill.setAttribute("data-status", data.status); }'
                . 'showToast(data.message, true);'
                . '}).catch(function () { select.disabled = false; showToast("The status could not be saved.", false); });'
                . '});'
                . '});';
        }

        if ($canRequest) {
            $html .= 'var overlay = document.getElementById("wfi-request-overlay");'
                . 'var fieldTitle = document.getElementById("wfi-request-title");'
                . 'var fieldDepartment = document.getElementById("wfi-request-department");'
                . 'var fieldPriority = document.getElementById("wfi-request-priority");'
                . 'var fieldMessage = document.getElementById("wfi-request-message");'
                . 'var sourceLine = document.getElementById("wfi-request-source");'
                . 'var submitBtn = document.getElementById("wfi-request-submit");'
                . 'var cancelBtn = document.getElementById("wfi-request-cancel");'
                . 'var current = null;'
                . 'function closeModal() { if (overlay) { overlay.style.display = "none"; } current = null; }'
                . 'document.querySelectorAll(".wfi-request").forEach(function (button) {'
                . 'button.addEventListener("click", function () {'
                . 'if (!overlay) { return; }'
                . 'current = { dataset: button.getAttribute("data-dataset"), id: button.getAttribute("data-id") };'
                . 'fieldTitle.value = button.getAttribute("data-title") || "";'
                . 'fieldDepartment.value = button.getAttribute("data-department") || "";'
                . 'fieldPriority.value = button.getAttribute("data-priority") || "medium";'
                . 'fieldMessage.value = button.getAttribute("data-message") || "";'
                . 'sourceLine.textContent = "From record #" + current.id + " (" + current.dataset.replace("_", " ") + "). The record moves to its next status once the request is raised.";'
                . 'overlay.style.display = "flex";'
                . '});'
                . '});'
                . 'if (cancelBtn) { cancelBtn.addEventListener("click", closeModal); }'
                . 'if (overlay) { overlay.addEventListener("click", function (event) { if (event.target === overlay) { closeModal(); } }); }'
                . 'if (submitBtn) {'
                . 'submitBtn.addEventListener("click", function () {'
                . 'if (!current) { return; }'
                . 'submitBtn.disabled = true;'
                . 'fetch("pages/admin/ajax/workforce-integration-create-request.php", {'
                . 'method: "POST", credentials: "same-origin",'
                . 'headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest", "X-CSRF-Token": token() },'
                . 'body: JSON.stringify({'
                . 'dataset: current.dataset, id: current.id,'
                . 'title: fieldTitle.value, department: fieldDepartment.value,'
                . 'priority: fieldPriority.value, message: fieldMessage.value'
                . '})'
                . '}).then(function (response) { return response.json(); })'
                . '.then(function (data) {'
                . 'submitBtn.disabled = false;'
                . 'if (!data || !data.success) { showToast((data && (data.error || data.message)) || "The request could not be raised.", false); return; }'
                . 'var row = document.querySelector(".wfi-row .wfi-request[data-dataset=\\"" + current.dataset + "\\"][data-id=\\"" + current.id + "\\"]");'
                . 'row = row ? row.closest("tr") : null;'
                . 'if (row && data.status_change && data.status_change.success) {'
                . 'var pill = row.querySelector(".wfi-status-pill");'
                . 'if (pill) { pill.textContent = data.status_change.label; pill.setAttribute("data-status", data.status_change.status); }'
                . 'var select = row.querySelector(".wfi-status-select");'
                . 'if (select) { select.value = data.status_change.status; }'
                . '}'
                . 'showToast(data.message, true);'
                . 'closeModal();'
                . '}).catch(function () { submitBtn.disabled = false; showToast("The request could not be raised.", false); });'
                . '});'
                . '}';
        }

        // Triage history: each toggle opens the hidden row rendered under its record.
        $html .= 'document.querySelectorAll(".wfi-history").forEach(function (button) {'
            . 'button.addEventListener("click", function () {'
            . 'var row = document.getElementById(button.getAttribute("data-target"));'
            . 'if (!row) { return; }'
            . 'var open = row.style.display !== "none";'
            . 'row.style.display = open ? "none" : "table-row";'
            . 'button.setAttribute("aria-expanded", open ? "false" : "true");'
            . '});'
            . '});';

        // Scheduled job. Bound per panel, not to document, so swapping in the panel the
        // endpoint returns after a run stays the only live copy of these handlers.
        $html .= 'function bindCronPanel(panel) {'
            . 'if (!panel) { return; }'
            . 'var job = panel.getAttribute("data-cron-job") || "";'
            . 'var canRun = panel.getAttribute("data-cron-can-run") === "1";'
            . 'var button = panel.querySelector("#wfi-cron-run");'
            . 'var busy = false;'
            . 'function run(force) {'
            . 'if (busy) { return; }'
            . 'busy = true;'
            . 'if (button) { button.disabled = true; }'
            . 'fetch("pages/admin/ajax/workforce-integration-run-cron.php", {'
            . 'method: "POST", credentials: "same-origin",'
            . 'headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest", "X-CSRF-Token": token() },'
            . 'body: JSON.stringify({ job: job, force: !!force })'
            . '}).then(function (response) { return response.json(); })'
            . '.then(function (data) {'
            . 'busy = false;'
            . 'if (button) { button.disabled = false; }'
            . 'var live = document.getElementById("wfi-cron");'
            . 'if (data && data.panel && live) {'
            . 'live.outerHTML = data.panel;'
            . 'bindCronPanel(document.getElementById("wfi-cron"));'
            . '}'
            . 'if (data && data.ran) { showToast(data.message, !!data.success); }'
            . 'else if (data && !data.success) { showToast(data.error || data.message || "The scheduled job could not be started.", false); }'
            . '}).catch(function () { busy = false; if (button) { button.disabled = false; }'
            . 'showToast("The scheduled job could not be started.", false); });'
            . '}'
            . 'if (canRun && button) { button.addEventListener("click", function () { run(true); }); }'
            // No OS scheduler needed: the first page load after the slot runs the job.
            . 'if (canRun && panel.getAttribute("data-cron-due") === "1") { run(false); }'
            . '}'
            . 'bindCronPanel(document.getElementById("wfi-cron"));';

        // A notification opened this page on one specific record: bring it into view. The
        // row is either the pinned one or a row of the tab that was opened below it.
        if ($highlight > 0) {
            $html .= 'var reached = document.getElementById("wfi-' . self::esc($highlightDataset) . '-' . $highlight . '");'
                . 'if (reached) { reached.scrollIntoView({ block: "center", behavior: "smooth" }); }';
        }

        $html .= 'document.querySelectorAll(".wfi-jump").forEach(function (card) {'
            . 'card.addEventListener("click", function () {'
            . 'var target = document.querySelector(\'.tab-item[data-tab="wfi-\' + card.getAttribute("data-tab") + \'"]\');'
            . 'if (target) { target.click(); }'
            . '});'
            . '});'
            . '})();</script>';

        return $html;
    }

    // ─────────────────────────────────────────────────────────────
    // Shared tables
    // ─────────────────────────────────────────────────────────────

    /** Status value => [background, colour]. */
    private static array $statusStyles = [
        'received'    => ['rgba(100,116,139,0.12)', '#475569'],
        'monitoring'  => ['rgba(217,119,6,0.12)', '#b45309'],
        'in_progress' => ['rgba(217,119,6,0.12)', '#b45309'],
        'planned'     => ['rgba(37,99,235,0.12)', '#1d4ed8'],
        'mitigated'   => ['rgba(5,150,105,0.12)', '#047857'],
        'completed'   => ['rgba(5,150,105,0.12)', '#047857'],
        'escalated'   => ['rgba(220,53,69,0.12)', '#b91c1c'],
    ];

    /** Attach query params to a link, dropping empty ones. */
    private static function link(array $baseQuery, array $extra = []): string
    {
        $query = array_filter(array_merge($baseQuery, $extra), static function ($value): bool {
            return $value !== '' && $value !== null;
        });

        return '?' . http_build_query($query);
    }

    /** @return array<string, mixed> */
    private static function hiddenFields(array $baseQuery, array $skip): array
    {
        $fields = [];

        foreach (array_merge($baseQuery, ['page' => $baseQuery['page'] ?? null]) as $name => $value) {
            if (in_array($name, $skip, true) || $value === '' || $value === null) {
                continue;
            }
            $fields[$name] = $value;
        }

        return $fields;
    }

    private static function esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }
}

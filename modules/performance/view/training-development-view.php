<?php

class TrainingDevelopmentView
{
    public static function render(array $data): void
    {
        $stats = $data['stats'] ?? [];
        $recommendations = $data['recommendations'] ?? [];
        $employees = $data['employees'] ?? [];
        $programs = $data['programs'] ?? [];
        $upcoming = $data['upcoming'] ?? [];
        $categorySummary = $data['categorySummary'] ?? [];
        $messages = $data['messages'] ?? ['success' => '', 'error' => ''];
        $filters = $data['filters'] ?? [];

        $statusColors = [
            'Pending' => '#f59e0b',
            'Approved' => '#0ea5e9',
            'In Progress' => '#3b82f6',
            'Completed' => '#16a34a',
            'Rejected' => '#ef4444',
        ];

        $priorityColors = [
            'Low' => '#10b981',
            'Medium' => '#f59e0b',
            'High' => '#ef4444',
            'Critical' => '#7c3aed',
        ];

        $totalCategory = 0;
        foreach ($categorySummary as $item) {
            $totalCategory += (int) ($item['total'] ?? 0);
        }
        $totalCategory = $totalCategory > 0 ? $totalCategory : 1;

        $priorityLevels = ['Low', 'Medium', 'High', 'Critical'];
        $statuses = ['Pending', 'Approved', 'In Progress', 'Completed', 'Rejected'];
        ?>
        <link rel="stylesheet" href="css/pages/training-development.css">

        <div class="training-module">
            <div class="training-toolbar">
                <div>
                    <h2>Training & Development</h2>
                    <p>Employee learning plans, skill development, and recommended programs.</p>
                </div>
                <div class="training-toolbar-actions">
                    <button type="button" class="secondary-btn" data-open-modal="training-filter-modal">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <button type="button" class="primary-btn" data-open-modal="training-form-modal">
                        <i class="fa-solid fa-plus"></i> Add Recommendation
                    </button>
                </div>
            </div>

            <?php if ($messages['success'] !== ''): ?>
                <div class="alert success" role="alert"><?= htmlspecialchars($messages['success']) ?></div>
            <?php endif; ?>
            <?php if ($messages['error'] !== ''): ?>
                <div class="alert error" role="alert"><?= htmlspecialchars($messages['error']) ?></div>
            <?php endif; ?>

            <div class="training-summary-grid">
                <div class="training-stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Total Seminars</span>
                        <span class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                    </div>
                    <div class="stat-value"><?= number_format((int) ($stats['total_recommendations'] ?? 0)) ?></div>
                    <div class="stat-meta">All recommendations</div>
                </div>

                <div class="training-stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Upcoming Seminars</span>
                        <span class="stat-icon"><i class="fa-solid fa-calendar-day"></i></span>
                    </div>
                    <div class="stat-value"><?= number_format((int) ($stats['upcoming_training'] ?? 0)) ?></div>
                    <div class="stat-meta">For this month</div>
                </div>

                <div class="training-stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Registered Employees</span>
                        <span class="stat-icon"><i class="fa-solid fa-user-check"></i></span>
                    </div>
                    <div class="stat-value"><?= number_format((int) ($stats['registered_employees'] ?? 0)) ?></div>
                    <div class="stat-meta">Employees enrolled</div>
                </div>

                <div class="training-stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Ongoing Seminars</span>
                        <span class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
                    </div>
                    <div class="stat-value"><?= number_format((int) ($stats['ongoing_training'] ?? 0)) ?></div>
                    <div class="stat-meta">Currently in progress</div>
                </div>

                <div class="training-stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Completed Seminars</span>
                        <span class="stat-icon"><i class="fa-solid fa-circle-check"></i></span>
                    </div>
                    <div class="stat-value"><?= number_format((int) ($stats['completed_training'] ?? 0)) ?></div>
                    <div class="stat-meta">This year</div>
                </div>
            </div>

            <div class="training-main-grid">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Recommended for You</h3>
                        <a href="#">View All</a>
                    </div>

                    <?php if (!empty($recommendations)): ?>
                        <div class="recommendation-list">
                            <?php foreach (array_slice($recommendations, 0, 5) as $item): ?>
                                <?php
                                $employeeName = trim((string) ($item['employee_name'] ?? 'Unknown Employee'));
                                $initials = strtoupper(substr($employeeName, 0, 2));
                                $trainingTitle = trim((string) ($item['training_title'] ?? $item['development_area'] ?? 'Training Program'));
                                $category = trim((string) ($item['training_category'] ?? $item['development_area'] ?? 'Development'));
                                $skillFocus = trim((string) ($item['skill_focus'] ?? $item['performance_gap'] ?? 'General capability'));
                                $reason = trim((string) ($item['recommendation_reason'] ?? $item['performance_gap'] ?? 'Development opportunity'));
                                $priority = trim((string) ($item['priority_level'] ?? 'Medium'));
                                $status = trim((string) ($item['status'] ?? 'Pending'));
                                $recommendationDate = !empty($item['recommendation_date']) ? date('M d, Y', strtotime($item['recommendation_date'])) : 'N/A';
                                ?>
                                <div class="recommendation-item">
                                    <div class="person-avatar"><?= htmlspecialchars($initials) ?></div>
                                    <div class="recommendation-body">
                                        <div class="recommendation-top-row">
                                            <strong><?= htmlspecialchars($employeeName) ?></strong>
                                            <span class="priority-badge" style="background: <?= htmlspecialchars($priorityColors[$priority] ?? '#94a3b8') ?>; color: white;"><?= htmlspecialchars($priority) ?></span>
                                        </div>
                                        <div class="recommendation-title-row">
                                            <span><?= htmlspecialchars($trainingTitle) ?></span>
                                            <button type="button" class="ghost-btn">View Details</button>
                                        </div>
                                        <div class="recommendation-meta">
                                            <span><?= htmlspecialchars($category) ?></span>
                                            <span>•</span>
                                            <span><?= htmlspecialchars($skillFocus) ?></span>
                                        </div>
                                        <div class="recommendation-reason"><?= htmlspecialchars($reason) ?></div>
                                        <div class="recommendation-date"><?= htmlspecialchars($recommendationDate) ?> · <span class="status-pill" style="background: <?= htmlspecialchars($statusColors[$status] ?? '#94a3b8') ?>; color: white;"><?= htmlspecialchars($status) ?></span></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state compact">
                            <h4>No training recommendations available</h4>
                            <p>No recommendations have been recorded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3>Upcoming Trainings</h3>
                    </div>

                    <?php if (!empty($upcoming)): ?>
                        <div class="upcoming-list">
                            <?php foreach ($upcoming as $entry): ?>
                                <?php
                                $date = !empty($entry['recommendation_date']) ? date('M d, Y', strtotime($entry['recommendation_date'])) : 'TBD';
                                $training = trim((string) ($entry['training_title'] ?? 'Training Program'));
                                $employee = trim((string) ($entry['employee_name'] ?? 'Employee'));
                                $priority = trim((string) ($entry['priority_level'] ?? 'Medium'));
                                $status = trim((string) ($entry['status'] ?? 'Pending'));
                                ?>
                                <div class="upcoming-item">
                                    <div class="month-box"><?= htmlspecialchars(date('M', strtotime($entry['recommendation_date'] ?? 'now'))) ?></div>
                                    <div class="upcoming-body">
                                        <div class="upcoming-row">
                                            <strong><?= htmlspecialchars($training) ?></strong>
                                            <span class="status-pill" style="background: <?= htmlspecialchars($statusColors[$status] ?? '#94a3b8') ?>; color: white;"><?= htmlspecialchars($status) ?></span>
                                        </div>
                                        <div class="upcoming-meta"><?= htmlspecialchars($employee) ?> · <?= htmlspecialchars($date) ?></div>
                                        <div class="upcoming-meta small"><?= htmlspecialchars($entry['training_category'] ?? 'Development') ?> · <?= htmlspecialchars($priority) ?> priority</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state compact">
                            <h4>No upcoming schedule</h4>
                            <p>Training dates will appear here once recommendations are added.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="training-lower-grid">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Seminar Category</h3>
                    </div>

                    <?php if (!empty($categorySummary)): ?>
                        <div class="category-layout">
                            <div class="donut-shell">
                                <div class="donut-chart" style="background: conic-gradient(
                                    #2581ff 0 35%,
                                    #2ec4b6 35% 64%,
                                    #ffb703 64% 82%,
                                    #8ecae6 82% 100%
                                );">
                                    <div class="donut-center">
                                        <strong><?= number_format((int) count($categorySummary)) ?></strong>
                                        <span>Categories</span>
                                    </div>
                                </div>
                            </div>
                            <div class="category-legend">
                                <?php foreach (array_slice($categorySummary, 0, 4) as $index => $item): ?>
                                    <?php
                                    $label = trim((string) ($item['category'] ?? 'General'));
                                    $count = (int) ($item['total'] ?? 0);
                                    $percent = round(($count / max(1, $totalCategory)) * 100);
                                    $swatches = ['#2581ff', '#2ec4b6', '#ffb703', '#8ecae6', '#ef476f'];
                                    ?>
                                    <div class="legend-row">
                                        <div class="legend-label"><span class="legend-swatch" style="background: <?= htmlspecialchars($swatches[$index % count($swatches)]) ?>;"></span><?= htmlspecialchars($label) ?></div>
                                        <div class="legend-values">
                                            <strong><?= $count ?></strong>
                                            <span><?= $percent ?>%</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state compact">
                            <h4>No training categories available</h4>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3>Employees Needing Development</h3>
                    </div>

                    <?php if (!empty($recommendations)): ?>
                        <div class="development-list">
                            <?php foreach (array_slice($recommendations, 0, 5) as $entry): ?>
                                <?php
                                $employeeName = trim((string) ($entry['employee_name'] ?? 'Unknown employee'));
                                $reason = trim((string) ($entry['recommendation_reason'] ?? 'Development opportunity'));
                                $priority = trim((string) ($entry['priority_level'] ?? 'Medium'));
                                ?>
                                <div class="development-row">
                                    <div class="development-name-block">
                                        <strong><?= htmlspecialchars($employeeName) ?></strong>
                                        <span><?= htmlspecialchars($reason) ?></span>
                                    </div>
                                    <span class="mini-priority" style="background: <?= htmlspecialchars($priorityColors[$priority] ?? '#94a3b8') ?>; color: white;"><?= htmlspecialchars($priority) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state compact">
                            <h4>No employees flagged yet</h4>
                            <p>Development opportunities will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="floating-cta">
            <button type="button" class="primary-btn circular-btn" data-open-modal="training-form-modal">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>

        <div class="modal-overlay" id="training-form-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Add Recommendation</h3>
                    <button type="button" class="close-modal" data-close-modal="training-form-modal" aria-label="Close">×</button>
                </div>

                <form method="POST" class="training-form">
                    <input type="hidden" name="action" value="create_recommendation">
                    <div class="form-grid two-col">
                        <div class="field">
                            <label for="training_employee_id">Employee</label>
                            <select id="training_employee_id" name="employee_id" required>
                                <option value="">Select employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= (int) ($employee['employee_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($employee['employee_name'] ?? 'Unknown')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="training_program_id">Training Program</label>
                            <select id="training_program_id" name="training_program_id">
                                <option value="">Manual recommendation</option>
                                <?php foreach ($programs as $program): ?>
                                    <option value="<?= (int) ($program['training_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($program['training_title'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="training_title">Training Title</label>
                            <input id="training_title" name="development_area" type="text" placeholder="Communication Skills" required>
                        </div>

                        <div class="field">
                            <label for="training_category">Category</label>
                            <select id="training_category" name="category">
                                <option value="">Select category</option>
                                <?php foreach (['Leadership', 'Communication', 'Technical', 'Compliance', 'Productivity', 'Teamwork'] as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="training_priority">Priority</label>
                            <select id="training_priority" name="priority_level">
                                <?php foreach ($priorityLevels as $level): ?>
                                    <option value="<?= htmlspecialchars($level) ?>" <?= $level === 'Medium' ? 'selected' : '' ?>><?= htmlspecialchars($level) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="training_status">Status</label>
                            <select id="training_status" name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= $status === 'Pending' ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="training_date">Recommended Date</label>
                            <input id="training_date" name="recommendation_date" type="date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="field">
                            <label for="target_completion_date">Target Date</label>
                            <input id="target_completion_date" name="target_completion_date" type="date">
                        </div>

                        <div class="field full-width">
                            <label for="skill_focus">Skill / Competency</label>
                            <input id="skill_focus" name="performance_gap" type="text" placeholder="Leadership, communication, problem solving...">
                        </div>

                        <div class="field full-width">
                            <label for="recommendation_reason">Reason for Recommendation</label>
                            <textarea id="recommendation_reason" name="recommendation_reason" rows="4" placeholder="Explain why this training is needed for this employee." required></textarea>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="secondary-btn" data-close-modal="training-form-modal">Cancel</button>
                        <button type="submit" class="primary-btn">Submit Recommendation</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="training-filter-modal">
            <div class="modal-card small-modal">
                <div class="modal-header">
                    <h3>Filter Recommendations</h3>
                    <button type="button" class="close-modal" data-close-modal="training-filter-modal" aria-label="Close">×</button>
                </div>

                <form method="GET" class="training-form">
                    <input type="hidden" name="page" value="training-development">
                    <div class="form-grid two-col">
                        <div class="field">
                            <label for="filter_search">Search</label>
                            <input id="filter_search" type="text" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? '')) ?>" placeholder="Employee, skill, title">
                        </div>
                        <div class="field">
                            <label for="filter_status">Status</label>
                            <select id="filter_status" name="status">
                                <option value="">All statuses</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="filter_priority">Priority</label>
                            <select id="filter_priority" name="priority">
                                <option value="">All priorities</option>
                                <?php foreach ($priorityLevels as $level): ?>
                                    <option value="<?= htmlspecialchars($level) ?>" <?= (($filters['priority'] ?? '') === $level) ? 'selected' : '' ?>><?= htmlspecialchars($level) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="filter_employee">Employee</label>
                            <select id="filter_employee" name="employee_id">
                                <option value="">All employees</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= (int) ($employee['employee_id'] ?? 0) ?>" <?= (($filters['employee_id'] ?? '') === (string) ($employee['employee_id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($employee['employee_name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="filter_from">From</label>
                            <input id="filter_from" type="date" name="from_date" value="<?= htmlspecialchars((string) ($filters['from_date'] ?? '')) ?>">
                        </div>
                        <div class="field">
                            <label for="filter_to">To</label>
                            <input id="filter_to" type="date" name="to_date" value="<?= htmlspecialchars((string) ($filters['to_date'] ?? '')) ?>">
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="secondary-btn" data-close-modal="training-filter-modal">Cancel</button>
                        <button type="submit" class="primary-btn">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const openButtons = document.querySelectorAll('[data-open-modal]');
                const closeButtons = document.querySelectorAll('[data-close-modal]');

                openButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const modalId = this.getAttribute('data-open-modal');
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.classList.add('open');
                        }
                    });
                });

                closeButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const modalId = this.getAttribute('data-close-modal');
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.classList.remove('open');
                        }
                    });
                });

                document.querySelectorAll('.modal-overlay').forEach(function (modal) {
                    modal.addEventListener('click', function (event) {
                        if (event.target === modal) {
                            modal.classList.remove('open');
                        }
                    });
                });
            });
        </script>
    <?php }
}

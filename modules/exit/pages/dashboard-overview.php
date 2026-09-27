 <?php
    include_once __DIR__ . "/../../../database/db.php";
    $database = new Database();
    $conn     = $database->getConnection();

    /**
     * Safe function to get counts
     * $db       = mysqli connection
     * $table    = table name
     * $column   = optional column name
     * $value    = optional value for column
     * $isDate   = set true if column is a date and you want to count "today"
     */
    function getCount($conn, $table, $column = null, $value = null, $isDate = false)
    {
        if ($column && $value !== null && !$isDate) {

            $sql = "SELECT COUNT(*) AS cnt
            FROM `$table`
            WHERE `$column` = :value";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':value' => $value
            ]);
        } elseif ($column && $value !== null && $isDate) {

            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));

            $sql = "SELECT COUNT(*) AS cnt
            FROM `$table`
            WHERE `$column` >= :today
            AND `$column` < :tomorrow";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':today'    => $today,
                ':tomorrow' => $tomorrow
            ]);
        } else {

            $sql = "SELECT COUNT(*) AS cnt
            FROM `$table`";

            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['cnt'] ?? 0;
    }

    // Fetch counts
    $totalJobs = getCount($conn, 'rao_jobs');
    $activeJobsStmt = $conn->prepare("
SELECT COUNT(*) AS cnt
FROM rao_jobs j
WHERE j.max_applicants = 0
   OR (
        SELECT COUNT(*)
        FROM rao_applications a
        WHERE a.job_id = j.id
          AND a.is_archived = 0
   ) < j.max_applicants

");

    $activeJobsStmt->execute();

    $activeJobs = $activeJobsStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
    $totalApplicantsStmt = $conn->prepare("
SELECT COUNT(*) AS cnt
FROM rao_applications
WHERE is_archived = 0

");

    $totalApplicantsStmt->execute();

    $totalApplicants = $totalApplicantsStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
    $newApplicationsStmt = $conn->prepare("
SELECT COUNT(*) AS cnt
FROM rao_applications
WHERE created_at >= CURDATE()
  AND created_at < CURDATE() + INTERVAL 1 DAY
  AND is_archived = 0

");

    $newApplicationsStmt->execute();

    $newApplications = $newApplicationsStmt
        ->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
    $interviewsToday = getCount($conn, 'rao_interviews', 'interview_date', date('Y-m-d'));
    $pendingOffers = getCount($conn, 'rao_hired', 'pending');
    $activeOnboardings = getCount($conn, 'rao_onboarding', 'progress', 'Active'); // adjust value as needed
    $shortlistedStmt = $conn->prepare("
SELECT COUNT(*) AS cnt
FROM rao_applications
WHERE LOWER(TRIM(status)) = 'shortlisted'
  AND is_archived = 0

");

    $shortlistedStmt->execute();

    $shortlisted = $shortlistedStmt
        ->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;        ?>

  <div class="module-header">
      <h1>Applications</h1>
  </div>

  <div class="module-content">

      <link rel="stylesheet" href="style.css">
      <div class="dashboard-content">
          <div class="page-header" style="margin-bottom: 25px;">
              <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;">📊 Recruitment Overview</h2>
          </div>

          <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">

              <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #eff6ff; color: #2563eb; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-briefcase"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">Total Job Postings</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $totalJobs ?></h2>
                  </div>
              </div>

              <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #ecfdf5; color: #059669; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-check-circle"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">Active Postings</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $activeJobs ?></h2>
                  </div>
              </div>

              <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #f5f3ff; color: #7c3aed; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-users"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">Total Applicants</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $totalApplicants ?></h2>
                  </div>
              </div>

              <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #fff7ed; color: #ea580c; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-user-plus"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">New Apps (Today)</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $newApplications ?></h2>
                  </div>
              </div>

              <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #fefce8; color: #ca8a04; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-calendar-day"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">Interviews Today</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $interviewsToday ?></h2>
                  </div>
              </div>


              <div class=" stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #fff1f2; color: #e11d48; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-file-signature"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">Pending Offers</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $pendingOffers ?></h2>
                  </div>
              </div>

              <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #f0fdf4; color: #16a34a; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-rocket"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">Active Onboarding</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $activeOnboardings ?></h2>
                  </div>
              </div>

              <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="background: #faf5ff; color: #9333ea; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                      <i class="fas fa-star"></i>
                  </div>
                  <div>
                      <div style="font-size: 13px; color: #64748b; font-weight: 500;">Shortlisted</div>
                      <h2 style="margin: 0; color: #1e293b; font-size: 1.5rem;"><?= $shortlisted ?></h2>
                  </div>
              </div>
          </div>

          <div class="dashboard-secondary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-top: 25px;">

              <div class="widget-card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                      <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">Upcoming Interviews</h3>
                      <a href="index.php?page=interview-schedule" style="font-size: 12px; color: #2563eb; text-decoration: none; font-weight: 600;">View All</a>
                  </div>

                  <div class="interview-list">

                      <?php
                        $todayDate = date('Y-m-d');

                        $sql = "SELECT 
        i.*,
        CONCAT(a.first_name, ' ', a.last_name) AS applicant_name
    FROM rao_interviews i
    LEFT JOIN rao_applications a 
        ON i.application_id = a.id
    WHERE DATE(i.interview_date) >= :todayDate
    AND LOWER(TRIM(i.`status`)) = 'scheduled'
    GROUP BY i.application_id
    ORDER BY i.interview_date ASC, i.interview_time ASC
    LIMIT 5";

                        $stmt = $conn->prepare($sql);

                        $stmt->execute([
                            ':todayDate' => $todayDate
                        ]);

                        $upcomingInterviewsQuery = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($upcomingInterviewsQuery)):
                            foreach ($upcomingInterviewsQuery as $row):
                        ?>

                              <div style="display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">

                                  <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; border-radius: 8px; margin-right: 15px; text-align: center; min-width: 85px;">

                                      <span style="display: block; font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;">
                                          <?= date('M d', strtotime($row['interview_date'])) ?>
                                      </span>

                                      <span style="display: block; font-size: 13px; font-weight: 700; color: #1e293b;">
                                          <?= date('h:i A', strtotime($row['interview_time'])) ?>
                                      </span>

                                  </div>

                                  <div style="flex: 1;">

                                      <div style="font-weight: 600; color: #1e293b; font-size: 14px;">
                                          <?= htmlspecialchars($row['applicant_name'] ?? 'Unknown Applicant') ?>
                                      </div>

                                      <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                                          <?= htmlspecialchars($row['interview_type'] ?? '') ?>

                                          •

                                          <span style="color: #2563eb;">
                                              <?= htmlspecialchars($row['interview_mode'] ?? '') ?>
                                          </span>
                                      </div>

                                  </div>

                                  <?php if (
                                        !empty($row['meeting_link']) &&
                                        ($row['interview_mode'] ?? '') === 'Online'
                                    ): ?>

                                      <a
                                          href="<?= htmlspecialchars($row['meeting_link']) ?>"
                                          target="_blank"
                                          title="Join Meeting"
                                          style="color: #2563eb; background: #eff6ff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                                          <i class="fas fa-video" style="font-size: 12px;"></i>
                                      </a>

                                  <?php endif; ?>

                              </div>

                          <?php
                            endforeach;

                        else:
                            ?>

                          <div style="text-align: center; padding: 40px 0;">

                              <i class="fas fa-calendar-day"
                                  style="color: #cbd5e1; font-size: 30px; margin-bottom: 10px;">
                              </i>

                              <p style="color: #94a3b8; font-size: 14px; margin: 0;">
                                  No upcoming interviews found.
                              </p>

                              <small style="color: #cbd5e1;">
                                  Server Date: <?= htmlspecialchars($todayDate) ?>
                              </small>

                          </div>

                      <?php endif; ?>

                  </div>
              </div>
              <div class="widget-card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                      <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">Recent Applicants</h3>
                      <a href="index.php?page=applications" style="font-size: 12px; color: #2563eb; text-decoration: none; font-weight: 600;">View All</a>
                  </div>
                  <div class="widget-card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 20px;">
                      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                          <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">All Applicants</h3>
                          <span style="font-size: 12px; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 20px; font-weight: 600;">
                              Total: <?= $totalApplicants ?>
                          </span>
                      </div>

                      <div class="applicant-list-container">

    <?php
    try {

        $allAppsStmt = $conn->prepare("
            SELECT 
                id,
                first_name,
                last_name,
                status,
                created_at
            FROM rao_applications
            WHERE is_archived = 0
            ORDER BY created_at DESC
            LIMIT 10
        ");

        $allAppsStmt->execute();

        $allApplicants = $allAppsStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        $allApplicants = [];

        echo '<div style="
            padding: 20px;
            background: #fff1f2;
            color: #e11d48;
            border-radius: 8px;
            border: 1px solid #fecdd3;
            font-size: 13px;
        ">
            <strong>Database Error:</strong> '
            . htmlspecialchars($e->getMessage()) .
        '</div>';
    }
    ?>

    <?php if (!empty($allApplicants)): ?>

        <?php foreach ($allApplicants as $app): ?>

            <?php

            // ==========================
            // APPLICANT NAME
            // ==========================

            $firstName = trim($app['first_name'] ?? '');
            $lastName  = trim($app['last_name'] ?? '');

            $fullName = trim($firstName . ' ' . $lastName);

            if ($fullName === '') {
                $fullName = 'Unknown Applicant';
            }


            // ==========================
            // STATUS
            // ==========================

            $status = trim($app['status'] ?? 'Pending');

            if ($status === '') {
                $status = 'Pending';
            }


            // ==========================
            // STATUS BADGE
            // ==========================

            $badge = match (strtolower($status)) {

                'hired' => [
                    'bg' => '#dcfce7',
                    'text' => '#15803d'
                ],

                'offered' => [
                    'bg' => '#fef9c3',
                    'text' => '#854d0e'
                ],

                'shortlisted' => [
                    'bg' => '#f3e8ff',
                    'text' => '#7e22ce'
                ],

                'rejected' => [
                    'bg' => '#fee2e2',
                    'text' => '#b91c1c'
                ],

                default => [
                    'bg' => '#f1f5f9',
                    'text' => '#475569'
                ]
            };


            // ==========================
            // INITIALS
            // ==========================

            $nameParts = preg_split('/\s+/', $fullName);

            $initials = strtoupper(
                substr($nameParts[0] ?? '', 0, 1) .
                substr(
                    $nameParts[count($nameParts) - 1] ?? '',
                    0,
                    1
                )
            );

            ?>

            <div style="
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px 0;
                border-bottom: 1px solid #f1f5f9;
            ">

                <!-- INITIALS -->
                <div style="
                    width: 45px;
                    height: 45px;
                    border-radius: 10px;
                    background: #4f46e5;
                    color: #fff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    flex-shrink: 0;
                ">
                    <?= htmlspecialchars($initials) ?>
                </div>


                <!-- APPLICANT INFORMATION -->
                <div style="flex: 1;">

                    <div style="
                        font-weight: 600;
                        color: #1e293b;
                        font-size: 15px;
                    ">
                        <?= htmlspecialchars($fullName) ?>
                    </div>

                    <div style="
                        font-size: 12px;
                        color: #64748b;
                    ">
                        Applicant
                    </div>

                </div>


                <!-- STATUS -->
                <div style="text-align: right;">

                    <span style="
                        display: inline-block;
                        background: <?= $badge['bg'] ?>;
                        color: <?= $badge['text'] ?>;
                        font-size: 11px;
                        padding: 4px 10px;
                        border-radius: 6px;
                        font-weight: 700;
                        text-transform: uppercase;
                        margin-bottom: 5px;
                    ">
                        <?= htmlspecialchars($status) ?>
                    </span>

                    <div style="
                        font-size: 11px;
                        color: #94a3b8;
                    ">
                        <?= !empty($app['created_at'])
                            ? date('M d, Y', strtotime($app['created_at']))
                            : 'No date'
                        ?>
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div style="
            text-align: center;
            padding: 40px 0;
            color: #94a3b8;
        ">
            No applicants found in the database.
        </div>

    <?php endif; ?>

</div>

                           

                    
                  </div>
              </div>
          </div>

      </div>
  </div>
```
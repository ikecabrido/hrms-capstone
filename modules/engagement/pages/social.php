<?php

require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

$payload = [
  'feed' => [],
  'shared_files' => [],
  'forums' => [],
  'projects' => [],
  'groups' => [],
  'group_members' => [],
  'employees' => [],
];

try {
    $socialController = new \App\Controllers\SocialController();
    $pageData = $socialController->getPageData();
    $payload = array_replace($payload, $pageData);
} catch (\Throwable $e) {
    error_log('Social dashboard page data error: ' . $e->getMessage());
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

?>

<div class="module-header">
  <h1>Social</h1>
</div>

<!-- Create Forum Modal -->
<div class="modal fade" id="createForumModal" tabindex="-1" role="dialog" aria-labelledby="createForumModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createForumModalLabel"><i class="fas fa-comments mr-2"></i>Create Forum</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #6c757d; font-size: 1.8rem; padding: 0; border: none; background: none; cursor: pointer; transition: color 0.3s ease; margin-left: auto;" onmouseover="this.style.color='#495057'" onmouseout="this.style.color='#6c757d'"><span aria-hidden="true">×</span></button>
      </div>
      <form id="createForumForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="forumTitle">Title</label>
            <input id="forumTitle" type="text" class="form-control" required maxlength="255">
          </div>
          <div class="form-group">
            <label for="forumDescription">Description</label>
            <textarea id="forumDescription" class="form-control" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label for="forumCategory">Category</label>
            <select id="forumCategory" class="form-control" required>
              <option value="">Select category</option>
              <option value="Engagement">Engagement</option>
              <option value="Grievance">Grievance</option>
              <option value="HR">HR</option>
              <option value="Recognition">Recognition</option>
              <option value="General">General</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-plus mr-1"></i>Create Forum</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1" role="dialog" aria-labelledby="createPostModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createPostModalLabel"><i class="fas fa-rss mr-2"></i>Create Post</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form id="createPostForm">
        <div class="modal-body">
          <div class="form-group">
            <label for="postContent">Post content</label>
            <textarea id="postContent" class="form-control" rows="5" maxlength="5000" placeholder="Write your post here..."></textarea>
          </div>
          <div class="form-group mb-0">
            <label for="postDescription">Description <span class="text-muted font-weight-normal">(optional)</span></label>
            <input id="postDescription" type="text" class="form-control" maxlength="255" placeholder="Add a short description">
          </div>
          <div class="form-group mb-0 mt-3">
            <label for="postAttachment">Attachment <span class="text-muted font-weight-normal">(optional)</span></label>
            <input id="postAttachment" type="file" class="form-control-file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt,.xlsx,.xls">
            <small class="form-text text-muted">Maximum file size: 10MB</small>
          </div>
          <div id="postFormStatus" class="small mt-3" role="status" aria-live="polite"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Publish Post</button>
        </div>
      </form>
    </div>
  </div>
</div>
    <div class="social-area">
      <div class="row">
        <div class="col-12">
          <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success"><?=htmlspecialchars($flashSuccess)?></div>
          <?php endif; ?>
          <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($flashError)?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success"><?=htmlspecialchars($flashSuccess)?></div>
          <?php endif; ?>
          <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($flashError)?></div>
          <?php endif; ?>
        </div>
      </div>

        <?php
          $recentPosts = array_slice($payload['feed'] ?? [], 0, 3);
          $recentForums = array_slice($payload['forums'] ?? [], 0, 3);
          $recentProjects = array_slice($payload['projects'] ?? [], 0, 3);
          $groupCount = count($payload['groups'] ?? []);
          $forumCount = count($payload['forums'] ?? []);
          $projectCount = count($payload['projects'] ?? []);
          $postCount = count($payload['feed'] ?? []);
          $analyticsPosts = $payload['feed'] ?? [];
          $analyticsComments = 0;
          $analyticsReactions = 0;
          $sentimentCounts = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
          $moderationCounts = ['negative' => 0, 'resolved' => 0];
          $employeeSentimentSummary = [];
          $negativeReviewItems = [];
          $resolvedReviewItems = [];
          $openNegativeEmployees = [];
          $positiveWords = ['good', 'great', 'love', 'excellent', 'awesome', 'happy', 'nice', 'amazing'];
          $negativeWords = ['bad', 'sad', 'angry', 'terrible', 'hate', 'poor', 'worst', 'problem', 'putang', 'gago', 'tanga', 'bwisit', 'pangit', 'galit', 'inis', 'problema', 'ayaw'];

          foreach ($analyticsPosts as $analyticsPost) {
            $analyticsComments += count($analyticsPost['comments'] ?? []);
            $analyticsReactions += (int)($analyticsPost['like_count'] ?? 0) + (int)($analyticsPost['heart_count'] ?? 0) + (int)($analyticsPost['wow_count'] ?? 0);
            $analyticsText = strtolower((string)($analyticsPost['content'] ?? '') . ' ' . implode(' ', array_column($analyticsPost['comments'] ?? [], 'comment')));
            $employeeName = trim((string)($analyticsPost['author_name'] ?? 'Unknown'));
            if ($employeeName === '') {
              $employeeName = 'Unknown';
            }
            if (!isset($employeeSentimentSummary[$employeeName])) {
              $employeeSentimentSummary[$employeeName] = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            }

            $hasPositive = false;
            $hasNegative = false;
            foreach ($positiveWords as $word) {
              $hasPositive = $hasPositive || strpos($analyticsText, $word) !== false;
            }
            foreach ($negativeWords as $word) {
              $hasNegative = $hasNegative || strpos($analyticsText, $word) !== false;
            }
            if ($hasPositive && !$hasNegative) {
              $sentimentCounts['positive']++;
              $employeeSentimentSummary[$employeeName]['positive']++;
            } elseif ($hasNegative && !$hasPositive) {
              if (($analyticsPost['moderation_status'] ?? 'open') === 'resolved') {
                $moderationCounts['resolved']++;
                $resolvedReviewItems[] = [
                  'post_id' => (int)($analyticsPost['eer_social_post_id'] ?? 0),
                  'employee' => $employeeName,
                  'content' => trim((string)($analyticsPost['content'] ?? '')),
                  'created_at' => (string)($analyticsPost['created_at'] ?? ''),
                ];
                $sentimentCounts['neutral']++;
                continue;
              } else {
                $sentimentCounts['negative']++;
                $employeeSentimentSummary[$employeeName]['negative']++;
                $moderationCounts['negative']++;
                $openNegativeEmployees[$employeeName] = ($openNegativeEmployees[$employeeName] ?? 0) + 1;
                $negativeReviewItems[] = [
                  'post_id' => (int)($analyticsPost['eer_social_post_id'] ?? 0),
                  'employee' => $employeeName,
                  'content' => trim((string)($analyticsPost['content'] ?? '')),
                  'created_at' => (string)($analyticsPost['created_at'] ?? ''),
                ];
              }
            } else {
              $sentimentCounts['neutral']++;
              $employeeSentimentSummary[$employeeName]['neutral']++;
            }
          }

          $flaggedEmployees = [];
          foreach ($employeeSentimentSummary as $employeeName => $stats) {
            if ((int)($openNegativeEmployees[$employeeName] ?? 0) > 0) {
              $flaggedEmployees[] = [
                'name' => $employeeName,
                'negative' => (int)$openNegativeEmployees[$employeeName],
                'positive' => (int)($stats['positive'] ?? 0),
                'neutral' => (int)($stats['neutral'] ?? 0),
                'total' => (int)($stats['positive'] ?? 0) + (int)($stats['neutral'] ?? 0) + (int)($stats['negative'] ?? 0),
              ];
            }
          }
          usort($flaggedEmployees, static function ($a, $b) {
            return ($b['negative'] ?? 0) <=> ($a['negative'] ?? 0);
          });

          $positiveEmployees = [];
          foreach ($employeeSentimentSummary as $employeeName => $stats) {
            if ((int)($stats['positive'] ?? 0) > 0) {
              $positiveEmployees[] = [
                'name' => $employeeName,
                'positive' => (int)($stats['positive'] ?? 0),
                'neutral' => (int)($stats['neutral'] ?? 0),
                'negative' => (int)($stats['negative'] ?? 0),
                'total' => (int)($stats['positive'] ?? 0) + (int)($stats['neutral'] ?? 0) + (int)($stats['negative'] ?? 0),
              ];
            }
          }
          usort($positiveEmployees, static function ($a, $b) {
            return ($b['positive'] ?? 0) <=> ($a['positive'] ?? 0);
          });

          $mostActiveContributors = [];
          foreach ($employeeSentimentSummary as $employeeName => $stats) {
            $total = (int)($stats['positive'] ?? 0) + (int)($stats['neutral'] ?? 0) + (int)($stats['negative'] ?? 0);
            if ($total > 0) {
              $mostActiveContributors[] = [
                'name' => $employeeName,
                'total' => $total,
                'positive' => (int)($stats['positive'] ?? 0),
                'negative' => (int)($stats['negative'] ?? 0),
              ];
            }
          }
          usort($mostActiveContributors, static function ($a, $b) {
            return ($b['total'] ?? 0) <=> ($a['total'] ?? 0);
          });

          $moderationItems = [
            ['title' => 'Needs review', 'description' => 'Negative sentiment entries', 'count' => max(0, (int)$moderationCounts['negative'])],
            ['title' => 'Positive signals', 'description' => 'Healthy conversations', 'count' => max(0, (int)$sentimentCounts['positive'])],
            ['title' => 'Resolved items', 'description' => 'Closed by admin', 'count' => max(0, (int)$moderationCounts['resolved'])],
          ];

          $activityItems = [];
          foreach (array_slice($payload['feed'] ?? [], 0, 2) as $post) {
            $activityItems[] = [
              'title' => 'New post published',
              'meta' => htmlspecialchars((string)($post['author_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') . ' • ' . htmlspecialchars((string)($post['created_at'] ?? 'Now'), ENT_QUOTES, 'UTF-8'),
              'badge' => 'Post',
            ];
          }
          foreach (array_slice($payload['forums'] ?? [], 0, 2) as $forum) {
            $activityItems[] = [
              'title' => 'Forum created',
              'meta' => htmlspecialchars((string)($forum['title'] ?? 'Untitled Forum'), ENT_QUOTES, 'UTF-8') . ' • ' . htmlspecialchars((string)($forum['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'),
              'badge' => 'Forum',
            ];
          }
          foreach (array_slice($payload['projects'] ?? [], 0, 2) as $project) {
            $activityItems[] = [
              'title' => 'Project space updated',
              'meta' => htmlspecialchars((string)($project['name'] ?? 'Untitled Project'), ENT_QUOTES, 'UTF-8') . ' • ' . htmlspecialchars((string)($project['status'] ?? 'planning'), ENT_QUOTES, 'UTF-8'),
              'badge' => 'Project',
            ];
          }
          foreach (array_slice($payload['groups'] ?? [], 0, 2) as $group) {
            $activityItems[] = [
              'title' => 'Team group created',
              'meta' => htmlspecialchars((string)($group['name'] ?? 'Untitled Group'), ENT_QUOTES, 'UTF-8') . ' • ' . htmlspecialchars((string)($group['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'),
              'badge' => 'Group',
            ];
          }
          if (empty($activityItems)) {
            $activityItems[] = [
              'title' => 'No admin actions logged',
              'meta' => 'Created, edited, and removed content will appear here',
              'badge' => 'Awaiting data',
            ];
          }
        ?>

        <div class="social-overview-grid">
          <div class="social-stat-card social-stat-primary">
            <div class="social-stat-icon"><i class="fas fa-rss"></i></div>
            <div>
              <span class="social-stat-label">Posts</span>
              <strong><?= $postCount ?></strong>
            </div>
          </div>
          <div class="social-stat-card social-stat-warning">
            <div class="social-stat-icon"><i class="fas fa-comments"></i></div>
            <div>
              <span class="social-stat-label">Forums</span>
              <strong><?= $forumCount ?></strong>
            </div>
          </div>
          <div class="social-stat-card social-stat-success">
            <div class="social-stat-icon"><i class="fas fa-users"></i></div>
            <div>
              <span class="social-stat-label">Groups</span>
              <strong><?= $groupCount ?></strong>
            </div>
          </div>
          <div class="social-stat-card social-stat-info">
            <div class="social-stat-icon"><i class="fas fa-sitemap"></i></div>
            <div>
              <span class="social-stat-label">Projects</span>
              <strong><?= $projectCount ?></strong>
            </div>
          </div>
        </div>

        <div class="social-dashboard-grid">
          <div class="social-main-column">
            <div class="card social-panel" id="feed-section">
              <div class="card-header social-section-header">
                <h3 class="card-title"><i class="fas fa-rss mr-2"></i>Recent Posts</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-target="#createPostModal" data-toggle="modal">
                    <i class="fas fa-plus mr-1"></i>New Post
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div id="social-feed" data-can-reply="true" data-employee-id=""><?php if (!empty($recentPosts)): foreach ($recentPosts as $post): ?>
                  <div class="card mb-3 social-post-card" data-social-item="post" style="border-left: 4px solid #007bff;">
                    <div class="card-body">
                      <div class="post-header d-flex justify-content-between align-items-start mb-3">
                        <div>
                          <h6 class="card-title mb-1" style="font-weight: 600;">
                            <?= htmlspecialchars($post['author_name'] ?? 'Unknown') ?>
                          </h6>
                          <small class="text-muted"><?= htmlspecialchars($post['created_at'] ?? '') ?></small>
                        </div>
                      </div>
                      <p class="card-text mb-3"><?= nl2br(htmlspecialchars($post['content'] ?? '')) ?></p>
                      <?php if (!empty($post['description'])): ?>
                        <p class="card-text text-muted small mb-3"><strong>Description:</strong> <?= nl2br(htmlspecialchars($post['description'])) ?></p>
                      <?php endif; ?>
                      <?php if (!empty($post['file_name']) && !empty($post['file_path'])): ?>
                        <div class="shared-file-attachment mb-3 p-3 bg-light rounded-lg border">
                          <div class="shared-file-attachment-header">
                            <strong class="shared-file-name"><span class="shared-file-type mr-2">FILE</span><?= htmlspecialchars($post['file_name']) ?></strong>
                            <a href="download.php?id=<?= (int)($post['eer_social_post_id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary shared-file-download" download>Download</a>
                          </div>
                        </div>
                      <?php endif; ?>
                      <div class="reaction-summary border-top border-bottom py-2 px-0 mb-3">
                        <small class="text-muted">
                          <i class="fas fa-thumbs-up text-primary mr-1"></i><?= (int)($post['like_count'] ?? 0) ?>
                          <i class="fas fa-heart text-danger mr-1 ml-2"></i><?= (int)($post['heart_count'] ?? 0) ?>
                          <i class="fas fa-star text-warning mr-1 ml-2"></i><?= (int)($post['wow_count'] ?? 0) ?>
                          <i class="fas fa-frown-o text-danger mr-1 ml-2"></i><?= (int)($post['angry_count'] ?? 0) ?>
                        </small>
                      </div>
                      <?php $postId = (int)($post['eer_social_post_id'] ?? 0); $commentCount = count($post['comments'] ?? []); ?>
                      <details class="social-comments-panel">
                        <summary>
                          <span><i class="fas fa-comments mr-1"></i> Comments</span>
                          <span class="comment-count"><?= $commentCount ?></span>
                        </summary>
                        <div class="comments-section">
                          <?php if ($commentCount > 0): foreach ($post['comments'] as $comment): ?>
                            <div class="comment-item">
                              <div><strong class="small"><?= htmlspecialchars($comment['author_name'] ?? 'Unknown') ?>:</strong> <span class="small"><?= htmlspecialchars($comment['comment'] ?? '') ?></span></div>
                              <small class="text-muted d-block mb-2"><?= htmlspecialchars($comment['created_at'] ?? '') ?></small>
                            </div>
                          <?php endforeach; else: ?>
                            <p class="text-muted font-italic small mb-0">No comments yet.</p>
                          <?php endif; ?>
                        </div>
                        <form method="POST" class="comment-form" data-skip>
                          <input type="hidden" name="action" value="comment">
                          <input type="hidden" name="post_id" value="<?= $postId ?>">
                          <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Write a comment..." required></textarea>
                          <button type="submit" class="btn btn-sm btn-primary">Comment</button>
                        </form>
                      </details>
                    </div>
                  </div>
                <?php endforeach; else: ?><p class="text-muted">No posts yet. Share a team update to get the feed started.</p><?php endif; ?></div>
              </div>
            </div>
          </div>

          <div class="social-side-column">
            <div class="card social-panel" id="sentiment-section">
              <div class="card-header social-section-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Sentiment Analysis</h3>
              </div>
              <div class="card-body">
                <div id="sentiment-analysis" class="analytics-stat-grid">
                  <div class="analytics-stat analytics-stat-positive"><strong><?= $sentimentCounts['positive'] ?></strong><span>Positive</span></div>
                  <div class="analytics-stat analytics-stat-neutral"><strong><?= $sentimentCounts['neutral'] ?></strong><span>Neutral</span></div>
                  <div class="analytics-stat analytics-stat-negative"><strong><?= $sentimentCounts['negative'] ?></strong><span>Negative</span></div>
                </div>
              </div>
            </div>

            <div class="card social-panel" id="analytics-section">
              <div class="card-header social-section-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Engagement Analytics</h3>
              </div>
              <div class="card-body">
                <div id="engagement-analytics" class="analytics-stat-grid">
                  <div class="analytics-stat"><strong><?= count($analyticsPosts) ?></strong><span>Posts</span></div>
                  <div class="analytics-stat"><strong><?= $analyticsComments ?></strong><span>Comments</span></div>
                  <div class="analytics-stat"><strong><?= $analyticsReactions ?></strong><span>Reactions</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="social-feature-grid">
          <div class="card social-panel" id="forums-section">
            <div class="card-header social-section-header">
              <h3 class="card-title"><i class="fas fa-comments mr-2"></i>Discussion Forums</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#createForumModal">
                  <i class="fas fa-plus mr-1"></i>Create Forum
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="social-list-compact social-scroll-list">
                <?php if (!empty($recentForums)): ?>
                  <?php foreach ($recentForums as $forum): ?>
                    <div class="social-mini-item" data-social-item="forum">
                      <div class="social-mini-text">
                        <h6><?= htmlspecialchars($forum['title'] ?? 'Untitled Forum') ?></h6>
                        <small><?= htmlspecialchars($forum['category'] ?? 'General') ?></small>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted mb-0">No forums started yet.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="card social-panel" id="groups-section">
            <div class="card-header social-section-header">
              <h3 class="card-title"><i class="fas fa-users mr-2"></i>Team Groups</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createGroupModal">
                  <i class="fas fa-plus mr-1"></i>Create Group
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="social-list-compact social-scroll-list">
                <?php if (!empty($payload['groups'])): ?>
                  <?php foreach (array_slice($payload['groups'], 0, 4) as $group): ?>
                    <?php $groupId = (int)($group['eer_group_id'] ?? 0); ?>
                    <?php $groupMembers = $payload['group_members'][$groupId] ?? []; ?>
                    <div class="social-mini-item social-group-item" data-social-item="group" data-group-id="<?= $groupId ?>" data-group-name="<?= htmlspecialchars((string)($group['name'] ?? 'Untitled Group'), ENT_QUOTES, 'UTF-8') ?>" data-group-members="<?= htmlspecialchars(json_encode($groupMembers), ENT_QUOTES, 'UTF-8') ?>" role="button" tabindex="0" onclick="openGroupMembersModal(this)" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openGroupMembersModal(this); }">
                      <div class="social-mini-text">
                        <h6><?= htmlspecialchars($group['name'] ?? 'Untitled Group') ?></h6>
                        <small class="group-member-count"><?= count($groupMembers) ?> members</small>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted mb-0">No groups created yet.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="card social-panel" id="projects-section">
            <div class="card-header social-section-header">
              <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>Project Collaboration</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createProjectModal">
                  <i class="fas fa-plus mr-1"></i>Create Project
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="social-list-compact social-scroll-list">
                <?php if (!empty($recentProjects)): ?>
                  <?php foreach ($recentProjects as $project): ?>
                    <div class="social-mini-item" data-social-item="project">
                      <div class="social-mini-text">
                        <h6><?= htmlspecialchars($project['name'] ?? 'Untitled Project') ?></h6>
                        <small><?= htmlspecialchars($project['status'] ?? 'planning') ?></small>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted mb-0">No project spaces created yet.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="social-admin-grid">
          <div class="card social-panel" id="moderation-section">
            <div class="card-header social-section-header">
              <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Reports & Moderation</h3>
            </div>
            <div class="card-body">
              <div class="social-list-compact social-scroll-list">
                <?php foreach ($moderationItems as $moderationItem): ?>
                  <?php
                    $moderationTitle = (string)($moderationItem['title'] ?? '');
                    $insightsFilter = $moderationTitle === 'Needs review' ? 'needs-review' : ($moderationTitle === 'Resolved items' ? 'resolved' : 'positive-signals');
                  ?>
                  <?php $moderationCount = (int)($moderationItem['count'] ?? 0); ?>
                  <div class="social-mini-item moderation-insights-trigger<?= $moderationCount === 0 ? ' is-disabled' : '' ?>" data-social-item="post" data-moderation-type="<?= $insightsFilter === 'needs-review' ? 'negative' : ($insightsFilter === 'positive-signals' ? 'positive' : 'resolved') ?>" data-disabled="<?= $moderationCount === 0 ? 'true' : 'false' ?>" role="button" tabindex="<?= $moderationCount === 0 ? '-1' : '0' ?>" data-insights-target="employeeModerationDetails" data-insights-filter="<?= $insightsFilter ?>" aria-disabled="<?= $moderationCount === 0 ? 'true' : 'false' ?>" aria-expanded="false" aria-controls="employeeModerationDetails">
                    <div class="social-mini-text">
                      <h6><?= htmlspecialchars((string)($moderationItem['title'] ?? 'Review item')) ?></h6>
                      <small><?= htmlspecialchars((string)($moderationItem['description'] ?? '')) ?></small>
                    </div>
                    <span class="badge badge-warning" data-moderation-count="<?= $insightsFilter === 'needs-review' ? 'negative' : ($insightsFilter === 'positive-signals' ? 'positive' : 'resolved') ?>"><?= (int)($moderationItem['count'] ?? 0) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>

              <div id="employeeModerationDetails" class="moderation-insights-details" hidden>

              <?php if (!empty($negativeReviewItems)): ?>
                <div class="moderation-employee-list mt-3 pt-3 border-top" data-insights-section="needs-review">
                  <div class="moderation-employee-header">Posts needing review</div>
                  <?php foreach (array_slice($negativeReviewItems, 0, 5) as $reviewItem): ?>
                    <div class="moderation-review-row">
                      <strong><?= htmlspecialchars((string)($reviewItem['employee'] ?? 'Unknown')) ?></strong>
                      <span><?= htmlspecialchars((string)($reviewItem['content'] ?? 'No content')) ?></span>
                      <?php if (!empty($reviewItem['created_at'])): ?>
                        <small><?= htmlspecialchars((string)$reviewItem['created_at']) ?></small>
                      <?php endif; ?>
                      <?php if ((int)($reviewItem['post_id'] ?? 0) > 0): ?>
                        <button type="button" class="btn btn-sm btn-outline-success resolve-post-btn" data-post-id="<?= (int)$reviewItem['post_id'] ?>">
                          <i class="fas fa-check mr-1"></i>Mark resolved
                        </button>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($flaggedEmployees)): ?>
                <div class="moderation-employee-list mt-3 pt-3 border-top" data-insights-section="needs-review">
                  <div class="moderation-employee-header">Flagged employees</div>
                  <?php foreach (array_slice($flaggedEmployees, 0, 5) as $flaggedEmployee): ?>
                    <div class="moderation-employee-row">
                      <span><?= htmlspecialchars((string)($flaggedEmployee['name'] ?? 'Unknown')) ?></span>
                      <span class="badge badge-danger"><?= (int)($flaggedEmployee['negative'] ?? 0) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($positiveEmployees)): ?>
                <div class="moderation-employee-list mt-3 pt-3 border-top" data-insights-section="positive-signals">
                  <div class="moderation-employee-header">Positive employees</div>
                  <?php foreach (array_slice($positiveEmployees, 0, 5) as $positiveEmployee): ?>
                    <div class="moderation-employee-row">
                      <span><?= htmlspecialchars((string)($positiveEmployee['name'] ?? 'Unknown')) ?></span>
                      <span class="badge badge-success"><?= (int)($positiveEmployee['positive'] ?? 0) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($mostActiveContributors)): ?>
                <div class="moderation-employee-list mt-3 pt-3 border-top" data-insights-section="positive-signals">
                  <div class="moderation-employee-header">Most active contributors</div>
                  <?php foreach (array_slice($mostActiveContributors, 0, 5) as $activeContributor): ?>
                    <div class="moderation-employee-row">
                      <span><?= htmlspecialchars((string)($activeContributor['name'] ?? 'Unknown')) ?></span>
                      <span class="badge badge-info"><?= (int)($activeContributor['total'] ?? 0) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($employeeSentimentSummary)): ?>
                <div class="mt-3 pt-3 border-top" data-insights-section="positive-signals">
                  <div class="moderation-employee-header">Per-employee moderation breakdown</div>
                  <div class="table-responsive">
                    <table class="table table-sm moderation-breakdown-table">
                      <thead>
                        <tr>
                          <th><button type="button" class="moderation-sort-btn" data-sort-key="employee">Employee</button></th>
                          <th><button type="button" class="moderation-sort-btn" data-sort-key="positive">Positive</button></th>
                          <th><button type="button" class="moderation-sort-btn" data-sort-key="neutral">Neutral</button></th>
                          <th><button type="button" class="moderation-sort-btn" data-sort-key="negative">Negative</button></th>
                          <th><button type="button" class="moderation-sort-btn" data-sort-key="total">Total</button></th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($employeeSentimentSummary as $employeeName => $stats): ?>
                          <?php
                            $employeePositive = (int)($stats['positive'] ?? 0);
                            $employeeNeutral = (int)($stats['neutral'] ?? 0);
                            $employeeNegative = (int)($stats['negative'] ?? 0);
                            $employeeTotal = $employeePositive + $employeeNeutral + $employeeNegative;
                            $employeeStatus = $employeeNegative > 0 ? 'Flagged' : 'Stable';
                            $employeeStatusClass = $employeeNegative > 0 ? 'badge-danger' : 'badge-success';
                          ?>
                          <tr data-employee-row data-employee="<?= htmlspecialchars((string)$employeeName, ENT_QUOTES, 'UTF-8') ?>" data-positive="<?= $employeePositive ?>" data-neutral="<?= $employeeNeutral ?>" data-negative="<?= $employeeNegative ?>" data-total="<?= $employeeTotal ?>">
                            <td><?= htmlspecialchars((string)$employeeName) ?></td>
                            <td><?= $employeePositive ?></td>
                            <td><?= $employeeNeutral ?></td>
                            <td><?= $employeeNegative ?></td>
                            <td><?= $employeeTotal ?></td>
                            <td><span class="badge <?= $employeeStatusClass ?>"><?= $employeeStatus ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (!empty($resolvedReviewItems)): ?>
                <div class="moderation-employee-list mt-3 pt-3 border-top" data-insights-section="resolved">
                  <div class="moderation-employee-header">Resolved posts</div>
                  <?php foreach (array_slice($resolvedReviewItems, 0, 5) as $resolvedItem): ?>
                    <div class="moderation-review-row moderation-resolved-row">
                      <strong><?= htmlspecialchars((string)($resolvedItem['employee'] ?? 'Unknown')) ?></strong>
                      <span><?= htmlspecialchars((string)($resolvedItem['content'] ?? 'No content')) ?></span>
                      <?php if (!empty($resolvedItem['created_at'])): ?>
                        <small><?= htmlspecialchars((string)$resolvedItem['created_at']) ?></small>
                      <?php endif; ?>
                      <span class="badge badge-success">Resolved</span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="moderation-empty-state mt-3 pt-3 border-top" data-insights-section="resolved">
                  <span class="text-muted">No resolved items yet.</span>
                </div>
              <?php endif; ?>

              </div>
            </div>
          </div>

          <div class="card social-panel" id="activity-log-section">
            <div class="card-header social-section-header">
              <h3 class="card-title"><i class="fas fa-history mr-2"></i>Admin Activity Log</h3>
            </div>
            <div class="card-body">
              <div class="social-list-compact social-scroll-list">
                <?php foreach ($activityItems as $activityItem): ?>
                  <div class="social-mini-item" data-social-item="all">
                    <div class="social-mini-text">
                      <h6><?= htmlspecialchars((string)($activityItem['title'] ?? 'Activity')) ?></h6>
                      <small><?= htmlspecialchars((string)($activityItem['meta'] ?? '')) ?></small>
                    </div>
                    <span class="badge badge-secondary"><?= htmlspecialchars((string)($activityItem['badge'] ?? 'Info')) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-labelledby="createGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="createGroupModalLabel"><i class="fas fa-users mr-2"></i>Create Group</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #6c757d; font-size: 1.8rem; padding: 0; border: none; background: none; cursor: pointer; transition: color 0.3s ease; margin-left: auto;" onmouseover="this.style.color='#495057'" onmouseout="this.style.color='#6c757d'"><span aria-hidden="true">×</span></button>
            </div>
            <form method="post" class="group-create-form">
              <input type="hidden" name="action" value="create_group">
              <div class="modal-body">
                <div class="form-group">
                  <label for="groupName">Group Name</label>
                  <input id="groupName" type="text" name="group_name" class="form-control" placeholder="Enter group name" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Group</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="modal fade" id="groupMembersModal" tabindex="-1" role="dialog" aria-labelledby="groupMembersModalLabel" aria-hidden="true">
        <div class="modal-dialog group-members-modal-dialog" role="document">
          <div class="modal-content group-members-modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="groupMembersModalLabel"><i class="fas fa-users mr-2"></i>Group Members</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.closeSocialModal('groupMembersModal')"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="group-members-modal-layout">
                <section class="group-members-list-panel">
                  <h6>Members</h6>
                  <div id="groupMembersModalList" class="group-members-modal-list"></div>
                </section>
                <section class="group-members-add-panel">
                  <h6>Add member</h6>
                  <form id="group-members-modal-form">
                    <input type="hidden" id="groupMembersModalGroupId" name="group_id">
                    <div class="form-group">
                      <label for="groupMembersModalEmployeeId">Employee</label>
                      <select id="groupMembersModalEmployeeId" name="employee_id" class="form-control" required>
                        <option value="">Select employee</option>
                        <?php foreach ($payload['employees'] ?? [] as $employee): ?>
                          <?php $employeeName = trim(implode(' ', array_filter([$employee['first_name'] ?? '', $employee['middle_name'] ?? '', $employee['last_name'] ?? '']))); ?>
                          <?php if ($employeeName === '') { $employeeName = (string)($employee['email'] ?? 'Unnamed Employee'); } ?>
                          <option value="<?= (int)($employee['employee_id'] ?? 0) ?>"><?= htmlspecialchars($employeeName) ?> (ID: <?= (int)($employee['employee_id'] ?? 0) ?>)</option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus mr-1"></i>Add member</button>
                  </form>
                </section>
              </div>
            </div>
          </div>
        </div>
      </div>

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1" role="dialog" aria-labelledby="createProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header" style="display: flex; align-items: center;">
        <h5 class="modal-title" id="createProjectModalLabel"><i class="fas fa-sitemap mr-2"></i>Create Project Space</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #6c757d; font-size: 1.8rem; padding: 0; border: none; background: none; cursor: pointer; transition: color 0.3s ease; margin-left: auto;" onmouseover="this.style.color='#495057'" onmouseout="this.style.color='#6c757d'"><span aria-hidden="true">×</span></button>
      </div>
      <form id="createProjectForm">
        <div class="modal-body">
          <div class="form-group"><label for="projectName">Project Name</label><input id="projectName" type="text" class="form-control" required maxlength="255"></div>
          <div class="form-group"><label for="projectDescription">Description</label><textarea id="projectDescription" class="form-control" rows="4" required></textarea></div>
          <div class="form-row">
            <div class="form-group col-md-7"><label for="projectDeadline">Deadline</label><input id="projectDeadline" type="date" class="form-control" required></div>
            <div class="form-group col-md-5"><label for="projectDeadlineTime">Time</label><input id="projectDeadlineTime" type="time" class="form-control" required></div>
          </div>
          <div class="form-group"><label for="projectStatus">Status</label><select id="projectStatus" class="form-control"><option value="planning">Planning</option><option value="active">Active</option><option value="on-hold">On Hold</option><option value="completed">Completed</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Create Project Space</button></div>
      </form>
    </div>
  </div>
</div>

  <!-- View Project Modal -->
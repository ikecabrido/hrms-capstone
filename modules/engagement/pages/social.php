<?php

require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';


use App\Controllers\SocialController;
use App\Controllers\ReactionController;
use App\Controllers\ReplyController;
use App\Controllers\GroupController;
use App\Controllers\GroupMemberController;


$role = strtolower(trim($_SESSION['user']['role'] ?? ''));
$isHrAdmin = $role === 'admin' || $role === 'hr_admin' || strpos($role, 'hr') !== false || strpos($role, 'admin') !== false;
$currentEmployeeId = $_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? null;

$ctrl = new SocialController();
$reactionCtrl = new ReactionController();
$replyCtrl = new ReplyController();
$groupCtrl = new GroupController();
$groupMemberCtrl = new GroupMemberController();

$payload = $ctrl->getPageData();

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $employeeId = $_SESSION['user']['employee_id']
    ?? $_SESSION['employee_id']
    ?? $currentEmployeeId;
  $userId = $_SESSION['user']['id']
    ?? $_SESSION['user']['user_id']
    ?? $_SESSION['user_id']
    ?? null;
  $action = $_POST['action'] ?? '';
  $authorId = $employeeId ?: $userId;
  $userType = $employeeId ? 'employee' : 'user';

  if ($action === 'share_update') {
    if (!$authorId) {
      $_SESSION['flash_error'] = 'Unable to determine author.';
    } else {
      try {
        $messages = $ctrl->publishUpdate(
          $authorId,
          trim((string)($_POST['content'] ?? '')),
          $userType,
          $_FILES['shared_file'] ?? null,
          $_POST['description'] ?? ''
        );
        $_SESSION['flash_success'] = implode(' ', $messages);
      } catch (Throwable $exception) {
        $_SESSION['flash_error'] = $exception->getMessage();
      }
    }
  } elseif ($action === 'comment' && !empty($_POST['comment']) && !empty($_POST['post_id'])) {
        $commentText = trim($_POST['comment']);
        if ($authorId) {
            $ctrl->addComment((int)$_POST['post_id'], $authorId, $commentText, $userType);
            $_SESSION['flash_success'] = 'Comment added successfully.';
        } else {
            $_SESSION['flash_error'] = 'Unable to determine author.';
        }
        } elseif ($action === 'reply' && !empty($_POST['comment_id']) && !empty($_POST['post_id']) && !empty($_POST['content'])) {
          if ($authorId) {
            $replyCtrl->addReply(
              (int)$_POST['comment_id'],
              (int)$_POST['post_id'],
              $authorId,
              trim($_POST['content']),
              $userType
            );
            $_SESSION['flash_success'] = 'Reply added successfully.';
          } else {
            $_SESSION['flash_error'] = 'Unable to determine author.';
          }
    }

    if ($action === 'reaction' && !empty($_POST['reaction_type']) && !empty($_POST['post_id'])) {
        $reactionType = $_POST['reaction_type'];
        $postId = (int)$_POST['post_id'];

      if (!$authorId) {
        $_SESSION['flash_error'] = 'Unable to determine author.';
      } elseif ($userType === 'employee') {
            $reactionCtrl->addReaction($postId, $authorId, null, $reactionType);
        } else {
            $reactionCtrl->addReaction($postId, null, $authorId, $reactionType);
        }
        $_SESSION['flash_success'] = 'Reaction added successfully.';
    }

    if ($action === 'create_group' && !empty($_POST['group_name'])) {
        $groupName = trim($_POST['group_name']);
      if ($authorId) {
        $groupCtrl->createGroup($groupName, null, $authorId);
        $_SESSION['flash_success'] = 'Group created successfully.';
      } else {
        $_SESSION['flash_error'] = 'Unable to determine author.';
      }
    } elseif ($action === 'add_member' && !empty($_POST['group_id']) && !empty($_POST['employee_id'])) {
        $groupId = (int)$_POST['group_id'];
        $employeeIdValue = $_POST['employee_id'];
        $groupMemberCtrl->addMember($groupId, $employeeIdValue);
        $_SESSION['flash_success'] = 'Member added to group successfully.';
    }

    $refreshUrl = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
    echo '<script>window.location.replace(' . json_encode($refreshUrl) . ');</script>';
    exit;
}
?>
    <link rel="stylesheet" href="pages/css/style/social.css">
    <style>
      #forums-list .forum-card + .forum-card {
        margin-top: 1rem;
      }

      #projects-list .project-card + .project-card {
        margin-top: 1rem;
      }
    </style>

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
<script>
(function () {
  function initializeSocialActions() {
    var apiBase = 'api/index.php';
    var feed = document.getElementById('social-feed');
    if (!feed) return;
    if (feed.dataset.socialInlineBound === '1') return;
    feed.dataset.socialInlineBound = '1';

  function readResponse(response) {
    return response.text().then(function (text) {
      var data;
      try {
        data = JSON.parse(text);
      } catch (error) {
        throw new Error('Invalid server response.');
      }
      if (!response.ok || data.success === false) {
        throw new Error(data.error || data.message || 'Request failed.');
      }
      return data;
    });
  }

  function escapeHtml(value) {
    var element = document.createElement('div');
    element.textContent = value == null ? '' : String(value);
    return element.innerHTML;
  }

  function setBusy(form, busy) {
    var button = form.querySelector('button[type="submit"]');
    if (!button) return;
    if (busy) {
      button.dataset.label = button.textContent;
      button.textContent = 'Sending...';
      button.disabled = true;
    } else {
      button.textContent = button.dataset.label || 'Submit';
      button.disabled = false;
    }
  }

  function addPost(content) {
    var card = document.createElement('div');
    card.className = 'card mb-3 social-post-card';
    card.style.borderLeft = '4px solid #007bff';
    card.innerHTML = '<div class="card-body">' +
      '<div class="post-header"><div><h6 class="card-title mb-1">You</h6><small class="text-muted">Just now</small></div></div>' +
      '<p class="card-text mb-3">' + escapeHtml(content).replace(/\n/g, '<br>') + '</p>' +
      '<div class="reaction-summary"><small class="text-muted"><i class="fas fa-thumbs-up text-primary mr-1"></i>0 <i class="fas fa-heart text-danger mr-1 ml-2"></i>0 <i class="fas fa-star text-warning mr-1 ml-2"></i>0</small></div>' +
      '<details class="social-comments-panel"><summary><span><i class="fas fa-comments mr-1"></i> Comments</span><span class="comment-count">0</span></summary><div class="comments-section"><p class="text-muted font-italic small mb-0">No comments yet.</p></div></details>' +
      '</div>';
    feed.insertBefore(card, feed.firstChild);
  }

  function addComment(form, text) {
    var panel = form.closest('.social-comments-panel');
    if (!panel) return;
    var comments = panel.querySelector('.comments-section');
    var empty = comments.querySelector('.text-muted.font-italic');
    if (empty) empty.remove();
    var item = document.createElement('div');
    item.className = 'comment-item';
    item.innerHTML = '<div><strong class="small">You:</strong> <span class="small">' + escapeHtml(text) + '</span></div>' +
      '<small class="text-muted d-block mb-2">Just now</small>';
    comments.appendChild(item);
    panel.open = true;
    var count = panel.querySelector('.comment-count');
    if (count) count.textContent = String(parseInt(count.textContent || '0', 10) + 1);
  }

  document.querySelectorAll('.comment-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var text = form.querySelector('[name="comment"]').value.trim();
      var postId = form.querySelector('[name="post_id"]').value;
      if (!text) return;
      setBusy(form, true);
      fetch(apiBase + '?resource=social&action=comment', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({post_id: postId, comment: text})
      }).then(readResponse).then(function () {
        addComment(form, text);
        form.reset();
      }).catch(function (error) {
        alert(error.message);
      }).finally(function () {
        setBusy(form, false);
      });
    });
  });

  document.querySelectorAll('.reply-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var text = form.querySelector('[name="content"]').value.trim();
      if (!text) return;
      setBusy(form, true);
      fetch(apiBase + '?resource=reply&action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          comment_id: form.querySelector('[name="comment_id"]').value,
          post_id: form.querySelector('[name="post_id"]').value,
          content: text
        })
      }).then(readResponse).then(function () {
        var item = document.createElement('div');
        item.className = 'reply-item';
        item.innerHTML = '<strong class="small">You:</strong> <span class="small">' + escapeHtml(text) + '</span><small class="text-muted d-block">Just now</small>';
        form.closest('.comment-item').insertBefore(item, form.closest('.reply-panel'));
        form.reset();
      }).catch(function (error) {
        alert(error.message);
      }).finally(function () {
        setBusy(form, false);
      });
    });
  });

  document.querySelectorAll('.reaction-buttons form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var button = form.querySelector('button');
      var count = button ? button.querySelector('.reaction-count') : null;
      setBusy(form, true);
      fetch(apiBase + '?resource=reaction', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          post_id: form.querySelector('[name="post_id"]').value,
          type: form.querySelector('[name="reaction_type"]').value
        })
      }).then(readResponse).then(function (data) {
        var status = data.result && data.result.status;
        if (count) {
          var current = parseInt(count.textContent || '0', 10);
          if (status === 'removed') current = Math.max(0, current - 1);
          if (status === 'added') current += 1;
          count.textContent = String(current);
        }
        if (typeof window.fetchSocialFeed === 'function') {
          window.fetchSocialFeed();
        }
        window.location.reload();
      }).catch(function (error) {
        alert(error.message);
      }).finally(function () {
        setBusy(form, false);
      });
    });
  });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSocialActions, {once: true});
  } else {
    initializeSocialActions();
  }
})();
</script>
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

      <!-- Social Collaboration Tools Tabs -->
      <!-- Social Collaboration Tools Tabs -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header p-0">
        <ul class="nav nav-tabs" id="collaboration-tabs" role="tablist">

          <li class="nav-item">
            <a class="nav-link"
               id="feed-tab"
               data-toggle="tab"
               href="#feed"
               role="tab"
               aria-controls="feed"
               aria-selected="false">
              <i class="fas fa-rss mr-2"></i>
              Employee Interaction Feed
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link"
               id="forums-tab"
               data-toggle="tab"
               href="#forums"
               role="tab"
               aria-controls="forums"
               aria-selected="false">
              <i class="fas fa-comments mr-2"></i>
              Discussion Forums
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link"
               id="groups-tab"
               data-toggle="tab"
               href="#groups"
               role="tab"
               aria-controls="groups"
               aria-selected="false">
              <i class="fas fa-users mr-2"></i>
              Team Groups
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link"
               id="projects-tab"
               data-toggle="tab"
               href="#projects"
               role="tab"
               aria-controls="projects"
               aria-selected="false">
              <i class="fas fa-project-diagram mr-2"></i>
              Project Collaboration Spaces
            </a>
          </li>

        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content" id="collaboration-tab-content">

          <!-- Employee Interaction Feed Tab -->
          <div class="tab-pane fade"
               id="feed"
               role="tabpanel"
               aria-labelledby="feed-tab">
                  <div class="row social-share-layout">
                    <div class="col-lg-5 col-md-12 mb-4 mb-lg-0">
                      <div class="card card-info card-outline h-100">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-share-alt mr-2"></i>Share an Update</h3></div>
                        <div class="card-body">
                          <form method="post" enctype="multipart/form-data" class="share-form" data-skip>
                            <input type="hidden" name="action" value="share_update">
                            <div class="form-group">
                              <label for="content">Post / Update</label>
                              <textarea id="content" class="form-control" name="content" rows="4" placeholder="Share something with your team..." ></textarea>
                            </div>
                            <div class="form-row">
                              <div class="form-group col-md-6">
                                <label for="file-upload">Attach File</label>
                                <input id="file-upload" type="file" name="shared_file" class="form-control" />
                              </div>
                              <div class="form-group col-md-6">
                                <label for="file-description">Description (optional)</label>
                                <textarea id="file-description" name="description" class="form-control" rows="2" placeholder="Add a description for the file..."></textarea>
                              </div>
                            </div>
                            <div id="share-status" class="mb-3"></div>
                            <button class="btn btn-primary" type="submit">Share</button>
                          </form>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-7 col-md-12">
                      <div class="card card-info card-outline h-100">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-stream mr-2"></i>Social Feed</h3></div>
                        <div class="card-body">
                          <div id="social-feed" data-can-reply="true" data-employee-id="<?= htmlspecialchars((string)($currentEmployeeId ?? '')) ?>">
                            <?php if (!empty($payload['feed']) || !empty($payload['shared_files'])): ?>
                              <?php foreach ($payload['feed'] ?? [] as $post): ?>
                                <div class="card mb-3 social-post-card" style="border-left: 4px solid #007bff;">
                                  <div class="card-body">
                                    <div class="post-header d-flex justify-content-between align-items-start mb-3">
                                      <div>
                                        <h6 class="card-title mb-1" style="font-weight: 600;"><?= htmlspecialchars($post['author_name'] ?? 'Unknown') ?></h6>
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
                                          <a href="download.php?id=<?= (int)$post['eer_social_post_id'] ?>" class="btn btn-sm btn-outline-primary shared-file-download" download>Download</a>
                                        </div>
                                      </div>
                                    <?php endif; ?>
                                    <div class="reaction-summary border-top border-bottom py-2 px-0 mb-3">
                                      <small class="text-muted">
                                        <i class="fas fa-thumbs-up text-primary mr-1"></i><?= (int)($post['like_count'] ?? 0) ?>
                                        <i class="fas fa-heart text-danger mr-1 ml-2"></i><?= (int)($post['heart_count'] ?? 0) ?>
                                        <i class="fas fa-star text-warning mr-1 ml-2"></i><?= (int)($post['wow_count'] ?? 0) ?>
                                      </small>
                                    </div>
                                    <div class="reaction-buttons mt-3 d-flex gap-2">
                                      <?php $postId = (int)($post['eer_social_post_id'] ?? 0); ?>
                                      <?php foreach (['like' => 'primary fa-thumbs-up', 'heart' => 'danger fa-heart', 'wow' => 'warning fa-star'] as $reactionType => $reactionStyle): ?>
                                        <?php [$buttonStyle, $icon] = explode(' ', $reactionStyle, 2); ?>
                                        <form method="post" class="d-inline" data-skip>
                                          <input type="hidden" name="action" value="reaction">
                                          <input type="hidden" name="post_id" value="<?= $postId ?>">
                                          <input type="hidden" name="reaction_type" value="<?= $reactionType ?>">
                                          <button type="submit" class="btn btn-sm btn-outline-<?= $buttonStyle ?>"><i class="fas <?= $icon ?> mr-1"></i><?= ucfirst($reactionType) ?> <span class="reaction-count"><?= (int)($post[$reactionType . '_count'] ?? 0) ?></span></button>
                                        </form>
                                      <?php endforeach; ?>
                                    </div>
                                    <?php $commentCount = count($post['comments'] ?? []); ?>
                                    <details class="social-comments-panel">
                                      <summary>
                                        <span><i class="fas fa-comments mr-1"></i> Comments</span>
                                        <span class="comment-count"><?= $commentCount ?></span>
                                      </summary>
                                      <div class="comments-section">
                                        <?php if ($commentCount > 0): ?>
                                          <?php foreach ($post['comments'] as $comment): ?>
                                            <div class="comment-item">
                                              <div><strong class="small"><?= htmlspecialchars($comment['author_name'] ?? 'Unknown') ?>:</strong> <span class="small"><?= htmlspecialchars($comment['comment'] ?? '') ?></span></div>
                                              <small class="text-muted d-block mb-2"><?= htmlspecialchars($comment['created_at'] ?? '') ?></small>
                                              <?php foreach ($comment['replies'] ?? [] as $reply): ?>
                                                <div class="reply-item"><strong class="small"><?= htmlspecialchars($reply['author_name'] ?? 'Unknown') ?>:</strong> <span class="small"><?= htmlspecialchars($reply['content'] ?? '') ?></span><small class="text-muted d-block"><?= htmlspecialchars($reply['created_at'] ?? '') ?></small></div>
                                              <?php endforeach; ?>
                                              <details class="reply-panel">
                                                <summary><i class="fas fa-reply mr-1"></i> Reply</summary>
                                                <form method="POST" class="reply-form" data-skip data-comment-id="<?= (int)($comment['eer_comment_id'] ?? 0) ?>" data-post-id="<?= $postId ?>">
                                                  <input type="hidden" name="action" value="reply">
                                                  <input type="hidden" name="comment_id" value="<?= (int)($comment['eer_comment_id'] ?? 0) ?>">
                                                  <input type="hidden" name="post_id" value="<?= $postId ?>">
                                                  <textarea name="content" class="form-control form-control-sm" rows="2" placeholder="Write your reply..." required></textarea>
                                                  <button type="submit" class="btn btn-sm btn-primary">Post Reply</button>
                                                </form>
                                              </details>
                                            </div>
                                          <?php endforeach; ?>
                                        <?php else: ?>
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
                              <?php endforeach; ?>
                              <?php foreach ($payload['shared_files'] ?? [] as $file): ?>
                                <div class="card mb-3 shared-file-card" style="border-left: 4px solid #17a2b8;">
                                  <div class="card-body">
                                    <h5 class="card-title mb-1"><i class="fas fa-file mr-2 text-primary"></i><?= htmlspecialchars($file['file_name'] ?? 'Shared file') ?></h5>
                                    <span class="badge badge-info mb-2">Shared File</span>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($file['content'] ?? $file['description'] ?? 'No description provided.')) ?></p>
                                    <div class="shared-file-meta">
                                      <p class="text-muted small mb-0">Uploaded by <?= htmlspecialchars($file['uploader_name'] ?? 'Unknown') ?> | <?= htmlspecialchars($file['created_at'] ?? '') ?></p>
                                      <a href="download.php?id=<?= (int)($file['eer_social_post_id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary shared-file-download" download><i class="fas fa-download mr-1"></i>Download</a>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <p class="text-muted">No posts or shared files yet.</p>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <?php
                  $analyticsPosts = $payload['feed'] ?? [];
                  $analyticsComments = 0;
                  $analyticsReactions = 0;
                  $sentimentCounts = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
                  $positiveWords = ['good', 'great', 'love', 'excellent', 'awesome', 'happy', 'nice', 'amazing'];
                  $negativeWords = ['bad', 'sad', 'angry', 'terrible', 'hate', 'poor', 'worst', 'problem'];
                  foreach ($analyticsPosts as $analyticsPost) {
                    $analyticsComments += count($analyticsPost['comments'] ?? []);
                    $analyticsReactions += (int)($analyticsPost['like_count'] ?? 0) + (int)($analyticsPost['heart_count'] ?? 0) + (int)($analyticsPost['wow_count'] ?? 0);
                    $analyticsText = strtolower((string)($analyticsPost['content'] ?? '') . ' ' . implode(' ', array_column($analyticsPost['comments'] ?? [], 'comment')));
                    $hasPositive = false;
                    $hasNegative = false;
                    foreach ($positiveWords as $word) $hasPositive = $hasPositive || strpos($analyticsText, $word) !== false;
                    foreach ($negativeWords as $word) $hasNegative = $hasNegative || strpos($analyticsText, $word) !== false;
                    if ($hasPositive && !$hasNegative) $sentimentCounts['positive']++;
                    elseif ($hasNegative && !$hasPositive) $sentimentCounts['negative']++;
                    else $sentimentCounts['neutral']++;
                  }
                  ?>
                  <div class="row analytics-pair-layout">
                    <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                      <div class="card card-info card-outline h-100">
                        <div class="card-header"><h3 class="card-title">Sentiment Analysis</h3></div>
                        <div class="card-body" id="sentiment-analysis">
                          <div class="analytics-stat-grid">
                            <div class="analytics-stat analytics-stat-positive"><strong><?= $sentimentCounts['positive'] ?></strong><span>Positive</span></div>
                            <div class="analytics-stat analytics-stat-neutral"><strong><?= $sentimentCounts['neutral'] ?></strong><span>Neutral</span></div>
                            <div class="analytics-stat analytics-stat-negative"><strong><?= $sentimentCounts['negative'] ?></strong><span>Negative</span></div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                      <div class="card card-secondary card-outline h-100">
                        <div class="card-header"><h3 class="card-title">Engagement Analytics</h3></div>
                        <div class="card-body" id="engagement-analytics">
                          <div class="analytics-stat-grid">
                            <div class="analytics-stat"><strong><?= count($analyticsPosts) ?></strong><span>Posts</span></div>
                            <div class="analytics-stat"><strong><?= $analyticsComments ?></strong><span>Comments</span></div>
                            <div class="analytics-stat"><strong><?= $analyticsReactions ?></strong><span>Reactions</span></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Discussion Forums Tab -->
          <div class="tab-pane fade"
               id="forums"
               role="tabpanel"
               aria-labelledby="forums-tab">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-warning card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-comments mr-2"></i>Discussion Forums</h3>
                          <div class="card-tools">
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#createForumModal">
                              <i class="fas fa-plus mr-1"></i>Create Forum
                            </button>
                          </div>
                        </div>
                        <div class="card-body">
                          <div id="forums-list">
                            <?php if (empty($payload['forums'])): ?>
                              <div class="alert alert-info">No forums available yet.</div>
                            <?php else: ?>
                              <?php foreach ($payload['forums'] as $forum): ?>
                                <div class="card mb-3 forum-card">
                                  <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                      <div>
                                        <h5 class="mb-1" style="font-weight:600;">
                                          <?= htmlspecialchars($forum['title'] ?? 'Untitled Forum') ?>
                                        </h5>
                                        <p class="mb-1 text-muted"><?= htmlspecialchars($forum['description'] ?? '') ?></p>
                                        <small class="text-muted">
                                          Category: <?= htmlspecialchars($forum['category'] ?? 'General') ?>
                                        </small>
                                      </div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small">
                                      <span>
                                        Created by: <?= htmlspecialchars($forum['creator_name'] ?? $forum['created_by_employee_id'] ?? 'Unknown') ?>
                                      </span>
                                      <span><?= htmlspecialchars($forum['created_at'] ?? '') ?></span>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Team Groups Tab -->
          <!-- Team Groups Tab -->
          <div class="tab-pane fade"
               id="groups"
               role="tabpanel"
               aria-labelledby="groups-tab">                  <div class="row">
                    <div class="col-12">
                      <div class="card card-primary card-outline">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-users mr-2"></i>Manage Groups</h3></div>
                        <div class="card-body">
                          <div class="row group-setup-layout">
                            <div class="col-lg-7 col-md-12">
                              <div class="group-form-stack">
                                <form method="post" class="group-create-form">
                                  <input type="hidden" name="action" value="create_group">
                                  <div class="form-group">
                                    <label for="group-name">Group Name</label>
                                    <input id="group-name" type="text" name="group_name" class="form-control" placeholder="Enter group name" required>
                                  </div>
                                  <button class="btn btn-primary" type="submit">Create Group</button>
                                </form>

                                <?php if (!empty($payload['groups'])): ?>
                                  <form id="group-member-form" method="post" class="group-member-form" data-skip>
                                    <input type="hidden" name="action" value="add_member">
                                    <div class="form-group">
                                      <label for="group-id">Group</label>
                                      <select id="group-id" name="group_id" class="form-control" required>
                                        <option value="">Choose group</option>
                                        <?php foreach ($payload['groups'] as $group): ?>
                                          <option value="<?= htmlspecialchars($group['eer_group_id']) ?>"><?= htmlspecialchars($group['name'] . ' (ID: ' . $group['eer_group_id'] . ')') ?></option>
                                        <?php endforeach; ?>
                                      </select>
                                    </div>
                                    <div class="form-group">
                                      <label for="employee-id">Employee</label>
                                      <select id="employee-id" name="employee_id" class="form-control" required>
                                        <option value="">Choose employee</option>
                                        <?php foreach ($payload['employees'] as $employee): ?>
                                          <option value="<?= htmlspecialchars($employee['employee_id']) ?>"><?= htmlspecialchars($employee['employee_id'] . ' - ' . ($employee['full_name'] ?? 'No name')) ?></option>
                                        <?php endforeach; ?>
                                      </select>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Add Member</button>
                                  </form>
                                <?php else: ?>
                                  <div class="alert alert-warning mt-3">
                                    Create a group first before adding members.
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>

                            <?php if (!empty($payload['employees'])): ?>
                              <div class="col-lg-5 col-md-12">
                                <div class="employee-list-panel h-100">
                                  <h5 class="employee-list-title">Employee list</h5>
                                  <ul class="employee-list">
                                    <?php foreach ($payload['employees'] as $employee): ?>
                                      <li class="employee-list-item"><?= htmlspecialchars($employee['employee_id'] . ' - ' . ($employee['full_name'] ?? 'No name')) ?></li>
                                    <?php endforeach; ?>
                                  </ul>
                                </div>
                              </div>
                            <?php endif; ?>
                          </div>

                          <div class="mt-4">
                            <div class="card card-success card-outline">
                              <div class="card-header"><h3 class="card-title">Existing Groups</h3></div>
                              <div class="card-body">
                                <?php if (!empty($payload['groups'])): ?>
                                  <div class="existing-groups-grid">
                                    <?php foreach ($payload['groups'] as $group): ?>
                                      <div class="existing-group-card" data-group-id="<?= htmlspecialchars($group['eer_group_id']) ?>">
                                        <h5 class="mb-1"><?= htmlspecialchars($group['name'] ?? 'Untitled Group') ?></h5>
                                        <p class="mb-1 text-muted">ID: <?= htmlspecialchars($group['eer_group_id'] ?? 'N/A') ?></p>
                                        <?php $members = $payload['group_members'][(int)($group['eer_group_id'] ?? 0)] ?? []; ?>
                                        <p class="mb-1"><strong>Members:</strong></p>
                                        <div id="group-members-<?= htmlspecialchars($group['eer_group_id']) ?>">
                                          <?php if (!empty($members)): ?>
                                            <ul class="list-group list-group-flush">
                                              <?php foreach ($members as $member): ?>
                                                <li class="list-group-item py-1">
                                                  Employee ID: <?= htmlspecialchars($member['employee_id'] ?? 'N/A') ?>
                                                  <?php if (!empty($member['full_name'])): ?>
                                                    - <?= htmlspecialchars($member['full_name']) ?>
                                                  <?php endif; ?>
                                                </li>
                                              <?php endforeach; ?>
                                            </ul>
                                          <?php else: ?>
                                            <p class="text-muted mb-0">No members yet.</p>
                                          <?php endif; ?>
                                        </div>
                                      </div>
                                    <?php endforeach; ?>
                                  </div>
                                <?php else: ?>
                                  <p class="text-muted">No groups created yet.</p>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Project Collaboration Spaces Tab -->
                <div class="tab-pane fade" id="projects" role="tabpanel" aria-labelledby="projects-tab">                  
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-success card-outline">
                        <div class="card-header">
                          <h3 class="card-title"><i class="fas fa-project-diagram mr-2"></i>Project Collaboration Spaces</h3>
                          <div class="card-tools">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createProjectModal">
                              <i class="fas fa-plus mr-1"></i>Create Project Space
                            </button>
                          </div>
                        </div>
                        <div class="card-body">
                          <div id="projects-list">
                            <?php if (empty($payload['projects'])): ?>
                              <div class="alert alert-info">No project spaces available yet.</div>
                            <?php else: ?>
                              <?php foreach ($payload['projects'] as $project): ?>
                                <div class="card mb-3 project-card">
                                  <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                      <div>
                                        <h5 class="mb-1" style="font-weight:600;">
                                          <?= htmlspecialchars($project['name'] ?? 'Untitled Project') ?>
                                        </h5>
                                        <p class="mb-1 text-muted"><?= htmlspecialchars($project['description'] ?? '') ?></p>
                                        <div class="d-flex flex-wrap align-items-center">
                                          <?php
                                            $projectStatus = strtolower((string)($project['status'] ?? 'unknown'));
                                            $statusClasses = [
                                              'active' => 'badge-success',
                                              'completed' => 'badge-primary',
                                              'on-hold' => 'badge-warning',
                                              'planning' => 'badge-info',
                                            ];
                                            $statusLabels = [
                                              'active' => 'Active',
                                              'completed' => 'Completed',
                                              'on-hold' => 'On Hold',
                                              'planning' => 'Planning',
                                            ];
                                          ?>
                                          <span class="badge <?= htmlspecialchars($statusClasses[$projectStatus] ?? 'badge-secondary') ?>">
                                            <?= htmlspecialchars($statusLabels[$projectStatus] ?? ucfirst($projectStatus)) ?>
                                          </span>
                                          <small class="text-muted ml-3">
                                            Deadline: <?= htmlspecialchars($project['deadline'] ?? 'Not set') ?>
                                          </small>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small">
                                      <span>
                                        Created by: <?= htmlspecialchars($project['creator_name'] ?? $project['created_by_employee_id'] ?? 'Unknown') ?>
                                      </span>
                                      <span><?= htmlspecialchars($project['created_at'] ?? '') ?></span>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </div>
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
        <h5 class="modal-title" id="createProjectModalLabel"><i class="fas fa-project-diagram mr-2"></i>Create Project Space</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #6c757d; font-size: 1.8rem; padding: 0; border: none; background: none; cursor: pointer; transition: color 0.3s ease; margin-left: auto;" onmouseover="this.style.color='#495057'" onmouseout="this.style.color='#6c757d'"><span aria-hidden="true">×</span></button>
      </div>
      <form id="createProjectForm">
        <div class="modal-body">
          <div class="form-group"><label for="projectName">Project Name</label><input id="projectName" type="text" class="form-control" required maxlength="255"></div>
          <div class="form-group"><label for="projectDescription">Description</label><textarea id="projectDescription" class="form-control" rows="4" required></textarea></div>
          <div class="form-group"><label for="projectDeadline">Deadline</label><input id="projectDeadline" type="date" class="form-control"></div>
          <div class="form-group"><label for="projectStatus">Status</label><select id="projectStatus" class="form-control"><option value="planning">Planning</option><option value="active">Active</option><option value="on-hold">On Hold</option><option value="completed">Completed</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Create Project Space</button></div>
      </form>
    </div>
  </div>
</div>

  <!-- View Project Modal -->

              </div>
            </div>
          </div>
        </div>
    </div>

   
    </div>

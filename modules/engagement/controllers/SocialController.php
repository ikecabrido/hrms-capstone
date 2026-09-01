<?php
namespace App\Controllers;

use App\Models\SocialPost;
use App\Models\Comment;
use App\Models\Employee;
use App\Models\Reaction;
use App\Models\Reply;
use App\Models\SharedFile;

class SocialController
{
    private $post;
    private $comment;
    private $reaction;
    private $reply;
    private $employee;
    private $sharedFile;

    public function __construct()
    {
        $this->post = new SocialPost();
        $this->comment = new Comment();
        $this->reaction = new Reaction();
        $this->reply = new Reply();
        $this->employee = new Employee();
        $this->sharedFile = new SharedFile();
    }

    public function getPageData()
    {
        $forums = (new ForumController())->getForums();
        $projects = (new ProjectController())->getProjects();
        $groups = (new GroupController())->getGroups();
        $groupMembers = [];
        $groupMemberController = new GroupMemberController();

        foreach ($groups as $group) {
            $groupId = (int)($group['eer_group_id'] ?? 0);
            if ($groupId > 0) {
                $groupMembers[$groupId] = $groupMemberController->getMembersByGroup($groupId);
            }
        }

        $employees = array_map(function ($employee) {
            $employee['full_name'] = trim(implode(' ', array_filter([
                $employee['first_name'] ?? '',
                $employee['middle_name'] ?? '',
                $employee['last_name'] ?? ''
            ])));
            return $employee;
        }, $this->employee->all());

        return [
            'feed' => $this->getPosts(),
            'shared_files' => $this->sharedFile->getAllFiles(),
            'forums' => $forums,
            'projects' => $projects,
            'groups' => $groups,
            'group_members' => $groupMembers,
            'employees' => $employees,
        ];
    }

    public function getPosts()
    {
        $posts = $this->post->getPosts();
        foreach ($posts as &$p) {
            $p['comments'] = $this->comment->getComments($p['eer_social_post_id']);
            foreach ($p['comments'] as &$comment) {
                $comment['replies'] = $this->reply->getRepliesByComment($comment['eer_comment_id']);
            }
        }
        return $posts;
    }

    public function createPost($author_id, $content, $author_type = 'employee', $description = '')
    {
        return $this->post->createPost($author_id, $content, $author_type, $description);
    }

    public function publishUpdate($authorId, $content, $authorType, $uploadedFile = null, $description = '')
    {
        $messages = [];
        $hasFile = $uploadedFile && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($content === '' && !$hasFile) {
            throw new \InvalidArgumentException('Please add a message or attach a file before sharing.');
        }

        if ($hasFile) {
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'xlsx', 'xls'];
            $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

            if (($uploadedFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new \RuntimeException('File upload failed.');
            }
            if (!in_array($extension, $allowed, true)) {
                throw new \InvalidArgumentException('Invalid file type. Allowed: ' . implode(', ', $allowed));
            }
            if (($uploadedFile['size'] ?? 0) > 10 * 1024 * 1024) {
                throw new \InvalidArgumentException('File too large. Max size is 10MB.');
            }

            $uploadDirectory = __DIR__ . '/../../uploads/social_files/';
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                throw new \RuntimeException('Failed to create upload directory.');
            }

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($uploadedFile['name']));
            $storedName = time() . '_' . $safeName;
            $targetPath = $uploadDirectory . $storedName;

            if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                throw new \RuntimeException('Failed to store uploaded file.');
            }

            $fileId = $this->sharedFile->createFile(
                $safeName,
                'uploads/social_files/' . $storedName,
                (int)$uploadedFile['size'],
                $extension,
                $authorId,
                trim($description),
                $content !== '' ? $content : null
            );

            if (!$fileId) {
                @unlink($targetPath);
                throw new \RuntimeException('Failed to save file information.');
            }
            $messages[] = 'File shared successfully.';
        }

        if ($content !== '') {
            $this->createPost($authorId, $content, $authorType, trim($description));
            $messages[] = 'Post published successfully.';
        }

        return $messages;
    }

    public function addReaction($post_id, $employee_id, $user_id, $reaction_type)
    {
        return $this->reaction->addReaction($post_id, $employee_id, $user_id, $reaction_type);
    }

    public function addComment($post_id, $author_id, $comment, $author_type = 'employee')
    {
        return $this->comment->addComment($post_id, $author_id, $comment, $author_type);
    }

    public function deletePost($post_id)
    {
        $this->post->deletePost($post_id);
    }

    public function editPost($post_id, $content)
    {
        $this->post->editPost($post_id, $content);
    }

    public function getPostAnalytics($postId)
    {
        return $this->post->getAnalytics($postId);
    }
}

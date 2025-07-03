<?php
session_start();

// Path to the file storing comments.
$commentsFile = 'comments.txt';

// Loads comments from the comments file, ensuring necessary keys are present.
function loadComments() {
    global $commentsFile;
    if (file_exists($commentsFile)) {
        $json = file_get_contents($commentsFile);
        $comments = json_decode($json, true) ?: [];

        foreach ($comments as &$comment) {
            if (!isset($comment['parent_id'])) {
                $comment['parent_id'] = null;
            }
            if (!isset($comment['likes'])) {
                $comment['likes'] = 0;
            }
            if (!isset($comment['dislikes'])) {
                $comment['dislikes'] = 0;
            }
        }

        return $comments;
    }
    return [];
}

// Saves the array of comments to the comments file.
function saveComments(array $comments) {
    global $commentsFile;
    file_put_contents($commentsFile, json_encode($comments, JSON_PRETTY_PRINT));
}

// Adds a new comment or reply, sanitizing input.
function addComment($username, $commentText, $parentId = null) {
    $comments = loadComments();
    $newComment = [
        'id' => uniqid(),
        'username' => htmlspecialchars($username),
        'text' => htmlspecialchars($commentText),
        'timestamp' => time(),
        'likes' => 0,
        'dislikes' => 0,
        'parent_id' => $parentId
    ];
    $comments[] = $newComment;
    saveComments($comments);
}

// Increments the like count for a specific comment.
function likeComment($commentId) {
    $comments = loadComments();
    foreach ($comments as &$comment) {
        if ($comment['id'] === $commentId) {
            $comment['likes']++;
            break;
        }
    }
    saveComments($comments);
}

// Increments the dislike count for a specific comment.
function dislikeComment($commentId) {
    $comments = loadComments();
    foreach ($comments as &$comment) {
        if ($comment['id'] === $commentId) {
            $comment['dislikes']++;
            break;
        }
    }
    saveComments($comments);
}

// Converts a Unix timestamp into a human-readable "time ago" string.
function humanTiming ($time) {
    $time = time() - $time;
    $time = ($time < 1) ? 1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }
}

// Builds a hierarchical tree of comments from a flat list, including replies.
function buildCommentTree(array $flatComments, $parentId = null) {
    $branch = [];
    foreach ($flatComments as $comment) {
        if ($comment['parent_id'] == $parentId) {
            $children = buildCommentTree($flatComments, $comment['id']);
            if ($children) {
                usort($children, function($a, $b) {
                    return $b['timestamp'] <=> $a['timestamp'];
                });
                $comment['replies'] = $children;
                $comment['reply_count'] = count($children);
            } else {
                $comment['reply_count'] = 0;
            }
            $branch[] = $comment;
        }
    }
    return $branch;
}

// Renders the HTML for a single comment and prepares for its replies.
function renderSingleComment($comment, $level = 0) {
    $margin_left = min($level * 40, 160);
    ?>
    <div class="comment-wrapper" style="margin-left: <?php echo $margin_left; ?>px;" id="comment-<?php echo $comment['id']; ?>">
        <div class="comment-box <?php echo $level > 0 ? 'is-reply-box' : ''; ?>">
            <div class="avatar"></div>
            <div class="options-menu">...</div>
            <div class="comment-header">
                <span class="username"><?php echo $comment['username']; ?></span>
                <span class="time-ago"><?php echo humanTiming($comment['timestamp']); ?> Ago</span>
            </div>
            <div class="comment-text">
                <?php echo nl2br($comment['text']); ?>
            </div>
            <div class="comment-actions">
                <a href="?action=like&comment_id=<?php echo $comment['id']; ?>" class="action-btn like-btn">
                    <span class="icon"><img src="Like.png" alt="like"></span>
                    <span class="count"><?php echo $comment['likes']; ?></span>
                </a>
                <a href="?action=dislike&comment_id=<?php echo $comment['id']; ?>" class="action-btn dislike-btn">
                    <span class="icon"><img src="Dislike.png" alt="dislike"></span>
                    <span class="count"><?php echo $comment['dislikes']; ?></span>
                </a>
                <a href="#" class="reply-button" data-comment-id="<?php echo $comment['id']; ?>">Reply</a>
            </div>

            <div id="reply-form-<?php echo $comment['id']; ?>" class="reply-form-container" style="display: none;">
                <form action="" method="post" class="add-reply-area">
                    <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                    <img src="placeholder-avatar.png" alt="Your Avatar">
                    <input type="text" name="comment_text" placeholder="Add a reply..." required>
                    <button type="submit" name="submit_reply" class="submit-reply-button">Reply</button>
                </form>
            </div>
        </div>

        <?php
        if ($comment['reply_count'] > 0) {
            $replies_hidden_class = ($comment['reply_count'] > 3 && $level === 0) ? 'hidden' : '';
            ?>
            <div class="replies-toggle-area">
                <button class="toggle-replies-btn" data-comment-id="<?php echo $comment['id']; ?>" data-replies-count="<?php echo $comment['reply_count']; ?>">
                    <?php echo ($replies_hidden_class === 'hidden') ? 'See Replies (' . $comment['reply_count'] . ')' : 'Hide Replies'; ?>
                </button>
            </div>
            <div id="replies-container-<?php echo $comment['id']; ?>" class="replies-container <?php echo $replies_hidden_class; ?>">
                <?php
                if (isset($comment['replies']) && !empty($comment['replies'])) {
                    foreach ($comment['replies'] as $reply) {
                        renderSingleComment($reply, $level + 1);
                    }
                }
                ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

// Initializes error message variable.
$error = null;

// Handles submission of new top-level comments.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $username = "Guest";
    $commentText = trim($_POST['comment_text']);

    if (!empty($commentText)) {
        addComment($username, $commentText);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Comment cannot be empty.";
    }
}

// Handles submission of replies to comments.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $username = "Guest";
    $commentText = trim($_POST['comment_text']);
    $parentId = $_POST['parent_id'];

    if (!empty($commentText) && !empty($parentId)) {
        addComment($username, $commentText, $parentId);
        header('Location: ' . $_SERVER['PHP_SELF'] . '#comment-' . $parentId);
        exit();
    } else {
        $error = "Reply cannot be empty.";
    }
}

// Handles liking or disliking comments.
if (isset($_GET['action']) && isset($_GET['comment_id'])) {
    $commentId = $_GET['comment_id'];
    if ($_GET['action'] === 'like') {
        likeComment($commentId);
    } elseif ($_GET['action'] === 'dislike') {
        dislikeComment($commentId);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '#comment-' . $commentId);
    exit();
}

// Loads all comments.
$allFlatComments = loadComments();

// Builds the threaded comment structure.
$threadedComments = buildCommentTree($allFlatComments, null);

// Determines the sorting order for comments.
$sort = $_GET['sort'] ?? 'newest';
if ($sort === 'oldest') {
    usort($threadedComments, function($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    });
} elseif ($sort === 'most_liked') {
    usort($threadedComments, function($a, $b) {
        return $b['likes'] <=> $a['likes'];
    });
} else {
    usort($threadedComments, function($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commenting Section with Replies & Likes/Dislikes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="comment-section-container">
        <div class="add-comment-area-wrapper">
            <div class="comment-user-info">
                <img src="placeholder-avatar.png" alt="Your Avatar" class="user-avatar">
            </div>
            <div class="add-comment-input-box">
                <form action="" method="post" style="flex-grow: 1; display: flex;">
                    <input type="text" name="comment_text" placeholder="Add a comment" required>
                    <button type="submit" name="submit_comment" style="display: none;"></button>
                </form>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="comment-filters">
            <a href="?sort=all" class="<?php echo ($sort === 'all' || !isset($_GET['sort'])) ? 'active' : ''; ?>">All</a>
            <a href="?sort=newest" class="<?php echo ($sort === 'newest') ? 'active' : ''; ?>">Newest</a>
            <a href="?sort=oldest" class="<?php echo ($sort === 'oldest') ? 'active' : ''; ?>">Oldest</a>
            <a href="?sort=most_liked" class="<?php echo ($sort === 'most_liked') ? 'active' : ''; ?>">Most Liked</a>
        </div>

        <div class="comments-list">
            <?php if (empty($threadedComments)): ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php else: ?>
                <?php
                foreach ($threadedComments as $comment) {
                    renderSingleComment($comment, 0);
                }
                ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js"></script>

</body>
</html>
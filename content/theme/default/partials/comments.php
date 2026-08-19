<?php
defined('HOPE_ROOT') || exit('access denied!');

$comment_args = [
    'verifyCode' => $verifyCode ?? '',
];
$comment_cookie = [
    'ckname' => $ckname ?? '',
    'ckmail' => $ckmail ?? '',
    'ckurl'  => $ckurl ?? '',
];
?>
<section class="comments-section" id="comments">
    <h3>评论 <span class="comment-count"><?= (int)$comnum ?></span></h3>

    <?php if (!empty($comments['commentStacks'])): ?>
        <?php nova_render_comment_tree($comments, (int)$logid, $comment_args); ?>
        <?php if (!empty($comments['commentPageUrl'])): ?>
        <nav class="pagination comment-pagination" aria-label="评论分页"><ul class="pagination-list"><?= $comments['commentPageUrl'] ?></ul></nav>
        <?php endif; ?>
    <?php else: ?>
        <p class="empty-hint">暂无评论，来说两句吧。</p>
    <?php endif; ?>

    <?php doAction('comment_form', (int)$logid); ?>
    <?php nova_comment_form((int)$logid, $comment_args['verifyCode'], $comment_cookie); ?>
</section>
<script src="<?= nova_asset('js/comments.js') ?>" defer></script>

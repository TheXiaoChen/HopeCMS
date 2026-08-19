<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <div class="d-lg-flex align-items-center">
                    <div>
                        <h5><?= __('admin.menu.twitter') ?></h5>
                        <p class="text-sm text-secondary mb-0">支持 Markdown，前台仅展示非私密微语</p>
                    </div>
                    <?php if (User::isAdmin()): ?>
                    <div class="ms-auto mt-3 mt-lg-0">
                        <?php if ($viewAll): ?>
                        <a href="<?= SELF ?>?act=twitter" class="btn btn-xs btn-outline-primary mb-0">只看我的</a>
                        <?php else: ?>
                        <a href="<?= SELF ?>?act=twitter&all=y" class="btn btn-xs btn-outline-primary mb-0">查看全部</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <form action="<?= SELF ?>?act=twitter&post" method="post">
                    <label class="form-label">发布微语</label>
                    <textarea class="form-control mb-3" name="content" rows="4" placeholder="写点什么…" required></textarea>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <label class="form-check-label mb-0">
                            <input class="form-check-input" type="checkbox" name="private" value="y"> 设为私密（前台不显示）
                        </label>
                        <button type="submit" class="btn bg-gradient-primary btn-sm mb-0">发布</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header pb-0">
                <h6 class="mb-0">微语列表 <span class="text-secondary text-sm">共 <?= (int)$twnum ?> 条</span></h6>
            </div>
            <div class="card-body pt-3">
                <?php if (empty($tws)): ?>
                <p class="text-sm text-secondary mb-0">暂无微语，在上方输入内容并发布吧。</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($tws as $tw):
                        $authorName = $user_cache[$tw['author']]['name'] ?? ('UID ' . (int)$tw['author']);
                        $rawContent = htmlspecialchars($tw['t_raw'] ?? $tw['content'] ?? '', ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="list-group-item px-0 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                    <span class="text-sm font-weight-bold"><?= htmlspecialchars($authorName) ?></span>
                                    <span class="text-xs text-secondary"><?= htmlspecialchars($tw['date']) ?></span>
                                    <?php if (($tw['private'] ?? 'n') === 'y'): ?>
                                    <span class="badge badge-warning">私密</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm twitter-content"><?= $tw['t'] ?? nl2br(htmlspecialchars($tw['content'])) ?></div>
                            </div>
                            <div class="text-nowrap">
                                <button type="button"
                                        class="btn btn-link text-secondary mb-0 px-2 py-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#twitterModal"
                                        data-id="<?= (int)$tw['id'] ?>"
                                        data-content="<?= $rawContent ?>"
                                        data-private="<?= ($tw['private'] ?? 'n') === 'y' ? 'y' : 'n' ?>">
                                    编辑
                                </button>
                                <a href="javascript:hp_delete(<?= (int)$tw['id'] ?>, 'twitter', '<?= LoginAuth::genToken() ?>');"
                                   class="btn btn-link text-danger mb-0 px-2 py-1">删除</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($pageurl)): ?>
                <div class="mt-4"><?= $pageurl ?></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="twitterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="<?= SELF ?>?act=twitter&update" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">编辑微语</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" name="content" id="twitter-content" rows="5" required></textarea>
                    <label class="form-check-label mt-3">
                        <input class="form-check-input" type="checkbox" name="private" id="twitter-private" value="y"> 设为私密
                    </label>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id" id="twitter-id">
                    <button type="button" class="btn bg-gradient-secondary mb-0" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn bg-gradient-primary mb-0">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$("#menu_content_manage").addClass('active');
$("#menu_content").addClass('show');
$("#menu_twitter").addClass('active');

$('#twitterModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    $('#twitter-id').val(button.data('id') || '');
    $('#twitter-content').val(button.data('content') || '');
    $('#twitter-private').prop('checked', button.data('private') === 'y');
});

<?php if (Input::getStrVar('code') === 'post'): ?>Toast.fire({ icon: 'success', title: '发布成功' });
<?php endif; ?><?php if (Input::getStrVar('code') === 'save'): ?>Toast.fire({ icon: 'success', title: '保存成功' });
<?php endif; ?><?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除成功' });
<?php endif; ?><?php if (Input::getStrVar('code') === 'empty'): ?>Toast.fire({ icon: 'error', title: '内容不能为空' });
<?php endif; ?>
</script>

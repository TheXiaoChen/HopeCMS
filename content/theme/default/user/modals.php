<?php defined('HOPE_ROOT') || exit('access denied!'); ?>



<div class="modal-overlay" id="modal-forum">

    <div class="modal-box modal-box-lg">

        <h3 id="modal-forum-title">发布文章</h3>

        <form id="forum-form">

            <input type="hidden" id="forum-id">

            <input type="hidden" id="forum-cover-path">

            <div class="form-group"><label>标题 *</label><input type="text" id="forum-title" class="form-input" required></div>

            <div class="form-row">

                <div class="form-group"><label>分类</label><select id="forum-sort" class="form-select"></select></div>

                <div class="form-group"><label>链接别名</label><input type="text" id="forum-alias" class="form-input" placeholder="英文或拼音，留空自动生成"></div>

            </div>

            <div class="form-group"><label>封面图</label>

                <div class="cover-upload"><div id="forum-cover-preview" class="cover-preview"></div>

                <label class="btn btn-ghost btn-sm">选择图片<input type="file" id="forum-cover" accept="image/*" hidden></label></div>

            </div>

            <div class="form-group"><label>正文（Markdown）</label><div id="forum-content-editor"></div></div>

            <div class="form-row">

                <div class="form-group"><label>访问密码</label><input type="text" id="forum-password" class="form-input" placeholder="留空则公开访问"></div>

                <div class="form-group"><label>关键词</label><input type="text" id="forum-tags" class="form-input" placeholder="逗号分隔"></div>

            </div>

            <div class="form-row">

                <div class="form-group form-check-row"><label><input type="checkbox" id="forum-allow-remark" checked> 允许评论</label></div>

                <div class="form-group"><label>发布方式</label>

                    <select id="forum-submit-type" class="form-select">

                        <option value="draft">保存草稿</option>

                        <option value="pending">提交审核</option>

                    </select>

                </div>

            </div>

            <div class="modal-actions">

                <button type="button" class="btn btn-ghost btn-sm" id="modal-forum-cancel">取消</button>

                <button type="submit" class="btn btn-primary btn-sm">保存</button>

            </div>

        </form>

    </div>

</div>



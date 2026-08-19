(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var section = document.getElementById('comments');
        if (!section) return;

        var mainForm = document.getElementById('comment-form-main');
        var mainPid = document.getElementById('comment-main-pid');
        var mainText = document.getElementById('comment-main-text');
        var replyHint = document.getElementById('comment-reply-hint');
        var cancelMain = document.getElementById('comment-cancel-main-reply');
        var activeBox = null;

        function closeInlineReply() {
            if (activeBox) {
                activeBox.hidden = true;
                activeBox = null;
            }
        }

        function resetMainReply() {
            if (!mainForm) return;
            mainPid.value = '0';
            mainText.placeholder = '写下你的想法…';
            if (replyHint) {
                replyHint.hidden = true;
                replyHint.textContent = '';
            }
            if (cancelMain) cancelMain.hidden = true;
        }

        section.addEventListener('click', function (e) {
            var replyBtn = e.target.closest('.comment-reply-btn');
            if (replyBtn) {
                e.preventDefault();
                var cid = replyBtn.getAttribute('data-cid');
                var poster = replyBtn.getAttribute('data-poster') || '';
                var box = document.getElementById('reply-box-' + cid);

                closeInlineReply();
                resetMainReply();

                if (box) {
                    box.hidden = false;
                    activeBox = box;
                    var ta = box.querySelector('textarea');
                    if (ta) ta.focus();
                } else if (mainForm) {
                    mainPid.value = cid;
                    mainText.placeholder = '回复 @' + poster + '…';
                    if (replyHint) {
                        replyHint.hidden = false;
                        replyHint.textContent = '正在回复 @' + poster;
                    }
                    if (cancelMain) cancelMain.hidden = false;
                    mainForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    mainText.focus();
                }
                return;
            }

            if (e.target.closest('.comment-cancel-reply')) {
                e.preventDefault();
                closeInlineReply();
                return;
            }

            if (e.target.id === 'comment-cancel-main-reply') {
                e.preventDefault();
                resetMainReply();
            }
        });
    });
})();

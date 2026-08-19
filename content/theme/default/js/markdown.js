var MarkdownEditor = (function () {
    function render(container, initial) {
        container.innerHTML =
            '<div class="md-toolbar">' +
            '<button type="button" data-cmd="bold" title="粗体"><b>B</b></button>' +
            '<button type="button" data-cmd="italic" title="斜体"><i>I</i></button>' +
            '<button type="button" data-cmd="code" title="代码">&lt;/&gt;</button>' +
            '<button type="button" data-cmd="link" title="链接">🔗</button>' +
            '<button type="button" data-cmd="h2" title="标题">H</button>' +
            '<button type="button" data-cmd="ul" title="列表">•</button>' +
            '<span class="md-toolbar-spacer"></span>' +
            '<button type="button" data-cmd="preview" class="md-preview-btn">预览</button>' +
            '</div>' +
            '<textarea class="md-textarea form-textarea" placeholder="支持 Markdown 格式…"></textarea>' +
            '<div class="md-preview markdown-body" style="display:none"></div>';

        var textarea = container.querySelector('.md-textarea');
        var preview = container.querySelector('.md-preview');
        textarea.value = initial || '';

        container.querySelectorAll('[data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cmd = btn.dataset.cmd;
                if (cmd === 'preview') {
                    var show = preview.style.display === 'none';
                    preview.style.display = show ? 'block' : 'none';
                    textarea.style.display = show ? 'none' : 'block';
                    btn.textContent = show ? '编辑' : '预览';
                    if (show) preview.innerHTML = parse(textarea.value);
                    return;
                }
                insertCmd(textarea, cmd);
            });
        });

        return {
            getValue: function () { return textarea.value; },
            setValue: function (v) { textarea.value = v || ''; }
        };
    }

    function insertCmd(ta, cmd) {
        var start = ta.selectionStart, end = ta.selectionEnd;
        var sel = ta.value.substring(start, end);
        var insert = sel;
        switch (cmd) {
            case 'bold': insert = '**' + (sel || '粗体') + '**'; break;
            case 'italic': insert = '*' + (sel || '斜体') + '*'; break;
            case 'code': insert = sel.indexOf('\n') >= 0 ? '```\n' + (sel || 'code') + '\n```' : '`' + (sel || 'code') + '`'; break;
            case 'link': insert = '[' + (sel || '链接文字') + '](url)'; break;
            case 'h2': insert = '## ' + (sel || '标题'); break;
            case 'ul': insert = '- ' + (sel || '列表项'); break;
        }
        ta.value = ta.value.substring(0, start) + insert + ta.value.substring(end);
        ta.focus();
    }

    function parse(md) {
        if (!md) return '';
        var html = md
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/^### (.+)$/gm, '<h3>$1</h3>')
            .replace(/^## (.+)$/gm, '<h2>$1</h2>')
            .replace(/^# (.+)$/gm, '<h1>$1</h1>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>')
            .replace(/^- (.+)$/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>\n?)+/g, function (m) { return '<ul>' + m + '</ul>'; })
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>');
        return '<p>' + html + '</p>';
    }

    return { render: render, parse: parse };
})();

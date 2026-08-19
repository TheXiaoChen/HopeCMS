const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    backdrop: false,
    didOpen: (toast) => {
        // toast 容器默认全屏占位会挡住点击，放开穿透
        var container = toast.closest('.swal2-container');
        if (container) {
            container.style.pointerEvents = 'none';
        }
        toast.style.pointerEvents = 'auto';
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});


/** Hope CMS API 成功码：200 */
function hopeApiSuccess(code) {
    return Number(code) === 200;
}

/** 读取后台 CSRF Token */
function hopeAdminToken() {
    var el = document.getElementById('hope-admin-token');
    return el ? el.value : '';
}

function hopeWithToken(url) {
    var token = hopeAdminToken();
    if (!token) {
        return url;
    }
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'token=' + encodeURIComponent(token);
}

function getChecked(node) {
    let re = false;
    $('input.' + node).each(function (i) {
        if (this.checked) {
            re = true;
        }
    });
    return re;
}

function timestamp() {
    return new Date().getTime();
}
function hp_delete(id, property, token) {
    let url, msg;
    let text = '删除后可能无法恢复'
    switch (property) {
        case 'article':
            url = '?act=article&del&id=' + id;
            msg = '确定要删除该篇文章吗？';
            break;
        case 'comment':
            url = '?act=comment&del&id=' + id;
            msg = '确定要删除该评论吗？';
            break;
        case 'commentbyip':
            url = '?act=comment&delbyip&ip=' + id;
            msg = '确定要删除来自该IP的所有评论吗？';
            break;
        case 'link':
            url = '?act=link&del&linkid=' + id;
            msg = '确定要删除该链接吗？';
            break;
        case 'twitter':
            url = '?act=twitter&del&id=' + id;
            msg = '确定要删除该微语吗？';
            break;
        case 'menu':
            url = '?act=menu&del&id=' + id;
            msg = '确定要删除该菜单吗？';
            break;
        case 'upload':
            url = '?act=upload&del&aid=' + id;
            msg = '确定要删除该附件吗？';
            break;
        case 'category':
            url = '?act=category&del&sid=' + id;
            msg = '确定要删除该分类吗？';
            break;
        case 'del_user':
            url = '?act=user&del&uid=' + id;
            msg = '确定要删除该用户吗？';
            break;
        case 'forbid_user':
            url = '?act=user&forbid&uid=' + id;
            msg = '确定要禁用该用户吗？';
            text = '';
            break;
        case 'tpl':
            url = '?act=theme&del&tpl=' + id;
            msg = '确定要删除该主题吗？';
            break;
        case 'reset_widget':
            url = '?act=widgets&reset';
            msg = '确定要恢复组件设置到初始状态吗？这样会丢失你自定义的组件。';
            text = '';
            break;
        case 'plu':
            url = '?act=plugin&del&plugin=' + id;
            msg = '确定要删除该插件吗？';
            break;
        case 'upload_sort':
            url = '?act=upload&del_upload_sort&id=' + id;
            msg = '确定要删除该资源分类吗？';
            text = '不会删除分类下资源文件';
            break;
    }
    if(property == 'article'){
        Swal.fire({
            title: msg,
            text: text,
            icon: 'warning',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: "彻底删除",
            cancelButtonText: '取消',
            denyButtonText: '存入草稿'
          }).then((result) => {
            if (result.isConfirmed) {
                window.location = url + '&token=' + token;
            } else if (result.isDenied) {
                window.location = url + '&draft&token=' + token;
            }
          });

    }else{
        Swal.fire({
            title: msg,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: '取消',
            confirmButtonText: '确定'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = url + '&token=' + token;
            }
        });
    }
}

function changePerPage(select) {
    const params = new URLSearchParams(window.location.search);
    params.set('perpage_num', select.value);
    params.set('page', '1');
    window.location.search = params.toString();
}
function submitForm(formId, successMsg) {
    $.ajax({
        type: "POST", 
        url: $(formId).attr('action'),
        data: $(formId).serialize(),
        success: function() {
            Toast.fire({
                icon: 'success',
                title: successMsg || (window.HOPE_I18N && window.HOPE_I18N.save_success) || '保存成功'
            });
        },
        error: function(xhr) {
            const errorMsg = JSON.parse(xhr.responseText).msg;
            Toast.fire({
                icon: 'error', 
                title: errorMsg
            });
        }
    });
}

function focusEle(id) {
    try {
        document.getElementById(id).focus();
    } catch (e) {
    }
}

function hideActived() {
    $(".alert-success").slideUp(300);
    $(".alert-danger").slideUp(300);
}

// Click action of [More Options] 
let icon_mod = "down";

function displayToggle(id) {
    $("#" + id).toggle();
    if (icon_mod === "down") {
        icon_mod = "right";
        $(".icofont-simple-down").attr("class", "icofont-simple-right")
    } else {
        icon_mod = "down";
        $(".icofont-simple-right").attr("class", "icofont-simple-down")
    }

    Cookies.set('em_' + id, icon_mod, {expires: 365})
}

function doToggle(id) {
    $("#" + id).toggle();
}

function insertTag(tag, boxId) {
    var targetinput = $("#" + boxId).val();
    if (targetinput == '') {
        targetinput += tag;
    } else {
        var n = ',' + tag;
        targetinput += n;
    }
    $("#" + boxId).val(targetinput);
    if (boxId == "tag") $("#tag_label").hide();
}

function isalias(a) {
    var reg1 = /^[\u4e00-\u9fa5\w-]*$/;
    var reg2 = /^[\d]+$/;
    var reg3 = /^post(-\d+)?$/;
    if (!reg1.test(a)) {
        return 1;
    } else if (reg2.test(a)) {
        return 2;
    } else if (reg3.test(a)) {
        return 3;
    } else if (a == 't' || a == 'm' || a == 'admin') {
        return 4;
    } else {
        return 0;
    }
}

function checkform() {
    var a = $.trim($("#alias").val());
    var t = $.trim($("#title").val());

    if (typeof articleTextRecord !== "undefined") {  // 提交时，重置原文本记录值，防止出现离开提示
        articleTextRecord = $("textarea[name=logcontent]").text();
    } else {
        pageText = $("textarea").text();
    }
    if (0 == isalias(a)) {
        return true;
    } else {
        alert("链接别名错误");
        $("#alias").focus();
        return false;
    }
}

function checkalias() {
    var a = $.trim($("#alias").val());
    if (1 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，应由字母、数字、下划线、短横线组成</span>');
    } else if (2 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，不能为纯数字</span>');
    } else if (3 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，不能为\'post\'或\'post-数字\'</span>');
    } else if (4 == isalias(a)) {
        $("#alias_msg_hook").html('<span id="input_error">别名错误，与系统链接冲突</span>');
    } else {
        $("#alias_msg_hook").html('');
        $("#msg").html('');
    }
}

function insert_cover(imgsrc) {
    $('#cover_image').attr('src', imgsrc);
    $('#cover').val(imgsrc);
    $('#cover_rm').show();
}

// act 1：auto save 2：save
function autosave(act) {
    const nodeid = "as_logid";
    const timeout = 60000;
    const url = "article_save.php?action=autosave";
    const alias = $.trim($("#alias").val());
    const content = Editor.getMarkdown();
    let ishide = $.trim($("#ishide").val());
    if (ishide === "") {
        $("#ishide").val("y")
    }

    if (alias != '' && 0 != isalias(alias)) {
        $("#msg").show().html("链接别名错误，自动保存失败");
        if (act == 0) {
            setTimeout("autosave(1)", timeout);
        }
        return;
    }
    // 编辑发布状态的文章时不自动保存
    if (act == 1 && ishide == 'n') {
        return;
    }
    // 内容为空时不自动保存
    if (act == 1 && content == "") {
        setTimeout("autosave(1)", timeout);
        return;
    }
    // 距离上次保存成功时间小于一秒时不允许手动保存
    if ((new Date().getTime() - Cookies.get('em_saveLastTime')) < 1000 && act != 1) {
        alert("请勿频繁操作！");
        return;
    }
    const btname = $("#savedf").val();
    $("#savedf").val("正在保存中...");
    $('title').text('[保存中] ' + titleText);
    $("#savedf").attr("disabled", "disabled");
    $.post(url, $("#addlog").serialize(), function (data) {
        data = $.trim(data);
        var isresponse = /.*autosave\_gid\:\d+\_.*/;
        if (isresponse.test(data)) {
            const getvar = data.match(/_gid:([\d]+)_/);
            const logid = getvar[1];
            const d = new Date();
            const h = d.getHours();
            const m = d.getMinutes();
            const s = d.getSeconds();
            const tm = (h < 10 ? "0" + h : h) + ":" + (m < 10 ? "0" + m : m);
            $("#save_info").html("保存于：" + tm + " <a href=\"" + ((window.HOPE_SITE_URL || "/") + "?article=" + logid) + "\" target=\"_blank\">预览文章</a>");
            $('title').text('[保存成功] ' + titleText);
            articleTextRecord = $("#addlog textarea[name=logcontent]").val(); // 保存成功后，将原文本记录值替换为现在的文本
            Cookies.set('em_saveLastTime', new Date().getTime()); // 把保存成功时间戳记录（或更新）到 cookie 中
            $("#" + nodeid).val(logid);
            $("#savedf").attr("disabled", false).val(btname);
        } else {
            $("#savedf").attr("disabled", false).val(btname);
            $("#save_info").html("保存失败，可能文章不可编辑或达到每日发文限额").addClass("alert-danger");
            $('title').text('[保存失败] ' + titleText);
        }
    });
    if (act == 1) {
        setTimeout("autosave(1)", timeout);
    }
}

// “页面”的 editor.md 编辑器 Ctrl + S 快捷键的自动保存动作
//const pagetitle = $('title').text();

function pagesave() {
    document.addEventListener('keydown', function (e) {  // 阻止自动保存产生的浏览器默认动作
        if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
            e.preventDefault();
        }
    });
    let url = "page.php?action=save";
    if ($("[name='pageid']").attr("value") < 0) return alert("请先保存页面！");
    if (!$("[name='pagecontent']").html()) return alert("页面内容不能为空！");
    $('title').text('[保存中...] ' + pagetitle);
    $.post(url, $("#addlog").serialize(), function (data) {
        $('title').text('[保存成功] ' + pagetitle);
        setTimeout(function () {
            $('title').text(pagetitle);
        }, 2000);
        pageText = $("textarea").text();
    }).fail(function () {
        $('title').text('[保存失败] ' + pagetitle);
        alert("保存失败！")
    });
}

// toggle plugin
$.fn.toggleClick = function () {
    var functions = arguments;
    return this.click(function () {
        var iteration = $(this).data('iteration') || 0;
        functions[iteration].apply(this, arguments);
        iteration = (iteration + 1) % functions.length;
        $(this).data('iteration', iteration);
    });
};

function removeHTMLTag(str) {
    str = str.replace(/<\/?[^>]*>/g, ''); //去除HTML tag
    str = str.replace(/[ | ]*\n/g, '\n'); //去除行尾空白
    str = str.replace(/ /ig, '');
    return str;
}

// 表格全选
$(function () {
    $('#checkAll').click(function (event) {
        let tr_checkbox = $('table tbody tr').find('input[type=checkbox]');
        tr_checkbox.prop('checked', $(this).prop('checked'));
        event.stopPropagation();
    });
    // 点击表格每一行的checkbox，表格所有选中的checkbox数 = 表格行数时，则将表头的‘checkAll’单选框置为选中，否则置为未选中
    $('table tbody tr').find('input[type=checkbox]').click(function (event) {
        let tbr = $('table tbody tr');
        $('#checkAll').prop('checked', tbr.find('input[type=checkbox]:checked').length == tbr.length ? true : false);
        event.stopPropagation();
    });
});

// 卡片全选
$(function () {
    $('#checkAllCard').click(function (event) {
        let card_checkbox = $('.card-body').find('input[type=checkbox]');
        card_checkbox.prop('checked', $(this).prop('checked'));
        event.stopPropagation();
    });
    $('.card-body').find('input[type=checkbox]').click(function (event) {
        let cards = $('.card-body');
        $('#checkAllCard').prop('checked', cards.find('input[type=checkbox]:checked').length == cards.length ? true : false);
        event.stopPropagation();
    });
});


// editor.md 的 js 钩子
var queue = new Array();
var hooks = {
    addAction: function (hook, func) {
        if (typeof (queue[hook]) == "undefined" || queue[hook] == null) {
            queue[hook] = new Array();
        }
        if (typeof func == 'function') {
            queue[hook].push(func);
        }
    }, doAction: function (hook, obj) {
        try {
            for (var i = 0; i < queue[hook].length; i++) {
                queue[hook][i](obj);
            }
        } catch (e) {
        }
    }
}

// 粘贴上传图片函数（旧版：若已由 Editor.md pasteUpload 接管则跳过，避免重复上传）
function imgPasteExpand(thisEditor) {
    if (thisEditor && thisEditor._hopeMediaBound) {
        return;
    }
    var listenObj = document.querySelector("#logcontent .CodeMirror");
    if (!listenObj) {
        var ta = document.querySelector("#logcontent textarea") || document.querySelector("textarea");
        listenObj = ta ? ta.parentNode : null;
    }
    if (!listenObj) return;
    var postUrl = typeof hopeWithToken === 'function' ? hopeWithToken('?act=upload&uploade&editor=1') : '?act=upload&uploade&editor=1';

    function uploadImg(img) {
        var formData = new FormData();
        var imgName = "粘贴上传" + new Date().getTime() + "." + (img.name.split(".").pop() || "png");
        formData.append('editormd-image-file', img, imgName);
        var mark = "![上传中…]()";
        thisEditor.insertValue(mark);
        $.ajax({
            url: postUrl,
            type: 'post',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (json) {
                var value = thisEditor.getMarkdown();
                var idx = value.indexOf(mark);
                var md = (json && Number(json.success) === 1 && json.url)
                    ? ('![](' + json.url + ')')
                    : '';
                if (idx >= 0) {
                    thisEditor.setMarkdown(value.slice(0, idx) + md + value.slice(idx + mark.length));
                } else if (md) {
                    thisEditor.insertValue(md);
                }
                if (!md) {
                    alert((json && json.message) || '上传失败');
                }
            },
            error: function () {
                var value = thisEditor.getMarkdown();
                var idx = value.indexOf(mark);
                if (idx >= 0) {
                    thisEditor.setMarkdown(value.slice(0, idx) + value.slice(idx + mark.length));
                }
                alert('上传失败,图片类型错误或网络错误');
            }
        });
    }

    listenObj.addEventListener("paste", function (e) {
        if ($('.editormd-dialog:visible').length) return;
        if (!(e.clipboardData && e.clipboardData.items)) return;
        for (var i = 0; i < e.clipboardData.items.length; i++) {
            var item = e.clipboardData.items[i];
            if ((item.kind == "file") && (item.type.match('^image/'))) {
                var imgData = item.getAsFile();
                if (!imgData || imgData.size === 0) return;
                e.preventDefault();
                uploadImg(imgData);
                return;
            }
        }
    }, false);
}

// 把粘贴上传图片函数，挂载到位于文章编辑器、页面编辑器处的 js 钩子处
hooks.addAction("loaded", imgPasteExpand);
hooks.addAction("page_loaded", imgPasteExpand);

$(function () {
    // 网页加载完先检查一遍
    // 设置界面，如果设置“自动检测地址”，则设置 input 为只读，以表示该项是无效的
    if ($("#detect_url").prop("checked")) {
        $("[name=siteurl]").attr("readonly", "readonly")
    }

    $("#detect_url").click(function () {
        if ($(this).prop("checked")) {
            $("[name=siteurl]").attr("readonly", "readonly")
        } else {
            $("[name=siteurl]").removeAttr("readonly")
        }
    })

    // store app install
    $(document).on('click', '.installBtn', function (e) {
        e.preventDefault();
        let link = $(this);
        let down_url = link.data('url');
        let type = link.data('type');
        let alias = link.data('alias') || '';
        link.text('安装中…');
        $.post('?act=apply&install', {
            source: down_url,
            type: type,
            alias: alias
        }, function(res) {
            if (hopeApiSuccess(res.code)) {
                var msg = (res.data && res.data.msg) ? res.data.msg : (res.msg || '安装成功');
                Toast.fire({ icon: 'success', title: msg });
                link.text('安装');
            } else {
                Toast.fire({ icon: 'error', title: res.msg || '安装失败' });
                link.text('安装');
            }
        }, 'json').fail(function(xhr) {
            var msg = '安装请求失败';
            var res = xhr.responseJSON;
            if (!res && xhr.responseText) {
                try { res = JSON.parse(xhr.responseText); } catch (err) { res = null; }
            }
            if (res && res.msg) {
                msg = res.msg;
            }
            Toast.fire({ icon: 'error', title: msg });
            link.text('安装');
        });
    });
     
    $('#cover').on('mouseenter', function(e){
        var src = $.trim($(this).val());
        if (!src) return; // 无 URL 则不显示预览
        // 移除已有预览，避免重复
        $('#cover-tip').remove();
        var $tip = $('<div id="cover-tip" class="tip" style="position:absolute; z-index:9999; display:none; border:1px solid rgba(0,0,0,0.08); background:#fff; padding:4px; box-shadow:0 6px 18px rgba(0,0,0,0.12);"><img src="' + src + '" style="max-width:260px; max-height:180px; display:block;"/></div>');
        $('body').append($tip);
        $tip.show();
    }).on('mousemove', function(e){
        var $tip = $('#cover-tip');
        if ($tip.length) {
            var left = e.pageX + 12;
            var top = e.pageY + 12;
            var w = $tip.outerWidth();
            var h = $tip.outerHeight();
            var docW = $(window).width();
            var docH = $(window).height();
            // 超出右边界则显示在光标左侧
            if (left + w > docW) left = e.pageX - w - 12;
            // 超出下边界则显示在光标上方
            if (top + h > docH) top = e.pageY - h - 12;
            $tip.css({ left: left, top: top });
        }
    }).on('mouseleave', function(){
        $('#cover-tip').remove();
    });
    // 输入变化时，如果预览存在则更新图片
    $('#cover').on('input propertychange change', function(){
        var $tip = $('#cover-tip');
        if ($tip.length) {
            var src = $.trim($(this).val());
            $tip.find('img').attr('src', src);
        }
    });
    $('#upload_cover').on('change', function(event) {
        var files = event.target.files;
        if (!files || files.length === 0) return;
        
        var file = files[0];
        var inputElement = event.target; // 保存 input 元素引用
        
        // 创建 FormData 并添加文件
        var formData = new FormData();
        formData.append('image', file);
        
        // 显示上传中状态
        var $icon = $(inputElement).closest('label').find('i');
        var origHtml = $icon.prop('outerHTML');
        $icon.replaceWith('<i class="fa fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: hopeWithToken('?act=upload&upload_cover'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                // 处理上传成功的响应
                var path = '';
                if (resp) {
                    if (typeof resp === 'string') {
                        path = resp;
                    } else if (hopeApiSuccess(resp.code) && resp.data) {
                        path = resp.data;
                    } else if (resp.data) {
                        path = resp.data;
                    }
                }
                
                if (path) {
                    $('#cover').val(path).trigger('change');
                    if ($('#cover_image').length) {
                        $('#cover_image').attr('src', path);
                    }
                    if ($('#cover_rm').length) {
                        $('#cover_rm').show();
                    }
                    if (typeof Toast !== 'undefined') {
                        Toast.fire({ icon: 'success', title: '上传成功' });
                    }
                } else {
                    if (typeof Toast !== 'undefined') {
                        Toast.fire({ icon: 'error', title: '上传失败' });
                    } else {
                        alert('上传失败');
                    }
                }
            },
            error: function() {
                if (typeof Toast !== 'undefined') {
                    Toast.fire({ icon: 'error', title: '上传失败' });
                } else {
                    alert('上传失败');
                }
            },
            complete: function() {
                // 恢复图标
                $(inputElement).closest('label').find('i.fa-spinner').replaceWith(origHtml);
                // 清空选择，方便再次上传相同文件
                $(inputElement).val('');
            }
        });
    });

})

/** Admin nav-pills 滑动指示器（不依赖 Argon initNavs 的 mouseover 逻辑） */
function hopeNormalizeMovingTabIndicator(moving) {
    if (!moving) {
        return;
    }
    moving.classList.add('hope-moving-tab', 'position-absolute');
    moving.classList.remove('nav-link');
    moving.setAttribute('aria-hidden', 'true');
    moving.style.padding = '0';
    var inner = moving.querySelector(':scope > .hope-moving-tab-inner');
    if (!inner) {
        var legacy = moving.querySelector(':scope > .nav-link');
        inner = document.createElement('span');
        inner.className = 'hope-moving-tab-inner';
        if (legacy) {
            moving.replaceChild(inner, legacy);
        } else {
            moving.appendChild(inner);
        }
    }
}

function hopeDedupeMovingTabs(nav) {
    if (!nav) {
        return null;
    }
    var indicators = [];
    Array.prototype.forEach.call(nav.children, function (child) {
        if (child.classList.contains('hope-moving-tab') || child.classList.contains('moving-tab')) {
            indicators.push(child);
        }
    });
    var moving = indicators[0] || null;
    for (var i = 1; i < indicators.length; i++) {
        indicators[i].remove();
    }
    if (moving) {
        hopeNormalizeMovingTabIndicator(moving);
    }
    return moving;
}

function hopeEnsureMovingTab(nav) {
    var moving = hopeDedupeMovingTabs(nav);
    if (!moving) {
        moving = document.createElement('div');
        moving.className = 'hope-moving-tab position-absolute';
        moving.setAttribute('aria-hidden', 'true');
        var inner = document.createElement('span');
        inner.className = 'hope-moving-tab-inner';
        moving.appendChild(inner);
        nav.appendChild(moving);
    }
    return moving;
}

function hopeUpdateMovingTab(nav, activeLink) {
    if (!nav || !activeLink) {
        return;
    }
    var li = activeLink.closest('li');
    if (!li || li.parentElement !== nav) {
        return;
    }
    var moving = hopeEnsureMovingTab(nav);
    var left = li.offsetLeft;
    var top = li.offsetTop;
    if (li.offsetParent !== nav) {
        var navRect = nav.getBoundingClientRect();
        var liRect = li.getBoundingClientRect();
        left = liRect.left - navRect.left;
        top = liRect.top - navRect.top;
    }
    var width = li.offsetWidth;
    var height = li.offsetHeight;
    if (width <= 0 || height <= 0) {
        setTimeout(function () {
            hopeUpdateMovingTab(nav, activeLink);
        }, 120);
        return;
    }
    moving.style.width = width + 'px';
    moving.style.height = height + 'px';
    if (nav.classList.contains('flex-column')) {
        moving.style.transform = 'translate3d(0px,' + top + 'px, 0px)';
    } else {
        moving.style.transform = 'translate3d(' + left + 'px,' + top + 'px, 0px)';
    }
}

function hopeSyncNavPills(nav) {
    if (!nav || nav.getAttribute('data-hope-tabs-skip') === '1') {
        return;
    }
    if (nav.querySelector('.menu-nav-link')) {
        return;
    }
    var active = nav.querySelector('.nav-link.active') || nav.querySelector('.nav-link');
    if (active) {
        hopeUpdateMovingTab(nav, active);
    }
}

function hopeInitAdminTabPills() {
    document.querySelectorAll('ul.nav-pills[role="tablist"]').forEach(function (nav) {
        hopeDedupeMovingTabs(nav);
        hopeSyncNavPills(nav);
    });
}

function hopeActivateTabFromHash() {
    var hash = window.location.hash;
    if (!hash || hash.charAt(0) !== '#') {
        return;
    }
    document.querySelectorAll('ul.nav-pills[role="tablist"] .nav-link[href="' + hash + '"][data-bs-toggle="tab"]').forEach(function (link) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(link).show();
        }
    });
}

function hopeBindAdminTabPills() {
    if (window._hopeTabPillsBound) {
        return;
    }
    window._hopeTabPillsBound = true;

    document.addEventListener('shown.bs.tab', function (e) {
        var link = e.target;
        if (!link || !link.classList || !link.classList.contains('nav-link')) {
            return;
        }
        var nav = link.closest('ul.nav-pills[role="tablist"]');
        if (nav) {
            hopeUpdateMovingTab(nav, link);
        }
    });

    document.addEventListener('click', function (e) {
        var link = e.target.closest('ul.nav-pills[role="tablist"] > .nav-item > .nav-link');
        if (!link || link.closest('.hope-moving-tab, .moving-tab')) {
            return;
        }
        var nav = link.closest('ul.nav-pills[role="tablist"]');
        if (!nav || nav.querySelector('.menu-nav-link')) {
            return;
        }
        setTimeout(function () {
            var active = nav.querySelector('.nav-link.active') || link;
            hopeUpdateMovingTab(nav, active);
        }, 30);
    });
}

function hopeScheduleTabPillsInit() {
    hopeInitAdminTabPills();
    requestAnimationFrame(function () {
        hopeInitAdminTabPills();
        requestAnimationFrame(hopeInitAdminTabPills);
    });
}

hopeBindAdminTabPills();
document.addEventListener('DOMContentLoaded', function () {
    hopeActivateTabFromHash();
    setTimeout(hopeScheduleTabPillsInit, 100);
    setTimeout(hopeScheduleTabPillsInit, 350);
});
window.addEventListener('load', hopeScheduleTabPillsInit);
window.addEventListener('hashchange', function () {
    hopeActivateTabFromHash();
    setTimeout(hopeScheduleTabPillsInit, 50);
});
window.addEventListener('resize', function () {
    clearTimeout(window._hopeTabPillsResizeTimer);
    window._hopeTabPillsResizeTimer = setTimeout(hopeScheduleTabPillsInit, 120);
});
// 资源管理
function insert_media_img(fileurl) {
    var filename = fileurl.split('/').pop();
    Editor.insertValue('![' + filename + '](' + fileurl + ')\n\n');
}

function insert_media_video(fileurl) {
    Editor.insertValue('<video class=\"video-js\" controls preload=\"auto\" width=\"100%\" data-setup=\'{"aspectRatio":"16:9"}\'> <source src="' + fileurl + '" type=\'video/mp4\' > </video>');
}

function insert_media_audio(fileurl) {
    Editor.insertValue('<audio src="' + fileurl + '" preload="none" controls loop></audio>');
}

function insert_media(fileurl, filename) {
    Editor.insertValue('[' + filename + '](' + fileurl + ')\n\n');
}

function insert_cover(imgsrc) {
    $('#cover_image').attr('src', imgsrc);
    $('#cover').val(imgsrc);
    $('#cover_rm').show();
}

function mediaEscapeHtml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/** 用于 onclick='fn("...")' 中的 JS 字符串字面量 */
function mediaJsStr(str) {
    return String(str == null ? '' : str)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '')
        .replace(/</g, '\\u003c')
        .replace(/>/g, '\\u003e');
}

function delete_media(id) {
    Swal.fire({
        title: '确定要删除该资源吗？',
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: '取消',
        confirmButtonText: '确定'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(typeof hopeWithToken === 'function' ? hopeWithToken('?act=upload&del&aid=' + id) : '?act=upload&del&aid=' + id + '&token=' + hopeAdminToken(), {aid: id}, function () {
                mediaReloadList();
            });
        }
    });
}

function mediaToggleEmpty(isEmpty) {
    if (isEmpty) {
        $('#media-empty').removeClass('d-none');
    } else {
        $('#media-empty').addClass('d-none');
    }
}

function mediaUploadUrl() {
    var q = '?act=upload&uploade';
    if (sid) {
        q += '&sid=' + encodeURIComponent(sid);
    }
    return typeof hopeWithToken === 'function' ? hopeWithToken(q) : (q + '&token=' + hopeAdminToken());
}

function mediaReloadList() {
    page = 1;
    $('#image-list').empty();
    mediaToggleEmpty(false);
    loadImages();
    $('#load-more').show();
}

// 插入资源列表
let page = 1;
let sid = 0;
let keyword = '';
let mediaSearchTimer = null;

function loadImages() {
    $.ajax({
        type: 'GET',
        url: '?act=upload&list',
        data: {
            page: page,
            sid: sid || 0,
            keyword: keyword || ''
        },
        success: function (resp) {
            var images = (resp && resp.data && resp.data.images) ? resp.data.images : [];
            if (page === 1 && images.length === 0) {
                mediaToggleEmpty(true);
            } else if (images.length > 0) {
                mediaToggleEmpty(false);
            }
            $.each(images, function (i, image) {
                var name = mediaEscapeHtml(image.media_name);
                var nameTitle = mediaEscapeHtml(image.media_name);
                var iconSrc = mediaEscapeHtml(image.media_icon);
                var fileHref = mediaEscapeHtml(image.media_url);
                var jsIcon = mediaJsStr(image.media_icon);
                var jsUrl = mediaJsStr(image.media_url);
                var jsName = mediaJsStr(image.media_name);
                var jsId = mediaJsStr(image.media_id);
                var size = mediaEscapeHtml(image.attsize);
                var type = image.media_type || 'file';
                var typeLabel = ({ image: '图片', video: '视频', audio: '音频', zip: '压缩包' })[type] || '文件';
                var faClass = mediaEscapeHtml(image.media_fa || ({
                    zip: 'fa-file-zipper',
                    video: 'fa-file-video',
                    audio: 'fa-file-audio',
                    file: 'fa-file'
                })[type] || 'fa-file');

                var insertBtnHtml = '';
                if (type === 'image') {
                    insertBtnHtml =
                        '<button type="button" class="btn btn-outline-primary btn-xs media-act-btn" onclick="insert_media_img(\'' + jsIcon + '\')"><i class="fa fa-plus"></i> 插入</button>' +
                        '<button type="button" class="btn btn-outline-secondary btn-xs media-act-btn" onclick="insert_cover(\'' + jsIcon + '\')"><i class="fa fa-image"></i> 封面</button>';
                } else if (type === 'video') {
                    insertBtnHtml = '<button type="button" class="btn btn-outline-primary btn-xs media-act-btn" onclick="insert_media_video(\'' + jsUrl + '\')"><i class="fa fa-plus"></i> 插入</button>';
                } else if (type === 'audio') {
                    insertBtnHtml = '<button type="button" class="btn btn-outline-primary btn-xs media-act-btn" onclick="insert_media_audio(\'' + jsUrl + '\')"><i class="fa fa-plus"></i> 插入</button>';
                } else if (type === 'zip') {
                    var jsPublic = mediaJsStr(image.media_public_url || image.media_down_url || image.media_url);
                    var jsDown = mediaJsStr(image.media_down_url || image.media_url);
                    insertBtnHtml =
                        '<button type="button" class="btn btn-outline-primary btn-xs media-act-btn" onclick="insert_media(\'' + jsPublic + '\', \'' + jsName + '\')"><i class="fa fa-plus"></i> 公开</button>' +
                        '<button type="button" class="btn btn-outline-secondary btn-xs media-act-btn" onclick="insert_media(\'' + jsDown + '\', \'' + jsName + '\')"><i class="fa fa-user"></i> 用户</button>';
                } else {
                    insertBtnHtml = '<button type="button" class="btn btn-outline-primary btn-xs media-act-btn" onclick="insert_media(\'' + jsUrl + '\', \'' + jsName + '\')"><i class="fa fa-plus"></i> 插入</button>';
                }
                insertBtnHtml += '<button type="button" class="btn btn-outline-danger btn-xs media-act-btn" onclick="delete_media(\'' + jsId + '\')" title="删除"><i class="fa fa-trash"></i></button>';

                var thumbHtml = (type === 'image' && iconSrc)
                    ? '<img src="' + iconSrc + '" alt="' + nameTitle + '" loading="lazy">'
                    : '<i class="fa ' + faClass + ' media-lib-fa media-lib-fa--' + mediaEscapeHtml(type) + '" aria-hidden="true"></i>';

                var cardHtml =
                    '<div class="col-6 col-md-4 col-xl-3">' +
                    '<div class="card media-lib-card h-100 border-0 shadow-sm">' +
                    '<a class="media-lib-thumb' + (type === 'image' ? '' : ' is-icon') + '" href="' + fileHref + '" target="_blank" rel="noopener" title="' + nameTitle + '">' +
                    thumbHtml +
                    '<span class="media-lib-type">' + typeLabel + '</span>' +
                    '</a>' +
                    '<div class="card-body media-lib-body">' +
                    '<div class="media-lib-name" title="' + nameTitle + '">' + name + '</div>' +
                    '<div class="media-lib-meta text-muted">' + size + '</div>' +
                    '<div class="media-lib-actions">' + insertBtnHtml + '</div>' +
                    '</div></div></div>';
                $('#image-list').append(cardHtml);
            });
            if (resp.data && resp.data.hasMore) {
                page++;
                $('#load-more').show();
            } else {
                $('#load-more').hide();
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

$('#mediaModal').on('show.bs.modal', function () {
    page = 1;
    sid = $('#media-sort-select').length ? ($('#media-sort-select').val() || 0) : 0;
    keyword = $.trim($('#media-keyword').val() || '');
    $('#image-list').empty();
    mediaToggleEmpty(false);
    loadImages();
    $('#load-more').show();
});

$(document).on('change', '#media-sort-select', function () {
    sid = $(this).val() || 0;
    mediaReloadList();
});

$(document).on('input', '#media-keyword', function () {
    var val = $.trim($(this).val() || '');
    clearTimeout(mediaSearchTimer);
    mediaSearchTimer = setTimeout(function () {
        keyword = val;
        mediaReloadList();
    }, 300);
});

$('#load-more').click(function () {
    loadImages();
});

// 上传资源
Dropzone.autoDiscover = false;
var myDropzone = new Dropzone("#mediaAdd", {
    url: mediaUploadUrl(),
    addRemoveLinks: false,
    method: 'post',
    maxFilesize: 20480, // 20G
    filesizeBase: 1024,
    timeout: 3600000,// milliseconds
    previewsContainer: ".dropzone-previews",
    sending: function (file, xhr, formData) {
        formData.append("filesize", file.size);
        this.options.url = mediaUploadUrl();
        $('#mediaAdd').html('<i class="fa fa-spinner fa-spin me-1"></i>上传中…');
    },
    init: function () {
        this.on("error", function (file, response) {
            alert(response);
            $('#mediaAdd').html('<i class="fa fa-cloud-arrow-up me-1"></i>上传附件');
        });
        this.on("queuecomplete", function (file) {
            mediaReloadList();
            $('#mediaAdd').html('<i class="fa fa-cloud-arrow-up me-1"></i>上传附件');
        });
    }
});

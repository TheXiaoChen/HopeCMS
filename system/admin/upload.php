<?php
/**
 * Hope CMS(希望CMS) 
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
 defined('HOPE_ROOT') || exit('access denied!');

$Upload_Model = new Upload_Model();
$UploadSort_Model = new UploadSort_Model();

if (isset($_GET['upload_cover'])) {
    hope_admin_require_token();
    $ret = uploadCropImg();
    $Upload_Model = new Upload_Model();
    $Upload_Model->addMedia($ret['file_info'],0);
    Output::ok($ret['file_info']['file_path']);
}

if (isset($_GET['list'])) {
    $sid = Input::getIntVar('sid');
    $page = Input::getIntVar('page', 1);
    $keyword = Input::getStrVar('keyword');
    $uid = User::haveEditPermission() ? null : UID;
    $perPageCount = 12;

    $medias = $Upload_Model->getMedias($page, $perPageCount, $uid, $sid, '', $keyword);
    $count = $Upload_Model->getMediaCount($uid, $sid, '', $keyword);

    $ret['hasMore'] = !(count($medias) < $perPageCount);
    foreach ($medias as $v) {
        $data['media_id'] = $v['aid'];
        $data['media_alias'] = $v['alias'];
        $data['media_path'] = $v['filepath'];
        $data['media_url'] = rmUrlParams(getFileUrl($v['filepath']));
        $data['media_public_url'] = SITE_URL . '?resource_alias=' . $v['alias'] . '&name=' . rawurlencode($v['alias']);
        $data['media_down_url'] = SITE_URL . '?resource_alias=' . $v['alias'];
        $data['media_name'] = subString($v['filename'], 0, 20);
        $data['attsize'] = $v['attsize'];
        $data['media_type'] = '';
        $data['media_icon'] = '';
        $data['media_fa'] = '';
        if (isImage($v['mimetype'])) {
            $data['media_icon'] = getFileUrl($v['filepath_thum']);
            $data['media_type'] = 'image';
        } elseif (isZip($v['filename'])) {
            $data['media_type'] = 'zip';
            $data['media_fa'] = getMediaFaIcon($v['filename'], $v['mimetype']);
        } elseif (isVideo($v['mimetype']) || isVideo($v['filename'])) {
            $data['media_type'] = 'video';
            $data['media_fa'] = getMediaFaIcon($v['filename'], $v['mimetype']);
        } elseif (isAudio($v['filename']) || strpos((string)$v['mimetype'], 'audio/') === 0) {
            $data['media_type'] = 'audio';
            $data['media_fa'] = getMediaFaIcon($v['filename'], $v['mimetype']);
        } else {
            $data['media_type'] = 'file';
            $data['media_fa'] = getMediaFaIcon($v['filename'], $v['mimetype']);
        }
        $ret['images'][] = $data;
    }
    Output::ok($ret);
}

if (isset($_GET['uploade']) && isset($_FILES)) {
    $editor = isset($_GET['editor']) ? 1 : 0; // 是否来自Markdown编辑器的上传
    $editorMode = '';
    if ($editor) {
        // 编辑器 iframe 上传需返回 JSON，避免 token 失败时输出 HTML 导致解析报错
        LoginAuth::checkTokenJson();
    } else {
        hope_admin_require_token();
    }
    $sid = Input::getIntVar('sid');
    $list = isset($_POST['list']) ? 1 : 0; // 是否来自列表的上传
    $attach = $_FILES['file'] ?? '';
    if ($editor) {
        if (!empty($_FILES['editormd-video-file']['name'])) {
            $attach = $_FILES['editormd-video-file'];
            $editorMode = 'video';
        } elseif (!empty($_FILES['editormd-image-file']['name'])) {
            $attach = $_FILES['editormd-image-file'];
            $editorMode = 'image';
        } elseif (!empty($_FILES['file']['name'])) {
            $attach = $_FILES['file'];
            $editorMode = 'image';
        } else {
            $attach = '';
        }
    }

    if (!User::haveEditPermission() && Option::get('forbid_user_upload') === 'y') {
        Upload::uploadRespond(['success' => 0, 'message' => '系统关闭了资源上传'], $editor);
    }

    $uploadCheckResult = Upload::checkUpload($attach, $editorMode);
    if ($uploadCheckResult !== true) {
        Upload::uploadRespond(['success' => 0, 'message' => $uploadCheckResult], $editor);
    }

    $ret = '';

    addAction('upload_media', 'upload2local');
    doOnceAction('upload_media', $attach, $ret);

    if (empty($ret['success'])) {
        if ($list) {
            Output::error($ret['message'] ?? '上传失败');
        }
        Upload::uploadRespond($ret ?: ['success' => 0, 'message' => '上传失败'], $editor);
    }

    $aid = $Upload_Model->addMedia($ret['file_info'], $sid);
    if ($list) {
        Output::ok($ret);
    }
    Upload::uploadRespond($ret, $editor, true);
}
if (isset($_GET['update']) && isset($_POST)) {
    hope_admin_require_token();
    $name = Input::postStrVar('name');
    $id = Input::postIntVar('id');

    if (empty($name)) {
        Gohref(SELF."?act=upload&code=a");
    }

    $Upload_Model->updateMedia(["filename" => $name], $id);
    Gohref(SELF."?act=upload&code=edit");
}

if (isset($_GET['add_external']) && isset($_POST)) {
    hope_admin_require_token();
    $url = trim(Input::postStrVar('url'));
    $name = trim(Input::postStrVar('name'));
    $sid = Input::postIntVar('sid');
    $mimetype = trim(Input::postStrVar('mimetype'));

    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
        Gohref(SELF . "?act=upload&code=badurl");
    }

    if (empty($name)) {
        $path = (string)parse_url($url, PHP_URL_PATH);
        $name = $path !== '' ? urldecode(basename($path)) : '';
        if ($name === '' || $name === '/' || $name === '.') {
            $name = 'external-resource';
        }
    }

    if (empty($mimetype)) {
        $ext = getFileSuffix($name);
        if ($ext === '') {
            $ext = getFileSuffix((string)parse_url($url, PHP_URL_PATH));
        }
        $mimetype = get_mimetype($ext);
    }

    $Upload_Model->addMedia([
        'file_path' => $url,
        'file_name' => $name,
        'size'      => 0,
        'width'     => 0,
        'height'    => 0,
        'mime_type' => $mimetype,
    ], $sid);

    Gohref(SELF . '?act=upload&code=add' . ($sid ? '&sid=' . $sid : ''));
}



if (isset($_GET['del']) && isset($_GET['aid'])) {
    hope_admin_require_token();
    $aid = Input::getIntVar('aid');
    $Upload_Model->deleteMedia($aid);
    $CACHE->updateCache('sta');
    Gohref(SELF."?act=upload&8");
}

if (isset($_GET['update_sort']) && isset($_POST['sortname'])) {
    hope_admin_require_token();
    if (!User::isAdmin()) {
        hpMsg('权限不足！', SELF."?act=upload");
    }
    $sortname = Input::postStrVar('sortname');
    $id = (int)($_POST['id'] ?? '');

    if (empty($sortname)) {
        Gohref(SELF."?act=upload&code=error");
    }

    $UploadSort_Model->updateSort(["sortname" => $sortname], $id);
    Gohref(SELF."?act=upload&code=edit");
}
if (isset($_GET['add_sort']) && isset($_POST['sortname'])) {
    hope_admin_require_token();
    if (!User::isAdmin()) {
        hpMsg('权限不足！', SELF."?act=upload");
    }
    $sortname = Input::postStrVar('sortname');
    if (empty($sortname)) {
        Gohref(SELF."?act=upload&code=error");
    }
    $UploadSort_Model->addSort($sortname);
    Gohref(SELF."?act=upload&code=add");
}

if (isset($_GET['operate'])) {
    hope_admin_require_token();
    $operate = Input::postStrVar('operate');
    $sort = Input::postIntVar('sort');
    $aids = isset($_POST['aids']) ? array_map('intval', $_POST['aids']) : array();

    switch ($operate) {
        case 'del':
            foreach ($aids as $value) {
                $Upload_Model->deleteMedia($value);
            }
            Gohref(SELF."?act=upload&code=del");
            break;
        case 'move':
            foreach ($aids as $id) {
                $Upload_Model->updateMedia(['sortid' => $sort], $id);
            }
            Gohref(SELF."?act=upload&code=mov");
            break;
    }
}

$sid = Input::getIntVar('sid');
$page = Input::getIntVar('page', 1);
$date = Input::getStrVar('date');
$uid = Input::getStrVar('uid');
$keyword = Input::getStrVar('keyword');
if (!User::haveEditPermission()) {
    $uid = UID;
}
$page_count = 24;
$medias = $Upload_Model->getMedias($page, $page_count, $uid, $sid, $date, $keyword);
$count = $Upload_Model->getMediaCount($uid, $sid, $date, $keyword);
$page_url = pagination($count, $page_count, $page, SELF.'?act=upload'.($sid ? "sid=$sid&" : '').($date ? "date=$date&" : '').($uid ? "uid=$uid&" : '').($keyword ? "keyword=$keyword&" : '').'&page=');

$sorts = $UploadSort_Model->getSorts();
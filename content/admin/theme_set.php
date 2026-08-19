<?php defined('HOPE_ROOT') || exit('access denied!');?>
<link href="<?= SITE_URL ?>content/admin/css/opt-style.css" rel="stylesheet" />
<link href="<?= SITE_URL ?>content/admin/css/opt-min.css" rel="stylesheet" />
<link href="<?= SITE_URL ?>content/admin/css/remixicon.min.css" rel="stylesheet" /><!--Remixicon图标-->



<link href="<?= SITE_URL ?>content/admin/assets/css/monolith.min.css" rel="stylesheet" /><!--Pickr 颜色选择器-->
<link href="<?= SITE_URL ?>content/admin/assets/css/classic.min.css" rel="stylesheet" /><!--Pickr 颜色选择器-->
<link href="<?= SITE_URL ?>content/admin/assets/css/nano.min.css" rel="stylesheet" /><!--Pickr 颜色选择器-->
<style>
ul li input[type=text] {
    display: block;
}
</style>
<?php CSF::setup(); ?>
<script src="<?= SITE_URL ?>content/admin/assets/js/pickr.es5.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script><!--Pickr 颜色选择器-->
<script src="<?= SITE_URL ?>content/admin/assets/js/opt-plugins.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script id="hope-js-extra">
    var hope_vars = {
        "color_palette": [],
        "i18n": {
            "confirm": "\u662f\u5426\u786e\u5b9a?",
            "typing_text": "\u8bf7\u8f93\u5165\u81f3\u5c11 %s \u5b57\u7b26",
            "searching_text": "\u641c\u7d22\u4e2d...",
            "no_results_text": "\u672a\u627e\u5230\u7ed3\u679c\u3002"
        }
    };
</script>

<script src="<?= SITE_URL ?>content/admin/assets/js/opt-main.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>


<script> 
	$("#menu_surface_manage").addClass('active');
	$("#menu_surface").addClass('show');
	$("#menu_theme").addClass('active');
</script>
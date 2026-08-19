/**
 *
 * -----------------------------------------------------------
 *
 * Codestar Framework
 * A Simple and Lightweight WordPress Option Framework
 *
 * -----------------------------------------------------------
 *
 */
(function ($, window, document, undefined) {
    'use strict';

    //
    // Constants
    //
    var CSF = CSF || {};

    CSF.funcs = {};

    CSF.vars = {
        onloaded: false,
        $body: $('body'),
        $window: $(window),
        $document: $(document),
        $form_warning: null,
        is_confirm: false,
        form_modified: false,
        code_themes: [],
        is_rtl: $('body').hasClass('rtl'),
    };

    //
    // Helper Functions
    //
    CSF.helper = {
        //
        // Generate UID
        //
        uid: function (prefix) {
            return (prefix || '') + Math.random().toString(36).substr(2, 9);
        },

        // Quote regular expression characters
        //
        preg_quote: function (str) {
            return (str + '').replace(/(\[|\])/g, '\\$1');
        },

        //
        // Reneme input names
        //
        name_nested_replace: function ($selector, field_id) {
            var checks = [];
            var regex = new RegExp(CSF.helper.preg_quote(field_id + '[\\d+]'), 'g');

            $selector.find(':radio').each(function () {
                if (this.checked || this.orginal_checked) {
                    this.orginal_checked = true;
                }
            });

            $selector.each(function (index) {
                $(this)
                    .find(':input')
                    .each(function () {
                        this.name = this.name.replace(regex, field_id + '[' + index + ']');
                        if (this.orginal_checked) {
                            this.checked = true;
                        }
                    });
            });
        },

        //
        // Debounce
        //
        debounce: function (callback, threshold, immediate) {
            var timeout;
            return function () {
                var context = this,
                    args = arguments;
                var later = function () {
                    timeout = null;
                    if (!immediate) {
                        callback.apply(context, args);
                    }
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, threshold);
                if (callNow) {
                    callback.apply(context, args);
                }
            };
        },
    };

    //
    // Custom clone for textarea and select clone() bug
    //
    $.fn.hope_clone = function () {
        var base = $.fn.clone.apply(this, arguments),
            clone = this.find('select').add(this.filter('select')),
            cloned = base.find('select').add(base.filter('select'));

        for (var i = 0; i < clone.length; ++i) {
            for (var j = 0; j < clone[i].options.length; ++j) {
                if (clone[i].options[j].selected === true) {
                    cloned[i].options[j].selected = true;
                }
            }
        }

        this.find(':radio').each(function () {
            this.orginal_checked = this.checked;
        });

        return base;
    };

    //
    // Expand All Options
    //
    $.fn.hope_expand_all = function () {
        return this.each(function () {
            $(this).on('click', function (e) {
                e.preventDefault();
                $('.hope-wrapper').toggleClass('hope-show-all');
                $('.hope-section').hope_reload_script();
                $(this).find('.fa').toggleClass('fa-indent').toggleClass('fa-outdent');
            });
        });
    };
        
    //
    // Options Navigation
    //
    $.fn.hope_nav_options = function () {
        return this.each(function () {
            var $nav = $(this),
                $links = $nav.find('a'),
                $last;

            $(window)
                .on('hashchange hope.hashchange', function () {
                    var hash = window.location.hash.replace('#tab=', '');
                    var slug = decodeURIComponent(hash ? hash : $links.first().attr('href').replace('#tab=', ''));
                    var $link = $('[data-tab-id="' + slug + '"]');

                    if ($link.length) {
                        $link.closest('.hope-tab-item').addClass('hope-tab-expanded').siblings().removeClass('hope-tab-expanded');

                        if ($link.next().is('ul')) {
                            $link = $link.next().find('li').first().find('a');
                            slug = $link.data('tab-id');
                        }

                        $links.removeClass('hope-active');
                        $link.addClass('hope-active');

                        if ($last) {
                            $last.addClass('hidden');
                        }

                        var $section = $('[data-section-id="' + slug + '"]');

                        $section.removeClass('hidden');
                        $section.hope_reload_script();

                        $('.hope-section-id').val($section.index() + 1);

                        $last = $section;
                    }
                })
                .trigger('hope.hashchange');
        });
    };

    //
    // Metabox Tabs
    //
    $.fn.hope_nav_metabox = function () {
        return this.each(function () {
            var $nav = $(this),
                $links = $nav.find('a'),
                $sections = $nav.parent().find('.hope-section'),
                $last;

            $links.each(function (index) {
                $(this).on('click', function (e) {
                    e.preventDefault();

                    var $link = $(this);

                    $links.removeClass('hope-active');
                    $link.addClass('hope-active');

                    if ($last !== undefined) {
                        $last.addClass('hidden');
                    }

                    var $section = $sections.eq(index);

                    $section.removeClass('hidden');
                    $section.hope_reload_script();

                    $last = $section;
                });
            });

            $links.first().trigger('click');
        });
    };

    //
    // Metabox Page Templates Listener
    //
    $.fn.hope_page_templates = function () {
        if (this.length) {
            $(document).on('change', '.editor-page-attributes__template select, #page_template', function () {
                var maybe_value = $(this).val() || 'default';

                $('.hope-page-templates').removeClass('hope-metabox-show').addClass('hope-metabox-hide');
                $('.hope-page-' + maybe_value.toLowerCase().replace(/[^a-zA-Z0-9]+/g, '-'))
                    .removeClass('hope-metabox-hide')
                    .addClass('hope-metabox-show');
            });
        }
    };

    //
    // Metabox Post Formats Listener
    //
    $.fn.hope_post_formats = function () {
        if (this.length) {
            $(document).on('change', '.editor-post-format select, #formatdiv input[name="post_format"]', function () {
                var maybe_value = $(this).val() || 'default';

                // Fallback for classic editor version
                maybe_value = maybe_value === '0' ? 'default' : maybe_value;

                $('.hope-post-formats').removeClass('hope-metabox-show').addClass('hope-metabox-hide');
                $('.hope-post-format-' + maybe_value)
                    .removeClass('hope-metabox-hide')
                    .addClass('hope-metabox-show');
            });
        }
    };

    //
    // Search
    //
    $.fn.hope_search = function () {
        return this.each(function () {
            var $this = $(this),
                $input = $this.find('input');

            $input.on(
                'change input',
                CSF.helper.debounce(function () {
                    var value = $(this).val(),
                        $wrapper = $('.hope-wrapper'),
                        $section = $wrapper.find('.hope-section'),
                        $fields = $section.find('> .hope-field:not(.hope-depend-on)');

                    if (value.length > 1) {
                        $fields.addClass('hope-metabox-hide');
                        $wrapper.addClass('hope-search-all');

                        //修改内容：搜索增加对字段集的搜索
                        $fields.each(function () {
                            var $title = $(this);
                            var text = '';
                            $(this)
                                .find('.hope-title,.hope-fieldset>.hope--label,.hope-fieldset>.hope-desc-text, .hope-search-tags')
                                .each(function () {
                                    text += $(this).text();
                                });

                            if (text.match(new RegExp('.*?' + value + '.*?', 'i'))) {
                                var $field = $title.closest('.hope-field');

                                $field.removeClass('hope-metabox-hide');
                                $field.parent().hope_reload_script();
                            }
                        });
                    } else {
                        $fields.removeClass('hope-metabox-hide');
                        $wrapper.removeClass('hope-search-all');
                    }
                }, 500)
            );
        });
    };

    //
    // Sticky Header
    //
    $.fn.hope_sticky = function () {
        return this.each(function () {
            var $this = $(this),
                $window = $(window),
                $inner = $this.find('.hope-header-inner'),
                padding = 0,
                //padding = parseInt($inner.css('padding-left')) + parseInt($inner.css('padding-right')),
                offset = 32,
                scrollTop = 0,
                lastTop = 0,
                ticking = false,
                stickyUpdate = function () {
                    var offsetTop = $this.offset().top,
                        stickyTop = Math.max(offset, offsetTop - scrollTop),
                        winWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);

                    if (stickyTop <= offset && winWidth > 782) {
                        $inner.css({
                            width: $this.outerWidth() - padding,
                        });
                        $this
                            .css({
                                height: $this.outerHeight(),
                            })
                            .addClass('hope-sticky');
                    } else {
                        $inner.removeAttr('style');
                        $this.removeAttr('style').removeClass('hope-sticky');
                    }
                },
                requestTick = function () {
                    if (!ticking) {
                        requestAnimationFrame(function () {
                            stickyUpdate();
                            ticking = false;
                        });
                    }

                    ticking = true;
                },
                onSticky = function () {
                    scrollTop = $window.scrollTop();
                    requestTick();
                };

            $window.on('scroll resize', onSticky);

            onSticky();
        });
    };

    //
    // Dependency System
    //
    $.fn.hope_dependency = function () {
        return this.each(function () {
            var $this = $(this),
                $fields = $this.children('[data-controller]');

            if ($fields.length) {
                var normal_ruleset = $.hope_deps.createRuleset(),
                    global_ruleset = $.hope_deps.createRuleset(),
                    normal_depends = [],
                    global_depends = [];

                $fields.each(function () {
                    var $field = $(this),
                        controllers = $field.data('controller').split('|'),
                        conditions = $field.data('condition').split('|'),
                        values = $field.data('value').toString().split('|'),
                        is_global = $field.data('depend-global') ? true : false,
                        ruleset = is_global ? global_ruleset : normal_ruleset;

                    $.each(controllers, function (index, depend_id) {
                        var value = values[index] || '',
                            condition = conditions[index] || conditions[0];

                        ruleset = ruleset.createRule('[data-depend-id="' + depend_id + '"]', condition, value);

                        ruleset.include($field);

                        if (is_global) {
                            global_depends.push(depend_id);
                        } else {
                            normal_depends.push(depend_id);
                        }
                    });
                });

                if (normal_depends.length) {
                    $.hope_deps.enable($this, normal_ruleset, normal_depends);
                }

                if (global_depends.length) {
                    $.hope_deps.enable(CSF.vars.$body, global_ruleset, global_depends);
                }
            }
        });
    };

    //
    // Field: accordion
    //
    $.fn.hope_field_accordion = function () {
        return this.each(function () {
            var $titles = $(this).find('.hope-accordion-title');

            $titles.on('click', function () {
                var $title = $(this),
                    $icon = $title.find('.hope-accordion-icon'),
                    $content = $title.next();

                if ($icon.hasClass('fa-angle-right')) {
                    $icon.removeClass('fa-angle-right').addClass('fa-angle-down');
                } else {
                    $icon.removeClass('fa-angle-down').addClass('fa-angle-right');
                }

                if (!$content.data('opened')) {
                    $content.hope_reload_script();
                    $content.data('opened', true);
                }

                $content.toggleClass('hope-accordion-open');
            });
        });
    };

    //
    // Field: backup
    //
    $.fn.hope_field_backup = function () {
        return this.each(function () {

            // 定义一个基础变量，用于存储当前对象的引用
            var base = this,
                // 将当前 jQuery 对象存储在 $this 变量中
                $this = $(this),
                // 获取 body 元素的 jQuery 对象
                $body = $('body'),
                // 查找导入按钮元素
                $import = $this.find('.hope-import'),
                // 查找导出按钮元素
                $export = $this.find('.hope-export');
                
            $export.on('click', function (e) {
                e.preventDefault();
                
                var theme = $body.find('.hope').data('unique');
                var exportUrl = '?act=theme_set&export_options&theme=' + encodeURIComponent(theme);
                
                // 使用 XMLHttpRequest 来更好地处理响应
                var xhr = new XMLHttpRequest();
                xhr.open('GET', exportUrl, true);
                xhr.responseType = 'blob'; // 设置响应类型为 blob 以处理文件下载
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // 成功响应，处理文件下载
                        var blob = xhr.response;
                        var disposition = xhr.getResponseHeader('Content-Disposition');
                        var filename = "backup-" + new Date().toISOString().slice(0, 10) + ".json";
                        
                        // 尝试从响应头获取文件名
                        if (disposition && disposition.indexOf('attachment') !== -1) {
                            var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                            var matches = filenameRegex.exec(disposition);
                            if (matches != null && matches[1]) { 
                                filename = matches[1].replace(/['"]/g, '');
                            }
                        }
                        
                        // 创建下载链接
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        // 处理错误响应
                        var reader = new FileReader();
                        reader.onload = function() {
                            try {
                                var errorResponse = JSON.parse(reader.result);
                                if (errorResponse.error) {
                                    Toast.fire({ icon: 'error', title: '导出失败: ' + errorResponse.error});
                                } else {
                                    Toast.fire({ icon: 'error', title: '导出失败，请先保存主题设置后再导出！'});
                                }
                            } catch (e) {
                                Toast.fire({ icon: 'error', title: '导出失败，请先保存主题设置后再导出！'});
                            }
                        };
                        reader.readAsText(xhr.response);
                    }
                };
                
                xhr.onerror = function() {
                    alert('导出失败，网络错误，请重试！');
                };
                
                xhr.send();
            });
                

            $import.on('click', function (e) {
                e.preventDefault();

                $.ajax("?act=theme_set&import_options",{
                    method: "POST",
                    data: {
                        theme: $body.find('.hope').data('unique'),
                        data: $this.find('.hope-import-data').val(),
                    },
                })
                .done(function(response) {
                    if (hopeApiSuccess(response.code)) {
                        Toast.fire({ icon: 'success', title: response.data });
                        setTimeout(function() {
                            window.location.reload(true);
                        }, 5000);
                    }else{
                        Toast.fire({ icon: 'error', title: response.msg });
                    }
                })
                .fail(function(jqXHR, textStatus) {
                    if(jqXHR.responseJSON.code==0){
                        Toast.fire({ icon: 'error', title: jqXHR.responseJSON.msg });
                    }
                })





            });
        });
    };

    //
    // Field: background
    //
    $.fn.hope_field_background = function () {
        return this.each(function () {
            $(this).find('.hope--background-image').hope_reload_script();
        });
    };

    //
    // Field: code_editor
    //
    $.fn.hope_field_code_editor = function () {
        return this.each(function () {
            if (typeof CodeMirror !== 'function') {
                return;
            }

            var $this = $(this),
                $textarea = $this.find('textarea'),
                $inited = $this.find('.CodeMirror'),
                data_editor = $textarea.data('editor');

            if ($inited.length) {
                $inited.remove();
            }

            var interval = setInterval(function () {
                if ($this.is(':visible')) {
                    var code_editor = CodeMirror.fromTextArea($textarea[0], data_editor);

                    // load code-mirror theme css.
                    if (data_editor.theme !== 'default' && CSF.vars.code_themes.indexOf(data_editor.theme) === -1) {
                        var $cssLink = $('<link>');

                        $('#hope-codemirror-css').after($cssLink);

                        $cssLink.attr({
                            rel: 'stylesheet',
                            id: 'hope-codemirror-' + data_editor.theme + '-css',
                            href: data_editor.cdnURL + '/theme/' + data_editor.theme + '.css',
                            type: 'text/css',
                            media: 'all',
                        });

                        CSF.vars.code_themes.push(data_editor.theme);
                    }

                    CodeMirror.modeURL = data_editor.cdnURL + '/mode/%N/%N.js';
                    CodeMirror.autoLoadMode(code_editor, data_editor.mode);

                    code_editor.on('change', function (editor, event) {
                        $textarea.val(code_editor.getValue()).trigger('change');
                    });

                    clearInterval(interval);
                }
            });
        });
    };

    //
    // Field: date
    //
    $.fn.hope_field_date = function () {
        return this.each(function () {
            var $this = $(this),
                $inputs = $this.find('input'),
                settings = $this.find('.hope-date-settings').data('settings'),
                wrapper = '<div class="hope-datepicker-wrapper"></div>',
                $datepicker;
                
                $.datepicker.regional['zh-CN'] = {
                    closeText: '关闭',
                    prevText: '<上月',
                    nextText: '下月>',
                    currentText: '今天',
                    monthNames: ['一月','二月','三月','四月','五月','六月','七月','八月','九月','十月','十一月','十二月'],
                    monthNamesShort: ['一','二','三','四','五','六','七','八','九','十','十一','十二'],
                    dayNames: ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'],
                    dayNamesShort: ['周日','周一','周二','周三','周四','周五','周六'],
                    dayNamesMin: ['日','一','二','三','四','五','六'],
                    weekHeader: '周',
                    dateFormat: 'yy-mm-dd',
                    firstDay: 1,
                    isRTL: false,
                    showMonthAfterYear: true,
                    yearSuffix: '年'
                };
                $.datepicker.setDefaults($.datepicker.regional['zh-CN']);

            var defaults = {
                showAnim: '',
                beforeShow: function (input, inst) {
                    $(inst.dpDiv).addClass('hope-datepicker-wrapper');
                },
                onClose: function (input, inst) {
                    $(inst.dpDiv).removeClass('hope-datepicker-wrapper');
                },
            };

            settings = $.extend({}, settings, defaults);

            if ($inputs.length === 2) {
                settings = $.extend({}, settings, {
                    onSelect: function (selectedDate) {
                        var $this = $(this),
                            $from = $inputs.first(),
                            option = $inputs.first().attr('id') === $(this).attr('id') ? 'minDate' : 'maxDate',
                            date = $.datepicker.parseDate(settings.dateFormat, selectedDate);

                        $inputs.not(this).datepicker('option', option, date);
                    },
                });
            }

            $inputs.each(function () {
                var $input = $(this);

                if ($input.hasClass('hasDatepicker')) {
                    $input.removeAttr('id').removeClass('hasDatepicker');
                }

                $input.datepicker(settings);
            });
        });
    };

    //
    // Field: fieldset
    //
    $.fn.hope_field_fieldset = function () {
        return this.each(function () {
            $(this).find('.hope-fieldset-content').hope_reload_script();
        });
    };

    //
    // Field: gallery
    //
    $.fn.hope_field_gallery = function () {
        return this.each(function () {
            var $this = $(this),
                $edit = $this.find('.hope-edit-gallery'),
                $clear = $this.find('.hope-clear-gallery'),
                $list = $this.find('ul'),
                $input = $this.find('input'),
                $img = $this.find('img'),
                wp_media_frame;

            $this.on('click', '.hope-button, .hope-edit-gallery', function (e) {
                var $el = $(this),
                    ids = $input.val(),
                    what = $el.hasClass('hope-edit-gallery') ? 'edit' : 'add',
                    state = what === 'add' && !ids.length ? 'gallery' : 'gallery-edit';

                e.preventDefault();

                if (typeof window.wp === 'undefined' || !window.wp.media || !window.wp.media.gallery) {
                    return;
                }

                // Open media with state
                if (state === 'gallery') {
                    wp_media_frame = window.wp.media({
                        library: {
                            type: 'image',
                        },
                        frame: 'post',
                        state: 'gallery',
                        multiple: true,
                    });

                    wp_media_frame.open();
                } else {
                    wp_media_frame = window.wp.media.gallery.edit('[gallery ids="' + ids + '"]');

                    if (what === 'add') {
                        wp_media_frame.setState('gallery-library');
                    }
                }

                // Media Update
                wp_media_frame.on('update', function (selection) {
                    $list.empty();

                    var selectedIds = selection.models.map(function (attachment) {
                        var item = attachment.toJSON();
                        var thumb = item.sizes && item.sizes.thumbnail && item.sizes.thumbnail.url ? item.sizes.thumbnail.url : item.url;

                        $list.append('<li><img src="' + thumb + '"></li>');

                        return item.id;
                    });

                    $input.val(selectedIds.join(',')).trigger('change');
                    $clear.removeClass('hidden');
                    $edit.removeClass('hidden');
                });
            });

            $clear.on('click', function (e) {
                e.preventDefault();
                $list.empty();
                $input.val('').trigger('change');
                $clear.addClass('hidden');
                $edit.addClass('hidden');
            });
        });
    };

    //
    // Field: group
    //
    $.fn.hope_field_group = function () {
        return this.each(function () {
            var $this = $(this),
                $fieldset = $this.children('.hope-fieldset'),
                $group = $fieldset.length ? $fieldset : $this,
                $wrapper = $group.children('.hope-cloneable-wrapper'),
                $hidden = $group.children('.hope-cloneable-hidden'),
                $max = $group.children('.hope-cloneable-max'),
                $min = $group.children('.hope-cloneable-min'),
                field_id = $wrapper.data('field-id'),
                is_number = Boolean(Number($wrapper.data('title-number'))),
                max = parseInt($wrapper.data('max')),
                min = parseInt($wrapper.data('min'));

            // clear accordion arrows if multi-instance
            if ($wrapper.hasClass('ui-accordion')) {
                $wrapper.find('.ui-accordion-header-icon').remove();
            }

            var update_title_numbers = function ($selector) {
                $selector.find('.hope-cloneable-title-number').each(function (index) {
                    $(this).html($(this).closest('.hope-cloneable-item').index() + 1 + '.');
                });
            };

            $wrapper.accordion({
                header: '> .hope-cloneable-item > .hope-cloneable-title',
                collapsible: true,
                active: false,
                animate: false,
                heightStyle: 'content',
                icons: {
                    header: 'hope-cloneable-header-icon fa fa-angle-right',
                    activeHeader: 'hope-cloneable-header-icon fa fa-angle-down',
                },
                activate: function (event, ui) {
                    var $panel = ui.newPanel;
                    var $header = ui.newHeader;

                    if ($panel.length && !$panel.data('opened')) {
                        var $fields = $panel.children();
                        var $first = $fields.first().find(':input').first();
                        var $title = $header.find('.hope-cloneable-value');

                        $first.on('change keyup', function (event) {
                            $title.text($first.val());
                        });

                        $panel.hope_reload_script();
                        $panel.data('opened', true);
                        $panel.data('retry', false);
                    } else if ($panel.data('retry')) {
                        $panel.hope_reload_script_retry();
                        $panel.data('retry', false);
                    }
                },
            });

            $wrapper.sortable({
                axis: 'y',
                handle: '.hope-cloneable-title,.hope-cloneable-sort',
                helper: 'original',
                revert: 100,
                cursor: 'move',
                placeholder: 'widget-placeholder',
                start: function (event, ui) {
                    $wrapper.accordion({
                        active: false,
                    });
                    $wrapper.sortable('refreshPositions');
                    ui.item.children('.hope-cloneable-content').data('retry', true);
                },
                update: function () {
                    CSF.helper.name_nested_replace($wrapper.children('.hope-cloneable-item'), field_id);
                    $wrapper.hope_customizer_refresh();

                    if (is_number) {
                        update_title_numbers($wrapper);
                    }
                },
            });

            $group.children('.hope-cloneable-add').on('click', function (e) {
                e.preventDefault();

                var count = $wrapper.children('.hope-cloneable-item').length;

                $min.hide();

                if (max && count + 1 > max) {
                    $max.show();
                    return;
                }

                var $cloned_item = $hidden.hope_clone(true);

                $cloned_item.removeClass('hope-cloneable-hidden');

                $cloned_item.find(':input[name!="_pseudo"]').each(function () {
                    console.log(this.name, field_id);

                    this.name = this.name.replace('___', '').replace(field_id + '[0]', field_id + '[' + count + ']');
                    console.log(this.name);
                });

                $wrapper.append($cloned_item);
                $wrapper.accordion('refresh');
                $wrapper.accordion({
                    active: count,
                });
                $wrapper.hope_customizer_refresh();
                $wrapper.hope_customizer_listen({
                    closest: true,
                });

                if (is_number) {
                    update_title_numbers($wrapper);
                }
            });

            var event_clone = function (e) {
                e.preventDefault();

                var count = $wrapper.children('.hope-cloneable-item').length;

                $min.hide();

                if (max && count + 1 > max) {
                    $max.show();
                    return;
                }

                var $this = $(this),
                    $parent = $this.parent().parent(),
                    $cloned_helper = $parent.children('.hope-cloneable-helper').hope_clone(true),
                    $cloned_title = $parent.children('.hope-cloneable-title').hope_clone(),
                    $cloned_content = $parent.children('.hope-cloneable-content').hope_clone(),
                    $cloned_item = $('<div class="hope-cloneable-item" />');

                $cloned_item.append($cloned_helper);
                $cloned_item.append($cloned_title);
                $cloned_item.append($cloned_content);

                $wrapper.children().eq($parent.index()).after($cloned_item);

                CSF.helper.name_nested_replace($wrapper.children('.hope-cloneable-item'), field_id);

                $wrapper.accordion('refresh');
                $wrapper.hope_customizer_refresh();
                $wrapper.hope_customizer_listen({
                    closest: true,
                });

                if (is_number) {
                    update_title_numbers($wrapper);
                }
            };

            $wrapper.children('.hope-cloneable-item').children('.hope-cloneable-helper').on('click', '.hope-cloneable-clone', event_clone);
            $group.children('.hope-cloneable-hidden').children('.hope-cloneable-helper').on('click', '.hope-cloneable-clone', event_clone);

            var event_remove = function (e) {
                e.preventDefault();

                var count = $wrapper.children('.hope-cloneable-item').length;

                $max.hide();
                $min.hide();

                if (min && count - 1 < min) {
                    $min.show();
                    return;
                }

                $(this).closest('.hope-cloneable-item').remove();

                CSF.helper.name_nested_replace($wrapper.children('.hope-cloneable-item'), field_id);

                $wrapper.hope_customizer_refresh();

                if (is_number) {
                    update_title_numbers($wrapper);
                }
            };

            $wrapper.children('.hope-cloneable-item').children('.hope-cloneable-helper').on('click', '.hope-cloneable-remove', event_remove);
            $group.children('.hope-cloneable-hidden').children('.hope-cloneable-helper').on('click', '.hope-cloneable-remove', event_remove);
        });
    };

    //
    // Field: icon
    //
    $.fn.hope_field_icon = function () {
        return this.each(function () {
            var $this = $(this);

            $this.on('click', '.hope-icon-add', function (e) {
                e.preventDefault();

                var $button = $(this);
                var $modal = $('#hope-modal-icon');

                $modal.removeClass('hidden');

                CSF.vars.$icon_target = $this;

                if (!CSF.vars.icon_modal_loaded) {
                    $modal.find('.hope-modal-loading').show();
                    $.post('?act=theme_set&hope-get-icons', {
                        nonce: $button.data('nonce'),
                    })
                    .done(function (response) {
                        $modal.find('.hope-modal-loading').hide();

                        CSF.vars.icon_modal_loaded = true;

                        var $load = $modal.find('.hope-modal-load').html(response);
                        //修改内容-添加图标的挂钩
                        $modal.trigger('icon.loaded');

                        $load.on('click', 'i', function (e) {
                            e.preventDefault();

                            var icon = $(this).attr('title');

                            CSF.vars.$icon_target.find('.hope-icon-preview i').removeAttr('class').addClass(icon);
                            CSF.vars.$icon_target.find('.hope-icon-preview').removeClass('hidden');
                            CSF.vars.$icon_target.find('.hope-icon-remove').removeClass('hidden');
                            CSF.vars.$icon_target.find('input').val(icon).trigger('change');

                            $modal.addClass('hidden').trigger('icon.loaded');
                        });

                        $modal.on('change keyup', '.hope-icon-search', function () {
                            var value = $(this).val(),
                                $icons = $load.find('i');

                            $icons.each(function () {
                                var $elem = $(this);

                                if ($elem.attr('title').search(new RegExp(value, 'i')) < 0) {
                                    $elem.hide();
                                } else {
                                    $elem.show();
                                }
                            });
                        });

                        $modal.on('click', '.hope-modal-close, .hope-modal-overlay', function () {
                            $modal.addClass('hidden');
                        });
                    })
                    .fail(function (jqXHR, textStatus) {
                        $modal.find('.hope-modal-loading').hide();
                        $modal.find('.hope-modal-load').html(textStatus);
                        $modal.on('click', function () {
                            $modal.addClass('hidden');
                        });
                    });
                }
            });

            $this.on('click', '.hope-icon-remove', function (e) {
                e.preventDefault();
                $this.find('.hope-icon-preview').addClass('hidden');
                $this.find('input').val('').trigger('change');
                $(this).addClass('hidden');
            });
        });
    };

    //
    // Field: map
    //
    $.fn.hope_field_map = function () {
        return this.each(function () {
            if (typeof L === 'undefined') {
                return;
            }

            var $this = $(this),
                $map = $this.find('.hope--map-osm'),
                $search_input = $this.find('.hope--map-search input'),
                $latitude = $this.find('.hope--latitude'),
                $longitude = $this.find('.hope--longitude'),
                $zoom = $this.find('.hope--zoom'),
                map_data = $map.data('map');

            var mapInit = L.map($map.get(0), map_data);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(mapInit);

            var mapMarker = L.marker(map_data.center, {
                draggable: true,
            }).addTo(mapInit);

            var update_latlng = function (data) {
                $latitude.val(data.lat);
                $longitude.val(data.lng);
                $zoom.val(mapInit.getZoom());
            };

            mapInit.on('click', function (data) {
                mapMarker.setLatLng(data.latlng);
                update_latlng(data.latlng);
            });

            mapInit.on('zoom', function () {
                update_latlng(mapMarker.getLatLng());
            });

            mapMarker.on('drag', function () {
                update_latlng(mapMarker.getLatLng());
            });

            if (!$search_input.length) {
                $search_input = $('[data-depend-id="' + $this.find('.hope--address-field').data('address-field') + '"]');
            }

            var cache = {};

            $search_input.autocomplete({
                source: function (request, response) {
                    var term = request.term;

                    if (term in cache) {
                        response(cache[term]);
                        return;
                    }

                    $.get(
                        'https://nominatim.openstreetmap.org/search',
                        {
                            format: 'json',
                            q: term,
                        },
                        function (results) {
                            var data;

                            if (results.length) {
                                data = results.map(function (item) {
                                    return {
                                        value: item.display_name,
                                        label: item.display_name,
                                        lat: item.lat,
                                        lon: item.lon,
                                    };
                                }, 'json');
                            } else {
                                data = [
                                    {
                                        value: 'no-data',
                                        label: 'No Results.',
                                    },
                                ];
                            }

                            cache[term] = data;
                            response(data);
                        }
                    );
                },
                select: function (event, ui) {
                    if (ui.item.value === 'no-data') {
                        return false;
                    }

                    var latLng = L.latLng(ui.item.lat, ui.item.lon);

                    mapInit.panTo(latLng);
                    mapMarker.setLatLng(latLng);
                    update_latlng(latLng);
                },
                create: function (event, ui) {
                    $(this).autocomplete('widget').addClass('hope-map-ui-autocomplate');
                },
            });

            var input_update_latlng = function () {
                var latLng = L.latLng($latitude.val(), $longitude.val());

                mapInit.panTo(latLng);
                mapMarker.setLatLng(latLng);
            };

            $latitude.on('change', input_update_latlng);
            $longitude.on('change', input_update_latlng);
        });
    };

    //
    // Field: link
    //
    $.fn.hope_field_link = function () {
        return this.each(function () {
            var $this = $(this),
                $link = $this.find('.hope--link'),
                $add = $this.find('.hope--add'),
                $edit = $this.find('.hope--edit'),
                $remove = $this.find('.hope--remove'),
                $result = $this.find('.hope--result'),
                uniqid = CSF.helper.uid('hope-wplink-textarea-');

            $add.on('click', function (e) {
                e.preventDefault();

                window.wpLink.open(uniqid);
            });

            $edit.on('click', function (e) {
                e.preventDefault();

                $add.trigger('click');

                $('#wp-link-url').val($this.find('.hope--url').val());
                $('#wp-link-text').val($this.find('.hope--text').val());
                $('#wp-link-target').prop('checked', $this.find('.hope--target').val() === '_blank');
            });

            $remove.on('click', function (e) {
                e.preventDefault();

                $this.find('.hope--url').val('').trigger('change');
                $this.find('.hope--text').val('');
                $this.find('.hope--target').val('');

                $add.removeClass('hidden');
                $edit.addClass('hidden');
                $remove.addClass('hidden');
                $result.parent().addClass('hidden');
            });

            $link.attr('id', uniqid).on('change', function () {
                var atts = window.wpLink.getAttrs(),
                    href = atts.href,
                    text = $('#wp-link-text').val(),
                    target = atts.target ? atts.target : '';

                $this.find('.hope--url').val(href).trigger('change');
                $this.find('.hope--text').val(text);
                $this.find('.hope--target').val(target);

                $result.html('{url:"' + href + '", text:"' + text + '", target:"' + target + '"}');

                $add.addClass('hidden');
                $edit.removeClass('hidden');
                $remove.removeClass('hidden');
                $result.parent().removeClass('hidden');
            });
        });
    };

    //
    // Field: media
    //
    $.fn.hope_field_media = function () {
        return this.each(function () {
            var $this = $(this),
                $upload_button = $this.find('.hope--button'),
                $remove_button = $this.find('.hope--remove'),
                $library = ($upload_button.data('library') && $upload_button.data('library').split(',')) || '',
                $auto_attributes = $this.hasClass('hope-assign-field-background') ? $this.closest('.hope-field-background').find('.hope--auto-attributes') : false,
                wp_media_frame;

            $upload_button.on('click', function (e) {
                e.preventDefault();

                if (typeof window.wp === 'undefined' || !window.wp.media || !window.wp.media.gallery) {
                    return;
                }

                if (wp_media_frame) {
                    wp_media_frame.open();
                    return;
                }

                wp_media_frame = window.wp.media({
                    library: {
                        type: $library,
                    },
                });

                wp_media_frame.on('select', function () {
                    var thumbnail;
                    var attributes = wp_media_frame.state().get('selection').first().attributes;
                    var preview_size = $upload_button.data('preview-size') || 'thumbnail';

                    if ($library.length && $library.indexOf(attributes.subtype) === -1 && $library.indexOf(attributes.type) === -1) {
                        return;
                    }

                    $this.find('.hope--id').val(attributes.id);
                    $this.find('.hope--width').val(attributes.width);
                    $this.find('.hope--height').val(attributes.height);
                    $this.find('.hope--alt').val(attributes.alt);
                    $this.find('.hope--title').val(attributes.title);
                    $this.find('.hope--description').val(attributes.description);

                    if (typeof attributes.sizes !== 'undefined' && typeof attributes.sizes.thumbnail !== 'undefined' && preview_size === 'thumbnail') {
                        thumbnail = attributes.sizes.thumbnail.url;
                    } else if (typeof attributes.sizes !== 'undefined' && typeof attributes.sizes.full !== 'undefined') {
                        thumbnail = attributes.sizes.full.url;
                    } else {
                        thumbnail = attributes.icon;
                    }

                    if ($auto_attributes) {
                        $auto_attributes.removeClass('hope---attributes-hidden');
                    }

                    $remove_button.removeClass('hidden');

                    $this.find('.hope--preview').removeClass('hidden');
                    $this.find('.hope--src').attr('src', thumbnail);
                    $this.find('.hope--thumbnail').val(thumbnail);
                    $this.find('.hope--url').val(attributes.url).trigger('change');
                });

                wp_media_frame.open();
            });

            $remove_button.on('click', function (e) {
                e.preventDefault();

                if ($auto_attributes) {
                    $auto_attributes.addClass('hope---attributes-hidden');
                }

                $remove_button.addClass('hidden');
                $this.find('input').val('');
                $this.find('.hope--preview').addClass('hidden');
                $this.find('.hope--url').trigger('change');
            });
        });
    };

    //
    // Field: repeater
    //
    $.fn.hope_field_repeater = function () {
        return this.each(function () {
            var $this = $(this),
                $fieldset = $this.children('.hope-fieldset'),
                $repeater = $fieldset.length ? $fieldset : $this,
                $wrapper = $repeater.children('.hope-repeater-wrapper'),
                $hidden = $repeater.children('.hope-repeater-hidden'),
                $max = $repeater.children('.hope-repeater-max'),
                $min = $repeater.children('.hope-repeater-min'),
                field_id = $wrapper.data('field-id'),
                max = parseInt($wrapper.data('max')),
                min = parseInt($wrapper.data('min'));

            $wrapper.children('.hope-repeater-item').children('.hope-repeater-content').hope_reload_script();

            $wrapper.sortable({
                axis: 'y',
                handle: '.hope-repeater-sort',
                revert: 100,
                helper: 'original',
                cursor: 'move',
                placeholder: 'widget-placeholder',
                update: function (event, ui) {
                    CSF.helper.name_nested_replace($wrapper.children('.hope-repeater-item'), field_id);
                    $wrapper.hope_customizer_refresh();
                    ui.item.hope_reload_script_retry();
                },
            });

            $repeater.children('.hope-repeater-add').on('click', function (e) {
                e.preventDefault();

                var count = $wrapper.children('.hope-repeater-item').length;

                $min.hide();

                if (max && count + 1 > max) {
                    $max.show();
                    return;
                }

                var $cloned_item = $hidden.hope_clone(true);

                $cloned_item.removeClass('hope-repeater-hidden');

                $cloned_item.find(':input[name!="_pseudo"]').each(function () {
                    this.name = this.name.replace('___', '').replace(field_id + '[0]', field_id + '[' + count + ']');
                });

                $wrapper.append($cloned_item);
                $cloned_item.children('.hope-repeater-content').hope_reload_script();
                $wrapper.hope_customizer_refresh();
                $wrapper.hope_customizer_listen({
                    closest: true,
                });
            });

            var event_clone = function (e) {
                e.preventDefault();

                var count = $wrapper.children('.hope-repeater-item').length;

                $min.hide();

                if (max && count + 1 > max) {
                    $max.show();
                    return;
                }

                var $this = $(this),
                    $parent = $this.parent().parent().parent(),
                    $cloned_content = $parent.children('.hope-repeater-content').hope_clone(),
                    $cloned_helper = $parent.children('.hope-repeater-helper').hope_clone(true),
                    $cloned_item = $('<div class="hope-repeater-item" />');

                $cloned_item.append($cloned_content);
                $cloned_item.append($cloned_helper);

                $wrapper.children().eq($parent.index()).after($cloned_item);

                $cloned_item.children('.hope-repeater-content').hope_reload_script();

                CSF.helper.name_nested_replace($wrapper.children('.hope-repeater-item'), field_id);

                $wrapper.hope_customizer_refresh();
                $wrapper.hope_customizer_listen({
                    closest: true,
                });
            };

            $wrapper.children('.hope-repeater-item').children('.hope-repeater-helper').on('click', '.hope-repeater-clone', event_clone);
            $repeater.children('.hope-repeater-hidden').children('.hope-repeater-helper').on('click', '.hope-repeater-clone', event_clone);

            var event_remove = function (e) {
                e.preventDefault();

                var count = $wrapper.children('.hope-repeater-item').length;

                $max.hide();
                $min.hide();

                if (min && count - 1 < min) {
                    $min.show();
                    return;
                }

                $(this).closest('.hope-repeater-item').remove();

                CSF.helper.name_nested_replace($wrapper.children('.hope-repeater-item'), field_id);

                $wrapper.hope_customizer_refresh();
            };

            $wrapper.children('.hope-repeater-item').children('.hope-repeater-helper').on('click', '.hope-repeater-remove', event_remove);
            $repeater.children('.hope-repeater-hidden').children('.hope-repeater-helper').on('click', '.hope-repeater-remove', event_remove);
        });
    };

    //
    // Field: slider
    //
    $.fn.hope_field_slider = function () {
        return this.each(function () {
            var $this = $(this),
                $input = $this.find('input'),
                $slider = $this.find('.hope-slider-ui'),
                data = $input.data(),
                value = $input.val() || 0;

            if ($slider.hasClass('ui-slider')) {
                $slider.empty();
            }

            $slider.slider({
                range: 'min',
                value: value,
                min: data.min || 0,
                max: data.max || 100,
                step: data.step || 1,
                slide: function (e, o) {
                    $input.val(o.value).trigger('change');
                },
            });

            $input.on('keyup', function () {
                $slider.slider('value', $input.val());
            });
        });
    };

    //
    // Field: sortable
    //
    $.fn.hope_field_sortable = function () {
        return this.each(function () {
            var $sortable = $(this).find('.hope-sortable');

            $sortable.sortable({
                axis: 'y',
                helper: 'original',
                handle: '.hope-sortable-helper',
                cursor: 'move',
                revert: 100,
                placeholder: 'widget-placeholder',
                update: function (event, ui) {
                    $sortable.hope_customizer_refresh();
                },
            });

            $sortable.find('.hope-sortable-content').hope_reload_script();
        });
    };

    //
    // Field: sorter
    //
    $.fn.hope_field_sorter = function () {
        return this.each(function () {
            var $this = $(this),
                $enabled = $this.find('.hope-enabled'),
                $has_disabled = $this.find('.hope-disabled'),
                $disabled = $has_disabled.length ? $has_disabled : false;

            $enabled.sortable({
                connectWith: $disabled,
                placeholder: 'ui-sortable-placeholder',
                update: function (event, ui) {
                    var $el = ui.item.find('input');

                    if (ui.item.parent().hasClass('hope-enabled')) {
                        $el.attr('name', $el.attr('name').replace('disabled', 'enabled'));
                    } else {
                        $el.attr('name', $el.attr('name').replace('enabled', 'disabled'));
                    }

                    $this.hope_customizer_refresh();
                },
            });

            if ($disabled) {
                $disabled.sortable({
                    connectWith: $enabled,
                    placeholder: 'ui-sortable-placeholder',
                    update: function (event, ui) {
                        $this.hope_customizer_refresh();
                    },
                });
            }
        });
    };

    //
    // Field: spinner
    //
    $.fn.hope_field_spinner = function () {
        return this.each(function () {
            var $this = $(this),
                $input = $this.find('input'),
                $inited = $this.find('.ui-spinner-button'),
                data = $input.data();

            if ($inited.length) {
                $inited.remove();
            }

            $input.spinner({
                min: data.min || 0,
                max: data.max || 100,
                step: data.step || 1,
                create: function (event, ui) {
                    $this.find('.hope--unit').remove();
                    if (data.unit) {
                        $input.after('<span class="ui-button hope--unit">' + data.unit + '</span>');
                    }
                },
                spin: function (event, ui) {
                    $input.val(ui.value).trigger('change');
                },
            });
        });
    };

    //
    // Field: switcher
    //
    $.fn.hope_field_switcher = function () {
        return this.each(function () {
            var $switcher = $(this).find('.hope--switcher');

            $switcher.on('click', function () {
                var value = 0;
                var $input = $switcher.find('input');

                if ($switcher.hasClass('hope--active')) {
                    $switcher.removeClass('hope--active');
                } else {
                    value = 1;
                    $switcher.addClass('hope--active');
                }

                $input.val(value).trigger('change');
            });
        });
    };

    //
    // Field: tabbed
    //
    $.fn.hope_field_tabbed = function () {
        return this.each(function () {
            var $this = $(this),
                $links = $this.find('.hope-tabbed-nav a'),
                $contents = $this.find('.hope-tabbed-content');

            $contents.eq(0).hope_reload_script();

            $links.on('click', function (e) {
                e.preventDefault();

                var $link = $(this),
                    index = $link.index(),
                    $content = $contents.eq(index);

                $link.addClass('hope-tabbed-active').siblings().removeClass('hope-tabbed-active');
                $content.hope_reload_script();
                $content.removeClass('hidden').siblings().addClass('hidden');
            });
        });
    };

    //
    // Field: typography
    //
    $.fn.hope_field_typography = function () {
        return this.each(function () {
            var base = this;
            var $this = $(this);
            var loaded_fonts = [];
            var webfonts = hope_typography_json.webfonts;
            var googlestyles = hope_typography_json.googlestyles;
            var defaultstyles = hope_typography_json.defaultstyles;

            //
            //
            // Sanitize google font subset
            base.sanitize_subset = function (subset) {
                subset = subset.replace('-ext', ' Extended');
                subset = subset.charAt(0).toUpperCase() + subset.slice(1);
                return subset;
            };

            //
            //
            // Sanitize google font styles (weight and style)
            base.sanitize_style = function (style) {
                return googlestyles[style] ? googlestyles[style] : style;
            };

            //
            //
            // Load google font
            base.load_google_font = function (font_family, weight, style) {
                if (font_family && typeof WebFont === 'object') {
                    weight = weight ? weight.replace('normal', '') : '';
                    style = style ? style.replace('normal', '') : '';

                    if (weight || style) {
                        font_family = font_family + ':' + weight + style;
                    }

                    if (loaded_fonts.indexOf(font_family) === -1) {
                        WebFont.load({
                            google: {
                                families: [font_family],
                            },
                        });
                    }

                    loaded_fonts.push(font_family);
                }
            };

            //
            //
            // Append select options
            base.append_select_options = function ($select, options, condition, type, is_multi) {
                $select.find('option').not(':first').remove();

                var opts = '';

                $.each(options, function (key, value) {
                    var selected;
                    var name = value;

                    // is_multi
                    if (is_multi) {
                        selected = condition && condition.indexOf(value) !== -1 ? ' selected' : '';
                    } else {
                        selected = condition && condition === value ? ' selected' : '';
                    }

                    if (type === 'subset') {
                        name = base.sanitize_subset(value);
                    } else if (type === 'style') {
                        name = base.sanitize_style(value);
                    }

                    opts += '<option value="' + value + '"' + selected + '>' + name + '</option>';
                });

                $select.append(opts).trigger('hope.change').trigger('chosen:updated');
            };

            base.init = function () {
                //
                //
                // Constants
                var selected_styles = [];
                var $typography = $this.find('.hope--typography');
                var $type = $this.find('.hope--type');
                var $styles = $this.find('.hope--block-font-style');
                var unit = $typography.data('unit');
                var line_height_unit = $typography.data('line-height-unit');
                var exclude_fonts = $typography.data('exclude') ? $typography.data('exclude').split(',') : [];

                //
                //
                // Chosen init
                if ($this.find('.hope--chosen').length) {
                    var $chosen_selects = $this.find('select');

                    $chosen_selects.each(function () {
                        var $chosen_select = $(this),
                            $chosen_inited = $chosen_select.parent().find('.chosen-container');

                        if ($chosen_inited.length) {
                            $chosen_inited.remove();
                        }

                        $chosen_select.chosen({
                            allow_single_deselect: true,
                            disable_search_threshold: 15,
                            width: '100%',
                        });
                    });
                }

                //
                //
                // Font family select
                var $font_family_select = $this.find('.hope--font-family');
                var first_font_family = $font_family_select.val();

                // Clear default font family select options
                $font_family_select.find('option').not(':first-child').remove();

                var opts = '';

                $.each(webfonts, function (type, group) {
                    // Check for exclude fonts
                    if (exclude_fonts && exclude_fonts.indexOf(type) !== -1) {
                        return;
                    }

                    opts += '<optgroup label="' + group.label + '">';

                    $.each(group.fonts, function (key, value) {
                        // use key if value is object
                        value = typeof value === 'object' ? key : value;
                        var selected = value === first_font_family ? ' selected' : '';
                        opts += '<option value="' + value + '" data-type="' + type + '"' + selected + '>' + value + '</option>';
                    });

                    opts += '</optgroup>';
                });

                // Append google font select options
                $font_family_select.append(opts).trigger('chosen:updated');

                //
                //
                // Font style select
                var $font_style_block = $this.find('.hope--block-font-style');

                if ($font_style_block.length) {
                    var $font_style_select = $this.find('.hope--font-style-select');
                    var first_style_value = $font_style_select.val() ? $font_style_select.val().replace(/normal/g, '') : '';

                    //
                    // Font Style on on change listener
                    $font_style_select.on('change hope.change', function (event) {
                        var style_value = $font_style_select.val();

                        // set a default value
                        if (!style_value && selected_styles && selected_styles.indexOf('normal') === -1) {
                            style_value = selected_styles[0];
                        }

                        // set font weight, for eg. replacing 800italic to 800
                        var font_normal = style_value && style_value !== 'italic' && style_value === 'normal' ? 'normal' : '';
                        var font_weight = style_value && style_value !== 'italic' && style_value !== 'normal' ? style_value.replace('italic', '') : font_normal;
                        var font_style = style_value && style_value.substr(-6) === 'italic' ? 'italic' : '';

                        $this.find('.hope--font-weight').val(font_weight);
                        $this.find('.hope--font-style').val(font_style);
                    });

                    //
                    //
                    // Extra font style select
                    var $extra_font_style_block = $this.find('.hope--block-extra-styles');

                    if ($extra_font_style_block.length) {
                        var $extra_font_style_select = $this.find('.hope--extra-styles');
                        var first_extra_style_value = $extra_font_style_select.val();
                    }
                }

                //
                //
                // Subsets select
                var $subset_block = $this.find('.hope--block-subset');
                if ($subset_block.length) {
                    var $subset_select = $this.find('.hope--subset');
                    var first_subset_select_value = $subset_select.val();
                    var subset_multi_select = $subset_select.data('multiple') || false;
                }

                //
                //
                // Backup font family
                var $backup_font_family_block = $this.find('.hope--block-backup-font-family');

                //
                //
                // Font Family on Change Listener
                $font_family_select
                    .on('change hope.change', function (event) {
                        // Hide subsets on change
                        if ($subset_block.length) {
                            $subset_block.addClass('hidden');
                        }

                        // Hide extra font style on change
                        if ($extra_font_style_block.length) {
                            $extra_font_style_block.addClass('hidden');
                        }

                        // Hide backup font family on change
                        if ($backup_font_family_block.length) {
                            $backup_font_family_block.addClass('hidden');
                        }

                        var $selected = $font_family_select.find(':selected');
                        var value = $selected.val();
                        var type = $selected.data('type');

                        if (type && value) {
                            // Show backup fonts if font type google or custom
                            if ((type === 'google' || type === 'custom') && $backup_font_family_block.length) {
                                $backup_font_family_block.removeClass('hidden');
                            }

                            // Appending font style select options
                            if ($font_style_block.length) {
                                // set styles for multi and normal style selectors
                                var styles = defaultstyles;

                                // Custom or gogle font styles
                                if (type === 'google' && webfonts[type].fonts[value][0]) {
                                    styles = webfonts[type].fonts[value][0];
                                } else if (type === 'custom' && webfonts[type].fonts[value]) {
                                    styles = webfonts[type].fonts[value];
                                }

                                selected_styles = styles;

                                // Set selected style value for avoid load errors
                                var set_auto_style = styles.indexOf('normal') !== -1 ? 'normal' : styles[0];
                                var set_style_value = first_style_value && styles.indexOf(first_style_value) !== -1 ? first_style_value : set_auto_style;

                                // Append style select options
                                base.append_select_options($font_style_select, styles, set_style_value, 'style');

                                // Clear first value
                                first_style_value = false;

                                // Show style select after appended
                                $font_style_block.removeClass('hidden');

                                // Appending extra font style select options
                                if (type === 'google' && $extra_font_style_block.length && styles.length > 1) {
                                    // Append extra-style select options
                                    base.append_select_options($extra_font_style_select, styles, first_extra_style_value, 'style', true);

                                    // Clear first value
                                    first_extra_style_value = false;

                                    // Show style select after appended
                                    $extra_font_style_block.removeClass('hidden');
                                }
                            }

                            // Appending google fonts subsets select options
                            if (type === 'google' && $subset_block.length && webfonts[type].fonts[value][1]) {
                                var subsets = webfonts[type].fonts[value][1];
                                var set_auto_subset = subsets.length < 2 && subsets[0] !== 'latin' ? subsets[0] : '';
                                var set_subset_value = first_subset_select_value && subsets.indexOf(first_subset_select_value) !== -1 ? first_subset_select_value : set_auto_subset;

                                // check for multiple subset select
                                set_subset_value = subset_multi_select && first_subset_select_value ? first_subset_select_value : set_subset_value;

                                base.append_select_options($subset_select, subsets, set_subset_value, 'subset', subset_multi_select);

                                first_subset_select_value = false;

                                $subset_block.removeClass('hidden');
                            }
                        } else {
                            // Clear Styles
                            $styles.find(':input').val('');

                            // Clear subsets options if type and value empty
                            if ($subset_block.length) {
                                $subset_select.find('option').not(':first-child').remove();
                                $subset_select.trigger('chosen:updated');
                            }

                            // Clear font styles options if type and value empty
                            if ($font_style_block.length) {
                                $font_style_select.find('option').not(':first-child').remove();
                                $font_style_select.trigger('chosen:updated');
                            }
                        }

                        // Update font type input value
                        $type.val(type);
                    })
                    .trigger('hope.change');

                //
                //
                // Preview
                var $preview_block = $this.find('.hope--block-preview');

                if ($preview_block.length) {
                    var $preview = $this.find('.hope--preview');

                    // Set preview styles on change
                    $this.on(
                        'change',
                        CSF.helper.debounce(function (event) {
                            $preview_block.removeClass('hidden');

                            var font_family = $font_family_select.val(),
                                font_weight = $this.find('.hope--font-weight').val(),
                                font_style = $this.find('.hope--font-style').val(),
                                font_size = $this.find('.hope--font-size').val(),
                                font_variant = $this.find('.hope--font-variant').val(),
                                line_height = $this.find('.hope--line-height').val(),
                                text_align = $this.find('.hope--text-align').val(),
                                text_transform = $this.find('.hope--text-transform').val(),
                                text_decoration = $this.find('.hope--text-decoration').val(),
                                text_color = $this.find('.hope--color').val(),
                                word_spacing = $this.find('.hope--word-spacing').val(),
                                letter_spacing = $this.find('.hope--letter-spacing').val(),
                                custom_style = $this.find('.hope--custom-style').val(),
                                type = $this.find('.hope--type').val();

                            if (type === 'google') {
                                base.load_google_font(font_family, font_weight, font_style);
                            }

                            var properties = {};

                            if (font_family) {
                                properties.fontFamily = font_family;
                            }
                            if (font_weight) {
                                properties.fontWeight = font_weight;
                            }
                            if (font_style) {
                                properties.fontStyle = font_style;
                            }
                            if (font_variant) {
                                properties.fontVariant = font_variant;
                            }
                            if (font_size) {
                                properties.fontSize = font_size + unit;
                            }
                            if (line_height) {
                                properties.lineHeight = line_height + line_height_unit;
                            }
                            if (letter_spacing) {
                                properties.letterSpacing = letter_spacing + unit;
                            }
                            if (word_spacing) {
                                properties.wordSpacing = word_spacing + unit;
                            }
                            if (text_align) {
                                properties.textAlign = text_align;
                            }
                            if (text_transform) {
                                properties.textTransform = text_transform;
                            }
                            if (text_decoration) {
                                properties.textDecoration = text_decoration;
                            }
                            if (text_color) {
                                properties.color = text_color;
                            }

                            $preview.removeAttr('style');

                            // Customs style attribute
                            if (custom_style) {
                                $preview.attr('style', custom_style);
                            }

                            $preview.css(properties);
                        }, 100)
                    );

                    // Preview black and white backgrounds trigger
                    $preview_block.on('click', function () {
                        $preview.toggleClass('hope---black-background');

                        var $toggle = $preview_block.find('.hope--toggle');

                        if ($toggle.hasClass('fa-toggle-off')) {
                            $toggle.removeClass('fa-toggle-off').addClass('fa-toggle-on');
                        } else {
                            $toggle.removeClass('fa-toggle-on').addClass('fa-toggle-off');
                        }
                    });

                    if (!$preview_block.hasClass('hidden')) {
                        $this.trigger('change');
                    }
                }
            };

            base.init();
        });
    };

    //
    // Field: upload
    //
    $.fn.hope_field_upload = function () {
        return this.each(function () {
            var $this = $(this),
                $input = $this.find('input'),
                $upload_button = $this.find('.hope-upload-img'),
                $remove_button = $this.find('.hope--remove'),

                // 获取支持的文件类型库，如果没有则设为空数组
                $library = ($upload_button.data('library') && $upload_button.data('library').split(',')) || ''

            $input.on('change', function (e) {
                if ($input.val()) {
                    $remove_button.removeClass('hidden');
                } else {
                    $remove_button.addClass('hidden');
                }
            });

            $upload_button.on('click', function (e) {
                e.preventDefault();
                var page = 1;
                var sid = 0;
                var keyword = '';
                var isLoading = false;
                var searchTimer = null;
                var $modal = $('#hope_mediaModal');

                function escapeHtml(str) {
                    return String(str == null ? '' : str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                function toggleEmpty(isEmpty) {
                    if (isEmpty) {
                        $('#hope-media-empty').removeClass('d-none');
                    } else {
                        $('#hope-media-empty').addClass('d-none');
                    }
                }

                function uploadUrl() {
                    var q = '?act=upload&uploade';
                    if (sid) {
                        q += '&sid=' + encodeURIComponent(sid);
                    }
                    return typeof hopeWithToken === 'function' ? hopeWithToken(q) : q;
                }

                function renderCard(image) {
                    var type = image.media_type || 'file';
                    var typeLabel = ({ image: '图片', video: '视频', audio: '音频', zip: '压缩包' })[type] || '文件';
                    var name = escapeHtml(image.media_name);
                    var size = escapeHtml(image.attsize);
                    var url = escapeHtml(image.media_url);
                    var fa = escapeHtml(image.media_fa || ({
                        zip: 'fa-file-zipper',
                        video: 'fa-file-video',
                        audio: 'fa-file-audio',
                        file: 'fa-file'
                    })[type] || 'fa-file');
                    var thumb = (type === 'image' && image.media_icon)
                        ? '<img src="' + escapeHtml(image.media_icon) + '" alt="' + name + '" loading="lazy">'
                        : '<i class="fa ' + fa + ' hope-media-fa hope-media-fa--' + escapeHtml(type) + '" aria-hidden="true"></i>';
                    return '<div class="col-6 col-md-4 col-xl-3">' +
                        '<div class="card hope-media-card h-100 shadow-sm">' +
                        '<div class="hope-media-thumb">' + thumb +
                        '<span class="hope-media-type">' + typeLabel + '</span></div>' +
                        '<div class="hope-media-body">' +
                        '<div class="hope-media-name" title="' + name + '">' + name + '</div>' +
                        '<div class="hope-media-meta text-muted">' + size + '</div>' +
                        '<div class="hope-media-actions">' +
                        '<a class="btn btn-outline-primary btn-xs hope-insert-img" href="javascript:;" data-url="' + url + '"><i class="fa fa-check me-1"></i>选用</a>' +
                        '<a class="btn btn-outline-secondary btn-xs" href="' + url + '" target="_blank" rel="noopener"><i class="fa fa-arrow-up-right-from-square"></i></a>' +
                        '</div></div></div></div>';
                }

                function loadImages(pageNum) {
                    if (isLoading) return;
                    isLoading = true;
                    $('#hope-load-more').prop('disabled', true).find('i').removeClass('d-none').addClass('fa-spin');

                    $.ajax({
                        url: '?act=upload&list',
                        method: 'GET',
                        data: {
                            page: pageNum,
                            keyword: keyword || '',
                            sid: sid || 0
                        },
                        beforeSend: function () {
                            if (pageNum === 1) {
                                $('#hope-image-list').empty();
                                toggleEmpty(false);
                            }
                        }
                    })
                    .done(function (response) {
                        isLoading = false;
                        $('#hope-load-more').prop('disabled', false).find('i').addClass('d-none').removeClass('fa-spin');

                        if (hopeApiSuccess(response.code)) {
                            var images = (response.data && response.data.images) ? response.data.images : [];
                            if (images.length > 0) {
                                var imagesHtml = '';
                                $.each(images, function (i, image) {
                                    imagesHtml += renderCard(image);
                                });
                                if (pageNum === 1) {
                                    $('#hope-image-list').html(imagesHtml);
                                } else {
                                    $('#hope-image-list').append(imagesHtml);
                                }
                                toggleEmpty(false);
                                if (response.data.hasMore) {
                                    $('#hope-load-more').show();
                                } else {
                                    $('#hope-load-more').hide();
                                }
                            } else if (pageNum === 1) {
                                toggleEmpty(true);
                                $('#hope-load-more').hide();
                            } else {
                                $('#hope-load-more').hide();
                            }
                        } else if (pageNum === 1) {
                            $('#hope-image-list').html('<div class="col-12 text-center py-5"><p class="text-danger mb-0">加载失败: ' + escapeHtml(response.message || '未知错误') + '</p></div>');
                            $('#hope-load-more').hide();
                        }
                    })
                    .fail(function (jqXHR, textStatus) {
                        isLoading = false;
                        $('#hope-load-more').prop('disabled', false).find('i').addClass('d-none').removeClass('fa-spin');
                        console.error('失败原因:', textStatus);
                        if (pageNum === 1) {
                            $('#hope-image-list').html('<div class="col-12 text-center py-5"><p class="text-danger mb-0">加载失败，请检查网络连接</p></div>');
                            $('#hope-load-more').hide();
                        }
                    });
                }

                function reloadList() {
                    page = 1;
                    loadImages(page);
                }

                $modal.modal('show');
                sid = $('#hope-media-sort-select').length ? ($('#hope-media-sort-select').val() || 0) : 0;
                keyword = $.trim($('#hope-img-search').val() || '');
                $('#hope-image-list').empty();
                toggleEmpty(false);
                $('#hope-load-more').show();

                $modal.off('change.hopeMedia', '.hope-img-input').on('change.hopeMedia', '.hope-img-input', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var fileInput = this;
                    var file = fileInput.files[0];
                    if (!file) return;

                    var formData = new FormData();
                    formData.append('file', file);
                    formData.append('list', '1');

                    $('#upload-progress').removeClass('d-none hidden');
                    $('#upload-progress .progress-bar').css('width', '0%');

                    $.ajax({
                        url: uploadUrl(),
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        xhr: function () {
                            var xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener('progress', function (evt) {
                                if (evt.lengthComputable) {
                                    var percentComplete = evt.loaded / evt.total * 100;
                                    $('#upload-progress .progress-bar').css('width', percentComplete + '%');
                                }
                            }, false);
                            return xhr;
                        },
                        success: function (response) {
                            $('#upload-progress').addClass('d-none hidden');
                            fileInput.value = '';
                            if (hopeApiSuccess(response.code)) {
                                reloadList();
                            } else {
                                alert('上传失败：' + (response.message || '未知错误'));
                            }
                        },
                        error: function () {
                            $('#upload-progress').addClass('d-none hidden');
                            fileInput.value = '';
                            alert('文件上传失败，请重试');
                        }
                    });
                });

                $modal.off('input.hopeMedia', '#hope-img-search').on('input.hopeMedia', '#hope-img-search', function () {
                    var val = $.trim($(this).val() || '');
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function () {
                        keyword = val;
                        reloadList();
                    }, 300);
                });

                $modal.off('click.hopeMedia', '.hope-insert-img').on('click.hopeMedia', '.hope-insert-img', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var imageUrl = $(this).data('url');
                    $input.val(imageUrl).trigger('change');
                    $this.find('.hope--preview').removeClass('hidden');
                    $this.find('.hope--src').attr('src', imageUrl);
                    $modal.modal('hide');
                });

                $modal.off('click.hopeMedia', '#hope-load-more').on('click.hopeMedia', '#hope-load-more', function (e) {
                    e.preventDefault();
                    page++;
                    loadImages(page);
                });

                $modal.off('change.hopeMedia', '#hope-media-sort-select').on('change.hopeMedia', '#hope-media-sort-select', function () {
                    sid = $(this).val() || 0;
                    reloadList();
                });

                loadImages(page);
            });
            $remove_button.on('click', function (e) {
                e.preventDefault();
                $this.find('.hope--preview').addClass('hidden');
                $input.val('').trigger('change');
            });
        });
    };

    //
    // Field: wp_editor
    //
    $.fn.hope_field_wp_editor = function () {
        return this.each(function () {
            if (typeof window.wp.editor === 'undefined' || typeof window.tinyMCEPreInit === 'undefined' || typeof window.tinyMCEPreInit.mceInit.hope_wp_editor === 'undefined') {
                return;
            }

            var $this = $(this),
                $editor = $this.find('.hope-wp-editor'),
                $textarea = $this.find('textarea');

            // If there is wp-editor remove it for avoid dupliated wp-editor conflicts.
            var $has_wp_editor = $this.find('.wp-editor-wrap').length || $this.find('.mce-container').length;

            if ($has_wp_editor) {
                $editor.empty();
                $editor.append($textarea);
                $textarea.css('display', '');
            }

            // Generate a unique id
            var uid = CSF.helper.uid('hope-editor-');

            $textarea.attr('id', uid);

            // Get default editor settings
            var default_editor_settings = {
                tinymce: window.tinyMCEPreInit.mceInit.hope_wp_editor,
                quicktags: window.tinyMCEPreInit.qtInit.hope_wp_editor,
            };

            // Get default editor settings
            var field_editor_settings = $editor.data('editor-settings');

            // Callback for old wp editor
            var wpEditor = wp.oldEditor ? wp.oldEditor : wp.editor;

            if (wpEditor && wpEditor.hasOwnProperty('autop')) {
                wp.editor.autop = wpEditor.autop;
                wp.editor.removep = wpEditor.removep;
                wp.editor.initialize = wpEditor.initialize;
            }

            // Add on change event handle
            var editor_on_change = function (editor) {
                editor.on('change keyup', function () {
                    var value = field_editor_settings.wpautop ? editor.getContent() : wp.editor.removep(editor.getContent());
                    $textarea.val(value).trigger('change');
                });
            };

            // Extend editor selector and on change event handler
            default_editor_settings.tinymce = $.extend({}, default_editor_settings.tinymce, {
                selector: '#' + uid,
                setup: editor_on_change,
            });

            // Override editor tinymce settings
            if (field_editor_settings.tinymce === false) {
                default_editor_settings.tinymce = false;
                $editor.addClass('hope-no-tinymce');
            }

            // Override editor quicktags settings
            if (field_editor_settings.quicktags === false) {
                default_editor_settings.quicktags = false;
                $editor.addClass('hope-no-quicktags');
            }

            // Wait until :visible
            var interval = setInterval(function () {
                if ($this.is(':visible')) {
                    window.wp.editor.initialize(uid, default_editor_settings);
                    clearInterval(interval);
                }
            });

            // Add Media buttons
            if (field_editor_settings.media_buttons && window.hope_media_buttons) {
                var $editor_buttons = $editor.find('.wp-media-buttons');

                if ($editor_buttons.length) {
                    $editor_buttons.find('.hope-shortcode-button').data('editor-id', uid);
                } else {
                    var $media_buttons = $(window.hope_media_buttons);

                    $media_buttons.find('.hope-shortcode-button').data('editor-id', uid);

                    $editor.prepend($media_buttons);
                }
            }
        });
    };

    //
    // Confirm
    //
    $.fn.hope_confirm = function () {
        return this.each(function () {
            $(this).on('click', function (e) {
                var confirm_text = $(this).data('confirm') || window.hope_vars.i18n.confirm;
                var confirm_answer = confirm(confirm_text);

                if (confirm_answer) {
                    CSF.vars.is_confirm = true;
                    CSF.vars.form_modified = false;
                } else {
                    e.preventDefault();
                    return false;
                }
            });
        });
    };

    $.fn.serializeObject = function () {
        var obj = {};

        $.each(this.serializeArray(), function (i, o) {
            var n = o.name,
                v = o.value;

            obj[n] = obj[n] === undefined ? v : $.isArray(obj[n]) ? obj[n].concat(v) : [obj[n], v];
        });

        return obj;
    };

    //
    // Options Save
    //
    $.fn.hope_save = function () {
        return this.each(function () {
            var $this = $(this),
                $buttons = $('.hope-save'),
                $panel = $('.hope-options'),
                flooding = false,
                timeout;

            $this.on('click', function (e) {
                if (!flooding) {
                    var $text = $this.data('save'),
                        $value = $this.val();

                    $buttons.attr('value', $text);

                    if ($this.hasClass('hope-save-ajax')) {
                        e.preventDefault();

                        $panel.addClass('hope-saving');
                        $buttons.prop('disabled', true);

                        $.post('?act=theme_set&hope_ajax_save&tpl=' + $panel.data('unique'), $('#hope-form').serializeObject())
                        .done(function (response) {
                            $('.hope-error').remove();
                            if (response.errors==1) {
                                var error_icon = '<i class="hope-label-error hope-error">!</i>';

                                $.each(response.errors, function (key, error_message) {
                                    var $field = $('[data-depend-id="' + key + '"]'),
                                        $link = $('#hope-tab-link-' + ($field.closest('.hope-section').index() + 1)),
                                        $tab = $link.closest('.hope-tab-depth-0');

                                    $field.closest('.hope-fieldset').append('<p class="hope-error hope-error-text">' + error_message + '</p>');

                                    if (!$link.find('.hope-error').length) {
                                        $link.append(error_icon);
                                    }

                                    if (!$tab.find('.hope-arrow .hope-error').length) {
                                        $tab.find('.hope-arrow').append(error_icon);
                                    }
                                });
                            }

                            $panel.removeClass('hope-saving');
                            $buttons.prop('disabled', false).attr('value', $value);
                            flooding = false;

                            CSF.vars.form_modified = false;
                            CSF.vars.$form_warning.hide();

                            clearTimeout(timeout);

                            var $result_success = $('.csf-form-success');
                            $result_success
                                .empty()
                                .append(response.notice)
                                .fadeIn('fast', function () {
                                    timeout = setTimeout(function () {
                                        $result_success.fadeOut('fast');
                                    }, 3000);
                                });
                        })
                        .fail(function (response) {
                            console.error(response);
                            var e_msg = '保存时发生错误，请检查是否被防火墙或CDN屏蔽，同时请排除插件和子主题，或通过错误日志进行排查。 错误信息已输出到浏览器控制台，请对照分析。' + (response.error || '');
                            alert(e_msg);
                        });
                    } else {
                        CSF.vars.form_modified = false;
                    }
                }

                flooding = true;
            });
        });
    };

    //
    // Option Framework
    //
    $.fn.hope_options = function () {
        return this.each(function () {
            var $this = $(this),
                $content = $this.find('.hope-content'),
                $form_success = $this.find('.hope-form-success'),
                $form_warning = $this.find('.hope-form-warning'),
                $save_button = $this.find('.hope-header .hope-save');

            CSF.vars.$form_warning = $form_warning;

            // Shows a message white leaving theme options without saving
            if ($form_warning.length) {
                window.onbeforeunload = function () {
                    return CSF.vars.form_modified ? true : undefined;
                };

                $content.on('change keypress', ':input', function () {
                    if (!CSF.vars.form_modified) {
                        $form_success.hide();
                        $form_warning.fadeIn('fast');
                        CSF.vars.form_modified = true;
                    }
                });
            }

            if ($form_success.hasClass('hope-form-show')) {
                setTimeout(function () {
                    $form_success.fadeOut('fast');
                }, 1000);
            }

            $(document).keydown(function (event) {
                if ((event.ctrlKey || event.metaKey) && event.which === 83) {
                    $save_button.trigger('click');
                    event.preventDefault();
                    return false;
                }
            });
        });
    };

    //
    // Taxonomy Framework
    //
    $.fn.hope_taxonomy = function () {
        return this.each(function () {
            var $this = $(this),
                $form = $this.parents('form');

            if ($form.attr('id') === 'addtag') {
                var $submit = $form.find('#submit'),
                    $cloned = $this.find('.hope-field').hope_clone();

                $submit.on('click', function () {
                    if (!$form.find('.form-required').hasClass('form-invalid')) {
                        $this.data('inited', false);

                        $this.empty();

                        $this.html($cloned);

                        $cloned = $cloned.hope_clone();

                        $this.hope_reload_script();
                    }
                });
            }
        });
    };

    //
    // Shortcode Framework
    //
    $.fn.hope_shortcode = function () {
        var base = this;

        base.shortcode_parse = function (serialize, key) {
            var shortcode = '';

            $.each(serialize, function (shortcode_key, shortcode_values) {
                key = key ? key : shortcode_key;

                shortcode += '[' + key;

                $.each(shortcode_values, function (shortcode_tag, shortcode_value) {
                    if (shortcode_tag === 'content') {
                        shortcode += ']';
                        shortcode += shortcode_value;
                        shortcode += '[/' + key + '';
                    } else {
                        shortcode += base.shortcode_tags(shortcode_tag, shortcode_value);
                    }
                });

                shortcode += ']';
            });

            return shortcode;
        };

        base.shortcode_tags = function (shortcode_tag, shortcode_value) {
            var shortcode = '';

            if (shortcode_value !== '') {
                if (typeof shortcode_value === 'object' && !$.isArray(shortcode_value)) {
                    $.each(shortcode_value, function (sub_shortcode_tag, sub_shortcode_value) {
                        // sanitize spesific key/value
                        switch (sub_shortcode_tag) {
                            case 'background-image':
                                sub_shortcode_value = sub_shortcode_value.url ? sub_shortcode_value.url : '';
                                break;
                        }

                        if (sub_shortcode_value !== '') {
                            shortcode += ' ' + sub_shortcode_tag.replace('-', '_') + '="' + sub_shortcode_value.toString() + '"';
                        }
                    });
                } else {
                    shortcode += ' ' + shortcode_tag.replace('-', '_') + '="' + shortcode_value.toString() + '"';
                }
            }

            return shortcode;
        };

        base.insertAtChars = function (_this, currentValue) {
            var obj = typeof _this[0].name !== 'undefined' ? _this[0] : _this;

            if (obj.value.length && typeof obj.selectionStart !== 'undefined') {
                obj.focus();
                return obj.value.substring(0, obj.selectionStart) + currentValue + obj.value.substring(obj.selectionEnd, obj.value.length);
            } else {
                obj.focus();
                return currentValue;
            }
        };

        base.send_to_editor = function (html, editor_id) {
            var tinymce_editor;

            if (typeof tinymce !== 'undefined') {
                tinymce_editor = tinymce.get(editor_id);
            }

            if (tinymce_editor && !tinymce_editor.isHidden()) {
                tinymce_editor.execCommand('mceInsertContent', false, html);
            } else {
                var $editor = $('#' + editor_id);
                $editor.val(base.insertAtChars($editor, html)).trigger('change');
            }
        };

        return this.each(function () {
            var $modal = $(this),
                $load = $modal.find('.hope-modal-load'),
                $content = $modal.find('.hope-modal-content'),
                $insert = $modal.find('.hope-modal-insert'),
                $loading = $modal.find('.hope-modal-loading'),
                $select = $modal.find('select'),
                modal_id = $modal.data('modal-id'),
                nonce = $modal.data('nonce'),
                editor_id,
                target_id,
                gutenberg_id,
                sc_key,
                sc_name,
                sc_view,
                sc_group,
                $cloned,
                $button;

            $(document).on('click', '.hope-shortcode-button[data-modal-id="' + modal_id + '"]', function (e) {
                e.preventDefault();

                $button = $(this);
                editor_id = $button.data('editor-id') || false;
                target_id = $button.data('target-id') || false;
                gutenberg_id = $button.data('gutenberg-id') || false;

                $modal.removeClass('hidden');

                // single usage trigger first shortcode
                if ($modal.hasClass('hope-shortcode-single') && sc_name === undefined) {
                    $select.trigger('change');
                }
            });

            $select.on('change', function () {
                var $option = $(this);
                var $selected = $option.find(':selected');

                sc_key = $option.val();
                sc_name = $selected.data('shortcode');
                sc_view = $selected.data('view') || 'normal';
                sc_group = $selected.data('group') || sc_name;

                $load.empty();

                if (sc_key) {
                    $loading.show();

                    window.wp.ajax
                        .post('hope-get-shortcode-' + modal_id, {
                            shortcode_key: sc_key,
                            nonce: nonce,
                        })
                        .done(function (response) {
                            $loading.hide();

                            var $appended = $(response.content).appendTo($load);

                            $insert.parent().removeClass('hidden');

                            $cloned = $appended.find('.hope--repeat-shortcode').hope_clone();

                            $appended.hope_reload_script();
                            $appended.find('.hope-fields').hope_reload_script();
                        });
                } else {
                    $insert.parent().addClass('hidden');
                }
            });

            $insert.on('click', function (e) {
                e.preventDefault();

                if ($insert.prop('disabled') || $insert.attr('disabled')) {
                    return;
                }

                var shortcode = '';
                var serialize = $modal.find('.hope-field:not(.hope-depend-on)').find(':input:not(.ignore)').serializeObjectCSF();

                switch (sc_view) {
                    case 'contents':
                        var contentsObj = sc_name ? serialize[sc_name] : serialize;
                        $.each(contentsObj, function (sc_key, sc_value) {
                            var sc_tag = sc_name ? sc_name : sc_key;
                            shortcode += '[' + sc_tag + ']' + sc_value + '[/' + sc_tag + ']';
                        });
                        break;

                    case 'group':
                        shortcode += '[' + sc_name;
                        $.each(serialize[sc_name], function (sc_key, sc_value) {
                            shortcode += base.shortcode_tags(sc_key, sc_value);
                        });
                        shortcode += ']';
                        shortcode += base.shortcode_parse(serialize[sc_group], sc_group);
                        shortcode += '[/' + sc_name + ']';

                        break;

                    case 'repeater':
                        shortcode += base.shortcode_parse(serialize[sc_group], sc_group);
                        break;

                    default:
                        shortcode += base.shortcode_parse(serialize);
                        break;
                }

                shortcode = shortcode === '' ? '[' + sc_name + ']' : shortcode;

                if (gutenberg_id) {
                    var content = window.hope_gutenberg_props.attributes.hasOwnProperty('shortcode') ? window.hope_gutenberg_props.attributes.shortcode : '';
                    window.hope_gutenberg_props.setAttributes({
                        shortcode: content + shortcode,
                    });
                } else if (editor_id) {
                    base.send_to_editor(shortcode, editor_id);
                } else {
                    var $textarea = target_id ? $(target_id) : $button.parent().find('textarea');
                    $textarea.val(base.insertAtChars($textarea, shortcode)).trigger('change');
                }

                $modal.addClass('hidden');
            });

            $modal.on('click', '.hope--repeat-button', function (e) {
                e.preventDefault();

                var $repeatable = $modal.find('.hope--repeatable');
                var $new_clone = $cloned.hope_clone();
                var $remove_btn = $new_clone.find('.hope-repeat-remove');

                var $appended = $new_clone.appendTo($repeatable);

                $new_clone.find('.hope-fields').hope_reload_script();

                CSF.helper.name_nested_replace($modal.find('.hope--repeat-shortcode'), sc_group);

                $remove_btn.on('click', function () {
                    $new_clone.remove();

                    CSF.helper.name_nested_replace($modal.find('.hope--repeat-shortcode'), sc_group);
                });
            });

            $modal.on('click', '.hope-modal-close, .hope-modal-overlay', function () {
                $modal.addClass('hidden');
            });
        });
    };

    //
    /*
     * WP 颜色选择器扩展说明（中文）
     *
     * 功能：
     *   - 为带有 `.hope-color` 的输入字段初始化 WordPress 的 wpColorPicker，并在颜色选择器下加入“透明”开关和透明度滑块。
     *   - 支持 palette（预设色板），支持 rgba 透明度值，支持将值设置为字符串 'transparent'。
     *
     * 使用说明：
     *   1. 在 HTML 中，给目标 input 添加类 `hope-color`：
     *        <input type="text" class="hope-color" value="#ff0000" data-default-color="#ff0000" />
     *   2. 可选：通过 `data-default-color` 指定默认颜色（在点击恢复默认时使用）。
     *   3. 颜色面板的预设色板由 `window.hope_vars.color_palette` 提供；若为空则使用默认调色板。
     *
     * API / 事件：
     *   - 当用户从调色板或拾色器选择颜色时，会触发 input 的 `change` 事件，值为字符串：
     *       - 纯色："#rrggbb"
     *       - 带透明度："rgba(r,g,b,a)" （a < 1）
     *       - 透明：字符串 "transparent"（由透明开关触发）
     *   - 可以监听该 input 的 `change` 以获取最新颜色值并同步到后端或其他 UI。
     *
     * 行为（过程）：
     *   - 初始化：读取当前 input 值并解析为 `{ value, transparent, rgba }` 的对象（CSF.funcs.parse_color）。
     *   - 如果元素已经被初始化过（具有 `wp-color-picker` 类），会先销毁旧实例并重建，避免重复绑定。
     *   - 在 wpColorPicker 的 create 回调中，会注入一个透明控制条：包含滑块（调整 alpha）、显示区、文本及透明开关按钮。
     *   - 透明按钮：切换值为字符串 'transparent'（并为容器添加样式类 `hope---transparent-active`），再次点击会恢复到 Iris 当前颜色。
     *   - 滑块：值范围 0-100，映射到 alpha（0.0 - 1.0），滑动时直接设置 Iris 内部颜色并更新 picker 显示与 input 值。
     *   - 点击清除（.wp-picker-clear）：会把 alpha 复位为 1 并移除透明样式。
     *   - 点击默认（.wp-picker-default）：会读取 `data-default-color` 并应用其 alpha / 颜色，如果默认为 'transparent' 则进入透明状态。
     *
     * 依赖：
     *   - jQuery
     *   - WordPress 的 wpColorPicker（包含 Iris）
     *   - jQuery UI slider（用于透明度滑块）
     *
     * 注意事项：
     *   - Color.prototype.toString 在本文件中被扩展以支持在 alpha < 1 时输出 rgba 字符串。
     *   - 当你通过脚本修改 input 值并想触发界面刷新，请使用 $input.trigger('change')。
     *
     * 示例：
     *   $(document).ready(function () {
     *       $('.hope-color').hope_color();
     *   });
     */

    // WP Color Picker
    //
    if (typeof Color === 'function') {
        Color.prototype.toString = function () {
            if (this._alpha < 1) {
                return this.toCSS('rgba', this._alpha).replace(/\s+/g, '');
            }

            var hex = parseInt(this._color, 10).toString(16);

            if (this.error) {
                return '';
            }

            if (hex.length < 6) {
                for (var i = 6 - hex.length - 1; i >= 0; i--) {
                    hex = '0' + hex;
                }
            }

            return '#' + hex;
        };
    }

    CSF.funcs.parse_color = function (color) {
        var value = color.replace(/\s+/g, ''),
            trans = value.indexOf('rgba') !== -1 ? parseFloat(value.replace(/^.*,(.+)\)/, '$1') * 100) : 100,
            rgba = trans < 100 ? true : false;

        return {
            value: value,
            transparent: trans,
            rgba: rgba,
        };
    };

    $.fn.hope_color = function () {
        return this.each(function () {
            var $input = $(this),
                picker_color = CSF.funcs.parse_color($input.val()),
                palette_color = window.hope_vars.color_palette.length ? window.hope_vars.color_palette : true,
                $container;





                

            // If Pickr is available, initialize a Pickr color picker. Otherwise leave original behavior commented.
            if (typeof Pickr !== 'undefined') {
                // Destroy existing instance if present
                var existing = $input.data('pickr-instance');
                if (existing && typeof existing.destroy === 'function') {
                    try {
                        existing.destroy();
                    } catch (e) {
                        /* ignore destroy errors */
                    }
                }


                // Holder for Pickr and transparent UI
                $container = $('<div class="hope-color-picker"></div>').insertBefore($input);

                // Add a visible toggle button for Pickr (Pickr will attach to this element)
                var $toggle = $('<button type="button" class="hope--pickr-toggle" title="' + (window.hope_vars && window.hope_vars.i18n ? window.hope_vars.i18n.color : 'Color') + '"></button>').appendTo($container);

                // Build Pickr options
                var pickr_options = {
                    el: $toggle[0],
                    theme: 'classic',
                    default: picker_color.value || '#FFFFFF',
                    swatches: Array.isArray(palette_color) ? palette_color : undefined,
                    components: {
                        preview: true,
                        opacity: true,
                        hue: true,
                        interaction: {
                            hex: true,
                            rgba: true,
                            input: true,
                            clear: true,
                            save: true,
                        },
                    },
                    i18n: {
                        'ui:dialog': '颜色选择器',
                        'btn:toggle': '切换颜色选择器',
                        'btn:swatch': '选择预设颜色',
                        'btn:last-color': '使用上次选择的颜色',
                        'btn:save': '保存',
                        'btn:cancel': '取消',
                        'btn:clear': '清除',
                        'aria:btn:save': '保存并关闭',
                        'aria:btn:cancel': '取消并关闭',
                        'aria:btn:clear': '清除选择',
                        'aria:input': '颜色值输入框',
                        'aria:palette': '颜色调色板',
                        'aria:hue': '色相调节滑块',
                        'aria:opacity': '透明度调节滑块'
                    }
                };

                var pickr = Pickr.create(pickr_options);

                
                // store instance
                $input.data('pickr-instance', pickr);

                // When Pickr value changes
                pickr.on('change', function (color, instance) {
                    if (!color) return;
                    var colorStr;
                    try {
                        colorStr = color.toHEXA().toString();
                    } catch (e) {
                        var rgba = color.toRGBA();
                        colorStr = 'rgba(' + Math.round(rgba[0]) + ',' + Math.round(rgba[1]) + ',' + Math.round(rgba[2]) + ',1)';
                    }
                    $input.val(colorStr).trigger('change');
                });

                // Save event also syncs value
                pickr.on('save', function (color) {
                    if (!color) return;
                    var colorStr;
                    try {
                        colorStr = color.toHEXA().toString();
                    } catch (e) {
                        var rgba = color.toRGBA();
                        colorStr = 'rgba(' + Math.round(rgba[0]) + ',' + Math.round(rgba[1]) + ',' + Math.round(rgba[2]) + ',1)';
                    }
                    $input.val(colorStr).trigger('change');
                });

                // Clear sets empty
                pickr.on('clear', function () {
                    pickr.setColor('#FFFFFF');
                    $input.val('').trigger('change');
                });

                // expose container for backward compatibility
                $input.data('hope-color-container', $container);





            }
        });
    };

    //
    // ChosenJS
    //
    var aaaaa = 0;
    $.fn.hope_chosen = function () {
        return this.each(function () {
            aaaaa++;
            var $this = $(this),
                $inited = $this.parent().find('.chosen-container'),
                is_sortable = $this.hasClass('hope-chosen-sortable') || false,
                is_ajax = $this.hasClass('hope-chosen-ajax') || false,
                is_multiple = $this.attr('multiple') || false,
                set_width = is_multiple ? '100%' : 'auto',
                set_options = $.extend(
                    {
                        allow_single_deselect: true,
                        disable_search_threshold: 10,
                        width: set_width,
                        no_results_text: window.hope_vars.i18n.no_results_text,
                    },
                    $this.data('chosen-settings')
                );

            if ($inited.length) {
                $inited.remove();
            }

            // Chosen ajax
            if (is_ajax) {
                var set_ajax_options = $.extend(
                    {
                        data: {
                            type: 'post',
                            nonce: '',
                        },
                        allow_single_deselect: true,
                        disable_search_threshold: -1,
                        width: '100%',
                        min_length: 3,
                        type_delay: 500,
                        typing_text: window.hope_vars.i18n.typing_text,
                        searching_text: window.hope_vars.i18n.searching_text,
                        no_results_text: window.hope_vars.i18n.no_results_text,
                    },
                    $this.data('chosen-settings')
                );

                $this.HopeAjaxChosen(set_ajax_options);
            } else {
                $this.chosen(set_options);
            }

            // Chosen keep options order
            if (is_multiple) {
                var $hidden_select = $this.parent().find('.hope-hide-select');
                var $hidden_value = $hidden_select.val() || [];

                $this.on('change', function (obj, result) {
                    if (result && result.selected) {
                        //修改内容:添加一个空选项，避免post提交时，无法获取到值
                        $hidden_select.find('option.option-null').remove();

                        $hidden_select.append('<option value="' + result.selected + '" selected="selected">' + result.selected + '</option>');
                    } else if (result && result.deselected) {
                        $hidden_select.find('option[value="' + result.deselected + '"]').remove();

                        //修改内容:添加一个空选项，避免post提交时，无法获取到值
                        if (!$hidden_select.find('option').length) {
                            $hidden_select.append('<option class="option-null" value="" selected="selected"></option>');
                        }
                    }

                    // Force customize refresh
                    /*if (window.wp.customize !== undefined && $hidden_select.children().length === 0 && $hidden_select.data('customize-setting-link')) {
                        window.wp.customize.control($hidden_select.data('customize-setting-link')).setting.set('');
                    }*/

                    $hidden_select.trigger('change');
                });

                // Chosen order abstract
                $this.CSFChosenOrder($hidden_value, true);
            }

            // Chosen sortable
            if (is_sortable) {
                var $chosen_container = $this.parent().find('.chosen-container');
                var $chosen_choices = $chosen_container.find('.chosen-choices');

                $chosen_choices.bind('mousedown', function (event) {
                    if ($(event.target).is('span')) {
                        event.stopPropagation();
                    }
                });

                $chosen_choices.sortable({
                    items: 'li:not(.search-field)',
                    helper: 'orginal',
                    revert: 100,
                    cursor: 'move',
                    placeholder: 'search-choice-placeholder',
                    start: function (e, ui) {
                        ui.placeholder.width(ui.item.innerWidth());
                        ui.placeholder.height(ui.item.innerHeight());
                    },
                    update: function (e, ui) {
                        var select_options = '';
                        var chosen_object = $this.data('chosen');
                        var $prev_select = $this.parent().find('.hope-hide-select');

                        $chosen_choices.find('.search-choice-close').each(function () {
                            var option_array_index = $(this).data('option-array-index');
                            $.each(chosen_object.results_data, function (index, data) {
                                if (data.array_index === option_array_index) {
                                    select_options += '<option value="' + data.value + '" selected>' + data.value + '</option>';
                                }
                            });
                        });

                        $prev_select.children().remove();
                        $prev_select.append(select_options);
                        $prev_select.trigger('change');
                    },
                });
            }
        });
    };

    //
    // Helper Checkbox Checker
    //
    $.fn.hope_checkbox = function () {
        return this.each(function () {
            var $this = $(this),
                $input = $this.find('.hope--input'),
                $checkbox = $this.find('.hope--checkbox');

            $checkbox.on('click', function () {
                $input.val(Number($checkbox.prop('checked'))).trigger('change');
            });
        });
    };

    //
    // Siblings
    //
    $.fn.hope_siblings = function () {
        return this.each(function () {
            var $this = $(this),
                $siblings = $this.find('.hope--sibling'),
                multiple = $this.data('multiple') || false;

            $siblings.on('click', function () {
                var $sibling = $(this);

                if (multiple) {
                    if ($sibling.hasClass('hope--active')) {
                        $sibling.removeClass('hope--active');
                        $sibling.find('input').prop('checked', false).trigger('change');
                    } else {
                        $sibling.addClass('hope--active');
                        $sibling.find('input').prop('checked', true).trigger('change');
                    }
                } else {
                    $this.find('input').prop('checked', false);
                    $sibling.find('input').prop('checked', true).trigger('change');
                    $sibling.addClass('hope--active').siblings().removeClass('hope--active');
                }
            });
        });
    };

    //
    // Help Tooltip
    //
    $.fn.hope_help = function () {
        return this.each(function () {
            var $this = $(this),
                $tooltip,
                offset_left;

            $this.on({
                mouseenter: function () {
                    $tooltip = $('<div class="hope-tooltip"></div>').html($this.find('.hope-help-text').html()).appendTo('body');
                    offset_left = CSF.vars.is_rtl ? $this.offset().left + 24 : $this.offset().left - $tooltip.outerWidth();

                    $tooltip.css({
                        top: $this.offset().top - ($tooltip.outerHeight() / 2 - 14),
                        left: offset_left,
                    });
                },
                mouseleave: function () {
                    if ($tooltip !== undefined) {
                        $tooltip.remove();
                    }
                },
            });
        });
    };

    //
    // Customize Refresh
    //
    $.fn.hope_customizer_refresh = function () {
        return this.each(function () {
            var $this = $(this),
                $complex = $this.closest('.hope-customize-complex');

            if ($complex.length) {
                var $input = $complex.find(':input'),
                    $unique = $complex.data('unique-id'),
                    $option = $complex.data('option-id'),
                    obj = $input.serializeObjectCSF(),
                    data = !$.isEmptyObject(obj) ? obj[$unique][$option] : '',
                    control = window.wp.customize.control($unique + '[' + $option + ']');

                // clear the value to force refresh.
                control.setting._value = null;

                control.setting.set(data);
            } else {
                $this.find(':input').first().trigger('change');
            }

            $(document).trigger('hope-customizer-refresh', $this);
        });
    };

    //
    // Customize Listen Form Elements
    //
    $.fn.hope_customizer_listen = function (options) {
        var settings = $.extend(
            {
                closest: false,
            },
            options
        );

        return this.each(function () {
            if (window.wp.customize === undefined) {
                return;
            }

            var $this = settings.closest ? $(this).closest('.hope-customize-complex') : $(this),
                $input = $this.find(':input'),
                unique_id = $this.data('unique-id'),
                option_id = $this.data('option-id');

            if (unique_id === undefined) {
                return;
            }

            $input.on('change keyup', function () {
                var obj = $this.find(':input').serializeObjectCSF();
                var val = !$.isEmptyObject(obj) && obj[unique_id] && obj[unique_id][option_id] ? obj[unique_id][option_id] : '';

                window.wp.customize.control(unique_id + '[' + option_id + ']').setting.set(val);
            });
        });
    };

    //
    // Customizer Listener for Reload JS
    //
    $(document).on('expanded', '.control-section', function () {
        var $this = $(this);

        if ($this.hasClass('open') && !$this.data('inited')) {
            var $fields = $this.find('.hope-customize-field');
            var $complex = $this.find('.hope-customize-complex');

            if ($fields.length) {
                $this.hope_dependency();
                $fields.hope_reload_script({
                    dependency: false,
                });
                $complex.hope_customizer_listen();
            }

            $this.data('inited', true);
        }
    });

    //
    // Window on resize
    //
    CSF.vars.$window
        .on(
            'resize hope.resize',
            CSF.helper.debounce(function (event) {
                var window_width = navigator.userAgent.indexOf('AppleWebKit/') > -1 ? CSF.vars.$window.width() : window.innerWidth;

                if (window_width <= 782 && !CSF.vars.onloaded) {
                    $('.hope-section').hope_reload_script();
                    CSF.vars.onloaded = true;
                }
            }, 200)
        )
        .trigger('hope.resize');

    //
    // Widgets Framework
    //
    $.fn.hope_widgets = function () {
        if (this.length) {
            $(document).on('widget-added widget-updated', function (event, $widget) {
                $widget.find('.hope-fields').hope_reload_script();
            });

            $('.widgets-sortables, .control-section-sidebar').on('sortstop', function (event, ui) {
                ui.item.find('.hope-fields').hope_reload_script_retry();
            });

            $(document).on('click', '.widget-top', function (event) {
                $(this).parent().find('.hope-fields').hope_reload_script();
            });
        }
    };

    //
    // Nav Menu Options Framework
    //
    $.fn.hope_nav_menu = function () {
        return this.each(function () {
            var $navmenu = $(this);

            $navmenu.on('click', 'a.item-edit', function () {
                $(this).closest('li.menu-item').find('.hope-fields').hope_reload_script();
            });

            $navmenu.on('sortstop', function (event, ui) {
                ui.item.find('.hope-fields').hope_reload_script_retry();
            });
        });
    };

    //
    // Retry Plugins
    //
    $.fn.hope_reload_script_retry = function () {
        return this.each(function () {
            var $this = $(this);

            if ($this.data('inited')) {
                $this.children('.hope-field-wp_editor').hope_field_wp_editor();
            }
        });
    };

    //
    // Reload Plugins
    //
    $.fn.hope_reload_script = function (options) {
        var settings = $.extend(
            {
                dependency: true,
            },
            options
        );

        return this.each(function () {
            var $this = $(this);

            // Avoid for conflicts
            if (!$this.data('inited')) {
                // Field plugins
                $this.children('.hope-field-accordion').hope_field_accordion();
                $this.children('.hope-field-backup').hope_field_backup();
                $this.children('.hope-field-background').hope_field_background();
                $this.children('.hope-field-code_editor').hope_field_code_editor();
                $this.children('.hope-field-date').hope_field_date();
                $this.children('.hope-field-fieldset').hope_field_fieldset();
                $this.children('.hope-field-gallery').hope_field_gallery();
                $this.children('.hope-field-group').hope_field_group();
                $this.children('.hope-field-icon').hope_field_icon();
                $this.children('.hope-field-link').hope_field_link();
                $this.children('.hope-field-media').hope_field_media();
                $this.children('.hope-field-map').hope_field_map();
                $this.children('.hope-field-repeater').hope_field_repeater();
                $this.children('.hope-field-slider').hope_field_slider();
                $this.children('.hope-field-sortable').hope_field_sortable();
                $this.children('.hope-field-sorter').hope_field_sorter();
                $this.children('.hope-field-spinner').hope_field_spinner();
                $this.children('.hope-field-switcher').hope_field_switcher();
                $this.children('.hope-field-tabbed').hope_field_tabbed();
                $this.children('.hope-field-typography').hope_field_typography();
                $this.children('.hope-field-upload').hope_field_upload();
                $this.children('.hope-field-wp_editor').hope_field_wp_editor();

                // Field colors
                $this.children('.hope-field-border').find('.hope-color').hope_color();
                $this.children('.hope-field-background').find('.hope-color').hope_color();
                $this.children('.hope-field-color').find('.hope-color').hope_color();
                $this.children('.hope-field-color_group').find('.hope-color').hope_color();
                $this.children('.hope-field-link_color').find('.hope-color').hope_color();
                $this.children('.hope-field-typography').find('.hope-color').hope_color();

                // Field chosenjs
                $this.children('.hope-field-select').find('.hope-chosen').hope_chosen();

                // Field Checkbox
                $this.children('.hope-field-checkbox').find('.hope-checkbox').hope_checkbox();

                // Field Siblings
                $this.children('.hope-field-button_set').find('.hope-siblings').hope_siblings();
                $this.children('.hope-field-image_select').find('.hope-siblings').hope_siblings();
                $this.children('.hope-field-palette').find('.hope-siblings').hope_siblings();

                // Help Tooptip
                $this.children('.hope-field').find('.hope-help').hope_help();

                if (settings.dependency) {
                    $this.hope_dependency();
                }

                $this.data('inited', true);

                $(document).trigger('hope-reload-script', $this);
            }
        });
    };

    //
    // Document ready and run scripts
    //
    
    $(document).ready(function () {
        $('.hope-save').hope_save();
        $('.hope-options').hope_options();
        $('.hope-sticky-header').hope_sticky();
        $('.hope-nav-options').hope_nav_options();
        $('.hope-nav-metabox').hope_nav_metabox();
        $('.hope-taxonomy').hope_taxonomy();
        $('.hope-page-templates').hope_page_templates();
        $('.hope-post-formats').hope_post_formats();
        $('.hope-shortcode').hope_shortcode();
        $('.hope-search').hope_search();
        $('.hope-confirm').hope_confirm();
        $('.hope-expand-all').hope_expand_all();
        $('.hope-onload').hope_reload_script();
        $('.widget').hope_widgets();
        $('#menu-to-edit').hope_nav_menu();
    });
})(jQuery, window, document);



$(".hope-menu").on('click', function (e) {
    $(".hope-nav-options").toggleClass("show");
});

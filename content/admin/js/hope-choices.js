/**
 * HopeCMS Choices.js 封装
 * - 统一下拉初始化与预设
 * - 使用 Choices 原生定位，仅处理 z-index / overflow 层叠
 */
(function (window) {
    'use strict';

    var PRESETS = {
        select: {
            shouldSort: false,
            itemSelectText: '',
            searchEnabled: false,
            position: 'auto'
        },
        search: {
            shouldSort: false,
            itemSelectText: '',
            searchEnabled: true,
            position: 'auto'
        },
        tag: {
            shouldSort: false,
            itemSelectText: '',
            searchEnabled: true,
            delimiter: ',',
            editItems: true,
            removeItemButton: true,
            duplicateItemsAllowed: false,
            addItems: true
        }
    };

    function isChoicesElement(el) {
        if (!el || !el.tagName) return false;
        var tag = el.tagName.toUpperCase();
        return tag === 'SELECT' || tag === 'INPUT' || tag === 'TEXTAREA';
    }

    function parseOptions(el, custom) {
        var opts = Object.assign({}, PRESETS.select, custom || {});
        var preset = el.dataset && el.dataset.preset;
        if (preset && PRESETS[preset]) {
            opts = Object.assign({}, opts, PRESETS[preset]);
        }
        if (el.dataset) {
            if (el.dataset.search === 'true') opts.searchEnabled = true;
            if (el.dataset.search === 'false') opts.searchEnabled = false;
            if (el.dataset.sort === 'true') opts.shouldSort = true;
        }
        return opts;
    }

    function shouldSkip(el) {
        if (!el || !el.dataset) return false;
        var flag = el.dataset.hopeChoices;
        if (flag === 'false' || flag === 'off' || flag === '0') return true;
        if (el.classList.contains('hope-choices-skip')) return true;
        if (el.classList.contains('type-id-select') && el.disabled) return true;
        if (el.closest && el.closest('.modal:not(.show)')) return true;
        return false;
    }

    function hopeDestroyChoices(element) {
        if (!element || !element._hopeChoices) return;
        try {
            element._hopeChoices.destroy();
        } catch (e) {}
        delete element._hopeChoices;
    }

    function hopeRefreshChoices(element, options) {
        hopeDestroyChoices(element);
        return hopeInitChoices(element, options);
    }

    function hopeInitChoices(element, options) {
        if (!isChoicesElement(element) || typeof window.Choices === 'undefined') {
            return null;
        }
        if (shouldSkip(element)) {
            return null;
        }
        if (element._hopeChoices) {
            return element._hopeChoices;
        }
        if (element.closest && element.closest('.choices')) {
            return null;
        }
        var instance = new window.Choices(element, parseOptions(element, options));
        element._hopeChoices = instance;
        return instance;
    }

    function hopeInitChoicesAll(root) {
        var scope = root || document;
        if (!scope.querySelectorAll) return;
        scope.querySelectorAll('[data-hope-choices]').forEach(function (el) {
            hopeInitChoices(el);
        });
    }

    function toggleColOpen(select, open) {
        var col = select.closest('[class*="col-"]');
        if (col) col.classList.toggle('choices-col-open', open);
    }

    document.addEventListener('showDropdown', function (e) {
        var select = e.target;
        if (!select || select.tagName !== 'SELECT') return;
        toggleColOpen(select, true);
    });

    document.addEventListener('hideDropdown', function (e) {
        var select = e.target;
        if (!select || select.tagName !== 'SELECT') return;
        toggleColOpen(select, false);
    });

    document.addEventListener('shown.bs.modal', function (e) {
        var modal = e.target;
        if (!modal || !modal.querySelectorAll) return;
        modal.querySelectorAll('[data-hope-choices]').forEach(function (el) {
            hopeRefreshChoices(el);
        });
    });

    function boot() {
        hopeInitChoicesAll();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.HOPE_CHOICES_PRESETS = PRESETS;
    window.hopeDestroyChoices = hopeDestroyChoices;
    window.hopeRefreshChoices = hopeRefreshChoices;
    window.hopeInitChoices = hopeInitChoices;
    window.hopeInitChoicesAll = hopeInitChoicesAll;
}(window));

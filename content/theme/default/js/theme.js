(function () {
    'use strict';

    var root = document.documentElement;
    var stored = localStorage.getItem('nova-theme');
    var defaultTheme = root.getAttribute('data-theme') || 'auto';

    function systemDark() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function applyTheme(mode) {
        var resolved = mode;
        if (mode === 'auto') {
            resolved = systemDark() ? 'dark' : 'light';
        }
        root.setAttribute('data-theme-resolved', resolved);
        root.style.colorScheme = resolved;
        localStorage.setItem('nova-theme', mode);
        syncToggle(resolved);
    }

    function syncToggle(resolved) {
        var toggle = document.getElementById('theme-toggle');
        if (!toggle) return;
        var isDark = resolved === 'dark';
        toggle.setAttribute('aria-label', isDark ? '切换到浅色模式' : '切换到深色模式');
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggle.title = isDark ? '浅色' : '深色';
    }

    applyTheme(stored || defaultTheme);

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            var current = localStorage.getItem('nova-theme') || defaultTheme;
            if (current === 'auto') {
                applyTheme('auto');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('theme-toggle');
        if (toggle) {
            syncToggle(root.getAttribute('data-theme-resolved') || 'light');
            toggle.addEventListener('click', function () {
                var current = localStorage.getItem('nova-theme') || defaultTheme;
                var resolved = root.getAttribute('data-theme-resolved') || 'light';
                // 始终在当前视觉深/浅之间切换，避免 auto 状态混乱
                var next = resolved === 'dark' ? 'light' : 'dark';
                applyTheme(next);
            });
        }

        var navToggle = document.getElementById('nav-toggle');
        var nav = document.getElementById('site-nav');
        var navBackdrop = document.getElementById('nav-backdrop');

        function setMobileNav(open) {
            if (!nav || !navToggle) return;
            nav.classList.toggle('is-open', open);
            navToggle.classList.toggle('is-open', open);
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.setAttribute('aria-label', open ? '关闭菜单' : '打开菜单');
            document.body.classList.toggle('nav-open', open);
            if (navBackdrop) {
                if (open) {
                    navBackdrop.hidden = false;
                    navBackdrop.classList.add('is-open');
                } else {
                    navBackdrop.classList.remove('is-open');
                    navBackdrop.hidden = true;
                }
            }
            if (!open) {
                nav.querySelectorAll('.nav-item.is-open').forEach(function (item) {
                    item.classList.remove('is-open');
                    var link = item.querySelector(':scope > .nav-link');
                    if (link) link.setAttribute('aria-expanded', 'false');
                });
            }
        }

        function isMobileNav() {
            return window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
        }

        if (navToggle && nav) {
            navToggle.addEventListener('click', function () {
                setMobileNav(!nav.classList.contains('is-open'));
            });
        }
        if (navBackdrop) {
            navBackdrop.addEventListener('click', function () { setMobileNav(false); });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setMobileNav(false);
        });
        window.addEventListener('resize', function () {
            if (!isMobileNav()) setMobileNav(false);
        });

        if (nav) {
            nav.querySelectorAll('.nav-item.has-children > .nav-link').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    if (!isMobileNav()) return;
                    var item = link.closest('.nav-item');
                    if (!item) return;
                    e.preventDefault();
                    var open = !item.classList.contains('is-open');
                    nav.querySelectorAll('.nav-item.is-open').forEach(function (other) {
                        if (other !== item) {
                            other.classList.remove('is-open');
                            var otherLink = other.querySelector(':scope > .nav-link');
                            if (otherLink) otherLink.setAttribute('aria-expanded', 'false');
                        }
                    });
                    item.classList.toggle('is-open', open);
                    link.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            });
        }

        initHeroSlider();
        initReadingProgress();
        initSearchPanel();
        initHeaderScroll();
    });

    function initHeaderScroll() {
        var header = document.getElementById('site-header');
        if (!header) return;
        var onScroll = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    function initSearchPanel() {
        var btn = document.getElementById('search-toggle');
        var panel = document.getElementById('header-search-panel');
        if (!btn || !panel) return;
        btn.addEventListener('click', function () {
            var open = panel.hasAttribute('hidden');
            if (open) {
                panel.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
                var input = panel.querySelector('input[type="search"]');
                if (input) input.focus();
            } else {
                panel.setAttribute('hidden', '');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function initHeroSlider() {
        var slider = document.getElementById('hero-slider');
        if (!slider) return;

        var slides = slider.querySelectorAll('.hero-slide');
        var dots = slider.querySelectorAll('.hero-dot');
        if (slides.length <= 1) return;

        var index = 0;
        var autoplay = slider.getAttribute('data-autoplay') === '1';
        var timer = null;

        function goTo(i) {
            index = (i + slides.length) % slides.length;
            slides.forEach(function (s, n) { s.classList.toggle('is-active', n === index); });
            dots.forEach(function (d, n) { d.classList.toggle('is-active', n === index); });
        }

        function next() { goTo(index + 1); }
        function prev() { goTo(index - 1); }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                goTo(parseInt(dot.getAttribute('data-index'), 10));
                resetTimer();
            });
        });

        var prevBtn = slider.querySelector('.hero-prev');
        var nextBtn = slider.querySelector('.hero-next');
        if (prevBtn) prevBtn.addEventListener('click', function () { prev(); resetTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { next(); resetTimer(); });

        function resetTimer() {
            if (timer) clearInterval(timer);
            if (autoplay) timer = setInterval(next, 5500);
        }
        resetTimer();
    }

    function initReadingProgress() {
        var bar = document.getElementById('reading-progress');
        var article = document.querySelector('.article-content');
        if (!bar || !article) return;

        function update() {
            var rect = article.getBoundingClientRect();
            var total = article.offsetHeight - window.innerHeight;
            if (total <= 0) {
                bar.style.width = '100%';
                return;
            }
            var scrolled = Math.min(Math.max(-rect.top, 0), total);
            bar.style.width = (scrolled / total * 100) + '%';
        }

        window.addEventListener('scroll', update, { passive: true });
        update();
    }
})();

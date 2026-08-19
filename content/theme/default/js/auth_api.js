/* 个人中心认证 API - 登录 / 注册 / 退出 */
(function () {
    var loginForm = document.getElementById('login-form');
    var registerForm = document.getElementById('register-form');

    function showMsg(el, msg, isError) {
        var box = el.querySelector('.form-error') || document.createElement('p');
        box.className = 'form-error show';
        if (isError) box.style.color = '';
        box.textContent = msg;
        if (!el.querySelector('.form-error')) {
            el.insertBefore(box, el.firstElementChild.nextElementSibling);
        }
    }

    function authApi(form, action, buildBody) {
        var api = form.dataset.authApi;
        var token = form.dataset.token;
        var url = HopeAuthApi.buildUrl(api, action);
        var body = buildBody();
        body.append('token', token);
        return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.code !== 1 && res.code !== 200) throw new Error(res.msg || '请求失败');
                return res.data;
            });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = loginForm.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            authApi(loginForm, 'login', function () {
                var fd = new FormData();
                fd.append('user', loginForm.querySelector('[name="user"]').value.trim());
                fd.append('password', loginForm.querySelector('[name="password"]').value);
                fd.append('redirect', loginForm.dataset.redirect || loginForm.querySelector('[name="redirect"]')?.value || '');
                var remember = loginForm.querySelector('[name="rememberMe"]');
                if (remember && remember.checked) fd.append('rememberMe', '1');
                var code = loginForm.querySelector('[name="login_code"]');
                if (code) fd.append('login_code', code.value.trim());
                return fd;
            }).then(function (data) {
                window.location.href = data.redirect || loginForm.dataset.redirect || '/';
            }).catch(function (err) {
                showMsg(loginForm, err.message, true);
                if (btn) btn.disabled = false;
            });
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = registerForm.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            authApi(registerForm, 'register', function () {
                var fd = new FormData();
                fd.append('mail', registerForm.querySelector('[name="mail"]').value.trim());
                fd.append('passwd', registerForm.querySelector('[name="passwd"]').value);
                fd.append('repasswd', registerForm.querySelector('[name="repasswd"]').value);
                var invite = registerForm.querySelector('[name="invite"]');
                if (invite) fd.append('invite', invite.value.trim());
                var code = registerForm.querySelector('[name="login_code"]');
                if (code) fd.append('login_code', code.value.trim());
                var mailCode = registerForm.querySelector('[name="mail_code"]');
                if (mailCode) fd.append('mail_code', mailCode.value.trim());
                return fd;
            }).then(function (data) {
                alert(data.msg || '注册成功');
                window.location.href = data.redirect || '/';
            }).catch(function (err) {
                showMsg(registerForm, err.message, true);
                if (btn) btn.disabled = false;
            });
        });
    }

    window.HopeAuthApi = {
        buildUrl: function (apiUrl, action) {
            var url = apiUrl || '';
            if (url.indexOf('api=') < 0) {
                url += (url.indexOf('?') >= 0 ? '&' : '?') + 'api=1';
            }
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'action=' + encodeURIComponent(action);
            return url;
        },
        logout: function (apiUrl, token) {
            var url = HopeAuthApi.buildUrl(apiUrl, 'logout');
            var fd = new FormData();
            fd.append('token', token);
            return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.code !== 1) throw new Error(res.msg || '退出失败');
                    window.location.href = res.data.redirect || '/';
                });
        }
    };
})();

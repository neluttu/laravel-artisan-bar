/**
 * Laravel Artisan Bar - Self-contained vanilla JS
 */
(function () {
    'use strict';

    const bar = {
        el: null,
        open: false,
        authenticated: false,
        loading: false,
        cmd: '',
        outputs: [],
        history: [],
        historyIndex: -1,
        outputHeight: 224,
        _resizing: false,
        _startY: 0,
        _startHeight: 0,
        _pendingConfirm: null,

        config: {
            loginUrl: '',
            logoutUrl: '',
            runUrl: '',
            csrfToken: '',
            authMode: 'password',
            hasPasswordAuth: false,
            promptUser: '',
            promptHost: '',
            commands: {},
            shellMode: 'disabled',
            shellAliases: {},
        },

        init() {
            this.el = document.getElementById('artisan-bar');
            if (!this.el) return;

            const d = this.el.dataset;
            this.config.loginUrl = d.loginUrl || '';
            this.config.logoutUrl = d.logoutUrl || '';
            this.config.runUrl = d.runUrl || '';
            this.config.csrfToken = d.csrfToken || '';
            this.config.authMode = d.authMode || 'password';
            this.config.hasPasswordAuth = d.hasPasswordAuth === '1';
            this.config.promptUser = d.promptUser || 'admin';
            this.config.promptHost = d.promptHost || 'localhost';

            try {
                this.config.commands = JSON.parse(d.commands || '{}');
                this.config.shellAliases = JSON.parse(d.shellAliases || '{}');
                this.config.shellMode = d.shellMode || 'disabled';
            } catch (e) {}

            // If app-auth mode or either with no password, assume authenticated
            if (this.config.authMode === 'app-auth' ||
                (this.config.authMode === 'either' && !this.config.hasPasswordAuth)) {
                this.authenticated = true;
            }

            this.bindEvents();
            this.render();
        },

        bindEvents() {
            // Tab click
            this.el.querySelector('.artisan-bar-tab').addEventListener('click', () => {
                this.open = true;
                this.render();
                this.focusInput();
            });

            // Resize
            this.el.querySelector('.artisan-bar-resize').addEventListener('mousedown', (e) => {
                e.preventDefault();
                this._resizing = true;
                this._startY = e.clientY;
                this._startHeight = this.outputHeight;

                const onMove = (e) => {
                    if (!this._resizing) return;
                    const diff = this._startY - e.clientY;
                    this.outputHeight = Math.max(100, Math.min(window.innerHeight * 0.8, this._startHeight + diff));
                    this.el.querySelector('.artisan-bar-output').style.height = this.outputHeight + 'px';
                };
                const onUp = () => {
                    this._resizing = false;
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                };
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });

            // Output scroll lock
            const outputEl = this.el.querySelector('.artisan-bar-output');
            outputEl.addEventListener('mouseenter', () => { document.body.style.overflow = 'hidden'; });
            outputEl.addEventListener('mouseleave', () => { document.body.style.overflow = ''; });

            // Input events
            const cmdInput = this.el.querySelector('.artisan-bar-input');
            const pwdInput = this.el.querySelector('.artisan-bar-password-input');

            cmdInput.addEventListener('keydown', (e) => this.handleKeydown(e, 'cmd'));
            cmdInput.addEventListener('input', (e) => { this.cmd = e.target.value; this.renderSuggestions(); });

            pwdInput.addEventListener('keydown', (e) => this.handleKeydown(e, 'password'));

            // Suggestion clicks (delegated)
            this.el.querySelector('.artisan-bar-suggestions').addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (!btn) return;
                this.cmd = btn.textContent;
                cmdInput.value = this.cmd;
                cmdInput.focus();
                this.renderSuggestions();
            });

            // Minimize button
            this.el.querySelector('.artisan-bar-btn-minimize').addEventListener('click', () => {
                this.open = false;
                document.body.style.overflow = '';
                this.render();
            });

            // Clear button
            this.el.querySelector('.artisan-bar-btn-clear').addEventListener('click', () => {
                this.outputs = [];
                this.renderOutput();
            });
        },

        handleKeydown(e, type) {
            if (e.key === 'Escape') {
                this.open = false;
                document.body.style.overflow = '';
                this.render();
                return;
            }

            if (type === 'password' && e.key === 'Enter') {
                this.doLogin(e.target.value);
                e.target.value = '';
                return;
            }

            if (type === 'cmd') {
                if (e.key === 'Enter') {
                    this.runCommand();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.historyUp();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.historyDown();
                } else if (e.key === 'Tab') {
                    e.preventDefault();
                    this.pickFirstSuggestion();
                }
            }
        },

        async doLogin(password) {
            this.loading = true;
            this.render();

            try {
                const res = await fetch(this.config.loginUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ password }),
                });
                const data = await res.json();

                if (data.ok) {
                    this.authenticated = true;
                    this.addOutput('/login', true, 'Authenticated.');
                } else {
                    this.addOutput('/login', false, data.output || 'Invalid password.');
                }
            } catch (err) {
                this.addOutput('/login', false, err.message);
            }

            this.loading = false;
            this.render();
            this.focusInput();
        },

        async runCommand() {
            let cmd = this.cmd.trim();
            if (!cmd || this.loading) return;

            // Local commands
            if (cmd === '/help') { this.showHelp(); return; }
            if (cmd === '/clear') { this.outputs = []; this.cmd = ''; this.renderCmdInput(); this.renderOutput(); this.renderSuggestions(); return; }
            if (cmd === '/logout') { await this.doLogout(); return; }

            // Confirmation response
            if (this._pendingConfirm && (cmd.toLowerCase() === 'y' || cmd.toLowerCase() === 'yes')) {
                cmd = this._pendingConfirm;
                this._pendingConfirm = null;
                this.cmd = '';
                this.renderCmdInput();
                await this.sendCommand(cmd, true);
                return;
            } else if (this._pendingConfirm) {
                this._pendingConfirm = null;
                this.addOutput(cmd, true, 'Cancelled.');
                this.cmd = '';
                this.renderCmdInput();
                this.renderSuggestions();
                return;
            }

            this.history.unshift(cmd);
            this.historyIndex = -1;
            this.cmd = '';
            this.renderCmdInput();
            this.renderSuggestions();

            await this.sendCommand(cmd, false);
        },

        async sendCommand(cmd, confirmed) {
            this.loading = true;
            this.render();

            try {
                const res = await fetch(this.config.runUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ cmd, confirmed }),
                });

                if (res.status === 401) {
                    this.authenticated = false;
                    this.addOutput(cmd, false, 'Session expired. Please login again.');
                    this.loading = false;
                    this.render();
                    return;
                }

                const data = await res.json();

                if (data.confirm) {
                    this._pendingConfirm = cmd;
                    this.addOutput(cmd, null, data.output, 'warn');
                } else {
                    this.addOutput(cmd, data.ok, data.output);
                }
            } catch (err) {
                this.addOutput(cmd, false, err.message);
            }

            this.loading = false;
            this.render();
        },

        async doLogout() {
            try {
                await fetch(this.config.logoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'Accept': 'application/json',
                    },
                });
            } catch (e) {}

            this.authenticated = false;
            this.cmd = '';
            this.addOutput('/logout', true, 'Logged out.');
            this.render();
        },

        showHelp() {
            let output = '';
            const cmds = this.config.commands;
            const groups = {};

            for (const cmd in cmds) {
                const isDangerous = cmds[cmd].dangerous;
                const label = isDangerous ? cmd + ' [!]' : cmd;
                // Group by prefix
                const prefix = cmd.split(':')[0] || 'other';
                if (!groups[prefix]) groups[prefix] = [];
                groups[prefix].push(label);
            }

            for (const [group, list] of Object.entries(groups)) {
                output += `── ${group} ──\n${list.join(',  ')}\n\n`;
            }

            if (this.config.shellMode === 'aliases') {
                const aliases = Object.keys(this.config.shellAliases);
                if (aliases.length) {
                    output += `── shell aliases ──\n${aliases.join(',  ')}\n\n`;
                }
            }

            if (this.config.shellMode === 'raw') {
                output += `── shell (raw) ──\nAny command (requires confirmation)\n\n`;
            }

            output += '── terminal ──\n/help,  /clear,  /logout';

            this.addOutput('/help', true, output.trim());
            this.cmd = '';
            this.renderCmdInput();
            this.renderSuggestions();
        },

        addOutput(cmd, ok, text, type) {
            this.outputs.push({ cmd, ok, text, type: type || (ok === true ? 'ok' : ok === false ? 'err' : 'warn') });
            this.renderOutput();
        },

        historyUp() {
            if (this.history.length === 0) return;
            if (this.historyIndex < this.history.length - 1) {
                this.historyIndex++;
                this.cmd = this.history[this.historyIndex];
                this.renderCmdInput();
                this.renderSuggestions();
            }
        },

        historyDown() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.cmd = this.history[this.historyIndex];
            } else {
                this.historyIndex = -1;
                this.cmd = '';
            }
            this.renderCmdInput();
            this.renderSuggestions();
        },

        getAllCommands() {
            const list = [];
            for (const cmd in this.config.commands) list.push(cmd);
            if (this.config.shellMode === 'aliases') {
                for (const alias in this.config.shellAliases) list.push(alias);
            }
            list.push('/help', '/clear', '/logout');
            return list;
        },

        getSuggestions() {
            let q = this.cmd.trim().toLowerCase();
            if (!q) return [];

            // Strip php artisan prefix for matching
            const stripped = q.replace(/^(php\s+)?artisan\s+/, '');
            if (stripped !== q) q = stripped;

            return this.getAllCommands().filter(c => c.toLowerCase().includes(q)).slice(0, 8);
        },

        pickFirstSuggestion() {
            const suggestions = this.getSuggestions();
            if (suggestions.length > 0) {
                this.cmd = suggestions[0];
                this.renderCmdInput();
                this.renderSuggestions();
            }
        },

        // ── Rendering ──

        render() {
            const tab = this.el.querySelector('.artisan-bar-tab');
            const main = this.el.querySelector('.artisan-bar-main');
            const pwdRow = this.el.querySelector('.artisan-bar-password-row');
            const cmdRow = this.el.querySelector('.artisan-bar-cmd-row');
            const spinner = this.el.querySelector('.artisan-bar-spinner');
            const clearBtn = this.el.querySelector('.artisan-bar-btn-clear');

            tab.style.display = this.open ? 'none' : 'flex';
            main.style.display = this.open ? 'block' : 'none';

            const needsPassword = !this.authenticated && this.config.hasPasswordAuth;
            pwdRow.style.display = needsPassword ? 'flex' : 'none';
            cmdRow.style.display = needsPassword ? 'none' : 'flex';

            spinner.style.display = this.loading ? 'block' : 'none';
            clearBtn.style.display = this.outputs.length > 0 ? 'inline' : 'none';

            this.renderOutput();
            this.renderSuggestions();
        },

        renderOutput() {
            const outputEl = this.el.querySelector('.artisan-bar-output');
            const outputWrap = this.el.querySelector('.artisan-bar-output-wrap');
            const hasOutput = this.open && this.outputs.length > 0;

            outputWrap.style.display = hasOutput ? 'block' : 'none';
            outputEl.style.height = this.outputHeight + 'px';

            let html = '';
            for (const entry of this.outputs) {
                const cls = entry.type === 'ok' ? 'artisan-bar-output-ok' :
                            entry.type === 'warn' ? 'artisan-bar-output-warn' :
                            'artisan-bar-output-err';
                const cmdText = entry.cmd.startsWith('/') ? '$ ' + entry.cmd : '$ ' + entry.cmd;
                html += `<div class="artisan-bar-output-entry">
                    <div class="artisan-bar-output-cmd">${this.escHtml(cmdText)}</div>
                    <pre class="artisan-bar-output-text ${cls}">${this.escHtml(entry.text)}</pre>
                </div>`;
            }
            outputEl.innerHTML = html;
            outputEl.scrollTop = outputEl.scrollHeight;
        },

        renderSuggestions() {
            const sugEl = this.el.querySelector('.artisan-bar-suggestions');
            const suggestions = this.getSuggestions();

            if (!this.open || !this.authenticated || suggestions.length === 0 || this.cmd.trim() === '') {
                sugEl.style.display = 'none';
                return;
            }

            sugEl.style.display = 'flex';
            sugEl.innerHTML = suggestions.map(s => `<button>${this.escHtml(s)}</button>`).join('');
        },

        renderCmdInput() {
            const input = this.el.querySelector('.artisan-bar-input');
            input.value = this.cmd;
        },

        focusInput() {
            setTimeout(() => {
                if (this.authenticated) {
                    this.el.querySelector('.artisan-bar-input').focus();
                } else if (this.config.hasPasswordAuth) {
                    this.el.querySelector('.artisan-bar-password-input').focus();
                }
            }, 50);
        },

        escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },
    };

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => bar.init());
    } else {
        bar.init();
    }
})();

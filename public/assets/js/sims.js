// CyberQuest simulations. Everything runs client-side; no real attacks happen.
(function () {
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    const S = (window.CQSims = {});

    /* ---------- 1. Linux terminal ---------- */
    S.linux = (root) => {
        const F = {
            '/': 'd', '/home': 'd', '/home/student': 'd', '/home/student/Documents': 'd',
            '/home/student/notes.txt': 'Reminder: never store passwords in plain text files.\nNext lab: file permissions.',
            '/home/student/.hidden_flag': 'CQ{hidden_files_are_not_secret}',
            '/home/student/Documents/todo.txt': '1. Finish the CyberQuest module\n2. Revise Linux commands',
            '/etc': 'd', '/etc/hostname': 'cyberquest-lab',
            '/etc/passwd': 'root:x:0:0:root:/root:/bin/bash\nstudent:x:1000:1000:Student:/home/student:/bin/bash',
            '/var': 'd', '/var/log': 'd',
            '/var/log/auth.log': 'Nov 05 10:01 sshd: Failed password for root\nNov 05 10:02 sshd: Accepted password for student',
        };
        const HOME = '/home/student';
        let cwd = HOME;

        root.innerHTML = `<div class="term"><div class="term-out" id="t-out">Type "help" to see the available commands.\n</div>
            <div class="term-line"><span id="t-prompt"></span><input id="t-in" autocomplete="off" spellcheck="false" aria-label="Terminal input"></div></div>`;
        const out = root.querySelector('#t-out'), input = root.querySelector('#t-in'), prompt = root.querySelector('#t-prompt');

        const abs = (p) => {
            if (!p) return cwd;
            if (p === '~') p = HOME; else if (p.startsWith('~/')) p = HOME + p.slice(1);
            const parts = (p.startsWith('/') ? p : cwd + '/' + p).split('/'), o = [];
            for (const s of parts) { if (!s || s === '.') continue; if (s === '..') o.pop(); else o.push(s); }
            return '/' + o.join('/');
        };
        const list = (dir, all) => {
            const pre = dir === '/' ? '/' : dir + '/';
            return Object.keys(F).filter((k) => k !== dir && k.startsWith(pre) && !k.slice(pre.length).includes('/'))
                .map((k) => k.slice(pre.length)).filter((n) => all || !n.startsWith('.')).sort();
        };
        const setPrompt = () => { prompt.textContent = 'student@cyberquest:' + (cwd === HOME ? '~' : cwd) + '$'; };

        const run = (line) => {
            const [cmd, ...a] = line.trim().split(/\s+/);
            const flags = a.filter((x) => x.startsWith('-')).join(''), args = a.filter((x) => !x.startsWith('-'));
            switch (cmd) {
                case '': return null;
                case 'help': return 'Commands: help, pwd, ls [-a], cd, cat, grep, whoami, id, uname, echo, clear';
                case 'pwd': return cwd;
                case 'whoami': return 'student';
                case 'id': return 'uid=1000(student) gid=1000(student) groups=1000(student)';
                case 'uname': return 'Linux cyberquest-lab 6.5.0 x86_64 GNU/Linux';
                case 'echo': return a.join(' ');
                case 'clear': out.textContent = ''; return null;
                case 'ls': {
                    const p = abs(args[0]);
                    if (!F[p]) return `ls: cannot access '${args[0]}': No such file or directory`;
                    return F[p] === 'd' ? list(p, flags.includes('a')).join('  ') : args[0];
                }
                case 'cd': {
                    const p = abs(args[0] || '~');
                    if (!F[p]) return `cd: ${args[0]}: No such file or directory`;
                    if (F[p] !== 'd') return `cd: ${args[0]}: Not a directory`;
                    cwd = p; return null;
                }
                case 'cat': {
                    if (!args[0]) return 'cat: missing file operand';
                    const p = abs(args[0]);
                    if (!F[p]) return `cat: ${args[0]}: No such file or directory`;
                    return F[p] === 'd' ? `cat: ${args[0]}: Is a directory` : F[p];
                }
                case 'grep': {
                    if (args.length < 2) return 'usage: grep PATTERN FILE';
                    const p = abs(args[1]);
                    if (!F[p] || F[p] === 'd') return `grep: ${args[1]}: No such file`;
                    return F[p].split('\n').filter((l) => l.toLowerCase().includes(args[0].toLowerCase())).join('\n');
                }
                default: return `${cmd}: command not found`;
            }
        };

        input.addEventListener('keydown', (ev) => {
            if (ev.key !== 'Enter') return;
            const line = input.value;
            out.textContent += prompt.textContent + ' ' + line + '\n';
            const res = run(line);
            if (res !== null) out.textContent += res + (res ? '\n' : '');
            input.value = '';
            setPrompt();
            out.scrollTop = out.scrollHeight;
        });
        setPrompt();
    };

    /* ---------- 2. SQL injection ---------- */
    S.sqli = (root) => {
        const USERS = [
            { id: 1, username: 'admin', password: 's3cret!', role: 'admin' },
            { id: 2, username: 'alice', password: 'wonderland', role: 'student' },
            { id: 3, username: 'bob', password: 'builder99', role: 'student' },
        ];
        root.innerHTML = `
            <div class="sim-head"><strong>Vulnerable login form</strong>
                <label class="toggle"><input type="checkbox" id="q-safe"> Secure mode (prepared statement)</label></div>
            <div class="sim-row">
                <label>Username<input id="q-user" autocomplete="off" spellcheck="false" placeholder="alice"></label>
                <label>Password<input id="q-pass" autocomplete="off" spellcheck="false" placeholder="password"></label>
                <button class="btn" id="q-go" type="button">Login</button>
            </div>
            <p class="muted">Known usernames: admin, alice, bob. Passwords are secret.</p>
            <div class="sim-label">Query built by the server</div><pre class="qbox" id="q-sql"></pre>
            <div class="sim-label">Database response</div><div id="q-out"></div>`;
        const $ = (s) => root.querySelector(s);

        const val = (x, r) => {
            x = x.trim();
            if (/^'[\s\S]*'$/.test(x)) return x.slice(1, -1);
            if (/^\d+$/.test(x)) return x;
            if (Object.prototype.hasOwnProperty.call(r, x)) return String(r[x]);
            throw new Error(`Unknown column '${x}' in 'where clause'`);
        };
        const atom = (a, r) => {
            const m = a.trim().match(/^(.+?)\s*=\s*(.+)$/);
            if (!m) throw new Error(`You have an error in your SQL syntax near '${a.trim()}'`);
            return val(m[1], r) === val(m[2], r);
        };
        // Tiny WHERE evaluator: strips comments, then handles = / AND / OR.
        const exec = (q) => {
            let w = q.slice(q.indexOf('WHERE ') + 6).replace(/(--\s|--$|#)[\s\S]*$/, '').trim();
            if ((w.match(/'/g) || []).length % 2) return { error: `You have an error in your SQL syntax near '${w.slice(-14)}'` };
            try {
                return { rows: USERS.filter((r) => w.split(/\s+OR\s+/i).some((part) => part.split(/\s+AND\s+/i).every((a) => atom(a, r)))) };
            } catch (err) { return { error: err.message }; }
        };

        const go = () => {
            const u = $('#q-user').value, p = $('#q-pass').value, safe = $('#q-safe').checked;
            let res;
            if (safe) {
                $('#q-sql').innerHTML = `SELECT * FROM users WHERE username = <mark>?</mark> AND password = <mark>?</mark>\n-- bound values: ${esc(JSON.stringify([u, p]))}`;
                res = { rows: USERS.filter((r) => r.username === u && r.password === p) };
            } else {
                $('#q-sql').innerHTML = `SELECT * FROM users WHERE username = '<mark>${esc(u)}</mark>' AND password = '<mark>${esc(p)}</mark>'`;
                res = exec(`SELECT * FROM users WHERE username = '${u}' AND password = '${p}'`);
            }
            const outBox = $('#q-out');
            if (res.error) { outBox.innerHTML = `<div class="sim-msg bad">SQL error: ${esc(res.error)}</div>`; return; }
            if (!res.rows.length) { outBox.innerHTML = '<div class="sim-msg bad">0 rows returned. Login failed.</div>'; return; }
            outBox.innerHTML = `<table><tr><th>id</th><th>username</th><th>password</th><th>role</th></tr>` +
                res.rows.map((r) => `<tr><td>${r.id}</td><td>${esc(r.username)}</td><td>${esc(r.password)}</td><td>${esc(r.role)}</td></tr>`).join('') +
                `</table><div class="sim-msg ok">${res.rows.length} row(s) returned. The app logs you in as <b>${esc(res.rows[0].username)}</b>.</div>`;
        };
        $('#q-go').addEventListener('click', go);
        root.querySelectorAll('input').forEach((i) => i.addEventListener('keydown', (e) => { if (e.key === 'Enter') go(); }));
    };

    /* ---------- 3. Cross-site scripting ---------- */
    S.xss = (root) => {
        root.innerHTML = `
            <div class="sim-head"><strong>Guestbook (comment is echoed into the page)</strong>
                <label class="toggle"><input type="checkbox" id="x-safe"> Secure mode (htmlspecialchars)</label></div>
            <label class="muted" for="x-in">Your comment</label>
            <textarea id="x-in" rows="2" spellcheck="false" placeholder="Hello world"></textarea>
            <p><button class="btn" id="x-go" type="button">Post comment</button></p>
            <div class="sim-label">HTML the server sends (shown as text)</div><pre class="qbox" id="x-src"></pre>
            <div class="sim-label">What the browser does</div><div id="x-out"></div>`;
        const $ = (s) => root.querySelector(s);
        $('#x-go').addEventListener('click', () => {
            const v = $('#x-in').value, safe = $('#x-safe').checked;
            const html = safe ? esc(v) : v;
            $('#x-src').textContent = `<p class="comment">${html}</p>`;
            const out = $('#x-out');
            if (safe) {
                out.innerHTML = '<div class="sim-msg ok">The special characters were encoded, so the browser shows your text literally:</div><pre class="qbox" id="x-lit"></pre>';
                out.querySelector('#x-lit').textContent = v;
            } else if (/<script[\s>]/i.test(v) || /\son\w+\s*=/i.test(v) || /javascript:/i.test(v)) {
                const m = v.match(/alert\(([^)]*)\)/i);
                out.innerHTML = '<div class="sim-msg bad">The browser ran attacker-controlled JavaScript on this page:</div><div class="sim-fake-alert"><b>Simulated alert</b><span id="x-msg"></span></div>';
                out.querySelector('#x-msg').textContent = m ? m[1].replace(/^['"]|['"]$/g, '') : 'Attacker script executed';
            } else if (/<[a-z][^>]*>/i.test(v)) {
                out.innerHTML = '<div class="sim-msg bad">The browser treats this as real HTML tags and renders them. Try an event handler or a script tag.</div>';
            } else {
                out.innerHTML = '<div class="sim-msg ok">Plain text. Nothing special happens.</div>';
            }
        });
    };

    /* ---------- 4. Password brute force ---------- */
    S.bruteforce = (root) => {
        const PASS = 'sunshine7', LOCK_AFTER = 5;
        const LIST = ['123456', 'password', 'qwerty', 'letmein', 'welcome', 'iloveyou', 'admin123', 'monkey', 'sunshine7', 'dragon', 'football', 'abc123'];
        let fails = 0, locked = false;
        root.innerHTML = `
            <div class="sim-head"><strong>Target: login for user "admin"</strong>
                <label class="toggle"><input type="checkbox" id="b-lock"> Account lockout after ${LOCK_AFTER} failed attempts</label></div>
            <div class="sim-label">Wordlist (${LIST.length} common passwords)</div><pre class="qbox">${esc(LIST.join('  '))}</pre>
            <p><button class="btn" id="b-go" type="button">Run dictionary attack</button>
               <button class="btn btn-secondary" id="b-reset" type="button">Reset</button></p>
            <div id="b-out"></div>`;
        const $ = (s) => root.querySelector(s);
        const attempt = (pw) => {
            if (locked) return 'locked';
            if (pw === PASS) return 'success';
            fails++;
            if ($('#b-lock').checked && fails >= LOCK_AFTER) locked = true;
            return 'fail';
        };
        $('#b-go').addEventListener('click', () => {
            fails = 0; locked = false;
            const rows = []; let summary = '';
            for (let i = 0; i < LIST.length; i++) {
                const r = attempt(LIST[i]);
                rows.push(`<tr><td>${i + 1}</td><td>${esc(LIST[i])}</td><td class="${r === 'success' ? 'ok' : 'bad'}">${r === 'success' ? 'SUCCESS' : 'failed'}</td></tr>`);
                if (r === 'success') { summary = `<div class="sim-msg bad">Password found after ${i + 1} attempts: <b>${esc(LIST[i])}</b></div>`; break; }
                if (locked) { summary = `<div class="sim-msg ok">Account locked after ${fails} failed attempts. The attack is stopped.</div>`; break; }
            }
            if (!summary) summary = '<div class="sim-msg ok">Wordlist exhausted. Password not found.</div>';
            $('#b-out').innerHTML = `<table><tr><th>#</th><th>Password tried</th><th>Result</th></tr>${rows.join('')}</table>${summary}`;
        });
        $('#b-reset').addEventListener('click', () => { fails = 0; locked = false; $('#b-out').innerHTML = ''; });
    };

    /* ---------- 5. Directory traversal ---------- */
    S.dirtrav = (root) => {
        const BASE = '/var/www/files/';
        const F = {
            '/var/www/files/welcome.txt': 'Welcome to the CyberQuest document portal.',
            '/var/www/files/report.txt': 'Q3 report: all systems normal.',
            '/var/www/config.php': "<?php\n// Database settings\n$db_host = 'localhost';\n// flag: CQ{dot_dot_slash_escape}",
            '/etc/hostname': 'cyberquest-lab',
            '/etc/passwd': 'root:x:0:0:root:/root:/bin/bash\nwww-data:x:33:33:www-data:/var/www:/usr/sbin/nologin',
        };
        root.innerHTML = `
            <div class="sim-head"><strong>Document viewer</strong>
                <label class="toggle"><input type="checkbox" id="d-safe"> Secure mode (realpath check)</label></div>
            <div class="sim-label">Server code</div><pre class="qbox" id="d-code"></pre>
            <div class="sim-label">Request</div>
            <div class="sim-row"><label>http://cyberquest.local/view.php?file=<input id="d-in" autocomplete="off" spellcheck="false" placeholder="report.txt"></label>
                <button class="btn" id="d-go" type="button">Open</button></div>
            <p class="muted">Files in the folder: welcome.txt, report.txt</p>
            <div id="d-out"></div>`;
        const $ = (s) => root.querySelector(s);
        const norm = (p) => { const o = []; for (const s of p.split('/')) { if (!s || s === '.') continue; if (s === '..') o.pop(); else o.push(s); } return '/' + o.join('/'); };
        const code = () => {
            $('#d-code').textContent = $('#d-safe').checked
                ? "$path = realpath('/var/www/files/' . $_GET['file']);\nif ($path === false || strpos($path, '/var/www/files/') !== 0) { http_response_code(403); exit; }\nreadfile($path);"
                : "readfile('/var/www/files/' . $_GET['file']);";
        };
        $('#d-safe').addEventListener('change', code); code();
        const go = () => {
            let f = $('#d-in').value;
            try { f = decodeURIComponent(f); } catch (e) { /* keep raw value */ }
            const path = norm(BASE + f), out = $('#d-out');
            if ($('#d-safe').checked && !path.startsWith(BASE)) {
                out.innerHTML = `<div class="sim-msg ok">403 Forbidden. <code>${esc(path)}</code> is outside the allowed folder.</div>`; return;
            }
            out.innerHTML = `<p class="muted">Server opens: <code>${esc(path)}</code></p>` +
                (Object.prototype.hasOwnProperty.call(F, path) ? `<pre class="qbox">${esc(F[path])}</pre>` : '<div class="sim-msg bad">404 Not Found</div>');
        };
        $('#d-go').addEventListener('click', go);
        $('#d-in').addEventListener('keydown', (e) => { if (e.key === 'Enter') go(); });
    };

    /* ---------- 6. CSRF ---------- */
    S.csrf = (root) => {
        const start = () => ({ you: 1000, attacker: 0, log: [] });
        let st = start();
        root.innerHTML = `
            <div class="sim-head"><strong>You are logged in to bank.example in another tab</strong>
                <label class="toggle"><input type="checkbox" id="c-prot"> CSRF token protection</label></div>
            <div class="sim-grid">
                <div class="sim-panel"><h4>bank.example (your session cookie is active)</h4>
                    <table><tr><th>Account</th><th>Balance</th></tr>
                    <tr><td>You</td><td id="c-you"></td></tr><tr><td>Attacker</td><td id="c-att"></td></tr></table></div>
                <div class="sim-panel"><h4>evil.example (a page you were tricked into opening)</h4>
                    <p class="muted">"Click to claim your free prize!"</p>
                    <p><button class="btn" id="c-go" type="button">Claim prize</button></p>
                    <pre class="qbox">&lt;form action="http://bank.example/transfer" method="POST"&gt;
  &lt;input type="hidden" name="to" value="attacker"&gt;
  &lt;input type="hidden" name="amount" value="500"&gt;
&lt;/form&gt;
&lt;script&gt;document.forms[0].submit()&lt;/script&gt;</pre></div>
            </div>
            <div class="sim-label">Requests received by bank.example</div><pre class="qbox" id="c-log"></pre>
            <p><button class="btn btn-secondary btn-sm" id="c-reset" type="button">Reset</button></p>`;
        const $ = (s) => root.querySelector(s);
        const paint = () => {
            $('#c-you').textContent = '$' + st.you; $('#c-att').textContent = '$' + st.attacker;
            $('#c-log').textContent = st.log.join('\n') || 'No requests yet.';
        };
        $('#c-go').addEventListener('click', () => {
            const prot = $('#c-prot').checked;
            const line = 'POST /transfer to=attacker amount=500 | cookie: session=valid | csrf_token: missing';
            if (prot) st.log.push(line + ' -> 403 Forbidden (rejected)');
            else { const amt = Math.min(500, st.you); st.you -= amt; st.attacker += amt; st.log.push(line + ' -> 200 OK (transfer executed)'); }
            paint();
        });
        $('#c-reset').addEventListener('click', () => { st = start(); paint(); });
        paint();
    };
})();

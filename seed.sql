-- CyberQuest seed data: 6 modules (1 lesson each) + 3 'coming soon' modules.
-- Answers are stored as SHA-256(lowercase(trim(answer))). Run AFTER cyberquest_db.sql.
USE cyberquest_db;
SET NAMES utf8mb4;

-- Module 1: Linux Basics
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('Linux Basics', 'linux-basics', 'Move around a Linux system, read files and find hidden ones. The foundation for every other module.', 'Easy', 20, 1, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO lessons (module_id, title, content, sim_key, order_no) VALUES (@m, 'Getting around the Linux command line', '<h2>Why Linux?</h2>
<p>Most servers, and most security tools, run on Linux. Almost everything is done in a terminal by typing commands. Files are arranged in one tree that starts at <code>/</code> (the root).</p>
<h3>Commands you need first</h3>
<table><tr><th>Command</th><th>What it does</th></tr>
<tr><td><code>pwd</code></td><td>Print the directory you are in</td></tr>
<tr><td><code>ls</code></td><td>List files (<code>ls -a</code> also shows hidden ones)</td></tr>
<tr><td><code>cd folder</code></td><td>Change directory (<code>cd ..</code> goes up, <code>cd ~</code> goes home)</td></tr>
<tr><td><code>cat file</code></td><td>Print a file''s contents</td></tr>
<tr><td><code>grep word file</code></td><td>Show only lines containing a word</td></tr>
<tr><td><code>whoami</code>, <code>id</code></td><td>Show who you are logged in as</td></tr></table>
<h3>Hidden files</h3>
<p>Any file whose name starts with a dot (for example <code>.bashrc</code>) is hidden from a plain <code>ls</code>. Hidden does not mean secret: <code>ls -a</code> shows them. Attackers and defenders both check them first.</p>
<p><b>Try it:</b> use the terminal below. Start with <code>help</code>, then explore <code>/home/student</code> and <code>/etc</code>.</p>', 'linux', 1);
SET @l = LAST_INSERT_ID();
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which command prints the directory you are currently in?', '["ls", "pwd", "cd", "whoami"]', 'The name stands for ''print working directory''.', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 10, 1);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which option makes ls show hidden files?', '["-l", "-h", "-a", "-r"]', 'Think ''all''.', 'd4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35', 10, 2);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'There is a hidden file in the student home directory. What flag does it contain?', NULL, 'Run ls -a in ~ and cat the dotfile. The flag looks like CQ{...}.', 'b0ed9b66ac33f4bc0768380926f2396db48110ca64aefe8a3c78161455be15d7', 20, 3);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'What hostname is stored in /etc/hostname?', NULL, 'Use cat /etc/hostname.', '0d8bdf2653ec461084b57cc04eed97b194858c445e9da917278434be892af969', 15, 4);

-- Module 2: SQL Injection
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('SQL Injection', 'sql-injection', 'See how unsafe database queries let attackers bypass logins and read data, and how prepared statements stop them.', 'Easy', 25, 2, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO lessons (module_id, title, content, sim_key, order_no) VALUES (@m, 'SQL injection: breaking out of the query', '<h2>What is SQL injection?</h2>
<p>Web apps often build SQL by pasting user input into a string. If the input is not handled safely, the user can change the meaning of the query itself.</p>
<pre><code>$sql = "SELECT * FROM users
        WHERE username = ''$user'' AND password = ''$pass''";</code></pre>
<p>The input sits inside single quotes. A single quote in the input closes the string early, and whatever follows is read as SQL code.</p>
<h3>Login bypass</h3>
<p>Entering <code>admin''-- </code> (note the space after the dashes) as the username produces:</p>
<pre><code>... WHERE username = ''admin''-- '' AND password = ''''</code></pre>
<p>In MySQL, <code>-- </code> starts a comment, so the password check is ignored. Another classic is the always-true condition <code>'' OR ''1''=''1</code>.</p>
<h3>Prevention</h3>
<p>Use <b>prepared statements</b>: the SQL and the data are sent separately, so input can never become code.</p>
<pre><code>$stmt = $pdo-&gt;prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt-&gt;execute([$user, $pass]);</code></pre>
<p><b>Try it:</b> use the simulator. Watch the query change as you type, then switch on secure mode and try again.</p>', 'sqli', 1);
SET @l = LAST_INSERT_ID();
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which character lets an attacker break out of a quoted string in the query?', '["Single quote ('')", "Semicolon (;)", "Slash (/)", "Ampersand (&)"]', 'Look at what surrounds the input in the vulnerable query.', '5feceb66ffc86f38d952786c6d696c79c2dbc239dd4e91b46729d73a27fb57e9', 10, 1);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'What is the best defense against SQL injection?', '["Hiding error messages only", "Prepared statements", "Blocking the word SELECT", "Using longer passwords"]', 'SQL and data are sent separately.', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 10, 2);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'Log in as admin without the password using the simulator. What password value appears in the returned row?', NULL, 'Try admin''-- (with a trailing space) as the username.', '5cb7b35eb7ae9bbd505baa5cde3fb64c1e9a5e8862072013989c0a557ab9eaa2', 20, 3);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'Which two characters start a MySQL comment (followed by a space)?', NULL, 'It is the sequence used in the lesson''s admin example.', 'd8156bae0c4243d3742fc4e9774d8aceabe0410249d720c855f98afc88ff846c', 10, 4);

-- Module 3: Cross-Site Scripting (XSS)
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('Cross-Site Scripting (XSS)', 'xss', 'Learn how unescaped output lets attackers run JavaScript in other users'' browsers, and how to encode it.', 'Easy', 20, 3, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO lessons (module_id, title, content, sim_key, order_no) VALUES (@m, 'XSS: when output is not escaped', '<h2>What is XSS?</h2>
<p>Cross-site scripting happens when an app puts user input into a page without escaping it. The browser cannot tell the developer''s HTML from the attacker''s, so it runs the attacker''s JavaScript with the victim''s session.</p>
<pre><code>echo "&lt;p class=''comment''&gt;" . $_POST[''comment''] . "&lt;/p&gt;";</code></pre>
<p>If the comment is <code>&lt;script&gt;alert(''XSS'')&lt;/script&gt;</code>, the script runs for everyone who views the page. Real attacks steal cookies or act as the user.</p>
<h3>Prevention</h3>
<p>Encode output for the context it is placed in. In PHP: <code>htmlspecialchars($comment, ENT_QUOTES, ''UTF-8'')</code>. This turns <code>&lt;</code> into <code>&amp;lt;</code> so it is shown, not executed. A Content-Security-Policy header adds a second layer.</p>
<p><b>Try it:</b> post a comment in the simulator. The script never really runs; it only shows what a browser would do.</p>', 'xss', 1);
SET @l = LAST_INSERT_ID();
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'XSS happens when an application...', '["Sends user input back to the browser without escaping it", "Stores passwords in plain text", "Uses HTTP instead of HTTPS", "Has a weak password policy"]', 'The problem is output, not storage.', '5feceb66ffc86f38d952786c6d696c79c2dbc239dd4e91b46729d73a27fb57e9', 10, 1);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which PHP function is used to escape output for HTML?', '["md5()", "htmlspecialchars()", "strlen()", "trim()"]', 'The name mentions special characters.', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 10, 2);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'In secure mode, how is the < character written in the page source? (HTML entity)', NULL, 'Turn on secure mode and look at the HTML the server sends.', '3885ff5ae3386f58e4f1f1363d4863d8c9a5f280fe15e346ad7c78a09529637a', 15, 3);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'Which HTML tag is most commonly used to inject JavaScript? (tag name only)', NULL, 'It is the tag the lesson example uses.', '21a0270b7f66a1e4c25933f13a1e5a1bbb4757578072930c8189131f9c6aaae1', 10, 4);

-- Module 4: Password Brute Force
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('Password Brute Force', 'password-brute-force', 'Watch a dictionary attack guess passwords, and see why lockouts and strong passwords matter.', 'Easy', 15, 4, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO lessons (module_id, title, content, sim_key, order_no) VALUES (@m, 'Guessing passwords at scale', '<h2>Brute force and dictionary attacks</h2>
<p>A <b>brute-force</b> attack tries many passwords until one works. A <b>dictionary attack</b> tries a list of common passwords first (123456, password, qwerty), because people reuse weak choices.</p>
<h3>What makes it work</h3>
<ul><li>Weak or common passwords</li><li>No limit on login attempts</li><li>No monitoring of repeated failures</li></ul>
<h3>Defenses</h3>
<ul><li><b>Account lockout or rate limiting</b> after a few failed attempts</li>
<li>Long, unique passwords (a passphrase beats a short complex one)</li>
<li>Multi-factor authentication</li><li>Alerts on bursts of failed logins</li></ul>
<p><b>Try it:</b> run the attack with lockout off, then turn lockout on and run it again.</p>', 'bruteforce', 1);
SET @l = LAST_INSERT_ID();
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which defense most directly slows down automated password guessing?', '["Account lockout or rate limiting", "A larger font on the login page", "Hiding the username field", "Using HTTP instead of HTTPS"]', 'It limits how many guesses an attacker gets.', '5feceb66ffc86f38d952786c6d696c79c2dbc239dd4e91b46729d73a27fb57e9', 10, 1);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which password would a dictionary attack most likely crack first?', '["password1", "Xk9#mQ2$vL7@", "correct-horse-battery-staple-92", "T7!pR4wz@9Lq"]', 'Dictionary attacks start with common words.', '5feceb66ffc86f38d952786c6d696c79c2dbc239dd4e91b46729d73a27fb57e9', 10, 2);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'Run the attack with lockout OFF. What is the admin''s password?', NULL, 'Turn the lockout toggle off and read the result line.', 'c0e455dae172745191d9d3c1a472182baa04f054da36cc8d78ba738c98503727', 20, 3);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'With lockout ON, after how many failed attempts is the account locked? (number)', NULL, 'It is shown on the toggle label.', 'ef2d127de37b942baad06145e54b0c619a1f22327b2ebbcfbec78f5564afe39d', 10, 4);

-- Module 5: Directory Traversal
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('Directory Traversal', 'directory-traversal', 'Use ../ sequences to escape a web folder and read files you should not see, then fix the code.', 'Medium', 20, 5, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO lessons (module_id, title, content, sim_key, order_no) VALUES (@m, 'Directory traversal: escaping the web folder', '<h2>What is directory traversal?</h2>
<p>Some pages load a file named in the URL, such as <code>view.php?file=report.txt</code>. If the server just joins that name onto a folder path, the user can add <code>../</code> to climb out of it.</p>
<pre><code>readfile(''/var/www/files/'' . $_GET[''file'']);</code></pre>
<p>With <code>file=../../../etc/passwd</code> the path becomes <code>/var/www/files/../../../etc/passwd</code>, which the system resolves to <code>/etc/passwd</code>.</p>
<h3>Prevention</h3>
<ul><li>Resolve the final path with <code>realpath()</code> and check that it still starts with the allowed folder</li>
<li>Allow only known file names (a whitelist), or use IDs instead of file names</li>
<li>Run the web server with the least privileges</li></ul>
<p><b>Try it:</b> request files in the simulator, then try <code>../</code> sequences. Turn on secure mode to see the fix.</p>', 'dirtrav', 1);
SET @l = LAST_INSERT_ID();
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which sequence moves up one directory?', '["./", "../", "//", "~/"]', 'Two dots mean the parent folder.', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 10, 1);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which is the best defense against directory traversal?', '["Strip spaces from file names", "Resolve the real path and check it stays inside the allowed folder", "Hide file extensions", "Use longer file names"]', 'Check where the path really ends up.', '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b', 10, 2);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'Read /var/www/config.php through the viewer. What flag does it contain?', NULL, 'Files live in /var/www/files/. Go up one level with ../.', 'ac7e9e4800acfe55c0b602bdd890d08a4e7eadd355333bbb5267c45f5b7fb0d9', 20, 3);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'How many ../ are needed to reach /etc/passwd from /var/www/files/? (number)', NULL, 'Count the levels: files, www, var.', '4e07408562bedb8b60ce05c1decfe3ad16b72230967de01f640b7e4729b49fce', 15, 4);

-- Module 6: CSRF
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('CSRF', 'csrf', 'See how a malicious page can make your browser send requests to a site you are logged in to, and how tokens stop it.', 'Medium', 20, 6, 1);
SET @m = LAST_INSERT_ID();
INSERT INTO lessons (module_id, title, content, sim_key, order_no) VALUES (@m, 'CSRF: forged requests from another site', '<h2>What is CSRF?</h2>
<p>Cross-Site Request Forgery tricks a logged-in user''s browser into sending a request they did not intend. Browsers attach cookies automatically, so the target site thinks the request is genuine.</p>
<h3>Example</h3>
<p>You are logged in to a bank. You open a malicious page that silently submits a form to <code>bank.example/transfer</code>. Your session cookie goes along, and the transfer happens.</p>
<h3>Prevention</h3>
<ul><li><b>Anti-CSRF tokens</b>: a random value in every form that the server checks. An attacker''s page cannot read it.</li>
<li>SameSite cookies (<code>Lax</code> or <code>Strict</code>)</li>
<li>Never change state with GET requests</li></ul>
<p>CyberQuest itself uses a token on its logout and enroll forms.</p>
<p><b>Try it:</b> click the prize button with protection off, then on.</p>', 'csrf', 1);
SET @l = LAST_INSERT_ID();
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'What does a CSRF attack abuse?', '["The browser automatically sending the victim''s cookies", "Weak encryption", "SQL queries", "Open network ports"]', 'Think about what the browser attaches to every request.', '5feceb66ffc86f38d952786c6d696c79c2dbc239dd4e91b46729d73a27fb57e9', 10, 1);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'mcq', 'Which defense is most effective against CSRF?', '["Server-checked anti-CSRF tokens", "Hiding the form", "Using GET instead of POST", "Longer cookie names"]', 'The attacker''s page cannot know this value.', '5feceb66ffc86f38d952786c6d696c79c2dbc239dd4e91b46729d73a27fb57e9', 10, 2);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'With protection OFF, how much money does the attacker have after one click? (number)', NULL, 'Read the Attacker balance in the simulator.', '0604cd3138feed202ef293e062da2f4720f77a05d25ee036a7a01c9cfcdd1f0a', 15, 3);
INSERT INTO challenges (lesson_id, challenge_type, question, options, hint, answer_hash, points, order_no) VALUES (@l, 'text', 'What HTTP status code does the bank return when the CSRF token is missing? (number)', NULL, 'Turn protection on and click again. Read the log.', 'd26eae87829adde551bf4b852f9da6b8c3c2db9b65b8b68870632a2db5f53e00', 15, 4);

INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('Command Injection', 'command-injection', 'Run unintended OS commands through unsafe input.', 'Medium', 20, 8, 0);
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('File Upload Attacks', 'file-upload-attacks', 'Abuse weak upload checks to plant malicious files.', 'Medium', 20, 9, 0);
INSERT INTO modules (title, slug, description, difficulty, estimated_minutes, order_no, is_published) VALUES ('Network Scanning Basics', 'network-scanning-basics', 'Discover hosts and open ports the safe way.', 'Easy', 20, 10, 0);

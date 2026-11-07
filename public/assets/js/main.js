// CyberQuest: challenge submission, feedback effects and simulation start-up.
(function () {
    const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function toast(text) {
        const t = document.createElement('div');
        t.className = 'xp-toast';
        t.textContent = text;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 1900);
    }

    // Small canvas confetti burst for completing a module.
    function confetti() {
        if (reduced) return;
        const c = document.createElement('canvas');
        c.className = 'confetti';
        c.width = innerWidth; c.height = innerHeight;
        document.body.appendChild(c);
        const ctx = c.getContext('2d');
        const colors = ['#5b4bff', '#ff7a45', '#ffc233', '#ff4f81', '#2d9cff', '#37d392'];
        const bits = Array.from({ length: 120 }, () => ({
            x: c.width / 2, y: c.height * 0.4, vx: (Math.random() - 0.5) * 16, vy: -Math.random() * 14 - 4,
            s: 5 + Math.random() * 6, r: Math.random() * 6, vr: (Math.random() - 0.5) * 0.4, c: colors[(Math.random() * colors.length) | 0],
        }));
        let frames = 0;
        (function tick() {
            ctx.clearRect(0, 0, c.width, c.height);
            bits.forEach((b) => {
                b.x += b.vx; b.y += b.vy; b.vy += 0.35; b.vx *= 0.99; b.r += b.vr;
                ctx.save(); ctx.translate(b.x, b.y); ctx.rotate(b.r); ctx.fillStyle = b.c;
                ctx.fillRect(-b.s / 2, -b.s / 2, b.s, b.s * 0.6); ctx.restore();
            });
            if (++frames < 130) requestAnimationFrame(tick); else c.remove();
        })();
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Start any simulation placeholders on the page.
        document.querySelectorAll('.sim[data-sim]').forEach((el) => {
            const start = window.CQSims && window.CQSims[el.dataset.sim];
            if (start) start(el);
        });

        // Submit challenge answers without reloading the page.
        document.querySelectorAll('form.challenge-form').forEach((form) => {
            form.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const box = form.querySelector('.feedback');
                const btn = form.querySelector('button[type=submit]');
                btn.disabled = true;
                try {
                    const res = await fetch('submit_flag.php', { method: 'POST', body: new FormData(form) });
                    const data = await res.json();
                    box.textContent = data.message || 'Something went wrong.';
                    box.className = 'feedback ' + (data.correct ? 'ok' : 'bad');
                    if (data.correct) {
                        form.classList.add('is-solved');
                        btn.textContent = 'Solved ✓';
                        form.querySelectorAll('input').forEach((i) => { if (i.type !== 'hidden') i.disabled = true; });
                        if (data.points) toast('+' + data.points + ' XP');
                        if (data.completed) { confetti(); setTimeout(() => toast('Module complete! 🎉'), 600); }
                    } else {
                        form.classList.remove('shake'); void form.offsetWidth; form.classList.add('shake');
                    }
                    if (data.progress) {
                        const p = document.getElementById('module-progress');
                        if (p) p.textContent = data.progress.solved_challenges + '/' + data.progress.total_challenges + ' module challenges solved';
                    }
                } catch (err) {
                    box.textContent = 'Network error. Try again.';
                    box.className = 'feedback bad';
                }
                if (!form.classList.contains('is-solved')) btn.disabled = false;
            });
        });
    });
})();

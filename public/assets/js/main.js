// CyberQuest: challenge submission and simulation start-up.
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

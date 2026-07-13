/* LMS MVP  |  assets/js/main.js */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Mobile sidebar toggle ─ */
  const sidebar    = document.querySelector('.sidebar');
  const toggleBtn  = document.getElementById('sidebar-toggle');
  const overlay    = document.getElementById('sidebar-overlay');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('visible');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('visible');
    });
  }

  /* ── Active nav link ─ */
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
      link.classList.add('active');
    }
  });

  /* ── Confirm dialogs ─*/
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      const msg = el.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  /* ── Auto-dismiss alerts ─ */
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(a => {
    setTimeout(() => {
      a.style.transition = 'opacity .5s';
      a.style.opacity = '0';
      setTimeout(() => a.remove(), 500);
    }, 4500);
  });

  /* ── File upload label update ─*/
  document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', () => {
      const label = document.querySelector(`label[for="${input.id}"] .file-name`);
      if (label) label.textContent = input.files[0]?.name || 'No file chosen';
    });
  });

  /* ── Grade score live percentage ─ */
  const scoreInput = document.getElementById('score');
  const pointsMax  = document.getElementById('points_max');
  const pctDisplay = document.getElementById('score_pct');
  if (scoreInput && pointsMax && pctDisplay) {
    const update = () => {
      const pct = Math.round((+scoreInput.value / +pointsMax.value) * 100);
      pctDisplay.textContent = isNaN(pct) ? '' : `(${pct}%)`;
    };
    scoreInput.addEventListener('input', update);
  }

});
// assets/js/main.js — Deliverance Church CIMS

// ── Modal helpers ─────────────────────────────────────────
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Close modal on backdrop click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('open');
  }
});

// Close on Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
  }
});

// ── Auto-dismiss alerts ───────────────────────────────────
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .5s'; setTimeout(() => el.remove(), 500); }, 3500);
});

// ── Confirm delete ────────────────────────────────────────
function confirmDelete(form) {
  return confirm('Are you sure you want to delete this record? This cannot be undone.');
}

// ── Format numbers (UGX) ──────────────────────────────────
function fmtUGX(n) {
  return 'UGX ' + Number(n).toLocaleString('en-UG');
}

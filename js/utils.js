// ============================================================
// CIMS - Utility Functions
// ============================================================

const Utils = (() => {
  function fmt(n) {
    return new Intl.NumberFormat('en-UG').format(Math.round(n));
  }

  function fmtCurrency(n) {
    return 'UGX ' + fmt(n);
  }

  function fmtDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function today() {
    return new Date().toISOString().split('T')[0];
  }

  function initials(name) {
    return (name || '').split(' ').filter(Boolean).map(w => w[0]).join('').slice(0, 2).toUpperCase();
  }

  function memberName(members, id) {
    const m = members.find(x => x.id === id);
    return m ? `${m.fname} ${m.lname}` : 'Unknown';
  }

  function eventName(events, id) {
    const e = events.find(x => x.id === id);
    return e ? e.name : 'Unknown';
  }

  const AVATAR_COLORS = ['#1D9E75', '#185FA5', '#BA7517', '#534AB7', '#993C1D', '#0F6E56'];
  const AVATAR_BG = ['#E1F5EE', '#E6F1FB', '#FAEEDA', '#EEEDFE', '#FAECE7', '#085041'];

  function avatarStyle(index) {
    const i = index % AVATAR_COLORS.length;
    return `background:${AVATAR_BG[i]};color:${AVATAR_COLORS[i]}`;
  }

  function badge(text, type) {
    const classes = {
      success: 'badge-green', info: 'badge-blue', warning: 'badge-amber',
      danger: 'badge-red', muted: 'badge-gray', purple: 'badge-purple',
    };
    return `<span class="badge ${classes[type] || 'badge-gray'}">${text}</span>`;
  }

  function statusBadge(status) {
    const map = {
      'Active': 'success', 'Inactive': 'muted',
      'Present': 'success', 'Absent': 'danger', 'Excused': 'warning',
      'Upcoming': 'info', 'Past': 'muted', 'Completed': 'success',
      'Tithe': 'success', 'Offering': 'info', 'Donation': 'warning',
    };
    return badge(status, map[status] || 'muted');
  }

  function roleBadge(role) {
    const map = {
      'System Administrator': 'purple',
      'Finance Officer': 'warning',
      'Church Leader': 'success',
      'Church Member': 'info',
      'Event Coordinator': 'muted',
    };
    return badge(role, map[role] || 'muted');
  }

  function toast(message, type = 'success') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<span class="toast-icon">${type === 'success' ? '✓' : type === 'danger' ? '✕' : 'ℹ'}</span><span>${message}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add('show'), 10);
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3500);
  }

  function confirm(message, onYes, onNo) {
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
      <div class="confirm-box">
        <div class="confirm-icon">⚠</div>
        <p class="confirm-msg">${message}</p>
        <div class="confirm-actions">
          <button class="btn" id="confirmNo">Cancel</button>
          <button class="btn btn-danger" id="confirmYes">Confirm</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    setTimeout(() => overlay.classList.add('open'), 10);
    const close = () => { overlay.classList.remove('open'); setTimeout(() => overlay.remove(), 200); };
    overlay.querySelector('#confirmYes').onclick = () => { close(); if (onYes) onYes(); };
    overlay.querySelector('#confirmNo').onclick = () => { close(); if (onNo) onNo(); };
  }

  function debounce(fn, ms = 250) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), ms); };
  }

  function getMonthName(n) {
    return ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][n] || '';
  }

  function monthLabel(dateStr) {
    if (!dateStr) return '';
    const [y, m] = dateStr.split('-');
    return `${getMonthName(parseInt(m) - 1)} ${y}`;
  }

  function isUpcoming(dateStr) {
    return dateStr >= today();
  }

  function paginate(arr, page, perPage) {
    const start = (page - 1) * perPage;
    return { items: arr.slice(start, start + perPage), total: arr.length, pages: Math.ceil(arr.length / perPage) };
  }

  function paginationControls(currentPage, totalPages, onPageChange) {
    if (totalPages <= 1) return '';
    let html = '<div class="pagination">';
    html += `<button class="pg-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="(${onPageChange})(${currentPage - 1})">‹</button>`;
    for (let i = 1; i <= totalPages; i++) {
      html += `<button class="pg-btn ${i === currentPage ? 'active' : ''}" onclick="(${onPageChange})(${i})">${i}</button>`;
    }
    html += `<button class="pg-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="(${onPageChange})(${currentPage + 1})">›</button>`;
    html += '</div>';
    return html;
  }

  function exportCSV(data, filename) {
    if (!data.length) return toast('No data to export.', 'info');
    const headers = Object.keys(data[0]);
    const rows = data.map(r => headers.map(h => `"${(r[h] || '').toString().replace(/"/g, '""')}"`).join(','));
    const csv = [headers.join(','), ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename + '.csv'; a.click();
    URL.revokeObjectURL(url);
    toast('Export complete!', 'success');
  }

  function printSection(html, title) {
    const w = window.open('', '_blank');
    w.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>body{font-family:Georgia,serif;padding:24px;color:#222}
      h1{font-size:18px;margin-bottom:4px}p{font-size:12px;color:#666;margin:0 0 16px}
      table{width:100%;border-collapse:collapse;font-size:12px}
      th,td{padding:8px;border:1px solid #ddd;text-align:left}th{background:#f5f5f5}
      .header{text-align:center;margin-bottom:24px;border-bottom:2px solid #1D9E75;padding-bottom:12px}
      @media print{button{display:none}}</style></head><body>
      <div class="header"><h1>✝ Deliverance Church Nagongera</h1><h2 style="font-size:14px;margin:4px 0">${title}</h2>
      <p>Generated: ${new Date().toLocaleString('en-GB')}</p></div>
      ${html}<br><button onclick="window.print()" style="padding:8px 16px;background:#1D9E75;color:#fff;border:none;border-radius:4px;cursor:pointer">Print</button>
      </body></html>`);
    w.document.close();
  }

  return { fmt, fmtCurrency, fmtDate, today, initials, memberName, eventName, avatarStyle, badge, statusBadge, roleBadge, toast, confirm, debounce, getMonthName, monthLabel, isUpcoming, paginate, paginationControls, exportCSV, printSection };
})();

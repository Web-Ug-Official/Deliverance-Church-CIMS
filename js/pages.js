// ============================================================
// CIMS - Page Modules
// ============================================================

const Pages = {};

// ============================================================
// DASHBOARD
// ============================================================
Pages.Dashboard = (() => {
  function render() {
    const members = CIMS_DB.getAll('members');
    const transactions = CIMS_DB.getAll('transactions');
    const events = CIMS_DB.getAll('events');
    const attendance = CIMS_DB.getAll('attendance');

    const activeMembers = members.filter(m => m.status === 'Active').length;
    const today = Utils.today();
    const thisMonth = today.slice(0, 7);
    const monthlyTotal = transactions.filter(t => t.date.startsWith(thisMonth)).reduce((s, t) => s + t.amount, 0);
    const upcomingEvents = events.filter(e => e.date >= today).length;
    const presentCount = attendance.filter(a => a.status === 'Present').length;
    const attendRate = attendance.length ? Math.round((presentCount / attendance.length) * 100) : 0;

    document.getElementById('d-members').textContent = activeMembers;
    document.getElementById('d-tithes').textContent = Utils.fmtCurrency(monthlyTotal);
    document.getElementById('d-events').textContent = upcomingEvents;
    document.getElementById('d-attend').textContent = attendRate + '%';

    renderActivity(transactions, members, events);
    renderFinChart(transactions);
    renderRecentMembers(members);
  }

  function renderActivity(transactions, members, events) {
    const activities = [];
    [...members].sort((a, b) => b.regDate.localeCompare(a.regDate)).slice(0, 2).forEach(m => {
      activities.push({ dot: 'green', text: `${m.fname} ${m.lname} registered as new member`, date: m.regDate });
    });
    [...transactions].sort((a, b) => b.date.localeCompare(a.date)).slice(0, 2).forEach(t => {
      const mname = Utils.memberName(members, t.memberId);
      activities.push({ dot: 'blue', text: `${t.type} of ${Utils.fmtCurrency(t.amount)} recorded for ${mname}`, date: t.date });
    });
    [...events].sort((a, b) => b.date.localeCompare(a.date)).slice(0, 2).forEach(e => {
      activities.push({ dot: 'amber', text: `Event "${e.name}" scheduled for ${Utils.fmtDate(e.date)}`, date: e.date });
    });
    activities.sort((a, b) => b.date.localeCompare(a.date));
    document.getElementById('activityList').innerHTML = activities.slice(0, 6).map(a => `
      <div class="activity-item">
        <div class="activity-dot dot-${a.dot}"></div>
        <div><div class="activity-text">${a.text}</div>
        <div class="activity-time">${Utils.fmtDate(a.date)}</div></div>
      </div>`).join('') || '<div class="text-muted text-sm" style="padding:10px 0">No recent activity</div>';
  }

  function renderFinChart(transactions) {
    const monthly = {};
    transactions.forEach(t => {
      const key = t.date.slice(0, 7);
      monthly[key] = (monthly[key] || 0) + t.amount;
    });
    const months = Object.keys(monthly).sort().slice(-5);
    const max = Math.max(...months.map(m => monthly[m]), 1);
    document.getElementById('finChart').innerHTML = months.map(m => `
      <div class="chart-row">
        <div class="chart-row-label"><strong>${Utils.monthLabel(m + '-01')}</strong><span>${Utils.fmtCurrency(monthly[m])}</span></div>
        <div class="progress-bar"><div class="progress-fill fill-green" style="width:${Math.round(monthly[m]/max*100)}%"></div></div>
      </div>`).join('') || '<div class="text-muted text-sm">No financial data</div>';
  }

  function renderRecentMembers(members) {
    const recent = [...members].sort((a, b) => b.regDate.localeCompare(a.regDate)).slice(0, 5);
    document.getElementById('dashRecentMembersTable').innerHTML = recent.map((m, i) => `
      <tr>
        <td><div class="member-cell">
          <div class="avatar avatar-md" style="${Utils.avatarStyle(i)}">${Utils.initials(m.fname + ' ' + m.lname)}</div>
          <div class="member-cell-info"><p>${m.fname} ${m.lname}</p><span>${m.gender}</span></div>
        </div></td>
        <td>${Utils.fmtDate(m.regDate)}</td>
        <td>${Utils.statusBadge(m.status)}</td>
      </tr>`).join('') || '<tr><td colspan="3" class="table-empty">No members yet</td></tr>';
  }

  return { render };
})();

// ============================================================
// MEMBERS
// ============================================================
Pages.Members = (() => {
  let filteredMembers = [];
  let currentPage = 1;
  const PER_PAGE = 10;

  function render(searchVal = '', statusFilter = 'all') {
    const members = CIMS_DB.getAll('members');
    filteredMembers = members.filter(m => {
      const q = searchVal.toLowerCase();
      const matchSearch = !q || `${m.fname} ${m.lname} ${m.phone} ${m.email} ${m.address}`.toLowerCase().includes(q);
      const matchStatus = statusFilter === 'all' || m.status === statusFilter;
      return matchSearch && matchStatus;
    });
    renderTable();
  }

  function renderTable() {
    const { items, total, pages } = Utils.paginate(filteredMembers, currentPage, PER_PAGE);
    const isAdmin = App.isAdmin();

    document.getElementById('membersTable').innerHTML = items.map((m, i) => `
      <tr>
        <td><div class="member-cell">
          <div class="avatar avatar-md" style="${Utils.avatarStyle(i)}">${Utils.initials(m.fname + ' ' + m.lname)}</div>
          <div class="member-cell-info"><p>${m.fname} ${m.lname}</p><span>${m.email}</span></div>
        </div></td>
        <td>${m.phone}</td>
        <td>${m.gender}</td>
        <td>${Utils.fmtDate(m.regDate)}</td>
        <td>${Utils.statusBadge(m.status)}</td>
        <td>
          <div class="flex gap-6">
            <button class="btn btn-xs" onclick="Pages.Members.openEdit(${m.id})">Edit</button>
            ${isAdmin ? `<button class="btn btn-xs btn-danger" onclick="Pages.Members.deleteMember(${m.id})">Remove</button>` : ''}
          </div>
        </td>
      </tr>`).join('') || '<tr><td colspan="6" class="table-empty">No members found</td></tr>';

    document.getElementById('membersPagination').innerHTML = Utils.paginationControls(currentPage, pages,
      `function(p){Pages.Members.setPage(p)}`);
    document.getElementById('membersCount').textContent = `${total} member${total !== 1 ? 's' : ''}`;
  }

  function setPage(p) { currentPage = p; renderTable(); }

  function openAdd() {
    document.getElementById('memberModalTitle').textContent = 'Add New Member';
    document.getElementById('memberForm').reset();
    document.getElementById('memberId').value = '';
    document.getElementById('memberRegDate').value = Utils.today();
    openModal('memberModal');
  }

  function openEdit(id) {
    const m = CIMS_DB.getById('members', id);
    if (!m) return;
    document.getElementById('memberModalTitle').textContent = 'Edit Member';
    document.getElementById('memberId').value = m.id;
    document.getElementById('memberFname').value = m.fname;
    document.getElementById('memberLname').value = m.lname;
    document.getElementById('memberPhone').value = m.phone;
    document.getElementById('memberEmail').value = m.email;
    document.getElementById('memberDob').value = m.dob;
    document.getElementById('memberGender').value = m.gender;
    document.getElementById('memberAddress').value = m.address;
    document.getElementById('memberOccupation').value = m.occupation || '';
    document.getElementById('memberRegDate').value = m.regDate;
    document.getElementById('memberStatus').value = m.status;
    document.getElementById('memberNotes').value = m.notes || '';
    openModal('memberModal');
  }

  function saveMember() {
    const fname = document.getElementById('memberFname').value.trim();
    const lname = document.getElementById('memberLname').value.trim();
    const phone = document.getElementById('memberPhone').value.trim();
    if (!fname || !lname || !phone) { Utils.toast('First name, last name, and phone are required.', 'danger'); return; }

    const data = {
      fname, lname, phone,
      email: document.getElementById('memberEmail').value.trim(),
      dob: document.getElementById('memberDob').value,
      gender: document.getElementById('memberGender').value,
      address: document.getElementById('memberAddress').value.trim(),
      occupation: document.getElementById('memberOccupation').value.trim(),
      regDate: document.getElementById('memberRegDate').value || Utils.today(),
      status: document.getElementById('memberStatus').value,
      notes: document.getElementById('memberNotes').value.trim(),
    };

    const id = document.getElementById('memberId').value;
    if (id) {
      CIMS_DB.update('members', parseInt(id), data);
      Utils.toast('Member updated successfully!');
    } else {
      CIMS_DB.insert('members', data);
      Utils.toast('Member added successfully!');
    }
    closeModal('memberModal');
    render();
  }

  function deleteMember(id) {
    const m = CIMS_DB.getById('members', id);
    Utils.confirm(`Remove ${m.fname} ${m.lname} from the registry?`, () => {
      CIMS_DB.remove('members', id);
      Utils.toast('Member removed.', 'danger');
      render();
    });
  }

  function exportMembers() {
    const members = CIMS_DB.getAll('members');
    Utils.exportCSV(members.map(m => ({
      'First Name': m.fname, 'Last Name': m.lname, 'Phone': m.phone,
      'Email': m.email, 'Gender': m.gender, 'Address': m.address,
      'Registered': m.regDate, 'Status': m.status,
    })), 'CIMS_Members_' + Utils.today());
  }

  function printMembers() {
    const members = CIMS_DB.getAll('members');
    const rows = members.map((m, i) => `<tr><td>${i+1}</td><td>${m.fname} ${m.lname}</td><td>${m.phone}</td><td>${m.gender}</td><td>${m.regDate}</td><td>${m.status}</td></tr>`).join('');
    Utils.printSection(`<table><thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Gender</th><th>Registered</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>`, 'Member Registry');
  }

  return { render, renderTable, setPage, openAdd, openEdit, saveMember, deleteMember, exportMembers, printMembers };
})();

// ============================================================
// FINANCE
// ============================================================
Pages.Finance = (() => {
  let filteredTx = [];
  let currentPage = 1;
  const PER_PAGE = 12;

  function render(typeFilter = 'all', dateFrom = '', dateTo = '') {
    const transactions = CIMS_DB.getAll('transactions');
    const members = CIMS_DB.getAll('members');

    filteredTx = transactions.filter(t => {
      const matchType = typeFilter === 'all' || t.type === typeFilter;
      const matchFrom = !dateFrom || t.date >= dateFrom;
      const matchTo = !dateTo || t.date <= dateTo;
      return matchType && matchFrom && matchTo;
    }).sort((a, b) => b.date.localeCompare(a.date));

    const tithes = transactions.filter(t => t.type === 'Tithe').reduce((s, t) => s + t.amount, 0);
    const offerings = transactions.filter(t => t.type === 'Offering').reduce((s, t) => s + t.amount, 0);
    const donations = transactions.filter(t => t.type === 'Donation').reduce((s, t) => s + t.amount, 0);

    document.getElementById('f-tithes').textContent = Utils.fmtCurrency(tithes);
    document.getElementById('f-offerings').textContent = Utils.fmtCurrency(offerings);
    document.getElementById('f-donations').textContent = Utils.fmtCurrency(donations);
    document.getElementById('f-total').textContent = Utils.fmtCurrency(tithes + offerings + donations);

    renderTable(members);
    populateMemberSelect();
  }

  function renderTable(members) {
    members = members || CIMS_DB.getAll('members');
    const { items, total, pages } = Utils.paginate(filteredTx, currentPage, PER_PAGE);
    document.getElementById('transactionsTable').innerHTML = items.map(t => `
      <tr>
        <td>${Utils.fmtDate(t.date)}</td>
        <td>${Utils.memberName(members, t.memberId)}</td>
        <td>${Utils.statusBadge(t.type)}</td>
        <td style="font-weight:500;font-family:'Lora',serif">${Utils.fmtCurrency(t.amount)}</td>
        <td style="color:var(--text-muted);font-size:12px">${t.notes || '—'}</td>
        <td>
          ${App.isFinance() ? `<button class="btn btn-xs btn-danger" onclick="Pages.Finance.deleteTransaction(${t.id})">Delete</button>` : ''}
        </td>
      </tr>`).join('') || '<tr><td colspan="6" class="table-empty">No transactions found</td></tr>';
    document.getElementById('txPagination').innerHTML = Utils.paginationControls(currentPage, pages, `function(p){Pages.Finance.setPage(p)}`);
    document.getElementById('txCount').textContent = `${total} record${total !== 1 ? 's' : ''}`;
  }

  function setPage(p) { currentPage = p; renderTable(); }

  function populateMemberSelect() {
    const members = CIMS_DB.getAll('members');
    const sel = document.getElementById('txMember');
    sel.innerHTML = members.map(m => `<option value="${m.id}">${m.fname} ${m.lname}</option>`).join('');
    document.getElementById('txDate').value = Utils.today();
  }

  function openAdd() {
    document.getElementById('txForm').reset();
    populateMemberSelect();
    document.getElementById('txDate').value = Utils.today();
    openModal('transactionModal');
  }

  function saveTransaction() {
    const amount = parseFloat(document.getElementById('txAmount').value);
    if (!amount || amount <= 0) { Utils.toast('Enter a valid amount.', 'danger'); return; }
    const memberId = parseInt(document.getElementById('txMember').value);
    if (!memberId) { Utils.toast('Please select a member.', 'danger'); return; }

    CIMS_DB.insert('transactions', {
      memberId,
      type: document.getElementById('txType').value,
      amount,
      date: document.getElementById('txDate').value || Utils.today(),
      notes: document.getElementById('txNotes').value.trim(),
    });
    closeModal('transactionModal');
    Utils.toast('Transaction recorded!');
    render();
  }

  function deleteTransaction(id) {
    Utils.confirm('Delete this transaction record?', () => {
      CIMS_DB.remove('transactions', id);
      Utils.toast('Transaction deleted.', 'danger');
      render();
    });
  }

  function applyFilter() {
    const type = document.getElementById('txTypeFilter').value;
    const from = document.getElementById('txDateFrom').value;
    const to = document.getElementById('txDateTo').value;
    currentPage = 1;
    render(type, from, to);
  }

  function exportTransactions() {
    const members = CIMS_DB.getAll('members');
    Utils.exportCSV(filteredTx.map(t => ({
      'Date': t.date, 'Member': Utils.memberName(members, t.memberId),
      'Type': t.type, 'Amount (UGX)': t.amount, 'Notes': t.notes || '',
    })), 'CIMS_Finance_' + Utils.today());
  }

  function printReport() {
    const members = CIMS_DB.getAll('members');
    const transactions = CIMS_DB.getAll('transactions');
    const tithes = transactions.filter(t => t.type === 'Tithe').reduce((s, t) => s + t.amount, 0);
    const offerings = transactions.filter(t => t.type === 'Offering').reduce((s, t) => s + t.amount, 0);
    const donations = transactions.filter(t => t.type === 'Donation').reduce((s, t) => s + t.amount, 0);
    const rows = filteredTx.map((t, i) => `<tr><td>${i+1}</td><td>${t.date}</td><td>${Utils.memberName(members, t.memberId)}</td><td>${t.type}</td><td>UGX ${Utils.fmt(t.amount)}</td><td>${t.notes||''}</td></tr>`).join('');
    const summary = `<p><strong>Tithes:</strong> UGX ${Utils.fmt(tithes)} &nbsp; <strong>Offerings:</strong> UGX ${Utils.fmt(offerings)} &nbsp; <strong>Donations:</strong> UGX ${Utils.fmt(donations)} &nbsp; <strong>TOTAL:</strong> UGX ${Utils.fmt(tithes+offerings+donations)}</p><br>`;
    Utils.printSection(summary + `<table><thead><tr><th>#</th><th>Date</th><th>Member</th><th>Type</th><th>Amount</th><th>Notes</th></tr></thead><tbody>${rows}</tbody></table>`, 'Financial Report');
  }

  return { render, setPage, openAdd, saveTransaction, deleteTransaction, applyFilter, exportTransactions, printReport };
})();

// ============================================================
// EVENTS
// ============================================================
Pages.Events = (() => {
  function render(filter = 'all') {
    const events = CIMS_DB.getAll('events');
    const attendance = CIMS_DB.getAll('attendance');
    const today = Utils.today();

    let filtered = events;
    if (filter === 'upcoming') filtered = events.filter(e => e.date >= today);
    if (filter === 'past') filtered = events.filter(e => e.date < today);
    filtered.sort((a, b) => b.date.localeCompare(a.date));

    const CATEGORIES = { Worship: 'green', Youth: 'blue', Prayer: 'purple', Special: 'amber', Training: 'coral', Outreach: 'gray' };

    document.getElementById('eventsGrid').innerHTML = filtered.map(ev => {
      const attendees = attendance.filter(a => a.eventId === ev.id).length;
      const isPast = ev.date < today;
      const catClass = CATEGORIES[ev.category] || 'gray';
      return `<div class="event-card">
        <div class="event-card-header">
          <div>
            <div class="event-name">${ev.name}</div>
            <div style="margin-top:5px">${Utils.statusBadge(isPast ? 'Past' : 'Upcoming')} <span class="badge badge-${catClass}" style="margin-left:4px">${ev.category}</span></div>
          </div>
        </div>
        <div class="event-meta">
          <span>📅 ${Utils.fmtDate(ev.date)}</span>
          <span>🕐 ${ev.time}${ev.endTime ? ' – ' + ev.endTime : ''}</span>
          <span>📍 ${ev.location}</span>
          <span>👥 ${attendees} / ${ev.capacity || '∞'}</span>
        </div>
        <div class="event-desc">${ev.desc || ''}</div>
        <div class="event-footer">
          <div class="flex gap-6">
            ${App.canEdit() ? `<button class="btn btn-xs" onclick="Pages.Events.openEdit(${ev.id})">Edit</button>` : ''}
            ${App.isAdmin() || App.getRole() === 'Event Coordinator' ? `<button class="btn btn-xs btn-danger" onclick="Pages.Events.deleteEvent(${ev.id})">Delete</button>` : ''}
          </div>
          <span style="font-size:11px;color:var(--text-light)">${ev.capacity ? Math.round(attendees/ev.capacity*100) + '% full' : ''}</span>
        </div>
      </div>`;
    }).join('') || '<div class="table-empty" style="grid-column:1/-1">No events found</div>';

    document.getElementById('eventsTotal').textContent = filtered.length + ' event' + (filtered.length !== 1 ? 's' : '');
  }

  function openAdd() {
    document.getElementById('eventModalTitle').textContent = 'Create New Event';
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventDate').value = Utils.today();
    document.getElementById('eventTime').value = '09:00';
    openModal('eventModal');
  }

  function openEdit(id) {
    const ev = CIMS_DB.getById('events', id);
    if (!ev) return;
    document.getElementById('eventModalTitle').textContent = 'Edit Event';
    document.getElementById('eventId').value = ev.id;
    document.getElementById('eventName').value = ev.name;
    document.getElementById('eventDate').value = ev.date;
    document.getElementById('eventTime').value = ev.time;
    document.getElementById('eventEndTime').value = ev.endTime || '';
    document.getElementById('eventLocation').value = ev.location;
    document.getElementById('eventCategory').value = ev.category || 'Worship';
    document.getElementById('eventCapacity').value = ev.capacity || '';
    document.getElementById('eventDesc').value = ev.desc || '';
    openModal('eventModal');
  }

  function saveEvent() {
    const name = document.getElementById('eventName').value.trim();
    const date = document.getElementById('eventDate').value;
    if (!name || !date) { Utils.toast('Event name and date are required.', 'danger'); return; }

    const data = {
      name, date,
      time: document.getElementById('eventTime').value,
      endTime: document.getElementById('eventEndTime').value,
      location: document.getElementById('eventLocation').value.trim(),
      category: document.getElementById('eventCategory').value,
      capacity: parseInt(document.getElementById('eventCapacity').value) || 0,
      desc: document.getElementById('eventDesc').value.trim(),
    };

    const id = document.getElementById('eventId').value;
    if (id) { CIMS_DB.update('events', parseInt(id), data); Utils.toast('Event updated!'); }
    else { CIMS_DB.insert('events', data); Utils.toast('Event created!'); }
    closeModal('eventModal');
    render();
  }

  function deleteEvent(id) {
    const ev = CIMS_DB.getById('events', id);
    Utils.confirm(`Delete the event "${ev.name}"?`, () => {
      CIMS_DB.remove('events', id);
      CIMS_DB.query('attendance', a => a.eventId === id).forEach(a => CIMS_DB.remove('attendance', a.id));
      Utils.toast('Event deleted.', 'danger');
      render();
    });
  }

  return { render, openAdd, openEdit, saveEvent, deleteEvent };
})();

// ============================================================
// ATTENDANCE
// ============================================================
Pages.Attendance = (() => {
  let filteredAtt = [];
  let currentPage = 1;
  const PER_PAGE = 12;

  function render(eventFilter = 'all', statusFilter = 'all') {
    const attendance = CIMS_DB.getAll('attendance');
    const members = CIMS_DB.getAll('members');
    const events = CIMS_DB.getAll('events');

    filteredAtt = attendance.filter(a => {
      const matchEvent = eventFilter === 'all' || a.eventId === parseInt(eventFilter);
      const matchStatus = statusFilter === 'all' || a.status === statusFilter;
      return matchEvent && matchStatus;
    }).sort((a, b) => b.date.localeCompare(a.date));

    // Populate event filter dropdown
    const evSel = document.getElementById('attEventFilter');
    const currentVal = evSel.value;
    evSel.innerHTML = '<option value="all">All Events</option>' +
      events.map(e => `<option value="${e.id}" ${currentVal == e.id ? 'selected' : ''}>${e.name}</option>`).join('');

    // Populate modal selects
    const evModal = document.getElementById('attEvent');
    evModal.innerHTML = events.map(e => `<option value="${e.id}">${e.name} (${e.date})</option>`).join('');
    const mModal = document.getElementById('attMember');
    mModal.innerHTML = members.map(m => `<option value="${m.id}">${m.fname} ${m.lname}</option>`).join('');

    renderTable(members, events);

    const present = filteredAtt.filter(a => a.status === 'Present').length;
    const absent = filteredAtt.filter(a => a.status === 'Absent').length;
    document.getElementById('att-present').textContent = present;
    document.getElementById('att-absent').textContent = absent;
    document.getElementById('att-rate').textContent = filteredAtt.length ? Math.round(present / filteredAtt.length * 100) + '%' : '0%';
  }

  function renderTable(members, events) {
    members = members || CIMS_DB.getAll('members');
    events = events || CIMS_DB.getAll('events');
    const { items, total, pages } = Utils.paginate(filteredAtt, currentPage, PER_PAGE);

    document.getElementById('attendanceTable').innerHTML = items.map(a => `
      <tr>
        <td>${Utils.eventName(events, a.eventId)}</td>
        <td>${Utils.memberName(members, a.memberId)}</td>
        <td>${Utils.fmtDate(a.date)}</td>
        <td>${Utils.statusBadge(a.status)}</td>
        <td>
          ${App.canEdit() || App.getRole() === 'Event Coordinator' ?
            `<button class="btn btn-xs btn-danger" onclick="Pages.Attendance.deleteRecord(${a.id})">Remove</button>` : ''}
        </td>
      </tr>`).join('') || '<tr><td colspan="5" class="table-empty">No attendance records</td></tr>';

    document.getElementById('attPagination').innerHTML = Utils.paginationControls(currentPage, pages, `function(p){Pages.Attendance.setPage(p)}`);
    document.getElementById('attCount').textContent = total + ' record' + (total !== 1 ? 's' : '');
  }

  function setPage(p) { currentPage = p; renderTable(); }

  function openAdd() {
    document.getElementById('attForm').reset();
    document.getElementById('attDate').value = Utils.today();
    openModal('attendanceModal');
  }

  function saveAttendance() {
    const eventId = parseInt(document.getElementById('attEvent').value);
    const memberId = parseInt(document.getElementById('attMember').value);
    if (!eventId || !memberId) { Utils.toast('Select an event and member.', 'danger'); return; }

    const existing = CIMS_DB.query('attendance', a => a.eventId === eventId && a.memberId === memberId);
    if (existing.length) { Utils.toast('Attendance already recorded for this member and event.', 'danger'); return; }

    CIMS_DB.insert('attendance', {
      eventId, memberId,
      date: document.getElementById('attDate').value || Utils.today(),
      status: document.getElementById('attStatus').value,
    });
    closeModal('attendanceModal');
    Utils.toast('Attendance recorded!');
    render();
  }

  function deleteRecord(id) {
    Utils.confirm('Remove this attendance record?', () => {
      CIMS_DB.remove('attendance', id);
      Utils.toast('Record removed.', 'danger');
      render();
    });
  }

  function applyFilter() {
    const ev = document.getElementById('attEventFilter').value;
    const st = document.getElementById('attStatusFilter').value;
    currentPage = 1;
    render(ev, st);
  }

  function exportAttendance() {
    const members = CIMS_DB.getAll('members');
    const events = CIMS_DB.getAll('events');
    Utils.exportCSV(filteredAtt.map(a => ({
      'Event': Utils.eventName(events, a.eventId),
      'Member': Utils.memberName(members, a.memberId),
      'Date': a.date, 'Status': a.status,
    })), 'CIMS_Attendance_' + Utils.today());
  }

  return { render, setPage, openAdd, saveAttendance, deleteRecord, applyFilter, exportAttendance };
})();

// ============================================================
// REPORTS
// ============================================================
Pages.Reports = (() => {
  function render() {
    const members = CIMS_DB.getAll('members');
    const transactions = CIMS_DB.getAll('transactions');
    const events = CIMS_DB.getAll('events');
    const attendance = CIMS_DB.getAll('attendance');

    renderFinancialReport(transactions, members);
    renderMembershipReport(members);
    renderEventReport(events, attendance);
    renderTopContributors(transactions, members);
  }

  function renderFinancialReport(transactions, members) {
    const tithes = transactions.filter(t => t.type === 'Tithe').reduce((s, t) => s + t.amount, 0);
    const offerings = transactions.filter(t => t.type === 'Offering').reduce((s, t) => s + t.amount, 0);
    const donations = transactions.filter(t => t.type === 'Donation').reduce((s, t) => s + t.amount, 0);
    const total = tithes + offerings + donations;
    const max = Math.max(tithes, offerings, donations, 1);

    document.getElementById('r-total').textContent = Utils.fmtCurrency(total);
    document.getElementById('r-tithes').textContent = Utils.fmtCurrency(tithes);
    document.getElementById('r-offerings').textContent = Utils.fmtCurrency(offerings);

    document.getElementById('reportFinChart').innerHTML = [
      ['Tithes', tithes, 'fill-green'],
      ['Offerings', offerings, 'fill-blue'],
      ['Donations', donations, 'fill-amber'],
    ].map(([label, val, cls]) => `
      <div class="chart-row">
        <div class="chart-row-label"><strong>${label}</strong><span>${Utils.fmtCurrency(val)} (${total ? Math.round(val/total*100) : 0}%)</span></div>
        <div class="progress-bar"><div class="progress-fill ${cls}" style="width:${Math.round(val/max*100)}%"></div></div>
      </div>`).join('');

    // Monthly breakdown
    const monthly = {};
    transactions.forEach(t => {
      const k = t.date.slice(0, 7);
      if (!monthly[k]) monthly[k] = { Tithe: 0, Offering: 0, Donation: 0 };
      monthly[k][t.type] = (monthly[k][t.type] || 0) + t.amount;
    });
    const months = Object.keys(monthly).sort().slice(-6);
    const maxM = Math.max(...months.map(m => Object.values(monthly[m]).reduce((a, b) => a + b, 0)), 1);

    document.getElementById('reportMonthlyChart').innerHTML = months.map(m => {
      const tot = Object.values(monthly[m]).reduce((a, b) => a + b, 0);
      return `<div class="chart-row">
        <div class="chart-row-label"><strong>${Utils.monthLabel(m + '-01')}</strong><span>${Utils.fmtCurrency(tot)}</span></div>
        <div class="progress-bar"><div class="progress-fill fill-green" style="width:${Math.round(tot/maxM*100)}%"></div></div>
      </div>`;
    }).join('') || '<div class="text-muted text-sm">No data</div>';
  }

  function renderMembershipReport(members) {
    const active = members.filter(m => m.status === 'Active').length;
    const inactive = members.filter(m => m.status === 'Inactive').length;
    const total = members.length;
    document.getElementById('r-members-total').textContent = total;
    document.getElementById('r-members-active').textContent = active;

    const genderM = members.filter(m => m.gender === 'Male').length;
    const genderF = members.filter(m => m.gender === 'Female').length;
    const maxG = Math.max(genderM, genderF, 1);

    document.getElementById('reportMemberChart').innerHTML = `
      <div class="chart-row">
        <div class="chart-row-label"><strong>Active Members</strong><span>${active} (${total ? Math.round(active/total*100) : 0}%)</span></div>
        <div class="progress-bar"><div class="progress-fill fill-green" style="width:${total ? Math.round(active/total*100) : 0}%"></div></div>
      </div>
      <div class="chart-row">
        <div class="chart-row-label"><strong>Inactive Members</strong><span>${inactive}</span></div>
        <div class="progress-bar"><div class="progress-fill fill-amber" style="width:${total ? Math.round(inactive/total*100) : 0}%"></div></div>
      </div>
      <div class="chart-row" style="margin-top:16px">
        <div class="chart-row-label"><strong>Male</strong><span>${genderM}</span></div>
        <div class="progress-bar"><div class="progress-fill fill-blue" style="width:${Math.round(genderM/maxG*100)}%"></div></div>
      </div>
      <div class="chart-row">
        <div class="chart-row-label"><strong>Female</strong><span>${genderF}</span></div>
        <div class="progress-bar"><div class="progress-fill fill-purple" style="width:${Math.round(genderF/maxG*100)}%"></div></div>
      </div>`;
  }

  function renderEventReport(events, attendance) {
    const upcomingCount = events.filter(e => e.date >= Utils.today()).length;
    const presentTotal = attendance.filter(a => a.status === 'Present').length;
    document.getElementById('r-events').textContent = events.length;
    document.getElementById('r-upcoming').textContent = upcomingCount;

    const eventSummary = events.map(ev => {
      const att = attendance.filter(a => a.eventId === ev.id);
      const present = att.filter(a => a.status === 'Present').length;
      return { name: ev.name, total: att.length, present };
    }).filter(x => x.total > 0);

    const maxE = Math.max(...eventSummary.map(e => e.total), 1);
    document.getElementById('reportEventChart').innerHTML = eventSummary.map(e => `
      <div class="chart-row">
        <div class="chart-row-label"><strong>${e.name}</strong><span>${e.present}/${e.total} present</span></div>
        <div class="progress-bar"><div class="progress-fill fill-green" style="width:${Math.round(e.total/maxE*100)}%"></div></div>
      </div>`).join('') || '<div class="text-muted text-sm">No event attendance data</div>';
  }

  function renderTopContributors(transactions, members) {
    const byMember = {};
    transactions.forEach(t => { byMember[t.memberId] = (byMember[t.memberId] || 0) + t.amount; });
    const sorted = Object.entries(byMember).sort((a, b) => b[1] - a[1]).slice(0, 8);
    const maxC = sorted[0] ? sorted[0][1] : 1;

    document.getElementById('reportTopContrib').innerHTML = sorted.map(([id, amt]) => `
      <div class="chart-row">
        <div class="chart-row-label"><strong>${Utils.memberName(members, parseInt(id))}</strong><span>${Utils.fmtCurrency(amt)}</span></div>
        <div class="progress-bar"><div class="progress-fill fill-green" style="width:${Math.round(amt/maxC*100)}%"></div></div>
      </div>`).join('') || '<div class="text-muted text-sm">No contribution data</div>';
  }

  function printFinancialReport() {
    const transactions = CIMS_DB.getAll('transactions');
    const members = CIMS_DB.getAll('members');
    const tithes = transactions.filter(t => t.type === 'Tithe').reduce((s, t) => s + t.amount, 0);
    const offerings = transactions.filter(t => t.type === 'Offering').reduce((s, t) => s + t.amount, 0);
    const donations = transactions.filter(t => t.type === 'Donation').reduce((s, t) => s + t.amount, 0);
    const summary = `<p><b>Total Tithes:</b> UGX ${Utils.fmt(tithes)}<br><b>Total Offerings:</b> UGX ${Utils.fmt(offerings)}<br><b>Total Donations:</b> UGX ${Utils.fmt(donations)}<br><b>GRAND TOTAL:</b> UGX ${Utils.fmt(tithes+offerings+donations)}</p><br>`;
    const rows = transactions.map((t,i)=>`<tr><td>${i+1}</td><td>${t.date}</td><td>${Utils.memberName(members,t.memberId)}</td><td>${t.type}</td><td>UGX ${Utils.fmt(t.amount)}</td></tr>`).join('');
    Utils.printSection(summary+`<table><thead><tr><th>#</th><th>Date</th><th>Member</th><th>Type</th><th>Amount</th></tr></thead><tbody>${rows}</tbody></table>`, 'Financial Report');
  }

  return { render, printFinancialReport };
})();

// ============================================================
// USERS
// ============================================================
Pages.Users = (() => {
  function render() {
    const users = CIMS_DB.getAll('users');
    document.getElementById('usersTable').innerHTML = users.map((u, i) => `
      <tr>
        <td><div class="member-cell">
          <div class="avatar avatar-md" style="${Utils.avatarStyle(i)}">${Utils.initials(u.name)}</div>
          <div class="member-cell-info"><p>${u.name}</p><span>Last login: ${u.lastLogin || 'Never'}</span></div>
        </div></td>
        <td style="font-family:monospace;font-size:13px">${u.username}</td>
        <td>${Utils.roleBadge(u.role)}</td>
        <td>${Utils.statusBadge(u.status)}</td>
        <td>${u.createdAt || '—'}</td>
        <td>
          <div class="flex gap-6">
            <button class="btn btn-xs" onclick="Pages.Users.openEdit(${u.id})">Edit</button>
            ${u.id !== 1 ? `<button class="btn btn-xs btn-danger" onclick="Pages.Users.deleteUser(${u.id})">Remove</button>` : ''}
          </div>
        </td>
      </tr>`).join('') || '<tr><td colspan="6" class="table-empty">No users found</td></tr>';
  }

  function openAdd() {
    document.getElementById('userModalTitle').textContent = 'Create User Account';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userPasswordRow').style.display = '';
    openModal('userModal');
  }

  function openEdit(id) {
    const u = CIMS_DB.getById('users', id);
    if (!u) return;
    document.getElementById('userModalTitle').textContent = 'Edit User Account';
    document.getElementById('userId').value = u.id;
    document.getElementById('userName').value = u.name;
    document.getElementById('userUsername').value = u.username;
    document.getElementById('userRole').value = u.role;
    document.getElementById('userStatus').value = u.status;
    document.getElementById('userPasswordRow').style.display = 'none';
    openModal('userModal');
  }

  function saveUser() {
    const name = document.getElementById('userName').value.trim();
    const username = document.getElementById('userUsername').value.trim();
    if (!name || !username) { Utils.toast('Name and username are required.', 'danger'); return; }

    const id = document.getElementById('userId').value;
    const existing = CIMS_DB.query('users', u => u.username === username && u.id !== parseInt(id));
    if (existing.length) { Utils.toast('Username already taken.', 'danger'); return; }

    const data = {
      name, username,
      role: document.getElementById('userRole').value,
      status: document.getElementById('userStatus').value,
    };

    if (id) {
      CIMS_DB.update('users', parseInt(id), data);
      Utils.toast('User updated!');
    } else {
      const password = document.getElementById('userPassword').value;
      if (!password) { Utils.toast('Password is required.', 'danger'); return; }
      CIMS_DB.insert('users', { ...data, passwordHash: password, createdAt: Utils.today(), lastLogin: null });
      Utils.toast('User created!');
    }
    closeModal('userModal');
    render();
  }

  function deleteUser(id) {
    const u = CIMS_DB.getById('users', id);
    Utils.confirm(`Remove user account "${u.username}"?`, () => {
      CIMS_DB.remove('users', id);
      Utils.toast('User removed.', 'danger');
      render();
    });
  }

  return { render, openAdd, openEdit, saveUser, deleteUser };
})();

// ============================================================
// PROFILE
// ============================================================
Pages.Profile = (() => {
  function render() {
    const user = App.getUser();
    const members = CIMS_DB.getAll('members');
    const transactions = CIMS_DB.getAll('transactions');
    const events = CIMS_DB.getAll('events');

    const member = user.memberId ? CIMS_DB.getById('members', user.memberId) : members[0];

    if (!member) {
      document.getElementById('profileContent').innerHTML = '<div class="text-muted text-sm">No member profile linked to this account.</div>';
      return;
    }

    const myTx = transactions.filter(t => t.memberId === member.id).sort((a, b) => b.date.localeCompare(a.date));
    const myTotal = myTx.reduce((s, t) => s + t.amount, 0);

    document.getElementById('profileAvatar').textContent = Utils.initials(member.fname + ' ' + member.lname);
    document.getElementById('profileAvatar').style = Utils.avatarStyle(0);
    document.getElementById('profileName').textContent = `${member.fname} ${member.lname}`;
    document.getElementById('profileRole').textContent = user.role;

    document.getElementById('profileDetails').innerHTML = [
      ['Phone', member.phone], ['Email', member.email],
      ['Gender', member.gender], ['Date of Birth', Utils.fmtDate(member.dob)],
      ['Address', member.address], ['Occupation', member.occupation || '—'],
      ['Registered', Utils.fmtDate(member.regDate)], ['Status', Utils.statusBadge(member.status)],
    ].map(([l, v]) => `<div class="profile-info-row"><span class="profile-info-label">${l}</span><span>${v}</span></div>`).join('');

    document.getElementById('profileContribTotal').textContent = Utils.fmtCurrency(myTotal);
    document.getElementById('profileContribs').innerHTML = myTx.slice(0, 8).map(t => `
      <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--gray-100)">
        <div>
          <div style="font-size:13px;font-weight:500">${Utils.fmtDate(t.date)}</div>
          ${t.notes ? `<div style="font-size:11px;color:var(--text-light)">${t.notes}</div>` : ''}
        </div>
        <div style="text-align:right">
          ${Utils.statusBadge(t.type)}
          <div style="font-size:13px;font-weight:500;margin-top:3px">${Utils.fmtCurrency(t.amount)}</div>
        </div>
      </div>`).join('') || '<div class="text-muted text-sm" style="padding:10px 0">No contributions recorded</div>';

    document.getElementById('profileEvents').innerHTML = events.slice(0, 5).map(e => `
      <div style="padding:9px 0;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:13px;font-weight:500">${e.name}</div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">📅 ${Utils.fmtDate(e.date)} &nbsp; 📍 ${e.location}</div>
      </div>`).join('') || '<div class="text-muted text-sm">No events</div>';
  }

  return { render };
})();

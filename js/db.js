// ============================================================
// CIMS - Church Information Management System
// Database Module - localStorage-backed persistent store
// ============================================================

const CIMS_DB = (() => {
  const KEYS = {
    members: 'cims_members',
    transactions: 'cims_transactions',
    events: 'cims_events',
    attendance: 'cims_attendance',
    users: 'cims_users',
    session: 'cims_session',
  };

  const DEFAULT_DATA = {
    members: [
      { id: 1, fname: 'Sarah', lname: 'Namukasa', phone: '+256 700 111111', email: 'sarah.n@example.com', dob: '1990-03-12', gender: 'Female', address: 'Nagongera Town Council', occupation: 'Teacher', regDate: '2024-01-15', status: 'Active', notes: 'Choir member' },
      { id: 2, fname: 'Peter', lname: 'Okello', phone: '+256 701 222222', email: 'peter.o@example.com', dob: '1985-07-22', gender: 'Male', address: 'Tororo Municipality', occupation: 'Accountant', regDate: '2024-02-03', status: 'Active', notes: '' },
      { id: 3, fname: 'Grace', lname: 'Apio', phone: '+256 702 333333', email: 'grace.a@example.com', dob: '1995-11-05', gender: 'Female', address: 'Nagongera', occupation: 'Student', regDate: '2024-02-20', status: 'Active', notes: 'Youth leader' },
      { id: 4, fname: 'James', lname: 'Wanyama', phone: '+256 703 444444', email: 'james.w@example.com', dob: '1978-04-18', gender: 'Male', address: 'Tororo', occupation: 'Businessman', regDate: '2024-03-10', status: 'Active', notes: '' },
      { id: 5, fname: 'Ruth', lname: 'Aciro', phone: '+256 704 555555', email: 'ruth.a@example.com', dob: '1992-08-30', gender: 'Female', address: 'Nagongera', occupation: 'Nurse', regDate: '2024-04-05', status: 'Active', notes: '' },
      { id: 6, fname: 'David', lname: 'Mugisha', phone: '+256 705 666666', email: 'david.m@example.com', dob: '1988-12-14', gender: 'Male', address: 'Busia', occupation: 'Farmer', regDate: '2024-05-12', status: 'Inactive', notes: 'Relocated' },
      { id: 7, fname: 'Esther', lname: 'Nakato', phone: '+256 706 777777', email: 'esther.n@example.com', dob: '2000-01-20', gender: 'Female', address: 'Nagongera', occupation: 'Student', regDate: '2024-06-01', status: 'Active', notes: '' },
      { id: 8, fname: 'Moses', lname: 'Ochieng', phone: '+256 707 888888', email: 'moses.o@example.com', dob: '1975-09-08', gender: 'Male', address: 'Tororo', occupation: 'Engineer', regDate: '2024-07-15', status: 'Active', notes: 'Deacon' },
    ],
    transactions: [
      { id: 1, memberId: 1, type: 'Tithe', amount: 150000, date: '2025-01-05', notes: '' },
      { id: 2, memberId: 2, type: 'Offering', amount: 50000, date: '2025-01-05', notes: '' },
      { id: 3, memberId: 3, type: 'Tithe', amount: 200000, date: '2025-01-12', notes: '' },
      { id: 4, memberId: 4, type: 'Donation', amount: 500000, date: '2025-01-12', notes: 'Building fund' },
      { id: 5, memberId: 5, type: 'Offering', amount: 30000, date: '2025-01-19', notes: '' },
      { id: 6, memberId: 1, type: 'Tithe', amount: 150000, date: '2025-02-02', notes: '' },
      { id: 7, memberId: 2, type: 'Tithe', amount: 120000, date: '2025-02-09', notes: '' },
      { id: 8, memberId: 3, type: 'Offering', amount: 75000, date: '2025-02-16', notes: '' },
      { id: 9, memberId: 7, type: 'Tithe', amount: 80000, date: '2025-02-23', notes: '' },
      { id: 10, memberId: 8, type: 'Donation', amount: 300000, date: '2025-03-02', notes: 'Outreach program' },
      { id: 11, memberId: 4, type: 'Tithe', amount: 250000, date: '2025-03-09', notes: '' },
      { id: 12, memberId: 5, type: 'Offering', amount: 45000, date: '2025-03-16', notes: '' },
      { id: 13, memberId: 1, type: 'Tithe', amount: 150000, date: '2025-03-23', notes: '' },
      { id: 14, memberId: 6, type: 'Offering', amount: 20000, date: '2025-03-30', notes: '' },
      { id: 15, memberId: 2, type: 'Tithe', amount: 120000, date: '2025-04-06', notes: '' },
      { id: 16, memberId: 3, type: 'Donation', amount: 150000, date: '2025-04-13', notes: 'Easter offering' },
      { id: 17, memberId: 7, type: 'Tithe', amount: 80000, date: '2025-04-20', notes: '' },
      { id: 18, memberId: 8, type: 'Tithe', amount: 200000, date: '2025-04-27', notes: '' },
    ],
    events: [
      { id: 1, name: 'Sunday Service', date: '2025-05-04', time: '09:00', endTime: '11:30', location: 'Main Hall', category: 'Worship', desc: 'Weekly Sunday worship service with Holy Communion', capacity: 300 },
      { id: 2, name: 'Youth Conference', date: '2025-05-10', time: '10:00', endTime: '17:00', location: 'Fellowship Hall', category: 'Youth', desc: 'Annual youth gathering with guest speaker and worship', capacity: 150 },
      { id: 3, name: 'Prayer Night', date: '2025-05-17', time: '20:00', endTime: '05:00', location: 'Main Hall', category: 'Prayer', desc: 'Monthly all-night prayer and fasting session', capacity: 200 },
      { id: 4, name: 'Easter Convention', date: '2025-04-20', time: '09:00', endTime: '18:00', location: 'Church Grounds', category: 'Special', desc: 'Easter celebration with baptism and thanksgiving', capacity: 500 },
      { id: 5, name: 'Leadership Training', date: '2025-05-24', time: '09:00', endTime: '16:00', location: 'Conference Room', category: 'Training', desc: 'Quarterly leadership development workshop', capacity: 50 },
      { id: 6, name: 'Community Outreach', date: '2025-06-07', time: '08:00', endTime: '14:00', location: 'Nagongera Town', category: 'Outreach', desc: 'Community service and evangelism program', capacity: 100 },
    ],
    attendance: [
      { id: 1, eventId: 4, memberId: 1, date: '2025-04-20', status: 'Present' },
      { id: 2, eventId: 4, memberId: 2, date: '2025-04-20', status: 'Present' },
      { id: 3, eventId: 4, memberId: 3, date: '2025-04-20', status: 'Present' },
      { id: 4, eventId: 4, memberId: 4, date: '2025-04-20', status: 'Absent' },
      { id: 5, eventId: 4, memberId: 5, date: '2025-04-20', status: 'Present' },
      { id: 6, eventId: 4, memberId: 7, date: '2025-04-20', status: 'Present' },
      { id: 7, eventId: 4, memberId: 8, date: '2025-04-20', status: 'Excused' },
    ],
    users: [
      { id: 1, name: 'System Administrator', username: 'admin', passwordHash: 'admin123', role: 'System Administrator', status: 'Active', createdAt: '2024-01-01', lastLogin: '2025-04-29' },
      { id: 2, name: 'Mary Finances', username: 'mfinance', passwordHash: 'finance123', role: 'Finance Officer', status: 'Active', createdAt: '2024-01-05', lastLogin: '2025-04-28' },
      { id: 3, name: 'Pastor John Mukasa', username: 'pastor', passwordHash: 'pastor123', role: 'Church Leader', status: 'Active', createdAt: '2024-01-05', lastLogin: '2025-04-27' },
      { id: 4, name: 'Sarah Namukasa', username: 'sarah', passwordHash: 'member123', role: 'Church Member', status: 'Active', createdAt: '2024-01-15', lastLogin: '2025-04-20', memberId: 1 },
      { id: 5, name: 'Events Coordinator', username: 'events', passwordHash: 'events123', role: 'Event Coordinator', status: 'Active', createdAt: '2024-02-01', lastLogin: '2025-04-25' },
    ],
  };

  function init() {
    Object.keys(DEFAULT_DATA).forEach(key => {
      if (!localStorage.getItem(KEYS[key])) {
        localStorage.setItem(KEYS[key], JSON.stringify(DEFAULT_DATA[key]));
      }
    });
  }

  function getAll(table) {
    return JSON.parse(localStorage.getItem(KEYS[table]) || '[]');
  }

  function getById(table, id) {
    return getAll(table).find(r => r.id === id) || null;
  }

  function insert(table, record) {
    const all = getAll(table);
    const newId = all.length > 0 ? Math.max(...all.map(r => r.id)) + 1 : 1;
    const newRecord = { ...record, id: newId };
    all.push(newRecord);
    localStorage.setItem(KEYS[table], JSON.stringify(all));
    return newRecord;
  }

  function update(table, id, changes) {
    const all = getAll(table);
    const idx = all.findIndex(r => r.id === id);
    if (idx === -1) return null;
    all[idx] = { ...all[idx], ...changes };
    localStorage.setItem(KEYS[table], JSON.stringify(all));
    return all[idx];
  }

  function remove(table, id) {
    const all = getAll(table).filter(r => r.id !== id);
    localStorage.setItem(KEYS[table], JSON.stringify(all));
    return true;
  }

  function query(table, fn) {
    return getAll(table).filter(fn);
  }

  function setSession(user) {
    localStorage.setItem(KEYS.session, JSON.stringify({ ...user, loginTime: new Date().toISOString() }));
  }

  function getSession() {
    return JSON.parse(localStorage.getItem(KEYS.session) || 'null');
  }

  function clearSession() {
    localStorage.removeItem(KEYS.session);
  }

  function resetToDefaults() {
    Object.keys(KEYS).forEach(key => localStorage.removeItem(KEYS[key]));
    init();
  }

  return { init, getAll, getById, insert, update, remove, query, setSession, getSession, clearSession, resetToDefaults };
})();

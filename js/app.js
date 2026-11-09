// ============================================================
// CIMS - App Controller
// Authentication, Navigation, Page Routing
// ============================================================

const App = (() => {
  let currentRole = null;
  let currentUser = null;
  let currentPage = null;

  const ROLE_NAV = {
    'System Administrator': [
      { section: 'Overview' },
      { id: 'dashboard', icon: '⊞', label: 'Dashboard' },
      { section: 'Management' },
      { id: 'members', icon: '👥', label: 'Members' },
      { id: 'finance', icon: '💰', label: 'Finance' },
      { id: 'events', icon: '📅', label: 'Events' },
      { id: 'attendance', icon: '✅', label: 'Attendance' },
      { section: 'Administration' },
      { id: 'reports', icon: '📊', label: 'Reports' },
      { id: 'users', icon: '🔐', label: 'User Accounts' },
    ],
    'Finance Officer': [
      { section: 'Overview' },
      { id: 'dashboard', icon: '⊞', label: 'Dashboard' },
      { section: 'Finance' },
      { id: 'finance', icon: '💰', label: 'Transactions' },
      { id: 'reports', icon: '📊', label: 'Reports' },
    ],
    'Church Leader': [
      { section: 'Overview' },
      { id: 'dashboard', icon: '⊞', label: 'Dashboard' },
      { section: 'Church' },
      { id: 'members', icon: '👥', label: 'Members' },
      { id: 'events', icon: '📅', label: 'Events' },
      { id: 'attendance', icon: '✅', label: 'Attendance' },
      { id: 'reports', icon: '📊', label: 'Reports' },
    ],
    'Church Member': [
      { section: 'My Account' },
      { id: 'profile', icon: '👤', label: 'My Profile' },
      { section: 'Church' },
      { id: 'events', icon: '📅', label: 'Events' },
    ],
    'Event Coordinator': [
      { section: 'Overview' },
      { id: 'dashboard', icon: '⊞', label: 'Dashboard' },
      { section: 'Events' },
      { id: 'events', icon: '📅', label: 'Events' },
      { id: 'attendance', icon: '✅', label: 'Attendance' },
    ],
  };

  const PAGE_TITLES = {
    dashboard: 'Dashboard',
    members: 'Member Registry',
    finance: 'Financial Management',
    events: 'Events & Schedules',
    attendance: 'Attendance Records',
    reports: 'Reports & Analytics',
    users: 'User Accounts',
    profile: 'My Profile',
  };

  function init() {
    CIMS_DB.init();
    const session = CIMS_DB.getSession();
    if (session) {
      login(session, true);
    } else {
      showLoginScreen();
    }
  }

  function showLoginScreen() {
    document.getElementById('loginScreen').classList.add('visible');
    document.getElementById('appScreen').classList.remove('visible');
  }

  function showAppScreen() {
    document.getElementById('loginScreen').classList.remove('visible');
    document.getElementById('appScreen').classList.add('visible');
  }

  function login(user, fromSession = false) {
    currentUser = user;
    currentRole = user.role;

    if (!fromSession) {
      CIMS_DB.update('users', user.id, { lastLogin: Utils.today() });
      CIMS_DB.setSession(user);
    }

    document.getElementById('sidebarUserName').textContent = user.name;
    document.getElementById('sidebarUserRole').textContent = user.role;
    document.getElementById('sidebarUserInitials').textContent = Utils.initials(user.name);
    document.getElementById('topbarDate').textContent = new Date().toLocaleDateString('en-GB', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });

    buildNav();
    showAppScreen();

    const firstPage = ROLE_NAV[currentRole].find(n => n.id);
    if (firstPage) navigate(firstPage.id);
  }

  function logout() {
    Utils.confirm('Are you sure you want to sign out?', () => {
      CIMS_DB.clearSession();
      currentUser = null;
      currentRole = null;
      showLoginScreen();
    });
  }

  function buildNav() {
    const nav = document.getElementById('sidebarNav');
    nav.innerHTML = '';
    (ROLE_NAV[currentRole] || []).forEach(item => {
      if (item.section) {
        nav.innerHTML += `<div class="nav-section-label">${item.section}</div>`;
      } else {
        nav.innerHTML += `<div class="nav-item" id="nav-${item.id}" onclick="App.navigate('${item.id}')">
          <span class="nav-icon">${item.icon}</span>${item.label}</div>`;
      }
    });
  }

  function navigate(page) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const pg = document.getElementById('page-' + page);
    if (pg) pg.classList.add('active');
    const navEl = document.getElementById('nav-' + page);
    if (navEl) navEl.classList.add('active');

    currentPage = page;
    document.getElementById('topbarTitle').textContent = PAGE_TITLES[page] || page;

    const renders = {
      dashboard: Pages.Dashboard.render,
      members: Pages.Members.render,
      finance: Pages.Finance.render,
      events: Pages.Events.render,
      attendance: Pages.Attendance.render,
      reports: Pages.Reports.render,
      users: Pages.Users.render,
      profile: Pages.Profile.render,
    };
    if (renders[page]) renders[page]();
  }

  function getRole() { return currentRole; }
  function getUser() { return currentUser; }
  function canEdit() { return ['System Administrator', 'Finance Officer', 'Event Coordinator'].includes(currentRole); }
  function isAdmin() { return currentRole === 'System Administrator'; }
  function isFinance() { return currentRole === 'Finance Officer' || currentRole === 'System Administrator'; }
  function isLeader() { return ['System Administrator', 'Church Leader'].includes(currentRole); }

  function handleLogin() {
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value.trim();
    const errEl = document.getElementById('loginError');

    if (!username || !password) {
      errEl.textContent = 'Please enter username and password.';
      errEl.classList.add('show');
      return;
    }

    const users = CIMS_DB.getAll('users');
    const user = users.find(u => u.username === username && u.passwordHash === password && u.status === 'Active');

    if (!user) {
      errEl.textContent = 'Invalid credentials. Please try again.';
      errEl.classList.add('show');
      document.getElementById('loginPassword').value = '';
      return;
    }

    errEl.classList.remove('show');
    login(user);
  }

  return { init, navigate, logout, getRole, getUser, canEdit, isAdmin, isFinance, isLeader, handleLogin };
})();

// ============================================================
// Modal helpers
// ============================================================
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// Enter key on login
document.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && document.getElementById('loginScreen').classList.contains('visible')) {
    App.handleLogin();
  }
});

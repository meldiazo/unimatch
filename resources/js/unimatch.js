const DEFAULT_STATE = {
  banks: [
    { id: 'bn1', name: 'Banco Nacional de Bolivia', pending: 12, lastSync: 'Hoy 08:45', status: 'conectado' },
    { id: 'bn2', name: 'Banco Económico', pending: 8, lastSync: 'Hoy 09:02', status: 'conectado' },
    { id: 'bn3', name: 'Banco Mercantil', pending: 5, lastSync: 'Ayer 17:15', status: 'revisar' },
    { id: 'bn4', name: 'Banco Bisa', pending: 0, lastSync: 'Hoy 07:55', status: 'conectado' },
    { id: 'bn5', name: 'Banco Sol', pending: 4, lastSync: 'Ayer 18:22', status: 'conectado' },
    { id: 'bn6', name: 'Banco Unión', pending: 3, lastSync: 'Hoy 10:05', status: 'conectado' }
  ],
  transactions: [
    {
      id: 'tx-56321',
      studentId: 'st-001',
      student: 'Andrea López',
      enrollment: '20210145',
      bankId: 'bn1',
      bankName: 'Banco Nacional de Bolivia',
      amount: 425.5,
      reference: 'QR7835',
      date: '2024-05-28T09:35:00',
      status: 'pending',
      channel: 'QR',
      alert: null
    },
    {
      id: 'tx-56322',
      studentId: 'st-002',
      student: 'Carlos Jiménez',
      enrollment: '20210478',
      bankId: 'bn2',
      bankName: 'Banco Económico',
      amount: 612.0,
      reference: 'QR7836',
      date: '2024-05-28T09:48:00',
      status: 'suggested',
      channel: 'QR',
      alert: null
    },
    {
      id: 'tx-56323',
      studentId: 'st-003',
      student: 'Daniela Paredes',
      enrollment: '20210734',
      bankId: 'bn3',
      bankName: 'Banco Mercantil',
      amount: 380.0,
      reference: 'QR7837',
      date: '2024-05-27T16:02:00',
      status: 'flagged',
      channel: 'QR',
      alert: 'Monto no coincide con matrícula'
    },
    {
      id: 'tx-56324',
      studentId: 'st-004',
      student: 'Erick Morales',
      enrollment: '20210267',
      bankId: 'bn4',
      bankName: 'Banco Bisa',
      amount: 425.5,
      reference: 'QR7838',
      date: '2024-05-28T10:15:00',
      status: 'pending',
      channel: 'QR',
      alert: null
    },
    {
      id: 'tx-56325',
      studentId: 'st-005',
      student: 'Fernanda Aguilar',
      enrollment: '20210087',
      bankId: 'bn5',
      bankName: 'Banco Sol',
      amount: 612.0,
      reference: 'QR7839',
      date: '2024-05-26T11:45:00',
      status: 'pending',
      channel: 'QR',
      alert: null
    },
    {
      id: 'tx-56326',
      studentId: 'st-006',
      student: 'Gabriel Soto',
      enrollment: '20210543',
      bankId: 'bn6',
      bankName: 'Banco Unión',
      amount: 320.0,
      reference: 'QR7840',
      date: '2024-05-25T08:32:00',
      status: 'suggested',
      channel: 'QR',
      alert: null
    }
  ],
  vouchers: [
    {
      id: 'vc-9001',
      studentId: 'st-001',
      student: 'Andrea López',
      enrollment: '20210145',
      amount: 425.5,
      issueDate: '2024-05-20',
      dueDate: '2024-05-31',
      status: 'pendiente',
      bankId: 'bn1',
      bankName: 'Banco Nacional de Bolivia',
      paymentDate: '2024-05-20'
    },
    {
      id: 'vc-9002',
      studentId: 'st-002',
      student: 'Carlos Jiménez',
      enrollment: '20210478',
      amount: 612.0,
      issueDate: '2024-05-20',
      dueDate: '2024-05-30',
      status: 'pendiente',
      bankId: 'bn2',
      bankName: 'Banco Económico',
      paymentDate: '2024-05-20'
    },
    {
      id: 'vc-9003',
      studentId: 'st-003',
      student: 'Daniela Paredes',
      enrollment: '20210734',
      amount: 400.0,
      issueDate: '2024-05-18',
      dueDate: '2024-05-29',
      status: 'pendiente',
      bankId: 'bn3',
      bankName: 'Banco Mercantil',
      paymentDate: '2024-05-18'
    },
    {
      id: 'vc-9004',
      studentId: 'st-004',
      student: 'Erick Morales',
      enrollment: '20210267',
      amount: 425.5,
      issueDate: '2024-05-21',
      dueDate: '2024-06-01',
      status: 'pendiente',
      bankId: 'bn4',
      bankName: 'Banco Bisa',
      paymentDate: '2024-05-21'
    },
    {
      id: 'vc-9005',
      studentId: 'st-005',
      student: 'Fernanda Aguilar',
      enrollment: '20210087',
      amount: 612.0,
      issueDate: '2024-05-19',
      dueDate: '2024-05-30',
      status: 'pendiente',
      bankId: 'bn5',
      bankName: 'Banco Sol',
      paymentDate: '2024-05-19'
    },
    {
      id: 'vc-9006',
      studentId: 'st-006',
      student: 'Gabriel Soto',
      enrollment: '20210543',
      amount: 320.0,
      issueDate: '2024-05-17',
      dueDate: '2024-05-29',
      status: 'pendiente',
      bankId: 'bn6',
      bankName: 'Banco Unión',
      paymentDate: '2024-05-17'
    }
  ],
  reconciliations: [
    {
      id: 'rc-1001',
      transactionId: 'tx-5001',
      voucherId: 'vc-8801',
      bankId: 'bn1',
      student: 'Rosa Medina',
      amount: 425.5,
      date: '2024-05-24',
      status: 'conciliado',
      billing_status: 'pendiente'
    },
    {
      id: 'rc-1002',
      transactionId: 'tx-5002',
      voucherId: 'vc-8802',
      bankId: 'bn2',
      student: 'Luis Pérez',
      amount: 612.0,
      date: '2024-05-23',
      status: 'conciliado',
      billing_status: 'facturado'
    }
  ],
  students: [
    {
      id: 'st-001',
      name: 'Andrea López',
      enrollment: '20210145',
      lastPayment: '2024-05-28',
      status: 'pending'
    },
    {
      id: 'st-002',
      name: 'Carlos Jiménez',
      enrollment: '20210478',
      lastPayment: '2024-05-28',
      status: 'pending'
    },
    {
      id: 'st-003',
      name: 'Daniela Paredes',
      enrollment: '20210734',
      lastPayment: '2024-05-27',
      status: 'flagged'
    },
    {
      id: 'st-004',
      name: 'Erick Morales',
      enrollment: '20210267',
      lastPayment: '2024-05-28',
      status: 'pending'
    },
    {
      id: 'st-005',
      name: 'Fernanda Aguilar',
      enrollment: '20210087',
      lastPayment: '2024-05-26',
      status: 'conciliado'
    }
  ]
};

function loadMatchingData() {
  const script = document.getElementById('matching-data');
  if (!script) {
    return null;
  }

  try {
    return JSON.parse(script.textContent || '{}');
  } catch (error) {
    console.warn('No se pudo parsear matching-data', error);
    return null;
  }
}

const serverMatchingData = loadMatchingData();

const state = {
  banks: serverMatchingData?.banks ?? DEFAULT_STATE.banks,
  transactions: serverMatchingData?.transactions ?? DEFAULT_STATE.transactions,
  vouchers: serverMatchingData?.vouchers ?? DEFAULT_STATE.vouchers,
  reconciliations: serverMatchingData?.reconciliations ?? DEFAULT_STATE.reconciliations,
  students: serverMatchingData?.students ?? DEFAULT_STATE.students,
  ui: {
    selectedTransaction: null,
    selectedVoucher: null,
    currentUser: {
      name: '',
      role: '',
      email: ''
    }
  }
};

const STATUS_BADGE_MAP = {
  recibido: 'badge-info',
  conciliado: 'badge-success',
  rechazado: 'badge-danger',
  demasia: 'badge-warning',
  facturado: 'badge-success',
  pending: 'badge-secondary',
  suggested: 'badge-warning',
  flagged: 'badge-danger',
  matched: 'badge-success',
};

const STATUS_LABEL_MAP = {
  recibido: 'Recibido',
  conciliado: 'Conciliado',
  rechazado: 'Rechazado',
  demasia: 'Pago en demasía',
  facturado: 'Facturado',
  pending: 'Pendiente',
  suggested: 'Sugerido',
  flagged: 'Alerta',
  matched: 'Conciliado',
};

function normalizeStatusKey(status) {
  if (!status) {
    return '';
  }

  return typeof status === 'string' ? status.toLowerCase() : status;
}

function getStatusBadgeClass(status) {
  const key = normalizeStatusKey(status);
  return STATUS_BADGE_MAP[key] || 'badge-secondary';
}

function formatStatus(status) {
  const key = normalizeStatusKey(status);
  if (STATUS_LABEL_MAP[key]) {
    return STATUS_LABEL_MAP[key];
  }

  if (!status) {
    return '';
  }

  const str = String(status);
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function extractVoucherDbId(voucher) {
  if (!voucher) {
    return null;
  }

  if (voucher.db_id) {
    return String(voucher.db_id);
  }

  if (voucher.id) {
    const id = String(voucher.id);
    if (id.startsWith('vc-')) {
      return id.slice(3);
    }

    return id;
  }

  return null;
}

function updateRecentVoucherStatus(voucherDbId, status, reason = '') {
  if (!voucherDbId) {
    return;
  }

  const row = document.querySelector(`#voucher-table tr[data-voucher-id="${voucherDbId}"]`);
  if (!row) {
    return;
  }

  const badge = row.querySelector('[data-voucher-status]');
  const reasonEl = row.querySelector('[data-voucher-reason]');
  const statusKey = status ? status.toLowerCase() : 'recibido';

  if (badge) {
    badge.className = `badge ${getStatusBadgeClass(statusKey)}`;
    badge.textContent = formatStatus(statusKey);
  }

  if (reasonEl) {
    if (reason) {
      reasonEl.textContent = reason;
      reasonEl.hidden = false;
    } else {
      reasonEl.textContent = '';
      reasonEl.hidden = true;
    }
  }
}

function buildStudentDirectory(students = []) {
  const directory = new Map();
  students.forEach((student) => {
    const enrollment = (student?.enrollment ?? '').toString().trim();
    if (!enrollment) {
      return;
    }

    directory.set(enrollment.toUpperCase(), student?.name ?? enrollment);
  });

  return directory;
}

function lookupStudentName(directory, identifier) {
  if (!identifier) {
    return null;
  }

  const normalized = identifier.toString().trim().toUpperCase();
  if (!normalized) {
    return null;
  }

  return directory.get(normalized) ?? null;
}

function normalizeStudentEntities() {
  const directory = buildStudentDirectory(state.students || []);
  const assignName = (record, codeKey = 'enrollment') => {
    if (!record) {
      return;
    }

    const candidateCode = record[codeKey] ?? record.enrollment ?? record.student_code;
    const resolvedName = lookupStudentName(directory, candidateCode);
    if (resolvedName) {
      record.student_name = resolvedName;
      if (!record.student || record.student === candidateCode) {
        record.student = resolvedName;
      }
    }
  };

  (state.transactions || []).forEach((tx) => assignName(tx, 'enrollment'));
  (state.vouchers || []).forEach((voucher) => assignName(voucher, 'student_code'));
  (state.reconciliations || []).forEach((item) => assignName(item, 'student_code'));
}

normalizeStudentEntities();

function getStudentName(entity) {
  if (!entity) {
    return '—';
  }

  return entity.student_name || entity.student || '—';
}

const views = document.querySelectorAll('.view');
const navLinks = document.querySelectorAll('.nav-link[data-target]');
const bankFilter = document.getElementById('bank-filter');
const statusFilter = document.getElementById('status-filter');
const transactionList = document.getElementById('transaction-list');
const voucherList = document.getElementById('voucher-list');
const matchDetail = document.getElementById('match-detail');
const toast = document.getElementById('toast');
const modal = document.getElementById('match-modal');
const modalBody = document.getElementById('modal-body');
const confirmMatchBtn = document.getElementById('confirm-match');
const alertList = document.getElementById('alert-list');
const alertEmpty = document.getElementById('alerts-empty');
const reconciliationTable = document.querySelector('#reconciliation-table tbody');
const studentTable = document.querySelector('#student-table tbody');
const bankGrid = document.getElementById('bank-grid');
const globalSearch = document.getElementById('global-search');
const appWrapper = document.getElementById('app-wrapper');
const userNameLabel = document.querySelector('.user-name');
const userRoleLabel = document.querySelector('.user-role');
const userAvatar = document.getElementById('sidebar-avatar');
const rangeButtons = document.querySelectorAll('.toggle[data-range]');
const summaryCards = document.querySelectorAll('.summary-filter');
const confirmMatchUrl = appWrapper?.dataset.confirmUrl || '';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const diffThreshold = Number(appWrapper?.dataset.diffThreshold || 1);
const shortageThreshold = Number(appWrapper?.dataset.shortageThreshold || diffThreshold);
let creditLimit = Number(appWrapper?.dataset.creditLimit || 0);
const hasCreditLimit = Number.isFinite(creditLimit) && creditLimit > 0;
if (!hasCreditLimit) {
  creditLimit = Infinity;
}
const DEFAULT_PANEL_VIEW = 'dashboard';
const initialPanelView = appWrapper?.dataset.initialView || DEFAULT_PANEL_VIEW;
const canManageBilling = appWrapper?.dataset.canManageBilling === '1';

let trendChart = null;
let currentTrendRange = 7;
let modalBackdrop = null;

const viewAllAlertsBtn = document.getElementById('view-all-alerts');
if (viewAllAlertsBtn) {
  viewAllAlertsBtn.addEventListener('click', (event) => {
    event.preventDefault();
    switchView('matching');
    const matchingSection = document.querySelector('[data-view="matching"]');
    if (matchingSection) {
      matchingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB'
  }).format(value);
}

function formatDate(value) {
  return new Intl.DateTimeFormat('es-EC', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(new Date(value));
}

function formatTime(value) {
  return new Intl.DateTimeFormat('es-EC', {
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(value));
}

function formatClock(value, fallback = '') {
  if (!value) {
    return fallback || '—';
  }

  if (value.includes('T')) {
    return formatTime(value);
  }

  const parts = value.split(':');
  const hours = (parts[0] ?? '00').padStart(2, '0');
  const minutes = (parts[1] ?? '00').padStart(2, '0');

  return `${hours}:${minutes}`;
}

function getBankName(bankId) {
  const bank = state.banks.find((b) => b.id === bankId);
  return bank ? bank.name : 'Sin banco';
}

function buildTrendSeries(rangeDays) {
  const labels = [];
  const keys = [];
  const formatter = new Intl.DateTimeFormat('es-BO', { day: '2-digit', month: 'short' });
  const today = new Date();

  for (let offset = rangeDays - 1; offset >= 0; offset -= 1) {
    const date = new Date(today);
    date.setDate(today.getDate() - offset);
    const key = date.toISOString().slice(0, 10);
    keys.push(key);
    labels.push(formatter.format(date));
  }

  const conciliations = keys.map((key) =>
    state.reconciliations.filter((item) => item.date.slice(0, 10) === key).length
  );

  const pendingStatuses = new Set(['pending', 'suggested', 'flagged']);
  const pendientes = keys.map((key) =>
    state.transactions.filter(
      (tx) => pendingStatuses.has(tx.status) && tx.date.slice(0, 10) === key
    ).length
  );

  return { labels, conciliations, pendientes };
}

function closeAlert(alert) {
  if (!alert || alert.dataset.dismissed === 'true') {
    return;
  }

  alert.dataset.dismissed = 'true';
  alert.classList.remove('show');
  alert.classList.add('fade');
  window.setTimeout(() => {
    alert.remove();
  }, 200);
}

function initFlashAlerts() {
  const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
  alerts.forEach((alert) => {
    const dismissBtn = alert.querySelector('[data-dismiss="alert"]');
    if (dismissBtn) {
      dismissBtn.addEventListener('click', () => closeAlert(alert));
    }

    const delay = Number(alert.dataset.autoDismiss) || 6000;
    window.setTimeout(() => closeAlert(alert), delay);
  });
}

function renderTrendChart(rangeDays = 7) {
  const canvas = document.getElementById('trend-chart');
  const ChartLib = window.Chart;
  if (!canvas || !ChartLib) return;

  const { labels, conciliations, pendientes } = buildTrendSeries(rangeDays);

  const datasets = [
    {
      label: 'Conciliaciones',
      data: conciliations,
      borderColor: 'rgba(40, 167, 69, 1)',
      backgroundColor: 'rgba(40, 167, 69, 0.25)',
      tension: 0.35,
      fill: true,
      pointRadius: 4
    },
    {
      label: 'Pendientes',
      data: pendientes,
      borderColor: 'rgba(60, 141, 188, 1)',
      backgroundColor: 'rgba(60, 141, 188, 0.2)',
      tension: 0.35,
      fill: true,
      pointRadius: 4
    }
  ];

  if (trendChart) {
    trendChart.data.labels = labels;
    trendChart.data.datasets[0].data = datasets[0].data;
    trendChart.data.datasets[1].data = datasets[1].data;
    trendChart.update();
    return;
  }

  trendChart = new ChartLib(canvas.getContext('2d'), {
    type: 'line',
    data: {
      labels,
      datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          display: true,
          position: 'bottom'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      }
    }
  });
}

function showToast(message) {
  if (!toast) return;

  toast.textContent = message;
  toast.hidden = false;
  setTimeout(() => {
    toast.hidden = true;
  }, 2400);
}

function applyUserProfile(user) {
  if (!user) return;

  state.ui.currentUser = {
    name: user.name || '',
    role: user.role || '',
    email: user.email || ''
  };

  if (userNameLabel) {
    userNameLabel.textContent = state.ui.currentUser.name || 'Usuario';
  }
  if (userRoleLabel) {
    userRoleLabel.textContent = state.ui.currentUser.role || 'Sin rol';
  }
  if (userAvatar) {
    const initials = state.ui.currentUser.name
      .split(' ')
      .filter(Boolean)
      .map((part) => part[0])
      .join('')
      .slice(0, 2)
      .toUpperCase();
    userAvatar.textContent = initials || 'UM';
  }
}

function switchView(target) {
  const targetView = target || DEFAULT_PANEL_VIEW;
  views.forEach((view) => {
    view.classList.toggle('d-none', view.dataset.view !== targetView);
  });

  navLinks.forEach((link) => {
    link.classList.toggle('active', link.dataset.target === targetView);
  });

  if (targetView === 'matching') {
    renderTransactionList();
  }
}

function updateViewUrl(href, target) {
  const targetView = target || DEFAULT_PANEL_VIEW;

  if (href) {
    window.history.replaceState({}, '', href);
    return;
  }

  const url = new URL(window.location.href);

  if (targetView && targetView !== DEFAULT_PANEL_VIEW) {
    url.searchParams.set('view', targetView);
  } else {
    url.searchParams.delete('view');
  }

  window.history.replaceState({}, '', url);
}

function populateFilters() {
  const reconciliationSelect = document.getElementById('reconciliation-bank');

  if (!bankFilter && !reconciliationSelect) {
    return;
  }

  if (bankFilter) {
    [...bankFilter.querySelectorAll('option[data-dynamic]')].forEach((option) => option.remove());
  }

  if (reconciliationSelect) {
    [...reconciliationSelect.querySelectorAll('option[data-dynamic]')].forEach((option) => option.remove());
  }

  state.banks.forEach((bank) => {
    if (bankFilter) {
      const option = document.createElement('option');
      option.value = bank.id;
      option.textContent = bank.name;
      option.dataset.dynamic = 'true';
      bankFilter.appendChild(option);
    }

    if (reconciliationSelect) {
      const option = document.createElement('option');
      option.value = bank.id;
      option.textContent = bank.name;
      option.dataset.dynamic = 'true';
      reconciliationSelect.appendChild(option);
    }
  });
}

function updateDashboard() {
  const pending = state.transactions.filter((tx) => tx.status === 'pending').length;
  const suggested = state.transactions.filter((tx) => tx.status === 'suggested').length;
  const flagged = state.transactions.filter((tx) => tx.status === 'flagged').length;

  const pendingEl = document.getElementById('pending-count');
  const suggestedEl = document.getElementById('suggested-count');
  const matchedEl = document.getElementById('matched-count');
  const alertsEl = document.getElementById('alerts-count');
  const transactionEl = document.getElementById('transaction-count');

  if (pendingEl) pendingEl.textContent = pending;
  if (suggestedEl) suggestedEl.textContent = suggested;
  if (matchedEl) matchedEl.textContent = state.reconciliations.length;
  if (alertsEl) alertsEl.textContent = flagged;
  if (transactionEl) transactionEl.textContent = pending + suggested + flagged;
}

function renderAlerts() {
  if (!alertList || !alertEmpty) {
    return;
  }

  const alerts = state.transactions.filter((tx) => tx.alert);
  alertList.innerHTML = '';

  if (!alerts.length) {
    alertEmpty.classList.remove('d-none');
    alertList.classList.add('d-none');
    return;
  }

  alertEmpty.classList.add('d-none');
  alertList.classList.remove('d-none');

  alerts.forEach((tx) => {
    const studentName = getStudentName(tx);
    const item = document.createElement('li');
    item.className = 'alert-item';
    item.innerHTML = `
      <div>
        <strong>${studentName}</strong>
        <p>${tx.alert}</p>
      </div>
      <span>${formatCurrency(tx.amount)}</span>
    `;
    alertList.appendChild(item);
  });
}

function renderTransactionList() {
  if (!transactionList) {
    return;
  }

  const selectedBank = bankFilter ? bankFilter.value : '';
  const selectedStatus = statusFilter ? statusFilter.value : '';
  const query = ((globalSearch && globalSearch.value) || '').toLowerCase();

  const filtered = state.transactions.filter((tx) => {
    const studentName = getStudentName(tx).toLowerCase();
    const matchBank = !selectedBank || tx.bankId === selectedBank;
    const matchStatus = selectedStatus ? tx.status === selectedStatus : true;
    const matchQuery = !query ||
      studentName.includes(query) ||
      (tx.enrollment || '').toLowerCase().includes(query) ||
      (tx.id || '').toLowerCase().includes(query) ||
      tx.reference.toLowerCase().includes(query);
    return matchBank && matchStatus && matchQuery;
  });

  transactionList.innerHTML = '';

  if (!filtered.length) {
    transactionList.innerHTML = '<div class="empty-state">No hay transacciones con los filtros actuales.</div>';
    return;
  }

  filtered.forEach((tx) => {
    const studentName = getStudentName(tx);
    const bankLabel = tx.bankName || getBankName(tx.bankId) || 'Sin banco';
    const officeLabel = tx.office || bankLabel;
    const timeLabel = tx.transaction_time ? formatClock(tx.transaction_time) : formatTime(tx.date);
    const item = document.createElement('article');
    item.className = 'list-item';
    item.dataset.id = tx.id;
    if (state.ui.selectedTransaction === tx.id) {
      item.classList.add('active');
    }

    item.innerHTML = `
      <div class="list-meta">
        <span>${bankLabel}</span>
        <span>${timeLabel}</span>
      </div>
      <strong>${formatCurrency(tx.amount)}</strong>
      <div class="list-meta">
        <span>${studentName} · ${tx.enrollment}</span>
        <span class="badge ${getStatusBadgeClass(tx.status)}">${formatStatus(tx.status)}</span>
      </div>
      <div class="list-meta">
        <span>${tx.operation_number || '—'}</span>
        <span>${officeLabel}</span>
      </div>
    `;

    item.addEventListener('click', () => {
      state.ui.selectedTransaction = tx.id;
      state.ui.selectedVoucher = null;
      renderTransactionList();
      renderVoucherList();
      renderMatchDetail();
    });

    transactionList.appendChild(item);
  });
}

function getVoucherSuggestions(transaction) {
  if (!transaction) return [];

  const candidates = state.vouchers.filter((voucher) => voucher.status !== 'conciliado');

  return candidates
    .map((voucher) => {
      const amountDifference = Math.abs(voucher.amount - transaction.amount);
      const isSameStudent = voucher.studentId === transaction.studentId;
      const sameOperation =
        transaction.operation_number &&
        voucher.operation_number &&
        voucher.operation_number === transaction.operation_number;
      const score =
        (isSameStudent ? 2 : 0) +
        (amountDifference < 1 ? 2 : amountDifference < 10 ? 1 : 0) +
        (sameOperation ? 3 : 0);
      return { voucher, score, amountDifference };
    })
    .sort((a, b) => b.score - a.score || a.amountDifference - b.amountDifference)
    .map((item) => ({
      ...item.voucher,
      rank: item.score,
      amountDifference: item.amountDifference,
    }));
}

function renderVoucherList() {
  if (!voucherList) {
    return;
  }

  voucherList.innerHTML = '';
  const transaction = state.transactions.find((tx) => tx.id === state.ui.selectedTransaction);
  const voucherCountLabel = document.getElementById('voucher-count');

  if (!transaction) {
    if (voucherCountLabel) {
      voucherCountLabel.textContent = 0;
    }
    voucherList.innerHTML = '<div class="empty-state" data-empty="voucher">Selecciona una transacción para ver sugerencias.</div>';
    return;
  }

  const suggestions = getVoucherSuggestions(transaction);
  if (voucherCountLabel) {
    voucherCountLabel.textContent = suggestions.length;
  }

  if (!suggestions.length) {
    voucherList.innerHTML = '<div class="empty-state">No hay vouchers compatibles. Revisa manualmente.</div>';
    return;
  }

  suggestions.forEach((voucher) => {
    const studentName = getStudentName(voucher);
    const voucherDate = voucher.paymentDate || voucher.issueDate;
    const item = document.createElement('article');
    item.className = 'list-item';
    item.dataset.id = voucher.id;
    if (state.ui.selectedVoucher === voucher.id) {
      item.classList.add('active');
    }

    item.innerHTML = `
      <div class="list-meta">
        <span>${voucher.id}</span>
        <span>${voucherDate ? formatDate(voucherDate) : '—'}</span>
      </div>
      <strong>${formatCurrency(voucher.amount)}</strong>
      <div class="list-meta">
        <span>${studentName} · ${voucher.enrollment}</span>
        <span class="badge suggested">Nivel ${voucher.rank}</span>
      </div>
    `;

    item.addEventListener('click', () => {
      state.ui.selectedVoucher = voucher.id;
      renderVoucherList();
      renderMatchDetail();
    });

    voucherList.appendChild(item);
  });
}

function renderMatchDetail() {
  if (!matchDetail) {
    return;
  }

  matchDetail.innerHTML = '';
  const transaction = state.transactions.find((tx) => tx.id === state.ui.selectedTransaction);
  const voucher = state.vouchers.find((vc) => vc.id === state.ui.selectedVoucher);

  if (!transaction) {
    matchDetail.innerHTML = '<div class="empty-state" data-empty="detail">Selecciona transacción y voucher para revisar coincidencia.</div>';
    return;
  }

  const transactionName = getStudentName(transaction);
  const voucherName = getStudentName(voucher);
  const transactionBank = transaction.bankName || getBankName(transaction.bankId) || 'Sin banco';
  const voucherBank = voucher ? (voucher.bankName || getBankName(voucher.bankId) || 'Sin banco') : 'Sin banco';
  const voucherDate = voucher ? (voucher.paymentDate || voucher.issueDate) : null;
  const debitDisplay = transaction.debit_amount !== null ? formatCurrency(transaction.debit_amount) : '—';
  const creditDisplay = transaction.credit_amount !== null ? formatCurrency(transaction.credit_amount) : '—';
  const balanceDisplay =
    transaction.running_balance !== null ? formatCurrency(transaction.running_balance) : '—';
  const clockDisplay = transaction.transaction_time
    ? formatClock(transaction.transaction_time)
    : formatTime(transaction.date);

  const txCard = document.createElement('div');
  txCard.className = 'detail-card';
  txCard.innerHTML = `
    <div class="detail-header">
      <h4>Transacción ${transaction.id}</h4>
      <span class="badge ${getStatusBadgeClass(transaction.status)}">${formatStatus(transaction.status)}</span>
    </div>
    <div class="detail-grid">
      <div>
        <span class="detail-label">Estudiante</span>
        <p class="detail-value">${transactionName}</p>
      </div>
      <div>
        <span class="detail-label">Código</span>
        <p class="detail-value">${transaction.enrollment}</p>
      </div>
      <div>
        <span class="detail-label">Monto</span>
        <p class="detail-value">${formatCurrency(transaction.amount)}</p>
      </div>
      <div>
        <span class="detail-label">Banco</span>
        <p class="detail-value">${transactionBank}</p>
      </div>
      <div>
        <span class="detail-label">Fecha</span>
        <p class="detail-value">${formatDate(transaction.date)} · ${clockDisplay}</p>
      </div>
      <div>
        <span class="detail-label">Oficina</span>
        <p class="detail-value">${transaction.office || '—'}</p>
      </div>
      <div>
        <span class="detail-label">Detalle</span>
        <p class="detail-value">${transaction.reference}</p>
      </div>
      <div>
        <span class="detail-label">N.º operación</span>
        <p class="detail-value">${transaction.operation_number || '—'}</p>
      </div>
      <div>
        <span class="detail-label">Débito</span>
        <p class="detail-value">${debitDisplay}</p>
      </div>
      <div>
        <span class="detail-label">Crédito</span>
        <p class="detail-value">${creditDisplay}</p>
      </div>
      <div>
        <span class="detail-label">Saldo</span>
        <p class="detail-value">${balanceDisplay}</p>
      </div>
    </div>
  `;
  matchDetail.appendChild(txCard);

  if (!voucher) {
    const info = document.createElement('div');
    info.className = 'empty-state';
    info.textContent = 'Selecciona un voucher para comparar.';
    matchDetail.appendChild(info);
    return;
  }

  const difference = Number((transaction.amount - voucher.amount).toFixed(2));
  const hasOverpayment = difference > 0;
  const shortage = difference < 0 && Math.abs(difference) >= shortageThreshold;

  const voucherCard = document.createElement('div');
  voucherCard.className = 'detail-card';
  voucherCard.innerHTML = `
    <div class="detail-header">
      <h4>Voucher ${voucher.id}</h4>
      <span class="badge suggested">Sugerido</span>
    </div>
    <div class="detail-grid">
      <div>
        <span class="detail-label">Estudiante</span>
        <p class="detail-value">${voucherName}</p>
      </div>
      <div>
        <span class="detail-label">Código</span>
        <p class="detail-value">${voucher.enrollment}</p>
      </div>
      <div>
        <span class="detail-label">Monto</span>
        <p class="detail-value">${formatCurrency(voucher.amount)}</p>
      </div>
      <div>
        <span class="detail-label">Fecha de pago</span>
        <p class="detail-value">${voucherDate ? formatDate(voucherDate) : '—'}</p>
      </div>
      <div>
        <span class="detail-label">Banco</span>
        <p class="detail-value">${voucherBank}</p>
      </div>
      <div>
        <span class="detail-label">N.º operación</span>
        <p class="detail-value">${voucher.operation_number || '—'}</p>
      </div>
    </div>
    <div class="mt-3">
      <span class="detail-label d-block">Diferencia detectada</span>
      <p class="detail-value ${difference > 0 ? 'text-success' : difference < 0 ? 'text-danger' : ''}">
        ${formatCurrency(difference)}
      </p>
    </div>
  `;
  matchDetail.appendChild(voucherCard);

  if (shortage) {
    const warning = document.createElement('div');
    warning.className = 'alert alert-warning';
    warning.innerHTML = `
      <strong>Atención:</strong> El voucher es menor que el monto registrado por el banco. Solicita al estudiante completar el pago o adjuntar el comprobante correcto.
    `;
    matchDetail.appendChild(warning);
  }

  const actionCard = document.createElement('div');
  actionCard.className = 'detail-card';
  actionCard.innerHTML = `
    <p class="mb-3">
      ${hasOverpayment
        ? `Hay un excedente de ${formatCurrency(difference)}. Puedes registrarlo como demasía para acreditarlo al estudiante.`
        : 'Revisa la información para confirmar o rechazar la conciliación.'}
    </p>
    <div class="btn-group btn-group-sm flex-wrap" role="group">
      <button class="btn btn-outline-danger" id="reject-match">Rechazar</button>
      ${hasOverpayment ? '<button class="btn btn-outline-warning" id="overpay-match">Marcar demasía</button>' : ''}
      <button class="btn btn-brand" id="open-modal">Confirmar coincidencia</button>
    </div>
  `;
  matchDetail.appendChild(actionCard);

  const rejectBtn = actionCard.querySelector('#reject-match');
  if (rejectBtn) {
    rejectBtn.addEventListener('click', () => rejectMatch());
  }

  const overpayBtn = actionCard.querySelector('#overpay-match');
  if (overpayBtn) {
    overpayBtn.addEventListener('click', () => creditMatch(difference));
  }

  actionCard.querySelector('#open-modal').addEventListener('click', () => openModal(transaction, voucher));
}

function openModal(transaction, voucher) {
  const transactionName = getStudentName(transaction);
  const voucherName = getStudentName(voucher);
  modalBody.innerHTML = `
    <div>
      <h4>Transacción</h4>
      <p><strong>${transactionName}</strong> · ${transaction.enrollment}</p>
      <p>${formatCurrency(transaction.amount)} · ${formatDate(transaction.date)}</p>
      <p><small class="text-muted">N.º operación: ${transaction.operation_number || '—'}</small></p>
    </div>
    <div>
      <h4>Voucher</h4>
      <p><strong>${voucherName}</strong> · ${voucher.enrollment}</p>
      <p>${formatCurrency(voucher.amount)} · ${formatDate(voucher.paymentDate || voucher.issueDate)}</p>
      <p><small class="text-muted">N.º operación: ${voucher.operation_number || '—'}</small></p>
    </div>
  `;
  showModal();
}

function closeModal() {
  hideModal();
}

function showModal() {
  if (!modal) {
    return;
  }

  modal.classList.add('show');
  modal.style.display = 'block';
  modal.removeAttribute('aria-hidden');
  modal.setAttribute('aria-modal', 'true');
  modal.scrollTop = 0;

  if (!modalBackdrop) {
    modalBackdrop = document.createElement('div');
    modalBackdrop.className = 'modal-backdrop fade show';
    document.body.appendChild(modalBackdrop);
  }
}

function hideModal() {
  if (!modal) {
    return;
  }

  modal.classList.remove('show');
  modal.style.display = 'none';
  modal.setAttribute('aria-hidden', 'true');
  modal.removeAttribute('aria-modal');

  if (modalBackdrop) {
    modalBackdrop.remove();
    modalBackdrop = null;
  }
}

function confirmMatch() {
  submitMatchAction('confirm');
}

function rejectMatch() {
  const reason = prompt('Motivo del rechazo', 'Datos del voucher no coinciden con el extracto.');
  if (reason === null) {
    return;
  }

  submitMatchAction('reject', { reason });
}

function creditMatch(defaultAmount) {
  const creditAmount = Number(defaultAmount);
  if (!Number.isFinite(creditAmount) || creditAmount <= 0) {
    showToast('No existe excedente para acreditar.');
    return;
  }

  if (creditAmount > creditLimit) {
    showToast(`El crédito no puede exceder ${creditLimit.toFixed(2)} Bs.`);
    return;
  }

  submitMatchAction('credit', { credit_amount: creditAmount });
}

async function submitMatchAction(action, extraPayload = {}) {
  const transaction = state.transactions.find((tx) => tx.id === state.ui.selectedTransaction);
  const voucher = state.vouchers.find((vc) => vc.id === state.ui.selectedVoucher);

  if (!transaction || !voucher) {
    showToast('Selecciona una transacción y un voucher.');
    return;
  }

  if (!confirmMatchUrl) {
    showToast('La ruta de conciliación no está configurada.');
    return;
  }

  const payload = {
    action,
    bank_statement_line_id: transaction.db_id ?? transaction.dbId ?? transaction.lineId ?? transaction.id,
    payment_voucher_id: voucher.db_id ?? voucher.dbId ?? voucher.id,
    ...extraPayload,
  };

  try {
    const response = await fetch(confirmMatchUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok) {
      showToast(data.message || 'No se pudo actualizar la conciliación.');
      return;
    }

    applyMatchResult(data);
    closeModal();
  } catch (error) {
    console.error(error);
    showToast('Ocurrió un error al procesar la acción.');
  }
}

function applyMatchResult(data) {
  const action = data.action || 'confirm';

  if (action === 'reject') {
    if (data.transaction) {
      const txIndex = state.transactions.findIndex(
        (tx) => tx.db_id === data.transaction.db_id || tx.id === data.transaction.id
      );
      if (txIndex !== -1) {
        state.transactions[txIndex] = {
          ...state.transactions[txIndex],
          status: 'pending',
          alert: data.message || 'Rechazado manualmente.',
        };
      }
    }

    if (data.voucher) {
      state.vouchers = state.vouchers.filter(
        (voucher) => voucher.db_id !== data.voucher.db_id && voucher.id !== data.voucher.id
      );

      updateRecentVoucherStatus(
        extractVoucherDbId(data.voucher),
        'rechazado',
        data.voucher?.reason || 'Rechazado en conciliación'
      );
    }
  } else {
    if (data.transaction) {
      state.transactions = state.transactions.filter(
        (tx) => tx.db_id !== data.transaction.db_id && tx.id !== data.transaction.id
      );
    }

    if (data.voucher) {
      state.vouchers = state.vouchers.filter(
        (voucher) => voucher.db_id !== data.voucher.db_id && voucher.id !== data.voucher.id
      );

      const newStatus = action === 'credit' ? 'demasia' : 'conciliado';
      const reason =
        action === 'credit'
          ? data.voucher?.reason || 'Pago en demasía'
          : action === 'reject'
            ? (data.voucher?.reason || '')
            : '';
      updateRecentVoucherStatus(extractVoucherDbId(data.voucher), newStatus, reason);
    }

    if (data.reconciliation) {
      const reconciliation = {
        ...data.reconciliation,
      };
      if (!reconciliation.student_name && reconciliation.student) {
        reconciliation.student_name = reconciliation.student;
      }
      if (reconciliation.status) {
        reconciliation.status = reconciliation.status.toLowerCase();
      }
      state.reconciliations.unshift(reconciliation);
    }
  }

  state.ui.selectedTransaction = null;
  state.ui.selectedVoucher = null;

  renderTransactionList();
  renderVoucherList();
  renderMatchDetail();
  renderReconciliations();
  renderStudents();
  renderTrendChart(currentTrendRange);
  updateDashboard();

  showToast(data.message || 'Acción aplicada.');
}

function renderReconciliations() {
  if (!reconciliationTable) {
    return;
  }

  reconciliationTable.innerHTML = '';
  state.reconciliations.forEach((item) => {
    const studentName = getStudentName(item);
    const statusClass = getStatusBadgeClass(item.status);
    const billingStatus = item.billing_status || 'pendiente';
    const billingClass = getStatusBadgeClass(billingStatus);
    const row = document.createElement('tr');
    let billingCell = `<span class="badge ${billingClass}">${formatStatus(billingStatus)}</span>`;

    row.innerHTML = `
      <td>${formatDate(item.date)}</td>
      <td>${getBankName(item.bankId)}</td>
      <td>${studentName}</td>
      <td>${formatCurrency(item.amount)}</td>
      <td>${billingCell}</td>
      <td><span class="badge ${statusClass}">${formatStatus(item.status)}</span></td>
    `;
    reconciliationTable.appendChild(row);
  });
}

function renderStudents() {
  if (!studentTable) {
    return;
  }

  studentTable.innerHTML = '';
  const query = (document.getElementById('student-search').value || '').toLowerCase();

  state.students
    .filter((student) => {
      if (!query) {
        return true;
      }
      return student.name.toLowerCase().includes(query) ||
        (student.enrollment || '').toLowerCase().includes(query);
    })
    .forEach((student) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${student.name}</td>
        <td>${student.enrollment}</td>
        <td>${formatDate(student.lastPayment)}</td>
      `;
      studentTable.appendChild(row);
    });
}

function renderBanks() {
  if (!bankGrid) {
    return;
  }

  bankGrid.innerHTML = '';
  state.banks.forEach((bank) => {
    const col = document.createElement('div');
    col.className = 'col-xl-4 col-md-6 mb-3';
    col.innerHTML = `
      <div class="card shadow-none bank-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-0">${bank.name}</h5>
            <span class="badge ${bank.status === 'conectado' ? 'badge-success' : 'badge-warning'}">${bank.status === 'conectado' ? 'Conectado' : 'Revisión'}</span>
          </div>
          <p class="mb-1">Pendientes: <strong>${bank.pending}</strong></p>
          <p class="mb-3 text-muted">Última sincronización: ${bank.lastSync}</p>
          <button class="btn btn-sm btn-outline-brand" disabled>Configurar</button>
        </div>
      </div>
    `;
    bankGrid.appendChild(col);
  });
}

function initNavigation() {
  if (!navLinks.length || !views.length) {
    return;
  }

  navLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = link.dataset.target;
      if (!target) {
        return;
      }

      event.preventDefault();
      const href = link.getAttribute('href');
      switchView(target);
      updateViewUrl(href && href !== '#' ? href : null, target);
    });
  });
}

function initFilters() {
  if (bankFilter) {
    bankFilter.addEventListener('change', () => {
      clearSummaryFilterState();
      renderTransactionList();
    });
  }
  if (statusFilter) {
    statusFilter.addEventListener('change', () => {
      clearSummaryFilterState();
      renderTransactionList();
    });
  }
  if (globalSearch) {
    globalSearch.addEventListener('input', renderTransactionList);
    if (globalSearch.form) {
      globalSearch.form.addEventListener('submit', (event) => event.preventDefault());
    }
  }
  const studentSearch = document.getElementById('student-search');
  if (studentSearch) {
    studentSearch.addEventListener('input', renderStudents);
  }
}

function initModal() {
  if (!modal) {
    return;
  }

  const closeTriggers = modal.querySelectorAll('[data-dismiss="modal"], [data-close-modal]');
  closeTriggers.forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.preventDefault();
      closeModal();
    });
  });

  if (confirmMatchBtn) {
    confirmMatchBtn.addEventListener('click', confirmMatch);
  }

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('show')) {
      closeModal();
    }
  });
}

function initTrendControls() {
  if (!rangeButtons.length) {
    renderTrendChart(currentTrendRange);
    return;
  }

  rangeButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      rangeButtons.forEach((btn) => btn.classList.remove('active'));
      button.classList.add('active');
      currentTrendRange = Number(button.dataset.range) || 7;
      renderTrendChart(currentTrendRange);
    });
  });

  const activeButton = Array.from(rangeButtons).find((button) => button.classList.contains('active'));
  currentTrendRange = activeButton ? Number(activeButton.dataset.range) || 7 : currentTrendRange;
  renderTrendChart(currentTrendRange);
}

function hydrateCurrentUser() {
  if (!appWrapper) return;

  const datasetUser = {
    name: appWrapper.dataset.userName || '',
    role: appWrapper.dataset.userRole || '',
    email: appWrapper.dataset.userEmail || ''
  };

  applyUserProfile(datasetUser);
}

function clearSummaryFilterState() {
  summaryCards.forEach((card) => card.classList.remove('active-filter'));
}

function initSummaryFilters() {
  if (!summaryCards.length) {
    return;
  }

  summaryCards.forEach((card) => {
    card.addEventListener('click', () => {
      const targetView = card.dataset.filterView || '';
      const status = card.dataset.filterStatus || '';

      clearSummaryFilterState();
      card.classList.add('active-filter');

      if (targetView) {
        switchView(targetView);
      }

      if (targetView === 'matching' && statusFilter) {
        statusFilter.value = status;
        renderTransactionList();
      }

      if (targetView === 'matching') {
        const matchingSection = document.querySelector('[data-view="matching"]');
        if (matchingSection) {
          matchingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }

      if (targetView === 'reconciliations') {
        const reconciliationSection = document.querySelector('[data-view="reconciliations"]');
        if (reconciliationSection) {
          reconciliationSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });
}

function init() {
  if (!appWrapper) {
    return;
  }

  if (views.length) {
    switchView(initialPanelView);
  }

  hydrateCurrentUser();
  initFlashAlerts();
  populateFilters();
  updateDashboard();
  renderAlerts();
  renderTransactionList();
  renderVoucherList();
  renderMatchDetail();
  renderReconciliations();
  renderStudents();
  renderBanks();
  initNavigation();
  initFilters();
  initModal();
  initTrendControls();
  initSummaryFilters();
}

document.addEventListener('DOMContentLoaded', init);

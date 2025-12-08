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
  reportEntries: [
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
      entryId: 'sr-8801',
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
      entryId: 'sr-8802',
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
  ],
  overpayments: [
    {
      id: 'sb-001',
      student_name: 'Andrea López',
      balance: 150,
      credited_at: '2024-05-28 10:00:00'
    },
    {
      id: 'sb-002',
      student_name: 'Carlos Jiménez',
      balance: 80.5,
      credited_at: '2024-05-27 09:15:00'
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
  reportEntries: serverMatchingData?.report_entries ?? DEFAULT_STATE.reportEntries,
  reconciliations: serverMatchingData?.reconciliations ?? DEFAULT_STATE.reconciliations,
  students: serverMatchingData?.students ?? DEFAULT_STATE.students,
  overpayments: serverMatchingData?.overpayments ?? DEFAULT_STATE.overpayments,
  pendingEntryUpdates: {},
  pendingTransactionUpdates: {},
  ui: {
    selectedTransaction: null,
    selectedEntry: null,
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

function extractEntryDbId(entry) {
  if (!entry) {
    return null;
  }

  if (entry.db_id) {
    return String(entry.db_id);
  }

  if (entry.id) {
    const id = String(entry.id);
    if (id.startsWith('sr-')) {
      return id.slice(3);
    }

    return id;
  }

  return null;
}

function updateReportRowStatus(entryDbId, status, reason = '') {
  if (!entryDbId) {
    return;
  }

  const row = document.querySelector(`#voucher-table tr[data-voucher-id="${entryDbId}"]`);
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

function getEntryNumericId(entry) {
  if (!entry) {
    return null;
  }

  if (entry.db_id) {
    return String(entry.db_id);
  }

  if (entry.id) {
    const raw = String(entry.id);
    return raw.startsWith('sr-') ? raw.slice(3) : raw;
  }

  return null;
}

function resolveEntryUpdateUrl(entry) {
  const entryId = getEntryNumericId(entry);
  if (!entryId) {
    return null;
  }

  if (entry?.update_url) {
    return entry.update_url;
  }

  if (salesReportBaseUrl) {
    return `${salesReportBaseUrl}/${entryId}`;
  }

  return null;
}

function resolveTransactionUpdateUrl(transaction) {
  const lineId = transaction?.db_id ?? transaction?.dbId ?? transaction?.lineId ?? null;
  if (!lineId) {
    return null;
  }

  if (transaction?.update_url) {
    return transaction.update_url;
  }

  if (statementBaseUrl) {
    return `${statementBaseUrl}/${lineId}`;
  }

  return null;
}

async function persistEntryField(entry, field, value, { silent = false } = {}) {
  const entryId = getEntryNumericId(entry);
  if (!entryId) {
    showToast('No se pudo identificar el registro diario.');
    return;
  }

  state.pendingEntryUpdates[entryId] = state.pendingEntryUpdates[entryId] || {};
  state.pendingEntryUpdates[entryId][field] = value;

  const idx = state.reportEntries.findIndex((item) => getEntryNumericId(item) === entryId);
  if (idx !== -1) {
    state.reportEntries[idx][field] = value;
    if (field === 'bank_name') {
      state.reportEntries[idx].bankName = value;
    }
    if (field === 'custom_id') {
      state.reportEntries[idx].enrollment = value || '—';
    }
    if (field === 'operation_reference') {
      state.reportEntries[idx].operation_number = value;
    }
    if (field === 'recorded_date') {
      state.reportEntries[idx].recorded_date = value;
    }
  }

  if (!silent) {
    showToast('Campo actualizado. Se guardará al confirmar.');
  }
}

function attachEntryFieldListeners(container, entry) {
  container.querySelectorAll('[data-entry-field]').forEach((input) => {
    input.addEventListener('input', () => {
      const field = input.dataset.entryField;
      const newValue = input.value;
      persistEntryField(entry, field, newValue, { silent: true });
    });
    input.addEventListener('change', () => {
      const field = input.dataset.entryField;
      const newValue = input.value;
      persistEntryField(entry, field, newValue);
    });
    input.addEventListener('click', (event) => event.stopPropagation());
  });
}

async function persistTransactionField(transaction, field, value, { silent = false } = {}) {
  const lineId = transaction?.db_id ?? transaction?.dbId ?? transaction?.lineId ?? null;
  if (!lineId) {
    showToast('No se pudo identificar la transacción del extracto.');
    return;
  }

  state.pendingTransactionUpdates[lineId] = state.pendingTransactionUpdates[lineId] || {};
  state.pendingTransactionUpdates[lineId][field] = value;

  const txIndex = state.transactions.findIndex((item) => item.db_id === lineId || item.id === transaction.id);
  if (txIndex !== -1) {
    if (field === 'custom_identifier') {
      state.transactions[txIndex].custom_id = value;
    } else if (field === 'billing_reference_date') {
      state.transactions[txIndex].billing_reference_date = value;
    } else {
      state.transactions[txIndex][field] = value;
    }
  }

  if (!silent) {
    showToast('Campo actualizado. Se guardará al confirmar.');
  }
}

function attachTransactionFieldListeners(container, transaction) {
  container.querySelectorAll('[data-transaction-field]').forEach((input) => {
    input.addEventListener('input', () => {
      const field = input.dataset.transactionField;
      const newValue = input.value;
      persistTransactionField(transaction, field, newValue, { silent: true });
      maybeScrollToEntryEditors(container);
    });
    input.addEventListener('change', () => {
      const field = input.dataset.transactionField;
      const newValue = input.value;
      persistTransactionField(transaction, field, newValue);
      maybeScrollToEntryEditors(container);
    });
    input.addEventListener('click', (event) => event.stopPropagation());
  });
}

function maybeScrollToEntryEditors(container) {
  const idInput = container.querySelector('[data-transaction-field="custom_identifier"]');
  const dateInput = container.querySelector('[data-transaction-field="billing_reference_date"]');
  const hasId = idInput && idInput.value.trim() !== '';
  const hasDate = dateInput && dateInput.value.trim() !== '';
  if (hasId && hasDate && matchDetail) {
    const entryEditor = matchDetail.querySelector('[data-entry-field]');
    if (entryEditor) {
      entryEditor.closest('.detail-card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

function escapeHtml(value) {
  if (value === null || value === undefined) {
    return '';
  }

  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
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
  (state.reportEntries || []).forEach((voucher) => assignName(voucher, 'student_code'));
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
const transactionList = document.getElementById('transaction-list');
const reportList = document.getElementById('report-entry-list');
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
const appOrigin = window.location.origin;

function resolveBaseUrl(value, fallbackPath) {
  const safeFallback = `${appOrigin}${fallbackPath}`;
  if (!value) {
    return safeFallback;
  }

  try {
    const parsed = new URL(value, appOrigin);
    if (!parsed.pathname.startsWith(fallbackPath)) {
      return safeFallback;
    }
    return `${parsed.origin}${parsed.pathname}`;
  } catch (error) {
    console.warn('URL inválida para base de datos:', value, error);
    return safeFallback;
  }
}

const confirmMatchUrl = resolveBaseUrl(appWrapper?.dataset.confirmUrl, '/ingresos/conciliacion/confirmar');
const salesReportBaseUrl = resolveBaseUrl(appWrapper?.dataset.salesReportUrl, '/ingresos/reporte-diario');
const statementBaseUrl = resolveBaseUrl(appWrapper?.dataset.statementUrl, '/ingresos/extractos');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const diffThreshold = Number(appWrapper?.dataset.diffThreshold || 1);
const shortageThreshold = Number(appWrapper?.dataset.shortageThreshold || diffThreshold);
let creditLimit = Number(appWrapper?.dataset.creditLimit || 0);
const hasCreditLimit = Number.isFinite(creditLimit) && creditLimit > 0;
if (!hasCreditLimit) {
  creditLimit = Infinity;
}
let matchingBusy = false;
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

function parseDateValue(value) {
  if (!value) {
    return new Date();
  }

  if (typeof value === 'string') {
    const isoDateOnly = /^\d{4}-\d{2}-\d{2}$/;
    if (isoDateOnly.test(value)) {
      const [year, month, day] = value.split('-').map(Number);
      return new Date(year, month - 1, day);
    }
  }

  return new Date(value);
}

function formatDate(value) {
  return new Intl.DateTimeFormat('es-EC', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(parseDateValue(value));
}

function formatTime(value) {
  return new Intl.DateTimeFormat('es-EC', {
    hour: '2-digit',
    minute: '2-digit'
  }).format(parseDateValue(value));
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

function toggleMatchButtons(disabled) {
  const selectors = ['#confirm-match', '#reject-match', '#overpay-match', '#open-modal'];
  selectors.forEach((sel) => {
    document.querySelectorAll(sel).forEach((btn) => {
      btn.disabled = disabled;
      btn.classList.toggle('disabled', disabled);
    });
  });
}

function clearValidationMarks() {
  if (!matchDetail) return;
  matchDetail.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
}

function markInvalidSelectors(selectors) {
  if (!matchDetail || !selectors?.length) return;
  selectors.forEach((sel) => {
    const el = matchDetail.querySelector(sel);
    if (el) {
      el.classList.add('is-invalid');
    }
  });
  const first = matchDetail.querySelector('.is-invalid');
  if (first) {
    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
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
  const query = ((globalSearch && globalSearch.value) || '').toLowerCase();

  const filtered = state.transactions.filter((tx) => {
    const studentName = getStudentName(tx).toLowerCase();
    const matchBank = !selectedBank || tx.bankId === selectedBank;
    const matchQuery = !query ||
      studentName.includes(query) ||
      (tx.enrollment || '').toLowerCase().includes(query) ||
      (tx.id || '').toLowerCase().includes(query) ||
      tx.reference.toLowerCase().includes(query);
    return matchBank && matchQuery;
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
        <div>
          <strong>${studentName}</strong>
          <small>${bankLabel} · ${officeLabel}</small>
        </div>
        <div class="text-right">
          <span class="badge ${getStatusBadgeClass(tx.status)}">${formatStatus(tx.status)}</span>
          <small>${timeLabel}</small>
        </div>
      </div>
      <strong>${formatCurrency(tx.amount)}</strong>
      <div class="list-meta">
        <span>Operación: ${tx.operation_number || '—'}</span>
        <span>Fecha: ${formatDate(tx.date)}</span>
      </div>
    `;

    item.addEventListener('click', () => {
      state.ui.selectedTransaction = tx.id;
      state.ui.selectedEntry = null;
      renderTransactionList();
      renderEntryList();
      renderMatchDetail();
    });
    transactionList.appendChild(item);
  });
}

function getEntrySuggestions(transaction) {
  const entries = [...state.reportEntries];

  return entries
    .map((entry) => {
      const entryAmount = Number(entry.amount) || 0;
      const transactionAmount = transaction ? Number(transaction.amount) || 0 : 0;
      if (!transaction) {
        return {
          entry,
          score: 0,
          amountDifference: null,
          dateDifference: null,
        };
      }

      const amountDifference = Math.abs(entryAmount - transactionAmount);
      const isSameStudent = entry.studentId && transaction.studentId
        ? entry.studentId === transaction.studentId
        : false;
      const sameOperation =
        transaction.operation_number &&
        entry.operation_number &&
        entry.operation_number === transaction.operation_number;
      const sameBank =
        transaction.bankName &&
        entry.bankName &&
        transaction.bankName === entry.bankName;
      const entryDateRaw = entry.issueDate || entry.recorded_date;
      const txDateRaw = transaction.date;
      let dateDifference = null;
      let dateScore = 0;
      if (entryDateRaw && txDateRaw) {
        const entryDate = new Date(entryDateRaw);
        const txDate = new Date(txDateRaw);
        dateDifference = Math.abs(entryDate - txDate) / (1000 * 60 * 60 * 24);
        if (dateDifference < 0.1) {
          dateScore = 3;
        } else if (dateDifference <= 1) {
          dateScore = 2;
        } else if (dateDifference <= 3) {
          dateScore = 1;
        }
      }

      let amountScore = 0;
      if (amountDifference === 0) {
        amountScore = 4;
      } else if (amountDifference <= 5) {
        amountScore = 3;
      } else if (amountDifference <= 20) {
        amountScore = 1;
      } else if (amountDifference > 100) {
        amountScore = -2;
      } else {
        amountScore = 0;
      }

      const score =
        (isSameStudent ? 2 : 0) +
        amountScore +
        dateScore +
        (sameOperation ? 4 : 0) +
        (sameBank ? 1 : 0);

      return { entry, score, amountDifference, dateDifference };
    })
    .sort((a, b) => {
      if (transaction) {
        return b.score - a.score || a.amountDifference - b.amountDifference;
      }

      const dateA = a.entry.issueDate ? new Date(a.entry.issueDate).getTime() : 0;
      const dateB = b.entry.issueDate ? new Date(b.entry.issueDate).getTime() : 0;
      return dateB - dateA;
    })
    .map((item) => ({
      ...item.entry,
      rank: item.score,
      amountDifference: item.amountDifference,
      dateDifference: item.dateDifference,
    }));
}

function renderEntryList() {
  if (!reportList) {
    return;
  }

  reportList.innerHTML = '';
  const transaction = state.transactions.find((tx) => tx.id === state.ui.selectedTransaction);
  const countLabel = document.getElementById('report-count');

  const suggestions = getEntrySuggestions(transaction);
  if (countLabel) {
    countLabel.textContent = suggestions.length;
  }

  if (transaction) {
    const hasSelected = suggestions.some((entry) => entry.id === state.ui.selectedEntry);
    if (!hasSelected && suggestions.length) {
      state.ui.selectedEntry = suggestions[0].id;
    }
  }

  if (!suggestions.length) {
    reportList.innerHTML = '<div class="empty-state">No hay registros cargados. Importa el reporte diario para comenzar.</div>';
    return;
  }

  const notice = document.createElement('div');
  notice.className = 'small text-muted px-3 py-2';
  notice.textContent = transaction
    ? 'Ordenado por compatibilidad con el extracto seleccionado.'
    : 'Mostrando todos los registros del reporte diario.';
  reportList.appendChild(notice);

  suggestions.forEach((entry, index) => {
    const studentName = getStudentName(entry);
    const invoiceDate = entry.issueDate ? formatDate(entry.issueDate) : '—';
    const recordedDate = entry.recorded_date ? formatDate(entry.recorded_date) : 'Sin registrar';
    const badgeSuggestion = transaction && index === 0 ? '<span class="badge suggested">Sugerido</span>' : '';
    const statusBadge = `<span class="badge ${getStatusBadgeClass(entry.status)}">${formatStatus(entry.status)}</span>`;
    const item = document.createElement('article');
    item.className = 'list-item';
    item.dataset.id = entry.id;
    if (state.ui.selectedEntry === entry.id) {
      item.classList.add('active');
    }

    item.innerHTML = `
      <div class="list-meta">
        <div>
          <strong>Factura ${entry.invoice_number || '—'}</strong>
          <small>${invoiceDate}</small>
        </div>
        <div class="text-right">
          ${badgeSuggestion}
          ${statusBadge}
        </div>
      </div>
      <strong>${formatCurrency(entry.amount)}</strong>
      <div class="list-meta">
        <span>${escapeHtml(entry.razon_social || '—')}</span>
        <small>${studentName}</small>
      </div>
      <div class="list-meta small text-muted">
        <span>Tipo: ${escapeHtml(entry.payment_type || '—')}</span>
        <span>Cuenta: ${escapeHtml(entry.account || '—')}</span>
        <span>Operación: ${escapeHtml(entry.operation_reference || entry.operation_number || '—')}</span>
      </div>
    `;

    item.addEventListener('click', () => {
      state.ui.selectedEntry = entry.id;
      renderEntryList();
      renderMatchDetail();
    });
    reportList.appendChild(item);
  });
}

function renderMatchDetail() {
  if (!matchDetail) {
    return;
  }

  matchDetail.innerHTML = '';
  const transaction = state.transactions.find((tx) => tx.id === state.ui.selectedTransaction);
  const voucher = state.reportEntries.find((vc) => vc.id === state.ui.selectedEntry);

  if (!transaction) {
    matchDetail.innerHTML = '<div class="empty-state" data-empty="detail">Selecciona transacción y registro para revisar coincidencia.</div>';
    return;
  }

  const transactionName = getStudentName(transaction);
  const voucherName = getStudentName(voucher);
  const txAmount = Number(transaction.amount) || 0;
  const voucherAmount = voucher ? Number(voucher.amount) || 0 : 0;
  const debitDisplay = transaction.debit_amount !== null ? formatCurrency(transaction.debit_amount) : '—';
  const creditDisplay = transaction.credit_amount !== null ? formatCurrency(transaction.credit_amount) : '—';
  const balanceDisplay =
    transaction.running_balance !== null ? formatCurrency(transaction.running_balance) : '—';
  const clockDisplay = transaction.transaction_time
    ? formatClock(transaction.transaction_time)
    : formatTime(transaction.date);
  const entryIssueDate = voucher?.issueDate ? formatDate(voucher.issueDate) : '—';
  const entryRecordedValue = voucher?.recorded_date || '';
  const entryOperation = voucher?.operation_reference
    || voucher?.operation_number
    || transaction.operation_number
    || '';
  const transactionLineId = transaction?.db_id ?? transaction?.dbId ?? transaction?.lineId ?? null;
  const entryNumericId = getEntryNumericId(voucher);
  const transactionUpdateUrl =
    transaction?.update_url
    || (transactionLineId && statementBaseUrl ? `${statementBaseUrl}/${transactionLineId}` : '');
  const entryUpdateUrl =
    voucher?.update_url
    || (entryNumericId && salesReportBaseUrl ? `${salesReportBaseUrl}/${entryNumericId}` : '');

  const transactionColumns = [
    { label: 'Fecha', value: formatDate(transaction.date) },
    { label: 'Hora', value: clockDisplay },
    { label: 'N.º', value: transaction.operation_number || '—' },
    { label: 'Descripción', value: transaction.reference || '—' },
    { label: 'Débito', value: debitDisplay },
    { label: 'Crédito', value: creditDisplay },
    { label: 'Saldo', value: balanceDisplay },
  ];

  const txCard = document.createElement('div');
  txCard.className = 'detail-card';
  txCard.innerHTML = `
    <div class="detail-header">
      <h4>Transacción ${transaction.id}</h4>
    </div>
    <div class="detail-grid">
      ${transactionColumns
        .map(
          (col) => `
            <div>
              <span class="detail-label">${col.label}</span>
              <p class="detail-value">${col.value}</p>
            </div>
          `
        )
        .join('')}
    </div>
  `;
  matchDetail.appendChild(txCard);

  const txEditCard = document.createElement('div');
  txEditCard.className = 'detail-card';
  txEditCard.innerHTML = `
    <div class="detail-grid editable-inputs">
      <input
        type="text"
        class="form-control form-control-sm"
        placeholder="ID manual"
        value="${escapeHtml(transaction.custom_id || '')}"
        data-transaction-field="custom_identifier"
        data-update-url="${escapeHtml(transactionUpdateUrl || '')}"
      >
      <input
        type="date"
        class="form-control form-control-sm"
        value="${transaction.billing_reference_date || ''}"
        min="${transaction.date ? escapeHtml(transaction.date.slice(0, 10)) : ''}"
        data-transaction-field="billing_reference_date"
        data-update-url="${escapeHtml(transactionUpdateUrl || '')}"
      >
    </div>
  `;
  matchDetail.appendChild(txEditCard);
  attachTransactionFieldListeners(txEditCard, transaction);

  if (!voucher) {
    const info = document.createElement('div');
    info.className = 'empty-state';
    info.textContent = 'Selecciona un registro para comparar.';
    matchDetail.appendChild(info);
    return;
  }

  const difference = Number((txAmount - voucherAmount).toFixed(2));
  const hasOverpayment = difference > 0;
  const shortage = difference < 0 && Math.abs(difference) >= shortageThreshold;

  const voucherColumns = [
    { label: 'N.º', value: voucher.entry_number || voucher.serial_number || voucher.id },
    { label: 'Fecha', value: entryIssueDate },
    { label: 'Número factura', value: voucher.invoice_number || '—' },
    { label: 'NIT / CI', value: escapeHtml(voucher.nit_ci || '—') },
    { label: 'Razón social', value: escapeHtml(voucher.razon_social || '—') },
    { label: 'Nombre estudiante', value: voucherName },
    { label: 'Tipo de pago', value: escapeHtml(voucher.payment_type || '—') },
    { label: 'Monto', value: formatCurrency(voucher.amount) },
    { label: 'Cuenta', value: escapeHtml(voucher.account || '—') },
    { label: 'Estado', value: formatStatus(voucher.status) },
  ];

  const voucherCard = document.createElement('div');
  voucherCard.className = 'detail-card';
  voucherCard.innerHTML = `
    <div class="detail-header">
      <h4>Registro ${voucher.id}</h4>
    </div>
    <div class="detail-grid">
      ${voucherColumns
        .map(
          (col) => `
            <div>
              <span class="detail-label">${col.label}</span>
              <p class="detail-value">${col.value}</p>
            </div>
          `
        )
        .join('')}
    </div>
  `;
  matchDetail.appendChild(voucherCard);

  const editCard = document.createElement('div');
  editCard.className = 'detail-card';
  editCard.innerHTML = `
    <div class="detail-grid editable-inputs">
      <input
        type="text"
        class="form-control form-control-sm"
        placeholder="ID manual"
        data-entry-field="custom_id"
        value="${escapeHtml(voucher.custom_id || '')}"
        data-update-url="${escapeHtml(entryUpdateUrl || '')}"
      >
      <input
        type="text"
        class="form-control form-control-sm"
        placeholder="Banco"
        data-entry-field="bank_name"
        value="${escapeHtml(voucher.bank_name || voucher.bankName || '')}"
        data-update-url="${escapeHtml(entryUpdateUrl || '')}"
      >
      <input
        type="date"
        class="form-control form-control-sm"
        data-entry-field="recorded_date"
        value="${escapeHtml(entryRecordedValue)}"
        data-update-url="${escapeHtml(entryUpdateUrl || '')}"
        ${voucher.issueDate ? `min="${escapeHtml(voucher.issueDate)}"` : ''}
      >
      <input
        type="text"
        class="form-control form-control-sm"
        placeholder="Operación"
        data-entry-field="operation_reference"
        value="${escapeHtml(entryOperation)}"
        data-update-url="${escapeHtml(entryUpdateUrl || '')}"
      >
    </div>
  `;
  matchDetail.appendChild(editCard);
  attachEntryFieldListeners(editCard, voucher);

  const differenceCard = document.createElement('div');
  differenceCard.className = 'detail-card';
  differenceCard.innerHTML = `
    <span class="detail-label d-block">Diferencia detectada</span>
    <p class="detail-value ${difference > 0 ? 'text-success' : difference < 0 ? 'text-danger' : ''}">
      ${formatCurrency(difference)}
    </p>
  `;
  matchDetail.appendChild(differenceCard);

  if (shortage) {
    const warning = document.createElement('div');
    warning.className = 'alert alert-warning';
    warning.innerHTML = `
      <strong>Atención:</strong> El registro diario es menor que el monto del extracto. Solicita al estudiante completar el pago o adjuntar el comprobante correcto.
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
      <h4>Registro diario</h4>
      <p><strong>${voucherName}</strong> · ${voucher.enrollment}</p>
      <p>${formatCurrency(voucher.amount)} · ${formatDate(voucher.issueDate || voucher.recorded_date || transaction.date)}</p>
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
  const reason = prompt('Motivo del rechazo', 'Los datos del registro no coinciden con el extracto.');
  if (reason === null) {
    return;
  }

  submitMatchAction('reject', { reason });
}

function creditMatch(defaultAmount) {
  const parsedAmount = Number(defaultAmount);
  const normalizedAmount = Number.isFinite(parsedAmount) ? Math.abs(parsedAmount) : NaN;
  const creditAmount = Number.isFinite(normalizedAmount)
    ? Number(normalizedAmount.toFixed(2))
    : NaN;

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
  if (matchingBusy) {
    showToast('Ya se está procesando la conciliación. Espera un momento.');
    return;
  }

  const transaction = state.transactions.find((tx) => tx.id === state.ui.selectedTransaction);
  const voucher = state.reportEntries.find((vc) => vc.id === state.ui.selectedEntry);

  if (!transaction || !voucher) {
    showToast('Selecciona una transacción y un registro.');
    return;
  }

  if (!confirmMatchUrl) {
    showToast('La ruta de conciliación no está configurada.');
    return;
  }

  const transactionDbId = transaction.db_id ?? transaction.dbId ?? transaction.lineId ?? null;
  const entryNumericId = getEntryNumericId(voucher);
  const pendingLineUpdates = transactionDbId ? state.pendingTransactionUpdates[transactionDbId] : null;
  const pendingEntryUpdates = entryNumericId ? state.pendingEntryUpdates[entryNumericId] : null;
  const getDetailInputValue = (selector) => {
    const el = matchDetail?.querySelector(selector);
    return el ? el.value : '';
  };

  const requiresManualIds = action === 'confirm' || action === 'credit' || action === 'reject';
  if (requiresManualIds) {
    clearValidationMarks();
    const normalizeId = (value) => (value ?? '').toString().trim();
    const hasTransactionId = normalizeId(
      transaction.custom_id
        || transaction.custom_identifier
        || pendingLineUpdates?.custom_identifier
    ) !== '';
    const hasEntryId = normalizeId(
      voucher.custom_id
        || pendingEntryUpdates?.custom_id
    ) !== '';
    const hasEntryBank = normalizeId(
      voucher.bank_name
        || voucher.bankName
        || pendingEntryUpdates?.bank_name
        || getDetailInputValue('[data-entry-field=\"bank_name\"]')
    ) !== '';
    const hasEntryRecordedDate = normalizeId(
      voucher.recorded_date
        || pendingEntryUpdates?.recorded_date
        || getDetailInputValue('[data-entry-field=\"recorded_date\"]')
    ) !== '';
    const hasEntryOperation = normalizeId(
      voucher.operation_reference
        || voucher.operation_number
        || pendingEntryUpdates?.operation_reference
        || getDetailInputValue('[data-entry-field=\"operation_reference\"]')
    ) !== '';
    const hasTxBillingDate = normalizeId(
      transaction.billing_reference_date
        || pendingLineUpdates?.billing_reference_date
    ) !== '';

    const missing = [];
    const missingSelectors = [];
    if (!hasTransactionId) {
      missing.push('ID manual del extracto');
      missingSelectors.push('[data-transaction-field="custom_identifier"]');
    }
    if (!hasTxBillingDate) {
      missing.push('Fecha del extracto (facturación)');
      missingSelectors.push('[data-transaction-field="billing_reference_date"]');
    }
    if (!hasEntryId) {
      missing.push('ID manual del reporte diario');
      missingSelectors.push('[data-entry-field="custom_id"]');
    }
    if (!hasEntryBank) {
      missing.push('Banco en reporte diario');
      missingSelectors.push('[data-entry-field="bank_name"]');
    }
    if (!hasEntryRecordedDate) {
      missing.push('Fecha del reporte diario');
      missingSelectors.push('[data-entry-field="recorded_date"]');
    }
    if (!hasEntryOperation) {
      missing.push('Operación en reporte diario');
      missingSelectors.push('[data-entry-field="operation_reference"]');
    }

    const mustCheckAll = action === 'confirm' || action === 'credit';
    const mustCheckIdsOnly = action === 'reject';
    const isMissingCritical = mustCheckAll ? missing.length > 0 : (!hasTransactionId || !hasEntryId);

    if (isMissingCritical) {
      const message = mustCheckAll
        ? `Completa los campos requeridos antes de continuar: ${missing.join(', ')}.`
        : 'Asigna el ID manual en el extracto y en el reporte diario antes de conciliar.';
      markInvalidSelectors(missingSelectors);
      showToast(message);
      return;
    }
  }

  matchingBusy = true;
  toggleMatchButtons(true);

  const lineUpdates = pendingLineUpdates;
  const entryUpdates = pendingEntryUpdates;

  const payload = {
    action,
    bank_statement_line_id: transaction.db_id ?? transaction.dbId ?? transaction.lineId ?? transaction.id,
    sales_book_entry_id: voucher.db_id ?? voucher.dbId ?? voucher.id,
    ...extraPayload,
  };

  if (lineUpdates && Object.keys(lineUpdates).length) {
    payload.line_updates = lineUpdates;
  }

  if (entryUpdates && Object.keys(entryUpdates).length) {
    payload.entry_updates = entryUpdates;
  }

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

    applyMatchResult(data, transactionDbId, entryNumericId);
    closeModal();
  } catch (error) {
    console.error(error);
    showToast('Ocurrió un error al procesar la acción.');
  } finally {
    matchingBusy = false;
    toggleMatchButtons(false);
  }
}

function applyMatchResult(data, transactionDbId = null, entryNumericId = null) {
  const action = data.action || 'confirm';

  if (action === 'reject') {
    if (data.transaction) {
      state.transactions = state.transactions.filter(
        (tx) => tx.db_id !== data.transaction.db_id && tx.id !== data.transaction.id
      );
    }

    if (data.report_entry) {
      state.reportEntries = state.reportEntries.filter(
        (entry) => entry.db_id !== data.report_entry.db_id && entry.id !== data.report_entry.id
      );

      updateReportRowStatus(
        extractEntryDbId(data.report_entry),
        'rechazado',
        data.report_entry?.reason || 'Rechazado en conciliación'
      );
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
  } else {
    if (data.transaction) {
      state.transactions = state.transactions.filter(
        (tx) => tx.db_id !== data.transaction.db_id && tx.id !== data.transaction.id
      );
    }

    if (data.report_entry) {
      state.reportEntries = state.reportEntries.filter(
        (entry) => entry.db_id !== data.report_entry.db_id && entry.id !== data.report_entry.id
      );

      const newStatus = action === 'credit' ? 'demasia' : 'conciliado';
      const reason = action === 'credit'
        ? data.report_entry?.reason || 'Pago en demasía'
        : action === 'reject'
          ? (data.report_entry?.reason || '')
          : '';

      updateReportRowStatus(extractEntryDbId(data.report_entry), newStatus, reason);
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

    if (action === 'credit' && data.overpayment) {
      const overpayment = normalizeOverpayment(data.overpayment, data.report_entry);
      upsertOverpayment(overpayment);
    }
  }

  state.ui.selectedTransaction = null;
  state.ui.selectedEntry = null;
  if (transactionDbId) {
    delete state.pendingTransactionUpdates[transactionDbId];
  }
  if (entryNumericId) {
    delete state.pendingEntryUpdates[entryNumericId];
  }

  renderTransactionList();
  renderEntryList();
  renderMatchDetail();
  renderReconciliations();
  renderOverpayments();
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
      <td>${formatDate(item.report_date || item.date)}</td>
      <td>${escapeHtml(item.bank_name || getBankName(item.bankId))}</td>
      <td>${studentName}</td>
      <td>${formatCurrency(item.amount)}</td>
      <td>${billingCell}</td>
      <td><span class="badge ${statusClass}">${formatStatus(item.status)}</span></td>
    `;
    reconciliationTable.appendChild(row);
  });
}

function renderOverpayments() {
  if (!studentTable) {
    return;
  }

  const searchInput = document.getElementById('student-search');
  const query = (searchInput?.value || '').toLowerCase();
  const balances = (state.overpayments || []).filter((item) => {
    if (!query) {
      return true;
    }

    return (item.student_name || '').toLowerCase().includes(query);
  });

  studentTable.innerHTML = '';

  if (!balances.length) {
    const row = document.createElement('tr');
    row.innerHTML = '<td colspan="3" class="text-center text-muted py-3">No hay saldos acreditados.</td>';
    studentTable.appendChild(row);
    return;
  }

  balances.forEach((item) => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${escapeHtml(item.student_name || '—')}</td>
      <td>${formatCurrency(item.balance || 0)}</td>
      <td>${item.credited_at ? formatDate(item.credited_at) : '—'}</td>
    `;
    studentTable.appendChild(row);
  });
}
function normalizeOverpayment(raw, reportEntry = null) {
  const entryStudent = reportEntry?.student || reportEntry?.student_name;
  return {
    id: raw.id || `sb-${raw.db_id || Date.now()}`,
    db_id: raw.db_id || null,
    student_name: raw.student_name || entryStudent || 'Sin estudiante',
    student_code: raw.student_code || reportEntry?.custom_id || '—',
    balance: Number(raw.balance ?? raw.credit_amount ?? 0),
    credited_at: raw.credited_at || new Date().toISOString(),
  };
}

function upsertOverpayment(overpayment) {
  if (!Array.isArray(state.overpayments)) {
    state.overpayments = [];
  }

  const matcher = (item) => {
    if (item.db_id && overpayment.db_id) {
      return String(item.db_id) === String(overpayment.db_id);
    }
    return item.id === overpayment.id;
  };

  const existingIndex = state.overpayments.findIndex(matcher);
  if (existingIndex >= 0) {
    state.overpayments[existingIndex] = {
      ...state.overpayments[existingIndex],
      ...overpayment,
    };
  } else {
    state.overpayments.unshift(overpayment);
  }
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
  if (globalSearch) {
    globalSearch.addEventListener('input', renderTransactionList);
    if (globalSearch.form) {
      globalSearch.form.addEventListener('submit', (event) => event.preventDefault());
    }
  }
  const studentSearch = document.getElementById('student-search');
  if (studentSearch) {
    studentSearch.addEventListener('input', renderOverpayments);
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

      if (targetView === 'matching') {
        renderTransactionList();
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
  renderEntryList();
  renderMatchDetail();
  renderReconciliations();
  renderOverpayments();
  renderBanks();
  initNavigation();
  initFilters();
  initModal();
  initTrendControls();
  initSummaryFilters();
}

document.addEventListener('DOMContentLoaded', init);

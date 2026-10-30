import { escapeHTML, setStatus, renderPagination } from './utils.js';

const API = '../api/students.php';
const csrf = document.querySelector('meta[name="csrf-token"]').content;

const rows = document.querySelector('#student-rows');
const status = document.querySelector('#ajax-status');
const count = document.querySelector('#ajax-count');
const pagination = document.querySelector('#ajax-pagination');
const form = document.querySelector('#student-form');
const search = document.querySelector('#ajax-search');
const courseFilter = document.querySelector('#ajax-course');
const idField = document.querySelector('#student-id');
const formTitle = document.querySelector('#form-title');
const saveButton = document.querySelector('#save-button');
const cancelButton = document.querySelector('#cancel-edit');

const state = { q: '', course: 0, page: 1 };
let students = [];

/* ---- one place for every request: JSON in, JSON out, centralised error handling ---- */
async function api(url, options = {}) {
  const headers = { 'Accept': 'application/json' };
  if (options.body) headers['Content-Type'] = 'application/json';
  if (options.method && options.method !== 'GET') headers['X-CSRF-Token'] = csrf;
  const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
  let payload = null;
  try { payload = await response.json(); } catch (e) { /* not JSON */ }
  if (!response.ok || !payload || payload.success === false) {
    const error = new Error((payload && payload.message) || 'Request failed (HTTP ' + response.status + ')');
    error.status = response.status;
    error.errors = (payload && payload.errors) || {};
    throw error;
  }
  return payload;
}

/* ---- list ---- */
async function loadStudents() {
  setStatus(status, 'loading', 'Loading students...');
  try {
    const query = new URLSearchParams({ q: state.q, course: state.course, page: state.page, per_page: 8 });
    const result = await api(API + '?' + query);
    students = result.data;
    state.page = result.meta.page;
    setStatus(status, null);
    renderRows();
    count.textContent = result.meta.total + ' student(s) found';
    renderPagination(pagination, result.meta.page, result.meta.pages, page => { state.page = page; loadStudents(); });
  } catch (error) {
    setStatus(status, 'error', error.message, loadStudents);
  }
}

function renderRows() {
  if (!students.length) {
    rows.innerHTML = '<tr><td colspan="7">No students found.</td></tr>';
    return;
  }
  rows.innerHTML = students.map(s =>
    '<tr><td>' + escapeHTML(s.student_code) + '</td><td>' + escapeHTML(s.full_name) + '</td><td>' + escapeHTML(s.email) +
    '</td><td>' + escapeHTML(s.course) + '</td><td>' + s.year + '</td><td>' + escapeHTML(s.city || '-') + '</td>' +
    '<td class="row-actions"><button class="btn-small" type="button" data-action="edit" data-id="' + s.id + '">Edit</button> ' +
    '<button class="btn-small btn-danger" type="button" data-action="delete" data-id="' + s.id + '">Delete</button></td></tr>'
  ).join('');
}

/* ---- search with debounce (waits until typing pauses) ---- */
function debounce(fn, delay) {
  let timer;
  return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}
search.addEventListener('input', debounce(() => { state.q = search.value.trim(); state.page = 1; loadStudents(); }, 350));
courseFilter.addEventListener('change', () => { state.course = Number(courseFilter.value); state.page = 1; loadStudents(); });

/* ---- form helpers ---- */
function showFieldErrors(errors = {}) {
  form.querySelectorAll('[data-error]').forEach(span => { span.textContent = errors[span.dataset.error] || ''; });
}
function resetForm() {
  form.reset();
  idField.value = '';
  formTitle.textContent = 'Add Student';
  saveButton.textContent = 'Add Student';
  cancelButton.hidden = true;
  showFieldErrors();
}
function formData() {
  const data = {};
  ['student_code', 'full_name', 'email', 'mobile', 'course_id', 'year', 'gender', 'city'].forEach(name => {
    data[name] = form.elements[name].value;
  });
  return data;
}

/* ---- create / update ---- */
form.addEventListener('submit', async event => {
  event.preventDefault();
  showFieldErrors();
  const editing = idField.value !== '';
  saveButton.disabled = true;
  setStatus(status, 'loading', editing ? 'Saving changes...' : 'Adding student...');
  try {
    await api(editing ? API + '?id=' + idField.value : API, {
      method: editing ? 'PUT' : 'POST',
      body: JSON.stringify(formData())
    });
    resetForm();
    await loadStudents();
    setStatus(status, 'success', editing ? 'Student updated.' : 'Student added.');
  } catch (error) {
    showFieldErrors(error.errors);
    setStatus(status, 'error', error.message);
  } finally {
    saveButton.disabled = false;
  }
});

cancelButton.addEventListener('click', resetForm);

/* ---- edit / delete buttons (event delegation) ---- */
rows.addEventListener('click', async event => {
  const button = event.target.closest('button[data-action]');
  if (!button) return;
  const student = students.find(s => s.id === Number(button.dataset.id));
  if (!student) return;

  if (button.dataset.action === 'edit') {
    idField.value = student.id;
    ['student_code', 'full_name', 'email', 'mobile', 'course_id', 'year', 'gender'].forEach(name => {
      form.elements[name].value = student[name];
    });
    form.elements.city.value = student.city || '';
    formTitle.textContent = 'Edit Student';
    saveButton.textContent = 'Save Changes';
    cancelButton.hidden = false;
    showFieldErrors();
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.elements.full_name.focus();
    return;
  }

  if (!window.confirm('Move "' + student.full_name + '" to the deleted list?')) return;
  setStatus(status, 'loading', 'Deleting...');
  try {
    await api(API + '?id=' + student.id, { method: 'DELETE' });
    await loadStudents();
    setStatus(status, 'success', 'Student deleted.');
  } catch (error) {
    setStatus(status, 'error', error.message);
  }
});

loadStudents();

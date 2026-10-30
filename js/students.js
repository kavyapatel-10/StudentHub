import {
  fetchJSON, escapeHTML, setStatus, matchesSearch,
  sortItems, paginate, renderPagination
} from './utils.js';

const PER_PAGE = 6;

const list = document.querySelector('#students-list');
const status = document.querySelector('#status');
const count = document.querySelector('#result-count');
const pagination = document.querySelector('#pagination');
const searchInput = document.querySelector('#search');
const courseSelect = document.querySelector('#course-filter');
const yearSelect = document.querySelector('#year-filter');
const sortSelect = document.querySelector('#sort-by');
const clearButton = document.querySelector('#clear-filters');

const state = { all: [], search: '', course: 'all', year: 'all', sort: 'name-asc', page: 1 };

function getVisibleStudents() {
  const [key, direction] = state.sort.split('-');
  const filtered = state.all
    .filter(s => matchesSearch(s, ['name', 'email', 'course', 'city'], state.search))
    .filter(s => state.course === 'all' || s.course === state.course)
    .filter(s => state.year === 'all' || String(s.year) === state.year);
  return sortItems(filtered, key, direction);
}

function initials(name) {
  return name.split(' ').map(word => word[0]).join('').slice(0, 2).toUpperCase();
}

function yearLabel(year) {
  return year + ['st', 'nd', 'rd', 'th'][year - 1] + ' Year';
}

function cardHTML(student) {
  return '<article class="card student-card">' +
    '<div class="avatar" aria-hidden="true">' + escapeHTML(initials(student.name)) + '</div>' +
    '<h3>' + escapeHTML(student.name) + '</h3>' +
    '<p><span class="badge">' + escapeHTML(student.course) + '</span> <span class="badge">' + yearLabel(student.year) + '</span></p>' +
    '<p>' + escapeHTML(student.city) + '<br><a href="mailto:' + escapeHTML(student.email) + '">' + escapeHTML(student.email) + '</a></p>' +
    '</article>';
}

function render() {
  const visible = getVisibleStudents();
  const result = paginate(visible, state.page, PER_PAGE);
  state.page = result.page;

  if (visible.length === 0) {
    list.replaceChildren();
    count.textContent = '';
    pagination.replaceChildren();
    setStatus(status, 'empty', 'No students match your search or filter.');
    return;
  }

  setStatus(status, null);
  list.innerHTML = result.items.map(cardHTML).join('');
  count.textContent = 'Showing ' + (result.start + 1) + '-' + (result.start + result.items.length) +
    ' of ' + visible.length + ' students';
  renderPagination(pagination, result.page, result.totalPages, page => {
    state.page = page;
    render();
    list.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

function fillCourses() {
  const courses = [...new Set(state.all.map(s => s.course))].sort();
  courseSelect.length = 1;
  courses.forEach(name => courseSelect.add(new Option(name, name)));
}

searchInput.addEventListener('input', () => { state.search = searchInput.value; state.page = 1; render(); });
courseSelect.addEventListener('change', () => { state.course = courseSelect.value; state.page = 1; render(); });
yearSelect.addEventListener('change', () => { state.year = yearSelect.value; state.page = 1; render(); });
sortSelect.addEventListener('change', () => { state.sort = sortSelect.value; state.page = 1; render(); });
clearButton.addEventListener('click', () => {
  searchInput.value = '';
  courseSelect.value = 'all';
  yearSelect.value = 'all';
  sortSelect.value = 'name-asc';
  Object.assign(state, { search: '', course: 'all', year: 'all', sort: 'name-asc', page: 1 });
  render();
});

async function init() {
  setStatus(status, 'loading', 'Loading students...');
  try {
    state.all = await fetchJSON('data/students.json');
    fillCourses();
    render();
  } catch (error) {
    console.error(error);
    setStatus(status, 'error',
      'Students could not be loaded. Open the site through a local server (Live Server / XAMPP) and check data/students.json.',
      init);
  }
}

init();

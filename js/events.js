import {
  fetchJSON, escapeHTML, setStatus, matchesSearch,
  sortItems, paginate, renderPagination, formatDate
} from './utils.js';

const PER_PAGE = 6;

const list = document.querySelector('#events-list');
const status = document.querySelector('#status');
const count = document.querySelector('#result-count');
const pagination = document.querySelector('#pagination');
const searchInput = document.querySelector('#search');
const categorySelect = document.querySelector('#category-filter');
const sortSelect = document.querySelector('#sort-by');
const clearButton = document.querySelector('#clear-filters');

const modal = document.querySelector('#event-modal');
const modalTitle = document.querySelector('#modal-title');
const modalMeta = document.querySelector('#modal-meta');
const modalDescription = document.querySelector('#modal-description');
const closeModalButton = document.querySelector('#close-modal');

const state = { all: [], search: '', category: 'all', sort: 'date-asc', page: 1 };

function getVisibleEvents() {
  const [key, direction] = state.sort.split('-');
  const filtered = state.all
    .filter(e => matchesSearch(e, ['title', 'venue', 'description', 'category'], state.search))
    .filter(e => state.category === 'all' || e.category === state.category);
  return sortItems(filtered, key, direction);
}

function cardHTML(event) {
  return '<article class="card">' +
    '<h3>' + escapeHTML(event.title) + '</h3>' +
    '<p><span class="badge">' + escapeHTML(event.category) + '</span></p>' +
    '<p><strong>' + formatDate(event.date) + '</strong><br>' + escapeHTML(event.venue) + '</p>' +
    '<button class="details-button" type="button" data-id="' + event.id + '">View Details</button>' +
    '</article>';
}

function render() {
  const visible = getVisibleEvents();
  const result = paginate(visible, state.page, PER_PAGE);
  state.page = result.page;

  if (visible.length === 0) {
    list.replaceChildren();
    count.textContent = '';
    pagination.replaceChildren();
    setStatus(status, 'empty', 'No events match your search or filter.');
    return;
  }

  setStatus(status, null);
  list.innerHTML = result.items.map(cardHTML).join('');
  count.textContent = 'Showing ' + (result.start + 1) + '-' + (result.start + result.items.length) +
    ' of ' + visible.length + ' events';
  renderPagination(pagination, result.page, result.totalPages, page => {
    state.page = page;
    render();
    list.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

function fillCategories() {
  const categories = [...new Set(state.all.map(e => e.category))].sort();
  categorySelect.length = 1; // keep "All categories"
  categories.forEach(name => categorySelect.add(new Option(name, name)));
}

function openDetails(id) {
  const event = state.all.find(e => e.id === id);
  if (!event || !modal) return;
  modalTitle.textContent = event.title;
  modalMeta.textContent = formatDate(event.date) + ' | ' + event.venue + ' | ' + event.category;
  modalDescription.textContent = event.description;
  modal.hidden = false;
  closeModalButton.focus();
}

// ---- Events ----
searchInput.addEventListener('input', () => { state.search = searchInput.value; state.page = 1; render(); });
categorySelect.addEventListener('change', () => { state.category = categorySelect.value; state.page = 1; render(); });
sortSelect.addEventListener('change', () => { state.sort = sortSelect.value; state.page = 1; render(); });
clearButton.addEventListener('click', () => {
  searchInput.value = '';
  categorySelect.value = 'all';
  sortSelect.value = 'date-asc';
  Object.assign(state, { search: '', category: 'all', sort: 'date-asc', page: 1 });
  render();
});
list.addEventListener('click', e => {
  const button = e.target.closest('.details-button');
  if (button) openDetails(Number(button.dataset.id));
});

// ---- Load data ----
async function init() {
  setStatus(status, 'loading', 'Loading events...');
  try {
    state.all = await fetchJSON('data/events.json');
    fillCategories();
    render();
  } catch (error) {
    console.error(error);
    setStatus(status, 'error',
      'Events could not be loaded. Open the site through a local server (Live Server / XAMPP) and check data/events.json.',
      init);
  }
}

init();

import { fetchJSON, escapeHTML, setStatus, matchesSearch } from './utils.js';

const list = document.querySelector('#faq-list');
const status = document.querySelector('#status');
const count = document.querySelector('#result-count');
const searchInput = document.querySelector('#search');
const categorySelect = document.querySelector('#category-filter');
const clearButton = document.querySelector('#clear-filters');

const state = { all: [], search: '', category: 'all' };

function itemHTML(faq) {
  const id = 'faq-' + faq.id;
  return '<article class="faq-item">' +
    '<h3><button class="faq-question" type="button" aria-expanded="false" aria-controls="' + id + '">' +
    escapeHTML(faq.question) + '</button></h3>' +
    '<div id="' + id + '" class="faq-answer" hidden>' + escapeHTML(faq.answer) + '</div>' +
    '</article>';
}

function render() {
  const visible = state.all
    .filter(f => matchesSearch(f, ['question', 'answer', 'category'], state.search))
    .filter(f => state.category === 'all' || f.category === state.category);

  if (visible.length === 0) {
    list.replaceChildren();
    count.textContent = '';
    setStatus(status, 'empty', 'No questions match your search.');
    return;
  }
  setStatus(status, null);
  list.innerHTML = visible.map(itemHTML).join('');
  count.textContent = 'Showing ' + visible.length + ' of ' + state.all.length + ' questions';
}

// Open / close an answer (event delegation, because items are created by JS)
list.addEventListener('click', e => {
  const button = e.target.closest('.faq-question');
  if (!button) return;
  const answer = document.getElementById(button.getAttribute('aria-controls'));
  const expanded = button.getAttribute('aria-expanded') === 'true';
  button.setAttribute('aria-expanded', String(!expanded));
  answer.hidden = expanded;
});

searchInput.addEventListener('input', () => { state.search = searchInput.value; render(); });
categorySelect.addEventListener('change', () => { state.category = categorySelect.value; render(); });
clearButton.addEventListener('click', () => {
  searchInput.value = '';
  categorySelect.value = 'all';
  Object.assign(state, { search: '', category: 'all' });
  render();
});

async function init() {
  setStatus(status, 'loading', 'Loading questions...');
  try {
    state.all = await fetchJSON('data/faqs.json');
    const categories = [...new Set(state.all.map(f => f.category))].sort();
    categorySelect.length = 1;
    categories.forEach(name => categorySelect.add(new Option(name, name)));
    render();
  } catch (error) {
    console.error(error);
    setStatus(status, 'error',
      'Questions could not be loaded. Open the site through a local server (Live Server / XAMPP) and check data/faqs.json.',
      init);
  }
}

init();

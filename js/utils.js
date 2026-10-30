/* Shared helpers for Practical 6 (imported as an ES module) */

// Fetch a JSON file and return the parsed data. Throws on network/HTTP errors.
export async function fetchJSON(url) {
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error('Could not load ' + url + ' (HTTP ' + response.status + ')');
  }
  return response.json();
}

// Escape text before putting it inside innerHTML.
export function escapeHTML(value) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  return String(value).replace(/[&<>"']/g, ch => map[ch]);
}

// Show a loading / error / empty message. Pass type = null to hide it.
export function setStatus(element, type, message = '', onRetry = null) {
  element.replaceChildren();
  if (!type) {
    element.hidden = true;
    element.className = 'status';
    return;
  }
  element.hidden = false;
  element.className = 'status status-' + type;
  const text = document.createElement('span');
  text.textContent = message;
  element.append(text);
  if (onRetry) {
    const retry = document.createElement('button');
    retry.type = 'button';
    retry.textContent = 'Retry';
    retry.addEventListener('click', onRetry);
    element.append(' ', retry);
  }
}

// Case-insensitive search across the given fields of an item.
export function matchesSearch(item, fields, term) {
  const text = term.trim().toLowerCase();
  if (!text) return true;
  return fields.some(field => String(item[field]).toLowerCase().includes(text));
}

// Return a sorted copy (numbers sorted numerically, text alphabetically).
export function sortItems(items, key, direction = 'asc') {
  const factor = direction === 'desc' ? -1 : 1;
  return [...items].sort((a, b) => {
    const x = a[key];
    const y = b[key];
    if (typeof x === 'number' && typeof y === 'number') return (x - y) * factor;
    return String(x).localeCompare(String(y)) * factor;
  });
}

// Slice one page of items and report the page info.
export function paginate(items, page, perPage) {
  const totalPages = Math.max(1, Math.ceil(items.length / perPage));
  const current = Math.min(Math.max(1, page), totalPages);
  const start = (current - 1) * perPage;
  return {
    items: items.slice(start, start + perPage),
    page: current,
    totalPages: totalPages,
    total: items.length,
    start: start
  };
}

// Draw Previous / 1 2 3 / Next buttons inside the container.
export function renderPagination(container, current, totalPages, onChange) {
  container.replaceChildren();
  if (totalPages <= 1) return;

  const addButton = (label, page, options = {}) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = label;
    if (options.ariaLabel) button.setAttribute('aria-label', options.ariaLabel);
    if (options.disabled) button.disabled = true;
    if (options.current) button.setAttribute('aria-current', 'page');
    button.addEventListener('click', () => onChange(page));
    container.append(button);
  };

  addButton('Previous', current - 1, { disabled: current === 1 });
  for (let p = 1; p <= totalPages; p++) {
    addButton(String(p), p, { current: p === current, ariaLabel: 'Page ' + p });
  }
  addButton('Next', current + 1, { disabled: current === totalPages });
}

// "2026-11-05" -> "5 November 2026"
export function formatDate(isoDate) {
  return new Date(isoDate + 'T00:00:00').toLocaleDateString('en-IN', {
    day: 'numeric', month: 'long', year: 'numeric'
  });
}

'use strict';

/* Confirmation dialog for any form that has data-confirm="message" (delete / cancel / deactivate). */
document.addEventListener('submit', event => {
  const message = event.target.dataset ? event.target.dataset.confirm : '';
  if (message && !window.confirm(message)) {
    event.preventDefault();
  }
});

# Accessibility checklist

| Done | Check | Where |
|---|---|---|
| [ ] | Semantic landmarks: header, nav, main, section, article, aside, footer | all pages |
| [ ] | One `h1` per page, headings in order | pages |
| [ ] | "Skip to main content" link works with Tab | `includes/header.php` |
| [ ] | Every input has a `<label for>` | all forms |
| [ ] | Errors are announced (`aria-live`) and linked with `aria-describedby` | `register.php`, `js/validation.js` |
| [ ] | Whole site usable with keyboard only (Tab, Enter, Esc closes the modal) | `js/script.js` |
| [ ] | Menu button uses `aria-expanded`; FAQ buttons use `aria-expanded` | `js/script.js`, `js/faq.js` |
| [ ] | Current page marked with `aria-current="page"` | `includes/header.php` |
| [ ] | Images have `alt` text (posters) | `js/events.js`, admin pages |
| [ ] | Text contrast checked in light and dark mode (browser dev tools -> Accessibility) | `css/style.css` |
| [ ] | Respects `prefers-reduced-motion` | `css/style.css` |
| [ ] | Page works at 200% zoom and on a 360px wide screen | `css/style.css` |

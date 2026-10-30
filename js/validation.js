'use strict';

/* Practical 5 - Registration form validation (loaded only on register.html) */

const form = document.querySelector('#register-form');

if (form) {
  const strengthBar = document.querySelector('#strength-bar');
  const strengthText = document.querySelector('#strength-text');

  // ---- Regular expressions ----
  const patterns = {
    name: /^[A-Za-z][A-Za-z .'-]{2,49}$/,
    email: /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/,
    studentId: /^[A-Za-z0-9-]{5,15}$/,
    mobile: /^[6-9]\d{9}$/,
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/
  };

  // ---- Read the value of any field ----
  function getValue(id) {
    if (id === 'gender') {
      const checked = form.querySelector('input[name="gender"]:checked');
      return checked ? checked.value : '';
    }
    if (id === 'terms') return form.querySelector('#terms').checked ? 'yes' : '';
    return form.querySelector('#' + id).value;
  }

  // ---- One validator per field: returns an error message, or '' if valid ----
  const validators = {
    'name': v => patterns.name.test(v.trim()) ? '' : 'Enter a valid name (3-50 letters, spaces allowed).',
    'email': v => patterns.email.test(v.trim()) ? '' : 'Enter a valid email address, e.g. name@example.com.',
    'student-id': v => patterns.studentId.test(v.trim()) ? '' : 'Student ID must be 5-15 letters, numbers or hyphens.',
    'mobile': v => patterns.mobile.test(v) ? '' : 'Enter a 10-digit mobile number starting with 6, 7, 8 or 9.',
    'course': v => v ? '' : 'Please select your course.',
    'year': v => v ? '' : 'Please select your year.',
    'gender': v => v ? '' : 'Please select your gender.',
    'password': v => patterns.password.test(v) ? '' : 'Password needs 8+ characters with uppercase, lowercase, number and special character.',
    'confirm-password': v => {
      if (!v) return 'Please confirm your password.';
      return v === getValue('password') ? '' : 'Passwords do not match.';
    },
    'terms': v => v ? '' : 'You must accept the terms and conditions.'
  };

  // ---- Show / clear an error next to its field ----
  function showResult(id, message) {
    const errorBox = document.querySelector('#' + id + '-error');
    const input = document.querySelector('#' + id);
    if (errorBox) errorBox.textContent = message;
    if (input) {
      input.classList.toggle('invalid', Boolean(message));
      input.classList.toggle('valid', !message);
      input.setAttribute('aria-invalid', String(Boolean(message)));
    }
  }

  function validateField(id) {
    const message = validators[id](getValue(id));
    showResult(id, message);
    return message === '';
  }

  // ---- Password strength meter ----
  function updateStrength() {
    const p = getValue('password');
    const checks = [
      p.length >= 8,
      /[a-z]/.test(p),
      /[A-Z]/.test(p),
      /\d/.test(p),
      /[^A-Za-z0-9]/.test(p)
    ];
    const score = p ? checks.filter(Boolean).length : 0;
    const levels = ['-', 'Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['transparent', '#dc2626', '#ea580c', '#ca8a04', '#65a30d', '#16a34a'];
    strengthBar.style.width = (score * 20) + '%';
    strengthBar.style.background = colors[score];
    strengthText.textContent = 'Password strength: ' + levels[score];
  }

  // ---- Real-time validation (keyup for text, change for select/radio/checkbox) ----
  ['name', 'email', 'student-id', 'mobile', 'password', 'confirm-password'].forEach(id => {
    const el = document.querySelector('#' + id);
    el.addEventListener('keyup', () => {
      validateField(id);
      if (id === 'password') {
        updateStrength();
        if (getValue('confirm-password')) validateField('confirm-password');
      }
    });
    el.addEventListener('blur', () => validateField(id));
  });

  ['course', 'year'].forEach(id => {
    document.querySelector('#' + id).addEventListener('change', () => validateField(id));
  });

  form.querySelectorAll('input[name="gender"]').forEach(r => {
    r.addEventListener('change', () => validateField('gender'));
  });

  document.querySelector('#terms').addEventListener('change', () => validateField('terms'));

  // Mobile: allow digits only while typing
  document.querySelector('#mobile').addEventListener('input', e => {
    e.target.value = e.target.value.replace(/\D/g, '');
  });

  // ---- Submit ----
  form.addEventListener('submit', event => {
    event.preventDefault();
    let firstInvalid = null;
    Object.keys(validators).forEach(id => {
      if (!validateField(id) && !firstInvalid) firstInvalid = id;
    });

    if (firstInvalid) {
      const target = firstInvalid === 'gender'
        ? form.querySelector('input[name="gender"]')
        : document.querySelector('#' + firstInvalid);
      target.focus();
      return;
    }

    form.submit();   // all checks passed: send the form to register.php (server validates again)
  });
}

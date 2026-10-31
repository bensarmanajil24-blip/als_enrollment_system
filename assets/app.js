// ALS Connect - Interactive Client-Side Validation & File Handling
// WCAG 2.2 AA Keyboard and Screen Reader Support

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('enrollmentForm');
  if (!form) return;

  const fileInput = document.getElementById('supportingDoc');
  const fileDropzone = document.getElementById('fileDropzone');
  const attachedFileCard = document.getElementById('attachedFileCard');
  const attachedFileName = document.getElementById('attachedFileName');
  const attachedFileMeta = document.getElementById('attachedFileMeta');
  const removeFileBtn = document.getElementById('removeFileBtn');
  const fileErrorNotice = document.getElementById('fileErrorNotice');

  // Handle Drag & Drop
  if (fileDropzone && fileInput) {
    ['dragenter', 'dragover'].forEach(eventName => {
      fileDropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        fileDropzone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(eventName => {
      fileDropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        fileDropzone.classList.remove('dragover');
      });
    });

    fileDropzone.addEventListener('drop', (e) => {
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        handleFileSelection(e.dataTransfer.files[0]);
      }
    });

    fileDropzone.addEventListener('click', () => {
      fileInput.click();
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files && fileInput.files[0]) {
        handleFileSelection(fileInput.files[0]);
      }
    });
  }

  function handleFileSelection(file) {
    const maxBytes = 5 * 1024 * 1024; // 5 MB
    const allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
    const ext = file.name.split('.').pop().toLowerCase();

    if (file.size > maxBytes || !allowedExts.includes(ext)) {
      if (fileErrorNotice) {
        fileErrorNotice.style.display = 'flex';
        fileErrorNotice.querySelector('.error-text').innerText = 
          'Hindi na-upload ang file. Lumampas sa halimbawang 5 MB limit o hindi valid ang format (PDF, JPG, PNG). Pumili ng mas maliit at subukan muli.';
      }
      if (attachedFileCard) attachedFileCard.style.display = 'none';
      fileInput.value = '';
      return;
    }

    if (fileErrorNotice) fileErrorNotice.style.display = 'none';
    if (attachedFileCard) {
      attachedFileCard.style.display = 'flex';
      attachedFileName.innerText = file.name;
      const sizeKB = Math.round(file.size / 1024);
      attachedFileMeta.innerText = `${sizeKB} KB • Nakakabit • Handa nang isumite`;
    }
  }

  if (removeFileBtn) {
    removeFileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      fileInput.value = '';
      if (attachedFileCard) attachedFileCard.style.display = 'none';
      if (fileErrorNotice) fileErrorNotice.style.display = 'none';
    });
  }

  // Real-time Phone Validation (11 digits, begins with 09)
  const contactInput = document.getElementById('contactNumber');
  if (contactInput) {
    contactInput.addEventListener('input', () => {
      const val = contactInput.value.replace(/\D/g, '');
      const group = contactInput.closest('.form-group');
      const err = group ? group.querySelector('.error-message') : null;

      if (val.length > 0 && (val.length !== 11 || !val.startsWith('09'))) {
        if (group) group.classList.add('has-error');
        if (err) {
          err.style.display = 'flex';
          err.innerHTML = '<span>⚠</span> Ilagay ang kumpletong 11-digit mobile number (e.g. 09123456789).';
        }
      } else {
        if (group) group.classList.remove('has-error');
        if (err) err.style.display = 'none';
      }
    });
  }

  // Form Submission Validation & First Error Focus
  form.addEventListener('submit', (e) => {
    let hasError = false;
    let firstInvalidField = null;

    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach((field) => {
      const group = field.closest('.form-group');
      const err = group ? group.querySelector('.error-message') : null;
      const isBlank = !field.value.trim();

      if (isBlank) {
        hasError = true;
        if (group) group.classList.add('has-error');
        if (err) {
          err.style.display = 'flex';
          err.innerHTML = '<span>⚠</span> Kinakailangan punan ang field na ito.';
        }
        if (!firstInvalidField) firstInvalidField = field;
      }
    });

    // Check contact number
    if (contactInput) {
      const val = contactInput.value.replace(/\D/g, '');
      if (val.length !== 11 || !val.startsWith('09')) {
        hasError = true;
        const group = contactInput.closest('.form-group');
        const err = group ? group.querySelector('.error-message') : null;
        if (group) group.classList.add('has-error');
        if (err) {
          err.style.display = 'flex';
          err.innerHTML = '<span>⚠</span> Ilagay ang kumpletong 11-digit mobile number (e.g. 09123456789).';
        }
        if (!firstInvalidField) firstInvalidField = contactInput;
      }
    }

    if (hasError) {
      e.preventDefault();
      if (firstInvalidField) {
        firstInvalidField.focus();
        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }
  });
});

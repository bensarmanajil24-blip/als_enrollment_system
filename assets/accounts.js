/**
 * DepEd ALS - Accounts & Authentication Client-Side Interactions
 */

/**
 * Filter the account management table based on search input and selected role dropdown.
 */
function filterTable() {
  var searchElem = document.getElementById('searchInput');
  var roleElem = document.getElementById('roleDropdown');
  if (!searchElem || !roleElem) return;

  var q = searchElem.value.toLowerCase();
  var role = roleElem.value;
  var rows = document.querySelectorAll('#accountsTable tbody tr');

  rows.forEach(function(row) {
    var txt = row.innerText.toLowerCase();
    var rowRole = row.getAttribute('data-role');
    var matchesSearch = txt.indexOf(q) !== -1;
    var matchesRole = (role === 'all' || rowRole === role);

    row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
  });
}

/**
 * Filter accounts table using the pill tabs (All accounts, Teachers / IM, Students).
 */
function filterByRole(roleType, btn) {
  if (!btn) return;
  var btns = btn.parentElement.querySelectorAll('button');
  btns.forEach(function(b) {
    b.style.background = '#F8FAFC';
    b.style.color = '#334155';
    b.style.borderColor = '#E2E8F0';
  });
  btn.style.background = '#185ADB';
  btn.style.color = '#FFFFFF';
  btn.style.borderColor = '#185ADB';

  var rows = document.querySelectorAll('#accountsTable tbody tr');
  rows.forEach(function(row) {
    var type = row.getAttribute('data-type');
    if (roleType === 'all' || type === roleType) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

/**
 * Export table data to CSV format.
 */
function exportCsv() {
  var table = document.getElementById('accountsTable');
  if (!table) return;

  var csv = "Account,Role,Learning Center,Status,Last Active\n";
  var rows = table.querySelectorAll('tbody tr');
  rows.forEach(function(row) {
    if (row.style.display === 'none') return;
    var cols = row.querySelectorAll('td');
    if (cols.length >= 5) {
      var acc = cols[0].innerText.replace(/\n/g, ' - ');
      var r = cols[1].innerText;
      var c = cols[2].innerText;
      var s = cols[3].innerText;
      var l = cols[4].innerText;
      csv += '"' + acc + '","' + r + '","' + c + '","' + s + '","' + l + '"\n';
    }
  });

  var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  var url = window.URL.createObjectURL(blob);
  var a = document.createElement('a');
  a.setAttribute('href', url);
  a.setAttribute('download', 'deped_als_accounts_2026.csv');
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

/**
 * Switch form dynamic labels and hints between Teacher and Student.
 */
function switchAccountType(type) {
  var title = document.getElementById('cardHeaderTitle');
  var lrnLabel = document.getElementById('lrnLabel');
  var hint = document.getElementById('emailHintText');
  var rTeach = document.getElementById('radioLabelTeacher');
  var rStud = document.getElementById('radioLabelStudent');

  if (type === 'teacher') {
    if (title) title.innerText = 'Teacher / IM information';
    if (lrnLabel) lrnLabel.innerText = 'Teacher Employee ID *';
    if (hint) hint.innerText = 'The teacher will use this email to activate and access the IM Teacher portal.';
    if (rTeach) rTeach.classList.add('selected');
    if (rStud) rStud.classList.remove('selected');
  } else {
    if (title) title.innerText = 'Student information';
    if (lrnLabel) lrnLabel.innerText = 'Learner reference number *';
    if (hint) hint.innerText = 'The learner will use this email to activate and access the Student portal.';
    if (rStud) rStud.classList.add('selected');
    if (rTeach) rTeach.classList.remove('selected');
  }
}

/**
 * Toggle password field masking / visibility on login.
 */
function togglePasswordVisibility() {
  var pInput = document.getElementById('passwordInput');
  var eye = document.getElementById('eyeIcon');
  if (!pInput || !eye) return;

  if (pInput.type === 'password') {
    pInput.type = 'text';
    eye.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
  } else {
    pInput.type = 'password';
    eye.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
  }
}

/**
 * Fill demo credentials for testing.
 */
function fillDemo(user, pass) {
  var idInput = document.getElementById('identifierInput');
  var passInput = document.getElementById('passwordInput');
  if (idInput) idInput.value = user;
  if (passInput) passInput.value = pass;
}


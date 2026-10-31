<?php
declare(strict_types=1);

// Ensure $pdo is available
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}
if (!function_exists('get_next_sequential_student_id')) {
    require_once __DIR__ . '/functions.php';
}

$nextAutoStudentId = get_next_sequential_student_id($pdo);
$currentSelfPage = basename($_SERVER['PHP_SELF'] ?? 'teacher_assigned_learners.php');
?>
<!-- ==================== MODAL: ENROLL STUDENT (REGISTER STUDENT) ==================== -->
<style>
.als-enroll-modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(11, 30, 59, 0.72);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 999999;
  align-items: center;
  justify-content: center;
  padding: 16px;
  box-sizing: border-box;
}
.als-enroll-modal-backdrop.active {
  display: flex !important;
}
.als-enroll-modal-card {
  background: #FFFFFF;
  width: 100%;
  max-width: 580px;
  max-height: 94vh;
  border-radius: 16px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
  border: 1px solid #CBD5E1;
  overflow-y: auto;
  box-sizing: border-box;
  animation: enrollFadeIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes enrollFadeIn {
  from { opacity: 0; transform: scale(0.96) translateY(-10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.als-enroll-modal-header {
  background: linear-gradient(135deg, #07162C 0%, #0B1E3B 100%);
  color: #FFFFFF;
  padding: 18px 22px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #E2E8F0;
}
.als-enroll-modal-body {
  padding: 20px 22px;
}
.bulk-assign-banner {
  background: #F0FDF4;
  border: 1.5px solid #86EFAC;
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 16px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.bulk-assign-banner input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #107C41;
  cursor: pointer;
  margin-top: 2px;
}
.enroll-grid-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}
.enroll-field-label {
  font-size: 11.5px;
  font-weight: 750;
  color: #0F172A;
  display: block;
  margin-bottom: 4px;
}
.enroll-field-input {
  width: 100%;
  padding: 9px 12px;
  border: 1.5px solid #CBD5E1;
  border-radius: 8px;
  font-size: 12.5px;
  background: #FFFFFF;
  box-sizing: border-box;
  font-family: inherit;
  color: #0F172A;
  outline: none;
  transition: border-color 0.15s ease;
}
.enroll-field-input:focus {
  border-color: #185ADB;
  box-shadow: 0 0 0 3px rgba(24, 90, 219, 0.12);
}
.checklist-container-box {
  background: #F8FAFD;
  border: 1px solid #CBD5E1;
  border-radius: 10px;
  padding: 14px 16px;
  margin-top: 14px;
  margin-bottom: 18px;
}
.checklist-item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px dashed #E2E8F0;
  font-size: 12.5px;
  font-weight: 650;
  color: #1E293B;
}
.checklist-item-row:last-child {
  border-bottom: none;
}
.doc-pill-mandatory {
  font-size: 10.5px;
  font-weight: 800;
  color: #15803D;
  background: #DCFCE7;
  padding: 3px 8px;
  border-radius: 6px;
}
.doc-pill-mandatory.amber {
  color: #B45309;
  background: #FEF3C7;
}
.als-enroll-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 14px;
  border-top: 1px solid #E2E8F0;
}
.btn-enroll-cancel {
  background: #FFFFFF;
  border: 1px solid #CBD5E1;
  color: #334155;
  padding: 9px 18px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}
.btn-enroll-submit {
  background: #107C41;
  border: none;
  color: #FFFFFF;
  padding: 9px 20px;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 750;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.btn-enroll-submit:hover {
  background: #0E6B38;
}
</style>

<div id="enrollStudentModal" class="als-enroll-modal-backdrop">
  <div class="als-enroll-modal-card">

    <!-- Header (Matching Unang Larawan) -->
    <div class="als-enroll-modal-header">
      <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:20px;">🎓</span>
        <div>
          <strong style="font-size:15px;color:#FFFFFF;display:block;">+ Enroll Student (Register Student)</strong>
          <span style="font-size:11px;color:#CBD5E1;">One-Click Bulk Assignment for LS1 to LS6</span>
        </div>
      </div>
      <button type="button" onclick="closeEnrollModal()" style="background:transparent;border:none;color:#CBD5E1;font-size:24px;cursor:pointer;line-height:1;">&times;</button>
    </div>

    <!-- Body -->
    <div class="als-enroll-modal-body">
      <form method="post" action="action_enroll_student.php" id="enrollStudentForm">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="redirect_to" value="<?= e($currentSelfPage) ?>">

        <!-- Green Box: One-Click Bulk Assignment for LS1 to LS6 -->
        <div class="bulk-assign-banner">
          <input type="checkbox" name="bulk_assignment" id="bulkAssignCheckbox" value="1" checked>
          <label for="bulkAssignCheckbox" style="cursor:pointer;">
            <strong style="font-size:12.5px;color:#15803D;display:block;margin-bottom:2px;">
              ✓ One-Click Bulk Assignment:
            </strong>
            <span style="font-size:11.5px;color:#1E293B;">
              Automatically assign all <strong>LS1 to LS6 modules</strong> and activities to the student's account.
            </span>
          </label>
        </div>

        <!-- Row 1: First Name & Middle Name -->
        <div class="enroll-grid-2col">
          <div>
            <label class="enroll-field-label">Name (First Name) *</label>
            <input type="text" name="first_name" required placeholder="Page: John" class="enroll-field-input">
          </div>
          <div>
            <label class="enroll-field-label">Middle Name (Middle Name)</label>
            <input type="text" name="middle_name" placeholder="Hal: Market" class="enroll-field-input">
          </div>
        </div>

        <!-- Row 2: Last Name & LRN / Student No. (Auto sequential) -->
        <div class="enroll-grid-2col">
          <div>
            <label class="enroll-field-label">Last Name *</label>
            <input type="text" name="last_name" required placeholder="Hal: Dela Cruz" class="enroll-field-input">
          </div>
          <div>
            <label class="enroll-field-label">LRN / Student No. (Auto if blank)</label>
            <input 
              type="text" 
              name="student_number" 
              value="<?= e($nextAutoStudentId) ?>" 
              placeholder="<?= e($nextAutoStudentId) ?>" 
              class="enroll-field-input" 
              style="font-family:ui-monospace,monospace;font-weight:700;color:#185ADB;"
              title="Awtomatikong binubuo mula 2026-000-001 hanggang 2026-000-040"
            >
          </div>
        </div>

        <!-- Row 3: Date of Birth & Gender -->
        <div class="enroll-grid-2col">
          <div>
            <label class="enroll-field-label">Date of Birth *</label>
            <input type="date" name="birthdate" value="2006-05-15" required class="enroll-field-input">
          </div>
          <div>
            <label class="enroll-field-label">Gender</label>
            <select name="gender" class="enroll-field-input">
              <option value="Male" selected>Male (Male)</option>
              <option value="Female">Female (Female)</option>
              <option value="Prefer not to say">Prefer not to say</option>
            </select>
          </div>
        </div>

        <!-- Row 4: Main Subject (Replaced label) & Levels/Program -->
        <div class="enroll-grid-2col">
          <div>
            <!-- LABEL CHANGED FROM "Main Strand (Focus) *" TO "Main Subject *" -->
            <label class="enroll-field-label">Main Subject *</label>
            <select name="main_subject" required class="enroll-field-input">
              <option value="LS1 - Communication Skills" selected>LS1 - Communication Skills</option>
              <option value="LS2 - Scientific Literacy & Critical Thinking">LS2 - Scientific Literacy & Critical Thinking</option>
              <option value="LS3 - Mathematical & Problem Solving Skills">LS3 - Mathematical & Problem Solving Skills</option>
              <option value="LS4 - Life & Career Skills">LS4 - Life & Career Skills</option>
              <option value="LS5 - Understanding the Self & Society">LS5 - Understanding the Self & Society</option>
              <option value="LS6 - Digital Literacy">LS6 - Digital Literacy</option>
            </select>
          </div>
          <div>
            <label class="enroll-field-label">Levels/Program *</label>
            <select name="learner_status" required class="enroll-field-input">
              <option value="Junior High School" selected>ALS Junior High School (JHS)</option>
              <option value="Elementary">ALS Elementary (Elem)</option>
            </select>
          </div>
        </div>

        <!-- Row 5: Phone Number & Email Address -->
        <div class="enroll-grid-2col">
          <div>
            <label class="enroll-field-label">Phone Number</label>
            <input type="text" name="contact_number" value="0917-000-0000" placeholder="0917-000-0000" class="enroll-field-input">
          </div>
          <div>
            <label class="enroll-field-label">Email Address (Optional)</label>
            <input type="email" name="email" placeholder="mag-aaral@deped.gov.ph" class="enroll-field-input">
          </div>
        </div>

        <!-- Row 6: Residence / Village -->
        <div style="margin-bottom:12px;">
          <label class="enroll-field-label">Residence/Village *</label>
          <input type="text" name="address" value="San Nicolas, Pasig City" required placeholder="San Nicolas, Pasig City" class="enroll-field-input">
        </div>

        <!-- Checklist of Mandatory Requirements (Matching Image 1) -->
        <div class="checklist-container-box">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
            <span style="font-size:14px;">📑</span>
            <strong style="font-size:12px;color:#0F172A;">Checklist of Mandatory Requirements</strong>
          </div>
          <span style="font-size:11px;color:#64748B;display:block;margin-bottom:10px;">
            Check the documents that have already been submitted:
          </span>

          <!-- Item 1: PSA Birth Certificate -->
          <div class="checklist-item-row">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="doc_psa" value="1" checked style="accent-color:#107C41;">
              <span>PSA Birth Certificate</span>
            </label>
            <span class="doc-pill-mandatory">Mandatory</span>
          </div>

          <!-- Item 2: Barangay Certificate / Clearance -->
          <div class="checklist-item-row">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="doc_brgy" value="1" checked style="accent-color:#107C41;">
              <span>Barangay Certificate / Clearance</span>
            </label>
            <span class="doc-pill-mandatory">Mandatory</span>
          </div>

          <!-- Item 3: Form 137 / Report Card -->
          <div class="checklist-item-row">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="doc_card" value="1" style="accent-color:#107C41;">
              <span>Form 137 / Report Card (Permanent Record)</span>
            </label>
            <span class="doc-pill-mandatory amber">Mandatory</span>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="als-enroll-modal-footer">
          <button type="button" onclick="closeEnrollModal()" class="btn-enroll-cancel">Cancel</button>
          <button type="submit" class="btn-enroll-submit">
            ✓ Save and Enroll the Student
          </button>
        </div>

      </form>
    </div>

  </div>
</div>

<script>
function openEnrollModal() {
  const modal = document.getElementById('enrollStudentModal');
  if (modal) {
    modal.classList.add('active');
  }
}

function closeEnrollModal() {
  const modal = document.getElementById('enrollStudentModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

// Close when clicking outside
window.addEventListener('click', function(e) {
  const modal = document.getElementById('enrollStudentModal');
  if (modal && e.target === modal) {
    closeEnrollModal();
  }
});
</script>

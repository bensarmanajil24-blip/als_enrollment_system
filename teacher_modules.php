<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

require_teacher();

$teacherId = (int)($_SESSION['teacher_id'] ?? 0);
$teacher = null;
if ($teacherId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = ?');
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch();
}

if (!$teacher) {
    $email = $_SESSION['user_email'] ?? 'elena.reyes@deped.gov.ph';
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE email = ?');
    $stmt->execute([$email]);
    $teacher = $stmt->fetch();
}

$teacherName = $teacher['name'] ?? ($_SESSION['user_name'] ?? 'Teacher Elena Reyes');
$assignedLs = $teacher['assigned_ls'] ?? 'LS1, LS4, LS6';
$assignedCenter = $teacher['assigned_center'] ?? 'San Nicolas Community Learning Center';

// Master Definitions for LS1 to LS6 Modules
$modules = [
    'LS1' => [
        'code' => 'LS1',
        'title' => 'Communication Skills (Pakikipagtalastasan)',
        'language' => 'Filipino at English',
        'desc' => 'Nakapokus sa pagbasa nang may pag-unawa, pagsulat ng mga sanaysay at pormal na liham, pakikinig, at mabisang pagpapahayag ng ideya sa pamilya at komunidad.',
        'competencies' => [
            'Pagpapalawak ng talasalitaan at pag-unawa sa binasa (Reading Comprehension)',
            'Pagsulat ng liham pangnegosyo, aplikasyon, at pormal na ulat',
            'Pakikipagtalastasan gamit ang angkop na balarila at tono sa trabaho'
        ],
        'lessons' => [
            'Aralin 1: Ang Wastong Balarila at Pagbuo ng Mabuting Pangungusap',
            'Aralin 2: Pagsusuri ng mga Tekstong Impormatibo at Sanaysay',
            'Aralin 3: Business Correspondence and Job Application Strategies'
        ],
        'color' => '#185ADB',
        'bgColor' => '#EBF2FD'
    ],
    'LS2' => [
        'code' => 'LS2',
        'title' => 'Scientific Literacy & Critical Thinking Skills',
        'language' => 'Agham at Siyentipikong Pag-iisip',
        'desc' => 'Paggamit ng siyentipikong pamamaraan sa paglutas ng problema, pangangalaga sa kalikasan, kalusugan, nutrisyon, at pag-iwas sa sakit.',
        'competencies' => [
            'Siyentipikong pamamaraan (Scientific Method) sa paggawa ng desisyon',
            'Ekolohikal na kamalayan, climate change, at waste management',
            'Wastong nutrisyon, kalinisan sa katawan, at pangunang lunas (First Aid)'
        ],
        'lessons' => [
            'Aralin 1: Siyentipikong Pagmamasid at Pagsusuri ng Ebidensya',
            'Aralin 2: Ekolohiya at Pangangalaga ng Likas-Yaman sa Barangay',
            'Aralin 3: Kalusugan ng Pamilya at Pamamahala ng Sakit'
        ],
        'color' => '#107C41',
        'bgColor' => '#EAF7EE'
    ],
    'LS3' => [
        'code' => 'LS3',
        'title' => 'Mathematical & Problem Solving Skills',
        'language' => 'Praktikal na Matematika',
        'desc' => 'Pag-aaral ng basic numeracy, fractions, ratio at proportion, percentages, pagsusuri ng datos, at financial literacy sa pang-araw-araw.',
        'competencies' => [
            'Pangunahing operasyon sa matematika, fractions, at decimals',
            'Porsyento, diskwento, interes sa pautang, at budget sa tahanan',
            'Pagsukat ng lawak, timbang, temperatura, at pagbasa ng graph'
        ],
        'lessons' => [
            'Aralin 1: Praktikal na Pagkukuwenta ng Kita, Gastos, at Ipon',
            'Aralin 2: Porsyento, Diskwento, at Simple Interest sa Pamilihan',
            'Aralin 3: Basic Statistics and Interpreting Community Survey Graphs'
        ],
        'color' => '#D97706',
        'bgColor' => '#FEF3C7'
    ],
    'LS4' => [
        'code' => 'LS4',
        'title' => 'Life & Career Skills (Kasanayan sa Hanapbuhay)',
        'language' => 'Paghahanda sa Trabaho at Negosyo',
        'desc' => 'Paghahanda para sa hanapbuhay, TVET courses, micro-entrepreneurship, work ethics, at kaligtasan sa lugar ng paggawa.',
        'competencies' => [
            'Pagpaplano ng sariling karera at pagtukoy sa kakayahan sa trabaho',
            'Pagsisimula at pamamahala ng sariling micro-business',
            'Work ethics, karapatan ng manggagawa, at Occupational Safety (OSH)'
        ],
        'lessons' => [
            'Aralin 1: Pagtukoy sa Sariling Kasanayan at Career Assessment',
            'Aralin 2: Pagbuo ng Simpleng Business Plan at Pagsukat ng Tubo',
            'Aralin 3: Kaligtasan sa Trabaho at Karapatan ng mga Manggagawa'
        ],
        'color' => '#7C3AED',
        'bgColor' => '#F3E8FF'
    ],
    'LS5' => [
        'code' => 'LS5',
        'title' => 'Understanding the Self & Society',
        'language' => 'Komunidad at Responsableng Pagkamamamayan',
        'desc' => 'Pag-unawa sa sarili, emosyonal na kalusugan, kasaysayan ng Pilipinas, karapatang pantao, batas, at aktibong pakikilahok sa komunidad.',
        'competencies' => [
            'Pagpapahalaga sa sarili, emosyonal na katatagan, at pamilya',
            'Kamalayang pangkasaysayan at pagpapahalaga sa kulturang Pilipino',
            'Karapatang pantao, kapayapaan, at pagsunod sa mga batas'
        ],
        'lessons' => [
            'Aralin 1: Ang Aking Tungkulin sa Aking Sarili at Pamilya',
            'Aralin 2: Karapatang Pantao at Responsableng Pagsunod sa Batas',
            'Aralin 3: Pakikilahok sa mga Proyekto at Serbisyo sa Barangay'
        ],
        'color' => '#C5221F',
        'bgColor' => '#FCE8E6'
    ],
    'LS6' => [
        'code' => 'LS6',
        'title' => 'Digital Literacy (Kasanayang Digital)',
        'language' => 'Paggamit ng Computer at Internet',
        'desc' => 'Paggamit ng computer at smartphone, paggawa ng dokumento at spreadsheet, ligtas na pag-surf sa internet, at cyber hygiene.',
        'competencies' => [
            'Pangunahing bahagi at paggamit ng computer at smartphone',
            'Paggawa ng dokumento (Word Processor) at simple spreadsheets',
            'Ligtas at responsableng paggamit ng internet, email, at social media'
        ],
        'lessons' => [
            'Aralin 1: Introduksyon sa Computer Hardware, OS, at File Management',
            'Aralin 2: Paggawa ng Dokumento at Resume gamit ang Word Processor',
            'Aralin 3: Cyber Security, Fact-Checking, at Pag-iwas sa Fake News'
        ],
        'color' => '#0284C7',
        'bgColor' => '#E0F2FE'
    ]
];

// Student counts per strand
$strandLearnersCount = [];
foreach ($modules as $k => $m) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE is_archived = 0 AND strand LIKE ?");
    $stmt->execute(['%' . $k . '%']);
    $strandLearnersCount[$k] = (int)$stmt->fetchColumn();
}
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DepEd ALS - Learning Modules (LS1-LS6) | Dalubguro Portal</title>
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .modules-card-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-top: 14px;
    }
    @media (max-width: 1024px) {
      .modules-card-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    .module-item-box {
      background: #FFFFFF;
      border: 1px solid var(--deped-border-subtle);
      border-radius: 14px;
      padding: 18px 20px;
      display: flex;
      flex-direction: column;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
      transition: all 0.2s ease;
    }
    .module-item-box:hover {
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
      border-color: #94A3B8;
    }
    .module-badge-num {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: grid;
      place-items: center;
      font-size: 13px;
      font-weight: 850;
      color: #fff;
    }
    .btn-download-module {
      background: #107C41;
      color: #FFFFFF;
      text-decoration: none;
      font-size: 12px;
      font-weight: 750;
      padding: 8px 12px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.15s ease;
      margin-top: auto;
    }
    .btn-download-module:hover {
      background: #0E6B37;
    }

    /* Modal for Module Preview */
    .deped-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(7, 22, 44, 0.65);
      backdrop-filter: blur(4px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 16px;
      box-sizing: border-box;
    }
    .deped-modal-box {
      background: #FFFFFF;
      width: 100%;
      max-width: 600px;
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
      border: 1px solid #E2E8F0;
      overflow: hidden;
      animation: modalFadeIn 0.2s ease-out;
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.96); }
      to { opacity: 1; transform: scale(1); }
    }
    .deped-modal-header {
      background: linear-gradient(135deg, #07162C 0%, #0B1E3B 100%);
      color: #FFFFFF;
      padding: 18px 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2.5px solid #F4BB4F;
    }
    .deped-modal-body {
      padding: 20px 24px;
      max-height: 80vh;
      overflow-y: auto;
    }
    .deped-modal-footer {
      padding: 14px 24px;
      background: #F8FAFD;
      border-top: 1px solid #E2E8F0;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
  </style>
</head>
<body class="admin-body">

<div class="deped-laptop-layout">

  <!-- ==================== SIDEBAR ==================== -->
  <aside class="deped-sidebar">
    <a href="teacher_dashboard.php" class="deped-sidebar-brand">
      <div class="deped-brand-logo-icon" style="background:#107C41;color:#fff;">
        👨‍🏫
      </div>
      <div class="deped-brand-text">
        <h2>DepEd ALS</h2>
        <span style="color:#86EFAC;">TEACHER PORTAL</span>
      </div>
    </a>

    <div class="sidebar-section-title">INSTRUCTIONAL MANAGER</div>
    <nav class="sidebar-nav-group">
      <a href="teacher_dashboard.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>Dashboard</span>
      </a>
      <a href="teacher_assigned_learners.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        <span>Assigned Learners</span>
      </a>
      <!-- Active Link -->
      <a href="teacher_modules.php" class="deped-nav-link active" style="background:#107C41;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        <span>Learning Modules (LS1-LS6)</span>
      </a>
      <a href="teacher_attendance.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>Attendance Tracker</span>
      </a>
      <a href="admin_scanner.php" class="deped-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg>
        <span>Community QR Scanner</span>
      </a>
    </nav>

    <div class="sidebar-footer-info">
      <span><?= e($teacherName) ?></span>
      <a href="logout.php" style="color:#F87171;text-decoration:none;font-weight:700;">Logout</a>
    </div>
  </aside>

  <!-- ==================== MAIN VIEWPORT ==================== -->
  <div class="deped-main-viewport">

    <header class="deped-top-header">
      <div style="font-weight:750;font-size:13.5px;color:var(--deped-text-dark);display:flex;align-items:center;gap:8px;">
        <span class="stat-box-sub-pill green">📚 Dedicated Page</span>
        <span>Learning Modules Repository (LS1 hanggang LS6)</span>
      </div>

      <div class="header-user-profile">
        <div class="user-profile-badge">
          <div class="user-avatar-circle" style="background:#107C41;">IM</div>
          <div class="user-info-text">
            <strong><?= e($teacherName) ?></strong>
            <span><?= e($assignedLs) ?></span>
          </div>
        </div>
        <a href="logout.php" class="toolbar-btn" style="color:#C5221F;">Mag-logout</a>
      </div>
    </header>

    <main class="deped-workspace-container">

      <!-- Header Description -->
      <div class="deped-panel-card" style="padding:18px 22px;border-left:4px solid #107C41;">
        <h2 style="font-size:16px;font-weight:850;color:var(--deped-navy-dark);margin:0 0 6px 0;">
          Opisyal na DepEd ALS Learning Activity Packages (LS1 - LS6)
        </h2>
        <p style="font-size:12px;color:var(--deped-text-muted);margin:0;">
          Lahat ng anim (6) na Learning Strands ay ganap nang functional at interactive. Pindutin ang bawat modyul upang suriin ang mga aralin, learning competencies, at i-download ang opisyal na module activity package para sa klase.
        </p>
      </div>

      <!-- 6 Learning Strands Grid -->
      <div class="modules-card-grid">
        <?php foreach ($modules as $k => $m): ?>
          <div class="module-item-box">
            
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="module-badge-num" style="background:<?= $m['color'] ?>;">
                  <?= $k ?>
                </div>
                <div>
                  <strong style="font-size:13.5px;color:var(--deped-navy-dark);display:block;"><?= e($m['title']) ?></strong>
                  <span style="font-size:11px;color:var(--deped-text-muted);"><?= e($m['language']) ?></span>
                </div>
              </div>
            </div>

            <p style="font-size:12px;color:#475569;line-height:1.45;margin:0 0 14px 0;flex:1;">
              <?= e($m['desc']) ?>
            </p>

            <div style="background:<?= $m['bgColor'] ?>;border-radius:8px;padding:8px 12px;margin-bottom:14px;">
              <span style="font-size:10.5px;font-weight:800;color:<?= $m['color'] ?>;display:block;margin-bottom:4px;">
                MGA PANGUNAHING KASANAYAN:
              </span>
              <ul style="margin:0;padding-left:16px;font-size:11px;color:#334155;">
                <?php foreach (array_slice($m['competencies'], 0, 2) as $c): ?>
                  <li><?= e($c) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div style="display:flex;gap:8px;">
              <button 
                type="button" 
                class="toolbar-btn" 
                style="flex:1;justify-content:center;font-size:11.5px;"
                onclick='openModuleModal(<?= json_encode($m) ?>)'
              >
                👁️ Suriin ang Aralin
              </button>

              <a 
                href="download_module.php?ls=<?= $k ?>" 
                class="btn-download-module" 
                title="I-download ang buong modyul package"
              >
                ⬇ I-download
              </a>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

    </main>

  </div>

</div>

<!-- ==================== MODAL: MODULE LESSONS & COMPETENCIES PREVIEW ==================== -->
<div class="deped-modal-backdrop" id="moduleModal">
  <div class="deped-modal-box">
    <div class="deped-modal-header">
      <div style="display:flex;align-items:center;gap:8px;">
        <span style="font-size:16px;">📚</span>
        <div>
          <h3 style="font-size:15px;font-weight:800;margin:0;color:#fff;" id="modalModuleTitle">Module Title</h3>
          <span style="font-size:11px;color:#CBD5E1;" id="modalModuleCode">LS Details</span>
        </div>
      </div>
      <button type="button" onclick="closeModuleModal()" style="background:none;border:none;color:#CBD5E1;font-size:20px;cursor:pointer;">&times;</button>
    </div>

    <div class="deped-modal-body">
      <p style="font-size:12.5px;color:#334155;line-height:1.5;margin-top:0;" id="modalModuleDesc"></p>

      <strong style="font-size:12.5px;color:#0B1E3B;display:block;margin-bottom:6px;">
        Mga Aralin sa Modyul na Ito:
      </strong>
      <div style="background:#F8FAFD;border:1px solid #CBD5E1;border-radius:10px;padding:10px 14px;margin-bottom:14px;" id="modalLessonsContainer">
      </div>

      <strong style="font-size:12.5px;color:#0B1E3B;display:block;margin-bottom:6px;">
        Learning Competencies:
      </strong>
      <div style="background:#F1F5F9;border:1px solid #CBD5E1;border-radius:10px;padding:10px 14px;margin-bottom:14px;" id="modalCompetenciesContainer">
      </div>
    </div>

    <div class="deped-modal-footer">
      <button type="button" onclick="closeModuleModal()" class="toolbar-btn">Isara</button>
      <a href="#" id="modalDownloadLink" class="btn-download-module" style="margin-top:0;">
        ⬇ I-download ang Opisyal na Package
      </a>
    </div>
  </div>
</div>

<script>
function openModuleModal(mod) {
  document.getElementById('modalModuleTitle').innerText = mod.title;
  document.getElementById('modalModuleCode').innerText = mod.code + ' • ' + mod.language;
  document.getElementById('modalModuleDesc').innerText = mod.desc;

  // Render lessons
  const lessonsBox = document.getElementById('modalLessonsContainer');
  lessonsBox.innerHTML = '';
  mod.lessons.forEach((les, idx) => {
    const row = document.createElement('div');
    row.style.padding = '5px 0';
    row.style.borderBottom = idx < mod.lessons.length - 1 ? '1px dashed #E2E8F0' : 'none';
    row.style.fontSize = '12px';
    row.innerHTML = '<strong>' + les + '</strong><span style="display:block;font-size:10.5px;color:#64748B;">Kasama ang formative exercises at portfolio checklist.</span>';
    lessonsBox.appendChild(row);
  });

  // Render competencies
  const compBox = document.getElementById('modalCompetenciesContainer');
  compBox.innerHTML = '';
  const ul = document.createElement('ul');
  ul.style.margin = '0';
  ul.style.paddingLeft = '18px';
  ul.style.fontSize = '12px';
  ul.style.color = '#334155';
  mod.competencies.forEach(c => {
    const li = document.createElement('li');
    li.innerText = c;
    ul.appendChild(li);
  });
  compBox.appendChild(ul);

  // Set download link
  document.getElementById('modalDownloadLink').href = 'download_module.php?ls=' + mod.code;

  document.getElementById('moduleModal').style.display = 'flex';
}

function closeModuleModal() {
  document.getElementById('moduleModal').style.display = 'none';
}

window.addEventListener('click', function(event) {
  const modal = document.getElementById('moduleModal');
  if (event.target === modal) closeModuleModal();
});
</script>

</body>
</html>


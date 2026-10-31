<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
?>
<!doctype html>
<html lang="tl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ALS Connect • Abutin ang Pangarap sa Tulong ng ALS</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    /* Home Page Specific Layout Enhancements */
    .hero-grid {
      display: grid;
      grid-template-columns: 1.15fr 0.85fr;
      gap: 40px;
      align-items: center;
      margin-bottom: 48px;
    }

    .hero-pill-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--color-fresh-green-light);
      color: var(--color-fresh-green);
      border: 1px solid #B8DEC7;
      padding: 6px 14px;
      border-radius: var(--radius-pill);
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 16px;
    }

    .hero-title {
      font-size: clamp(38px, 4.5vw, 56px);
      font-weight: 850;
      line-height: 1.12;
      letter-spacing: -0.03em;
      color: var(--color-deep-navy);
      margin-bottom: 16px;
    }

    .hero-title span.highlight-blue {
      color: var(--color-trust-blue);
    }

    .hero-actions {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 28px 0 14px;
      flex-wrap: wrap;
    }

    /* Right Hero Visual Card */
    .hero-visual-card {
      position: relative;
      background: #DCE7F5;
      border-radius: 24px;
      overflow: hidden;
      min-height: 380px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-card);
    }

    .hero-illustration {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .hero-floating-card {
      position: absolute;
      bottom: 20px;
      left: 20px;
      right: 20px;
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.8);
      border-radius: 16px;
      padding: 16px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 10px 25px -5px rgba(18, 53, 87, 0.12);
    }

    .floating-card-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--color-warm-gold-light);
      color: #925C05;
      border: 1px solid #F8D88E;
      display: grid;
      place-items: center;
      flex-shrink: 0;
    }

    /* Feature Ribbon (3 items) */
    .features-ribbon {
      background: var(--color-white);
      border-top: 1px solid var(--color-border);
      border-bottom: 1px solid var(--color-border);
      padding: 18px 24px;
      display: flex;
      justify-content: space-around;
      align-items: center;
      gap: 20px;
      margin-bottom: 64px;
    }

    .ribbon-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      font-weight: 600;
      color: var(--color-deep-navy);
    }

    .ribbon-icon {
      width: 20px;
      height: 20px;
      color: var(--color-trust-blue);
    }

    /* Program Cards Grid */
    .programs-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      margin-top: 28px;
      margin-bottom: 32px;
    }

    .program-card {
      background: var(--color-white);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-card);
      padding: 32px;
      display: flex;
      flex-direction: column;
      box-shadow: var(--shadow-subtle);
      transition: all 0.2s ease;
    }

    .program-card:hover {
      border-color: var(--color-trust-blue);
      box-shadow: var(--shadow-card);
    }

    .program-top-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
    }

    .program-icon-box {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: grid;
      place-items: center;
    }

    .program-icon-box.blue { background: #EBF3FC; color: var(--color-trust-blue); }
    .program-icon-box.green { background: var(--color-fresh-green-light); color: var(--color-fresh-green); }

    .program-tag {
      font-size: 11px;
      font-weight: 750;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--color-trust-blue);
    }

    .program-bullets {
      list-style: none;
      margin: 20px 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .program-bullets li {
      font-size: 13.5px;
      color: var(--color-slate);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .program-link {
      margin-top: auto;
      font-size: 14px;
      font-weight: 700;
      color: var(--color-trust-blue);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .program-link:hover {
      text-decoration: underline;
    }

    /* 3 Steps Cards */
    .steps-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-top: 28px;
    }

    .step-card {
      background: var(--color-white);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-card);
      padding: 28px 24px;
      box-shadow: var(--shadow-subtle);
    }

    .step-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }

    .step-number {
      font-size: 26px;
      font-weight: 800;
      color: var(--color-trust-blue);
      letter-spacing: -0.02em;
    }

    .step-icon {
      width: 22px;
      height: 22px;
      color: var(--color-slate);
    }

    /* Navy Support Callout Banner */
    .navy-support-banner {
      background: var(--color-deep-navy);
      color: #ffffff;
      border-radius: var(--radius-card);
      padding: 44px;
      margin: 64px 0;
      box-shadow: 0 12px 36px rgba(18, 53, 87, 0.15);
    }

    .support-bullet-row {
      display: flex;
      gap: 24px;
      margin: 24px 0 28px;
      flex-wrap: wrap;
    }

    .support-bullet {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: #C3D5E8;
    }

    /* FAQ Section */
    .faq-grid {
      display: grid;
      grid-template-columns: 0.9fr 1.1fr;
      gap: 48px;
      align-items: flex-start;
      margin: 64px 0;
    }

    .faq-item {
      background: var(--color-white);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-control);
      padding: 20px 22px;
      margin-bottom: 14px;
      box-shadow: var(--shadow-subtle);
    }

    .faq-question {
      font-size: 15.5px;
      font-weight: 700;
      color: var(--color-deep-navy);
      margin-bottom: 8px;
    }

    .faq-answer {
      font-size: 14px;
      line-height: 1.55;
      color: var(--color-slate);
    }

    /* Pre-Footer CTA */
    .prefooter-cta {
      background: var(--color-white);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-hero);
      padding: 56px 24px;
      text-align: center;
      margin: 64px 0 40px;
      box-shadow: var(--shadow-card);
    }

    .prefooter-sparkle {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: var(--color-warm-gold-light);
      color: #925C05;
      border: 1px solid #F8D88E;
      display: grid;
      place-items: center;
      margin: 0 auto 18px;
    }

    @media (max-width: 900px) {
      .hero-grid,
      .programs-grid,
      .steps-grid,
      .faq-grid {
        grid-template-columns: 1fr;
        gap: 24px;
      }

      .features-ribbon {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
    }
  </style>
</head>
<body>

<!-- Header Navigation Bar -->
<header class="site-header">
  <div class="header-inner">
    <a class="site-brand" href="index.php">
      <div class="brand-badge">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      </div>
      <div>
        <div class="brand-name">ALS Connect</div>
        <span class="brand-tag">PAGKATUTO PARA SA LAHAT</span>
      </div>
    </a>

    <nav style="display:flex;align-items:center;gap:18px;">
      <a href="#programs" class="nav-link" style="color:var(--color-deep-navy);">Mga Programa</a>
      <a href="#how-to-enroll" class="nav-link" style="color:var(--color-deep-navy);">Paano Mag-enroll</a>
      <a href="#faqs" class="nav-link" style="color:var(--color-deep-navy);">Mga Tanong</a>
      <a href="login.php" class="btn btn-secondary" style="min-height:42px;padding:0 16px;font-size:14px;">Portal Login</a>
      <a href="enroll.php" class="btn btn-primary" style="min-height:42px;padding:0 18px;font-size:14px;">Mag-enroll Na</a>
    </nav>
  </div>
</header>

<main class="container">

  <!-- 1. HERO SECTION (Image 2) -->
  <section class="hero-grid">
    <div>
      <div class="hero-pill-badge">
        <span>🌱</span> May bagong simula para sa iyo
      </div>

      <h1 class="hero-title">
        Abutin ang Pangarap sa Tulong ng <span class="highlight-blue">ALS.</span>
      </h1>

      <p class="body-lead" style="margin-bottom:20px;">
        Hindi pa huli para magpatuloy. Tuklasin ang alternatibong paraan ng pag-aaral para sa Elementary at Junior High School.
      </p>

      <div class="hero-actions">
        <a href="enroll.php" class="btn btn-primary" style="font-size:16px;">
          Mag-enroll Na &rarr;
        </a>
        <a href="#programs" class="btn btn-secondary" style="font-size:16px;">
          Kilalanin ang ALS
        </a>
      </div>

      <p class="muted-text">May gabay sa bawat hakbang ng aplikasyon.</p>
    </div>

    <!-- Right Hero Visual Card -->
    <div class="hero-visual-card">
      <!-- SVG Learning Classroom Illustration -->
      <svg class="hero-illustration" viewBox="0 0 500 380" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="500" height="380" fill="#E8F1FC"/>
        <!-- Window frame -->
        <rect x="260" y="30" width="200" height="180" rx="8" fill="#D2E3F7" stroke="#A9C7ED" stroke-width="4"/>
        <line x1="360" y1="30" x2="360" y2="210" stroke="#A9C7ED" stroke-width="4"/>
        <line x1="260" y1="120" x2="460" y2="120" stroke="#A9C7ED" stroke-width="4"/>
        <!-- Classroom Desk -->
        <rect x="60" y="240" width="380" height="90" rx="8" fill="#C5D9F2"/>
        <!-- Learners -->
        <circle cx="160" cy="180" r="32" fill="#1455A0"/>
        <path d="M120 250 C120 220, 200 220, 200 250" fill="#1455A0"/>
        <circle cx="340" cy="175" r="32" fill="#21734B"/>
        <path d="M300 250 C300 215, 380 215, 380 250" fill="#21734B"/>
        <circle cx="250" cy="165" r="30" fill="#F4BB4F"/>
        <path d="M215 250 C215 210, 285 210, 285 250" fill="#123557"/>
        <!-- Books on table -->
        <rect x="200" y="235" width="45" height="12" rx="2" fill="#ffffff"/>
        <rect x="255" y="232" width="50" height="15" rx="2" fill="#F4BB4F"/>
      </svg>

      <!-- Floating Encouragement Card -->
      <div class="hero-floating-card">
        <div class="floating-card-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        </div>
        <div>
          <strong style="display:block;font-size:14px;color:var(--color-deep-navy);">Ang pangarap, puwedeng ituloy.</strong>
          <span style="font-size:12.5px;color:var(--color-slate);">Magsimula sa isang maliit na hakbang.</span>
        </div>
      </div>
    </div>
  </section>

</main>

<!-- 2. FEATURES RIBBON (Image 2) -->
<div class="features-ribbon">
  <div class="ribbon-item">
    <svg class="ribbon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
    <span>Pagkakataong akma sa iyo</span>
  </div>
  <div class="ribbon-item">
    <svg class="ribbon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
    <span>Gabay mula sa ALS coordinator</span>
  </div>
  <div class="ribbon-item">
    <svg class="ribbon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
    <span>Mag-apply sa cellphone o computer</span>
  </div>
</div>

<main class="container">

  <!-- 3. MGA PROGRAMA SECTION (Image 2) -->
  <section id="programs" style="margin-bottom:64px;">
    <span class="eyebrow">MGA PROGRAMA</span>
    <h2 class="page-heading">May landas para sa iyong pangarap.</h2>
    <p class="muted-text" style="font-size:15px;max-width:700px;">
      Piliin ang antas na nais mong ipagpatuloy. Tutulungan ka ng ALS coordinator na matukoy ang angkop na programa.
    </p>

    <div class="programs-grid">
      <!-- Card 1: ALS Elementary -->
      <div class="program-card">
        <div class="program-top-row">
          <div class="program-icon-box blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          </div>
          <span class="program-tag">ALS PROGRAM</span>
        </div>
        <h3 style="font-size:20px;margin-bottom:8px;">ALS Elementary</h3>
        <p class="muted-text">
          Patatagin ang pundasyon sa pagbasa, pagsulat, pagbilang, at mga kasanayan sa araw-araw.
        </p>
        <ul class="program-bullets">
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--color-fresh-green);"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Para sa nais magpatuloy sa antas Elementary
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--color-fresh-green);"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Pag-aaral na may gabay at praktikal na gawain
          </li>
        </ul>
        <a href="enroll.php?program=Elementary" class="program-link">
          Piliin sa enrollment &rarr;
        </a>
      </div>

      <!-- Card 2: ALS Junior High School -->
      <div class="program-card">
        <div class="program-top-row">
          <div class="program-icon-box green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
          </div>
          <span class="program-tag" style="color:var(--color-fresh-green);">ALS PROGRAM</span>
        </div>
        <h3 style="font-size:20px;margin-bottom:8px;">ALS Junior High School</h3>
        <p class="muted-text">
          Ipagpatuloy ang pagkatuto at palawakin ang kaalaman para sa susunod mong layunin.
        </p>
        <ul class="program-bullets">
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--color-fresh-green);"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Para sa nais magpatuloy sa antas Junior High
          </li>
          <li>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--color-fresh-green);"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Kasanayang magagamit sa buhay at komunidad
          </li>
        </ul>
        <a href="enroll.php?program=Junior+High+School" class="program-link" style="color:var(--color-fresh-green);">
          Piliin sa enrollment &rarr;
        </a>
      </div>
    </div>

    <!-- Info Callout Card -->
    <div class="card-panel" style="padding:28px 32px;background:#fff;border-color:var(--color-border);">
      <span class="eyebrow">PARA KANINO ANG ALS?</span>
      <h3 style="font-size:20px;margin-bottom:8px;">Babalik sa pag-aaral? Tara, pag-usapan natin.</h3>
      <p class="muted-text" style="margin-bottom:18px;">
        Maaaring maging landas ang ALS para sa mga hindi nakatapos ng batayang edukasyon at naghahanap ng alternatibong paraan ng pagkatuto.
      </p>

      <div class="feedback-banner info" style="margin-bottom:0;align-items:center;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <div style="font-size:13.5px;color:var(--color-deep-navy);">
          <strong>Alamin muna ang angkop sa iyo.</strong> Makipag-ugnayan sa lokal na ALS coordinator para sa eligibility, assessment, iskedyul, at mga kinakailangang dokumento. Maaaring mag-iba ang proseso ayon sa learning center.
        </div>
      </div>
    </div>
  </section>

  <!-- 4. PAANO MAG-ENROLL SECTION (Image 3) -->
  <section id="how-to-enroll" style="margin-bottom:64px;">
    <span class="eyebrow">PAANO MAG-ENROLL</span>
    <h2 class="page-heading">Tatlong hakbang. Isang bagong simula.</h2>
    <p class="muted-text" style="font-size:15px;">Simple at malinaw ang pag-apply, kahit cellphone lang ang gamit mo.</p>

    <div class="steps-grid">
      <!-- Step 01 -->
      <div class="step-card">
        <div class="step-header">
          <span class="step-number">01</span>
          <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        </div>
        <h4 style="font-size:16px;margin-bottom:6px;">Ikuwento ang tungkol sa iyo</h4>
        <p class="muted-text" style="font-size:13.5px;">Sagutan ang personal na impormasyon at huling antas na napag-aralan.</p>
      </div>

      <!-- Step 02 -->
      <div class="step-card">
        <div class="step-header">
          <span class="step-number">02</span>
          <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
        <h4 style="font-size:16px;margin-bottom:6px;">Piliin ang nais mong antas</h4>
        <p class="muted-text" style="font-size:13.5px;">Piliin ang Elementary o Junior High at magdagdag ng dokumento kung mayroon.</p>
      </div>

      <!-- Step 03 -->
      <div class="step-card">
        <div class="step-header">
          <span class="step-number">03</span>
          <svg class="step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </div>
        <h4 style="font-size:16px;margin-bottom:6px;">Isumite at hintayin ang gabay</h4>
        <p class="muted-text" style="font-size:13.5px;">Itabi ang tracking number. Susuriin ang aplikasyon at kokontakin ka para sa susunod na hakbang.</p>
      </div>
    </div>
  </section>

  <!-- 5. NAVY SUPPORT CALLOUT BANNER (Image 3) -->
  <div class="navy-support-banner">
    <div style="font-size:11px;font-weight:750;letter-spacing:0.1em;text-transform:uppercase;color:var(--color-warm-gold);margin-bottom:8px;">
      HINDI MO KAILANGANG MAG-ISA
    </div>
    <h2 style="font-size:28px;color:#fff;margin-bottom:10px;">May tanong? May makikinig.</h2>
    <p style="font-size:15px;color:#C3D5E8;max-width:720px;line-height:1.55;">
      Unang beses mag-apply online, kulang ang dokumento, o hindi sigurado sa antas? Makipag-usap sa lokal na ALS coordinator bago magpatuloy.
    </p>

    <div class="support-bullet-row">
      <div class="support-bullet">
        <span>🏠</span> Bumisita sa learning center
      </div>
      <div class="support-bullet">
        <span>💬</span> Humingi ng gabay sa coordinator
      </div>
      <div class="support-bullet">
        <span>🔒</span> Ibahagi lang ang kailangang datos
      </div>
    </div>

    <a href="#faqs" class="btn btn-warm" style="font-size:14px;min-height:44px;padding:0 20px;">
      Basahin ang mga karaniwang tanong
    </a>
  </div>

  <!-- 6. MGA KARANIWANG TANONG (FAQ) (Image 3) -->
  <section id="faqs" class="faq-grid">
    <div>
      <span class="eyebrow">MGA KARANIWANG TANONG</span>
      <h2 class="page-heading">Linawin natin ang susunod mong hakbang.</h2>
      <p class="muted-text" style="font-size:15px;margin-bottom:20px;">
        Mga simpleng sagot para makapagsimula nang may kumpiyansa.
      </p>
      <a href="enroll.php" class="btn btn-secondary" style="font-size:13.5px;min-height:42px;">
        Lumapit sa lokal na ALS coordinator &rarr;
      </a>
    </div>

    <div>
      <div class="faq-item">
        <div class="faq-question">Ano ang Alternative Learning System?</div>
        <div class="faq-answer">
          Ang ALS ay alternatibong paraan upang makapagpatuloy ng batayang edukasyon sa labas ng karaniwang pormal na paaralan.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">Paano ko malalaman ang angkop na antas?</div>
        <div class="faq-answer">
          Ibahagi ang huling baitang na napag-aralan. Gagabayan ka ng ALS coordinator sa assessment at angkop na programa.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">Paano kung wala pa akong dokumento?</div>
        <div class="faq-answer">
          Sa konseptong form na ito, puwedeng laktawan ang upload. Kumpirmahin sa iyong learning center ang tunay na requirements bago mag-aral.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">Ano ang mangyayari pagkatapos mag-apply?</div>
        <div class="faq-answer">
          Itabi ang tracking number. Hihintayin mo ang pag-review at pakikipag-ugnayan ng coordinator. Ang pagpapasumite ay hindi pa awtomatikong pagtanggap.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">May bayad ba o takdang iskedyul?</div>
        <div class="faq-answer">
          Humingi ng kasalukuyang impormasyon sa lokal na ALS coordinator tungkol sa iskedyul, proseso, at anumang kaugnay na gastusin.
        </div>
      </div>
    </div>
  </section>

  <!-- 7. PRE-FOOTER CTA SECTION (Image 3) -->
  <section class="prefooter-cta">
    <div class="prefooter-sparkle">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
    </div>

    <h2 class="page-heading">Ang susunod na kabanata, simulan na.</h2>
    <p class="body-lead" style="max-width:620px;margin:0 auto 28px;">
      Isang aplikasyon ang unang hakbang. Nandito ang ALS para tulungan kang maipagpatuloy ang iyong pagkatuto.
    </p>

    <div style="display:flex;justify-content:center;">
      <a href="enroll.php" class="btn btn-primary" style="font-size:16px;min-height:52px;padding:0 32px;">
        Mag-enroll Na &rarr;
      </a>
    </div>

    <p class="muted-text" style="margin-top:14px;font-size:13px;">May tanong muna? Basahin ang gabay sa itaas.</p>
  </section>

</main>

<!-- Footer (Image 3) -->
<footer class="site-footer">
  <div style="max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:16px;">
    <div class="site-brand">
      <div class="brand-badge" style="width:32px;height:32px;font-size:14px;">ALS</div>
      <div style="text-align:left;">
        <div class="brand-name" style="font-size:16px;">ALS Connect</div>
        <span class="brand-tag">PAGKATUTO PARA SA LAHAT</span>
      </div>
    </div>

    <div style="display:flex;gap:20px;font-size:13px;">
      <a href="#programs" style="color:var(--color-slate);text-decoration:none;">Privacy at Datos</a>
      <a href="#how-to-enroll" style="color:var(--color-slate);text-decoration:none;">Accessibility</a>
      <a href="login.php" style="color:var(--color-trust-blue);font-weight:600;text-decoration:none;">Portal Login</a>
    </div>
  </div>

  <div style="font-size:12px;color:var(--color-slate);max-width:850px;margin:0 auto;line-height:1.5;">
    ALS Connect • Konseptong disenyo lamang. Hindi opisyal na portal ng DepEd at walang pinapanatili o pinapatakbong opisyal na serbisyo. Kumpirmahin ang programa sa pinakamalapit na lokal na ALS coordinator.
  </div>
</footer>

</body>
</html>

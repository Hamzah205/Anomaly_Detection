<?php require_once __DIR__ . "/bootstrap.php";
// No closing PHP tag — prevents "headers already sent"
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PDAM Anomaly Detection - Sistem Deteksi Anomali Cerdas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- SVG Icons Sprite -->
  <svg style="display: none;">
    <defs>
      <symbol id="icon-search" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></symbol>
      <symbol id="icon-robot" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7v4a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-4a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M6 13a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1H6m3-9a1 1 0 1 0 0 2 1 1 0 0 0 0-2m6 11a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-6 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/></symbol>
      <symbol id="icon-chart" viewBox="0 0 24 24"><path fill="currentColor" d="M3 13h2v8H3zm4-8h2v16H7zm4-2h2v18h-2zm4 4h2v14h-2zm4-2h2v16h-2z"/></symbol>
      <symbol id="icon-target" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 0 0 2 12a10 10 0 0 0 10 10 10 10 0 0 0 10-10A10 10 0 0 0 12 2m0 2a8 8 0 0 1 8 8 8 8 0 0 1-8 8 8 8 0 0 1-8-8 8 8 0 0 1 8-8m0 2a6 6 0 0 0-6 6 6 6 0 0 0 6 6 6 6 0 0 0 6-6 6 6 0 0 0-6-6m0 2a4 4 0 0 1 4 4 4 4 0 0 1-4 4 4 4 0 0 1-4-4 4 4 0 0 1 4-4z"/></symbol>
      <symbol id="icon-users" viewBox="0 0 24 24"><path fill="currentColor" d="M12 5.5A3.5 3.5 0 0 1 15.5 9a3.5 3.5 0 0 1-3.5 3.5A3.5 3.5 0 0 1 8.5 9 3.5 3.5 0 0 1 12 5.5M5 8a3 3 0 0 1 3 3 3 3 0 0 1-3 3 3 3 0 0 1-3-3 3 3 0 0 1 3-3m14 0a3 3 0 0 1 3 3 3 3 0 0 1-3 3 3 3 0 0 1-3-3 3 3 0 0 1 3-3M12 12.5c2.67 0 8 1.33 8 4v2H4v-2c0-2.67 5.33-4 8-4m-8.5 4.5h2.05c.11-.67.32-1.28.6-1.82.5.23 1.05.43 1.64.58-.46.54-.88 1.16-1.24 1.82H4.5v-.58m15 0v.58h-2.05c-.36-.66-.78-1.28-1.24-1.82.59-.15 1.14-.35 1.64-.58.28.54.49 1.15.6 1.82z"/></symbol>
      <symbol id="icon-bolt" viewBox="0 0 24 24"><path fill="currentColor" d="M11 15H6l7-14v8h5l-7 14v-8z"/></symbol>
      <symbol id="icon-sparkle" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2L9 7l-5 3 5 3 3 5 3-5 5-3-5-3-3-5z"/></symbol>
      <symbol id="icon-upload" viewBox="0 0 24 24"><path fill="currentColor" d="M9 16v-6H5l7-7 7 7h-4v6H9m-4 2v2h10v-2H5z"/></symbol>
      <symbol id="icon-settings" viewBox="0 0 24 24"><path fill="currentColor" d="M12 15.5A3.5 3.5 0 0 1 8.5 12 3.5 3.5 0 0 1 12 8.5a3.5 3.5 0 0 1 3.5 3.5 3.5 3.5 0 0 1-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97 0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.5.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.63c-.04.34-.07.67-.07 1 0 .33.03.66.07.97l-2.11 1.63c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1.01c.22.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.63z"/></symbol>
      <symbol id="icon-play" viewBox="0 0 24 24"><path fill="currentColor" d="M8 5v14l11-7z"/></symbol>
      <symbol id="icon-arrow" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></symbol>
      <symbol id="icon-moon" viewBox="0 0 24 24"><path fill="currentColor" d="M9 2a7 7 0 1 0 9.4 10.5 9 9 0 0 1-5.4-4.5A9 9 0 0 1 9 2m0 2a7 7 0 0 0 5.4 9.5 7 7 0 1 1-5.4-9.5z"/></symbol>
      <symbol id="icon-sun" viewBox="0 0 24 24"><path fill="currentColor" d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5M2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1m18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1M11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1m0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1M5.99 4.58a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58m12.37 12.37a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0 .39-.39.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36a.996.996 0 0 0 0 1.41.996.996 0 0 0 1.41 0l1.06-1.06c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06z"/></symbol>
    </defs>
  </svg>
  <style>
    /* ===== GLOBAL SVG SIZING FIX ===== */
    svg {
      display: inline-block;
      vertical-align: middle;
    }
    
    /* Ensure all icon SVGs have proper sizing */
    .nav-links svg,
    .btn-nav svg,
    .btn-primary svg,
    .btn-secondary svg,
    .hero-badge svg,
    .section-label svg,
    .feature-icon svg,
    .cta-btn svg {
      width: 1em;
      height: 1em;
      flex-shrink: 0;
    }

    /* ===== MODERN COLOR SYSTEM + DARK MODE ===== */
    :root {
      /* Primary Gradient - Modern Purple to Blue */
      --primary-start: #6366f1;
      --primary-end: #8b5cf6;
      --primary-dark: #4f46e5;
      --accent: #f59e0b;
      
      /* Light Mode */
      --bg: #FAFBFC;
      --bg-card: #ffffff;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --text-1: #0f172a;
      --text-2: #64748b;
      --border: #e2e8f0;
      --shadow: 0 4px 20px rgba(0,0,0,0.08);
      --shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
      
      /* Gradient Backgrounds */
      --gradient-hero: linear-gradient(135deg, #f5f3ff 0%, #e0e7ff 50%, #dbeafe 100%);
      --gradient-primary: linear-gradient(135deg, var(--primary-start), var(--primary-end));
      --gradient-text: linear-gradient(135deg, var(--primary-start), var(--primary-end));
    }

    [data-theme="dark"] {
      --bg: #0f172a;
      --bg-card: #1e293b;
      --text-primary: #f1f5f9;
      --text-secondary: #94a3b8;
      --text-1: #f1f5f9;
      --text-2: #94a3b8;
      --border: #334155;
      --shadow: 0 4px 20px rgba(0,0,0,0.3);
      --shadow-lg: 0 10px 40px rgba(0,0,0,0.4);
      --gradient-hero: linear-gradient(135deg, #1e1b4b 0%, #1e293b 50%, #0f172a 100%);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text-1);
      overflow-x: hidden;
      transition: background 0.3s, color 0.3s;
    }

    /* ===== NAVBAR ===== */
    .navbar {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      padding: 16px 0;
      animation: slideDown 0.8s ease-out;
      transition: background 0.3s, border-color 0.3s;
    }

    [data-theme="dark"] .navbar {
      background: rgba(15,23,42,0.95);
    }

    .nav-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 24px;
      font-weight: 800;
      background: var(--gradient-text);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logo-icon {
      width: 36px; height: 36px;
      background: var(--gradient-primary);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }

    .logo-icon svg {
      width: 22px;
      height: 22px;
    }

    .nav-links {
      display: flex;
      gap: 32px;
      align-items: center;
    }

    .nav-links a {
      text-decoration: none;
      color: var(--text-2);
      font-weight: 600;
      font-size: 14px;
      transition: color 0.3s;
      position: relative;
    }

    .nav-links a:hover {
      color: var(--primary-start);
    }

    .nav-links a::after {
      content: '';
      position: absolute;
      bottom: -4px; left: 0;
      width: 0; height: 2px;
      background: var(--gradient-primary);
      transition: width 0.3s;
    }

    .nav-links a:hover::after {
      width: 100%;
    }

    .btn-nav {
      background: var(--gradient-primary);
      color: white !important;
      padding: 10px 24px;
      border-radius: 50px;
      font-weight: 700;
      box-shadow: var(--shadow-sm);
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-nav:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* Dark Mode Toggle */
    .theme-toggle {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 50px;
      padding: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: all 0.3s;
    }

    .theme-toggle:hover {
      border-color: var(--primary-start);
    }

    .theme-toggle svg {
      width: 20px;
      height: 20px;
      color: var(--text-2);
    }

    [data-theme="dark"] .theme-toggle svg:first-child,
    .theme-toggle svg:last-child {
      display: none;
    }

    [data-theme="dark"] .theme-toggle svg:last-child {
      display: block;
    }

    .btn-nav::after { display: none !important; }

    /* ===== HERO SECTION ===== */
    .hero {
      min-height: 100vh;
      padding: 140px 24px 80px;
      background: var(--gradient-hero);
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      transition: background 0.3s;
    }

    .hero-bg-pattern {
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: 
        radial-gradient(circle at 20% 30%, rgba(99,102,241,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(139,92,246,0.08) 0%, transparent 50%);
      pointer-events: none;
    }

    [data-theme="dark"] .hero-bg-pattern {
      background-image: 
        radial-gradient(circle at 20% 30%, rgba(99,102,241,0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(139,92,246,0.12) 0%, transparent 50%);
    }

    .floating-shapes {
      position: absolute;
      width: 100%; height: 100%;
      pointer-events: none;
    }

    .shape {
      position: absolute;
      border-radius: 50%;
      opacity: 0.1;
    }

    .shape-1 {
      width: 400px; height: 400px;
      background: var(--gradient-primary);
      top: -100px; right: -100px;
      animation: float 6s ease-in-out infinite;
    }

    .shape-2 {
      width: 200px; height: 200px;
      background: linear-gradient(135deg, var(--primary-end), var(--primary-start));
      bottom: 10%; left: 10%;
      animation: float 8s ease-in-out infinite reverse;
    }

    .hero-container {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    .hero-content {
      animation: fadeInUp 1s ease-out;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(99,102,241,0.1);
      color: var(--primary-start);
      padding: 8px 16px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 24px;
      animation: pulse 2s ease-in-out infinite;
    }

    .hero-badge svg {
      width: 16px;
      height: 16px;
    }

    .hero-title {
      font-size: 56px;
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 24px;
      background: var(--gradient-text);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    [data-theme="dark"] .hero-title {
      background: linear-gradient(135deg, #f1f5f9 0%, var(--primary-start) 100%);
      -webkit-background-clip: text;
      background-clip: text;
    }

    .hero-title span {
      background: var(--gradient-primary);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-desc {
      font-size: 18px;
      color: var(--text-2);
      line-height: 1.8;
      margin-bottom: 40px;
      max-width: 500px;
    }

    .hero-buttons {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
    }

    .btn-primary {
      background: var(--gradient-primary);
      color: white;
      padding: 16px 32px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 15px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: var(--shadow-sm);
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .btn-primary::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }

    .btn-primary:hover::before {
      left: 100%;
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }

    .btn-primary svg {
      width: 20px;
      height: 20px;
    }

    .btn-secondary {
      background: var(--bg-card);
      color: var(--primary-start);
      padding: 16px 32px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 15px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 2px solid var(--border);
      transition: all 0.3s;
    }

    .btn-secondary:hover {
      border-color: var(--primary-start);
      transform: translateY(-3px);
      box-shadow: var(--shadow-sm);
    }

    .btn-secondary svg {
      width: 20px;
      height: 20px;
    }

    [data-theme="dark"] .btn-secondary {
      background: rgba(255,255,255,0.05);
    }

    /* Hero Visual */
    .hero-visual {
      position: relative;
      animation: fadeInUp 1s ease-out 0.3s both;
    }

    .dashboard-mockup {
      background: var(--bg-card);
      border-radius: 24px;
      box-shadow: 0 25px 80px rgba(0,0,0,0.15);
      padding: 24px;
      position: relative;
      animation: float 4s ease-in-out infinite;
    }

    .mockup-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 1px solid #F0F0F0;
    }

    .mockup-dot {
      width: 12px; height: 12px;
      border-radius: 50%;
    }

    .mockup-dot:nth-child(1) { background: #FF5F57; }
    .mockup-dot:nth-child(2) { background: #FFBD2E; }
    .mockup-dot:nth-child(3) { background: #28CA42; }

    .mockup-content {
      display: grid;
      gap: 16px;
    }

    .mockup-card {
      background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.05));
      border-radius: 12px;
      padding: 16px;
      border: 1px solid rgba(99,102,241,0.1);
    }

    [data-theme="dark"] .mockup-card {
      background: rgba(255,255,255,0.05);
      border-color: rgba(255,255,255,0.1);
    }

    .mockup-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }

    .mockup-label {
      font-size: 11px;
      color: var(--text-2);
      font-weight: 600;
    }

    .mockup-value {
      font-size: 24px;
      font-weight: 800;
      color: var(--primary-start);
    }

    .mockup-bar {
      height: 8px;
      background: rgba(99,102,241,0.1);
      border-radius: 4px;
      overflow: hidden;
    }

    .mockup-bar-fill {
      height: 100%;
      background: var(--gradient-primary);
      border-radius: 4px;
      width: 75%;
      animation: growBar 2s ease-out 1s both;
    }

    .floating-elements {
      position: absolute;
      width: 100%; height: 100%;
      top: 0; left: 0;
      pointer-events: none;
    }

    .floating-card {
      position: absolute;
      background: var(--bg-card);
      border-radius: 16px;
      padding: 16px 20px;
      box-shadow: var(--shadow-md);
      animation: float 5s ease-in-out infinite;
    }

    .floating-card:nth-child(1) {
      top: -30px; right: -40px;
      animation-delay: 0s;
    }

    .floating-card:nth-child(2) {
      bottom: 40px; left: -60px;
      animation-delay: 1s;
    }

    .floating-card-title {
      font-size: 11px;
      color: var(--text-2);
      margin-bottom: 4px;
    }

    .floating-card-value {
      font-size: 20px;
      font-weight: 800;
      color: #E53935;
    }

    .floating-card:nth-child(2) .floating-card-value {
      color: #43A047;
    }

    /* ===== FEATURES SECTION ===== */
    .features {
      padding: 100px 24px;
      background: var(--bg-card);
      transition: background 0.3s;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
    }

    .section-label {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(99,102,241,0.1);
      color: var(--primary-start);
      padding: 8px 20px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 16px;
    }

    .section-title {
      font-size: 40px;
      font-weight: 800;
      color: var(--text-1);
      margin-bottom: 16px;
    }

    .section-desc {
      font-size: 17px;
      color: var(--text-2);
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.7;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }

    .feature-card {
      background: var(--bg);
      border-radius: 20px;
      padding: 40px 30px;
      border: 1px solid var(--border);
      transition: all 0.4s;
      position: relative;
      overflow: hidden;
    }

    [data-theme="dark"] .feature-card {
      background: rgba(255,255,255,0.03);
    }

    .feature-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 4px;
      background: var(--gradient-primary);
      transform: scaleX(0);
      transition: transform 0.4s;
    }

    .feature-card:hover::before {
      transform: scaleX(1);
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-md);
      border-color: var(--primary-start);
    }

    .feature-icon {
      width: 60px; height: 60px;
      background: var(--gradient-primary);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 24px;
      transition: transform 0.4s;
      color: white;
    }

    .feature-icon svg {
      width: 32px;
      height: 32px;
    }

    .feature-card:hover .feature-icon {
      transform: scale(1.1) rotate(5deg);
    }

    .feature-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-1);
      margin-bottom: 12px;
    }

    .feature-desc {
      font-size: 14px;
      color: var(--text-2);
      line-height: 1.7;
    }

    /* ===== HOW IT WORKS ===== */
    .how-it-works {
      padding: 100px 24px;
      background: linear-gradient(180deg, var(--bg) 0%, rgba(99,102,241,0.08) 100%);
      transition: background 0.3s;
    }

    [data-theme="dark"] .how-it-works {
      background: linear-gradient(180deg, var(--bg) 0%, rgba(99,102,241,0.05) 100%);
    }

    .steps-container {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
      margin-top: 60px;
    }

    .step-card {
      background: var(--bg-card);
      border-radius: 20px;
      padding: 32px 24px;
      text-align: center;
      position: relative;
      transition: all 0.4s;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border);
    }

    .step-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-md);
    }

    .step-number {
      width: 50px; height: 50px;
      background: var(--gradient-primary);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 800;
      margin: 0 auto 20px;
      position: relative;
    }

    .step-number::after {
      content: '';
      position: absolute;
      width: 100%; height: 100%;
      border-radius: 50%;
      background: inherit;
      animation: pulse-ring 2s ease-out infinite;
      z-index: -1;
    }

    .step-title {
      font-size: 17px;
      font-weight: 700;
      color: var(--text-1);
      margin-bottom: 8px;
    }

    .step-desc {
      font-size: 13px;
      color: var(--text-2);
      line-height: 1.6;
    }

    /* ===== STATS SECTION ===== */
    .stats {
      padding: 80px 24px;
      background: var(--gradient-primary);
      position: relative;
      overflow: hidden;
    }

    .stats::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.08'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 40px;
      position: relative;
      z-index: 1;
    }

    .stat-item {
      text-align: center;
      color: white;
    }

    .stat-value {
      font-size: 48px;
      font-weight: 800;
      margin-bottom: 8px;
      background: linear-gradient(135deg, #ffffff, #c7d2fe);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .stat-label {
      font-size: 14px;
      opacity: 0.9;
      font-weight: 500;
    }

    /* ===== CTA SECTION ===== */
    .cta {
      padding: 100px 24px;
      background: var(--bg);
      text-align: center;
    }

    .cta-box {
      max-width: 800px;
      margin: 0 auto;
      background: var(--gradient-primary);
      border-radius: 30px;
      padding: 60px 40px;
      position: relative;
      overflow: hidden;
    }

    .cta-box::before {
      content: '';
      position: absolute;
      top: -50%; right: -20%;
      width: 400px; height: 400px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      animation: float 8s ease-in-out infinite;
    }

    .cta-title {
      font-size: 32px;
      font-weight: 800;
      color: white;
      margin-bottom: 16px;
      position: relative;
      z-index: 1;
    }

    .cta-desc {
      font-size: 16px;
      color: rgba(255,255,255,0.9);
      margin-bottom: 32px;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
      position: relative;
      z-index: 1;
    }

    .cta-btn {
      background: white;
      color: var(--primary-start);
      padding: 16px 40px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 15px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
      position: relative;
      z-index: 1;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }

    .cta-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }

    .cta-btn svg {
      width: 20px;
      height: 20px;
    }

    /* ===== FOOTER ===== */
    .footer {
      padding: 50px 24px;
      background: #0f172a;
      color: white;
      text-align: center;
    }

    [data-theme="dark"] .footer {
      background: #0a0f1a;
    }

    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer-logo {
      font-size: 24px;
      font-weight: 800;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .footer-logo svg {
      width: 28px;
      height: 28px;
      color: var(--primary-start);
    }

    .footer-text {
      font-size: 14px;
      color: rgba(255,255,255,0.8);
      margin-bottom: 20px;
    }

    .footer-credit {
      font-size: 13px;
      color: rgba(255,255,255,0.6);
      padding-top: 20px;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    .footer-credit a {
      color: #a5b4fc;
      text-decoration: none;
      font-weight: 600;
    }

    .footer-credit a:hover {
      color: #c7d2fe;
    }

    /* ===== ANIMATIONS KEYFRAMES ===== */
    @keyframes slideDown {
      from { transform: translateY(-100%); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes fadeInUp {
      from { transform: translateY(40px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-20px); }
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    @keyframes pulse-ring {
      0% { transform: scale(1); opacity: 0.5; }
      100% { transform: scale(1.5); opacity: 0; }
    }

    @keyframes growBar {
      from { width: 0; }
      to { width: 75%; }
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
      .hero-container {
        grid-template-columns: 1fr;
        text-align: center;
      }

      .hero-title {
        font-size: 40px;
      }

      .hero-desc {
        margin-left: auto;
        margin-right: auto;
      }

      .hero-buttons {
        justify-content: center;
      }

      .hero-visual {
        order: -1;
        max-width: 500px;
        margin: 0 auto;
      }

      .features-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .steps-container {
        grid-template-columns: repeat(2, 1fr);
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .nav-links { display: none; }

      .hero {
        padding: 120px 20px 60px;
      }

      .hero-title {
        font-size: 32px;
      }

      .section-title {
        font-size: 28px;
      }

      .features-grid,
      .steps-container {
        grid-template-columns: 1fr;
      }

      .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
      }

      .stat-value {
        font-size: 32px;
      }

      .cta-box {
        padding: 40px 24px;
      }

      .cta-title {
        font-size: 24px;
      }
    }

    /* Scroll Animation Classes */
    .scroll-hidden {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s ease-out;
    }

    .scroll-visible {
      opacity: 1;
      transform: translateY(0);
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="nav-container">
      <div class="logo">
        <div class="logo-icon">
          <svg><use href="#icon-search"/></svg>
        </div>
        PDAM Anomaly
      </div>
      <div class="nav-links">
        <a href="#features">Fitur</a>
        <a href="#how-it-works">Cara Kerja</a>
        <a href="login.php">Login</a>
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
          <svg><use href="#icon-sun"/></svg>
          <svg><use href="#icon-moon"/></svg>
        </button>
        <a href="dashboard1.php" class="btn-nav">
          Mulai Analisis
          <svg><use href="#icon-arrow"/></svg>
        </a>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <section class="hero">
    <div class="hero-bg-pattern"></div>
    <div class="floating-shapes">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
    </div>

    <div class="hero-container">
      <div class="hero-content">
        <div class="hero-badge">
          <svg><use href="#icon-sparkle"/></svg>
          Sistem Terbaru v2.0
        </div>
        <h1 class="hero-title">
          Deteksi Anomali PDAM<br>
          <span>Lebih Cerdas</span>
        </h1>
        <p class="hero-desc">
          Identifikasi data tidak wajar pada pelanggan PDAM secara otomatis 
          menggunakan Machine Learning. Cepat, akurat, dan multi-user safe.
        </p>
        <div class="hero-buttons">
          <a href="dashboard1.php" class="btn-primary">
            <svg><use href="#icon-play"/></svg>
            Mulai Analisis Gratis
          </a>
          <a href="#how-it-works" class="btn-secondary">
            <svg><use href="#icon-settings"/></svg>
            Pelajari Cara Kerja
          </a>
        </div>
      </div>

      <div class="hero-visual">
        <div class="dashboard-mockup">
          <div class="mockup-header">
            <div class="mockup-dot"></div>
            <div class="mockup-dot"></div>
            <div class="mockup-dot"></div>
          </div>
          <div class="mockup-content">
            <div class="mockup-card">
              <div class="mockup-card-header">
                <span class="mockup-label">Total Data</span>
                <span class="mockup-value">1,248</span>
              </div>
              <div class="mockup-bar">
                <div class="mockup-bar-fill"></div>
              </div>
            </div>
            <div class="mockup-card">
              <div class="mockup-card-header">
                <span class="mockup-label">Anomali Terdeteksi</span>
                <span class="mockup-value" style="color: #E53935;">184</span>
              </div>
              <div class="mockup-bar">
                <div class="mockup-bar-fill" style="width: 35%; background: linear-gradient(90deg, #E53935, #FF6B6B);"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="floating-elements">
          <div class="floating-card">
            <div class="floating-card-title">Anomali High</div>
            <div class="floating-card-value">44 data</div>
          </div>
          <div class="floating-card">
            <div class="floating-card-title">Data Normal</div>
            <div class="floating-card-value">1,064</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURES SECTION -->
  <section class="features" id="features">
    <div class="container">
      <div class="section-header scroll-hidden">
        <span class="section-label">
          <svg><use href="#icon-sparkle"/></svg>
          Fitur Unggulan
        </span>
        <h2 class="section-title">Kenapa Memilih Sistem Kami?</h2>
        <p class="section-desc">
          Dirancang khusus untuk kebutuhan PDAM dengan algoritma Machine Learning 
          terdepan dan keamanan multi-user.
        </p>
      </div>

      <div class="features-grid">
        <div class="feature-card scroll-hidden">
          <div class="feature-icon">
            <svg><use href="#icon-robot"/></svg>
          </div>
          <h3 class="feature-title">Isolation Forest</h3>
          <p class="feature-desc">
            Algoritma ML canggih yang mendeteksi anomali tanpa perlu data training. 
            Cocok untuk data PDAM yang kompleks.
          </p>
        </div>

        <div class="feature-card scroll-hidden">
          <div class="feature-icon">
            <svg><use href="#icon-chart"/></svg>
          </div>
          <h3 class="feature-title">Severity Level</h3>
          <p class="feature-desc">
            Kategorikan anomali menjadi High, Medium, dan Low berdasarkan 
            tingkat kepentingan untuk prioritas penanganan.
          </p>
        </div>

        <div class="feature-card scroll-hidden">
          <div class="feature-icon">
            <svg><use href="#icon-target"/></svg>
          </div>
          <h3 class="feature-title">Z-Score Analysis</h3>
          <p class="feature-desc">
            Penjelasan otomatis kenapa data dianggap anomali dengan perbandingan 
            kontekstual per golongan dan tahun.
          </p>
        </div>

        <div class="feature-card scroll-hidden">
          <div class="feature-icon">
            <svg><use href="#icon-users"/></svg>
          </div>
          <h3 class="feature-title">Multi-User Safe</h3>
          <p class="feature-desc">
            Setiap user punya ruang kerja terisolasi. Tidak ada data leak 
            antar pengguna. Aman untuk digunakan bersamaan.
          </p>
        </div>

        <div class="feature-card scroll-hidden">
          <div class="feature-icon">
            <svg><use href="#icon-bolt"/></svg>
          </div>
          <h3 class="feature-title">Dual Mode</h3>
          <p class="feature-desc">
            Guest mode untuk analisis cepat, User mode untuk history tersimpan. 
            Fleksibel sesuai kebutuhan.
          </p>
        </div>

        <div class="feature-card scroll-hidden">
          <div class="feature-icon">
            <svg><use href="#icon-upload"/></svg>
          </div>
          <h3 class="feature-title">Auto Cleanup</h3>
          <p class="feature-desc">
            File temporary guest dihapus otomatis setelah 30 menit. 
            Tidak perlu maintenance manual.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="how-it-works" id="how-it-works">
    <div class="container">
      <div class="section-header scroll-hidden">
        <span class="section-label">
          <svg><use href="#icon-settings"/></svg>
          Cara Penggunaan
        </span>
        <h2 class="section-title">Hanya 4 Langkah Mudah</h2>
        <p class="section-desc">
          Tidak perlu keahlian teknis. Upload file Excel dan biarkan sistem 
          bekerja untuk Anda.
        </p>
      </div>

      <div class="steps-container">
        <div class="step-card scroll-hidden">
          <div class="step-number">1</div>
          <h3 class="step-title">Upload Excel</h3>
          <p class="step-desc">
            Upload file Excel dengan kolom: Tahun, Bulan, Golongan, Rp, M3
          </p>
        </div>

        <div class="step-card scroll-hidden">
          <div class="step-number">2</div>
          <h3 class="step-title">Pilih Parameter</h3>
          <p class="step-desc">
            Atur contamination, mode analisis, dan filter tahun jika diperlukan
          </p>
        </div>

        <div class="step-card scroll-hidden">
          <div class="step-number">3</div>
          <h3 class="step-title">Jalankan Analisis</h3>
          <p class="step-desc">
            Klik "Analyze" dan tunggu sistem memproses data dengan Python ML
          </p>
        </div>

        <div class="step-card scroll-hidden">
          <div class="step-number">4</div>
          <h3 class="step-title">Lihat Hasil</h3>
          <p class="step-desc">
            Dapatkan summary, grafik, dan detail anomali dengan penjelasan Z-score
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- STATS SECTION -->
  <section class="stats">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-value">2</div>
          <div class="stat-label">Mode Penggunaan</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">4</div>
          <div class="stat-label">Mode Analisis</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">3</div>
          <div class="stat-label">Level Severity</div>
        </div>
        <div class="stat-item">
          <div class="stat-value">∞</div>
          <div class="stat-label">Multi-User Safe</div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA SECTION -->
  <section class="cta">
    <div class="container">
      <div class="cta-box">
        <h2 class="cta-title">Siap Untuk Mendeteksi Anomali?</h2>
        <p class="cta-desc">
          Gunakan sistem kami sekarang dan temukan data tidak wajar 
          pada pelanggan PDAM Anda dengan cepat dan akurat.
        </p>
        <a href="dashboard1.php" class="cta-btn">
          <svg><use href="#icon-play"/></svg>
          Mulai Analisis Sekarang
          <svg><use href="#icon-arrow"/></svg>
        </a>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-logo">
        <svg><use href="#icon-search"/></svg>
        PDAM Anomaly
      </div>
      <p class="footer-text">
        © 2024 PDAM Anomaly Detection System.<br>
        Dibangun untuk kemajuan PDAM Indonesia.
      </p>
      <div class="footer-credit">
        Created by <a href="https://github.com/Hamza045S" target="_blank">Mohammad Ilham Arifin / Hamza045S</a>
      </div>
    </div>
  </footer>

  <!-- SCROLL ANIMATION SCRIPT -->
  <script>
    // Dark Mode Toggle
    function toggleTheme() {
      const html = document.documentElement;
      const currentTheme = html.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      html.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
    }

    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
      document.documentElement.setAttribute('data-theme', savedTheme);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      document.documentElement.setAttribute('data-theme', 'dark');
    }

    // Scroll animation observer
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('scroll-visible');
          entry.target.classList.remove('scroll-hidden');
        }
      });
    }, observerOptions);

    document.querySelectorAll('.scroll-hidden').forEach(el => {
      observer.observe(el);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  </script>

</body>
</html>

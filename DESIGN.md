# DESIGN SYSTEM SPECIFICATION: PDAM ANOMALY DETECTION SYSTEM

## 1. Executive Summary & Brand Architecture
- **Application Title**: PDAM Anomaly Detection System (Perumdam Tirta Kencana Kota Samarinda)
- **Primary Goal**: Intelligent, machine learning-driven anomaly detection on water customer billing and usage data (Rp, M3, Rp/M3).
- **Core Technology Stack**: PHP 8.x + Python (Scikit-Learn Isolation Forest & Z-Score Analysis) + Chart.js + Custom CSS Design System v2.0.
- **Theme Paradigm**: Dual-theme system (Light & Dark mode) with liquid/fresh-water gradient aesthetics and glassmorphic cards.

---

## 2. Global Design Tokens

### 2.1 Theme & Palette Specs
- **Dynamic Attribute**: `html[data-theme="light"]` / `html[data-theme="dark"]`

#### Light Mode Colors
- **Page Background (`--bg`)**: `linear-gradient(135deg, #f5f3ff 0%, #eef2ff 40%, #e0e7ff 70%, #dbeafe 100%)`
- **Surface Background (`--bg-solid`)**: `#FAFBFC`
- **Card Background (`--bg-card`)**: `#FFFFFF` / `rgba(255, 255, 255, 0.85)` (Glassmorphic Blur 8px)
- **Primary Text (`--text-1`)**: `#0F172A` (Slate 900)
- **Secondary Text (`--text-2`)**: `#64748B` (Slate 500)
- **Muted Text (`--text-3`)**: `#94A3B8` (Slate 400)
- **Border (`--border`)**: `#E2E8F0` / `rgba(255, 255, 255, 0.6)`
- **Strong Border (`--border-strong`)**: `#CBD5E1`

#### Dark Mode Colors
- **Page Background (`--bg`)**: `#0F172A` (Deep Slate)
- **Card Background (`--bg-card`)**: `#1E293B` / `rgba(30, 41, 59, 0.80)`
- **Primary Text (`--text-1`)**: `#F1F5F9` (Slate 100)
- **Secondary Text (`--text-2`)**: `#94A3B8` (Slate 400)
- **Muted Text (`--text-3`)**: `#64748B` (Slate 500)
- **Border (`--border`)**: `#334155`
- **Strong Border (`--border-strong`)**: `#475569`

#### Brand & Action Accents
- **Brand Blue (`--brand-blue`)**: `#6366F1` (Indigo / Ocean Blue)
- **Brand Blue Dark (`--brand-blue-dark`)**: `#4F46E5`
- **Brand Cyan (`--brand-cyan`)**: `#06B6D4`
- **Brand Purple (`--brand-purple`)**: `#8B5CF6`
- **Primary Gradient (`--gradient-primary`)**: `linear-gradient(135deg, #6366F1, #8B5CF6)`
- **Hero Water Gradient (`--gradient-hero`)**: `linear-gradient(120deg, #e0f2fe 0%, #bae6fd 30%, #7dd3fc 60%, #38bdf8 100%)`

#### Status & Severity Tokens
- **High Severity Anomaly**: `#E11D48` (Crimson Red / `--anomaly-color`)
  - Tag Light: `bg: rgba(225, 29, 72, 0.1)`, `text: #B91C1C`
  - Tag Dark: `bg: rgba(225, 29, 72, 0.15)`, `text: #FCA5A5`
- **Medium Severity Anomaly**: `#D97706` (Amber Orange / `--score-color`)
  - Tag Light: `bg: rgba(217, 119, 6, 0.1)`, `text: #92400E`
  - Tag Dark: `bg: rgba(217, 119, 6, 0.15)`, `text: #FCD34D`
- **Low Severity Anomaly**: `#059669` (Emerald Green / `--normal-color`)
  - Tag Light: `bg: rgba(5, 150, 105, 0.1)`, `text: #065F46`
  - Tag Dark: `bg: rgba(5, 150, 105, 0.15)`, `text: #6EE7B7`
- **Normal Data Point**: `#0891B2` (Teal / `--secondary`)
  - Tag Light: `bg: rgba(8, 145, 178, 0.1)`, `text: #0C4A6E`
  - Tag Dark: `bg: rgba(8, 145, 178, 0.15)`, `text: #7DD3FC`

---

## 3. Typography Specs
- **Display / Heading Font**: `'Outfit', sans-serif` (Weights: 600, 700, 800)
- **Body Font**: `'DM Sans', system-ui, sans-serif` (Weights: 400, 500, 700)
- **Code & Numeric Data Font**: `'DM Mono', monospace`

### Text Hierarchy
- **Hero Title**: `56px` / Line Height `1.1` / Bold 800
- **Page Header (H1)**: `22px` / Line Height `1.2` / ExtraBold 800
- **Section Title (H2)**: `18px` / Line Height `1.3` / Bold 700
- **Card Stat Value**: `26px - 28px` / Line Height `1.0` / ExtraBold 800
- **Body Regular**: `13px - 14px` / Line Height `1.6` / Regular 400
- **Table Data / Meta**: `12px - 12.5px` / Medium 500
- **Badge / Micro Label**: `10px - 11px` / SemiBold 600 / UpperCase

---

## 4. UI Components & Control Systems

### 4.1 Card Systems
- **Glassmorphic Standard Card (`.card-pdam`)**:
  - `padding: 20px 22px`, `border-radius: 20px`
  - `backdrop-filter: blur(8px)`
  - Hover effect: `transform: translateY(-3px)`, `box-shadow: 0 8px 32px rgba(99, 102, 241, 0.12)`
  - Top highlight bar: 3px primary gradient on hover.
- **KPI Stat Card (`.card-stat`)**:
  - Grid layout 4 columns, icon size `44px x 44px` with `12px` rounded background.

### 4.2 Buttons & Controls
- **Primary Button (`.btn-primary`, `.btn-run`)**:
  - Primary color gradient background, `border-radius: 50px` or `var(--radius-sm)`.
  - Shimmer overlay animation on hover.
- **Filter Tabs (`.mtab`)**:
  - Interactive pill buttons for ML mode switching (`multi_tahun_semua_golongan`, `multi_tahun_per_golongan`, `near_tahun_per_golongan`, `near_tahun_near_golongan`).
  - Active state: `#6366F1` background with white text.

### 4.3 Data Presentation Components
- **Data Table (`.tbl`)**:
  - Striped hover rows (`.tbl tbody tr:hover`), uppercase table header (`th`).
  - Severity indicator borders (`.row-anomaly td:first-child`).
- **Z-Score Indicators (`.zscore-tag`)**:
  - Rounded pill tags showing deviation ranking and cause explanation.
- **Chart Wrappers**:
  - Donut Chart with central percentage indicator (`#donutCenter`).
  - Responsive Bar Chart comparing Anomaly vs Normal per customer category.
- **Insight Box (`.insight-box`)**:
  - Highlight box with left accent border (`border-left: 3px solid var(--primary)` or red/amber for warnings).

---

## 5. Detailed Screen Specifications

### Screen 1: Public Landing Page (`index.php`)
- **Header**: Fixed Navbar (`.navbar`) with theme toggle (Sun/Moon icons) and "Mulai Analisis" action.
- **Hero Section**: Dual column grid with animated liquid water background, floating bubbles, animated summary metrics mockup card, and primary CTA buttons.
- **Features Section**: 6-card grid highlighting Isolation Forest, Severity Level, Z-Score Analysis, Multi-User Safety, Dual Mode, and Auto Cleanup.
- **How It Works**: 4-step sequence cards (1. Upload Excel -> 2. Select Parameters -> 3. Run Analysis -> 4. View Results).
- **Statistics Counter**: Dark blue banner with numbers for usage modes and severity levels.

### Screen 2: Dashboard 1 — Upload & Configuration (`dashboard1.php`)
- **Upload Zone**: Drag-and-drop file uploader accepting `.xlsx` datasets with column validation guidelines.
- **Model Parameter Controls**: Form card for Contamination Rate selection (`auto`, `0.05`, `0.1`, `0.2`, `0.3`) and Grouping Modes.
- **System Status Card**: User session status (Guest temporary session vs Authenticated User).

### Screen 3: Dashboard 2 — Global Analysis (`dashboard2.php`)
- **Top Control Bar**: Page Badge ("Dashboard 2 — Pusat Sistem"), CSV Export, and PDF Print triggers.
- **Global Filter Bar**: Inline controls to execute ML model on demand with mode tabs and year range dropdowns.
- **4 Key KPI Stat Cards**: Total Records, Total Anomalies, Anomaly Percentage, Contamination Rate.
- **3 Severity Stat Cards**: High Severity count (Red border), Medium Severity count (Orange border), Low Severity count (Yellow border).
- **Data Visualizations**: Donut Chart for Anomaly distribution + Bar Chart per customer category.
- **Automated Insights**: AI-generated summary banner detailing critical anomaly findings.
- **Breakdown Tables**: Summary per year and summary per customer category with visual progress bars.

### Screen 4: Dashboard 3 — Visual Analytics & Scatter Plots (`dashboard3.php`)
- **Scatter Plot Section**: Rp vs M3 scatter plots highlighting anomaly points in red.
- **Heatmap Grid**: Heatmap visualising anomaly density by month and category.
- **Filter Drawer**: Interactive range sliders for anomaly score thresholding.

### Screen 5: Dashboard 4 — Detailed Anomaly Table (`dashboard4.php`)
- **Filter & Search Bar**: Quick keyword search input + severity filter dropdown.
- **Detailed Data Table**: Displaying Year, Month, Category, Billing Rp, Usage M3, Unit Price (Rp/M3), Anomaly Score, Severity Badge, Z-Score breakdown, and natural language cause.
- **Pagination & Export**: Page numbers, rows per page switcher, CSV and PDF exporter buttons.

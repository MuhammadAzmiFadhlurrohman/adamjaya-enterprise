# 🏛️ ADAM JAYA ECOSYSTEM RULES & ARCHITECTURE MEMORY

## Project Overview & Ecosystem
Adam Jaya operates two interconnected systems:

1. **Adam Jaya Landing Page (Public Website)**
   - **Workspace Path**: `c:\xampp\htdocs\adamjaya`
   - **Purpose**: World-class public showcase, company profile, procurement & enterprise supply services overview, interactive features, client trust badges, and direct access gate to the internal enterprise portal.
   - **Tech Stack**: Laravel Framework (Blade Templates, TailwindCSS / Custom Luxury CSS, Vanilla JS / Alpine.js), FontAwesome 6, Smooth Micro-animations.
   - **Design Style**: Ultra-luxury corporate (similar to Apple / Stripe / Premium Executive Brands).
   - **Key CTA**: Portal Login Button pointing to `http://localhost/adamjaya-enterprise/login.php` (or production domain `app.adamjaya.com` / `enterprise.adamjaya.com`).

2. **Adam Jaya Enterprise (Internal Management System)**
   - **Workspace Path**: `c:\xampp\htdocs\adamjaya-enterprise`
   - **Purpose**: Internal management system for procurement, multi-variant inventory management, operational expense tracking, financial reporting, and executive CEO dashboard.
   - **Tech Stack**: PHP 8.x, MySQL/MariaDB (MySQLi prepared statements + transaction locks `FOR UPDATE`), FontAwesome 6, Bootstrap 5, Chart.js, SweetAlert2.

## Design System & Branding Tokens
All Adam Jaya digital products MUST share the **Executive Luxury Wine & Gold Design System**:
- **Primary Wine**: `#7A1E33` (Wine Dark: `#58101F`, Wine Light: `#A5334C`)
- **Brushed Executive Gold**: `#C9973E` / `#C9A84C` (Gold Light: `#E8D5A0`, Soft Glow: `rgba(201, 168, 76, 0.12)`)
- **CEO Navy**: `#0A1628` (Navy Soft: `#1A2A4A`, Navy Slate: `#2A3F6A`)
- **Ink / Surface**: `#1E2430` Ink, `#F6F4F1` Light Soft BG, Dark Glassmorphism for Landing UI.
- **Typography**: `Plus Jakarta Sans` for Headings, `Inter` for Body Text.

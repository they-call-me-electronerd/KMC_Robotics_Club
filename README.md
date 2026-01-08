# Kathmandu Model College Robotics Club (KMC RC) 

> **Official Website** - The digital hub for KMC's student-led technical community.

[![Version](https://img.shields.io/badge/version-2.1-cyan.svg)](https://github.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/status-active-success.svg)](https://github.com)
[![Theme](https://img.shields.io/badge/theme-Tech%20Dark-050a14)](css/styles.css)

---

##  Project Overview

This is the official responsive website for the **Kathmandu Model College Robotics Club (KMC RC)**. It serves as the central platform for showcasing club activities, recruiting new members, and displaying student projects. The site features a premium, "sci-fi professional" aesthetic with a strict dark mode theme.

**Tagline:** *Build  Code  Innovate*

---

##  Key Features

### **Visual Identity**
-  **Premium Innovation Theme:** Deep navy backgrounds (`#050a14`) with Neon Cyan (`#00f2ff`) accents.
-  **Glassmorphism:** Frosted glass effects on cards and navigation using backdrop filters.
-  **Advanced Typography:** 
    - **Orbitron:** For headers and sci-fi elements.
    - **Inter/Poppins:** For highly readable body text.
    - **Roboto Mono:** For tech specs and code snippets.
-  **Interactive Visuals:** Particle background canvas, cyber-grid overlays, and hover glow effects.

### **Core Functionality**
-  **Fully Responsive:** Adapts seamlessly from mobile (<640px) to ultra-wide desktop screens.
-  **Component Architecture:** JS-based loader for shared `nav.html` and `footer.html` to ensure consistency across pages.
-  **AOS Animations:** Smooth scroll-on-reveal animations for all major sections.
-  **Permanent Dark Mode:** Optimized for reduced eye strain and high-tech appeal.

---

##  Project Structure

```bash
KMC_Robotics_Club/

 index.html              # Main Landing Page
 assets/                 # Static assets (images, icons)
    images/
        kmc-rc-logo.png

 components/             # Reusable HTML fragments
    footer.html         # Global Footer (4-column layout)
    nav.html            # Global Navigation (Responsive, Pill-shaped)

 css/
    club-styles.css     # Custom component styles (cards, effects)
    styles.css          # Global variables and typography

 js/
    main.js             # Logic for component loading, mobile menu, particles

 pages/                  # Secondary Pages
    about.html          # Club history and mission
    events.html         # Upcoming workshops and calendar
    gallery.html        # Project and event photos
    join.html           # Membership application
    team.html           # Executive committee list

 docs/                   # Documentation
```

---

##  Technical Details

### **Tech Stack**
- **HTML5:** Semantic markup.
- **Tailwind CSS (CDN):** Utility-first styling with custom config in `head` for colors and fonts.
- **Vanilla JavaScript:** Zero-dependency logic for component injection and UI interactions.
- **AOS (Animate On Scroll):** Library for scroll animations.
- **Feather Icons:** Lightweight SVG icons.

### **Custom Styling**
The project uses two primary CSS files:
1. **`styles.css`**: Defines global variables, font imports (Google Fonts), and base HTML overrides.
2. **`club-styles.css`**: Contains complex custom classes like `.club-card`, `.accent-glow`, and `.enhanced-nav` where Tailwind utilities are too verbose.

### **Navigation System**
The navigation bar is a standalone file (`components/nav.html`) injected into every page by `js/main.js`. It features:
- **Active State Detection:** Automatically highlights the current page link.
- **Mobile Menu:** Collapsible backdrop-blur menu for small screens.
- **Scroll Effects:** Transitions to detailed view on scroll (configurable).

---

##  Setup & Usage

Since this project uses no build tools (Webpack/Vite), it is extremely easy to run:

1.  **Clone or Download** the repository.
2.  **Open** the folder in VS Code.
3.  **Run with Live Server:**
    *   Install the "Live Server" extension in VS Code.
    *   Right-click `index.html` and select "Open with Live Server".
    *   *Note: Directly opening the file (`file://`) may block the component loading due to CORS policies. A local server is recommended.*

---

##  Contribution

1.  Keep the **Dark Mode** aesthetic intact.
2.  Use the defined color variables (`--accent`, `--dark-navy`) in `styles.css`.
3.  Ensure all new pages include the `<div id="nav-placeholder"></div>` and `<div id="footer-placeholder"></div>` tags.

---

 2026 Kathmandu Model College Robotics Club.

function adjustNavLinks(isRootPage) {
    const navRoot = document.getElementById('nav-placeholder');
    if (!navRoot) return;

    const targetMap = {
        home: isRootPage ? 'index.html' : '../index.html',
        about: isRootPage ? 'pages/about.html' : 'about.html',
        team: isRootPage ? 'pages/team.html' : 'team.html',
        events: isRootPage ? 'pages/events.html' : 'events.html',
        gallery: isRootPage ? 'pages/gallery.html' : 'gallery.html',
        join: isRootPage ? 'pages/join.html' : 'join.html'
    };

    navRoot.querySelectorAll('[data-page]').forEach((link) => {
        const pageKey = link.getAttribute('data-page');
        if (pageKey && targetMap[pageKey]) {
            link.setAttribute('href', targetMap[pageKey]);
        }
    });
}

// Load navigation component
function loadNavigation() {
    const navPlaceholder = document.getElementById('nav-placeholder');
    if (!navPlaceholder) return;
    
    // Determine the correct path based on current page location
    const isRootPage = window.location.pathname.endsWith('index.html') || 
                       window.location.pathname.endsWith('/') ||
                       !window.location.pathname.includes('/pages/');
    const navPath = isRootPage ? 'components/nav.html' : '../components/nav.html';
    
    fetch(navPath)
        .then(response => response.text())
        .then(html => {
            navPlaceholder.innerHTML = html;
            adjustNavLinks(isRootPage);
            // Re-initialize Feather Icons after nav loads
            if (window.feather && typeof window.feather.replace === 'function') {
                window.feather.replace();
            }
            // Re-attach mobile menu event listener
            initializeMobileMenu();
        })
        .catch(error => console.error('Error loading navigation:', error));
}

// Load footer component
function loadFooter() {
    const footerPlaceholder = document.getElementById('footer-placeholder');
    if (!footerPlaceholder) return;
    
    // Determine the correct path
    const isRootPage = window.location.pathname.endsWith('index.html') || 
                       window.location.pathname.endsWith('/') ||
                       !window.location.pathname.includes('/pages/');
    const footerPath = isRootPage ? 'components/footer.html' : '../components/footer.html';
    
    fetch(footerPath)
        .then(response => response.text())
        .then(html => {
            footerPlaceholder.innerHTML = html;
            if (window.feather && typeof window.feather.replace === 'function') {
                window.feather.replace();
            }
             if (document.getElementById('year')) {
                document.getElementById('year').textContent = new Date().getFullYear();
            }
        })
        .catch(error => console.error('Error loading footer:', error));
}

// Initialize mobile menu
function initializeMobileMenu() {
    const mobileBtn = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileBtn && mobileMenu) {
        // Remove any existing listeners
        mobileBtn.replaceWith(mobileBtn.cloneNode(true));
        const newMobileBtn = document.getElementById('mobile-menu-button');
        
        newMobileBtn.addEventListener('click', toggleMobileMenu);
    }
}

// Load navigation on page load
document.addEventListener('DOMContentLoaded', loadNavigation);
document.addEventListener('DOMContentLoaded', loadFooter);

// Initialize Feather Icons
if (window.feather && typeof window.feather.replace === 'function') {
    window.feather.replace();
}

// Initialize AOS animation library
if (window.AOS && typeof window.AOS.init === 'function') {
    window.AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true, /* Change to true for animations to happen only once */
        mirror: false
    });
}

// Mobile menu toggle with touch support
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileBtn = document.getElementById('mobile-menu-button');
    if (!mobileMenu) return;
    const isHidden = mobileMenu.classList.contains('hidden');
    
    if (isHidden) {
        mobileMenu.classList.remove('hidden');
        // Trigger reflow
        void mobileMenu.offsetWidth;
        mobileMenu.classList.remove('opacity-0', 'scale-95');
        mobileMenu.classList.add('opacity-100', 'scale-100');
        // Update button icon to X
        if (mobileBtn) {
            mobileBtn.innerHTML = '<i data-feather="x" class="w-6 h-6"></i>';
            if (window.feather) feather.replace();
        }
        // Prevent body scroll when menu is open
        document.body.style.overflow = 'hidden';
    } else {
        mobileMenu.classList.remove('opacity-100', 'scale-100');
        mobileMenu.classList.add('opacity-0', 'scale-95');
        // Update button icon back to menu
        if (mobileBtn) {
            mobileBtn.innerHTML = '<i data-feather="menu" class="w-6 h-6"></i>';
            if (window.feather) feather.replace();
        }
        // Restore body scroll
        document.body.style.overflow = '';
        setTimeout(() => {
            if (mobileMenu.classList.contains('opacity-0')) {
                mobileMenu.classList.add('hidden');
            }
        }, 300); 
    }
}

// Close mobile menu when clicking on a link
document.addEventListener('click', function(e) {
    const mobileMenu = document.getElementById('mobile-menu');
    if (e.target.closest('.nav-link-mobile') && mobileMenu && !mobileMenu.classList.contains('hidden')) {
        toggleMobileMenu();
    }
});

// Close mobile menu on escape key
document.addEventListener('keydown', function(e) {
    const mobileMenu = document.getElementById('mobile-menu');
    if (e.key === 'Escape' && mobileMenu && !mobileMenu.classList.contains('hidden')) {
        toggleMobileMenu();
    }
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
            if (mobileMenu && !mobileMenu.classList.contains('hidden')) toggleMobileMenu();
        }
    });
});

// ============================================
// NEURAL WEB CURSOR REPULSION EFFECT
// Particles move and repel from cursor on hover
// ============================================

(function() {
    'use strict';
    
    const canvas = document.getElementById('particle-canvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    // Configuration
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;
    const PARTICLE_COUNT = isMobile ? 50 : 120;
    const CONNECT_DISTANCE = isMobile ? 100 : 140;
    const MOUSE_RADIUS = 150;
    const REPEL_STRENGTH = 8;
    const PARTICLE_SPEED = 0.4;
    const PARTICLE_COLOR = 'rgba(0, 242, 255, 0.8)';
    const LINE_COLOR = '0, 242, 255';
    
    // State
    const mouse = { x: null, y: null, radius: MOUSE_RADIUS };
    const particles = [];
    
    // Resize canvas
    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    resize();
    window.addEventListener('resize', resize);
    
    // Mouse events
    window.addEventListener('mousemove', (e) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
    });
    
    window.addEventListener('mouseout', () => {
        mouse.x = null;
        mouse.y = null;
    });
    
    // Touch events for mobile
    window.addEventListener('touchmove', (e) => {
        if (e.touches.length > 0) {
            mouse.x = e.touches[0].clientX;
            mouse.y = e.touches[0].clientY;
        }
    }, { passive: true });
    
    window.addEventListener('touchend', () => {
        mouse.x = null;
        mouse.y = null;
    });
    
    // Particle class
    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 2 + 1;
            this.vx = (Math.random() - 0.5) * PARTICLE_SPEED;
            this.vy = (Math.random() - 0.5) * PARTICLE_SPEED;
        }
        
        update() {
            // Normal movement
            this.x += this.vx;
            this.y += this.vy;
            
            // Bounce off edges
            if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
            if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
            
            // Cursor repulsion
            if (mouse.x !== null && mouse.y !== null) {
                const dx = this.x - mouse.x;
                const dy = this.y - mouse.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < mouse.radius) {
                    const angle = Math.atan2(dy, dx);
                    const force = (mouse.radius - distance) / mouse.radius;
                    const repelX = Math.cos(angle) * force * REPEL_STRENGTH;
                    const repelY = Math.sin(angle) * force * REPEL_STRENGTH;
                    
                    this.x += repelX;
                    this.y += repelY;
                }
            }
            
            // Keep particles in bounds after repulsion
            if (this.x < 0) this.x = 0;
            if (this.x > canvas.width) this.x = canvas.width;
            if (this.y < 0) this.y = 0;
            if (this.y > canvas.height) this.y = canvas.height;
        }
        
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = PARTICLE_COLOR;
            ctx.fill();
        }
    }
    
    // Draw connections between nearby particles
    function connect() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < CONNECT_DISTANCE) {
                    const opacity = (1 - dist / CONNECT_DISTANCE) * 0.5;
                    ctx.strokeStyle = `rgba(${LINE_COLOR}, ${opacity})`;
                    ctx.lineWidth = 0.8;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }
    }
    
    // Animation loop
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
        }
        
        connect();
        requestAnimationFrame(animate);
    }
    
    // Initialize particles
    function init() {
        particles.length = 0;
        for (let i = 0; i < PARTICLE_COUNT; i++) {
            particles.push(new Particle());
        }
        animate();
    }
    
    init();
    
    console.log('Neural Web: Initialized with', PARTICLE_COUNT, 'particles');
})();

// Typing animation
const typingText = document.getElementById('typingText');
const words = ["Student-Led Community", "Future Leaders", "Technical Excellence"];
let i = 0, j = 0, currentWord = '', isDeleting = false;

function type() {
    if (!typingText) return;
    currentWord = words[i];
    if (!isDeleting) {
        typingText.innerHTML = currentWord.slice(0, ++j);
        if (j === currentWord.length) {
            isDeleting = true;
            setTimeout(type, 1000);
            return;
        }
    } else {
        typingText.innerHTML = currentWord.slice(0, --j);
        if (j === 0) {
            isDeleting = false;
            i = (i + 1) % words.length;
        }
    }
    setTimeout(type, isDeleting ? 50 : 100);
}

if (typingText) {
    type();
}

// Scroll functionality
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('section, footer');
    const navLinks = document.querySelectorAll('.nav-link');
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (pageYOffset >= (sectionTop - sectionHeight / 3)) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href').substring(1) === current) {
            link.classList.add('active');
        }
    });
    
    // Scroll progress
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    const progressBar = document.getElementById('progressBar');
    if (progressBar) progressBar.style.width = scrolled + "%";
    
    // Scroll to top button
    const scrollBtn = document.getElementById('scrollToTop');
    if (scrollBtn) {
        if (document.body.scrollTop > 500 || document.documentElement.scrollTop > 500) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    }
});

// Scroll to top functionality
const scrollToTopBtn = document.getElementById('scrollToTop');
if (scrollToTopBtn) {
    scrollToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Project Modal Functionality
const modalBtns = document.querySelectorAll('[data-modal]');
const modals = document.querySelectorAll('.modal-overlay');
const closeBtns = document.querySelectorAll('[data-close]');

modalBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const modalId = btn.getAttribute('data-modal');
        const modal = document.getElementById(modalId);
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
});

closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const modalId = btn.getAttribute('data-close');
        const modal = document.getElementById(modalId);
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    });
});

modals.forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    });
});

// Enforce Dark Mode
document.body.classList.remove('light-mode');
localStorage.setItem('theme', 'dark');

// Enhanced project card interactions
document.querySelectorAll('.project-card .project-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const card = btn.closest('.project-card');
        card.style.transform = 'scale(0.98)';
        setTimeout(() => {
            card.style.transform = '';
        }, 150);
    });
});

// Enhanced scroll effect for project cards
const cards = document.querySelectorAll('.project-card');
const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('aos-animate');
        }
    });
}, { threshold: 0.1 });

cards.forEach(card => observer.observe(card));

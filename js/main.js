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

// Mobile menu toggle
const mobileBtn = document.getElementById('mobile-menu-button');
const mobileMenu = document.getElementById('mobile-menu');

function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    if (!mobileMenu) return;
    const isHidden = mobileMenu.classList.contains('hidden');
    
    if (isHidden) {
        mobileMenu.classList.remove('hidden');
        // Trigger reflow
        void mobileMenu.offsetWidth;
        mobileMenu.classList.remove('opacity-0', 'scale-95');
        mobileMenu.classList.add('opacity-100', 'scale-100');
    } else {
        mobileMenu.classList.remove('opacity-100', 'scale-100');
        mobileMenu.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            if (mobileMenu.classList.contains('opacity-0')) { // Check to prevent race condition
                mobileMenu.classList.add('hidden');
            }
        }, 300); 
    }
}

if (mobileBtn) {
    mobileBtn.addEventListener('click', toggleMobileMenu);
}

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

// Particle animation with increased speed
const canvas = document.getElementById('particle-canvas');
const ctx = canvas ? canvas.getContext('2d') : null;
const resize = () => {
    if (!canvas) return;
    canvas.width = innerWidth;
    canvas.height = innerHeight;
};
resize();
window.addEventListener('resize', resize);

class Particle {
    constructor(x, y, dx, dy, size) {
        this.x = x; this.y = y; this.dx = dx; this.dy = dy; this.size = size;
        this.color = `rgba(0, 242, 255, ${Math.random() * 0.5 + 0.1})`;
    }
    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = this.color;
        ctx.fill();
    }
    update() {
        if (this.x > canvas.width || this.x < 0) this.dx *= -1;
        if (this.y > canvas.height || this.y < 0) this.dy *= -1;
        this.x += this.dx; this.y += this.dy;
        this.draw();
    }
}

let particles = [];
const init = () => {
    if (!canvas) return;
    particles = [];
    const count = (canvas.width * canvas.height) / 9000;
    for (let i = 0; i < count; i++) {
        const size = Math.random() * 2 + 1;
        const x = Math.random() * (canvas.width - size * 2) + size;
        const y = Math.random() * (canvas.height - size * 2) + size;
        // Increased particle speed
        const dx = Math.random() * 2 - 1; // Increased from 0.4 to 1
        const dy = Math.random() * 2 - 1; // Increased from 0.4 to 1
        particles.push(new Particle(x, y, dx, dy, size));
    }
};

const connect = () => {
    if (!canvas) return;
    for (let a = 0; a < particles.length; a++) {
        for (let b = a; b < particles.length; b++) {
            const dx = particles[a].x - particles[b].x;
            const dy = particles[a].y - particles[b].y;
            const dist = dx * dx + dy * dy;
            if (dist < (canvas.width / 7) * (canvas.height / 7)) {
                const opacity = 1 - dist / 20000;
                ctx.strokeStyle = `rgba(0, 242, 255, ${opacity * 0.1})`;
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(particles[a].x, particles[a].y);
                ctx.lineTo(particles[b].x, particles[b].y);
                ctx.stroke();
            }
        }
    }
};

const animate = () => {
    if (!canvas || !ctx) return;
    requestAnimationFrame(animate);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach((p) => p.update());
    connect();
};

if (canvas && ctx) {
    init();
    animate();
}

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

// Dark mode toggle functionality
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');

if (themeToggle && themeIcon) {
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        document.body.classList.remove('light-mode');
        themeIcon.setAttribute('data-feather', 'moon');
    } else if (currentTheme === 'light') {
        document.body.classList.add('light-mode');
        themeIcon.setAttribute('data-feather', 'sun');
    }
    if (window.feather && typeof window.feather.replace === 'function') {
        window.feather.replace();
    }

    themeToggle.addEventListener('click', function() {
        if (document.body.classList.contains('light-mode')) {
            document.body.classList.remove('light-mode');
            localStorage.setItem('theme', 'dark');
            themeIcon.setAttribute('data-feather', 'moon');
        } else {
            document.body.classList.add('light-mode');
            localStorage.setItem('theme', 'light');
            themeIcon.setAttribute('data-feather', 'sun');
        }
        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }
    });
}

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

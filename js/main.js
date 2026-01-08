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

// Particle animation with reduced intensity on mobile for performance
const canvas = document.getElementById('particle-canvas');
const ctx = canvas ? canvas.getContext('2d') : null;

// Detect mobile device
const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;

// Debounce function for resize events
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const resize = () => {
    if (!canvas) return;
    canvas.width = innerWidth;
    canvas.height = innerHeight;
    // Reinitialize particles on resize
    if (particles.length > 0) {
        init();
    }
};

resize();
window.addEventListener('resize', debounce(resize, 250));

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
    // Significantly reduce particles on mobile for better performance
    const baseDivisor = isMobile ? 25000 : 9000;
    const count = Math.min((canvas.width * canvas.height) / baseDivisor, isMobile ? 30 : 80);
    for (let i = 0; i < count; i++) {
        const size = Math.random() * 2 + 1;
        const x = Math.random() * (canvas.width - size * 2) + size;
        const y = Math.random() * (canvas.height - size * 2) + size;
        // Slower particles on mobile for smoother animation
        const speedMultiplier = isMobile ? 0.5 : 1;
        const dx = (Math.random() * 2 - 1) * speedMultiplier;
        const dy = (Math.random() * 2 - 1) * speedMultiplier;
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

// Initialize Feather Icons
feather.replace();

// Initialize AOS animation library
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true, /* Change to true for animations to happen only once */
    mirror: false
});

// Mobile menu toggle
const mobileBtn = document.getElementById('mobile-menu-button');
const mobileMenu = document.getElementById('mobile-menu');
mobileBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelector(a.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
        if (!mobileMenu.classList.contains('hidden')) mobileMenu.classList.add('hidden');
    });
});

// Particle animation with increased speed
const canvas = document.getElementById('particle-canvas');
const ctx = canvas.getContext('2d');
const resize = () => ((canvas.width = innerWidth), (canvas.height = innerHeight));
resize(); window.addEventListener('resize', resize);

class Particle {
    constructor(x, y, dx, dy, size) {
        this.x = x; this.y = y; this.dx = dx; this.dy = dy; this.size = size;
        this.color = `rgba(0, 245, 212, ${Math.random() * 0.5 + 0.1})`;
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
    for (let a = 0; a < particles.length; a++) {
        for (let b = a; b < particles.length; b++) {
            const dx = particles[a].x - particles[b].x;
            const dy = particles[a].y - particles[b].y;
            const dist = dx * dx + dy * dy;
            if (dist < (canvas.width / 7) * (canvas.height / 7)) {
                const opacity = 1 - dist / 20000;
                ctx.strokeStyle = `rgba(0,245,212,${opacity * 0.1})`;
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
    requestAnimationFrame(animate);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach((p) => p.update());
    connect();
};

init(); 
animate();

// Typing animation
const typingText = document.getElementById('typingText');
const words = ["AI & Robotics Innovator", "Tech Mentor", "Future Engineer"];
let i = 0, j = 0, currentWord = '', isDeleting = false;

function type() {
    currentWord = words[i];
    if (!isDeleting) {
        typingText.innerHTML = currentWord.slice(0, ++j) + '<span class="cursor"></span>';
        if (j === currentWord.length) {
            isDeleting = true;
            setTimeout(type, 1000);
            return;
        }
    } else {
        typingText.innerHTML = currentWord.slice(0, --j) + '<span class="cursor"></span>';
        if (j === 0) {
            isDeleting = false;
            i = (i + 1) % words.length;
        }
    }
    setTimeout(type, isDeleting ? 50 : 100);
}

type();

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
    document.getElementById("progressBar").style.width = scrolled + "%";
    
    // Scroll to top button
    const scrollBtn = document.getElementById('scrollToTop');
    if (document.body.scrollTop > 500 || document.documentElement.scrollTop > 500) {
        scrollBtn.classList.add('show');
    } else {
        scrollBtn.classList.remove('show');
    }
});

// Scroll to top functionality
document.getElementById('scrollToTop').addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

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

const currentTheme = localStorage.getItem('theme');
if (currentTheme === 'dark') {
    document.body.classList.remove('light-mode');
    themeIcon.setAttribute('data-feather', 'moon');
} else if (currentTheme === 'light') {
    document.body.classList.add('light-mode');
    themeIcon.setAttribute('data-feather', 'sun');
}
feather.replace();

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
    feather.replace();
});

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

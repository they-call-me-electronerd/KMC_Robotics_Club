/**
 * KMC Robotics Club - API Client
 * Handles all AJAX/fetch API calls for dynamic content
 */

const API = {
    baseUrl: '/api',
    
    /**
     * Make an API request
     * @param {string} endpoint - API endpoint
     * @param {object} options - Fetch options
     * @returns {Promise}
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint}`;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        };
        
        const fetchOptions = { ...defaultOptions, ...options };
        
        // If body is FormData, remove Content-Type header
        if (options.body instanceof FormData) {
            delete fetchOptions.headers['Content-Type'];
        }
        
        try {
            const response = await fetch(url, fetchOptions);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'An error occurred');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },
    
    // GET request helper
    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },
    
    // POST request helper
    post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: data instanceof FormData ? data : JSON.stringify(data)
        });
    },
    
    // PUT request helper
    put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    // DELETE request helper
    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

/**
 * Events Module
 */
const Events = {
    async getAll(params = {}) {
        const query = new URLSearchParams(params).toString();
        return API.get(`events.php?action=list&${query}`);
    },
    
    async getUpcoming(limit = 6) {
        return API.get(`events.php?action=upcoming&limit=${limit}`);
    },
    
    async getFeatured() {
        return API.get('events.php?action=featured');
    },
    
    async getById(id) {
        return API.get(`events.php?action=get&id=${id}`);
    },
    
    async register(eventId) {
        return API.post('events.php?action=register', { event_id: eventId });
    },
    
    // Render event card
    renderCard(event) {
        const date = new Date(event.event_date);
        const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        
        return `
            <div class="event-card bg-light-navy/50 backdrop-blur-sm rounded-lg overflow-hidden border border-slate-800 hover:border-accent/30 transition" data-aos="fade-up">
                <div class="aspect-video relative overflow-hidden">
                    ${event.image_path 
                        ? `<img src="uploads/events/${event.image_path}" alt="${event.title}" class="w-full h-full object-cover">`
                        : `<div class="w-full h-full bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                            <i data-feather="calendar" class="w-16 h-16 text-accent/30"></i>
                           </div>`
                    }
                    ${event.is_featured ? '<span class="absolute top-2 right-2 bg-yellow-500/80 text-dark-navy text-xs px-2 py-1 rounded">Featured</span>' : ''}
                </div>
                <div class="p-4">
                    <span class="text-xs text-purple-400 bg-purple-500/20 px-2 py-1 rounded">${event.category}</span>
                    <h3 class="text-lg font-bold text-white mt-2">${event.title}</h3>
                    <div class="flex items-center gap-2 text-slate-400 text-sm mt-2">
                        <i data-feather="calendar" class="w-4 h-4"></i>
                        ${dateStr}
                    </div>
                    ${event.location ? `
                    <div class="flex items-center gap-2 text-slate-400 text-sm mt-1">
                        <i data-feather="map-pin" class="w-4 h-4"></i>
                        ${event.location}
                    </div>` : ''}
                    ${event.short_description ? `<p class="text-slate-300 text-sm mt-2 line-clamp-2">${event.short_description}</p>` : ''}
                </div>
            </div>
        `;
    }
};

/**
 * Team Module
 */
const Team = {
    async getAll(params = {}) {
        const query = new URLSearchParams(params).toString();
        return API.get(`team.php?action=list&${query}`);
    },
    
    async getExecutive() {
        return API.get('team.php?action=executive');
    },
    
    async getCategories() {
        return API.get('team.php?action=categories');
    },
    
    // Render team member card
    renderCard(member) {
        return `
            <div class="team-card bg-light-navy/50 backdrop-blur-sm rounded-lg overflow-hidden border border-slate-800 hover:border-accent/30 transition text-center p-6" data-aos="fade-up">
                <div class="w-24 h-24 mx-auto rounded-full overflow-hidden mb-4">
                    ${member.image_path 
                        ? `<img src="uploads/team/${member.image_path}" alt="${member.name}" class="w-full h-full object-cover">`
                        : `<div class="w-full h-full bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                            <span class="text-accent font-bold text-2xl">${member.name.charAt(0)}</span>
                           </div>`
                    }
                </div>
                <h3 class="text-lg font-bold text-white">${member.name}</h3>
                <p class="text-accent text-sm">${member.position}</p>
                ${member.bio ? `<p class="text-slate-400 text-sm mt-2">${member.bio}</p>` : ''}
                <div class="flex justify-center gap-3 mt-4">
                    ${member.linkedin_url ? `<a href="${member.linkedin_url}" target="_blank" class="text-slate-400 hover:text-accent"><i data-feather="linkedin" class="w-5 h-5"></i></a>` : ''}
                    ${member.github_url ? `<a href="${member.github_url}" target="_blank" class="text-slate-400 hover:text-accent"><i data-feather="github" class="w-5 h-5"></i></a>` : ''}
                    ${member.email ? `<a href="mailto:${member.email}" class="text-slate-400 hover:text-accent"><i data-feather="mail" class="w-5 h-5"></i></a>` : ''}
                </div>
            </div>
        `;
    }
};

/**
 * Gallery Module
 */
const Gallery = {
    async getAll(params = {}) {
        const query = new URLSearchParams(params).toString();
        return API.get(`gallery.php?action=list&${query}`);
    },
    
    async getFeatured() {
        return API.get('gallery.php?action=featured');
    },
    
    async upload(formData) {
        return API.post('gallery.php?action=upload', formData);
    },
    
    // Render gallery item
    renderItem(item) {
        return `
            <div class="gallery-item relative aspect-square overflow-hidden rounded-lg cursor-pointer group" data-id="${item.id}" data-aos="fade-up">
                <img src="uploads/gallery/${item.thumbnail_path || item.image_path}" 
                     alt="${item.title || ''}" 
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-dark-navy/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                    <div>
                        <h4 class="text-white font-medium">${item.title || 'Untitled'}</h4>
                        <p class="text-slate-400 text-sm">${item.category}</p>
                    </div>
                </div>
            </div>
        `;
    }
};

/**
 * Messages Module
 */
const Messages = {
    async sendContact(data) {
        return API.post('messages.php?action=contact', data);
    },
    
    async getUnreadCount() {
        return API.get('messages.php?action=unread-count');
    }
};

/**
 * Utility Functions
 */
const Utils = {
    // Show notification
    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all transform translate-x-full ${
            type === 'success' ? 'bg-green-500/90 text-white' : 
            type === 'error' ? 'bg-red-500/90 text-white' : 
            'bg-accent/90 text-dark-navy'
        }`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <i data-feather="${type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info'}" class="w-5 h-5"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => notification.classList.remove('translate-x-full'), 10);
        
        // Remove after delay
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
        
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    },
    
    // Format date
    formatDate(dateStr, options = {}) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            ...options
        });
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Load more functionality
    createLoadMore(container, fetchFn, renderFn) {
        let page = 1;
        let loading = false;
        let hasMore = true;
        
        return {
            async load() {
                if (loading || !hasMore) return;
                
                loading = true;
                try {
                    const data = await fetchFn(page);
                    
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(item => {
                            container.insertAdjacentHTML('beforeend', renderFn(item));
                        });
                        page++;
                        
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                        if (typeof AOS !== 'undefined') {
                            AOS.refresh();
                        }
                    }
                    
                    hasMore = data.pagination && page <= data.pagination.total_pages;
                } catch (error) {
                    console.error('Load more error:', error);
                }
                loading = false;
            },
            
            hasMore() {
                return hasMore;
            }
        };
    }
};

/**
 * Contact Form Handler
 */
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form');
    
    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                subject: formData.get('subject'),
                message: formData.get('message')
            };
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i data-feather="loader" class="w-4 h-4 animate-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            try {
                await Messages.sendContact(data);
                Utils.showNotification('Message sent successfully!', 'success');
                this.reset();
            } catch (error) {
                Utils.showNotification(error.message || 'Failed to send message', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    }
});

// Export modules for use in other scripts
window.KMCRC = {
    API,
    Events,
    Team,
    Gallery,
    Messages,
    Utils
};

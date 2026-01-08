<?php
/**
 * KMC Robotics Club - Gallery Page
 * Displays approved gallery images with filtering and lightbox
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/Auth.php';

$db = Database::getInstance();
Security::startSession();

$isLoggedIn = Auth::isLoggedIn();
$currentUser = $isLoggedIn ? Auth::getCurrentUser() : null;

// Filters
$category = $_GET['category'] ?? '';

// Get categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM gallery WHERE is_approved = 1 ORDER BY category");

// Build query
$where = "WHERE is_approved = 1";
$params = [];

if ($category) {
    $where .= " AND category = ?";
    $params[] = $category;
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$countQuery = "SELECT COUNT(*) as total FROM gallery $where";
$total = $db->fetchOne($countQuery, $params)['total'];
$totalPages = ceil($total / $perPage);

$query = "SELECT g.*, u.full_name as uploader_name 
          FROM gallery g 
          LEFT JOIN users u ON g.uploaded_by = u.id 
          $where 
          ORDER BY g.is_featured DESC, g.created_at DESC 
          LIMIT $perPage OFFSET $offset";
$items = $db->fetchAll($query, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - KMC Robotics Club</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark-navy': '#050a14',
                        'light-navy': '#0a1628',
                        'accent': '#00f2ff',
                        'secondary-accent': '#7000ff'
                    },
                    fontFamily: {
                        'orbitron': ['Orbitron', 'sans-serif'],
                        'rajdhani': ['Rajdhani', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    
    <style>
        .masonry-grid {
            columns: 1;
            column-gap: 1rem;
        }
        @media (min-width: 640px) {
            .masonry-grid { columns: 2; }
        }
        @media (min-width: 768px) {
            .masonry-grid { columns: 3; }
        }
        @media (min-width: 1024px) {
            .masonry-grid { columns: 4; }
        }
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 1rem;
        }
        
        .lightbox {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .lightbox.active {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>
<body class="bg-dark-navy text-white font-rajdhani min-h-screen">
    <!-- Particle Background -->
    <div id="particles-container" class="fixed inset-0 z-0"></div>
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-dark-navy/80 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="../index.php" class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-accent to-secondary-accent rounded-lg flex items-center justify-center">
                        <span class="font-orbitron font-bold text-dark-navy">KR</span>
                    </div>
                    <span class="font-orbitron font-bold text-lg hidden sm:block">KMC Robotics</span>
                </a>
                
                <div class="hidden md:flex items-center gap-6">
                    <a href="../index.php" class="text-slate-300 hover:text-accent transition">Home</a>
                    <a href="about.php" class="text-slate-300 hover:text-accent transition">About</a>
                    <a href="events.php" class="text-slate-300 hover:text-accent transition">Events</a>
                    <a href="team.php" class="text-slate-300 hover:text-accent transition">Team</a>
                    <a href="gallery.php" class="text-accent font-medium">Gallery</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $currentUser['role'] === 'admin' ? '../admin/dashboard.php' : '../member/dashboard.php' ?>" class="text-slate-300 hover:text-accent transition">Dashboard</a>
                        <a href="../auth/logout.php" class="bg-red-500/20 text-red-400 px-4 py-2 rounded-lg hover:bg-red-500/30 transition">Logout</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="text-slate-300 hover:text-accent transition">Login</a>
                        <a href="join.php" class="bg-accent text-dark-navy px-4 py-2 rounded-lg font-semibold hover:bg-accent/80 transition">Join Us</a>
                    <?php endif; ?>
                </div>
                
                <button id="mobile-menu-btn" class="md:hidden text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <div id="mobile-menu" class="hidden md:hidden bg-light-navy border-t border-slate-800">
            <div class="px-4 py-4 space-y-2">
                <a href="../index.php" class="block text-slate-300 hover:text-accent py-2">Home</a>
                <a href="about.php" class="block text-slate-300 hover:text-accent py-2">About</a>
                <a href="events.php" class="block text-slate-300 hover:text-accent py-2">Events</a>
                <a href="team.php" class="block text-slate-300 hover:text-accent py-2">Team</a>
                <a href="gallery.php" class="block text-accent py-2">Gallery</a>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="pt-32 pb-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="font-orbitron text-4xl md:text-5xl font-bold mb-4" data-aos="fade-up">
                <span class="bg-gradient-to-r from-accent to-secondary-accent bg-clip-text text-transparent">Gallery</span>
            </h1>
            <p class="text-slate-400 text-lg max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">
                Explore our projects, events, and memorable moments captured through the lens.
            </p>
        </div>
    </section>
    
    <!-- Category Filter -->
    <section class="pb-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-2 justify-center" data-aos="fade-up">
                <a href="gallery.php" 
                   class="px-4 py-2 rounded-lg <?= !$category ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                    All
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= urlencode($cat['category']) ?>" 
                   class="px-4 py-2 rounded-lg <?= $category === $cat['category'] ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                    <?= htmlspecialchars($cat['category']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Gallery Grid -->
    <section class="py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (empty($items)): ?>
                <div class="text-center py-16 bg-light-navy/30 rounded-lg border border-slate-800">
                    <svg class="w-16 h-16 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-slate-400">No images found</h3>
                    <p class="text-slate-500 mt-2">Check back later for new content!</p>
                </div>
            <?php else: ?>
                <div class="masonry-grid">
                    <?php foreach ($items as $index => $item): ?>
                    <div class="masonry-item" data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 50 ?>">
                        <div class="relative overflow-hidden rounded-lg cursor-pointer group" 
                             onclick="openLightbox(<?= $item['id'] ?>)">
                            <img src="../uploads/gallery/<?= htmlspecialchars($item['thumbnail_path'] ?: $item['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($item['title']) ?>" 
                                 class="w-full object-cover transition-transform duration-500 group-hover:scale-110">
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-dark-navy/90 via-dark-navy/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <div class="absolute bottom-0 left-0 right-0 p-4">
                                    <h4 class="text-white font-semibold"><?= htmlspecialchars($item['title'] ?: 'Untitled') ?></h4>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-accent text-sm"><?= htmlspecialchars($item['category']) ?></span>
                                        <?php if ($item['is_featured']): ?>
                                            <span class="text-yellow-400 text-xs flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                </svg>
                                                Featured
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="w-10 h-10 bg-accent/80 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-dark-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center gap-2 mt-12">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?>" 
                           class="px-4 py-2 bg-light-navy rounded-lg text-slate-300 hover:bg-slate-800 transition">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?><?= $category ? '&category=' . urlencode($category) : '' ?>" 
                           class="px-4 py-2 rounded-lg <?= $i === $page ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?>" 
                           class="px-4 py-2 bg-light-navy rounded-lg text-slate-300 hover:bg-slate-800 transition">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Upload CTA (for logged-in members) -->
    <?php if ($isLoggedIn): ?>
    <section class="py-12 relative z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="bg-gradient-to-br from-accent/10 to-secondary-accent/10 rounded-2xl p-8 border border-accent/20">
                <h3 class="font-orbitron text-2xl font-bold mb-4">Share Your Work</h3>
                <p class="text-slate-400 mb-6">Have photos from our events or your projects? Upload them to our gallery!</p>
                <a href="../member/dashboard.php" class="inline-flex items-center gap-2 bg-accent text-dark-navy px-6 py-3 rounded-lg font-semibold hover:bg-accent/80 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Upload Images
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Lightbox -->
    <div id="lightbox" class="lightbox fixed inset-0 z-[100] bg-dark-navy/95 flex items-center justify-center p-4">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-accent transition">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        
        <button onclick="prevImage()" class="absolute left-4 text-white hover:text-accent transition">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        
        <button onclick="nextImage()" class="absolute right-4 text-white hover:text-accent transition">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        
        <div class="max-w-5xl max-h-[90vh] relative">
            <img id="lightbox-image" src="" alt="" class="max-w-full max-h-[80vh] object-contain rounded-lg">
            <div id="lightbox-info" class="mt-4 text-center">
                <h4 id="lightbox-title" class="text-xl font-bold text-white"></h4>
                <p id="lightbox-description" class="text-slate-400 mt-2"></p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-light-navy/50 border-t border-slate-800 py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between text-sm text-slate-400">
                <p>&copy; <?= date('Y') ?> KMC Robotics Club. All rights reserved.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="../index.php" class="hover:text-accent transition">Home</a>
                    <a href="about.php" class="hover:text-accent transition">About</a>
                    <a href="team.php" class="hover:text-accent transition">Team</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="../js/main.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Gallery data
        const galleryItems = <?= json_encode(array_map(function($item) {
            return [
                'id' => $item['id'],
                'image' => $item['image_path'],
                'title' => $item['title'] ?: 'Untitled',
                'description' => $item['description'] ?: ''
            ];
        }, $items)) ?>;
        
        let currentIndex = 0;
        
        function openLightbox(id) {
            const index = galleryItems.findIndex(item => item.id == id);
            if (index !== -1) {
                currentIndex = index;
                updateLightbox();
                document.getElementById('lightbox').classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function updateLightbox() {
            const item = galleryItems[currentIndex];
            document.getElementById('lightbox-image').src = '../uploads/gallery/' + item.image;
            document.getElementById('lightbox-title').textContent = item.title;
            document.getElementById('lightbox-description').textContent = item.description;
        }
        
        function nextImage() {
            currentIndex = (currentIndex + 1) % galleryItems.length;
            updateLightbox();
        }
        
        function prevImage() {
            currentIndex = (currentIndex - 1 + galleryItems.length) % galleryItems.length;
            updateLightbox();
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (!document.getElementById('lightbox').classList.contains('active')) return;
            
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') nextImage();
            if (e.key === 'ArrowLeft') prevImage();
        });
        
        // Close on background click
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) closeLightbox();
        });
    </script>
</body>
</html>

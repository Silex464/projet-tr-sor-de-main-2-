// Fonction pour basculer le menu mobile
function toggleMobileMenu() {
    var menu = document.getElementById("menuMobile");
    if (menu) {
        menu.classList.toggle("visible");
        
        // Fermer le menu si on clique ailleurs
        document.addEventListener('click', function(event) {
            var menuButton = document.querySelector('.btn-menu-mobile');
            var isClickInside = menu.contains(event.target) || 
                               (menuButton && menuButton.contains(event.target));
            
            if (!isClickInside && menu.classList.contains('visible')) {
                menu.classList.remove('visible');
            }
        });
    }
}

// Fermer le menu en appuyant sur Echap
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        var menu = document.getElementById("menuMobile");
        if (menu && menu.classList.contains('visible')) {
            menu.classList.remove('visible');
        }
    }
});

// Smooth scroll pour les ancres
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            e.preventDefault();
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
            
            // Fermer le menu mobile si ouvert
            var menu = document.getElementById("menuMobile");
            if (menu && menu.classList.contains('visible')) {
                menu.classList.remove('visible');
            }
        }
    });
});

// Animation au scroll
function handleScrollAnimations() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const windowHeight = window.innerHeight;
        
        if (elementTop < windowHeight - 100) {
            element.classList.add('animate-fadeIn');
        }
    });
}

// Initialiser les animations au chargement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnimations);
} else {
    initAnimations();
}

function initAnimations() {
    // Ajouter la classe aux éléments qui doivent s'animer
    document.querySelectorAll('section, .card, .boite-contenu').forEach((el, index) => {
        if (!el.classList.contains('animate-on-scroll')) {
            el.classList.add('animate-on-scroll');
            el.style.animationDelay = `${index * 0.1}s`;
        }
    });
    
    handleScrollAnimations();
    window.addEventListener('scroll', handleScrollAnimations);
}

// Gestion des images qui se chargent
document.addEventListener('DOMContentLoaded', function() {
    // Observer les images pour le lazy loading
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
    }
});

// ============================================================================
// SYSTÈME DE NOTIFICATIONS TOAST
// ============================================================================

function showToast(message, type = 'info', duration = 3000) {
    // Créer le conteneur si nécessaire
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;top:100px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }
    
    // Créer le toast
    const toast = document.createElement('div');
    const colors = {
        success: { bg: '#28a745', icon: '✓' },
        error: { bg: '#dc3545', icon: '✗' },
        warning: { bg: '#ffc107', icon: '⚠' },
        info: { bg: '#8D5524', icon: 'ℹ' }
    };
    const { bg, icon } = colors[type] || colors.info;
    
    toast.style.cssText = `
        background:${bg};color:white;padding:15px 25px;border-radius:10px;
        box-shadow:0 5px 20px rgba(0,0,0,0.2);display:flex;align-items:center;gap:12px;
        font-weight:500;min-width:250px;max-width:400px;
        animation:slideIn 0.3s ease-out;
    `;
    toast.innerHTML = `<span style="font-size:1.3rem;">${icon}</span><span>${message}</span>`;
    
    container.appendChild(toast);
    
    // Supprimer après le délai
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Ajouter les styles d'animation
(function() {
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        `;
        document.head.appendChild(style);
    }
})();

// ============================================================================
// LOADING STATES
// ============================================================================

function showLoading(element, text = 'Chargement...') {
    if (element) {
        element.dataset.originalContent = element.innerHTML;
        element.innerHTML = `<span class="loading-spinner"></span> ${text}`;
        element.disabled = true;
        element.style.opacity = '0.7';
    }
}

function hideLoading(element) {
    if (element && element.dataset.originalContent) {
        element.innerHTML = element.dataset.originalContent;
        element.disabled = false;
        element.style.opacity = '1';
    }
}

// Ajouter le spinner CSS
(function() {
    if (!document.getElementById('spinner-styles')) {
        const style = document.createElement('style');
        style.id = 'spinner-styles';
        style.textContent = `
            .loading-spinner {
                display: inline-block;
                width: 16px; height: 16px;
                border: 2px solid rgba(255,255,255,0.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 0.8s linear infinite;
            }
            @keyframes spin { to { transform: rotate(360deg); } }
        `;
        document.head.appendChild(style);
    }
})();

// ============================================================================
// UTILITAIRES
// ============================================================================

// Fonction pour les filtres mobiles (si utilisée)
function toggleFiltresMobile() {
    const filtres = document.getElementById('filtresMobile');
    if (filtres) {
        filtres.classList.toggle('active');
        document.body.style.overflow = filtres.classList.contains('active') ? 'hidden' : '';
    }
}

// Fermer les filtres mobiles en cliquant sur le bouton fermer
document.addEventListener('DOMContentLoaded', function() {
    const closeButtons = document.querySelectorAll('.close-filtres, .btn-filtres-mobile');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const filtres = document.getElementById('filtresMobile');
            if (filtres) {
                filtres.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
});

// Gérer le redimensionnement de la fenêtre
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        // Fermer le menu mobile si on passe en desktop
        if (window.innerWidth > 768) {
            const menu = document.getElementById("menuMobile");
            if (menu && menu.classList.contains('visible')) {
                menu.classList.remove('visible');
            }
        }
    }, 250);
});

// ============================================================================
// CONFIRMATION DIALOGS
// ============================================================================

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ============================================================================
// FORMAT HELPERS
// ============================================================================

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
}
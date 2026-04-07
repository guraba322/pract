// ============================================
// JAVASCRIPT ДЛЯ АНИМАЦИЙ И ЭФФЕКТОВ
// Фитнес-клуб "Энергия"
// ============================================

// Анимации при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Добавляем классы для анимации элементов
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-on-load-delay-' + (index % 3 + 1));
    });

    // Анимация для hero секции - исправлено, чтобы текст не пропадал
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        // Не добавляем класс, который может скрыть контент
        // Вместо этого используем встроенную анимацию из CSS
        heroContent.style.opacity = '1';
        heroContent.style.visibility = 'visible';
    }
    
    // Убеждаемся, что элементы hero всегда видимы
    const heroH1 = document.querySelector('.hero h1');
    const heroP = document.querySelector('.hero p');
    const heroButton = document.querySelector('.hero .cta-button');
    
    if (heroH1) {
        heroH1.style.opacity = '1';
        heroH1.style.visibility = 'visible';
    }
    if (heroP) {
        heroP.style.opacity = '1';
        heroP.style.visibility = 'visible';
    }
    if (heroButton) {
        heroButton.style.opacity = '1';
        heroButton.style.visibility = 'visible';
    }

    // Анимация для формы
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach((group, index) => {
        group.style.opacity = '0';
        setTimeout(() => {
            group.style.transition = 'opacity 0.6s ease-out';
            group.style.opacity = '1';
        }, index * 100);
    });
});

// Анимации при скролле
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animated');
        }
    });
}, observerOptions);

// Наблюдаем за элементами с классом animate-on-scroll
document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    animateElements.forEach(el => observer.observe(el));
});

// Эффект для header при скролле
let lastScroll = 0;
const header = document.querySelector('.header');

window.addEventListener('scroll', function() {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
    
    lastScroll = currentScroll;
});

// Параллакс эффект для hero - исправлено, чтобы не скрывать контент
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const hero = document.querySelector('.hero');
    const heroContent = document.querySelector('.hero-content');
    
    if (hero) {
        // Применяем параллакс только к фону, не к контенту
        hero.style.transform = `translateY(${scrolled * 0.3}px)`;
    }
    
    // Убеждаемся, что контент всегда видим
    if (heroContent) {
        heroContent.style.opacity = '1';
        heroContent.style.visibility = 'visible';
        heroContent.style.transform = 'translateY(0)';
    }
});

// Анимация для кнопок при наведении
document.querySelectorAll('.cta-button, .submit-btn, .buy-ticket-btn, .register-btn').forEach(button => {
    button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px) scale(1.05)';
    });
    
    button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Анимация для карточек услуг
document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Плавная прокрутка для якорных ссылок
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Анимация для иконок в карточках услуг
document.querySelectorAll('.service-card i').forEach(icon => {
    icon.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.2) rotate(10deg)';
    });
    
    icon.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1) rotate(0deg)';
    });
});

// Анимация для социальных ссылок
document.querySelectorAll('.social-link').forEach(link => {
    link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) rotate(5deg)';
    });
    
    link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) rotate(0deg)';
    });
});

// Анимация для формы при фокусе
document.querySelectorAll('input, textarea, select').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});

// Анимация появления сообщений
function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
    messageDiv.textContent = message;
    messageDiv.style.opacity = '0';
    messageDiv.style.transform = 'translateY(-20px)';
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.style.transition = 'all 0.5s ease-out';
        messageDiv.style.opacity = '1';
        messageDiv.style.transform = 'translateY(0)';
    }, 10);
    
    setTimeout(() => {
        messageDiv.style.opacity = '0';
        messageDiv.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            messageDiv.remove();
        }, 500);
    }, 3000);
}

// Анимация для профильного меню
function toggleProfileMenu() {
    const menu = document.getElementById('profileMenu');
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        if (menu.style.display === 'block') {
            menu.style.opacity = '0';
            menu.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                menu.style.transition = 'all 0.3s ease-out';
                menu.style.opacity = '1';
                menu.style.transform = 'translateY(0)';
            }, 10);
        }
    }
}

// Закрытие профильного меню при клике вне его
document.addEventListener('click', function(event) {
    const profileIcon = document.querySelector('.profile-icon');
    const profileMenu = document.getElementById('profileMenu');
    
    if (profileIcon && profileMenu && 
        !profileIcon.contains(event.target) && 
        !profileMenu.contains(event.target)) {
        profileMenu.style.display = 'none';
    }
});

// Анимация для breadcrumbs
document.querySelectorAll('.breadcrumb-link').forEach(link => {
    link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(5px)';
    });
    
    link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});

// Эффект печатания для заголовков (опционально)
function typeWriter(element, text, speed = 100) {
    let i = 0;
    element.textContent = '';
    
    function type() {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// Анимация счетчиков (для статистики)
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    function updateCounter() {
        start += increment;
        if (start < target) {
            element.textContent = Math.floor(start);
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = target;
        }
    }
    
    updateCounter();
}

// Lazy loading для изображений
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('fade-in');
                observer.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// Анимация для таблиц
document.querySelectorAll('table tr').forEach((row, index) => {
    row.style.opacity = '0';
    row.style.transform = 'translateX(-20px)';
    
    setTimeout(() => {
        row.style.transition = 'all 0.5s ease-out';
        row.style.opacity = '1';
        row.style.transform = 'translateX(0)';
    }, index * 50);
});

// Эффект ripple для кнопок
document.querySelectorAll('.cta-button, .submit-btn, .buy-ticket-btn, .register-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});

// Добавляем стили для ripple эффекта
const style = document.createElement('style');
style.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Анимация для модальных окон
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.transition = 'opacity 0.3s ease-out';
            modal.style.opacity = '1';
        }, 10);
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

// Предзагрузка страницы (loader)
window.addEventListener('load', function() {
    const loader = document.querySelector('.page-loader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500);
    }
});

// Анимация для аккордеона
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', function() {
        const item = this.parentElement;
        const content = item.querySelector('.accordion-content');
        
        if (item.classList.contains('active')) {
            content.style.maxHeight = '0';
            item.classList.remove('active');
        } else {
            document.querySelectorAll('.accordion-item.active').forEach(activeItem => {
                activeItem.querySelector('.accordion-content').style.maxHeight = '0';
                activeItem.classList.remove('active');
            });
            
            content.style.maxHeight = content.scrollHeight + 'px';
            item.classList.add('active');
        }
    });
});

// Анимация для табов
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabContent = document.querySelectorAll('.tab-content');
        tabContent.forEach(content => {
            content.style.opacity = '0';
            setTimeout(() => {
                content.style.display = 'none';
            }, 300);
        });
        
        const targetContent = document.querySelector(this.dataset.tab);
        if (targetContent) {
            targetContent.style.display = 'block';
            setTimeout(() => {
                targetContent.style.opacity = '1';
            }, 10);
        }
    });
});

// Консольное сообщение
console.log('%c🎉 Анимации загружены!', 'color: #3498db; font-size: 20px; font-weight: bold;');
console.log('%cФитнес-клуб "Энергия"', 'color: #2c3e50; font-size: 14px;');


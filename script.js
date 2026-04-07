function toggleProfileMenu() {
    const menu = document.getElementById('profileMenu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Закрываем меню при клике вне его
document.addEventListener('click', function(e) {
    if (!e.target.closest('.profile-icon')) {
        document.getElementById('profileMenu').style.display = 'none';
    }
});

// Закрываем сообщения через 5 секунд
setTimeout(function() {
    const messages = document.querySelectorAll('.success-message, .error-message');
    messages.forEach(message => {
        message.style.display = 'none';
    });
}, 5000);

// Подсветка активной ссылки в навигации при скролле
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav a[href^="#"]');
    
    function highlightActiveSection() {
        let current = '';
        const scrollPosition = window.pageYOffset + 150;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            if (href === '#' + current) {
                link.classList.add('active');
            }
        });
    }
    
    // Подсветка при скролле
    window.addEventListener('scroll', highlightActiveSection);
    
    // Плавная прокрутка к секциям
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const targetId = href.substring(1);
                const targetSection = document.getElementById(targetId);
                
                if (targetSection) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const breadcrumbsHeight = document.querySelector('.breadcrumbs')?.offsetHeight || 0;
                    const targetPosition = targetSection.offsetTop - headerHeight - breadcrumbsHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Обновляем URL без перезагрузки страницы
                    history.pushState(null, null, href);
                }
            }
        });
    });
    
    // Проверяем активную секцию при загрузке страницы
    highlightActiveSection();
});
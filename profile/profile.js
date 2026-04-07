// Profile Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Плавная прокрутка к секциям
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                // Убираем активный класс у всех ссылок
                navLinks.forEach(nl => nl.classList.remove('active'));
                // Добавляем активный класс текущей ссылке
                this.classList.add('active');
                
                // Плавная прокрутка
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Валидация формы смены пароля
    const passwordForm = document.querySelector('form[action*="change_password"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Пароли не совпадают!');
                return;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 6 символов!');
                return;
            }
            
            // Показываем состояние загрузки
            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });
    }

    // Авто-форматирование телефона
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.startsWith('7') || value.startsWith('8')) {
                value = value.substring(1);
            }
            
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue = '+7 (';
                if (value.length > 3) {
                    formattedValue += value.substring(0, 3) + ') ' + value.substring(3, 6);
                    if (value.length > 6) {
                        formattedValue += '-' + value.substring(6, 8);
                        if (value.length > 8) {
                            formattedValue += '-' + value.substring(8, 10);
                        }
                    }
                } else {
                    formattedValue += value;
                }
            }
            
            e.target.value = formattedValue;
        });
    }

    // Автоматическое скрытие сообщений через 5 секунд
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });

    // Подсветка активной секции при прокрутке
    const sections = document.querySelectorAll('.profile-section');
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.3
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                const correspondingLink = document.querySelector(`.nav-link[href="#${id}"]`);
                
                if (correspondingLink) {
                    navLinks.forEach(nl => nl.classList.remove('active'));
                    correspondingLink.classList.add('active');
                }
            }
        });
    }, observerOptions);

    sections.forEach(section => {
        observer.observe(section);
    });

    // Автоматическое заполнение duration и price при выборе типа абонемента
    const typeSelect = document.querySelector('select[name="type"]');
    const durationInput = document.getElementById('duration');
    const priceInput = document.getElementById('price');
    
    if (typeSelect && durationInput && priceInput) {
        typeSelect.addEventListener('change', function() {
            const value = this.value;
            switch(value) {
                case 'Базовый':
                    durationInput.value = '1';
                    priceInput.value = '2000';
                    break;
                case 'Стандарт':
                    durationInput.value = '3';
                    priceInput.value = '5000';
                    break;
                case 'Премиум':
                    durationInput.value = '6';
                    priceInput.value = '9000';
                    break;
                case 'VIP':
                    durationInput.value = '12';
                    priceInput.value = '15000';
                    break;
                default:
                    durationInput.value = '1';
                    priceInput.value = '2000';
            }
        });
    }
});

// Функция для проверки силы пароля
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (/[A-Z]/.test(password)) strength += 25;
    if (/[a-z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 25;
    
    return strength;
}
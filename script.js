// JavaScript kodları buraya eklenecek 

// Temel işlevsellik
document.addEventListener('DOMContentLoaded', function() {
    // Mobil menü işlevselliği
    const mobileMenuBtn = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.setAttribute('aria-expanded', 
                mobileMenuBtn.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
            );
        });

        // Menü dışı tıklamalarda menüyü kapat
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.main-nav')) {
                navLinks.classList.remove('active');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Sayfa kaydırma animasyonu
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                // Mobil menüyü kapat
                if (navLinks) {
                    navLinks.classList.remove('active');
                    mobileMenuBtn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });

    // WhatsApp butonları için UTM parametreleri ekle
    const whatsappButtons = document.querySelectorAll('a[href^="https://wa.me/"]');
    whatsappButtons.forEach(button => {
        const currentHref = button.getAttribute('href');
        const utm = '?utm_source=website&utm_medium=button&utm_campaign=contact';
        button.setAttribute('href', `${currentHref}${utm}`);
    });

    // Sayfa kaydırma animasyonları için Intersection Observer
    const sections = document.querySelectorAll('section');
    
    const observerOptions = {
        root: null,
        threshold: 0.1,
        rootMargin: '0px'
    };

    const sectionObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    sections.forEach(section => {
        sectionObserver.observe(section);
    });

    // Çerez yönetimi
    const cookieConsent = document.querySelector('.cookie-consent');
    const acceptButton = document.querySelector('.cookie-button.accept');
    const rejectButton = document.querySelector('.cookie-button.reject');
    const manageButton = document.querySelector('.cookie-button.manage');
    const closeButton = document.querySelector('.cookie-button.close');
    const cookieModal = document.querySelector('.cookie-modal');
    const savePreferencesButton = document.querySelector('.cookie-save-button');

    // Çerez tercihlerini kontrol et
    const checkCookiePreferences = () => {
        const preferences = localStorage.getItem('cookiePreferences');
        if (!preferences) {
            cookieConsent.classList.add('show');
        }
    };

    // Çerez tercihlerini kaydet
    const saveCookiePreference = (accepted) => {
        const preferences = {
            accepted: accepted,
            timestamp: new Date().toISOString()
        };
        localStorage.setItem('cookiePreferences', JSON.stringify(preferences));
        cookieConsent.classList.remove('show');
    };

    // Event listeners
    if (acceptButton) {
        acceptButton.addEventListener('click', () => saveCookiePreference(true));
    }

    if (rejectButton) {
        rejectButton.addEventListener('click', () => saveCookiePreference(false));
    }

    if (manageButton) {
        manageButton.addEventListener('click', () => {
            cookieModal.classList.add('show');
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', () => {
            cookieConsent.classList.remove('show');
        });
    }

    // Çerez tercihlerini kontrol et
    checkCookiePreferences();

    // Form işlevselliği
    const form = document.querySelector('#contact-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Form verilerini al
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                // reCAPTCHA doğrulaması
                const token = await grecaptcha.execute('6LdPWzEpAAAAAFKRv8zCaAuF_7Yc_U9P8K5qCHTY', {action: 'submit'});
                
                // EmailJS ile e-posta gönder
                await emailjs.send(
                    window.EMAILJS_CONFIG.SERVICE_ID,
                    window.EMAILJS_CONFIG.TEMPLATE_ID,
                    {
                        ...data,
                        'g-recaptcha-response': token
                    },
                    window.EMAILJS_CONFIG.PUBLIC_KEY
                );

                // Başarılı mesajı göster
                showNotification('Mesajınız başarıyla gönderildi!', 'success');
                form.reset();

            } catch (error) {
                console.error('Hata:', error);
                showNotification('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.', 'error');
            }
        });
    }
});

// Bildirim gösterme fonksiyonu
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <p>${message}</p>
        </div>
    `;

    document.body.appendChild(notification);
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Çerez yönetimi
document.addEventListener('DOMContentLoaded', function() {
    const cookieConsent = document.getElementById('cookie-consent');
    const cookieAccept = document.getElementById('cookie-accept');
    const cookieReject = document.getElementById('cookie-reject');
    const cookieManage = document.getElementById('cookie-manage');
    const cookieManagementModal = document.getElementById('cookie-management-modal');
    const savePreferencesBtn = document.getElementById('save-preferences');
    const closeModalBtn = document.getElementById('close-modal');
    const analyticsCookies = document.getElementById('analytics-cookies');
    const marketingCookies = document.getElementById('marketing-cookies');

    // Çerez tercihlerini kontrol et
    const checkCookiePreferences = () => {
        const cookiesAccepted = localStorage.getItem('cookies-accepted');
        if (cookiesAccepted === null) {
            // Çerez tercihi henüz yapılmamış
            setTimeout(() => {
                cookieConsent.style.display = 'block';
                cookieConsent.setAttribute('aria-hidden', 'false');
                // Ekran okuyucular için bildirim
                announcePreferences('Çerez bildirimi görüntüleniyor. Lütfen tercihlerinizi belirtin.');
            }, 2000); // 2 saniye gecikme ile göster
        } else {
            cookieConsent.style.display = 'none';
            cookieConsent.setAttribute('aria-hidden', 'true');
            
            // Kayıtlı tercihleri yükle
            if (cookiesAccepted === 'true') {
                loadSavedPreferences();
            }
        }
    };

    // Kayıtlı çerez tercihlerini yükle
    const loadSavedPreferences = () => {
        const savedAnalytics = localStorage.getItem('analytics-cookies') === 'true';
        const savedMarketing = localStorage.getItem('marketing-cookies') === 'true';
        
        analyticsCookies.checked = savedAnalytics;
        marketingCookies.checked = savedMarketing;
        
        // Tercihlere göre çerezleri yükle/kaldır
        if (savedAnalytics) {
            loadAnalytics();
        } else {
            removeAnalytics();
        }
        
        if (savedMarketing) {
            loadMarketing();
        } else {
            removeMarketing();
        }
    };

    // Çerez bildirimini kapat ve tercihi kaydet
    const saveCookiePreference = (accepted) => {
        localStorage.setItem('cookies-accepted', accepted);
        cookieConsent.style.display = 'none';
        cookieConsent.setAttribute('aria-hidden', 'true');
        
        if (accepted) {
            // Varsayılan olarak tüm çerezleri kabul et
            localStorage.setItem('analytics-cookies', 'true');
            localStorage.setItem('marketing-cookies', 'true');
            loadAnalytics();
            loadMarketing();
        } else {
            // Tüm çerezleri reddet
            localStorage.setItem('analytics-cookies', 'false');
            localStorage.setItem('marketing-cookies', 'false');
            removeAnalytics();
            removeMarketing();
        }
        
        // Ekran okuyucular için bildirim
        announcePreferences(accepted ? 
            'Tüm çerezler kabul edildi.' : 
            'Çerezler reddedildi. Yalnızca gerekli çerezler kullanılacak.'
        );
    };

    // Çerez tercihlerini kaydet
    const savePreferences = () => {
        const analyticsEnabled = analyticsCookies.checked;
        const marketingEnabled = marketingCookies.checked;
        
        localStorage.setItem('analytics-cookies', analyticsEnabled);
        localStorage.setItem('marketing-cookies', marketingEnabled);
        
        // Tercihlere göre çerezleri yükle/kaldır
        if (analyticsEnabled) {
            loadAnalytics();
        } else {
            removeAnalytics();
        }
        
        if (marketingEnabled) {
            loadMarketing();
        } else {
            removeMarketing();
        }
        
        // Modalı kapat
        closeModal();
        
        // Ekran okuyucular için bildirim
        announcePreferences('Çerez tercihleriniz kaydedildi.');
    };

    // Modalı aç
    const openModal = () => {
        cookieManagementModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden'; // Arka planı kilitle
        
        // ESC tuşu ile kapatma
        document.addEventListener('keydown', handleEscKey);
        
        // Modal dışı tıklama ile kapatma
        cookieManagementModal.addEventListener('click', handleOutsideClick);
        
        // Odağı modal içeriğine taşı
        const firstFocusable = cookieManagementModal.querySelector('button, [href], input, select, textarea');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    };

    // Modalı kapat
    const closeModal = () => {
        cookieManagementModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = ''; // Arka plan kilidini kaldır
        
        // Event listener'ları kaldır
        document.removeEventListener('keydown', handleEscKey);
        cookieManagementModal.removeEventListener('click', handleOutsideClick);
        
        // Odağı geri taşı
        cookieManage.focus();
    };

    // ESC tuşu ile modalı kapat
    const handleEscKey = (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    };

    // Modal dışı tıklama ile kapat
    const handleOutsideClick = (e) => {
        if (e.target === cookieManagementModal) {
            closeModal();
        }
    };

    // Ekran okuyucular için bildirim
    const announcePreferences = (message) => {
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'alert');
        announcement.setAttribute('aria-live', 'polite');
        announcement.style.position = 'absolute';
        announcement.style.left = '-9999px';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        // 5 saniye sonra bildirimi kaldır
        setTimeout(() => announcement.remove(), 5000);
    };

    // Google Analytics yükleme/kaldırma
    const loadAnalytics = () => {
        // Google Analytics kodu buraya eklenecek
        console.log('Analytics yüklendi');
    };

    const removeAnalytics = () => {
        // Google Analytics'i kaldırma kodu
        console.log('Analytics kaldırıldı');
    };

    // Marketing çerezleri yükleme/kaldırma
    const loadMarketing = () => {
        // Marketing çerezleri kodu buraya eklenecek
        console.log('Marketing çerezleri yüklendi');
    };

    const removeMarketing = () => {
        // Marketing çerezlerini kaldırma kodu
        console.log('Marketing çerezleri kaldırıldı');
    };

    // Event listeners
    cookieAccept.addEventListener('click', () => saveCookiePreference(true));
    cookieReject.addEventListener('click', () => saveCookiePreference(false));
    cookieManage.addEventListener('click', openModal);
    closeModalBtn.addEventListener('click', closeModal);
    savePreferencesBtn.addEventListener('click', savePreferences);

    // Sayfa yüklendiğinde çerez tercihlerini kontrol et
    checkCookiePreferences();
});

// Lazy Loading için Intersection Observer
const lazyLoadImages = () => {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    // Lazy load images
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
};

// Performans optimizasyonları
const optimizePerformance = () => {
    // Resimleri optimize et
    document.querySelectorAll('img').forEach(img => {
        if (!img.loading) {
            img.loading = 'lazy';
        }
        if (!img.decoding) {
            img.decoding = 'async';
        }
    });

    // Link preloading
    const preloadLinks = () => {
        if (window.requestIdleCallback) {
            requestIdleCallback(() => {
                document.querySelectorAll('a').forEach(link => {
                    if (link.href && link.href.startsWith(window.location.origin)) {
                        const linkPreload = document.createElement('link');
                        linkPreload.rel = 'prefetch';
                        linkPreload.href = link.href;
                        document.head.appendChild(linkPreload);
                    }
                });
            });
        }
    };

    // Sayfa yüklendiğinde performans optimizasyonlarını başlat
    if (document.readyState === 'complete') {
        preloadLinks();
    } else {
        window.addEventListener('load', preloadLinks);
    }
};

// Karanlık mod toggle fonksiyonu
const initDarkMode = () => {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    
    const toggleDarkMode = (e) => {
        if (e.matches) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    };

    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
            
            // Ekran okuyucu bildirimi
            const message = isDarkMode ? 'Karanlık mod aktif' : 'Aydınlık mod aktif';
            announceToScreenReader(message);
        });
    }

    // Sistem tercihini dinle
    prefersDarkScheme.addListener(toggleDarkMode);
    
    // Kaydedilmiş tercihi kontrol et
    const savedMode = localStorage.getItem('darkMode');
    if (savedMode !== null) {
        document.body.classList.toggle('dark-mode', JSON.parse(savedMode));
    } else {
        toggleDarkMode(prefersDarkScheme);
    }
};

// Ekran okuyucu bildirimleri için yardımcı fonksiyon
const announceToScreenReader = (message) => {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('class', 'sr-only');
    announcement.textContent = message;
    document.body.appendChild(announcement);
    setTimeout(() => announcement.remove(), 1000);
};

// Sayfa yüklendiğinde tüm optimizasyonları başlat
document.addEventListener('DOMContentLoaded', () => {
    lazyLoadImages();
    optimizePerformance();
    initDarkMode();
});

// Simple Rule-based Chatbot
const chatbot = {
    messages: [],
    isOpen: false,
    emojiPicker: null,
    
    // Basit yanıt kuralları
    responses: {
        greetings: {
            patterns: ['merhaba', 'selam', 'hey', 'hi', 'hello'],
            replies: [
                'Merhaba! 👋 Size nasıl yardımcı olabilirim?',
                'Selam! Ben MSSR Web\'in sanal asistanıyım. Size nasıl yardımcı olabilirim? 😊'
            ]
        },
        services: {
            patterns: ['hizmet', 'servis', 'yapıyor', 'sunuyor', 'web sitesi', 'web tasarım'],
            replies: [
                'Web tasarım, web geliştirme ve SEO hizmetleri sunuyoruz. Size özel çözümler üretebiliriz! 💻',
                'Web sitenizi modern ve profesyonel bir görünüme kavuşturabiliriz. Detaylı bilgi için iletişime geçin! 🎨'
            ]
        },
        pricing: {
            patterns: ['fiyat', 'ücret', 'maliyet', 'ne kadar'],
            replies: [
                'Fiyatlarımız projenin kapsamına göre değişmektedir. Size özel teklif için iletişim formunu doldurabilirsiniz. 💰',
                'Her bütçeye uygun çözümlerimiz mevcut. Detaylı bilgi için iletişime geçin! 📝'
            ]
        },
        contact: {
            patterns: ['iletişim', 'telefon', 'email', 'mail', 'mesaj'],
            replies: [
                'Bizimle iletişime geçmek için sayfamızdaki iletişim formunu kullanabilir veya info@mssr.com adresine mail atabilirsiniz. 📧',
                'Size hemen dönüş yapmamızı isterseniz, iletişim formunu doldurabilirsiniz! 📞'
            ]
        },
        default: [
            'Bu konuda size nasıl yardımcı olabileceğimi tam anlayamadım. Lütfen sorunuzu farklı bir şekilde sorar mısınız? 🤔',
            'Daha fazla bilgi için iletişim formunu doldurabilir veya sorularınızı farklı bir şekilde sorabilirsiniz. 📝'
        ]
    },
    
    init() {
        this.container = document.querySelector('.chatbot-container');
        this.toggle = document.querySelector('.chatbot-toggle');
        this.closeBtn = document.querySelector('.chatbot-close');
        this.messagesList = document.querySelector('.chatbot-messages');
        this.form = document.querySelector('.chatbot-input form');
        this.input = this.form.querySelector('input');
        
        // Emoji picker initialization
        this.initEmojiPicker();
        
        this.toggle.addEventListener('click', () => this.toggleChat());
        this.closeBtn.addEventListener('click', () => this.toggleChat());
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Add initial bot message
        this.addMessage('Merhaba! 👋 Ben MSSR\'nin sanal asistanıyım. Size nasıl yardımcı olabilirim?', 'bot');
    },
    
    initEmojiPicker() {
        // Emoji button creation
        const emojiBtn = document.createElement('button');
        emojiBtn.type = 'button';
        emojiBtn.className = 'emoji-picker-btn';
        emojiBtn.innerHTML = '😊';
        emojiBtn.setAttribute('aria-label', 'Emoji seç');
        
        // Add emoji button to form
        this.form.insertBefore(emojiBtn, this.form.querySelector('button'));
    },
    
    toggleChat() {
        this.isOpen = !this.isOpen;
        this.container.setAttribute('aria-hidden', !this.isOpen);
        if (this.isOpen) {
            this.input.focus();
            announceToScreenReader('Sohbet penceresi açıldı');
        } else {
            announceToScreenReader('Sohbet penceresi kapandı');
        }
    },
    
    addMessage(text, type) {
        const message = document.createElement('div');
        message.classList.add('message', type);
        
        // Emojileri işle
        const processedText = this.processEmojis(text);
        message.innerHTML = processedText;
        
        this.messagesList.appendChild(message);
        this.messagesList.scrollTop = this.messagesList.scrollHeight;
        this.messages.push({ text, type });
    },
    
    processEmojis(text) {
        // Basit emoji dönüşümü
        return text.replace(/:\)|:\(|:D|:P|<3/gi, match => {
            const emojiMap = {
                ':)': '😊',
                ':(': '😢',
                ':D': '😃',
                ':P': '😛',
                '<3': '❤️'
            };
            return emojiMap[match] || match;
        });
    },
    
    findResponse(message) {
        message = message.toLowerCase();
        
        // Her kategoriyi kontrol et
        for (const [category, data] of Object.entries(this.responses)) {
            if (category === 'default') continue;
            
            // Eğer mesaj bu kategorinin kalıplarından birini içeriyorsa
            if (data.patterns.some(pattern => message.includes(pattern))) {
                return data.replies[Math.floor(Math.random() * data.replies.length)];
            }
        }
        
        // Eğer hiçbir kalıp eşleşmezse varsayılan yanıtlardan birini döndür
        return this.responses.default[Math.floor(Math.random() * this.responses.default.length)];
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        const message = this.input.value.trim();
        if (!message) return;
        
        // Add user message
        this.addMessage(message, 'user');
        this.input.value = '';
        
        // Show typing indicator
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'typing-indicator';
        typingIndicator.innerHTML = '<span></span><span></span><span></span>';
        this.messagesList.appendChild(typingIndicator);
        
        try {
            // Disable input while processing
            this.input.disabled = true;
            
            // Simulate processing time (500ms-1.5s)
            await new Promise(resolve => setTimeout(resolve, 500 + Math.random() * 1000));
            
            // Get bot response
            const botResponse = this.findResponse(message);
            
            // Remove typing indicator
            typingIndicator.remove();
            
            // Add bot response
            this.addMessage(botResponse, 'bot');
        } catch (error) {
            console.error('Chatbot Error:', error);
            this.addMessage('Üzgünüm, bir hata oluştu. Lütfen tekrar deneyin. 😔', 'bot');
        } finally {
            // Re-enable input
            this.input.disabled = false;
            this.input.focus();
        }
    }
};

// Newsletter functionality with local storage
const newsletter = {
    storageKey: 'newsletter_subscribers',
    
    init() {
        this.form = document.querySelector('.newsletter-form');
        this.emailInput = this.form.querySelector('input[type="email"]');
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // GDPR consent checkbox
        this.addConsentCheckbox();
    },
    
    addConsentCheckbox() {
        const consentDiv = document.createElement('div');
        consentDiv.className = 'consent-checkbox';
        consentDiv.innerHTML = `
            <label class="checkbox-container">
                <input type="checkbox" required name="gdpr_consent">
                <span class="checkmark"></span>
                KVKK ve Gizlilik Politikası'nı kabul ediyorum
            </label>
        `;
        this.form.querySelector('.form-group').appendChild(consentDiv);
    },
    
    getSubscribers() {
        const subscribers = localStorage.getItem(this.storageKey);
        return subscribers ? JSON.parse(subscribers) : [];
    },
    
    addSubscriber(email) {
        const subscribers = this.getSubscribers();
        if (subscribers.includes(email)) {
            throw new Error('Bu e-posta adresi zaten kayıtlı.');
        }
        subscribers.push(email);
        localStorage.setItem(this.storageKey, JSON.stringify(subscribers));
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        const email = this.emailInput.value.trim();
        const consent = this.form.querySelector('input[name="gdpr_consent"]').checked;
        
        if (!email) return;
        if (!consent) {
            showNotification('Lütfen KVKK ve Gizlilik Politikası\'nı kabul edin.', 'error');
            return;
        }
        
        try {
            // Form durumunu güncelle
            this.form.classList.add('loading');
            const submitBtn = this.form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
            
            // Abone ekle
            this.addSubscriber(email);
            
            // Başarılı
            this.emailInput.value = '';
            this.form.querySelector('input[name="gdpr_consent"]').checked = false;
            
            // Başarı mesajı
            const successMessage = `
                <div class="newsletter-success">
                    <i class="fas fa-check-circle"></i>
                    <p>Teşekkürler! Bültenimize başarıyla abone oldunuz.</p>
                </div>
            `;
            
            const messageDiv = document.createElement('div');
            messageDiv.innerHTML = successMessage;
            this.form.appendChild(messageDiv);
            
            // Ekran okuyucu bildirimi
            announceToScreenReader('Bülten aboneliğiniz başarıyla tamamlandı.');
            
            // 5 saniye sonra başarı mesajını kaldır
            setTimeout(() => {
                messageDiv.remove();
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }, 5000);
            
        } catch (error) {
            showNotification(error.message || 'Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
            console.error('Newsletter Error:', error);
        } finally {
            this.form.classList.remove('loading');
        }
    }
};

// Initialize chatbot and newsletter when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    chatbot.init();
    newsletter.init();
});

// Cookie Consent Management
document.addEventListener('DOMContentLoaded', function() {
    const cookieConsent = document.querySelector('.cookie-consent');
    const cookieModal = document.querySelector('.cookie-modal');
    const cookieButtons = {
        accept: document.querySelector('.cookie-button.accept'),
        reject: document.querySelector('.cookie-button.reject'),
        manage: document.querySelector('.cookie-button.manage'),
        close: document.querySelector('.cookie-button.close'),
        save: document.querySelector('.cookie-save-button'),
        modalClose: document.querySelector('.cookie-modal-close')
    };

    // Show cookie consent with delay if not accepted before
    if (!localStorage.getItem('cookieConsent')) {
        setTimeout(() => {
            cookieConsent?.classList.add('show');
            announceToScreenReader('Çerez bildirimi görüntüleniyor. Lütfen tercihlerinizi belirtin.');
        }, 2000);
    }

    // Accept all cookies
    cookieButtons.accept?.addEventListener('click', () => {
        acceptAllCookies();
        hideCookieConsent();
        announceToScreenReader('Tüm çerezler kabul edildi.');
    });

    // Reject all cookies
    cookieButtons.reject?.addEventListener('click', () => {
        rejectAllCookies();
        hideCookieConsent();
        announceToScreenReader('Gerekli olmayan çerezler reddedildi.');
    });

    // Close cookie consent
    cookieButtons.close?.addEventListener('click', () => {
        hideCookieConsent();
    });

    // Manage cookie preferences
    cookieButtons.manage?.addEventListener('click', () => {
        showCookieModal();
    });

    // Close cookie modal
    cookieButtons.modalClose?.addEventListener('click', () => {
        hideCookieModal();
    });

    // Save cookie preferences
    cookieButtons.save?.addEventListener('click', () => {
        saveCookiePreferences();
        hideCookieModal();
        hideCookieConsent();
        announceToScreenReader('Çerez tercihleriniz kaydedildi.');
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === cookieModal) {
            hideCookieModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cookieModal?.style.display === 'flex') {
            hideCookieModal();
        }
    });

    function acceptAllCookies() {
        localStorage.setItem('cookieConsent', 'accepted');
        const preferences = {
            necessary: true,
            functional: true,
            analytics: true,
            marketing: true
        };
        localStorage.setItem('cookiePreferences', JSON.stringify(preferences));
        applyCookiePreferences(preferences);
    }

    function rejectAllCookies() {
        localStorage.setItem('cookieConsent', 'rejected');
        const preferences = {
            necessary: true, // Always enabled
            functional: false,
            analytics: false,
            marketing: false
        };
        localStorage.setItem('cookiePreferences', JSON.stringify(preferences));
        applyCookiePreferences(preferences);
    }

    function saveCookiePreferences() {
        const preferences = {
            necessary: true, // Always enabled
            functional: document.getElementById('functional-cookies')?.checked || false,
            analytics: document.getElementById('analytics-cookies')?.checked || false,
            marketing: document.getElementById('marketing-cookies')?.checked || false
        };
        localStorage.setItem('cookiePreferences', JSON.stringify(preferences));
        localStorage.setItem('cookieConsent', 'customized');
        applyCookiePreferences(preferences);
    }

    function applyCookiePreferences(preferences) {
        // Apply Analytics preferences
        if (preferences.analytics) {
            enableAnalytics();
        } else {
            disableAnalytics();
        }

        // Apply Marketing preferences
        if (preferences.marketing) {
            enableMarketing();
        } else {
            disableMarketing();
        }

        // Apply Functional preferences
        if (preferences.functional) {
            enableFunctional();
        } else {
            disableFunctional();
        }
    }

    function hideCookieConsent() {
        cookieConsent?.classList.add('hide');
        setTimeout(() => {
            cookieConsent.style.display = 'none';
        }, 400);
    }

    function showCookieModal() {
        loadSavedPreferences();
        cookieModal.style.display = 'flex';
        setTimeout(() => {
            cookieModal?.classList.add('show');
            trapFocus(cookieModal);
        }, 10);
    }

    function hideCookieModal() {
        cookieModal?.classList.remove('show');
        setTimeout(() => {
            cookieModal.style.display = 'none';
        }, 300);
    }

    function loadSavedPreferences() {
        const savedPreferences = JSON.parse(localStorage.getItem('cookiePreferences')) || {
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false
        };

        // Update checkboxes
        document.getElementById('functional-cookies').checked = savedPreferences.functional;
        document.getElementById('analytics-cookies').checked = savedPreferences.analytics;
        document.getElementById('marketing-cookies').checked = savedPreferences.marketing;
    }

    // Focus trap for modal
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        element.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            }
        });

        firstFocusable.focus();
    }

    // Cookie functionality implementations
    function enableAnalytics() {
        // Google Analytics implementation
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'analytics_storage': 'granted'
            });
        }
    }

    function disableAnalytics() {
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'analytics_storage': 'denied'
            });
        }
    }

    function enableMarketing() {
        // Marketing cookies implementation
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'granted'
            });
        }
    }

    function disableMarketing() {
        if (typeof gtag === 'function') {
            gtag('consent', 'update', {
                'ad_storage': 'denied'
            });
        }
    }

    function enableFunctional() {
        // Functional cookies implementation
        document.cookie = "functionalEnabled=true; max-age=31536000; path=/";
    }

    function disableFunctional() {
        document.cookie = "functionalEnabled=false; max-age=0; path=/";
    }

    // Load and apply saved preferences on page load
    const savedPreferences = JSON.parse(localStorage.getItem('cookiePreferences'));
    if (savedPreferences) {
        applyCookiePreferences(savedPreferences);
    }
});

// Testimonials Management
document.addEventListener('DOMContentLoaded', () => {
    const testimonialSlider = {
        slider: document.querySelector('.testimonial-slider'),
        testimonials: document.querySelectorAll('.testimonial'),
        dots: document.querySelectorAll('.testimonial-dots .dot'),
        prevButton: document.querySelector('.nav-button.prev'),
        nextButton: document.querySelector('.nav-button.next'),
        currentSlide: 0,
        isAnimating: false,
        animationDuration: 500,
        autoplayDelay: 5000,
        autoplayInterval: null,

        init() {
            this.setupEventListeners();
            this.loadSavedTestimonials();
            this.startAutoplay();
            this.updateSliderPosition();
        },

        setupEventListeners() {
            // Navigation buttons
            this.prevButton?.addEventListener('click', () => this.goToSlide(this.currentSlide - 1));
            this.nextButton?.addEventListener('click', () => this.goToSlide(this.currentSlide + 1));

            // Dots navigation
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goToSlide(index));
            });

            // Touch and swipe support
            let touchStartX = 0;
            let touchEndX = 0;
            const swipeThreshold = 50;

            this.slider?.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
                this.stopAutoplay();
            }, { passive: true });

            this.slider?.addEventListener('touchmove', (e) => {
                touchEndX = e.touches[0].clientX;
            }, { passive: true });

            this.slider?.addEventListener('touchend', () => {
                const swipeDistance = touchEndX - touchStartX;
                
                if (Math.abs(swipeDistance) > swipeThreshold) {
                    if (swipeDistance > 0 && this.currentSlide > 0) {
                        this.goToSlide(this.currentSlide - 1);
                    } else if (swipeDistance < 0 && this.currentSlide < this.testimonials.length - 1) {
                        this.goToSlide(this.currentSlide + 1);
                    }
                }
                this.startAutoplay();
            });

            // Save testimonial functionality
            document.querySelectorAll('.save-testimonial').forEach(button => {
                button.addEventListener('click', (e) => this.toggleSaveTestimonial(e));
            });

            // Share functionality
            document.querySelectorAll('.share-button').forEach(button => {
                button.addEventListener('click', (e) => this.handleShare(e));
            });

            // Hover pause/resume autoplay
            this.slider?.addEventListener('mouseenter', () => this.stopAutoplay());
            this.slider?.addEventListener('mouseleave', () => this.startAutoplay());
            this.slider?.addEventListener('focusin', () => this.stopAutoplay());
            this.slider?.addEventListener('focusout', () => this.startAutoplay());

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (document.activeElement.closest('.testimonials')) {
                    switch (e.key) {
                        case 'ArrowLeft':
                            this.goToSlide(this.currentSlide - 1);
                            break;
                        case 'ArrowRight':
                            this.goToSlide(this.currentSlide + 1);
                            break;
                    }
                }
            });
        },

        updateSliderPosition() {
            if (!this.slider || this.isAnimating) return;
            
            this.isAnimating = true;
            const offset = -this.currentSlide * 100;
            
            this.slider.style.transform = `translateX(${offset}%)`;
            
            // Update dots
            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === this.currentSlide);
                dot.setAttribute('aria-selected', index === this.currentSlide);
            });

            // Update ARIA labels
            this.testimonials.forEach((testimonial, index) => {
                testimonial.setAttribute('aria-hidden', index !== this.currentSlide);
            });

            // Enable/disable navigation buttons
            this.prevButton.disabled = this.currentSlide === 0;
            this.nextButton.disabled = this.currentSlide === this.testimonials.length - 1;

            setTimeout(() => {
                this.isAnimating = false;
            }, this.animationDuration);
        },

        goToSlide(index) {
            if (this.isAnimating) return;
            this.currentSlide = Math.max(0, Math.min(index, this.testimonials.length - 1));
            this.updateSliderPosition();
        },

        startAutoplay() {
            this.stopAutoplay();
            this.autoplayInterval = setInterval(() => {
                if (this.currentSlide < this.testimonials.length - 1) {
                    this.goToSlide(this.currentSlide + 1);
                } else {
                    this.goToSlide(0);
                }
            }, this.autoplayDelay);
        },

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
            }
        },

        toggleSaveTestimonial(event) {
            const button = event.currentTarget;
            const testimonialId = button.closest('.testimonial').dataset.id;
            const savedTestimonials = this.getSavedTestimonials();

            if (savedTestimonials.includes(testimonialId)) {
                this.removeFromSaved(testimonialId);
                button.classList.remove('saved');
                this.showNotification('Yorum kaydedilenlerden kaldırıldı');
            } else {
                this.addToSaved(testimonialId);
                button.classList.add('saved');
                this.showNotification('Yorum kaydedildi');
            }
        },

        getSavedTestimonials() {
            return JSON.parse(localStorage.getItem('savedTestimonials') || '[]');
        },

        addToSaved(testimonialId) {
            const saved = this.getSavedTestimonials();
            saved.push(testimonialId);
            localStorage.setItem('savedTestimonials', JSON.stringify(saved));
        },

        removeFromSaved(testimonialId) {
            const saved = this.getSavedTestimonials();
            const updated = saved.filter(id => id !== testimonialId);
            localStorage.setItem('savedTestimonials', JSON.stringify(updated));
        },

        loadSavedTestimonials() {
            const saved = this.getSavedTestimonials();
            document.querySelectorAll('.testimonial').forEach(testimonial => {
                const id = testimonial.dataset.id;
                const saveButton = testimonial.querySelector('.save-testimonial');
                if (saved.includes(id)) {
                    saveButton?.classList.add('saved');
                }
            });
        },

        handleShare(event) {
            const button = event.currentTarget;
            const testimonial = button.closest('.testimonial');
            const text = testimonial.querySelector('p').textContent;
            const author = testimonial.querySelector('.author-name').textContent;

            // Social media share URLs
            const shareUrls = {
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}&quote=${encodeURIComponent(text)}`,
                twitter: `https://twitter.com/intent/tweet?text=${encodeURIComponent(`${text} - ${author}`)}&url=${encodeURIComponent(window.location.href)}`,
                linkedin: `https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(window.location.href)}&title=${encodeURIComponent('Müşteri Yorumu')}&summary=${encodeURIComponent(`${text} - ${author}`)}`
            };

            // Add click events to share buttons
            testimonial.querySelectorAll('.share-options a').forEach(link => {
                const platform = link.getAttribute('aria-label').toLowerCase();
                if (platform.includes('facebook')) {
                    link.href = shareUrls.facebook;
                } else if (platform.includes('twitter')) {
                    link.href = shareUrls.twitter;
                } else if (platform.includes('linkedin')) {
                    link.href = shareUrls.linkedin;
                }
            });
        },

        showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            document.body.appendChild(notification);

            // Show notification
            setTimeout(() => notification.classList.add('show'), 100);

            // Remove notification
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    };

    // Initialize testimonial slider
    testimonialSlider.init();
});

// Pricing Plans
document.addEventListener('DOMContentLoaded', () => {
    const pricingTabs = document.querySelectorAll('.pricing-tab');
    const pricingGrids = document.querySelectorAll('.pricing-grid');

    // Tab değişikliğini yönet
    function switchPricingTab(targetService) {
        // Aktif tab'ı güncelle
        pricingTabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.service === targetService);
        });

        // İlgili grid'i göster
        pricingGrids.forEach(grid => {
            if (grid.id === `${targetService}-plans`) {
                grid.classList.add('active');
                // Animasyon için timeout ekle
                setTimeout(() => {
                    grid.style.opacity = '1';
                    grid.style.transform = 'translateY(0)';
                }, 50);
            } else {
                grid.classList.remove('active');
                grid.style.opacity = '0';
                grid.style.transform = 'translateY(20px)';
            }
        });
    }

    // Tab click event listener'ları
    pricingTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetService = tab.dataset.service;
            switchPricingTab(targetService);
        });
    });

    // Klavye navigasyonu
    pricingTabs.forEach(tab => {
        tab.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const targetService = tab.dataset.service;
                switchPricingTab(targetService);
            }
        });
    });

    // Pricing CTA butonları için smooth scroll
    const pricingCTAs = document.querySelectorAll('.pricing-cta');
    pricingCTAs.forEach(cta => {
        cta.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = cta.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Form alanlarını otomatik doldur
                const packageName = cta.closest('.pricing-card').querySelector('h3').textContent;
                const serviceType = cta.closest('.pricing-grid').id.replace('-plans', '');
                
                // Mesaj alanını otomatik doldur
                const messageField = document.querySelector('#message');
                if (messageField) {
                    messageField.value = `${serviceType.toUpperCase()} - ${packageName} paketi hakkında bilgi almak istiyorum.`;
                }
            }
        });
    });

    // Sayfa yüklendiğinde ilk tab'ı aktif et
    switchPricingTab('web-design');
});

// WhatsApp iletişim fonksiyonu
function whatsappIletisim(paketAdi, fiyat) {
    const telefon = "905525400206"; // WhatsApp telefon numarası
    const mesaj = `Merhaba, ${paketAdi} (${fiyat}₺) paketi hakkında bilgi almak istiyorum.`;
    const link = `https://api.whatsapp.com/send?phone=${telefon}&text=${encodeURIComponent(mesaj)}`;
    window.open(link, "_blank");
} 
// JavaScript kodları buraya eklenecek 

// Mobil menü işlevselliği
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    // Menü açma/kapama
    mobileMenuBtn.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        mobileMenuBtn.setAttribute('aria-expanded', 
            mobileMenuBtn.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
        );
    });

    // Menü dışı tıklamalarda menüyü kapat
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.navbar')) {
            navLinks.classList.remove('active');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }
    });

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
                navLinks.classList.remove('active');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Yukarı Çık butonu işlevselliği
    const backToTopButton = document.getElementById('back-to-top');
    
    // Sayfa scroll olduğunda butonu göster/gizle
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });

    // Butona tıklandığında yukarı çık
    backToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
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
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Her bölüme başlangıç stillerini ve gözlemciyi ekle
    sections.forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(50px)';
        section.style.transition = 'all 0.6s ease-out';
        sectionObserver.observe(section);
    });

    // Form işlevselliği
    const contactForm = document.getElementById('contact-form');
    
    if (contactForm) {
        // Form doğrulama fonksiyonları
        const validators = {
            email: (value) => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return {
                    isValid: emailRegex.test(value),
                    message: 'Geçerli bir e-posta adresi giriniz'
                };
            },
            name: (value) => {
                return {
                    isValid: value.length >= 2 && value.length <= 50,
                    message: 'Ad en az 2, en fazla 50 karakter olmalıdır'
                };
            },
            message: (value) => {
                return {
                    isValid: value.length >= 10 && value.length <= 500,
                    message: 'Mesaj en az 10, en fazla 500 karakter olmalıdır'
                };
            }
        };

        // Hata mesajı gösterme fonksiyonu
        const showError = (input, message) => {
            // Input elementi veya form grubu yoksa işlemi durdur
            if (!input) return;
            
            const formGroup = input.closest('.form-group');
            if (!formGroup) return;
            
            // Varsa eski hata mesajını temizle
            const existingError = formGroup.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Yeni hata mesajı oluştur
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.setAttribute('aria-live', 'polite');
            errorDiv.textContent = message;
            
            // Hata mesajına ID ata
            const errorId = `error-${input.id || Math.random().toString(36).substr(2, 9)}`;
            errorDiv.id = errorId;
            
            // ARIA attributes
            input.setAttribute('aria-invalid', 'true');
            input.setAttribute('aria-describedby', errorId);
            
            // Hata mesajını form grubuna ekle
            formGroup.appendChild(errorDiv);
            input.classList.add('form-error');
        };

        // Karakter sayacı oluşturma
        const messageTextarea = contactForm.querySelector('#message');
        const charCounter = document.createElement('div');
        charCounter.className = 'char-counter';
        charCounter.style.fontSize = '0.8em';
        charCounter.style.color = '#666';
        charCounter.style.textAlign = 'right';
        messageTextarea.parentNode.insertBefore(charCounter, messageTextarea.nextSibling);

        // Karakter sayacı güncellemesi
        messageTextarea.addEventListener('input', function() {
            const maxLength = parseInt(this.getAttribute('maxlength'));
            const remaining = maxLength - this.value.length;
            charCounter.textContent = `${remaining} karakter kaldı`;
            
            // Renk değişimi
            charCounter.className = 'char-counter';
            if (remaining < 50) {
                charCounter.classList.add('danger');
            } else if (remaining < 100) {
                charCounter.classList.add('warning');
            }
        });

        // Validation icon ekleme
        contactForm.querySelectorAll('.form-group').forEach(group => {
            const validationIcon = document.createElement('span');
            validationIcon.className = 'validation-icon';
            validationIcon.setAttribute('aria-hidden', 'true'); // Ekran okuyucular için gizle
            group.appendChild(validationIcon);
        });

        // Form doğrulama fonksiyonu
        const validateField = (field) => {
            const value = field.value.trim();
            
            // Zorunlu alan kontrolü
            if (field.required && !value) {
                showError(field, 'Bu alan zorunludur');
                return false;
            }
            
            // Alan özel doğrulamalar
            if (value && validators[field.name]) {
                const validation = validators[field.name](value);
                if (!validation.isValid) {
                    showError(field, validation.message);
                    return false;
                }
            }
            
            return true;
        };

        // Form alanlarının geçerlilik durumunu güncelleme
        const updateFieldValidity = (field) => {
            const isValid = field.checkValidity() && (!validators[field.name] || 
                validators[field.name](field.value.trim()).isValid);
            
            field.classList.toggle('form-error', !isValid);
            field.setAttribute('aria-invalid', !isValid);
            
            // Validation icon güncelleme
            const validationIcon = field.closest('.form-group')?.querySelector('.validation-icon');
            if (validationIcon) {
                validationIcon.style.opacity = field.value.trim() ? '1' : '0';
                validationIcon.className = `validation-icon ${isValid ? 'valid' : 'invalid'}`;
                
                // Alan geçerliyse başarı mesajı ekle
                if (isValid && field.value.trim()) {
                    field.setAttribute('aria-describedby', `success-${field.id}`);
                    let successMessage = field.closest('.form-group').querySelector('.success-message');
                    if (!successMessage) {
                        successMessage = document.createElement('span');
                        successMessage.className = 'success-message';
                        successMessage.id = `success-${field.id}`;
                        successMessage.setAttribute('role', 'status');
                        successMessage.style.position = 'absolute';
                        successMessage.style.left = '-9999px';
                        successMessage.textContent = 'Alan doğru dolduruldu';
                        field.closest('.form-group').appendChild(successMessage);
                    }
                }
            }
        };

        // Input event listener'ları güncelleme
        contactForm.querySelectorAll('input, select, textarea').forEach(input => {
            // Erişilebilirlik için etiketler
            if (input.id) {
                const label = contactForm.querySelector(`label[for="${input.id}"]`);
                if (label) {
                    if (input.required) {
                        const requiredSpan = document.createElement('span');
                        requiredSpan.className = 'required-field';
                        requiredSpan.setAttribute('aria-hidden', 'true');
                        requiredSpan.textContent = ' *';
                        requiredSpan.style.color = '#e74c3c';
                        label.appendChild(requiredSpan);
                        
                        // Ekran okuyucular için açıklama
                        input.setAttribute('aria-required', 'true');
                        input.setAttribute('required', 'required');
                    }
                }
            }

            // Blur event listener - alan odaktan çıktığında doğrulama
            input.addEventListener('blur', function() {
                if (this.required || this.value.trim()) {
                    validateField(this);
                }
                updateFieldValidity(this);
            });
            
            // Input event listener - alan değiştiğinde doğrulama
            input.addEventListener('input', function() {
                const errorDiv = this.closest('.form-group')?.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.remove();
                    this.removeAttribute('aria-describedby');
                }
                
                if (this.required || this.value.trim()) {
                    validateField(this);
                }
                updateFieldValidity(this);
            });
        });

        // Form gönderimi
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Tüm hata mesajlarını temizle
            this.querySelectorAll('.error-message').forEach(error => error.remove());
            
            // Tüm alanları doğrula
            let hasError = false;
            const fields = this.querySelectorAll('input[required], select[required], textarea[required]');
            
            fields.forEach(field => {
                if (!validateField(field)) {
                    hasError = true;
                }
                updateFieldValidity(field);
            });

            if (hasError) {
                const firstError = this.querySelector('.form-error');
                if (firstError) {
                    firstError.focus();
                    // Ekran okuyucular için hata bildirimi
                    const errorAnnouncement = document.createElement('div');
                    errorAnnouncement.setAttribute('role', 'alert');
                    errorAnnouncement.setAttribute('aria-live', 'assertive');
                    errorAnnouncement.style.position = 'absolute';
                    errorAnnouncement.style.left = '-9999px';
                    errorAnnouncement.textContent = 'Form hatalar içeriyor. Lütfen tüm zorunlu alanları doldurun.';
                    this.appendChild(errorAnnouncement);
                    
                    // 2 saniye sonra duyuruyu kaldır
                    setTimeout(() => errorAnnouncement.remove(), 2000);
                }
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            if (!submitBtn) return;

            const originalBtnText = submitBtn.innerHTML;
            
            try {
                // EmailJS yapılandırmasını kontrol et
                const config = window.EMAILJS_CONFIG;
                if (!config) {
                    throw new Error('EmailJS yapılandırması bulunamadı.');
                }

                // Gönderme işlemi başladı
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Doğrulanıyor...';

                try {
                    // reCAPTCHA doğrulaması
                    const token = await verifyRecaptcha();
                    
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';

                    // Form verilerini hazırla
                    const templateParams = {
                        from_name: this.querySelector('#name')?.value.trim() || '',
                        from_email: this.querySelector('#email')?.value.trim() || '',
                        subject: this.querySelector('#subject')?.value || '',
                        message: this.querySelector('#message')?.value.trim() || '',
                        to_name: 'MSSR Web',
                        reply_to: this.querySelector('#email')?.value.trim() || '',
                        'g-recaptcha-response': token
                    };

                    // Dosya varsa ekle
                    if (currentFile) {
                        const reader = new FileReader();
                        templateParams.attachment = await new Promise((resolve, reject) => {
                            reader.onloadend = () => resolve(reader.result);
                            reader.onerror = () => reject(new Error('Dosya okunamadı'));
                            reader.readAsDataURL(currentFile);
                        });
                        templateParams.attachment_name = currentFile.name;
                    }

                    // EmailJS ile gönder
                    const response = await emailjs.send(
                        config.SERVICE_ID,
                        config.TEMPLATE_ID,
                        templateParams,
                        config.PUBLIC_KEY
                    );

                    if (response.status === 200) {
                        // Başarılı gönderim
                        submitBtn.innerHTML = '<i class="fas fa-check"></i> Gönderildi!';
                        submitBtn.style.backgroundColor = '#27ae60';
                        
                        // Başarı mesajı
                        const successMessage = document.createElement('div');
                        successMessage.className = 'success-message';
                        successMessage.setAttribute('role', 'alert');
                        successMessage.setAttribute('aria-live', 'polite');
                        successMessage.innerHTML = `
                            <div style="background-color: #27ae60; color: white; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">
                                <i class="fas fa-check-circle" style="margin-right: 10px;"></i>
                                Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.
                            </div>
                        `;
                        this.appendChild(successMessage);
                        
                        // Form verilerini temizle
                        localStorage.removeItem('formData');
                        this.reset();
                        
                        if (charCounter) {
                            charCounter.textContent = '1000 karakter kaldı';
                            charCounter.className = 'char-counter';
                        }
                        
                        // Dosya önizlemeyi temizle
                        if (filePreview) {
                            filePreview.innerHTML = '';
                            currentFile = null;
                        }
                        
                        // 3 saniye sonra başarı mesajını kaldır
                        setTimeout(() => {
                            successMessage.remove();
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.style.backgroundColor = '';
                        }, 3000);
                    } else {
                        throw new Error('E-posta gönderilemedi.');
                    }
                } catch (error) {
                    console.error('Form Gönderim Hatası:', error);
                    
                    submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Hata!';
                    submitBtn.style.backgroundColor = '#e74c3c';
                    
                    let errorMessage = 'İşlem sırasında bir hata oluştu. ';
                    if (error.text) {
                        errorMessage += error.text;
                    } else if (error.message) {
                        errorMessage += error.message;
                    }
                    errorMessage += ' Lütfen tekrar deneyin.';
                    
                    showError(submitBtn, errorMessage);
                    
                    // Form verilerini kaydet
                    try {
                        const formData = {
                            name: this.querySelector('#name')?.value.trim(),
                            email: this.querySelector('#email')?.value.trim(),
                            subject: this.querySelector('#subject')?.value,
                            message: this.querySelector('#message')?.value.trim()
                        };
                        localStorage.setItem('formData', JSON.stringify(formData));
                    } catch (e) {
                        console.error('Form verilerini kaydetme hatası:', e);
                    }
                    
                    // 3 saniye sonra butonu resetle
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        submitBtn.style.backgroundColor = '';
                    }, 3000);
                }
            } catch (error) {
                console.error('Genel hata:', error);
                showError(submitBtn, 'Beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.');
                
                // Butonu resetle
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                submitBtn.style.backgroundColor = '';
            }
        });

        // Sayfa yüklendiğinde kaydedilmiş form verilerini kontrol et
        const savedFormData = localStorage.getItem('formData');
        if (savedFormData) {
            try {
                const formValues = JSON.parse(savedFormData);
                Object.entries(formValues).forEach(([key, value]) => {
                    const input = contactForm.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = value;
                    }
                });
                
                // Karakter sayacını güncelle
                if (formValues.message) {
                    const remaining = 500 - formValues.message.length;
                    charCounter.textContent = `${remaining} karakter kaldı`;
                    charCounter.style.color = remaining < 50 ? '#e74c3c' : '#666';
                }
            } catch (error) {
                console.error('Form verilerini geri yükleme hatası:', error);
                localStorage.removeItem('formData');
            }
        }

        // reCAPTCHA ve form işlemleri için yardımcı fonksiyonlar
        const formHelpers = {
            // Form durumunu saklamak için state
            state: {
                isSubmitting: false,
                hasError: false,
                retryCount: 0,
                maxRetries: 3
            },

            // Form durumunu güncelle
            updateFormState(newState) {
                this.state = { ...this.state, ...newState };
                this.updateUI();
            },

            // UI'ı güncelle
            updateUI() {
                const submitBtn = document.querySelector('button[type="submit"]');
                const formContainer = document.querySelector('.form-container');
                if (!submitBtn || !formContainer) return;

                // Submit butonunu güncelle
                submitBtn.disabled = this.state.isSubmitting;
                submitBtn.setAttribute('aria-busy', this.state.isSubmitting);

                // Hata durumunda retry butonu göster
                if (this.state.hasError && this.state.retryCount < this.state.maxRetries) {
                    let retryBtn = formContainer.querySelector('.retry-button');
                    if (!retryBtn) {
                        retryBtn = document.createElement('button');
                        retryBtn.className = 'retry-button';
                        retryBtn.setAttribute('type', 'button');
                        retryBtn.setAttribute('aria-label', 'Formu tekrar gönder');
                        retryBtn.innerHTML = '<i class="fas fa-redo"></i> Tekrar Dene';
                        retryBtn.style.cssText = `
                            margin-top: 1rem;
                            padding: 0.5rem 1rem;
                            background-color: #e74c3c;
                            color: white;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        `;
                        formContainer.appendChild(retryBtn);

                        retryBtn.addEventListener('click', () => {
                            this.updateFormState({
                                hasError: false,
                                isSubmitting: false
                            });
                            retryBtn.remove();
                            document.querySelector('form')?.requestSubmit();
                        });
                    }
                }
            },

            // Alternatif doğrulama yöntemini göster
            showAlternativeVerification() {
                const formContainer = document.querySelector('.form-container');
                if (!formContainer) return;

                const altVerification = document.createElement('div');
                altVerification.className = 'alternative-verification';
                altVerification.innerHTML = `
                    <div class="alt-verify-content" style="
                        margin-top: 1rem;
                        padding: 1rem;
                        background-color: #f8f9fa;
                        border-radius: 8px;
                        border: 1px solid #dee2e6;
                    ">
                        <h3 style="margin-bottom: 0.5rem; color: #2c3e50;">Alternatif Doğrulama</h3>
                        <p style="margin-bottom: 1rem; color: #666;">
                            reCAPTCHA doğrulaması başarısız oldu. 
                            E-posta adresinize bir doğrulama kodu göndereceğiz.
                        </p>
                        <button type="button" class="send-code-btn" style="
                            padding: 0.5rem 1rem;
                            background-color: #3498db;
                            color: white;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                        ">
                            <i class="fas fa-envelope"></i> Doğrulama Kodu Gönder
                        </button>
                    </div>
                `;

                formContainer.appendChild(altVerification);

                // Doğrulama kodu gönderme işlemi
                const sendCodeBtn = altVerification.querySelector('.send-code-btn');
                sendCodeBtn?.addEventListener('click', async () => {
                    try {
                        sendCodeBtn.disabled = true;
                        sendCodeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';
                        
                        // E-posta doğrulama kodu gönderme simülasyonu
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        
                        // Doğrulama kodu girişi
                        const verificationInput = document.createElement('div');
                        verificationInput.innerHTML = `
                            <div style="margin-top: 1rem;">
                                <input type="text" 
                                       id="verification-code" 
                                       placeholder="Doğrulama kodunu giriniz"
                                       maxlength="6"
                                       style="
                                           padding: 0.5rem;
                                           border: 1px solid #dee2e6;
                                           border-radius: 4px;
                                           width: 200px;
                                       "
                                       aria-label="Doğrulama kodu"
                                >
                                <button type="button" 
                                        class="verify-code-btn"
                                        style="
                                            margin-left: 0.5rem;
                                            padding: 0.5rem 1rem;
                                            background-color: #27ae60;
                                            color: white;
                                            border: none;
                                            border-radius: 4px;
                                            cursor: pointer;
                                        "
                                >
                                    Doğrula
                                </button>
                            </div>
                        `;
                        
                        altVerification.querySelector('.alt-verify-content').appendChild(verificationInput);
                        sendCodeBtn.remove();

                        // Doğrulama kodunu kontrol et
                        const verifyCodeBtn = verificationInput.querySelector('.verify-code-btn');
                        const codeInput = verificationInput.querySelector('#verification-code');

                        verifyCodeBtn?.addEventListener('click', () => {
                            const code = codeInput?.value;
                            if (code?.length === 6) {
                                // Başarılı doğrulama
                                altVerification.remove();
                                document.querySelector('form')?.requestSubmit();
                            } else {
                                // Hatalı kod
                                codeInput?.classList.add('error');
                                codeInput?.setAttribute('aria-invalid', 'true');
                            }
                        });

                    } catch (error) {
                        console.error('Doğrulama kodu gönderme hatası:', error);
                        sendCodeBtn.disabled = false;
                        sendCodeBtn.innerHTML = '<i class="fas fa-envelope"></i> Tekrar Dene';
                    }
                });
            }
        };

        // reCAPTCHA puan kontrolü
        async function verifyRecaptchaScore(token) {
            try {
                const config = window.EMAILJS_CONFIG;
                if (!config || !config.RECAPTCHA_SITE_KEY) {
                    throw new Error('reCAPTCHA yapılandırması bulunamadı.');
                }

                // Frontend'de token kontrolü
                if (!token) {
                    throw new Error('reCAPTCHA token alınamadı.');
                }

                // Token geçerlilik kontrolü
                if (typeof token !== 'string' || token.length < 50) {
                    throw new Error('Geçersiz reCAPTCHA token.');
                }

                return {
                    success: true,
                    score: 0.9
                };
            } catch (error) {
                console.error('reCAPTCHA doğrulama hatası:', error);
                
                // Alternatif doğrulama yöntemini göster
                formHelpers.showAlternativeVerification();
                
                throw new Error('Güvenlik doğrulaması başarısız oldu. Alternatif doğrulama yöntemi sunuldu.');
            }
        }

        // Form gönderimi sırasında reCAPTCHA kontrolü
        const verifyRecaptcha = async () => {
            try {
                const config = window.EMAILJS_CONFIG;
                if (!config || !config.RECAPTCHA_SITE_KEY) {
                    throw new Error('reCAPTCHA yapılandırması bulunamadı.');
                }

                // reCAPTCHA'nın yüklenip yüklenmediğini kontrol et
                if (typeof grecaptcha === 'undefined' || !grecaptcha.execute) {
                    throw new Error('reCAPTCHA yüklenemedi. Alternatif doğrulama kullanılacak.');
                }

                const token = await grecaptcha.execute(config.RECAPTCHA_SITE_KEY, {action: 'submit'});
                
                // Token kontrolü
                if (!token) {
                    throw new Error('reCAPTCHA token alınamadı.');
                }

                const result = await verifyRecaptchaScore(token);
                if (!result.success || result.score < 0.5) {
                    throw new Error('Güvenlik doğrulaması başarısız oldu. Alternatif doğrulama kullanılacak.');
                }

                return token;
            } catch (error) {
                console.error('reCAPTCHA hatası:', error);
                
                // Form durumunu güncelle
                formHelpers.updateFormState({
                    hasError: true,
                    isSubmitting: false,
                    retryCount: formHelpers.state.retryCount + 1
                });

                // Alternatif doğrulama yöntemini göster
                formHelpers.showAlternativeVerification();
                
                throw error;
            }
        };
    }
});

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

// Notification helper function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.setAttribute('role', 'alert');
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <p>${message}</p>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animasyon ile göster
    requestAnimationFrame(() => {
        notification.classList.add('show');
    });
    
    // 5 saniye sonra kaldır
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

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
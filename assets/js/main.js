// Toggle password visibility
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== MOBILE MENU =====
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileOverlay = document.querySelector('.mobile-menu-overlay');
    const mobileClose = document.querySelector('.mobile-close');
    
    function openMenu() {
        mobileMenu.classList.add('active');
        mobileOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (menuBtn) menuBtn.addEventListener('click', openMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMenu);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu?.classList.contains('active')) {
            closeMenu();
        }
    });
    
    // ===== PASSWORD TOGGLE =====
    window.togglePassword = function(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    };
    
    // ===== SIGNUP LIVE VALIDATION =====
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const termsCheckbox = document.getElementById('terms');
        const submitBtn = document.getElementById('submitBtn');
        
        let usernameDebounce;
        let usernameChecked = false;
        let usernameAvailable = false;
        
        // Username live validation with debounce
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
                const value = this.value.trim();
                const group = document.getElementById('usernameGroup');
                const status = document.getElementById('usernameStatus');
                const error = document.getElementById('usernameError');
                const hint = document.getElementById('usernameHint');
                const checkUrl = this.dataset.checkUrl;
                
                clearTimeout(usernameDebounce);
                usernameChecked = false;
                
                // Reset states
                group.classList.remove('is-valid', 'is-invalid');
                status.querySelectorAll('i').forEach(i => i.style.display = 'none');
                error.innerHTML = '';
                
                // Empty check
                if (!value) {
                    hint.textContent = '3-30 characters, letters, numbers, underscores, hyphens';
                    updateSubmitButton();
                    return;
                }
                
                // Format validation
                const formatValid = /^[a-zA-Z0-9_-]{3,30}$/.test(value);
                
                if (!formatValid) {
                    group.classList.add('is-invalid');
                    status.querySelector('.fa-times').style.display = 'inline-block';
                    hint.textContent = value.length < 3 ? 'Too short (min 3 chars)' : 'Invalid characters';
                    updateSubmitButton();
                    return;
                }
                
                // Show loading
                status.querySelector('.fa-spinner').style.display = 'inline-block';
                hint.textContent = 'Checking availability...';
                
                // Debounce database check
                usernameDebounce = setTimeout(() => {
                    fetch(`${checkUrl}?username=${encodeURIComponent(value)}`)
                        .then(res => res.json())
                        .then(data => {
                            status.querySelector('.fa-spinner').style.display = 'none';
                            usernameChecked = true;
                            
                            if (data.available) {
                                group.classList.add('is-valid');
                                group.classList.remove('is-invalid');
                                status.querySelector('.fa-check').style.display = 'inline-block';
                                hint.textContent = 'Username available!';
                                usernameAvailable = true;
                            } else {
                                group.classList.add('is-invalid');
                                group.classList.remove('is-valid');
                                status.querySelector('.fa-times').style.display = 'inline-block';
                                hint.textContent = data.message;
                                usernameAvailable = false;
                            }
                            updateSubmitButton();
                        })
                        .catch(() => {
                            status.querySelector('.fa-spinner').style.display = 'none';
                            hint.textContent = 'Could not check. Try again.';
                            updateSubmitButton();
                        });
                }, 500);
            });
        }
        
        // Password strength meter
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const value = this.value;
                const bars = document.querySelectorAll('.strength-bar span');
                const text = document.querySelector('.strength-text');
                
                let strength = 0;
                if (value.length >= 8) strength++;
                if (value.length >= 12) strength++;
                if (/[a-z]/.test(value) && /[A-Z]/.test(value)) strength++;
                if (/[0-9]/.test(value)) strength++;
                if (/[^A-Za-z0-9]/.test(value)) strength++;
                
                const classes = ['weak', 'fair', 'good', 'strong'];
                const labels = ['Too weak', 'Weak', 'Good', 'Strong'];
                
                bars.forEach((bar, i) => {
                    bar.className = '';
                    if (i < Math.min(strength, 4)) {
                        bar.classList.add(classes[Math.min(strength - 1, 3)]);
                    }
                });
                
                if (value.length === 0) {
                    text.textContent = 'Password strength';
                    text.className = 'strength-text';
                } else {
                    const idx = Math.min(strength - 1, 3);
                    text.textContent = labels[idx] || 'Too weak';
                    text.className = `strength-text ${classes[idx] || 'weak'}`;
                }
                
                checkPasswordMatch();
                updateSubmitButton();
            });
        }
        
        // Confirm password match
        if (confirmInput) {
            confirmInput.addEventListener('input', checkPasswordMatch);
        }
        
        function checkPasswordMatch() {
            const matchStatus = document.getElementById('matchStatus');
            if (!matchStatus || !passwordInput || !confirmInput) return;
            
            const pass = passwordInput.value;
            const confirm = confirmInput.value;
            
            matchStatus.querySelectorAll('i').forEach(i => i.style.display = 'none');
            
            if (!confirm) return;
            
            if (pass === confirm && pass.length >= 8) {
                matchStatus.querySelector('.fa-check').style.display = 'inline-block';
                confirmInput.parentElement.parentElement.classList.add('is-valid');
                confirmInput.parentElement.parentElement.classList.remove('is-invalid');
            } else {
                matchStatus.querySelector('.fa-times').style.display = 'inline-block';
                confirmInput.parentElement.parentElement.classList.add('is-invalid');
                confirmInput.parentElement.parentElement.classList.remove('is-valid');
            }
        }
        
        // Terms checkbox
        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', updateSubmitButton);
        }
        
        // Enable/disable submit button
        function updateSubmitButton() {
            if (!submitBtn) return;
            
            const email = document.getElementById('email')?.value.trim();
            const firstName = document.getElementById('first_name')?.value.trim();
            const lastName = document.getElementById('last_name')?.value.trim();
            const pass = passwordInput?.value;
            const confirm = confirmInput?.value;
            const terms = termsCheckbox?.checked;
            
            const allFilled = email && firstName && lastName && pass && confirm;
            const passValid = pass && pass.length >= 8 && /[A-Za-z]/.test(pass) && /[0-9]/.test(pass);
            const passMatch = pass === confirm && pass.length > 0;
            const usernameReady = usernameChecked && usernameAvailable;
            
            submitBtn.disabled = !(allFilled && passValid && passMatch && usernameReady && terms);
        }
        
        // Update on any input change
        signupForm.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updateSubmitButton);
        });
        
        // Initial check
        updateSubmitButton();
    }
    
    // ===== FAVORITE BUTTONS =====
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const icon = this.querySelector('i');
            this.classList.toggle('active');
            
            if (this.classList.contains('active')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
            
            const productId = this.dataset.productId;
            console.log('Toggle favorite for product:', productId);
        });
    });
    
    // ===== NAVBAR SCROLL =====
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            } else {
                navbar.style.boxShadow = 'none';
            }
        });
    }
    
});
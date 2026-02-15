document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.login-container');
    const SignUpBtn = document.querySelector('.SignUp-btn');
    const SignInBtn = document.querySelector('.SignIn-btn');

    if (container && SignUpBtn && SignInBtn) {
        SignUpBtn.addEventListener('click', () => {
            container.classList.add('active');
        });

        SignInBtn.addEventListener('click', () => {
            container.classList.remove('active');
        });
    }

    // Password visibility toggle
    function setupPassToggle(inputId, toggleId) {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);
        if (!input || !toggle) return;

        // Conditional visibility: only show eye if input has text
        input.addEventListener('input', () => {
            if (input.value.length > 0) {
                toggle.classList.add('visible');
            } else {
                toggle.classList.remove('visible');
            }
        });

        toggle.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            // Switch icons: bx-hide (slashed eye) vs bx-show (open eye)
            // Support both Boxicons and FontAwesome
            if (isPassword) {
                toggle.classList.replace('bx-hide', 'bx-show');
                toggle.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                toggle.classList.replace('bx-show', 'bx-hide');
                toggle.classList.replace('fa-eye', 'fa-eye-slash');
            }
        });
    }

    setupPassToggle('signup-password', 'toggle-signup-eye');
    setupPassToggle('signin-password', 'toggle-signin-eye');
});

<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // معالجة تسجيل الدخول (Sign In)
    if (isset($_POST['sign_in'])) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = "خطأ في التحقق من الطلب. الرجاء المحاولة مرة أخرى.";
        } else {
            $loginInput = sanitize($_POST['login_input']);
            $password = $_POST['password'];

            if (empty($loginInput) || empty($password)) {
                $errors[] = "الرجاء ملء جميع الحقول.";
            } else {
                if (!checkLoginAttempts($loginInput)) {
                    $waitMinutes = ceil(getRemainingLockoutTime($loginInput) / 60);
                    $errors[] = "لقد تجاوزت عدد المحاولات المسموحة. الرجاء المحاولة بعد $waitMinutes دقيقة.";
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$loginInput, $loginInput]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        if ($user['activation'] == 0) {
                            $errors[] = "حسابك غير مفعل. الرجاء التواصل مع الإدارة.";
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['user_type'] = $user['user_type'];
                            resetLoginAttempts($loginInput);
                            redirect('index.php');
                        }
                    } else {
                        recordFailedLogin($loginInput);
                        $errors[] = "بيانات الدخول غير صحيحة.";
                    }
                }
            }
        }
    }

    // معالجة تسجيل مستخدم جديد (Sign Up)
    if (isset($_POST['sign_up'])) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = "خطأ في التحقق من الطلب. الرجاء المحاولة مرة أخرى.";
        } else {
            $name = sanitize($_POST['name']);
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];

            if (empty($name) || empty($username) || empty($email) || empty($password)) {
                $errors[] = "جميع الحقول المطلوبة يجب ملؤها.";
            } elseif (!validateEmail($email)) {
                $errors[] = "البريد الإلكتروني غير صالح.";
            } elseif (strlen($password) < 6) {
                $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل.";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = "اسم المستخدم أو البريد الإلكتروني مستخدم مسبقاً.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, activation) VALUES (?, ?, ?, ?, 1)");
                        if ($stmt->execute([$name, $username, $email, $hashed_password])) {
                            $success = "تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول باستخدام بياناتك.";
                            triggerPortableBackup(); // حفظ نسخة محملة من البيانات
                        } else {
                            $errors[] = "حدث خطأ غير متوقع أثناء إنشاء الحساب.";
                            logError("Registration failed for user: $username", 'error');
                        }
                    } catch (PDOException $e) {
                        $errors[] = "خطأ في قاعدة البيانات: " . $e->getMessage();
                        logError("Registration DB Error: " . $e->getMessage(), 'critical');
                    }
                }
            }
        }
    }
}


require_once 'components/header.php';
?>

<!-- Dynamic Background Orbs -->
<div class="bg-orb orb1"></div>
<div class="bg-orb orb2"></div>
<div class="bg-orb orb3"></div>
<div class="bg-orb orb4"></div>
<div class="bg-orb orb5"></div>
<div class="bg-orb orb6"></div>
<div class="bg-orb orb7"></div>
<div class="bg-orb orb8"></div>
<div class="bg-orb orb9"></div>
<div class="bg-orb orb10"></div>
<div class="bg-orb orb11"></div>
<div class="bg-orb orb12"></div>

<!-- Boxicons CDN -->
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<a href="index.php" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    <span data-i18n="auth-back">Back</span>
</a>
<link rel="stylesheet" href="assets/css/auth.css?v=<?= APP_VERSION ?>">

<style>
    /* Hide Navbar specifically on this page */
    .navbar { display: none !important; }
    body { padding-top: 0 !important; }
    /* Override alert styles for auth page if needed */
    .alert-error { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 5px; margin-bottom: 10px; font-size: 0.9em; }
    .alert-success { color: #059669; background: #d1fae5; padding: 10px; border-radius: 5px; margin-bottom: 10px; font-size: 0.9em; }
</style>

<div class="login-container <?= (isset($_POST['sign_up']) && empty($success)) ? 'active' : '' ?>" id="container"> 
    <!-- ======================================[ Form Auth ]====================================== -->
    <!-- ======================================================================= [ Sign-In_Form ] ======================================================================= -->
    <div class="form-box SignIn">
        <form action="" method="POST">
            <h1 data-i18n="auth-signin-title">Sign In</h1>
            <p data-i18n="auth-signin-subtitle" class="form-subtitle">Or use your verified credentials</p>
            
            <?php if (!empty($errors) && isset($_POST['sign_in'])): ?>
                <div class="alert alert-error">
                    <svg stroke="currentColor" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path></svg>
                    <p><?= $errors[0] ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg stroke="currentColor" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path></svg>
                    <p><?= $success ?></p>
                </div>
            <?php endif; ?>

            <?php csrfField(); ?>
            <div class="input-box">
                <input type="text" name="login_input" title="username" placeholder="Username or Email" data-i18n="placeholder-login" required />
                <i class="fas fa-user"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" id="signin-password" placeholder="Password" data-i18n="placeholder-pass" required />
                <i class="fas fa-lock"></i>
                <i class="fas fa-eye-slash toggle-eye" id="toggle-signin-eye"></i>
            </div>
            <div class="forgot-link">
                <a href="#" data-i18n="auth-forgot">Forgot password?</a>
            </div>
            <button type="submit" name="sign_in" class="btn" data-i18n="btn-signin">Sign In</button>
            <p data-i18n="auth-social-login">or Sign In with social platforms</p>
            <div class="social-icons">
                <a href="#" title="google" class="google"><i class="fab fa-google"></i></a>
                <a href="#" title="facebook" class="faceboock"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="github" class="github"><i class="fab fa-github"></i></a>
                <a href="#" title="linkedin" class="linkedin"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </form>
    </div>
    <!-- ======================================================================= [ Sign-Up_Form ] ======================================================================= -->
    <div class="form-box SignUp">
        <form action="" method="POST">
            <h1 data-i18n="auth-signup-title">Sign Up</h1>
            <p data-i18n="auth-signup-subtitle" class="form-subtitle">Or use your email for fresh enrollment</p>

            <?php if (!empty($errors) && isset($_POST['sign_up'])): ?>
                <div class="alert alert-error">
                    <svg stroke="currentColor" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path></svg>
                    <p><?= $errors[0] ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg stroke="currentColor" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path></svg>
                    <p><?= $success ?></p>
                </div>
            <?php endif; ?>

            <?php csrfField(); ?>
            <div class="input-box">
                <input type="text" name="name" placeholder="Full Name" data-i18n="placeholder-name" required />
                <i class="fas fa-id-card"></i>
            </div>
            <div class="input-box">
                <input type="text" name="username" placeholder="Username" data-i18n="placeholder-username" required />
                <i class="fas fa-user"></i>
            </div>
            <div class="input-box">
                <input type="email" name="email" placeholder="Email" data-i18n="placeholder-email" required />
                <i class="fas fa-envelope"></i>
            </div>
            <div class="input-box">
                <input type="password" name="password" id="signup-password" placeholder="Password" data-i18n="placeholder-pass" required />
                <i class="fas fa-lock"></i>
                <i class="fas fa-eye-slash toggle-eye" id="toggle-signup-eye"></i>
            </div>
            <button type="submit" name="sign_up" class="btn" data-i18n="btn-signup">Sign Up</button>
            <p data-i18n="auth-social-signup">or Sign Up with social platforms</p>
            <div class="social-icons">
                <a href="#" title="google" class="google"><i class="fab fa-google"></i></a>
                <a href="#" title="facebook" class="faceboock"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="github" class="github"><i class="fab fa-github"></i></a>
                <a href="#" title="linkedin" class="linkedin"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </form>
    </div>
    <!-- ======================================================================== [ Toggle_Box ] ======================================================================== -->
    <div class="toggle-box" id="toggle-box">
        <div class="toggle-panel toggle-left">
            <h1 data-i18n="auth-welcome-back">Hello, Welcome!</h1>
            <p data-i18n="auth-welcome-desc">Don't have an account?</p>
            <button class="btn SignUp-btn" data-i18n="btn-signup">Sign Up</button>
        </div>
        <div class="toggle-panel toggle-right">
            <h1 data-i18n="auth-join-unit">Welcome Back</h1>
            <p data-i18n="auth-join-desc">Already have an account?</p>
            <button class="btn SignIn-btn" data-i18n="btn-signin">Sign In</button>
        </div>
    </div>
</div>

<script src="assets/js/main.js?v=<?= APP_VERSION ?>"></script>
<script src="assets/js/auth.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>

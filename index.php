<?php
// Include error handling configuration
require_once __DIR__ . '/middleware/error_handler.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#007bff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SmartSpace">
    <meta name="application-name" content="SmartSpace">
    <meta name="msapplication-TileColor" content="#007bff">
    <meta name="msapplication-TileImage" content="public/assets/logo.webp">
    
    <!-- Icons and Manifest -->
    <link rel="icon" href="public/assets/final_logo.svg" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="public/assets/final_logo.svg">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="partials/terms.css">
    
    <!-- Fonts and Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/login.css">
    <title>MCiSmartSpace</title>
    <style>
        .alert {
            transition: opacity 1s ease-in-out;
        }
        .fade-out {
            opacity: 0;
        }
    </style>
</head>

<body>
    <!-- Install App Button -->
    <div class="install-app-button">
        <div class="button-wrapper">
            <button class="shine-button button-forest" onclick="window.open('https://app.mcismartspace.com/', '_blank')">
                Install APK
            </button>
        </div>
    </div>
    
    <div class="login-image">
        <div class="image-overlay-text"></div>
    </div>
    <div class="login-form">
        <div class="form-container">
            <div class="branding">
                <img src="public/assets/final_logo.svg" alt="Logo" class="logo">
                <h1 class="brand-title">MCiSmartSpace</h1>
                <div class="college-name">Meycauayan College</div>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert" style="padding: 10px; margin-bottom: 15px; border-radius: 5px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                    <?php
                    switch ($_GET['error']) {
                        case 'locked':
                            echo "Too many failed login attempts. Please try again in 15 minutes.";
                            break;
                        case 'invalid':
                            echo "Invalid email or password.";
                            break;
                        case 'timeout':
                            echo "Your session has expired. Please log in again.";
                            break;
                        case 'denied':
                             echo "Access denied.";
                             break;
                        case 'not_authenticated':
                            echo "Please log in to report equipment issues.";
                            if (isset($_GET['equipment_id'])) {
                                echo " <small>(Equipment ID: " . htmlspecialchars($_GET['equipment_id']) . ")</small>";
                            }
                            break;
                        case 'unauthorized_role':
                            echo "Your account role is not authorized to report equipment issues.";
                            break;
                        case 'invalid_equipment_id':
                            echo "Invalid or missing equipment ID in QR code.";
                            break;
                        case 'equipment_not_found':
                            echo "The scanned equipment was not found in the system.";
                            if (isset($_GET['equipment_id'])) {
                                echo " <small>(Equipment ID: " . htmlspecialchars($_GET['equipment_id']) . ")</small>";
                            }
                            break;
                        case 'system_error':
                            echo "A system error occurred while processing the QR code.";
                            break;
                        case 'unauthorized_access':
                            echo "Unauthorized access. Please log in first.";
                            break;
                        default:
                            // Check if there's a custom message from QR redirect
                            if (isset($_GET['message'])) {
                                echo htmlspecialchars(urldecode($_GET['message']));
                            } else {
                                echo "An unknown error occurred.";
                            }
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['attempts_left'])): ?>
                <div class="alert alert-warning" role="alert" style="padding: 10px; margin-bottom: 15px; border-radius: 5px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;">
                    You have <?php echo htmlspecialchars($_GET['attempts_left']); ?> login attempts remaining.
                </div>
            <?php endif; ?>

            <form action="auth/login.php" method="POST">
                <div class="form-group">
                    <input
                        type="email"
                        class="form-control"
                        name="email"
                        placeholder="Email"
                        required>
                </div>
                <div class="form-group" style="position:relative;">
                    <input
                        type="password"
                        class="form-control"
                        name="password"
                        id="password"
                        placeholder="Password"
                        required>
                    <button type="button" id="togglePassword" aria-label="Show password" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:none; outline:none; cursor:pointer; padding:0; display:flex; align-items:center;">
                        <svg id="eyeIcon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="transition:stroke 0.2s;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
            <!-- Professional Footer -->
            <div class="login-footer">
                <div class="footer-content">
                    <div class="footer-copyright">
                        © <?php echo date('Y'); ?> MCISmartSpace
                    </div>
                    <div class="footer-links">
                        <a href="https://lins-documentation.gitbook.io/mcismartspacedocs/legal/privacy-policy" class="footer-link" target="_blank">Privacy Policy</a>
                        <span class="footer-separator">|</span>
                        <a href="#" class="footer-link" id="openTermsModal">Terms of Service</a>
                        <span class="footer-separator">|</span>
                        <a href="https://lins-documentation.gitbook.io/mcismartspacedocs/support/contact" class="footer-link" target="_blank">Support</a>
                    </div>
                </div>
            </div>
            <script>
                        const passwordInput = document.getElementById('password');
                        const togglePassword = document.getElementById('togglePassword');
                        const eyeIcon = document.getElementById('eyeIcon');
                        let passwordVisible = false;
                        togglePassword.addEventListener('click', function() {
                                passwordVisible = !passwordVisible;
                                passwordInput.type = passwordVisible ? 'text' : 'password';
                                eyeIcon.innerHTML = passwordVisible
                                    ? '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.07 21.07 0 0 1 5.06-7.06"/><path d="M9.53 9.53A3 3 0 0 1 12 15a3 3 0 0 1-2.47-5.47"/><path d="M1 1l22 22"/>'
                                    : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                                eyeIcon.setAttribute('stroke', passwordVisible ? '#007bff' : '#666');
                        });
            </script>
        </div>
    </div>
    
    <!-- Terms of Service Modal -->
    <?php include 'partials/terms.php'; ?>


    
    <script src="./public/js/alert.js"></script>
    <script src="./public/js/connection-status.js"></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
        // Register service worker for PWA functionality
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                        
                        // Check for updates
                        registration.addEventListener('updatefound', function() {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', function() {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New content is available, refresh the page
                                    if (confirm('New version available! Refresh to update?')) {
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }

        // PWA Install Prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA install prompt triggered');
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button or banner (you can customize this)
            showInstallPromotion();
        });

        function showInstallPromotion() {
            // Don't create the blue install button - we have our custom green one
            return;
        }

        // Handle app installed event
        window.addEventListener('appinstalled', (evt) => {
            console.log('SmartSpace PWA was installed');
            // Remove install button if it exists
            const installButton = document.getElementById('installButton');
            if (installButton) {
                installButton.remove();
            }
        });
    </script>
    
    <script>
        // Terms of Service Modal JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('termsModal');
            var openBtn = document.getElementById('openTermsModal');
            var closeBtn = document.querySelector('.terms-close');
            var closeBtnFooter = document.querySelector('.terms-btn-close');
            
            // Open modal when Terms of Service link is clicked
            if (openBtn) {
                openBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
                });
            }
            
            // Close modal when X button is clicked
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            }
            
            // Close modal when Close button in footer is clicked
            if (closeBtnFooter) {
                closeBtnFooter.addEventListener('click', function() {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            }
            
            // Close modal when clicking outside of it (backdrop)
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.style.display === 'block') {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });
    </script>
</body>

</html>
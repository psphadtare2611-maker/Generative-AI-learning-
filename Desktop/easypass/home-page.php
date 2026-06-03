<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyPass - Bus Pass Management System</title>
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #4285f4;
            --accent-color: #fbbc05;
            --success-color: #34a853;
            --danger-color: #ea4335;
            --light-gray: #f5f5f5;
            --dark-gray: #333333;
            --medium-gray: #757575;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-image: url('istockphoto-889402258-612x612.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            color: var(--dark-gray);
        }

        .content-wrapper {
            background-color: rgba(255, 255, 255, 0.9);
            min-height: 100vh;
        }

        header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 0.5rem;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav li {
            margin-left: 1.5rem;
        }

        nav a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav a:hover {
            color: var(--accent-color);
        }

        .auth-buttons a {
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }

        .login-btn {
            color: var(--white);
            border: 1px solid var(--white);
        }

        .login-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .signup-btn {
            background-color: var(--accent-color);
            color: var(--dark-gray);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .signup-btn:hover {
            background-color: #ffce3a;
        }


        .hero {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('/api/placeholder/1200/600');
            background-size: cover;
            background-position: center;
            height: 70vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: var(--white);
            padding: 0 1rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin-bottom: 2rem;
        }

        .cta-btn {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .cta-btn:hover {
            background-color: var(--secondary-color);
        }

        .features {
            padding: 4rem 2rem;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: var(--dark-gray);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .how-it-works {
            padding: 4rem 2rem;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .steps {
            max-width: 800px;
            margin: 0 auto;
        }

        .step {
            display: flex;
            margin-bottom: 2rem;
            align-items: center;
        }

        .step-number {
            background-color: var(--primary-color);
            color: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .step-content h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .pricing {
            padding: 4rem 2rem;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .pricing-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .pricing-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .price {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
            color: var(--primary-color);
        }

        .price span {
            font-size: 1rem;
            color: var(--medium-gray);
        }

        .pricing-features {
            list-style: none;
            margin: 1.5rem 0;
            text-align: left;
        }

        .pricing-features li {
            margin-bottom: 0.5rem;
            position: relative;
            padding-left: 1.5rem;
        }

        .pricing-features li::before {
            content: "✓";
            color: var(--success-color);
            position: absolute;
            left: 0;
        }

        .popular {
            position: relative;
            overflow: hidden;
            border: 2px solid var(--accent-color);
        }

        .popular::before {
            content: "Most Popular";
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--accent-color);
            color: var(--dark-gray);
            padding: 0.25rem 2rem;
            transform: rotate(45deg) translateX(1.5rem) translateY(-1rem);
            font-size: 0.8rem;
            font-weight: bold;
        }

        footer {
            background-color: var(--dark-gray);
            color: var(--white);
            padding: 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--accent-color);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--accent-color);
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icons a {
            color: var(--white);
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .social-icons a:hover {
            color: var(--accent-color);
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            color: var(--medium-gray);
        }

        .app-screenshot {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .auth-buttons {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
                font-size: 1.5rem;
                color: var(--white);
                background: none;
                border: none;
                cursor: pointer;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .step {
                flex-direction: column;
                text-align: center;
            }

            .step-number {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }

        /* Login Form Styles */
        .form-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .login-form {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .form-header h2 {
            font-size: 1.5rem;
            color: var(--dark-gray);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--medium-gray);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .forgot-password {
            display: block;
            text-align: right;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--medium-gray);
        }

        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
    <div class="content-wrapper">
        <header>
            <div class="logo">
                <i class="fas fa-bus"></i> EasyPass
            </div>
            <nav class="nav-menu">
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <a href="#" class="login-btn" id="open-login">Login</a>
                <a href="#" class="signup-btn">Sign Up</a>
            </div>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <section class="hero">
            <h1>Manage Your Bus Pass Online</h1>
            <p>Say goodbye to long queues and paperwork. Get your bus pass online and manage it from anywhere, anytime.</p>
            <a href="#" class="cta-btn">Get Started</a>
        </section>

        <section class="features" id="features">
            <h2 class="section-title">Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Digital Bus Pass</h3>
                    <p>Access your bus pass directly from your smartphone. No need to carry physical cards anymore.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Easy Renewal</h3>
                    <p>Renew your bus pass with just a few clicks. Get notifications before your pass expires.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <h3>Route Planning</h3>
                    <p>Plan your journey and check bus schedules for all routes covered by your pass.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Secure Payments</h3>
                    <p>Multiple payment options with bank-grade security to protect your information.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>Travel History</h3>
                    <p>View your travel history and manage your frequent routes for quicker access.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Notifications</h3>
                    <p>Get real-time notifications about delays, schedule changes, and pass status.</p>
                </div>
            </div>
        </section>

        <section class="how-it-works" id="how-it-works">
            <h2 class="section-title">How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Create an Account</h3>
                        <p>Sign up with your email and create a password. Verify your identity with your government ID.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Choose Your Pass Type</h3>
                        <p>Select from daily, weekly, monthly, or annual passes based on your travel needs.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Make Payment</h3>
                        <p>Pay securely with your preferred payment method - credit/debit card, net banking, or mobile wallet.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Get Your Digital Pass</h3>
                        <p>Your pass is instantly generated and available in the app. Download it for offline access.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h3>Scan & Travel</h3>
                        <p>Show your QR code when boarding the bus. The conductor will scan it to validate your pass.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="pricing" id="pricing">
            <h2 class="section-title">Choose Your Plan</h2>
            <div class="pricing-cards">
                <div class="pricing-card">
                    <h3>Daily Pass</h3>
                    <div class="price">$5 <span>/day</span></div>
                    <p>Perfect for occasional travelers</p>
                    <ul class="pricing-features">
                        <li>Unlimited rides for 24 hours</li>
                        <li>All routes coverage</li>
                        <li>Mobile pass</li>
                    </ul>
                    <a href="#" class="cta-btn">Buy Now</a>
                </div>
                <div class="pricing-card popular">
                    <h3>Monthly Pass</h3>
                    <div class="price">$75 <span>/month</span></div>
                    <p>Best value for regular commuters</p>
                    <ul class="pricing-features">
                        <li>Unlimited rides for 30 days</li>
                        <li>All routes coverage</li>
                        <li>Mobile pass</li>
                        <li>Priority customer support</li>
                        <li>Trip history</li>
                    </ul>
                    <a href="#" class="cta-btn">Buy Now</a>
                </div>
                <div class="pricing-card">
                    <h3>Annual Pass</h3>
                    <div class="price">$750 <span>/year</span></div>
                    <p>Save big with our annual plan</p>
                    <ul class="pricing-features">
                        <li>Unlimited rides for 365 days</li>
                        <li>All routes coverage</li>
                        <li>Mobile pass</li>
                        <li>Priority customer support</li>
                        <li>Trip history</li>
                        <li>Free companion pass on weekends</li>
                    </ul>
                    <a href="#" class="cta-btn">Buy Now</a>
                </div>
            </div>
        </section>

        <footer id="contact">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>EasyPass</h3>
                    <p>Making public transportation easier and more accessible for everyone.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Transit Street, City</li>
                        <li><i class="fas fa-phone"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope"></i> support@easypass.com</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Download App</h3>
                    <p>Get our mobile app for a better experience.</p>
                    <div class="app-links">
                        <a href="#"><img src="/api/placeholder/120/40" alt="App Store"></a>
                        <a href="#"><img src="/api/placeholder/120/40" alt="Google Play"></a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                &copy; 2025 EasyPass. All rights reserved.
            </div>
        </footer>
    </div>

    <!-- Login Form (Hidden by default) -->
    <div class="form-container" id="login-form-container" style="display: none;">
        <div class="login-form">
            <div class="form-header">
                <h2>Login to Your Account</h2>
                <button class="close-btn" id="close-login">&times;</button>
            </div>
            <form>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" required>
                </div>
                <a href="#" class="forgot-password">Forgot Password?</a>
                <button type="submit" class="submit-btn">Login</button>
            </form>
            <div class="form-footer">
                Don't have an account? <a href="#">Sign Up</a>
            </div>
        </div>
    </div>
    <div class="form-container" id="signup-form-container" style="display: none;">
        <div class="signup-form">
            <div class="form-header">
                <h2>Create Your Account</h2>
                <button class="close-btn" id="close-signup">&times;</button>
            </div>
            <form id="registration.php">
                <div class="form-group">
                    <label for="signup-name">Full Name</label>
                    <input type="text" id="signup-name" name="signup-name" required>
                </div>
                <div class="form-group">
                    <label for="signup-email">Email Address</label>
                    <input type="email" id="signup-email" name="signup-email"required>
                </div>
                <div class="form-group">
                    <label for="signup-phone">Phone Number</label>
                    <input type="tel" id="signup-phone" name="signup-phone" required>
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="signup-password" required>
                </div>
                <div class="form-group">
                    <label for="signup-confirm-password">Confirm Password</label>
                    <input type="password" id="signup-confirm-password" name="signup-confirm-password" required>
                </div>
                <button type="submit" class="submit-btn">Create Account</button>
            </form>
            <div class="form-footer">
                Already have an account? <a href="#" id="switch-to-login">Login</a>
            </div>
        </div>
    </div>

    <script>
        // Simple JavaScript for login form toggle
        document.addEventListener('DOMContentLoaded', function() {
            const openLoginBtn = document.getElementById('open-login');
            const closeLoginBtn = document.getElementById('close-login');
            const loginFormContainer = document.getElementById('login-form-container');

            openLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                loginFormContainer.style.display = 'flex';
            });

            closeLoginBtn.addEventListener('click', function() {
                loginFormContainer.style.display = 'none';
            });

            // Close when clicking outside the form
            loginFormContainer.addEventListener('click', function(e) {
                if (e.target === loginFormContainer) {
                    loginFormContainer.style.display = 'none';
                }
            });
        });
    </script>
    
</body>
</html>
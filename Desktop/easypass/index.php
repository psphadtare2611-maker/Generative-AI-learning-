<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EasyPass - Bus Pass Management System</title>
  <link rel="stylesheet" href="styles/styles.css">
  <link rel="stylesheet" href="styles/pricing.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
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
        <?php
        if (isset($_SESSION['user_id'])) {
          echo '<li><a href="dashboard.php">Dashboard</a></li>';
        }
        ?>
      </ul>
    </nav>
    <div class="auth-buttons">
      <?php
      if (!isset($_SESSION['user_id'])) {
        echo '<a href="login.php" class="login-btn">Login</a>
      <a href="signup.php" class="signup-btn">Sign Up</a>';
      } else {
        echo '<a href="logout.php" class="logout-btn">Logout</a>';
      }
      ?>
    </div>
  </header>
  <section class="hero">
    <h1>Manage Your Bus Pass Online</h1>
    <p>Say goodbye to long queues and paperwork. Get your bus pass online and manage it from anywhere, anytime.</p>
    <a href="signup.php" class="cta-btn">Get Started</a>
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
        <h3>Monthly Pass</h3>
        <div class="price">₹680 <span>/day</span></div>
        <p>Perfect for occasional travelers</p>
        <ul class="pricing-features">
          <li>Unlimited rides for 24 hours</li>
          <li>All routes coverage</li>
          <li>Mobile pass</li>
        </ul>
        <a href="passes.php" class="cta-btn">Buy Now</a>
      </div>
      <div class="pricing-card popular">
        <h3>Six months Pass</h3>
        <div class="price">₹3699 <span>/month</span></div>
        <p>Best value for regular commuters</p>
        <ul class="pricing-features">
          <li>Unlimited rides for 30 days</li>
          <li>All routes coverage</li>
          <li>Mobile pass</li>
          <li>Priority customer support</li>
          <li>Trip history</li>
        </ul>
        <a href="passes.php" class="cta-btn">Buy Now</a>
      </div>
      <div class="pricing-card">
        <h3>Annual Pass</h3>
        <div class="price">₹7499 <span>/year</span></div>
        <p>Save big with our annual plan</p>
        <ul class="pricing-features">
          <li>Unlimited rides for 365 days</li>
          <li>All routes coverage</li>
          <li>Mobile pass</li>
          <li>Priority customer support</li>
          <li>Trip history</li>
          <li>Free companion pass on weekends</li>
        </ul>
        <a href="passes.php" class="cta-btn">Buy Now</a>
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
          <li><i class="fas fa-map-marker-alt"></i> 123 ADCET, Ashta</li>
          <li><i class="fas fa-phone"></i> +91 7666935049</li>
          <li><i class="fas fa-envelope"></i> support@easypass.com</li>
        </ul>
      </div>
    </div>
    <div class="copyright">
      &copy; 2025 EasyPass. All rights reserved.
    </div>
  </footer>

  <script>
    // JavaScript for login and signup form functionality
    document.addEventListener('DOMContentLoaded', function () {
      // Get all necessary elements
      const openLoginBtn = document.getElementById('open-login');
      const closeLoginBtn = document.getElementById('close-login');
      const loginFormContainer = document.getElementById('login-form-container');
      const loginForm = document.getElementById('login-form');

      const openSignupBtn = document.getElementById('open-signup');
      const closeSignupBtn = document.getElementById('close-signup');
      const signupFormContainer = document.getElementById('signup-form-container');
      const signupForm = document.getElementById('signup-form');

      const switchToSignupLink = document.getElementById('switch-to-signup');
      const switchToLoginLink = document.getElementById('switch-to-login');

      // Login form toggle
      /*
      openLoginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        loginFormContainer.style.display = 'flex';
      });

      closeLoginBtn.addEventListener('click', function() {
        loginFormContainer.style.display = 'none';
      });
      */

      // Close login form when clicking outside
      loginFormContainer.addEventListener('click', function (e) {
        if (e.target === loginFormContainer) {
          loginFormContainer.style.display = 'none';
        }
      });

      // Signup form toggle
      /*
      openSignupBtn.addEventListener('click', function(e) {
        e.preventDefault();
        signupFormContainer.style.display = 'flex';
    });
    
    closeSignupBtn.addEventListener('click', function() {
        signupFormContainer.style.display = 'none';
    });
    */

      // Close signup form when clicking outside
      signupFormContainer.addEventListener('click', function (e) {
        if (e.target === signupFormContainer) {
          signupFormContainer.style.display = 'none';
        }
      });

      // Switch between login and signup forms
      switchToSignupLink.addEventListener('click', function (e) {
        e.preventDefault();
        loginFormContainer.style.display = 'none';
        signupFormContainer.style.display = 'flex';
      });

      switchToLoginLink.addEventListener('click', function (e) {
        e.preventDefault();
        signupFormContainer.style.display = 'none';
        loginFormContainer.style.display = 'flex';
      });

      // Handle login form submission
      loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        // In a real application, you would validate credentials here
        // For now, we'll just redirect to the dashboard
        window.location.href = 'dashboard.html';
      });

      // Handle signup form submission
      signupForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const name = document.getElementById('signup-name').value;
        const email = document.getElementById('signup-email').value;
        const phone = document.getElementById('signup-phone').value;
        const password = document.getElementById('signup-password').value;
        const confirmPassword = document.getElementById('signup-confirm-password').value;

        // Validate passwords match
        if (password !== confirmPassword) {
          alert('Passwords do not match!');
          return;
        }

        // In a real application, you would create an account here
        // For now, we'll redirect to the dashboard
        window.location.href = 'dashboard.html';
      });
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Elements for payment form
      const buyNowButtons = document.querySelectorAll('.pricing-card .cta-btn');
      const paymentFormContainer = document.getElementById('payment-form-container');
      const closePaymentBtn = document.getElementById('close-payment');
      const selectedPlanElement = document.getElementById('selected-plan');
      const totalAmountElement = document.getElementById('total-amount');
      const purchaseForm = document.getElementById('pass-purchase-form');
      const successMessage = document.querySelector('.success-message');

      // Payment method toggles
      const creditCardRadio = document.getElementById('credit-card');
      const upiRadio = document.getElementById('upi');
      const netBankingRadio = document.getElementById('net-banking');
      const walletRadio = document.getElementById('wallet');

      const creditCardDetails = document.getElementById('credit-card-details');
      const upiDetails = document.getElementById('upi-details');
      const netBankingDetails = document.getElementById('net-banking-details');
      const walletDetails = document.getElementById('wallet-details');

      // Format card number with spaces
      const cardNumberInput = document.getElementById('card-number');
      cardNumberInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
          if (i > 0 && i % 4 === 0) {
            formattedValue += ' ';
          }
          formattedValue += value.charAt(i);
        }
        e.target.value = formattedValue;
      });

      // Format expiry date with slash
      const expiryDateInput = document.getElementById('expiry-date');
      expiryDateInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) {
          value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
      });

      // Handle Buy Now button clicks
      buyNowButtons.forEach(button => {
        button.addEventListener('click', function (e) {
          e.preventDefault();

          // Find the pricing card that contains this button
          const pricingCard = this.closest('.pricing-card');
          const planTitle = pricingCard.querySelector('h3').textContent;
          const planPrice = pricingCard.querySelector('.price').textContent.split(' ')[0];

          // Update the payment form with plan details
          selectedPlanElement.textContent = planTitle;
          totalAmountElement.textContent = planPrice;

          // Reset form if previously used
          if (purchaseForm) purchaseForm.reset();
          if (successMessage) successMessage.style.display = 'none';
          purchaseForm.style.display = 'block';

          // Make sure credit card details are shown by default
          creditCardDetails.style.display = 'block';
          upiDetails.style.display = 'none';
          netBankingDetails.style.display = 'none';
          walletDetails.style.display = 'none';

          // Show payment form with animation
          paymentFormContainer.style.opacity = '0';
          paymentFormContainer.style.display = 'flex';
          setTimeout(() => {
            paymentFormContainer.style.opacity = '1';
          }, 10);
        });
      });

      // Close payment form
      closePaymentBtn.addEventListener('click', function () {
        paymentFormContainer.style.opacity = '0';
        setTimeout(() => {
          paymentFormContainer.style.display = 'none';
        }, 300);
      });

      // Close payment form when clicking outside
      paymentFormContainer.addEventListener('click', function (e) {
        if (e.target === paymentFormContainer) {
          paymentFormContainer.style.opacity = '0';
          setTimeout(() => {
            paymentFormContainer.style.display = 'none';
          }, 300);
        }
      });

      // Toggle payment method details
      creditCardRadio.addEventListener('change', function () {
        if (this.checked) {
          creditCardDetails.style.display = 'block';
          upiDetails.style.display = 'none';
          netBankingDetails.style.display = 'none';
          walletDetails.style.display = 'none';
        }
      });

      upiRadio.addEventListener('change', function () {
        if (this.checked) {
          creditCardDetails.style.display = 'none';
          upiDetails.style.display = 'block';
          netBankingDetails.style.display = 'none';
          walletDetails.style.display = 'none';
        }
      });

      netBankingRadio.addEventListener('change', function () {
        if (this.checked) {
          creditCardDetails.style.display = 'none';
          upiDetails.style.display = 'none';
          netBankingDetails.style.display = 'block';
          walletDetails.style.display = 'none';
        }
      });

      walletRadio.addEventListener('change', function () {
        if (this.checked) {
          creditCardDetails.style.display = 'none';
          upiDetails.style.display = 'none';
          netBankingDetails.style.display = 'none';
          walletDetails.style.display = 'block';
        }
      });

      // Form submission
      purchaseForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Simulate loading
        const submitBtn = this.querySelector('.submit-btn');
        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;

        // Simulate server request
        setTimeout(() => {
          // Hide form and show success message
          purchaseForm.style.display = 'none';
          successMessage.style.display = 'block';

          // Reset button
          submitBtn.textContent = 'Proceed to Payment';
          submitBtn.disabled = false;
        }, 1500);
      });

      // Close success button
      const closeSuccessBtn = document.querySelector('.close-success');
      if (closeSuccessBtn) {
        closeSuccessBtn.addEventListener('click', function () {
          paymentFormContainer.style.opacity = '0';
          setTimeout(() => {
            paymentFormContainer.style.display = 'none';
            // Reset for next use
            successMessage.style.display = 'none';
            purchaseForm.style.display = 'block';
          }, 300);
        });
      }
    });
  </script>
  <div class="form-container" id="payment-form-container" style="display: none;">
    <div class="payment-form">
      <div class="form-header">
        <h2>Purchase Your <span id="selected-plan">Monthly</span> Pass</h2>
        <button class="close-btn" id="close-payment">&times;</button>
      </div>
      <form id="pass-purchase-form">
        <div class="form-group">
          <label for="payment-name">Full Name</label>
          <input type="text" id="payment-name" placeholder="Enter your full name" required>
        </div>
        <div class="form-group">
          <label for="payment-email">Email Address</label>
          <input type="email" id="payment-email" placeholder="your.email@example.com" required>
        </div>
        <div class="form-group">
          <label for="payment-phone">Mobile Number</label>
          <input type="tel" id="payment-phone" placeholder="Enter 10-digit mobile number" required>
        </div>
        <div class="form-group">
          <label for="payment-route">Select Route</label>
          <select id="payment-route" required>
            <option value="">-- Select a route --</option>
            <option value="city-local">City Local</option>
            <option value="intercity-express">Ashta</option>
            <option value="airport-shuttle">Islampur</option>
            <option value="all-routes">All Routes</option>
          </select>
        </div>
        <div class="form-group">
          <label>Payment Method</label>
          <div class="payment-methods">
            <div class="payment-method">
              <input type="radio" id="credit-card" name="payment-method" value="credit-card" checked>
              <label for="credit-card">
                <i class="fas fa-credit-card"></i> Credit/Debit Card
              </label>
            </div>
            <div class="payment-method">
              <input type="radio" id="net-banking" name="payment-method" value="net-banking">
              <label for="net-banking">
                <i class="fas fa-university"></i> Net Banking
              </label>
            </div>
            <div class="payment-method">
              <input type="radio" id="upi" name="payment-method" value="upi">
              <label for="upi">
                <i class="fas fa-mobile-alt"></i> UPI
              </label>
            </div>
            <div class="payment-method">
              <input type="radio" id="wallet" name="payment-method" value="wallet">
              <label for="wallet">
                <i class="fas fa-wallet"></i> Mobile Wallet
              </label>
            </div>
          </div>
        </div>

        <!-- Credit Card Details (shown by default) -->
        <div id="credit-card-details">
          <div class="form-group">
            <label for="card-number">Card Number</label>
            <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19">
          </div>
          <div class="form-row">
            <div class="form-group half">
              <label for="expiry-date">Expiry Date</label>
              <input type="text" id="expiry-date" placeholder="MM/YY" maxlength="5">
            </div>
            <div class="form-group half">
              <label for="cvv">CVV</label>
              <input type="text" id="cvv" placeholder="123" maxlength="3">
            </div>
          </div>
          <div class="form-group">
            <label for="card-name">Name on Card</label>
            <input type="text" id="card-name" placeholder="As shown on the card">
          </div>
        </div>

        <!-- UPI Details (hidden by default) -->
        <div id="upi-details" style="display: none;">
          <div class="form-group">
            <label for="upi-id">UPI ID</label>
            <input type="text" id="upi-id" placeholder="yourname@upi">
          </div>
        </div>

        <!-- Net Banking Details (hidden by default) -->
        <div id="net-banking-details" style="display: none;">
          <div class="form-group">
            <label for="bank-name">Select Bank</label>
            <select id="bank-name">
              <option value="">-- Select Bank --</option>
              <option value="sbi">State Bank of India</option>
              <option value="hdfc">HDFC Bank</option>
              <option value="icici">ICICI Bank</option>
              <option value="axis">Axis Bank</option>
              <option value="pnb">Punjab National Bank</option>
            </select>
          </div>
        </div>

        <!-- Wallet Details (hidden by default) -->
        <div id="wallet-details" style="display: none;">
          <div class="form-group">
            <label for="wallet-type">Select Wallet</label>
            <select id="wallet-type">
              <option value="">-- Select Wallet --</option>
              <option value="paytm">Paytm</option>
              <option value="phonepe">PhonePe</option>
              <option value="amazonpay">Amazon Pay</option>
              <option value="googlepay">Google Pay</option>
            </select>
          </div>
          <div class="form-group">
            <label for="wallet-mobile">Registered Mobile Number</label>
            <input type="tel" id="wallet-mobile" placeholder="Enter registered mobile number">
          </div>
        </div>

        <div class="total-amount">
          <div class="amount-label">Total Amount:</div>
          <div class="amount-value" id="total-amount">₹680</div>
        </div>

        <div class="form-group checkbox">
          <input type="checkbox" id="terms-checkbox" required>
          <label for="terms-checkbox">I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy
              Policy</a></label>
        </div>

        <button type="submit" class="submit-btn">Proceed to Payment</button>
      </form>

      <!-- Success Message (hidden by default) -->
      <div class="success-message" style="display: none;">
        <i class="fas fa-check-circle"></i>
        <h3>Payment Successful!</h3>
        <p>Your bus pass has been activated. You can access it from your account dashboard.</p>
        <button class="submit-btn close-success">Go to Dashboard</button>
      </div>
    </div>
  </div>
</body>

</html>
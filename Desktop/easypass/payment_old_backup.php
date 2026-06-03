<?php
/**
 * Payment Processing System
 * This file handles payment processing with multiple payment methods
 */

require_once 'auth_check.php';
require_once 'includes/functions.php';
require_once 'includes/email_notifications.php';
// Check user authentication
checkUserLogin();
$user = getCurrentUser();

$error_message = '';
$success_message = '';

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pass_type_id = (int)($_POST['pass_type_id'] ?? 0);
    $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    
    // Validate input
    if (!$pass_type_id || !$payment_method || !$amount) {
        $error_message = "Invalid payment data provided.";
    } else {
        // Get pass type details
        $pass_type_sql = "SELECT * FROM pass_types WHERE id = ? AND is_active = 1";
        $stmt = $conn->prepare($pass_type_sql);
        $stmt->bind_param("i", $pass_type_id);
        $stmt->execute();
        $pass_type = $stmt->get_result()->fetch_assoc();
        
        if (!$pass_type) {
            $error_message = "Invalid pass type selected.";
        } elseif ($pass_type['price'] != $amount) {
            $error_message = "Amount mismatch. Please try again.";
        } else {
            // Process payment based on method
            $payment_result = processPayment($user['id'], $pass_type, $payment_method);
            
            if ($payment_result['success']) {
                $success_message = "Payment processed successfully! Your pass is now active.";
                
                // Log payment activity
                logUserActivity('payment_processed', "Payment of ₹{$amount} via {$payment_method}");
            } else {
                $error_message = $payment_result['error'];
            }
        }
    }
}

/**
 * Process payment based on method
 */
function processPayment($user_id, $pass_type, $payment_method) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Create payment record
        $payment_result = createPayment(
            $user_id,
            $pass_type['price'],
            $payment_method,
            null,
            "Purchase of {$pass_type['name']}"
        );
        
        if (!$payment_result['success']) {
            throw new Exception($payment_result['error']);
        }
        
        // Simulate payment processing based on method
        $payment_status = simulatePaymentProcessing($payment_method);
        
        if ($payment_status['success']) {
            // Update payment status
            updatePaymentStatus($payment_result['payment_id'], 'completed', $payment_status['response']);
            
            // Create user pass
            $pass_result = createUserPass($user_id, $pass_type['id']);
            
            if (!$pass_result['success']) {
                throw new Exception($pass_result['error']);
            }
            
            // Update payment with pass ID
            $update_sql = "UPDATE payments SET pass_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $pass_result['pass_id'], $payment_result['payment_id']);
            $update_stmt->execute();
            
            // Send confirmation email
            // --------------------------
// Send pass & payment emails
// --------------------------

// include email notifications helper (safe to include here)
if (!function_exists('sendPassPurchaseConfirmationEmail')) {
    require_once __DIR__ . '/includes/email_notifications.php';
}

// Build pass details expected by sendPassPurchaseConfirmationEmail()
$pass_email_details = [
    'user_id'         => $user_id ?? $user['id'],
    'email'           => $user['email'],
    'full_name'       => $user['full_name'] ?? ($user['username'] ?? ''),
    'pass_type_name'  => $pass_type['name'],
    'pass_number'     => $pass_result['pass_number'] ?? ($pass_result['pass_number'] ?? ''),
    'valid_from'      => $pass_result['valid_from'] ?? $pass_result['valid_from'] ?? date('Y-m-d'),
    'valid_until'     => $pass_result['valid_until'] ?? $pass_result['valid_until'] ?? date('Y-m-d', strtotime('+1 month')),
    'amount'          => number_format($pass_type['price'], 2)
];

// Try sending payment confirmation (generic) and pass purchase confirmation (detailed)
try {
    // Payment confirmation (keeps existing behavior)
    $payment_subject = "Payment Confirmation - EasyPass";
    $payment_message = createPaymentConfirmationEmail($user['full_name'], [
        'amount' => $pass_type['price'],
        'method' => ucfirst(str_replace('_', ' ', $payment_method)),
        'transaction_id' => $payment_result['transaction_id'],
        'date' => date('d M, Y H:i')
    ]);
    // send the simpler payment email (optional)
    @sendEmail($user['email'], $payment_subject, $payment_message);

    // Send pass purchase confirmation (detailed pass info)
    $pass_mail_ok = false;
    if (function_exists('sendPassPurchaseConfirmationEmail')) {
        $pass_mail_ok = sendPassPurchaseConfirmationEmail([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'] ?? ($user['username'] ?? ''),
            'pass_type_name' => $pass_type['name'],
            'pass_number' => $pass_result['pass_number'] ?? $pass_result['pass_number'] ?? '',
            'valid_from' => $pass_result['valid_from'] ?? $pass_result['valid_from'] ?? date('Y-m-d'),
            'valid_until' => $pass_result['valid_until'] ?? $pass_result['valid_until'] ?? date('Y-m-d', strtotime('+30 days')),
            'amount' => number_format($pass_type['price'], 2)
        ]);
    }

    // Create notification (in-app)
    createNotification(
        $user['id'],
        'payment_success',
        'Payment Successful',
        "Your {$pass_type['name']} has been activated",
        'email'
    );

    // Log result for debugging
    if ($pass_mail_ok) {
        error_log("Pass purchase email sent to {$user['email']}");
    } else {
        error_log("Pass purchase email NOT sent to {$user['email']}. Check logs/email_debug.log");
    }

} catch (Throwable $e) {
    error_log("Error sending emails after payment: " . $e->getMessage());
}

            
            // Create notification
            createNotification(
                $user_id,
                'payment_success',
                'Payment Successful',
                "Your {$pass_type['name']} has been activated",
                'email'
            );
            
            $conn->commit();
            
            return [
                'success' => true,
                'pass_id' => $pass_result['pass_id'],
                'transaction_id' => $payment_result['transaction_id']
            ];
            
        } else {
            // Update payment status to failed
            updatePaymentStatus($payment_result['payment_id'], 'failed', $payment_status['response']);
            
            $conn->commit();
            
            return [
                'success' => false,
                'error' => $payment_status['error']
            ];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Simulate payment processing
 */
function simulatePaymentProcessing($payment_method) {
    // Simulate different success rates for different payment methods
    $success_rates = [
        'credit_card' => 0.95,
        'debit_card' => 0.90,
        'upi' => 0.98,
        'net_banking' => 0.85,
        'wallet' => 0.92
    ];
    
    $success_rate = $success_rates[$payment_method] ?? 0.90;
    $is_successful = (mt_rand() / mt_getrandmax()) < $success_rates[$payment_method];
    
    if ($is_successful) {
        return [
            'success' => true,
            'response' => [
                'gateway' => 'mock_gateway',
                'transaction_id' => 'TXN' . time() . rand(1000, 9999),
                'status' => 'success',
                'message' => 'Payment processed successfully'
            ]
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Payment failed. Please try again or use a different payment method.',
            'response' => [
                'gateway' => 'mock_gateway',
                'status' => 'failed',
                'message' => 'Payment declined by bank'
            ]
        ];
    }
}

// Get available pass types
$pass_types = getPassTypes();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - EasyPass</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-bus"></i> EasyPass
        </div>
        <div class="user-menu">
            <div class="user-info">Welcome, <?php echo htmlspecialchars($user['username']); ?></div>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </header>

    <div class="dashboard-container">
        <main class="main-content">
            <h1 class="page-title">Payment</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Complete Payment</h2>
                </div>
                
                <form method="POST" class="payment-form">
                    <div class="form-group">
                        <label>Select Pass Type</label>
                        <select name="pass_type_id" id="pass_type_select" required onchange="updateAmount()">
                            <option value="">Choose a pass type</option>
                            <?php foreach ($pass_types as $pass_type): ?>
                            <option value="<?php echo $pass_type['id']; ?>" data-price="<?php echo $pass_type['price']; ?>">
                                <?php echo htmlspecialchars($pass_type['name']); ?> - <?php echo formatCurrency($pass_type['price']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="credit_card" name="payment_method" value="credit_card" required>
                                <label for="credit_card">
                                    <i class="fas fa-credit-card"></i> Credit Card
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="debit_card" name="payment_method" value="debit_card">
                                <label for="debit_card">
                                    <i class="fas fa-credit-card"></i> Debit Card
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="upi" name="payment_method" value="upi">
                                <label for="upi">
                                    <i class="fas fa-mobile-alt"></i> UPI
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="net_banking" name="payment_method" value="net_banking">
                                <label for="net_banking">
                                    <i class="fas fa-university"></i> Net Banking
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="wallet" name="payment_method" value="wallet">
                                <label for="wallet">
                                    <i class="fas fa-wallet"></i> Mobile Wallet
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="payment-details" id="payment_details" style="display: none;">
                        <!-- Credit Card Details -->
                        <div id="credit_card_details" class="payment-method-details">
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="text" id="expiry_date" placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="form-group half">
                                    <label for="cvv">CVV</label>
                                    <input type="text" id="cvv" placeholder="123" maxlength="3">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="card_name">Name on Card</label>
                                <input type="text" id="card_name" placeholder="As shown on the card">
                            </div>
                        </div>

                        <!-- UPI Details -->
                        <div id="upi_details" class="payment-method-details" style="display: none;">
                            <div class="form-group">
                                <label for="upi_id">UPI ID</label>
                                <input type="text" id="upi_id" placeholder="yourname@upi">
                            </div>
                        </div>

                        <!-- Net Banking Details -->
                        <div id="net_banking_details" class="payment-method-details" style="display: none;">
                            <div class="form-group">
                                <label for="bank_name">Select Bank</label>
                                <select id="bank_name">
                                    <option value="">-- Select Bank --</option>
                                    <option value="sbi">State Bank of India</option>
                                    <option value="hdfc">HDFC Bank</option>
                                    <option value="icici">ICICI Bank</option>
                                    <option value="axis">Axis Bank</option>
                                    <option value="pnb">Punjab National Bank</option>
                                </select>
                            </div>
                        </div>

                        <!-- Wallet Details -->
                        <div id="wallet_details" class="payment-method-details" style="display: none;">
                            <div class="form-group">
                                <label for="wallet_type">Select Wallet</label>
                                <select id="wallet_type">
                                    <option value="">-- Select Wallet --</option>
                                    <option value="paytm">Paytm</option>
                                    <option value="phonepe">PhonePe</option>
                                    <option value="amazonpay">Amazon Pay</option>
                                    <option value="googlepay">Google Pay</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Pass Type:</span>
                            <span id="selected_pass_name">-</span>
                        </div>
                        <div class="summary-row">
                            <span>Amount:</span>
                            <span id="payment_amount">₹0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="total_amount">₹0.00</span>
                        </div>
                    </div>

                    <input type="hidden" name="amount" id="amount_input" value="0">

                    <div class="form-group checkbox">
                        <input type="checkbox" id="terms_checkbox" required>
                        <label for="terms_checkbox">I agree to the <a href="#" target="_blank">Terms & Conditions</a> and <a href="#" target="_blank">Privacy Policy</a></label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-credit-card"></i> Proceed to Payment
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Update amount when pass type changes
        function updateAmount() {
            const select = document.getElementById('pass_type_select');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const passName = selectedOption.textContent.split(' - ')[0];
            
            if (price) {
                document.getElementById('selected_pass_name').textContent = passName;
                document.getElementById('payment_amount').textContent = '₹' + parseFloat(price).toFixed(2);
                document.getElementById('total_amount').textContent = '₹' + parseFloat(price).toFixed(2);
                document.getElementById('amount_input').value = price;
            } else {
                document.getElementById('selected_pass_name').textContent = '-';
                document.getElementById('payment_amount').textContent = '₹0.00';
                document.getElementById('total_amount').textContent = '₹0.00';
                document.getElementById('amount_input').value = '0';
            }
        }

        // Show payment details when method is selected
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const paymentDetails = document.getElementById('payment_details');
                const methodDetails = document.querySelectorAll('.payment-method-details');
                
                // Hide all method details
                methodDetails.forEach(detail => detail.style.display = 'none');
                
                // Show payment details container
                paymentDetails.style.display = 'block';
                
                // Show selected method details
                const selectedMethod = this.value;
                const methodElement = document.getElementById(selectedMethod + '_details');
                if (methodElement) {
                    methodElement.style.display = 'block';
                }
            });
        });

        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
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
        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Form submission with loading state
        document.querySelector('.payment-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>

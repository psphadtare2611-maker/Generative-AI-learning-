<?php
/**
 * New Payment System with Email Collection
 * User provides email and receives pass details after payment
 */

require_once 'db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/email_notifications.php';

session_start();

$error_message = '';
$success_message = '';
$email_sent = false;

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pass_type_id = (int)($_POST['pass_type_id'] ?? 0);
    $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
    $user_email = sanitizeInput($_POST['user_email'] ?? '');
    $user_name = sanitizeInput($_POST['user_name'] ?? '');
    $user_phone = sanitizeInput($_POST['user_phone'] ?? '');
    
    // Validate input
    if (!$pass_type_id || !$payment_method || !$user_email || !$user_name) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Get pass type details
        $pass_type_sql = "SELECT * FROM pass_types WHERE id = ? AND is_active = 1";
        $stmt = $conn->prepare($pass_type_sql);
        $stmt->bind_param("i", $pass_type_id);
        $stmt->execute();
        $pass_type = $stmt->get_result()->fetch_assoc();
        
        if (!$pass_type) {
            $error_message = "Invalid pass type selected.";
        } else {
            try {
                $conn->begin_transaction();
                
                // Create user record if not exists (guest purchase)
                $user_check_sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
                $user_check_stmt = $conn->prepare($user_check_sql);
                $user_check_stmt->bind_param("s", $user_email);
                $user_check_stmt->execute();
                $existing_user = $user_check_stmt->get_result()->fetch_assoc();
                
                if ($existing_user) {
                    $user_id = $existing_user['id'];
                } else {
                    // Create guest user
                    $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $username = 'guest_' . time();
                    $insert_user_sql = "INSERT INTO users (username, email, full_name, phone, password, is_active) VALUES (?, ?, ?, ?, ?, 1)";
                    $insert_user_stmt = $conn->prepare($insert_user_sql);
                    $insert_user_stmt->bind_param("sssss", $username, $user_email, $user_name, $user_phone, $password_hash);
                    $insert_user_stmt->execute();
                    $user_id = $conn->insert_id;
                }
                
                // Create payment record
                $transaction_id = 'TXN' . time() . rand(1000, 9999);
                $payment_sql = "INSERT INTO payments (user_id, amount, payment_method, transaction_id, payment_status, description) VALUES (?, ?, ?, ?, 'completed', ?)";
                $payment_stmt = $conn->prepare($payment_sql);
                $description = "Purchase of {$pass_type['name']}";
                $payment_stmt->bind_param("idsss", $user_id, $pass_type['price'], $payment_method, $transaction_id, $description);
                $payment_stmt->execute();
                $payment_id = $conn->insert_id;
                
                // Create user pass
                $pass_result = createUserPass($user_id, $pass_type_id, $pass_type['price']);
                
                if (!$pass_result['success']) {
                    throw new Exception($pass_result['error']);
                }
                
                // Update payment with pass ID
                $update_payment_sql = "UPDATE payments SET pass_id = ? WHERE id = ?";
                $update_payment_stmt = $conn->prepare($update_payment_sql);
                $update_payment_stmt->bind_param("ii", $pass_result['pass_id'], $payment_id);
                $update_payment_stmt->execute();
                
                $conn->commit();
                
                // Send email with pass details
                $pass_details = [
                    'user_id' => $user_id,
                    'email' => $user_email,
                    'full_name' => $user_name,
                    'pass_type_name' => $pass_type['name'],
                    'pass_number' => $pass_result['pass_number'],
                    'valid_from' => date('Y-m-d'),
                    'valid_until' => $pass_result['valid_until'],
                    'amount' => number_format($pass_type['price'], 2)
                ];
                
                $email_sent = sendPassPurchaseConfirmationEmail($pass_details);
                
                if ($email_sent) {
                    $success_message = "Payment successful! Your pass details have been sent to {$user_email}";
                } else {
                    $success_message = "Payment successful! However, we couldn't send the email. Please contact support with transaction ID: {$transaction_id}";
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Payment failed: " . $e->getMessage();
            }
        }
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
    <title>Purchase Bus Pass - EasyPass</title>
    <link rel="stylesheet" href="styles/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #1a73e8;
            background-color: #f0f7ff;
        }
        .payment-method input[type="radio"] {
            margin-right: 8px;
        }
        .payment-method.selected {
            border-color: #1a73e8;
            background-color: #f0f7ff;
        }
        .btn-primary {
            background: linear-gradient(to right, #1a73e8, #4285f4);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .price-summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .price-row.total {
            font-size: 20px;
            font-weight: bold;
            color: #1a73e8;
            padding-top: 10px;
            border-top: 2px solid #ddd;
        }
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-bus"></i> EasyPass
        </div>
        <div class="user-menu">
            <a href="index.php" class="btn btn-secondary">Home</a>
        </div>
    </header>

    <div class="payment-container">
        <h1 style="text-align: center; margin-bottom: 30px;">Purchase Bus Pass</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                <div>
                    <strong>Success!</strong><br>
                    <?php echo $success_message; ?>
                    <?php if ($email_sent): ?>
                        <br><small>Please check your email inbox (and spam folder) for pass details.</small>
                    <?php endif; ?>
                </div>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" class="btn btn-primary" style="width: auto; display: inline-block;">Return to Home</a>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
                <div>
                    <strong>Error!</strong><br>
                    <?php echo $error_message; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success_message): ?>
        <div class="card">
            <form method="POST" id="payment-form">
                <h3 style="margin-bottom: 20px;">Your Information</h3>
                
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="user_name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="user_email" placeholder="your.email@example.com" required>
                    <small style="color: #666;">Your pass details will be sent to this email</small>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="user_phone" placeholder="10-digit mobile number" pattern="[0-9]{10}">
                </div>

                <hr style="margin: 30px 0;">

                <h3 style="margin-bottom: 20px;">Select Pass Type</h3>
                
                <div class="form-group">
                    <label>Pass Type <span class="required">*</span></label>
                    <select name="pass_type_id" id="pass_type_select" required onchange="updatePrice()">
                        <option value="">Choose a pass type</option>
                        <?php foreach ($pass_types as $pass_type): ?>
                        <option value="<?php echo $pass_type['id']; ?>" 
                                data-price="<?php echo $pass_type['price']; ?>"
                                data-name="<?php echo htmlspecialchars($pass_type['name']); ?>"
                                data-duration="<?php echo $pass_type['duration_days']; ?>">
                            <?php echo htmlspecialchars($pass_type['name']); ?> - 
                            ₹<?php echo number_format($pass_type['price'], 2); ?> 
                            (<?php echo $pass_type['duration_days']; ?> days)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="price-summary" id="price-summary" style="display: none;">
                    <div class="price-row">
                        <span>Pass Type:</span>
                        <span id="selected-pass-name">-</span>
                    </div>
                    <div class="price-row">
                        <span>Duration:</span>
                        <span id="selected-duration">-</span>
                    </div>
                    <div class="price-row">
                        <span>Price:</span>
                        <span id="selected-price">₹0.00</span>
                    </div>
                    <div class="price-row total">
                        <span>Total Amount:</span>
                        <span id="total-amount">₹0.00</span>
                    </div>
                </div>

                <hr style="margin: 30px 0;">

                <h3 style="margin-bottom: 20px;">Payment Method</h3>
                
                <div class="payment-methods">
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="credit_card" required>
                        <i class="fas fa-credit-card" style="font-size: 24px; color: #1a73e8;"></i>
                        <div>Credit Card</div>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="debit_card">
                        <i class="fas fa-credit-card" style="font-size: 24px; color: #1a73e8;"></i>
                        <div>Debit Card</div>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="upi">
                        <i class="fas fa-mobile-alt" style="font-size: 24px; color: #1a73e8;"></i>
                        <div>UPI</div>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="net_banking">
                        <i class="fas fa-university" style="font-size: 24px; color: #1a73e8;"></i>
                        <div>Net Banking</div>
                    </label>
                </div>

                <div style="margin-top: 30px;">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" required>
                        <span>I agree to the Terms & Conditions and Privacy Policy</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 30px;">
                    <i class="fas fa-lock"></i> Complete Payment
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Update price when pass type changes
        function updatePrice() {
            const select = document.getElementById('pass_type_select');
            const selectedOption = select.options[select.selectedIndex];
            const summary = document.getElementById('price-summary');
            
            if (selectedOption.value) {
                const price = parseFloat(selectedOption.getAttribute('data-price'));
                const name = selectedOption.getAttribute('data-name');
                const duration = selectedOption.getAttribute('data-duration');
                
                document.getElementById('selected-pass-name').textContent = name;
                document.getElementById('selected-duration').textContent = duration + ' days';
                document.getElementById('selected-price').textContent = '₹' + price.toFixed(2);
                document.getElementById('total-amount').textContent = '₹' + price.toFixed(2);
                
                summary.style.display = 'block';
            } else {
                summary.style.display = 'none';
            }
        }

        // Highlight selected payment method
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Form submission with loading state
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>

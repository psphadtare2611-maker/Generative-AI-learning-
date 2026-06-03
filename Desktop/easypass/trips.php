<?php
/**
 * Trip Management System
 * This file handles trip recording, viewing, and management
 */

require_once 'auth_check.php';
require_once 'includes/functions.php';

// Check user authentication
checkUserLogin();
$user = getCurrentUser();

// Handle trip recording
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'record_trip') {
        $route_id = (int)$_POST['route_id'];
        $from_stop = sanitizeInput($_POST['from_stop']);
        $to_stop = sanitizeInput($_POST['to_stop']);
        $fare = (float)$_POST['fare'];
        $pass_id = !empty($_POST['pass_id']) ? (int)$_POST['pass_id'] : null;
        
        // Validate input
        if (!$route_id || !$from_stop || !$to_stop || !$fare) {
            $error_message = "Please fill in all required fields.";
        } else {
            // Check if user has valid pass for this route
            if ($pass_id && !isPassValidForRoute($pass_id, $route_id)) {
                $error_message = "Your pass is not valid for this route.";
            } else {
                // Record the trip
                if (recordTrip($user['id'], $route_id, $from_stop, $to_stop, $fare, $pass_id)) {
                    $success_message = "Trip recorded successfully!";
                    
                    // Log activity
                    logUserActivity('trip_recorded', "Trip from {$from_stop} to {$to_stop}");
                } else {
                    $error_message = "Failed to record trip. Please try again.";
                }
            }
        }
    }
}

// Get user's trips with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$trips_sql = "SELECT t.*, r.route_number, r.route_name, r.fare as route_fare
              FROM trips t 
              JOIN routes r ON t.route_id = r.id 
              WHERE t.user_id = ? 
              ORDER BY t.trip_date DESC 
              LIMIT ? OFFSET ?";
$trips_stmt = $conn->prepare($trips_sql);
$trips_stmt->bind_param("iii", $user['id'], $limit, $offset);
$trips_stmt->execute();
$trips = $trips_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total trips count for pagination
$count_sql = "SELECT COUNT(*) as total FROM trips WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user['id']);
$count_stmt->execute();
$total_trips = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_trips / $limit);

// Get available routes
$routes_sql = "SELECT * FROM routes WHERE is_active = 1 ORDER BY route_number";
$routes_result = $conn->query($routes_sql);
$routes = $routes_result->fetch_all(MYSQLI_ASSOC);

// Get user's active passes
$active_passes = getUserPasses($user['id']);
$active_passes = array_filter($active_passes, function($pass) {
    return $pass['status'] === 'active' && $pass['valid_until'] >= date('Y-m-d');
});

// Get trip statistics
$stats_sql = "SELECT 
                COUNT(*) as total_trips,
                SUM(fare) as total_spent,
                AVG(fare) as avg_fare,
                COUNT(DISTINCT route_id) as routes_used
              FROM trips 
              WHERE user_id = ? AND status = 'completed'";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $user['id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Management - EasyPass</title>
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
            <h1 class="page-title">Trip Management</h1>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Trip Statistics -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Total Trips</h2>
                        <div class="card-icon primary">
                            <i class="fas fa-bus"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $stats['total_trips'] ?? 0; ?></div>
                    <p class="card-description">All time</p>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Total Spent</h2>
                        <div class="card-icon accent">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></div>
                    <p class="card-description">All time</p>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Average Fare</h2>
                        <div class="card-icon success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo formatCurrency($stats['avg_fare'] ?? 0); ?></div>
                    <p class="card-description">Per trip</p>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Routes Used</h2>
                        <div class="card-icon danger">
                            <i class="fas fa-route"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $stats['routes_used'] ?? 0; ?></div>
                    <p class="card-description">Different routes</p>
                </div>
            </div>

            <!-- Record New Trip -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Record New Trip</h2>
                </div>
                <form method="POST" class="trip-form">
                    <input type="hidden" name="action" value="record_trip">
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="route_id">Route *</label>
                            <select name="route_id" id="route_id" required onchange="updateFare()">
                                <option value="">Select Route</option>
                                <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['id']; ?>" data-fare="<?php echo $route['fare']; ?>">
                                    <?php echo htmlspecialchars($route['route_number']); ?> - <?php echo htmlspecialchars($route['route_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label for="fare">Fare *</label>
                            <input type="number" name="fare" id="fare" step="0.01" min="0" required readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="from_stop">From Stop *</label>
                            <input type="text" name="from_stop" id="from_stop" required>
                        </div>
                        <div class="form-group half">
                            <label for="to_stop">To Stop *</label>
                            <input type="text" name="to_stop" id="to_stop" required>
                        </div>
                    </div>
                    
                    <?php if (!empty($active_passes)): ?>
                    <div class="form-group">
                        <label for="pass_id">Use Pass (Optional)</label>
                        <select name="pass_id" id="pass_id">
                            <option value="">Pay with cash</option>
                            <?php foreach ($active_passes as $pass): ?>
                            <option value="<?php echo $pass['id']; ?>">
                                <?php echo htmlspecialchars($pass['pass_type_name']); ?> (Valid until <?php echo formatDate($pass['valid_until']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Record Trip
                    </button>
                </form>
            </div>

            <!-- Trip History -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Trip History</h2>
                </div>
                
                <?php if (empty($trips)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bus"></i>
                        <h3>No Trips Found</h3>
                        <p>Your trip history will appear here once you start recording trips.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Route</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Fare</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trips as $trip): ?>
                            <tr>
                                <td><?php echo formatDate($trip['trip_date'], 'd M, Y H:i'); ?></td>
                                <td><?php echo htmlspecialchars($trip['route_number']); ?></td>
                                <td><?php echo htmlspecialchars($trip['from_stop']); ?></td>
                                <td><?php echo htmlspecialchars($trip['to_stop']); ?></td>
                                <td><?php echo formatCurrency($trip['fare']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $trip['status'] === 'completed' ? 'success' : 
                                            ($trip['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($trip['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="viewTripDetails(<?php echo $trip['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Update fare when route changes
        function updateFare() {
            const routeSelect = document.getElementById('route_id');
            const selectedOption = routeSelect.options[routeSelect.selectedIndex];
            const fare = selectedOption.getAttribute('data-fare');
            
            if (fare) {
                document.getElementById('fare').value = fare;
            } else {
                document.getElementById('fare').value = '';
            }
        }

        // View trip details (placeholder)
        function viewTripDetails(tripId) {
            alert('Trip details for ID: ' + tripId + '\n\nThis feature will show detailed trip information.');
        }

        // Form validation
        document.querySelector('.trip-form').addEventListener('submit', function(e) {
            const fromStop = document.getElementById('from_stop').value.trim();
            const toStop = document.getElementById('to_stop').value.trim();
            
            if (fromStop === toStop) {
                e.preventDefault();
                alert('From and To stops cannot be the same.');
                return false;
            }
        });
    </script>
</body>
</html>

<?php
// resident/request_service.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get service ID from URL
$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Fetch service details
$query = "SELECT * FROM services WHERE id = :id AND is_active = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $service_id);
$stmt->execute();
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    $_SESSION['error'] = "Service not found or unavailable.";
    header('Location: ../index.php?page=services');
    exit();
}

// Function to get next occurrence of a day
function getNextDayDate($dayName) {
    $days = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6
    ];
    
    $today = new DateTime();
    $currentDayOfWeek = $today->format('N'); // 1=Monday, 7=Sunday
    
    $targetDay = $days[$dayName];
    
    if ($targetDay >= $currentDayOfWeek) {
        $daysToAdd = $targetDay - $currentDayOfWeek;
    } else {
        $daysToAdd = (7 - $currentDayOfWeek) + $targetDay;
    }
    
    $nextDate = clone $today;
    $nextDate->modify("+$daysToAdd days");
    
    return $nextDate->format('Y-m-d');
}

// Predefined schedule (Monday to Saturday)
$schedule_days = [
    ['day' => 'Monday', 'time_slots' => ['9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM']],
    ['day' => 'Tuesday', 'time_slots' => ['9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM']],
    ['day' => 'Wednesday', 'time_slots' => ['9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM']],
    ['day' => 'Thursday', 'time_slots' => ['9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM']],
    ['day' => 'Friday', 'time_slots' => ['9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM']],
    ['day' => 'Saturday', 'time_slots' => ['9:00 AM - 10:00 AM', '10:00 AM - 11:00 AM', '1:00 PM - 2:00 PM', '2:00 PM - 3:00 PM']]
];

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_type = 'walk-in'; // Always set to walk-in
    $preferred_day = $_POST['preferred_day'] ?? '';
    $preferred_time = $_POST['preferred_time'] ?? '';
    
    // Calculate the actual date based on selected day
    $preferred_date = '';
    if (!empty($preferred_day)) {
        $preferred_date = getNextDayDate($preferred_day);
    }
    
    if (empty($preferred_day)) {
        $error = "Please select a preferred day.";
    } elseif (empty($preferred_time)) {
        $error = "Please select a preferred time slot.";
    } else {
        try {
            // Insert request with walk-in type
            $insert_query = "INSERT INTO resident_requests (user_id, service_id, request_type, preferred_day, preferred_time, preferred_date, status) 
                             VALUES (:user_id, :service_id, :type, :preferred_day, :preferred_time, :preferred_date, 'pending')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $_SESSION['user_id']);
            $insert_stmt->bindParam(':service_id', $service_id);
            $insert_stmt->bindParam(':type', $request_type);
            $insert_stmt->bindParam(':preferred_day', $preferred_day);
            $insert_stmt->bindParam(':preferred_time', $preferred_time);
            $insert_stmt->bindParam(':preferred_date', $preferred_date);
            $insert_stmt->execute();
            
            $request_id = $db->lastInsertId();
            
            // Generate unique QR token
            $token = 'REQ-' . str_pad($request_id, 6, '0', STR_PAD_LEFT) . '-' . bin2hex(random_bytes(4));
            
            // Update the request with the token
            $update_token = "UPDATE resident_requests SET qr_token = :token WHERE id = :id";
            $update_stmt = $db->prepare($update_token);
            $update_stmt->bindParam(':token', $token);
            $update_stmt->bindParam(':id', $request_id);
            $update_stmt->execute();
            
            $success = "Your walk-in appointment has been scheduled successfully!";
            $_SESSION['last_request_token'] = $token;
            $_SESSION['last_request_id'] = $request_id;
        } catch (Exception $e) {
            $error = "Failed to submit request: " . $e->getMessage();
        }
    }
}

$page_title = "Request Service - " . htmlspecialchars($service['service_name']);
include '../includes/header.php';
?>

<style>
    .container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid #eef2f6;
    }
    
    .service-info {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1.5rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        border: 1px solid #e2e8f0;
    }
    
    .service-info h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .service-info .service-description {
        color: #475569;
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    
    .service-meta {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
    }
    
    .meta-item {
        flex: 1;
        text-align: center;
        padding: 0.5rem;
        background: white;
        border-radius: 12px;
    }
    
    .meta-item i {
        color: #667eea;
        font-size: 1rem;
        margin-bottom: 0.3rem;
        display: block;
    }
    
    .meta-item .meta-label {
        font-size: 0.7rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .meta-item .meta-value {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.9rem;
        margin-top: 0.2rem;
    }
    
    .requirements-section {
        background: #fef3c7;
        border-radius: 16px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #f59e0b;
    }
    
    .requirements-section h4 {
        font-size: 0.85rem;
        font-weight: 700;
        color: #92400e;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .schedule-section {
        background: #f0f9ff;
        border-radius: 16px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: 1px solid #bae6fd;
    }
    
    .schedule-section h4 {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0369a1;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .schedule-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 0.5rem;
    }
    
    .schedule-tab {
        padding: 0.5rem 1rem;
        background: #f1f5f9;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    
    .schedule-tab.active {
        background: #667eea;
        color: white;
    }
    
    .time-slots {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .time-slot {
        padding: 0.5rem 1rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    
    .time-slot:hover {
        border-color: #667eea;
        background: #f0f4ff;
    }
    
    .time-slot.selected {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .selected-info {
        margin-top: 1rem;
        padding: 0.75rem;
        background: #e6f7e6;
        border-radius: 10px;
        border-left: 4px solid #10b981;
        font-size: 0.85rem;
        color: #065f46;
    }
    
    .selected-info i {
        margin-right: 0.5rem;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }
    
    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border-left: 4px solid #10b981;
    }
    
    .btn-group {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
        cursor: pointer;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        flex: 1;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.35);
    }
    
    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        flex: 1;
    }
    
    .btn-secondary:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .success-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .container {
            margin: 1rem;
            padding: 1.5rem;
        }
        
        .btn-group {
            flex-direction: column;
        }
        
        .schedule-tabs {
            justify-content: center;
        }
        
        .time-slots {
            justify-content: center;
        }
    }
</style>

<div class="container">
    <h1 style="font-size: 1.8rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem;">Schedule Walk-in Appointment</h1>
    
    <div class="service-info">
        <h2><?php echo htmlspecialchars($service['service_name']); ?></h2>
        <div class="service-description">
            <?php echo nl2br(htmlspecialchars($service['description'])); ?>
        </div>
        
        <div class="service-meta">
            <div class="meta-item">
                <i class="fas fa-tag"></i>
                <div class="meta-label">Fee</div>
                <div class="meta-value">
                    <?php echo $service['fee'] > 0 ? '₱' . number_format($service['fee'], 2) : 'Free'; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($service['requirements'])): ?>
        <div class="requirements-section">
            <h4><i class="fas fa-clipboard-list"></i> Requirements</h4>
            <p><?php echo nl2br(htmlspecialchars($service['requirements'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
        <div class="success-actions">
            <a href="../index.php?page=services" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Services
            </a>
            <a href="my_requests.php" class="btn btn-primary">
                <i class="fas fa-file-alt"></i> View My Requests
            </a>
            <?php if (isset($_SESSION['last_request_id'])): ?>
            <a href="view_request_qr.php?id=<?php echo $_SESSION['last_request_id']; ?>" class="btn btn-primary">
                <i class="fas fa-qrcode"></i> View QR Code
            </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <form method="POST" action="" id="requestForm">
            <!-- Walk-in Appointment Schedule Section -->
            <div class="schedule-section">
                <h4><i class="fas fa-calendar-alt"></i> Select Your Preferred Schedule</h4>
                <p style="font-size: 0.85rem; color: #475569; margin-bottom: 1rem;">
                    <i class="fas fa-walking"></i> Please select your preferred day and time for your walk-in appointment.
                </p>
                <div class="schedule-tabs" id="scheduleTabs">
                    <?php foreach ($schedule_days as $index => $day): ?>
                    <button type="button" class="schedule-tab <?php echo $index === 0 ? 'active' : ''; ?>" data-day="<?php echo $day['day']; ?>">
                        <?php echo $day['day']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="preferred_day" id="preferredDay" value="Monday">
                <input type="hidden" name="preferred_time" id="preferredTime" value="">
                <div id="timeSlotsContainer">
                    <?php foreach ($schedule_days as $index => $day): ?>
                    <div class="time-slots" id="timeSlots-<?php echo $day['day']; ?>" style="display: <?php echo $index === 0 ? 'flex' : 'none'; ?>">
                        <?php foreach ($day['time_slots'] as $time): ?>
                        <div class="time-slot" data-time="<?php echo $time; ?>"><?php echo $time; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <small style="color: #64748b; display: block; margin-top: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Available Monday to Saturday, excluding Sunday
                </small>
                
                <!-- Selected schedule info display -->
                <div id="selectedInfo" class="selected-info" style="display: none;">
                    <i class="fas fa-calendar-check"></i>
                    <span id="selectedInfoText"></span>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Schedule Appointment
                </button>
                <a href="../index.php?page=services" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    // Function to get next occurrence date for a day
    function getNextDateForDay(dayName) {
        const days = {
            'Monday': 1,
            'Tuesday': 2,
            'Wednesday': 3,
            'Thursday': 4,
            'Friday': 5,
            'Saturday': 6
        };
        
        const today = new Date();
        const currentDay = today.getDay(); // 0=Sunday, 1=Monday, 6=Saturday
        
        const targetDayNum = days[dayName];
        let currentDayNum = currentDay === 0 ? 7 : currentDay; // Convert Sunday to 7
        
        let daysToAdd;
        if (targetDayNum >= currentDayNum) {
            daysToAdd = targetDayNum - currentDayNum;
        } else {
            daysToAdd = (7 - currentDayNum) + targetDayNum;
        }
        
        const nextDate = new Date(today);
        nextDate.setDate(today.getDate() + daysToAdd);
        
        return nextDate.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }
    
    // Update selected info display
    function updateSelectedInfo() {
        const selectedDay = preferredDayInput.value;
        const selectedTime = preferredTimeInput.value;
        const selectedInfoDiv = document.getElementById('selectedInfo');
        const selectedInfoText = document.getElementById('selectedInfoText');
        
        if (selectedDay && selectedTime) {
            const nextDate = getNextDateForDay(selectedDay);
            selectedInfoText.innerHTML = `You have selected: <strong>${selectedDay}</strong>, <strong>${selectedTime}</strong> on <strong>${nextDate}</strong>`;
            selectedInfoDiv.style.display = 'block';
        } else if (selectedDay && !selectedTime) {
            const nextDate = getNextDateForDay(selectedDay);
            selectedInfoText.innerHTML = `You have selected: <strong>${selectedDay}</strong>. Please select a time slot.`;
            selectedInfoDiv.style.display = 'block';
        } else {
            selectedInfoDiv.style.display = 'none';
        }
    }
    
    // Schedule tab switching
    const tabs = document.querySelectorAll('.schedule-tab');
    const timeSlotContainers = {};
    
    <?php foreach ($schedule_days as $day): ?>
    timeSlotContainers['<?php echo $day['day']; ?>'] = document.getElementById('timeSlots-<?php echo $day['day']; ?>');
    <?php endforeach; ?>
    
    const preferredDayInput = document.getElementById('preferredDay');
    const preferredTimeInput = document.getElementById('preferredTime');
    
    let selectedDay = 'Monday';
    let selectedTime = '';
    
    // Handle tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding time slots
            const day = this.getAttribute('data-day');
            selectedDay = day;
            preferredDayInput.value = day;
            
            // Hide all time slot containers
            Object.keys(timeSlotContainers).forEach(key => {
                if (timeSlotContainers[key]) {
                    timeSlotContainers[key].style.display = 'none';
                }
            });
            
            // Show selected container
            if (timeSlotContainers[day]) {
                timeSlotContainers[day].style.display = 'flex';
            }
            
            // Clear selected time if switching days
            if (selectedTime) {
                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.classList.remove('selected');
                });
                selectedTime = '';
                preferredTimeInput.value = '';
            }
            
            updateSelectedInfo();
        });
    });
    
    // Handle time slot selection
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.addEventListener('click', function() {
            // Remove selected class from all time slots in current container
            const currentContainer = this.closest('.time-slots');
            currentContainer.querySelectorAll('.time-slot').forEach(s => {
                s.classList.remove('selected');
            });
            
            // Add selected class to clicked slot
            this.classList.add('selected');
            
            // Set selected time
            selectedTime = this.getAttribute('data-time');
            preferredTimeInput.value = selectedTime;
            
            updateSelectedInfo();
        });
    });
    
    // Initial update
    updateSelectedInfo();
    
    // Form validation
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        const preferredDay = preferredDayInput.value;
        const preferredTime = preferredTimeInput.value;
        
        if (!preferredDay) {
            e.preventDefault();
            alert('Please select a preferred day.');
            return false;
        }
        
        if (!preferredTime) {
            e.preventDefault();
            alert('Please select a time slot for your appointment.');
            return false;
        }
        
        return true;
    });
</script>

<?php include '../includes/footer.php'; ?>
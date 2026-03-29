<?php
// admin/residents/get_resident.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if database connection is successful
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid resident ID']);
    exit();
}

try {
    // Query to get all resident information from both tables
    $query = "SELECT 
                u.id,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.address as user_address,
                u.contact_number as user_contact,
                u.profile_pic,
                u.created_at,
                u.user_type,
                ri.id as info_id,
                ri.middle_name,
                ri.suffix,
                ri.birth_date,
                ri.birth_place,
                ri.age,
                ri.gender,
                ri.civil_status,
                ri.citizenship,
                ri.occupation,
                ri.contact_number as resident_contact,
                ri.address as resident_address,
                ri.barangay,
                ri.city,
                ri.province,
                ri.zip_code,
                ri.emergency_contact_name,
                ri.emergency_contact_number,
                ri.emergency_contact_relation
              FROM users u
              LEFT JOIN resident_info ri ON u.id = ri.user_id
              WHERE u.id = :id AND u.user_type = 'resident'";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resident) {
        http_response_code(404);
        echo json_encode(['error' => 'Resident not found']);
        exit();
    }

    // Determine which contact and address to use (prefer resident_info data)
    $contact_number = !empty($resident['resident_contact']) ? $resident['resident_contact'] : ($resident['user_contact'] ?? '');
    $address = !empty($resident['resident_address']) ? $resident['resident_address'] : ($resident['user_address'] ?? '');
    
    // Build full address if barangay, city, province are available
    $address_parts = [];
    if (!empty($address)) $address_parts[] = $address;
    if (!empty($resident['barangay'])) $address_parts[] = $resident['barangay'];
    if (!empty($resident['city'])) $address_parts[] = $resident['city'];
    if (!empty($resident['province'])) $address_parts[] = $resident['province'];
    if (!empty($resident['zip_code'])) $address_parts[] = $resident['zip_code'];
    $full_address = !empty($address_parts) ? implode(', ', $address_parts) : '';
    
    // Calculate age if birth date is provided and age is not set
    $age = $resident['age'];
    if (empty($age) && !empty($resident['birth_date']) && $resident['birth_date'] != '0000-00-00') {
        $birth = new DateTime($resident['birth_date']);
        $today = new DateTime('today');
        $age = $birth->diff($today)->y;
    }
    
    // Format birth date for display
    $birth_date = $resident['birth_date'];
    if (!empty($birth_date) && $birth_date != '0000-00-00') {
        $birth_date_obj = new DateTime($birth_date);
        $birth_date = $birth_date_obj->format('Y-m-d');
    }
    
    // Prepare response with all resident information
    $response = [
        'id' => $resident['id'],
        'username' => $resident['username'] ?? '',
        'email' => $resident['email'] ?? '',
        'first_name' => $resident['first_name'] ?? '',
        'last_name' => $resident['last_name'] ?? '',
        'full_name' => trim(($resident['first_name'] ?? '') . ' ' . ($resident['last_name'] ?? '')),
        'middle_name' => $resident['middle_name'] ?? '',
        'suffix' => $resident['suffix'] ?? '',
        'profile_pic' => $resident['profile_pic'] ?? '',
        'created_at' => $resident['created_at'] ?? '',
        
        // Contact Information
        'contact_number' => $contact_number ?: 'N/A',
        
        // Address Information
        'address' => $address ?: 'N/A',
        'barangay' => $resident['barangay'] ?? '',
        'city' => $resident['city'] ?? '',
        'province' => $resident['province'] ?? '',
        'zip_code' => $resident['zip_code'] ?? '',
        'full_address' => $full_address ?: 'N/A',
        
        // Personal Information
        'birth_date' => $birth_date ?: '',
        'birth_place' => $resident['birth_place'] ?? '',
        'age' => $age ?: '',
        'gender' => $resident['gender'] ?? '',
        'civil_status' => $resident['civil_status'] ?? '',
        'citizenship' => $resident['citizenship'] ?? 'Filipino',
        'occupation' => $resident['occupation'] ?? '',
        
        // Emergency Contact
        'emergency_contact_name' => $resident['emergency_contact_name'] ?? '',
        'emergency_contact_number' => $resident['emergency_contact_number'] ?? '',
        'emergency_contact_relation' => $resident['emergency_contact_relation'] ?? ''
    ];

    // Set header and return JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in get_resident.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in get_resident.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
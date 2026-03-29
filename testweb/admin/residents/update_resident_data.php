<?php
// admin/residents/update_resident_data.php
// Temporary file to update resident data

session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die('Access denied');
}

$database = new Database();
$db = $database->getConnection();

// Get all residents
$query = "SELECT id, first_name, last_name FROM users WHERE user_type = 'resident'";
$stmt = $db->prepare($query);
$stmt->execute();
$residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Update Resident Information</h2>";
echo "<form method='POST'>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Contact Number</th><th>Address</th><th>Action</th></tr>";

foreach ($residents as $resident) {
    // Get existing info
    $info_query = "SELECT contact_number, address FROM resident_info WHERE user_id = :user_id";
    $info_stmt = $db->prepare($info_query);
    $info_stmt->bindParam(':user_id', $resident['id']);
    $info_stmt->execute();
    $info = $info_stmt->fetch(PDO::FETCH_ASSOC);
    
    $contact = $info['contact_number'] ?? '';
    $address = $info['address'] ?? '';
    
    echo "<tr>";
    echo "<td>{$resident['id']}</td>";
    echo "<td>{$resident['first_name']} {$resident['last_name']}</td>";
    echo "<td><input type='text' name='contact[{$resident['id']}]' value='{$contact}'></td>";
    echo "<td><input type='text' name='address[{$resident['id']}]' value='{$address}' style='width:300px'></td>";
    echo "<td><button type='submit' name='update' value='{$resident['id']}'>Update</button></td>";
    echo "</tr>";
}

echo "</table>";
echo "</form>";

if (isset($_POST['update'])) {
    $user_id = $_POST['update'];
    $contact = $_POST['contact'][$user_id] ?? '';
    $address = $_POST['address'][$user_id] ?? '';
    
    $update_query = "INSERT INTO resident_info (user_id, contact_number, address) 
                     VALUES (:user_id, :contact, :address)
                     ON DUPLICATE KEY UPDATE
                     contact_number = VALUES(contact_number),
                     address = VALUES(address)";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':user_id', $user_id);
    $update_stmt->bindParam(':contact', $contact);
    $update_stmt->bindParam(':address', $address);
    
    if ($update_stmt->execute()) {
        echo "<p style='color:green'>Updated successfully! <a href='information.php'>Go back to Resident Information</a></p>";
    } else {
        echo "<p style='color:red'>Update failed!</p>";
    }
}
?>
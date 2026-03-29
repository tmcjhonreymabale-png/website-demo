<?php
// setup_admin.php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Function to create admin account
function createAdmin($db, $username, $password, $email, $first_name, $last_name, $role) {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $check = "SELECT id FROM admins WHERE username = :username OR email = :email";
    $checkStmt = $db->prepare($check);
    $checkStmt->bindParam(':username', $username);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        echo "Admin $username already exists. Skipping...<br>";
        return;
    }
    
    // Insert new admin
    $query = "INSERT INTO admins (username, password, email, first_name, last_name, role) 
              VALUES (:username, :password, :email, :first_name, :last_name, :role)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        echo "Admin $username created successfully!<br>";
    } else {
        echo "Error creating admin $username<br>";
    }
}

// Create admin accounts
echo "Setting up admin accounts...<br><br>";

createAdmin($db, 'mainadmin', 'admin123', 'mainadmin@barangay.com', 'Main', 'Admin', 'Main Admin');
createAdmin($db, 'staffadmin', 'admin123', 'staff@barangay.com', 'Staff', 'Admin', 'Staff Admin');
createAdmin($db, 'subadmin', 'admin123', 'sub@barangay.com', 'Sub', 'Admin', 'Sub Admin');

echo "<br>Setup complete!";
?>
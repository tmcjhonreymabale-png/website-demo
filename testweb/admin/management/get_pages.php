<?php
// admin/management/get_page.php
require_once '../../config/database.php';

if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT * FROM pages WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$page = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode($page);
?>
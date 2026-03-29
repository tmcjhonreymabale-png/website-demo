<?php
// admin/management/carousel.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';
require_once '../includes/permissions.php';

// Check if user is Main Admin
if (!isMainAdmin()) {
    $_SESSION['error'] = "You don't have permission to manage carousel";
    header('Location: ../dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Ensure table exists
$create_table = "CREATE TABLE IF NOT EXISTS carousel_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(100),
    caption TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$db->exec($create_table);

// Handle add carousel image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_image'])) {
    $title = trim($_POST['title'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $image_path = null;
    if (isset($_FILES['carousel_image']) && $_FILES['carousel_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $file_type = $_FILES['carousel_image']['type'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['carousel_image']['size'] > $max_size) {
            $_SESSION['error'] = "Image size must be less than 5MB";
        } elseif (in_array($file_type, $allowed_types)) {
            $upload_dir = '../../assets/uploads/carousel/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['carousel_image']['name'], PATHINFO_EXTENSION);
            $file_name = 'carousel_' . time() . '_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['carousel_image']['tmp_name'], $upload_dir . $file_name)) {
                $image_path = '/testweb/assets/uploads/carousel/' . $file_name;
            } else {
                $_SESSION['error'] = "Failed to upload image";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
        }
    } else {
        $_SESSION['error'] = "Please select an image to upload";
    }
    
    if ($image_path) {
        try {
            $query = "INSERT INTO carousel_images (image_path, title, caption, display_order, is_active) 
                      VALUES (:image_path, :title, :caption, :display_order, :is_active)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':image_path', $image_path);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':caption', $caption);
            $stmt->bindParam(':display_order', $display_order);
            $stmt->bindParam(':is_active', $is_active);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Carousel image added successfully";
            } else {
                $_SESSION['error'] = "Failed to add carousel image";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    
    header('Location: carousel.php');
    exit();
}

// Handle update carousel image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_image'])) {
    $image_id = $_POST['image_id'];
    $title = trim($_POST['title'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Get current image path
    $current_query = "SELECT image_path FROM carousel_images WHERE id = :id";
    $current_stmt = $db->prepare($current_query);
    $current_stmt->bindParam(':id', $image_id);
    $current_stmt->execute();
    $current = $current_stmt->fetch(PDO::FETCH_ASSOC);
    $image_path = $current['image_path'];
    
    // Handle image upload
    if (isset($_FILES['carousel_image']) && $_FILES['carousel_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $file_type = $_FILES['carousel_image']['type'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['carousel_image']['size'] > $max_size) {
            $_SESSION['error'] = "Image size must be less than 5MB";
            header('Location: carousel.php');
            exit();
        } elseif (in_array($file_type, $allowed_types)) {
            $upload_dir = '../../assets/uploads/carousel/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            // Delete old image
            if ($image_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
            }
            $file_ext = pathinfo($_FILES['carousel_image']['name'], PATHINFO_EXTENSION);
            $file_name = 'carousel_' . time() . '_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['carousel_image']['tmp_name'], $upload_dir . $file_name)) {
                $image_path = '/testweb/assets/uploads/carousel/' . $file_name;
            }
        }
    }
    
    try {
        $query = "UPDATE carousel_images SET 
                  image_path = :image_path, 
                  title = :title, 
                  caption = :caption, 
                  display_order = :display_order, 
                  is_active = :is_active 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':image_path', $image_path);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':caption', $caption);
        $stmt->bindParam(':display_order', $display_order);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':id', $image_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Carousel image updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update carousel image";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    header('Location: carousel.php');
    exit();
}

// Handle delete carousel image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_image'])) {
    $image_id = $_POST['image_id'];
    
    try {
        // Get image path to delete file
        $query = "SELECT image_path FROM carousel_images WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $image_id);
        $stmt->execute();
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the file
        if ($image && $image['image_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $image['image_path'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $image['image_path']);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM carousel_images WHERE id = :id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':id', $image_id);
        $delete_stmt->execute();
        
        $_SESSION['success'] = "Carousel image deleted successfully";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete carousel image";
    }
    
    header('Location: carousel.php');
    exit();
}

// Fetch all carousel images
$carousel_images = [];
try {
    $query = "SELECT * FROM carousel_images ORDER BY display_order, id DESC";
    $stmt = $db->query($query);
    $carousel_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $carousel_images = [];
}

include '../includes/admin_header.php';
?>

<style>
    .carousel-module {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 1rem;
    }
    
    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .module-title h1 {
        font-size: 1.8rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }
    
    .module-title p {
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .carousel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .carousel-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #eef2f6;
        transition: all 0.3s;
    }
    
    .carousel-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .carousel-image-preview {
        width: 100%;
        height: 200px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    
    .carousel-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .carousel-image-preview .no-image {
        text-align: center;
        color: #94a3b8;
    }
    
    .carousel-image-preview .no-image i {
        font-size: 3rem;
        margin-bottom: 0.5rem;
    }
    
    .carousel-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .badge-active {
        background: #dcfce7;
        color: #166534;
    }
    
    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .carousel-info {
        padding: 1rem;
    }
    
    .carousel-info h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .carousel-caption {
        color: #64748b;
        font-size: 0.8rem;
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }
    
    .carousel-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.7rem;
        color: #94a3b8;
        margin-bottom: 1rem;
    }
    
    .carousel-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .btn-edit, .btn-delete {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 1rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
    }
    
    .btn-edit {
        background: #f1f5f9;
        color: #475569;
    }
    
    .btn-edit:hover {
        background: #e2e8f0;
    }
    
    .btn-delete {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .btn-delete:hover {
        background: #fecaca;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 20px;
        border: 1px solid #eef2f6;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: #64748b;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        overflow-y: auto;
    }
    
    .modal-content {
        background: white;
        margin: 2rem auto;
        border-radius: 24px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 20px 35px -10px rgba(0,0,0,0.15);
        animation: modalFade 0.3s;
    }
    
    @keyframes modalFade {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 24px 24px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h2 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .modal-header .close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #94a3b8;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-header .close:hover {
        background: #f1f5f9;
        color: #ef4444;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 0 0 24px 24px;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: #475569;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: inherit;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .checkbox-group label {
        margin-bottom: 0;
        cursor: pointer;
    }
    
    .btn-save, .btn-cancel {
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
    }
    
    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
    }
    
    .btn-cancel:hover {
        background: #e2e8f0;
    }
    
    .alert {
        padding: 0.75rem 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .alert-success {
        background: #dcfce7;
        color: #166534;
        border-left: 4px solid #10b981;
    }
    
    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }
    
    @media (max-width: 768px) {
        .carousel-grid {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
            margin: 1rem auto;
        }
    }
</style>

<div class="carousel-module">
    <div class="module-header">
        <div class="module-title">
            <h1>Carousel Management</h1>
            <p>Manage homepage carousel images, titles, and captions</p>
        </div>
        <div class="header-actions">
            <button onclick="showAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Carousel Image
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>
    
    <div class="carousel-grid">
        <?php if (empty($carousel_images)): ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <p>No carousel images yet. Click "Add Carousel Image" to get started.</p>
            </div>
        <?php else: ?>
            <?php foreach ($carousel_images as $image): ?>
            <div class="carousel-card">
                <div class="carousel-image-preview">
                    <?php if ($image['image_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $image['image_path'])): ?>
                        <img src="<?php echo $image['image_path']; ?>" alt="<?php echo htmlspecialchars($image['title'] ?? 'Carousel Image'); ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                            <p>No image preview</p>
                        </div>
                    <?php endif; ?>
                    <div class="carousel-badge <?php echo $image['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                        <?php echo $image['is_active'] ? 'Active' : 'Inactive'; ?>
                    </div>
                </div>
                <div class="carousel-info">
                    <h3><?php echo htmlspecialchars($image['title'] ?? 'Untitled'); ?></h3>
                    <div class="carousel-caption">
                        <?php echo htmlspecialchars(substr($image['caption'] ?? '', 0, 80)) . (strlen($image['caption'] ?? '') > 80 ? '...' : ''); ?>
                    </div>
                    <div class="carousel-meta">
                        <span><i class="fas fa-sort-numeric-down"></i> Order: <?php echo $image['display_order']; ?></span>
                        <span><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($image['created_at'])); ?></span>
                    </div>
                    <div class="carousel-actions">
                        <button onclick="editCarousel(<?php echo htmlspecialchars(json_encode($image)); ?>)" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="deleteCarousel(<?php echo $image['id']; ?>)" class="btn-delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Add Carousel Image</h2>
            <button class="close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label>Image <span style="color: #ef4444;">*</span></label>
                    <input type="file" class="form-control" name="carousel_image" accept="image/*" required>
                    <small style="color: #64748b;">Recommended size: 1920x600px. Max 5MB. Allowed: JPG, PNG, GIF, WEBP</small>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" class="form-control" name="title" placeholder="e.g., Welcome to Barangay System">
                </div>
                <div class="form-group">
                    <label>Caption</label>
                    <textarea class="form-control" name="caption" rows="3" placeholder="Brief description..."></textarea>
                </div>
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" class="form-control" name="display_order" value="0" min="0">
                    <small>Lower numbers appear first</small>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="add_is_active" checked>
                    <label for="add_is_active">Active (visible on homepage)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="add_image" class="btn-save">Add Image</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Carousel Image</h2>
            <button class="close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="image_id" id="edit_image_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" class="form-control" name="carousel_image" accept="image/*">
                    <small>Leave empty to keep current image</small>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" class="form-control" id="edit_title" name="title">
                </div>
                <div class="form-group">
                    <label>Caption</label>
                    <textarea class="form-control" id="edit_caption" name="caption" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" class="form-control" id="edit_display_order" name="display_order" min="0">
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" id="edit_is_active">
                    <label for="edit_is_active">Active (visible on homepage)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn-cancel">Cancel</button>
                <button type="submit" name="update_image" class="btn-save">Update Image</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="image_id" id="delete_image_id">
    <input type="hidden" name="delete_image" value="1">
</form>

<script>
    function showAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }
    
    function editCarousel(image) {
        document.getElementById('edit_image_id').value = image.id;
        document.getElementById('edit_title').value = image.title || '';
        document.getElementById('edit_caption').value = image.caption || '';
        document.getElementById('edit_display_order').value = image.display_order || 0;
        document.getElementById('edit_is_active').checked = image.is_active == 1;
        document.getElementById('editModal').style.display = 'block';
    }
    
    function deleteCarousel(id) {
        if (confirm('Are you sure you want to delete this carousel image? This action cannot be undone.')) {
            document.getElementById('delete_image_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>

<?php include '../includes/admin_footer.php'; ?>
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$sellerId = (int)$_SESSION['user_id'];
$errors   = [];

//  Fetch categories
$stmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title           = trim($_POST['title'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $price           = $_POST['price'] ?? '';
    $conditionStatus = $_POST['condition_status'] ?? '';
    $categoryId      = (int)($_POST['category_id'] ?? 0);
    $location        = trim($_POST['location'] ?? '');

    // Validation
    if (strlen($title) < 3) {
        $errors[] = 'Title must be at least 3 characters.';
    }
    if (strlen($description) < 10) {
        $errors[] = 'Description must be at least 10 characters.';
    }
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = 'Please enter a valid price greater than 0.';
    }
    if (!in_array($conditionStatus, ['new', 'used', 'refurbished'])) {
        $errors[] = 'Please select a valid condition.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Please select a category.';
    }
    if (empty($location)) {
        $errors[] = 'Please enter a location.';
    }

    $uploadedImages = $_FILES['images'] ?? [];
    $hasImages      = !empty($uploadedImages['name'][0]);

    if (!$hasImages) {
        $errors[] = 'Please upload at least one image.';
    }

    if (empty($errors)) {
        // Generate unique slug 
        $baseSlug = slugify($title);
        $slug     = $baseSlug;
        $counter  = 2;
        while (true) {
            $check = $pdo->prepare("SELECT 1 FROM products WHERE slug = ?");
            $check->execute([$slug]);
            if (!$check->fetch()) break;
            $slug = $baseSlug . '-' . $counter++;
        }

        // Insert product
        $stmt = $pdo->prepare("
            INSERT INTO products 
                (slug, seller_id, category_id, title, description, price, condition_status, location, status, created_at, updated_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $slug,
            $sellerId,
            $categoryId,
            $title,
            $description,
            $price,
            $conditionStatus,
            $location
        ]);

        $productId = (int)$pdo->lastInsertId();

        // Handle image uploads 
        $uploadDir = __DIR__ . '/assets/images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize      = 5 * 1024 * 1024;

        foreach ($uploadedImages['tmp_name'] as $index => $tmpName) {
            if ($uploadedImages['error'][$index] !== UPLOAD_ERR_OK) continue;
            if (!in_array($uploadedImages['type'][$index], $allowedTypes)) continue;
            if ($uploadedImages['size'][$index] > $maxSize) continue;

            $ext      = pathinfo($uploadedImages['name'][$index], PATHINFO_EXTENSION);
            $filename = $slug . '-' . ($index + 1) . '-' . uniqid() . '.' . strtolower($ext);
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($tmpName, $filepath)) {
                $isPrimary = ($index === 0) ? 1 : 0;
                $imgStmt   = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_path, is_primary) 
                    VALUES (?, ?, ?)
                ");
                $imgStmt->execute([$productId, $filename, $isPrimary]);
            }
        }

        // Redirect to My Listings 
        flash('success', 'Your item has been listed successfully!');
        header('Location: my-listings.php');
        exit;
    }
}

$pageTitle = 'Sell an Item';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card" style="max-width: 720px;">
        <div class="auth-header">
            <div class="logo" style="justify-content: center; margin-bottom: 8px;">
                <i class="fas fa-plus-circle"></i>
                <span>Sell an Item</span>
            </div>
            <p>List your item for sale on the marketplace</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php if (count($errors) === 1): ?>
                        <?= htmlspecialchars($errors[0]) ?>
                    <?php else: ?>
                        <ul style="margin: 0; padding-left: 16px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="add-product.php" enctype="multipart/form-data" class="auth-form">

            <!-- Title -->
            <div class="form-group">
                <label for="title">Item Title <span style="color: #ff6b6b;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-tag"></i>
                    <input type="text" id="title" name="title"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                           placeholder="e.g. Samsung S26 Ultra" required>
                </div>
            </div>

            <!-- Category & Condition -->
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category <span style="color: #ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-th-large"></i>
                        <select id="category_id" name="category_id" required
                                style="padding-left: 42px; appearance: none;
                                       background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22%3E%3Cpath fill=%22%236b6b7b%22 d=%22M6 8L1 3h10z%22/%3E%3C/svg%3E');
                                       background-repeat: no-repeat; background-position: right 14px center;">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"
                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="condition_status">Condition <span style="color: #ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-star"></i>
                        <select id="condition_status" name="condition_status" required
                                style="padding-left: 42px; appearance: none;
                                       background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22%3E%3Cpath fill=%22%236b6b7b%22 d=%22M6 8L1 3h10z%22/%3E%3C/svg%3E');
                                       background-repeat: no-repeat; background-position: right 14px center;">
                            <option value="">Select Condition</option>
                            <option value="new"         <?= ($_POST['condition_status'] ?? '') === 'new'         ? 'selected' : '' ?>>New</option>
                            <option value="used"        <?= ($_POST['condition_status'] ?? '') === 'used'        ? 'selected' : '' ?>>Used</option>
                            <option value="refurbished" <?= ($_POST['condition_status'] ?? '') === 'refurbished' ? 'selected' : '' ?>>Refurbished</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Price & Location -->
            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price (ZMW) <span style="color: #ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-money-bill-wave"></i>
                        <input type="number" id="price" name="price" step="0.01" min="0.01"
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                               placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="location">Location <span style="color: #ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="location" name="location"
                               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
                               placeholder="e.g. Lusaka, Kitwe" required>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description <span style="color: #ff6b6b;">*</span></label>
                <textarea id="description" name="description" rows="5"
                          placeholder="Describe your item, its condition, and any other relevant details..."
                          required style="resize: vertical; min-height: 120px;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- Images -->
            <div class="form-group">
                <label>Images <span style="color: #ff6b6b;">*</span></label>
                <div class="file-upload" id="dropZone">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: var(--accent); margin-bottom: 12px;"></i>
                    <p style="font-weight: 500; margin-bottom: 4px;">Drag &amp; drop images here</p>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">or click to browse</p>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">First image will be the cover. Max 5 images, 5MB each. JPG, PNG, WebP.</p>
                    <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required hidden>
                </div>
                <div id="previewContainer" class="image-preview-grid"></div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-auth">
                <i class="fas fa-check"></i> List Item for Sale
            </button>
        </form>
    </div>
</section>

<style>
.file-upload {
    border: 2px dashed var(--border-color);
    border-radius: var(--radius-md);
    padding: 40px 24px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition-fast);
    background: var(--bg-input);
}
.file-upload:hover {
    border-color: var(--accent);
    background: var(--accent-glow);
}
.file-upload.dragover {
    border-color: var(--accent);
    background: rgba(0, 212, 170, 0.08);
}
.image-preview-grid {
    display: flex;
    gap: 12px;
    margin-top: 16px;
    flex-wrap: wrap;
}
.image-preview-grid .preview-item {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 2px solid var(--border-color);
    background: var(--bg-tertiary);
}
.image-preview-grid .preview-item:first-child {
    border-color: var(--accent);
    box-shadow: 0 0 0 2px var(--accent-glow);
}
.image-preview-grid .preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.image-preview-grid .preview-item .cover-badge {
    position: absolute;
    top: 4px;
    left: 4px;
    background: var(--accent);
    color: var(--text-inverse);
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    text-transform: uppercase;
}
.image-preview-grid .preview-item .remove-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255, 107, 107, 0.9);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    cursor: pointer;
    border: none;
    opacity: 0;
    transition: opacity var(--transition-fast);
}
.image-preview-grid .preview-item:hover .remove-btn {
    opacity: 1;
}
textarea {
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    font-size: 0.95rem;
    transition: all var(--transition-fast);
    outline: none;
    width: 100%;
    color: var(--text-primary);
}
textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-glow);
}
textarea::placeholder {
    color: var(--text-muted);
}
select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-glow);
}
.auth-card {
    width: 100%;
}
</style>

<script>
const dropZone         = document.getElementById('dropZone');
const fileInput        = document.getElementById('images');
const previewContainer = document.getElementById('previewContainer');
let fileList = [];

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const dropped = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
    addFiles(dropped);
});

fileInput.addEventListener('change', () => {
    addFiles(Array.from(fileInput.files));
});

function addFiles(newFiles) {
    const combined = [...fileList, ...newFiles];
    if (combined.length > 5) {
        alert('Maximum 5 images allowed. Only the first 5 will be kept.');
        fileList = combined.slice(0, 5);
    } else {
        fileList = combined;
    }
    updateInput();
    renderPreview();
}

function updateInput() {
    const dt = new DataTransfer();
    fileList.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
}

function removeFile(index) {
    fileList.splice(index, 1);
    updateInput();
    renderPreview();
}

function renderPreview() {
    previewContainer.innerHTML = '';
    fileList.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                ${i === 0 ? '<span class="cover-badge">Cover</span>' : ''}
                <img src="${e.target.result}" alt="Preview ${i + 1}">
                <button type="button" class="remove-btn" onclick="removeFile(${i})" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            `;
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
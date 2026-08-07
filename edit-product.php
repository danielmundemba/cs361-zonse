<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId    = (int)$_SESSION['user_id'];
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header('Location: my-listings.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, c.category_id
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ? AND p.seller_id = ?
    LIMIT 1
");
$stmt->execute([$productId, $userId]);
$product = $stmt->fetch();

if (!$product) {
    flash('error', 'Listing not found or you do not have permission to edit it.');
    header('Location: my-listings.php');
    exit;
}

$catStmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name");
$categories = $catStmt->fetchAll();

$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$imgStmt->execute([$productId]);
$existingImages = $imgStmt->fetchAll();

require_once __DIR__ . '/includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $imgId) {
            $imgId = (int)$imgId;
            $imgFetch = $pdo->prepare("SELECT image_path FROM product_images WHERE image_id = ? AND product_id = ?");
            $imgFetch->execute([$imgId, $productId]);
            $imgRow = $imgFetch->fetch();
            if ($imgRow) {
                $path = __DIR__ . '/assets/images/uploads/' . $imgRow['image_path'];
                if (file_exists($path)) unlink($path);
                $pdo->prepare("DELETE FROM product_images WHERE image_id = ?")->execute([$imgId]);
            }
        }
        $imgStmt->execute([$productId]);
        $existingImages = $imgStmt->fetchAll();

        if (!empty($existingImages)) {
            $hasPrimary = array_filter($existingImages, fn($i) => $i['is_primary']);
            if (empty($hasPrimary)) {
                $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE image_id = ? LIMIT 1")
                    ->execute([$existingImages[0]['image_id']]);
            }
        }
    }

    $title           = trim($_POST['title'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $price           = $_POST['price'] ?? '';
    $conditionStatus = $_POST['condition_status'] ?? '';
    $categoryId      = (int)($_POST['category_id'] ?? 0);
    $location        = trim($_POST['location'] ?? '');

    if (strlen($title) < 3)                                         $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10)                                  $errors[] = 'Description must be at least 10 characters.';
    if (!is_numeric($price) || $price <= 0)                         $errors[] = 'Please enter a valid price greater than 0.';
    if (!in_array($conditionStatus, ['new','used','refurbished']))   $errors[] = 'Please select a valid condition.';
    if ($categoryId <= 0)                                            $errors[] = 'Please select a category.';
    if (empty($location))                                            $errors[] = 'Please enter a location.';

    $remainingCount = count($existingImages);
    $newImages = $_FILES['images'] ?? [];
    $hasNewImages = !empty($newImages['name'][0]);
    if ($remainingCount === 0 && !$hasNewImages) {
        $errors[] = 'Your listing must have at least one image.';
    }

    if (empty($errors)) {
        $slug = $product['slug'];
        if ($title !== $product['title']) {
            $baseSlug = slugify($title);
            $slug     = $baseSlug;
            $counter  = 2;
            while (true) {
                $check = $pdo->prepare("SELECT 1 FROM products WHERE slug = ? AND product_id != ?");
                $check->execute([$slug, $productId]);
                if (!$check->fetch()) break;
                $slug = $baseSlug . '-' . $counter++;
            }
        }

        $pdo->prepare("
            UPDATE products
            SET slug = ?, title = ?, description = ?, price = ?,
                condition_status = ?, category_id = ?, location = ?, updated_at = NOW()
            WHERE product_id = ?
        ")->execute([$slug, $title, $description, $price, $conditionStatus, $categoryId, $location, $productId]);

        if ($hasNewImages) {
            $uploadDir    = __DIR__ . '/assets/images/uploads/';
            $allowedTypes = ['image/jpeg','image/png','image/webp'];
            $maxSize      = 5 * 1024 * 1024;

            $makeFirstPrimary = ($remainingCount === 0);

            foreach ($newImages['tmp_name'] as $index => $tmpName) {
                if ($newImages['error'][$index] !== UPLOAD_ERR_OK) continue;
                if (!in_array($newImages['type'][$index], $allowedTypes)) continue;
                if ($newImages['size'][$index] > $maxSize) continue;

                $ext      = pathinfo($newImages['name'][$index], PATHINFO_EXTENSION);
                $filename = $slug . '-new-' . ($index + 1) . '-' . uniqid() . '.' . strtolower($ext);
                $filepath = $uploadDir . $filename;

                if (move_uploaded_file($tmpName, $filepath)) {
                    $isPrimary = ($makeFirstPrimary && $index === 0) ? 1 : 0;
                    $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?,?,?)")
                        ->execute([$productId, $filename, $isPrimary]);
                }
            }
        }

        flash('success', 'Your listing has been updated successfully!');
        header('Location: my-listings.php');
        exit;
    }

    $product['title']            = $_POST['title'] ?? $product['title'];
    $product['description']      = $_POST['description'] ?? $product['description'];
    $product['price']            = $_POST['price'] ?? $product['price'];
    $product['condition_status'] = $_POST['condition_status'] ?? $product['condition_status'];
    $product['category_id']      = (int)($_POST['category_id'] ?? $product['category_id']);
    $product['location']         = $_POST['location'] ?? $product['location'];
}

$pageTitle = 'Edit Listing';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section" style="align-items: flex-start; padding-top: 48px; padding-bottom: 60px;">
    <div class="auth-card" style="max-width: 760px; margin: 0 auto;">

        <div class="auth-header">
            <div class="logo" style="justify-content: center; margin-bottom: 8px;">
                <i class="fas fa-pen-to-square"></i>
                <span>Edit Listing</span>
            </div>
            <p>Update the details for <strong style="color: var(--text-primary);"><?= htmlspecialchars($product['title']) ?></strong></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php if (count($errors) === 1): ?>
                        <?= htmlspecialchars($errors[0]) ?>
                    <?php else: ?>
                        <ul style="margin: 0; padding-left: 16px;">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="edit-product.php?id=<?= $productId ?>"
              enctype="multipart/form-data"
              class="auth-form">

            <div class="form-group">
                <label for="title">Item Title <span style="color:#ff6b6b;">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-tag"></i>
                    <input type="text" id="title" name="title"
                           value="<?= htmlspecialchars($product['title']) ?>"
                           placeholder="e.g. iPhone 13 Pro 128GB" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category <span style="color:#ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-th-large"></i>
                        <select id="category_id" name="category_id" required
                                style="padding-left:42px; appearance:none;
                                       background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22%3E%3Cpath fill=%22%236b6b7b%22 d=%22M6 8L1 3h10z%22/%3E%3C/svg%3E');
                                       background-repeat:no-repeat; background-position:right 14px center;">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"
                                    <?= $cat['category_id'] == $product['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="condition_status">Condition <span style="color:#ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-star"></i>
                        <select id="condition_status" name="condition_status" required
                                style="padding-left:42px; appearance:none;
                                       background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 12 12%22%3E%3Cpath fill=%22%236b6b7b%22 d=%22M6 8L1 3h10z%22/%3E%3C/svg%3E');
                                       background-repeat:no-repeat; background-position:right 14px center;">
                            <option value="">Select Condition</option>
                            <option value="new"         <?= $product['condition_status'] === 'new'         ? 'selected' : '' ?>>New</option>
                            <option value="used"        <?= $product['condition_status'] === 'used'        ? 'selected' : '' ?>>Used</option>
                            <option value="refurbished" <?= $product['condition_status'] === 'refurbished' ? 'selected' : '' ?>>Refurbished</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price (ZMW) <span style="color:#ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-money-bill-wave"></i>
                        <input type="number" id="price" name="price" step="0.01" min="0.01"
                               value="<?= htmlspecialchars($product['price']) ?>"
                               placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="location">Location <span style="color:#ff6b6b;">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="location" name="location"
                               value="<?= htmlspecialchars($product['location']) ?>"
                               placeholder="e.g. Lusaka, Kitwe" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description <span style="color:#ff6b6b;">*</span></label>
                <textarea id="description" name="description" rows="5"
                          placeholder="Describe your item..." required
                          style="resize:vertical; min-height:120px;"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <?php if (!empty($existingImages)): ?>
                <div class="form-group">
                    <label>Current Images</label>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:12px;">
                        Check images to remove them. The first remaining image becomes the cover.
                    </p>
                    <div class="existing-images-grid">
                        <?php foreach ($existingImages as $img): ?>
                            <div class="existing-img-item" id="existing-<?= $img['image_id'] ?>">
                                <img src="assets/images/uploads/<?= htmlspecialchars($img['image_path']) ?>"
                                     alt="Product image">
                                <?php if ($img['is_primary']): ?>
                                    <span class="cover-badge">Cover</span>
                                <?php endif; ?>
                                <label class="remove-overlay" title="Mark for removal">
                                    <input type="checkbox"
                                           name="delete_images[]"
                                           value="<?= $img['image_id'] ?>"
                                           onchange="markForRemoval(this)">
                                    <span class="remove-x"><i class="fas fa-times"></i></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Add More Images</label>
                <div class="file-upload" id="dropZone">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2.2rem; color:var(--accent); margin-bottom:10px;"></i>
                    <p style="font-weight:500; margin-bottom:4px;">Drag &amp; drop images here</p>
                    <p style="font-size:0.82rem; color:var(--text-muted);">or click to browse — JPG, PNG, WebP, max 5MB each</p>
                    <input type="file" id="images" name="images[]"
                           accept="image/jpeg,image/png,image/webp" multiple hidden>
                </div>
                <div id="previewContainer" class="image-preview-grid"></div>
            </div>

            <div style="display:flex; gap:12px; margin-top:4px;">
                <button type="submit" class="btn-auth" style="flex:1;">
                    <i class="fas fa-check"></i> Save Changes
                </button>
                <a href="my-listings.php" class="btn-auth"
                   style="flex:0 0 auto; background:var(--bg-tertiary); color:var(--text-secondary);
                          border:1px solid var(--border-color); text-decoration:none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</section>

<style>
.existing-images-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.existing-img-item {
    position: relative;
    width: 100px; height: 100px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 2px solid var(--border-color);
    background: var(--bg-tertiary);
    transition: border-color var(--transition-fast), opacity var(--transition-fast);
}
.existing-img-item img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.existing-img-item .cover-badge {
    position: absolute;
    bottom: 4px; left: 4px;
    background: var(--accent);
    color: var(--text-inverse);
    font-size: 0.6rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
    pointer-events: none;
}
.remove-overlay {
    position: absolute;
    inset: 0;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
}
.remove-overlay input { display: none; }
.remove-x {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: rgba(255,107,107,0);
    color: transparent;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem;
    transition: all var(--transition-fast);
    border: 2px solid transparent;
}
.existing-img-item:hover .remove-x {
    background: rgba(255,107,107,0.85);
    color: #fff;
    border-color: #ff6b6b;
}
.existing-img-item.marked-removal {
    opacity: 0.4;
    border-color: #ff6b6b;
}
.existing-img-item.marked-removal .remove-x {
    background: rgba(255,107,107,0.85);
    color: #fff;
    border-color: #ff6b6b;
}

.file-upload {
    border: 2px dashed var(--border-color);
    border-radius: var(--radius-md);
    padding: 36px 24px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition-fast);
    background: var(--bg-input);
}
.file-upload:hover { border-color: var(--accent); background: var(--accent-glow); }
.file-upload.dragover { border-color: var(--accent); background: rgba(0,212,170,0.08); }

.image-preview-grid {
    display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;
}
.image-preview-grid .preview-item {
    position: relative;
    width: 100px; height: 100px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 2px solid var(--border-color);
    background: var(--bg-tertiary);
}
.image-preview-grid .preview-item img { width:100%; height:100%; object-fit:cover; }
.image-preview-grid .preview-item .remove-btn {
    position: absolute;
    top: 4px; right: 4px;
    width: 24px; height: 24px;
    border-radius: 50%;
    background: rgba(255,107,107,0.9);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem;
    cursor: pointer;
    border: none;
    opacity: 0;
    transition: opacity var(--transition-fast);
}
.image-preview-grid .preview-item:hover .remove-btn { opacity: 1; }

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
textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
textarea::placeholder { color: var(--text-muted); }
select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
.auth-card { width: 100%; }
</style>

<script>
function markForRemoval(checkbox) {
    const item = document.getElementById('existing-' + checkbox.value.replace(/[^0-9]/g,''));
    const parent = checkbox.closest('.existing-img-item');
    parent.classList.toggle('marked-removal', checkbox.checked);
}

const dropZone         = document.getElementById('dropZone');
const fileInput        = document.getElementById('images');
const previewContainer = document.getElementById('previewContainer');
let fileList = [];

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    addFiles(Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')));
});
fileInput.addEventListener('change', () => addFiles(Array.from(fileInput.files)));

function addFiles(newFiles) {
    const combined = [...fileList, ...newFiles];
    fileList = combined.length > 5 ? (alert('Max 5 new images.'), combined.slice(0, 5)) : combined;
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
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-btn" onclick="removeFile(${i})">
                    <i class="fas fa-times"></i>
                </button>`;
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
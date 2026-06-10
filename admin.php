<?php
require_once __DIR__ . '/includes/auth.php';

// ─── Admin Access Check ───
if (!isLoggedIn() || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: index.php');
    exit;
}

// ─── Check if 'slug' column exists ───
$hasSlug = false;
try {
    $pdo->query("SELECT slug FROM categories LIMIT 1");
    $hasSlug = true;
} catch (PDOException $e) {
    $hasSlug = false;
}

// ─── Handle Category Actions ───
$success = '';
$error = '';

// Create Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-tag');
    $display_order = (int)($_POST['display_order'] ?? 0);

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        // Check if category already exists
        $check = $pdo->prepare("SELECT category_id FROM categories WHERE LOWER(name) = LOWER(?)");
        $check->execute([$name]);
        if ($check->fetch()) {
            $error = 'A category with this name already exists.';
        } else {
            if ($hasSlug) {
                $slug = strtolower(str_replace(' ', '-', $name));
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, display_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $icon, $display_order]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, icon, display_order) VALUES (?, ?, ?)");
                $stmt->execute([$name, $icon, $display_order]);
            }
            $success = 'Category created successfully!';
        }
    }
}

// Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $catId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-tag');
    $display_order = (int)($_POST['display_order'] ?? 0);

    if (empty($name) || $catId <= 0) {
        $error = 'Invalid category data.';
    } else {
        if ($hasSlug) {
            $slug = strtolower(str_replace(' ', '-', $name));
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon = ?, display_order = ? WHERE category_id = ?");
            $stmt->execute([$name, $slug, $icon, $display_order, $catId]);
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ?, display_order = ? WHERE category_id = ?");
            $stmt->execute([$name, $icon, $display_order, $catId]);
        }
        $success = 'Category updated successfully!';
    }
}

// Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $catId = (int)($_POST['category_id'] ?? 0);
    if ($catId > 0) {
        // Check if category has products
        $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check->execute([$catId]);
        $count = $check->fetchColumn();

        if ($count > 0) {
            $error = 'Cannot delete category with active listings. Move or delete listings first.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$catId]);
            $success = 'Category deleted successfully!';
        }
    }
}

// ─── Fetch Stats ───
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$stats['active_products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$stats['total_categories'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// ─── Fetch All Categories ───
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.product_id) AS listing_count
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
    GROUP BY c.category_id
    ORDER BY c.display_order, c.name
");
$categories = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="admin-section">
    <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
            <p>Manage your marketplace from one place</p>
        </div>

        <!--
        ?php if (!$hasSlug): ?>
            <div class="alert alert-warning" style="background: rgba(254, 202, 87, 0.1); border: 1px solid rgba(254, 202, 87, 0.2); color: #feca57;">
                <i class="fas fa-info-circle"></i> 
                <strong>Note:</strong> Your categories table is missing a <code>slug</code> column. 
                <a href="#" onclick="document.getElementById('sqlHelp').style.display='block'; return false;" style="color: #feca57; text-decoration: underline;">Click here for the fix SQL</a>.
            </div>
            <div id="sqlHelp" style="display:none; background: var(--bg-tertiary); padding: 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-family: monospace; font-size: 0.85rem; color: var(--text-secondary);">
                ALTER TABLE categories ADD COLUMN slug VARCHAR(100) UNIQUE AFTER name;<br>
                UPDATE categories SET slug = LOWER(REPLACE(name, ' ', '-'));<br>
                ALTER TABLE categories MODIFY slug VARCHAR(100) NOT NULL;
            </div>
        ?php endif; ?>
        -->

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= number_format($stats['total_users']) ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= number_format($stats['total_products']) ?></span>
                    <span class="stat-label">Total Listings</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= number_format($stats['active_products']) ?></span>
                    <span class="stat-label">Active Listings</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-th-large"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= number_format($stats['total_categories']) ?></span>
                    <span class="stat-label">Categories</span>
                </div>
            </div>
        </div>

        <!-- Category Management -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2><i class="fas fa-th-large"></i> Category Management</h2>
                <button class="btn-primary" onclick="openModal('createModal')"><i class="fas fa-plus"></i> New Category</button>
            </div>

            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Name</th>
                            <?php if ($hasSlug): ?><th>Slug</th><?php endif; ?>
                            <th>Listings</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= $cat['category_id'] ?></td>
                            <td><i class="fas <?= htmlspecialchars($cat['icon'] ?? 'fa-tag') ?>" style="color: var(--accent);"></i></td>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <?php if ($hasSlug): ?>
                                <td><code><?= htmlspecialchars($cat['slug'] ?? strtolower(str_replace(' ', '-', $cat['name']))) ?></code></td>
                            <?php endif; ?>
                            <td><?= (int)$cat['listing_count'] ?></td>
                            <td><?= (int)$cat['display_order'] ?></td>
                            <td class="actions">
                                <button class="btn-icon btn-edit" onclick="openEditModal(<?= $cat['category_id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', '<?= htmlspecialchars(addslashes($cat['icon'] ?? 'fa-tag')) ?>', <?= (int)$cat['display_order'] ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Delete this category? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                    <button type="submit" class="btn-icon btn-delete" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="<?= $hasSlug ? 7 : 6 ?>" class="empty-table">No categories yet. Create your first one!</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Create Category Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Create New Category</h3>
            <button class="modal-close" onclick="closeModal('createModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Category Name <span class="required">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Electronics" maxlength="50">
            </div>
            <div class="form-group">
                <label>Font Awesome Icon</label>
                <div class="input-wrapper">
                    <i class="fas fa-icons"></i>
                    <input type="text" name="icon" value="fa-tag" placeholder="e.g. fa-laptop">
                </div>
                <span class="hint-text">Use Font Awesome class names like fa-laptop, fa-car, fa-home</span>
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="0" min="0">
                <span class="hint-text">Lower numbers appear first</span>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-outline" onclick="closeModal('createModal')">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Category</h3>
            <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="category_id" id="edit_cat_id">
            <div class="form-group">
                <label>Category Name <span class="required">*</span></label>
                <input type="text" name="name" id="edit_name" required placeholder="e.g. Electronics" maxlength="50">
            </div>
            <div class="form-group">
                <label>Font Awesome Icon</label>
                <div class="input-wrapper">
                    <i class="fas fa-icons"></i>
                    <input type="text" name="icon" id="edit_icon" placeholder="e.g. fa-laptop">
                </div>
                <span class="hint-text">Use Font Awesome class names like fa-laptop, fa-car, fa-home</span>
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" id="edit_order" value="0" min="0">
                <span class="hint-text">Lower numbers appear first</span>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
function openEditModal(id, name, icon, order) {
    document.getElementById('edit_cat_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_icon').value = icon;
    document.getElementById('edit_order').value = order;
    openModal('editModal');
}
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(m => closeModal(m.id));
    }
});
</script>

<style>
/* ─── Admin Dashboard Styles ─── */
.admin-section { padding: 40px 0 80px; }

.admin-header {
    margin-bottom: 32px;
}
.admin-header h1 {
    font-size: 2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}
.admin-header h1 i {
    color: var(--accent);
}
.admin-header p {
    color: var(--text-secondary);
    margin-top: 4px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all var(--transition-base);
}
.stat-card:hover {
    border-color: var(--border-focus);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-md);
    background: var(--accent-glow);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--accent);
    flex-shrink: 0;
}
.stat-info {
    display: flex;
    flex-direction: column;
}
.stat-number {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}
.stat-label {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 2px;
}

/* Admin Card */
.admin-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
}
.admin-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
    gap: 12px;
}
.admin-card-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.admin-card-header h2 i {
    color: var(--accent);
}

/* Table */
.table-responsive {
    overflow-x: auto;
}
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}
.admin-table thead {
    background: var(--bg-tertiary);
}
.admin-table th {
    padding: 14px 20px;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.admin-table td {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
    vertical-align: middle;
}
.admin-table tbody tr {
    transition: background var(--transition-fast);
}
.admin-table tbody tr:hover {
    background: var(--bg-hover);
}
.admin-table code {
    background: var(--bg-tertiary);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: var(--text-secondary);
}
.admin-table .actions {
    display: flex;
    gap: 8px;
    align-items: center;
}
.empty-table {
    text-align: center;
    padding: 48px 20px !important;
    color: var(--text-muted);
}

/* Action Buttons */
.btn-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    border: none;
    background: none;
    color: var(--text-secondary);
    cursor: pointer;
}
.btn-icon:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}
.btn-edit:hover {
    color: var(--accent);
    background: var(--accent-glow);
}
.btn-delete:hover {
    color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
}
.inline-form {
    display: inline;
    margin: 0;
    padding: 0;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    transition: opacity var(--transition-base);
}
.modal.active {
    display: flex;
    opacity: 1;
}
.modal-content {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.95) translateY(10px);
    transition: transform var(--transition-base);
    box-shadow: var(--shadow-lg);
}
.modal.active .modal-content {
    transform: scale(1) translateY(0);
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
}
.modal-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}
.modal-header h3 i {
    color: var(--accent);
}
.modal-close {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    transition: all var(--transition-fast);
    border: none;
    background: none;
    cursor: pointer;
}
.modal-close:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}
.modal-form {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.modal-form .form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 6px;
}
.modal-form .form-group label .required {
    color: #ff6b6b;
}
.modal-form input {
    width: 100%;
}
.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 8px;
}
.modal-actions .btn-outline {
    padding: 10px 20px;
    font-size: 0.9rem;
}
.modal-actions .btn-primary {
    padding: 10px 20px;
    font-size: 0.9rem;
}

/* Alert Warning */
.alert-warning {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .admin-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .admin-table th,
    .admin-table td {
        padding: 10px 12px;
    }
}
@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
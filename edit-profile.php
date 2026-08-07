<?php
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

$stmt = $pdo->prepare("
    SELECT user_id, username, first_name, last_name, full_name,
           email, phone, location, profile_image, created_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $location   = trim($_POST['location'] ?? '');
        $full_name  = trim($first_name . ' ' . $last_name);

        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // ─── Validation ───
        if (empty($first_name)) {
            $errors['first_name'] = 'First name is required.';
        } elseif (strlen($first_name) > 50) {
            $errors['first_name'] = 'First name is too long.';
        }

        if (empty($last_name)) {
            $errors['last_name'] = 'Last name is required.';
        } elseif (strlen($last_name) > 50) {
            $errors['last_name'] = 'Last name is too long.';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } else {
            $checkEmail = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND user_id != ?");
            $checkEmail->execute([$email, $_SESSION['user_id']]);
            if ($checkEmail->fetch()) {
                $errors['email'] = 'This email is already in use.';
            }
        }

        if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }

        $password_hash = null;
        if (!empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                $errors['current_password'] = 'Current password is required to set a new password.';
            } else {
                $verifyStmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                $verifyStmt->execute([$_SESSION['user_id']]);
                $currentHash = $verifyStmt->fetchColumn();

                if (!password_verify($current_password, $currentHash)) {
                    $errors['current_password'] = 'Current password is incorrect.';
                } elseif (strlen($new_password) < 8) {
                    $errors['new_password'] = 'Password must be at least 8 characters.';
                } elseif ($new_password !== $confirm_password) {
                    $errors['confirm_password'] = 'Passwords do not match.';
                } else {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
        }

        $profile_image = $user['profile_image'] ?? 'default.jpg';
        if (!empty($_FILES['profile_image']['tmp_name'])) {
            $file = $_FILES['profile_image'];
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed)) {
                $errors['profile_image'] = 'Only JPG, PNG, and WebP images are allowed.';
            } elseif ($file['size'] > $maxSize) {
                $errors['profile_image'] = 'Image must be under 2MB.';
            } else {
                $uploadDir = __DIR__ . '/assets/images/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = uniqid('profile_') . '_' . $_SESSION['user_id'] . '.' . $ext;
                $uploadPath = $uploadDir . $newName;

                list($width, $height) = getimagesize($file['tmp_name']);
                $maxDim = 400;
                $ratio = min($maxDim / $width, $maxDim / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $src = match($file['type']) {
                    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                    'image/png'  => imagecreatefrompng($file['tmp_name']),
                    'image/webp' => imagecreatefromwebp($file['tmp_name']),
                };

                $dst = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                $saved = match($file['type']) {
                    'image/jpeg' => imagejpeg($dst, $uploadPath, 85),
                    'image/png'  => imagepng($dst, $uploadPath, 6),
                    'image/webp' => imagewebp($dst, $uploadPath, 85),
                };

                imagedestroy($src);
                imagedestroy($dst);

                if ($saved) {
                    if ($profile_image !== 'default.jpg' && file_exists($uploadDir . $profile_image)) {
                        unlink($uploadDir . $profile_image);
                    }
                    $profile_image = $newName;
                } else {
                    $errors['profile_image'] = 'Failed to process image. Please try again.';
                }
            }
        }

        if (empty($errors)) {
            try {
                $sql = "
                    UPDATE users
                    SET first_name = ?, last_name = ?, full_name = ?,
                        email = ?, phone = ?, location = ?, profile_image = ?
                ";
                $params = [$first_name, $last_name, $full_name, $email, $phone, $location, $profile_image];

                if ($password_hash) {
                    $sql .= ", password_hash = ?";
                    $params[] = $password_hash;
                }

                $sql .= " WHERE user_id = ?";
                $params[] = $_SESSION['user_id'];

                $updateStmt = $pdo->prepare($sql);
                $updateStmt->execute($params);

                $_SESSION['full_name'] = $full_name;
                $_SESSION['profile_image'] = $profile_image;

                $success = true;
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['full_name'] = $full_name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['location'] = $location;
                $user['profile_image'] = $profile_image;
            } catch (PDOException $e) {
                $errors['general'] = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit Profile';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card" style="max-width: 560px;">
        <div class="auth-header">
            <a href="profile.php?u=<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" class="logo">
                <i class="fas fa-user-edit"></i>
                <span>Edit Profile</span>
            </a>
            <h1>Update Your Profile</h1>
            <p>Keep your information up to date</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Profile updated successfully!
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit-profile.php" class="auth-form" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- Profile Image Upload -->
            <div class="form-group <?= isset($errors['profile_image']) ? 'has-error' : '' ?>">
                <label>Profile Picture</label>
                <div class="profile-image-upload">
                    <div class="profile-preview-wrap">
                        <img id="profilePreview"
                             src="assets/images/profiles/<?= htmlspecialchars($user['profile_image'] ?? 'default.jpg') ?>?v=<?= time() ?>"
                             alt="Profile Preview">
                        <div class="profile-preview-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Change</span>
                        </div>
                    </div>
                    <div class="profile-upload-actions">
                        <label for="profile_image" class="btn-upload">
                            <i class="fas fa-upload"></i> Choose New Photo
                        </label>
                        <input type="file" id="profile_image" name="profile_image"
                               accept="image/jpeg,image/png,image/webp" hidden>
                        <span class="upload-hint">JPG, PNG, WebP up to 2MB</span>
                    </div>
                </div>
                <?php if (isset($errors['profile_image'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['profile_image']) ?></span>
                <?php endif; ?>
            </div>

            <div class="names-row">
                <div class="form-group <?= isset($errors['first_name']) ? 'has-error' : '' ?>">
                    <label for="first_name">First Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="first_name" name="first_name"
                               value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required autocomplete="given-name">
                    </div>
                    <?php if (isset($errors['first_name'])): ?>
                        <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['first_name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group <?= isset($errors['last_name']) ? 'has-error' : '' ?>">
                    <label for="last_name">Last Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="last_name" name="last_name"
                               value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required autocomplete="family-name">
                    </div>
                    <?php if (isset($errors['last_name'])): ?>
                        <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['last_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                <label for="phone">Phone <span class="optional">(optional)</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" autocomplete="tel">
                </div>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['phone']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="location">Location <span class="optional">(optional)</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="location" name="location"
                           value="<?= htmlspecialchars($user['location'] ?? '') ?>" autocomplete="address-level2">
                </div>
            </div>

            <hr style="border-color: var(--border-color); margin: 8px 0;">

            <div class="form-group <?= isset($errors['current_password']) ? 'has-error' : '' ?>">
                <label for="current_password">Current Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="current_password" name="current_password"
                           placeholder="Required to change password" autocomplete="current-password">
                    <button type="button" class="toggle-password" data-target="current_password" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['current_password'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['current_password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['new_password']) ? 'has-error' : '' ?>">
                <label for="new_password">New Password <span class="optional">(leave blank to keep current)</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password"
                           placeholder="••••••••" autocomplete="new-password">
                    <button type="button" class="toggle-password" data-target="new_password" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="password-strength" style="display:none;">
                    <div class="strength-bar">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <span class="strength-text">Enter password</span>
                </div>
                <?php if (isset($errors['new_password'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['new_password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="••••••••" autocomplete="new-password">
                    <button type="button" class="toggle-password" data-target="confirm_password" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['confirm_password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-action-row">
                <a href="profile.php?u=<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" class="btn-outline">
                    Cancel
                </a>
                <button type="submit" class="btn-auth" style="flex:1;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</section>

<style>
.profile-image-upload {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 4px;
}

.profile-preview-wrap {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    border: 3px solid var(--border-color);
    transition: border-color var(--transition-fast);
    flex-shrink: 0;
}
.profile-preview-wrap:hover {
    border-color: var(--accent);
}

.profile-preview-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.profile-preview-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    opacity: 0;
    transition: opacity var(--transition-fast);
    font-size: 0.75rem;
    gap: 2px;
}
.profile-preview-overlay i {
    font-size: 1.2rem;
}
.profile-preview-wrap:hover .profile-preview-overlay {
    opacity: 1;
}

.profile-upload-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.btn-upload {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: var(--bg-hover);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-fast);
}
.btn-upload:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-glow);
}

.upload-hint {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.form-action-row {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}
.form-action-row .btn-outline {
    padding: 14px 24px;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
}
.form-action-row .btn-auth {
    margin: 0;
}

@media (max-width: 480px) {
    .profile-image-upload {
        flex-direction: column;
        text-align: center;
    }
    .form-action-row {
        flex-direction: column;
    }
    .form-action-row .btn-outline {
        order: 2;
    }
}
</style>

<script>
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        const icon   = btn.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

const newPasswordInput = document.getElementById('new_password');
const strengthBox      = document.getElementById('password-strength');
const strengthBars     = strengthBox.querySelectorAll('.strength-bar span');
const strengthText     = strengthBox.querySelector('.strength-text');

newPasswordInput.addEventListener('input', () => {
    const val = newPasswordInput.value;
    if (!val.length) { strengthBox.style.display = 'none'; return; }
    strengthBox.style.display = 'flex';

    let score = 0;
    if (val.length >= 8)           score++;
    if (val.length >= 12)          score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    const levels = ['weak', 'fair', 'good', 'strong'];
    let level = 0;
    if (score >= 2) level = 1;
    if (score >= 3) level = 2;
    if (score >= 5) level = 3;

    strengthBars.forEach((bar, i) => {
        bar.className = '';
        if (i <= level) bar.classList.add(levels[level]);
    });
    strengthText.textContent = levels[level].charAt(0).toUpperCase() + levels[level].slice(1);
    strengthText.className   = 'strength-text ' + levels[level];
});

const profileInput   = document.getElementById('profile_image');
const profilePreview = document.getElementById('profilePreview');
const previewWrap    = document.querySelector('.profile-preview-wrap');

profileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Please select a JPG, PNG, or WebP image.');
        profileInput.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert('Image must be under 2MB.');
        profileInput.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        profilePreview.src = event.target.result;
    };
    reader.readAsDataURL(file);
});

previewWrap.addEventListener('click', () => profileInput.click());
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
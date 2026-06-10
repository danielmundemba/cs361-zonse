<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$currentUserId      = (int)$_SESSION['user_id'];
$conversationId     = (int)($_GET['chat'] ?? 0);

if ($conversationId <= 0) {
    header('Location: conversations.php');
    exit;
}

// ─── Verify user is part of this conversation and load details ───
$stmt = $pdo->prepare("
    SELECT
        c.*,
        p.title     AS product_title,
        p.slug      AS product_slug,
        p.price     AS product_price,
        p.product_id,
        p.status    AS product_status,
        pi.image_path AS product_image,
        CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END AS other_user_id,
        ou.username       AS other_username,
        ou.full_name      AS other_full_name,
        ou.profile_image  AS other_profile_image
    FROM conversations c
    JOIN products p ON c.product_id = p.product_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    JOIN users ou ON ou.user_id = CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END
    WHERE c.conversation_id = ?
      AND (c.buyer_id = ? OR c.seller_id = ?)
    LIMIT 1
");
$stmt->execute([$currentUserId, $currentUserId, $conversationId, $currentUserId, $currentUserId]);
$chat = $stmt->fetch();

if (!$chat) {
    // Not found or no access
    header('Location: conversations.php');
    exit;
}

// ─── Mark incoming messages as read ───
$pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE conversation_id = ? AND sender_id != ? AND is_read = 0
")->execute([$conversationId, $currentUserId]);

// ─── Load initial messages ───
$msgStmt = $pdo->prepare("
    SELECT m.*, u.username, u.full_name, u.profile_image
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.conversation_id = ?
    ORDER BY m.created_at ASC
");
$msgStmt->execute([$conversationId]);
$messages = $msgStmt->fetchAll();

require_once __DIR__ . '/includes/functions.php';

// Last message ID for polling
$lastMessageId = !empty($messages) ? (int)end($messages)['message_id'] : 0;

$pageTitle = 'Chat with ' . htmlspecialchars($chat['other_full_name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="chat-page">

    <!-- ─── HEADER ─── -->
    <div class="chat-topbar">
        <a href="conversations.php" class="chat-back">
            <i class="fas fa-arrow-left"></i>
        </a>

        <img src="assets/images/profiles/<?= htmlspecialchars($chat['other_profile_image'] ?? 'default.jpg') ?>"
             alt="<?= htmlspecialchars($chat['other_full_name']) ?>"
             class="chat-topbar-avatar">

        <div class="chat-topbar-info">
            <a href="profile.php?u=<?= urlencode($chat['other_username']) ?>" class="chat-topbar-name">
                <?= htmlspecialchars($chat['other_full_name']) ?>
            </a>
            <span class="chat-topbar-sub">
                <i class="fas fa-circle chat-online-dot"></i> Active recently
            </span>
        </div>

        <!-- Product pill -->
        <a href="product.php?slug=<?= urlencode($chat['product_slug']) ?>" class="chat-product-pill" title="View listing">
            <img src="assets/images/uploads/<?= htmlspecialchars($chat['product_image'] ?? 'default-product.jpg') ?>"
                 alt="<?= htmlspecialchars($chat['product_title']) ?>">
            <div class="chat-product-pill-info">
                <span class="chat-product-pill-title"><?= htmlspecialchars($chat['product_title']) ?></span>
                <span class="chat-product-pill-price">ZMW <?= number_format($chat['product_price'], 2) ?></span>
            </div>
            <i class="fas fa-external-link-alt chat-product-pill-icon"></i>
        </a>
    </div>

    <!-- ─── MESSAGES ─── -->
    <div class="chat-body" id="chatBody">
        <div class="chat-messages" id="chatMessages">

            <?php if (empty($messages)): ?>
                <div class="chat-start-hint">
                    <div class="chat-start-avatar">
                        <img src="assets/images/profiles/<?= htmlspecialchars($chat['other_profile_image'] ?? 'default.jpg') ?>"
                             alt="">
                    </div>
                    <p>This is the start of your conversation with <strong><?= htmlspecialchars($chat['other_full_name']) ?></strong></p>
                    <span>Ask about the listing, negotiate a price, or arrange a meetup.</span>
                </div>
            <?php else: ?>
                <?php
                $lastDate = '';
                foreach ($messages as $msg):
                    $msgDate  = date('M j, Y', strtotime($msg['created_at']));
                    $isMe     = ((int)$msg['sender_id'] === $currentUserId);
                    $showDate = ($msgDate !== $lastDate);
                    $lastDate = $msgDate;
                ?>
                    <?php if ($showDate): ?>
                        <div class="chat-date-sep"><span><?= $msgDate === date('M j, Y') ? 'Today' : $msgDate ?></span></div>
                    <?php endif; ?>

                    <div class="msg-row <?= $isMe ? 'msg-mine' : 'msg-theirs' ?>" data-id="<?= $msg['message_id'] ?>">
                        <?php if (!$isMe): ?>
                            <img src="assets/images/profiles/<?= htmlspecialchars($msg['profile_image'] ?? 'default.jpg') ?>"
                                 class="msg-avatar" alt="">
                        <?php endif; ?>
                        <div class="msg-bubble">
                            <p><?= nl2br(htmlspecialchars($msg['message_text'])) ?></p>
                            <span class="msg-time"><?= date('g:i A', strtotime($msg['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- ─── INPUT ─── -->
    <div class="chat-footer">
        <div class="chat-input-wrap" id="chatInputWrap">
            <textarea
                id="msgInput"
                placeholder="Type a message…"
                rows="1"
                maxlength="2000"
                autocomplete="off"></textarea>
            <button id="sendBtn" class="send-btn" disabled>
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

</div>

<style>
/* ─── Reset main padding since we're full-viewport ─── */
main { padding-top: 0 !important; }

.chat-page {
    display: flex;
    flex-direction: column;
    height: 100vh;
    padding-top: var(--nav-height);
    background: var(--bg-primary);
    overflow: hidden;
}

/* ── Topbar ── */
.chat-topbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;
    flex-wrap: wrap;
}

.chat-back {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-secondary);
    transition: all var(--transition-fast);
    flex-shrink: 0;
}
.chat-back:hover { background: var(--bg-hover); color: var(--text-primary); }

.chat-topbar-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-color);
    flex-shrink: 0;
}

.chat-topbar-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
    min-width: 0;
}
.chat-topbar-name {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--text-primary);
    transition: color var(--transition-fast);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.chat-topbar-name:hover { color: var(--accent); }
.chat-topbar-sub {
    font-size: 0.72rem;
    color: var(--text-muted);
    display: flex; align-items: center; gap: 5px;
}
.chat-online-dot {
    font-size: 7px;
    color: var(--accent);
}

/* Product pill */
.chat-product-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px 8px 8px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    transition: all var(--transition-fast);
    flex-shrink: 0;
    max-width: 240px;
    text-decoration: none;
}
.chat-product-pill:hover {
    border-color: var(--accent);
    background: var(--accent-glow);
}
.chat-product-pill img {
    width: 32px; height: 32px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}
.chat-product-pill-info {
    display: flex; flex-direction: column; gap: 1px;
    min-width: 0;
}
.chat-product-pill-title {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 130px;
}
.chat-product-pill-price {
    font-size: 0.7rem;
    color: var(--accent);
    font-weight: 600;
}
.chat-product-pill-icon {
    font-size: 0.65rem;
    color: var(--text-muted);
    flex-shrink: 0;
}

/* ── Messages body ── */
.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 0;
}

.chat-messages {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 0 20px;
    min-height: 100%;
    justify-content: flex-end;
}

/* Date separator */
.chat-date-sep {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 16px 0 8px;
}
.chat-date-sep span {
    font-size: 0.68rem;
    color: var(--text-muted);
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    padding: 3px 12px;
    border-radius: var(--radius-xl);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

/* Conversation start hint */
.chat-start-hint {
    text-align: center;
    padding: 40px 24px;
    color: var(--text-muted);
}
.chat-start-avatar {
    width: 64px; height: 64px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto 16px;
    border: 3px solid var(--border-color);
}
.chat-start-avatar img { width: 100%; height: 100%; object-fit: cover; }
.chat-start-hint p {
    font-size: 0.95rem;
    color: var(--text-secondary);
    margin-bottom: 6px;
}
.chat-start-hint span { font-size: 0.82rem; }

/* Message rows */
.msg-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    margin-bottom: 2px;
    animation: msgIn 0.2s ease;
}
@keyframes msgIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.msg-mine  { flex-direction: row-reverse; }
.msg-theirs { flex-direction: row; }

/* Group same-sender messages closer */
.msg-mine  + .msg-mine,
.msg-theirs + .msg-theirs { margin-top: -8px; }

.msg-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid var(--border-color);
    margin-bottom: 2px;
}

.msg-bubble {
    max-width: 68%;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.msg-bubble p {
    padding: 10px 14px;
    border-radius: 18px;
    font-size: 0.9rem;
    line-height: 1.5;
    word-wrap: break-word;
    white-space: pre-wrap;
}
.msg-mine .msg-bubble p {
    background: var(--accent);
    color: var(--text-inverse);
    border-bottom-right-radius: 4px;
}
.msg-theirs .msg-bubble p {
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    border-bottom-left-radius: 4px;
}

.msg-time {
    font-size: 0.62rem;
    color: var(--text-muted);
    padding: 0 4px;
}
.msg-mine .msg-time  { text-align: right; }
.msg-theirs .msg-time { text-align: left; }

/* Sending state */
.msg-sending .msg-bubble p { opacity: 0.6; }

/* ── Footer / input ── */
.chat-footer {
    flex-shrink: 0;
    padding: 12px 16px 16px;
    background: var(--bg-secondary);
    border-top: 1px solid var(--border-color);
}

.chat-input-wrap {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 8px 8px 8px 16px;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}
.chat-input-wrap:focus-within {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

#msgInput {
    flex: 1;
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 6px 0;
    resize: none;
    max-height: 120px;
    min-height: 24px;
    line-height: 1.5;
    font-size: 0.9rem;
    color: var(--text-primary);
}
#msgInput:focus { border: none; box-shadow: none; }
#msgInput::placeholder { color: var(--text-muted); }

.send-btn {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--accent);
    color: var(--text-inverse);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all var(--transition-fast);
    border: none;
}
.send-btn:hover:not(:disabled) {
    background: var(--accent-hover);
    transform: scale(1.08);
}
.send-btn:disabled {
    background: var(--bg-hover);
    color: var(--text-muted);
    cursor: not-allowed;
    transform: none;
}

@media (max-width: 600px) {
    .chat-topbar { gap: 8px; padding: 10px 14px; }
    .chat-product-pill { display: none; }
    .chat-messages { padding: 0 12px; }
    .msg-bubble { max-width: 82%; }
}
</style>

<script>
const CONV_ID     = <?= $conversationId ?>;
const CURRENT_UID = <?= $currentUserId ?>;
let lastId        = <?= $lastMessageId ?>;
let pollTimer     = null;
let sending       = false;

const chatBody    = document.getElementById('chatBody');
const chatMsgs    = document.getElementById('chatMessages');
const msgInput    = document.getElementById('msgInput');
const sendBtn     = document.getElementById('sendBtn');

// ── Auto-scroll to bottom ──
function scrollBottom(smooth = false) {
    chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
}

// ── Auto-resize textarea ──
msgInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    sendBtn.disabled = this.value.trim().length === 0;
});

// ── Send on Enter (Shift+Enter = newline) ──
msgInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (!sendBtn.disabled) sendMessage();
    }
});

sendBtn.addEventListener('click', sendMessage);

// ── Build a message bubble element ──
function buildBubble(text, isMe, time, id, sending = false) {
    const row = document.createElement('div');
    row.className = `msg-row ${isMe ? 'msg-mine' : 'msg-theirs'}${sending ? ' msg-sending' : ''}`;
    if (id) row.dataset.id = id;

    const avatarHtml = isMe ? '' :
        `<img src="assets/images/profiles/<?= htmlspecialchars($chat['other_profile_image'] ?? 'default.jpg') ?>"
              class="msg-avatar" alt="">`;

    // Escape HTML
    const safe = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                     .replace(/\n/g,'<br>');

    row.innerHTML = `
        ${avatarHtml}
        <div class="msg-bubble">
            <p>${safe}</p>
            <span class="msg-time">${time}</span>
        </div>`;
    return row;
}

// ── Remove empty-state hint on first message ──
function removeHint() {
    const hint = chatMsgs.querySelector('.chat-start-hint');
    if (hint) hint.remove();
}

// ── Send message ──
function sendMessage() {
    const text = msgInput.value.trim();
    if (!text || sending) return;

    sending = true;
    sendBtn.disabled = true;

    // Optimistic bubble
    removeHint();
    const now = new Date();
    const timeStr = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    const tempBubble = buildBubble(text, true, timeStr, null, true);
    chatMsgs.appendChild(tempBubble);
    scrollBottom(true);

    msgInput.value = '';
    msgInput.style.height = 'auto';

    fetch('api/messages.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'conversation_id=' + CONV_ID + '&message=' + encodeURIComponent(text)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Replace temp bubble with real one (has correct ID for polling dedup)
            tempBubble.classList.remove('msg-sending');
            if (data.message_id) tempBubble.dataset.id = data.message_id;
            lastId = Math.max(lastId, parseInt(data.message_id) || lastId);
        } else {
            tempBubble.remove();
            if (typeof showToast === 'function') showToast('Failed to send message.', 'error');
        }
    })
    .catch(() => {
        tempBubble.remove();
        if (typeof showToast === 'function') showToast('Network error. Message not sent.', 'error');
    })
    .finally(() => {
        sending = false;
        sendBtn.disabled = msgInput.value.trim().length === 0;
        msgInput.focus();
    });
}

// ── Poll for new messages ──
function poll() {
    fetch(`api/messages.php?conversation_id=${CONV_ID}&last_id=${lastId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.messages.length) return;

            // Collect IDs already in DOM to avoid duplicates from optimistic bubbles
            const existing = new Set(
                [...chatMsgs.querySelectorAll('[data-id]')].map(el => el.dataset.id)
            );

            let appended = false;
            data.messages.forEach(msg => {
                if (existing.has(String(msg.message_id))) return;
                lastId = Math.max(lastId, parseInt(msg.message_id));

                removeHint();
                const bubble = buildBubble(
                    msg.message_text,
                    msg.is_me,
                    msg.time,
                    msg.message_id
                );
                chatMsgs.appendChild(bubble);
                appended = true;
            });

            if (appended) scrollBottom(true);
        })
        .catch(() => {});
}

// ── Init ──
scrollBottom();
pollTimer = setInterval(poll, 3000);

window.addEventListener('beforeunload', () => clearInterval(pollTimer));

// Focus input
msgInput.focus();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
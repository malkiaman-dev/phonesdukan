<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if (isset($_POST['image_id']) && !empty($_POST['image_id'])) {
    header('Content-Type: application/json');
    $imageId = (int)$_POST['image_id'];

    try {
        $stmt = $conn->prepare('DELETE FROM product_images WHERE image_id = :image_id');
        $stmt->bindParam(':image_id', $imageId, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $conn->prepare('DELETE FROM image_metadata WHERE image_id = :image_id');
        $stmt->bindParam(':image_id', $imageId, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

function mediaImageCandidates($rawPath)
{
    $raw = trim((string)$rawPath);
    if ($raw === '') return [];

    $normalized = str_replace('\\', '/', $raw);
    $candidates = [$normalized];

    if (preg_match('/^https?:\/\//i', $normalized)) {
        $parsed = parse_url($normalized);
        $path = isset($parsed['path']) ? (string)$parsed['path'] : '';
        if ($path !== '') {
            $trimmedPath = ltrim($path, '/');
            $candidates[] = '/' . $trimmedPath;
            $candidates[] = '/phonesdukan/' . $trimmedPath;
            if (strpos($trimmedPath, 'uploads/') !== false) {
                $uploadsPart = substr($trimmedPath, strpos($trimmedPath, 'uploads/'));
                $candidates[] = '/public/' . $uploadsPart;
                $candidates[] = '/phonesdukan/public/' . $uploadsPart;
            }
        }
    } else {
        // Handle local filesystem paths like C:/xampp/htdocs/phonesdukan/public/uploads/...
        if (preg_match('/^[A-Za-z]:\//', $normalized)) {
            $lower = strtolower($normalized);
            $projectMarker = '/xampp/htdocs/phonesdukan/';
            $docRootMarker = '/xampp/htdocs/';
            if (strpos($lower, $projectMarker) !== false) {
                $pos = strpos($lower, $projectMarker);
                $relative = substr($normalized, $pos + strlen($projectMarker));
                $relative = ltrim(str_replace('\\', '/', $relative), '/');
                $candidates[] = '/phonesdukan/' . $relative;
                $candidates[] = '/' . $relative;
            } elseif (strpos($lower, $docRootMarker) !== false) {
                $pos = strpos($lower, $docRootMarker);
                $relative = substr($normalized, $pos + strlen($docRootMarker));
                $relative = ltrim(str_replace('\\', '/', $relative), '/');
                $candidates[] = '/' . $relative;
            }
        }

        $trimmed = ltrim($normalized, './');
        $trimmed = ltrim($trimmed, '/');
        if ($trimmed !== '') {
            $candidates[] = '/' . $trimmed;
            $candidates[] = '/phonesdukan/' . $trimmed;
            $candidates[] = '/public/' . $trimmed;
            $candidates[] = '/phonesdukan/public/' . $trimmed;
            if (strpos($trimmed, 'uploads/') !== false) {
                $uploadsPart = substr($trimmed, strpos($trimmed, 'uploads/'));
                $candidates[] = '/' . $uploadsPart;
                $candidates[] = '/phonesdukan/' . $uploadsPart;
                $candidates[] = '/public/' . $uploadsPart;
                $candidates[] = '/phonesdukan/public/' . $uploadsPart;
            }
        }
    }

    return array_values(array_unique(array_filter($candidates)));
}

$sql = "
    SELECT i.image_id, i.image_url, i.product_id, im.alt_text, im.title, im.description, im.caption,
           COALESCE(p.product_name, 'Not Assigned') AS product_name
    FROM product_images i
    LEFT JOIN image_metadata im ON i.image_id = im.image_id
    LEFT JOIN products p ON i.product_id = p.product_id
    ORDER BY i.image_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>

<style>
    :root {
        --black: #111111;
        --yellow: #facc15;
        --light-yellow: #fffbeb;
        --white: #ffffff;
        --bg: #f8fafc;
        --border: #e5e7eb;
        --muted: #6b7280;
    }

    .ml-wrap {
        max-width: 1320px;
        margin: 0 auto;
        padding: 20px;
    }

    .ml-header,
    .ml-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
    }

    .ml-header {
        padding: 20px 24px;
        margin-bottom: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ml-title {
        margin: 0;
        font-size: clamp(1.5rem, 2vw, 1.75rem);
        font-weight: 600;
        line-height: 1.25;
        letter-spacing: -0.02em;
        color: var(--black);
    }
    .ml-subtitle {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.5;
    }

    .ml-header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ml-btn {
        height: 44px;
        padding: 0 14px;
        border-radius: 12px;
        border: 1px solid var(--black);
        background: var(--black);
        color: #fff !important;
        font-size: 0.88rem;
        font-weight: 800;
        cursor: pointer;
        text-decoration: none !important;
        transition: color .15s ease, transform .15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .ml-btn:hover { color: var(--yellow) !important; transform: translateY(-1px); }

    .ml-native-select {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
        overflow: hidden !important;
    }
    .ml-select-wrap { position: relative; min-width: 145px; }
    .ml-select-display {
        width: 100%;
        height: 44px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--black);
        padding: 0 36px 0 12px;
        font-size: 0.86rem;
        font-weight: 800;
        text-align: left;
        cursor: pointer;
        position: relative;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .ml-select-display::after {
        content: "";
        position: absolute;
        right: 12px;
        top: 50%;
        width: 8px;
        height: 8px;
        border-right: 2px solid var(--black);
        border-bottom: 2px solid var(--black);
        transform: translateY(-65%) rotate(45deg);
    }
    .ml-select-display:hover,
    .ml-select-wrap.is-open .ml-select-display {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
    }
    .ml-select-options {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 9999;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 14px 28px rgba(17,17,17,0.12);
        padding: 6px;
        display: none;
    }
    .ml-select-wrap.is-open .ml-select-options { display: block; }
    .ml-select-option {
        width: 100%;
        border: 0;
        background: transparent;
        border-radius: 8px;
        text-align: left;
        padding: 8px 10px;
        font-size: 0.86rem;
        font-weight: 800;
        color: var(--black);
        cursor: pointer;
    }
    .ml-select-option:hover { background: var(--light-yellow); }
    .ml-select-option.is-selected { background: var(--yellow); }

    .ml-card { padding: 16px; }
    .ml-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }

    .ml-item {
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 10px;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .ml-item:hover {
        border-color: var(--yellow);
        box-shadow: 0 10px 20px rgba(17,17,17,0.08);
        transform: translateY(-1px);
    }

    .ml-thumb-wrap {
        width: 100%;
        aspect-ratio: 1/1;
        border-radius: 12px;
        border: 1px solid var(--border);
        overflow: hidden;
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ml-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .ml-placeholder {
        display: none;
        color: var(--muted);
        font-size: 0.82rem;
        font-weight: 700;
        text-align: center;
        padding: 8px;
    }

    .ml-meta { margin-top: 8px; }
    .ml-meta p {
        margin: 0;
        font-size: 0.82rem;
        color: var(--muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ml-meta p strong { color: var(--black); }

    .ml-empty {
        text-align: center;
        color: var(--muted);
        font-weight: 700;
        padding: 18px;
    }

    .ml-pagebar {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }
    .ml-page-controls {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-left: auto;
    }
    .ml-page-btn {
        min-width: 38px;
        height: 38px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #fff;
        color: var(--black);
        font-size: 0.84rem;
        font-weight: 800;
        cursor: pointer;
    }
    .ml-page-btn:hover { border-color: var(--yellow); background: var(--light-yellow); }
    .ml-page-btn.is-active { border-color: var(--yellow); background: var(--yellow); color: var(--black); }
    .ml-page-btn:disabled { opacity: .45; cursor: not-allowed; }
    .ml-page-meta { color: var(--muted); font-size: 0.85rem; font-weight: 700; }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(17,17,17,0.45);
        z-index: 3000;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }
    .modal-content {
        width: 100%;
        max-width: 720px;
        max-height: 85vh;
        overflow-y: auto;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 20px 45px rgba(17,17,17,0.18);
        padding: 18px;
        position: relative;
    }
    .close {
        position: absolute;
        right: 12px;
        top: 10px;
        width: 42px;
        height: 42px;
        border-radius: 999px;
        background: var(--black) !important;
        border: 1px solid var(--black);
        font-size: 1.7rem;
        line-height: 1;
        color: #ffffff !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: color .15s ease;
    }
    .close:hover { color: var(--yellow) !important; }

    .ml-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .ml-field.full { grid-column: 1 / -1; }
    .ml-field label {
        display: block;
        margin-bottom: 6px;
        font-size: 0.88rem;
        font-weight: 700;
    }
    .input-field {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #fff;
        color: var(--black);
        font-size: 0.9rem;
        padding: 10px 12px;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    textarea.input-field { min-height: 110px; resize: vertical; }
    .input-field:focus, .input-field:focus-visible {
        outline: none !important;
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
    }

    .button-group {
        margin-top: 14px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    #uploadFrame { border: none; width: 100%; height: 430px; border-radius: 10px; background: #fff; }

    .ml-toast {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 4000;
        min-width: 260px;
        max-width: 380px;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 0.9rem;
        font-weight: 800;
        border: 1px solid var(--border);
        box-shadow: 0 16px 30px rgba(17, 17, 17, 0.15);
        opacity: 0;
        transform: translateY(10px);
        pointer-events: none;
        transition: opacity .2s ease, transform .2s ease;
        background: #111;
        color: #fff;
    }
    .ml-toast.is-show { opacity: 1; transform: translateY(0); }
</style>
<script>
// Define early so <img onerror> always has handler available.
window.handleMediaImageFallback = function (img) {
    let candidates = [];
    try {
        candidates = JSON.parse(img.getAttribute('data-candidates') || '[]');
    } catch (e) {
        candidates = [];
    }
    let idx = parseInt(img.getAttribute('data-candidate-index') || '0', 10);
    if (Number.isNaN(idx)) idx = 0;
    const next = idx + 1;
    if (next < candidates.length) {
        img.setAttribute('data-candidate-index', String(next));
        img.src = candidates[next];
        return;
    }
    img.style.display = 'none';
    const placeholder = img.parentElement ? img.parentElement.querySelector('.ml-placeholder') : null;
    if (placeholder) placeholder.style.display = 'block';
};
</script>

<div class="ml-wrap">
    <div class="ml-header">
        <div>
            <h2 class="ml-title">Media Library</h2>
            <p class="ml-subtitle">Manage uploaded product images and metadata.</p>
        </div>
        <div class="ml-header-right">
            <div class="ml-select-wrap" data-ml-select>
                <select id="mlPerPage" class="ml-native-select">
                    <option value="20">20 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>
                <button type="button" class="ml-select-display" data-ml-display>20 / page</button>
                <div class="ml-select-options">
                    <button type="button" class="ml-select-option" data-value="20">20 / page</button>
                    <button type="button" class="ml-select-option" data-value="50">50 / page</button>
                    <button type="button" class="ml-select-option" data-value="100">100 / page</button>
                </div>
            </div>
            <button id="openUploadModal" class="ml-btn" type="button">Upload New Image</button>
        </div>
    </div>

    <div class="ml-card">
        <?php if (!empty($images)): ?>
            <div class="ml-grid">
                <?php foreach ($images as $image): ?>
                    <?php
                        $candidates = mediaImageCandidates($image['image_url'] ?? '');
                        $imgSrc = $candidates[0] ?? '';
                        $candidatesAttr = htmlspecialchars(json_encode($candidates), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="ml-item image-card"
                         data-image-id="<?= (int)$image['image_id'] ?>"
                         onclick="openEditModal(
                            <?= (int)$image['image_id'] ?>,
                            <?= json_encode((string)($image['title'] ?? '')) ?>,
                            <?= json_encode((string)($image['alt_text'] ?? '')) ?>,
                            <?= json_encode((string)($image['caption'] ?? '')) ?>,
                            <?= json_encode((string)($image['description'] ?? '')) ?>
                         )">
                        <div class="ml-thumb-wrap">
                            <img class="ml-thumb"
                                 src="<?= htmlspecialchars($imgSrc) ?>"
                                 data-candidates="<?= $candidatesAttr; ?>"
                                 data-candidate-index="0"
                                 alt="<?= htmlspecialchars($image['alt_text'] ?? '') ?>"
                                 onerror="handleMediaImageFallback(this)">
                            <span class="ml-placeholder">No image</span>
                        </div>
                        <div class="ml-meta">
                            <p><strong>ID:</strong> <?= (int)$image['image_id'] ?></p>
                            <p><strong>Product:</strong> <?= htmlspecialchars((string)($image['product_name'] ?? 'Not Assigned')) ?></p>
                            <p><strong>Title:</strong> <?= htmlspecialchars((string)($image['title'] ?? '')) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="ml-empty">No media items found.</p>
        <?php endif; ?>
    </div>
    <div class="ml-pagebar">
        <div class="ml-page-controls" id="mlPagination"></div>
        <div class="ml-page-meta" id="mlPageMeta">Page 1 of 1</div>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2 style="margin:0 0 14px;">Edit Image Details</h2>
        <form id="editForm" onsubmit="return false;">
            <input type="hidden" id="editImageId">

            <div class="ml-form-grid">
                <div class="ml-field">
                    <label for="editTitle">Title</label>
                    <input type="text" id="editTitle" class="input-field">
                </div>
                <div class="ml-field">
                    <label for="editAltText">Alt Text</label>
                    <input type="text" id="editAltText" class="input-field">
                </div>
                <div class="ml-field">
                    <label for="editCaption">Caption</label>
                    <input type="text" id="editCaption" class="input-field">
                </div>
                <div class="ml-field full">
                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" class="input-field"></textarea>
                </div>
            </div>

            <div class="button-group">
                <button type="button" class="ml-btn update-btn" onclick="updateImageMetadata()">Update</button>
                <button type="button" id="deleteImageBtn" class="ml-btn delete-btn">Delete</button>
            </div>
        </form>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeUploadModal">&times;</span>
        <h2 style="margin:0 0 14px;">Upload Image</h2>
        <iframe id="uploadFrame" src="upload_image.php"></iframe>
    </div>
</div>

<div id="mlToast" class="ml-toast"></div>

<script>
function showMediaToast(message) {
    const toast = document.getElementById('mlToast');
    if (!toast) return;
    toast.textContent = message || '';
    toast.classList.add('is-show');
    setTimeout(() => toast.classList.remove('is-show'), 2600);
}

function openEditModal(imageId, title, altText, caption, description) {
    document.getElementById('editImageId').value = imageId;
    document.getElementById('editTitle').value = title || '';
    document.getElementById('editAltText').value = altText || '';
    document.getElementById('editCaption').value = caption || '';
    document.getElementById('editDescription').value = description || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function updateImageMetadata() {
    const imageId = document.getElementById('editImageId').value;
    const title = document.getElementById('editTitle').value;
    const altText = document.getElementById('editAltText').value;
    const caption = document.getElementById('editCaption').value;
    const description = document.getElementById('editDescription').value;

    const body = new URLSearchParams({
        action: 'update',
        image_id: imageId,
        title: title,
        alt_text: altText,
        caption: caption,
        description: description
    });

    fetch('update_image_metadata.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            showMediaToast('Image metadata updated successfully');
            closeEditModal();
            setTimeout(() => window.location.reload(), 350);
        } else {
            showMediaToast('Failed to update metadata');
        }
    })
    .catch(() => showMediaToast('Failed to update metadata'));
}

function deleteImageMetadata(imageId) {
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'image_id=' + encodeURIComponent(imageId)
    })
    .then(r => r.json())
    .then(response => {
        if (response && response.success) {
            showMediaToast('Image deleted successfully');
            const imageCard = document.querySelector(".image-card[data-image-id='" + imageId + "']");
            if (imageCard) imageCard.remove();
            closeEditModal();
        } else {
            showMediaToast('Error deleting image');
        }
    })
    .catch(() => showMediaToast('Error deleting image'));
}

document.addEventListener('DOMContentLoaded', function () {
    const perPageEl = document.getElementById('mlPerPage');
    const paginationEl = document.getElementById('mlPagination');
    const pageMetaEl = document.getElementById('mlPageMeta');
    let currentPage = 1;

    document.querySelectorAll('[data-ml-select]').forEach((wrap) => {
        const nativeSelect = wrap.querySelector('select');
        const display = wrap.querySelector('[data-ml-display]');
        const options = Array.from(wrap.querySelectorAll('.ml-select-option'));
        if (!nativeSelect || !display || options.length === 0) return;

        function sync() {
            const selected = options.find(opt => opt.dataset.value === nativeSelect.value);
            display.textContent = selected ? selected.textContent.trim() : (nativeSelect.options[nativeSelect.selectedIndex]?.text || 'Select');
            options.forEach(opt => opt.classList.toggle('is-selected', opt.dataset.value === nativeSelect.value));
        }

        display.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.ml-select-wrap.is-open').forEach((o) => {
                if (o !== wrap) o.classList.remove('is-open');
            });
            wrap.classList.toggle('is-open');
        });

        options.forEach((opt) => {
            opt.addEventListener('click', function () {
                nativeSelect.value = this.dataset.value;
                nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                sync();
                wrap.classList.remove('is-open');
                currentPage = 1;
                applyPagination();
            });
        });
        sync();
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.ml-select-wrap.is-open').forEach((wrap) => wrap.classList.remove('is-open'));
    });

    function renderPagination(totalPages) {
        if (!paginationEl) return;
        paginationEl.innerHTML = '';
        if (totalPages <= 1) return;

        function makeBtn(label, page, disabled, active) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ml-page-btn' + (active ? ' is-active' : '');
            btn.textContent = label;
            btn.disabled = !!disabled;
            btn.addEventListener('click', function () {
                if (disabled) return;
                currentPage = page;
                applyPagination();
            });
            return btn;
        }

        paginationEl.appendChild(makeBtn('Prev', Math.max(1, currentPage - 1), currentPage === 1, false));
        const windowSize = 2;
        const start = Math.max(1, currentPage - windowSize);
        const end = Math.min(totalPages, currentPage + windowSize);
        for (let p = start; p <= end; p++) {
            paginationEl.appendChild(makeBtn(String(p), p, false, p === currentPage));
        }
        paginationEl.appendChild(makeBtn('Next', Math.min(totalPages, currentPage + 1), currentPage === totalPages, false));
    }

    function applyPagination() {
        const items = Array.from(document.querySelectorAll('.ml-grid .ml-item'));
        if (!items.length || !perPageEl) return;
        const perPage = parseInt(perPageEl.value || '20', 10) || 20;
        const total = items.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) currentPage = totalPages;

        items.forEach((item) => item.style.display = 'none');
        const start = (currentPage - 1) * perPage;
        items.slice(start, start + perPage).forEach((item) => item.style.display = '');

        if (pageMetaEl) pageMetaEl.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        renderPagination(totalPages);
    }

    applyPagination();

    const deleteBtn = document.getElementById('deleteImageBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            const imageId = document.getElementById('editImageId').value;
            if (confirm('Are you sure you want to delete this image?')) {
                deleteImageMetadata(imageId);
            }
        });
    }

    const openUpload = document.getElementById('openUploadModal');
    const closeUpload = document.getElementById('closeUploadModal');
    const uploadModal = document.getElementById('uploadModal');
    if (openUpload && uploadModal) {
        openUpload.addEventListener('click', function () {
            uploadModal.style.display = 'flex';
        });
    }
    if (closeUpload && uploadModal) {
        closeUpload.addEventListener('click', function () {
            uploadModal.style.display = 'none';
        });
    }

    window.addEventListener('click', function (event) {
        const editModal = document.getElementById('editModal');
        if (event.target === editModal) closeEditModal();
        if (event.target === uploadModal) uploadModal.style.display = 'none';
    });

    // Refresh parent after upload is done inside iframe
    const uploadFrame = document.getElementById('uploadFrame');
    if (uploadFrame) {
        uploadFrame.addEventListener('load', function () {
            try {
                const frameDoc = uploadFrame.contentDocument || uploadFrame.contentWindow.document;
                if (!frameDoc) return;
                const successEl = frameDoc.querySelector('.success');
                if (successEl) {
                    showMediaToast('Image uploaded successfully');
                    setTimeout(() => window.location.reload(), 500);
                }
            } catch (e) {
                // ignore cross-context errors
            }
        });
    }
});
</script>


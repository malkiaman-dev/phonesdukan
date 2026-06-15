<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../app/Models/VariationModel.php';

$model = new VariationModel();
$toast = '';
$toastType = 'success';

if (isset($_SESSION['var_toast'])) {
    $toast = (string)($_SESSION['var_toast']['message'] ?? '');
    $toastType = (string)($_SESSION['var_toast']['type'] ?? 'success');
    unset($_SESSION['var_toast']);
}

function makeSlug($str) {
    return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $str), '-'));
}

// -------------------------------------------------------
// POST handlers
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add variation type
    if (isset($_POST['add_variation_type'])) {
        $name = trim($_POST['type_name'] ?? '');
        $display_type = $_POST['display_type'] ?? 'button';
        $sort_order   = (int)($_POST['sort_order'] ?? 0);
        if ($name === '') {
            $_SESSION['var_toast'] = ['type'=>'error','message'=>'Variation type name is required.'];
        } else {
            $slug = makeSlug($name);
            // ensure unique slug
            $base = $slug; $n = 1;
            while ($model->slugExistsForType($slug)) { $slug = $base . '-' . $n++; }
            $model->addVariationType($name, $slug, $display_type, $sort_order)
                ? $_SESSION['var_toast'] = ['type'=>'success','message'=>'Variation type "' . $name . '" added.']
                : $_SESSION['var_toast'] = ['type'=>'error','message'=>'Error adding variation type.'];
        }
        header('Location: manage-variations.php'); exit();
    }

    // Add variation value
    if (isset($_POST['add_variation_value'])) {
        $type_id    = (int)($_POST['value_type_id'] ?? 0);
        $value      = trim($_POST['value_name'] ?? '');
        $color_code = trim($_POST['color_code'] ?? '') ?: null;
        $sort_order = (int)($_POST['value_sort_order'] ?? 0);
        if (!$type_id || $value === '') {
            $_SESSION['var_toast'] = ['type'=>'error','message'=>'Select a variation type and enter a value.'];
        } else {
            $slug = makeSlug($value);
            $model->addVariationValue($type_id, $value, $slug, $color_code, null, $sort_order)
                ? $_SESSION['var_toast'] = ['type'=>'success','message'=>'Value "' . $value . '" added.']
                : $_SESSION['var_toast'] = ['type'=>'error','message'=>'Error adding variation value.'];
        }
        header('Location: manage-variations.php'); exit();
    }

    // Edit variation type
    if (isset($_POST['edit_variation_type'])) {
        $id           = (int)($_POST['edit_type_id'] ?? 0);
        $name         = trim($_POST['edit_type_name'] ?? '');
        $display_type = $_POST['edit_display_type'] ?? 'button';
        $sort_order   = (int)($_POST['edit_sort_order'] ?? 0);
        if (!$id || $name === '') {
            $_SESSION['var_toast'] = ['type'=>'error','message'=>'Invalid data.'];
        } else {
            $slug = makeSlug($name);
            $base = $slug; $n = 1;
            while ($model->slugExistsForType($slug, $id)) { $slug = $base . '-' . $n++; }
            $model->updateVariationType($id, $name, $slug, $display_type, $sort_order)
                ? $_SESSION['var_toast'] = ['type'=>'success','message'=>'Variation type updated.']
                : $_SESSION['var_toast'] = ['type'=>'error','message'=>'Error updating variation type.'];
        }
        header('Location: manage-variations.php'); exit();
    }

    // Edit variation value
    if (isset($_POST['edit_variation_value'])) {
        $id         = (int)($_POST['edit_value_id'] ?? 0);
        $value      = trim($_POST['edit_value_name'] ?? '');
        $color_code = trim($_POST['edit_color_code'] ?? '') ?: null;
        $sort_order = (int)($_POST['edit_value_sort_order'] ?? 0);
        if (!$id || $value === '') {
            $_SESSION['var_toast'] = ['type'=>'error','message'=>'Invalid data.'];
        } else {
            $slug = makeSlug($value);
            $model->updateVariationValue($id, $value, $slug, $color_code, null, $sort_order)
                ? $_SESSION['var_toast'] = ['type'=>'success','message'=>'Variation value updated.']
                : $_SESSION['var_toast'] = ['type'=>'error','message'=>'Error updating variation value.'];
        }
        header('Location: manage-variations.php'); exit();
    }
}

// -------------------------------------------------------
// GET handlers
// -------------------------------------------------------
if (isset($_GET['delete_type'])) {
    $id = (int)$_GET['delete_type'];
    $model->deleteVariationType($id)
        ? $_SESSION['var_toast'] = ['type'=>'success','message'=>'Variation type deleted.']
        : $_SESSION['var_toast'] = ['type'=>'error','message'=>'Error deleting variation type.'];
    header('Location: manage-variations.php'); exit();
}

if (isset($_GET['delete_value'])) {
    $id = (int)$_GET['delete_value'];
    if ($model->isValueUsedInProducts($id)) {
        $_SESSION['var_toast'] = ['type'=>'error','message'=>'Cannot delete: this value is used in product variations.'];
    } else {
        $model->deleteVariationValue($id)
            ? $_SESSION['var_toast'] = ['type'=>'success','message'=>'Variation value deleted.']
            : $_SESSION['var_toast'] = ['type'=>'error','message'=>'Error deleting variation value.'];
    }
    header('Location: manage-variations.php'); exit();
}

// -------------------------------------------------------
// Fetch for edit modals
// -------------------------------------------------------
$editType  = null;
$editValue = null;
if (isset($_GET['edit_type']))  { $editType  = $model->getVariationTypeById((int)$_GET['edit_type']); }
if (isset($_GET['edit_value'])) { $editValue = $model->getValueById((int)$_GET['edit_value']); }

// -------------------------------------------------------
// Data for display
// -------------------------------------------------------
$typesWithValues = $model->getVariationTypesWithValues();
$allTypes        = $model->getAllVariationTypes();

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Product Variations</title>
<style>
:root {
    --black:#111111; --yellow:#facc15; --yellow-hover:#eab308;
    --light-yellow:#fffbeb; --white:#ffffff; --bg:#f8fafc;
    --border:#e5e7eb; --muted:#6b7280;
}
.vp { max-width:1280px; margin:0 auto; padding:24px; background:var(--bg); }
.vcard {
    background:var(--white); border:1px solid var(--border);
    border-radius:16px; box-shadow:0 8px 24px rgba(17,17,17,.06);
}
/* reset global admin span styles */
.vp span { background:transparent!important; background-color:transparent!important;
    color:inherit!important; padding:0!important; margin:0!important; border-radius:0!important; }
.vp-header { padding:22px 26px; margin-bottom:24px; }
.vp-header h1 { margin:0; font-size:clamp(1.5rem,2vw,1.75rem); font-weight:600; line-height:1.25; letter-spacing:-0.02em; color:var(--black); }
.vp-header p { margin:6px 0 0; color:var(--muted); font-size:.875rem; font-weight:400; line-height:1.5; }
.vp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.vp-form { padding:24px; }
.vp-form h2 { margin:0 0 18px; font-size:1.125rem; font-weight:600; line-height:1.35; letter-spacing:-0.01em; color:var(--black); }
.vp-field { margin-bottom:14px; }
.vp-field label { display:block; margin-bottom:6px; font-size:.88rem; font-weight:700; color:var(--black); }
.vp-field input[type=text],
.vp-field input[type=number],
.vp-field input[type=color],
.vp-field select {
    width:100%; height:46px; border:1px solid var(--border); border-radius:10px;
    padding:0 14px; font-size:.9rem; color:var(--black); background:var(--white);
    outline:none; transition:border-color .15s,box-shadow .15s;
}
.vp-field input[type=color] { padding:4px 8px; cursor:pointer; }
.vp-field input:focus,.vp-field select:focus {
    border-color:var(--yellow); box-shadow:0 0 0 3px rgba(250,204,21,.18);
}
.vp-field select {
    -webkit-appearance:none; appearance:none; padding-right:36px;
    background-image:linear-gradient(45deg,transparent 50%,#111 50%),linear-gradient(135deg,#111 50%,transparent 50%);
    background-position:calc(100% - 18px) 50%,calc(100% - 12px) 50%;
    background-size:6px 6px,6px 6px; background-repeat:no-repeat;
}
.vp-btn {
    display:inline-flex; align-items:center; justify-content:center;
    height:44px; padding:0 18px; border:1px solid var(--black); border-radius:10px;
    background:var(--black); color:var(--white)!important; text-decoration:none!important;
    font-size:.88rem; font-weight:700; cursor:pointer;
    transition:transform .12s,box-shadow .12s,color .12s;
}
.vp-btn:hover { color:var(--yellow)!important; transform:translateY(-1px); box-shadow:0 8px 18px rgba(17,17,17,.14); }
.vp-btn-sm {
    display:inline-flex; align-items:center; justify-content:center;
    height:28px; padding:0 10px; border:1px solid var(--black); border-radius:999px;
    background:var(--black); color:var(--white)!important; text-decoration:none!important;
    font-size:.72rem; font-weight:700; cursor:pointer; white-space:nowrap;
}
.vp-btn-sm:hover { color:var(--yellow)!important; }
/* Color field row */
.color-row { display:none; gap:8px; align-items:center; }
.color-row.visible { display:flex; }
.color-row input[type=color] { width:46px; flex-shrink:0; }
.color-row input[type=text] { flex:1; }
/* Table */
.vtable-wrap { border:1px solid var(--border); border-radius:14px; overflow:hidden; }
.vtable { width:100%; border-collapse:separate; border-spacing:0; }
.vtable th,.vtable td {
    padding:13px 16px; border-bottom:1px solid var(--border);
    text-align:left; font-size:.88rem; vertical-align:top;
}
.vtable th { background:#f9fafb; font-weight:800; color:var(--black); }
.vtable tbody tr:hover { background:var(--light-yellow); }
.vtable tbody tr:last-child td { border-bottom:0; }
.vbadge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:999px; font-size:.78rem; font-weight:700;
    background:var(--light-yellow); border:1px solid var(--yellow); color:var(--black);
}
.vbadge-dot {
    width:10px; height:10px; border-radius:50%; flex-shrink:0;
}
.val-chips { display:flex; flex-wrap:wrap; gap:6px; }
.val-chip {
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 10px; border-radius:999px; font-size:.78rem; font-weight:700;
    background:var(--light-yellow); border:1px solid var(--yellow); color:var(--black);
}
.chip-swatch { width:14px; height:14px; border-radius:50%; border:1px solid rgba(0,0,0,.12); flex-shrink:0; }
.chip-actions { display:inline-flex; gap:4px; }
.row-actions { display:flex; gap:8px; }
.empty-state {
    padding:18px; text-align:center; color:var(--muted);
    border:1px dashed var(--border); border-radius:12px;
}
/* Overlay */
.vp-overlay {
    position:fixed; inset:0; background:rgba(17,17,17,.55);
    display:none; align-items:center; justify-content:center; z-index:1200; padding:16px;
}
.vp-overlay.open { display:flex; }
.vp-modal {
    width:100%; max-width:480px; background:#fff; border:1px solid var(--border);
    border-radius:16px; box-shadow:0 24px 48px rgba(17,17,17,.22); padding:24px;
}
.vp-modal h3 { margin:0 0 18px; font-size:1.125rem; font-weight:600; line-height:1.35; letter-spacing:-0.01em; color:var(--black); }
.modal-actions { margin-top:16px; display:flex; justify-content:flex-end; gap:8px; }
/* Confirm */
#confirmOverlay .vp-modal { max-width:400px; }
/* Toast */
.vp-toast {
    position:fixed; right:16px; bottom:16px; z-index:1400;
    background:#111; color:#fff; border:1px solid var(--yellow); border-left:5px solid var(--yellow);
    border-radius:12px; padding:12px 16px; font-size:.9rem; font-weight:600;
    box-shadow:0 12px 28px rgba(17,17,17,.28); display:none;
}
.vp-toast.show { display:block; }
.vp-toast.is-error { border-left-color:#facc15; }
/* Display type hint moved to label tooltip */
.dtype-hint { display: none; }
/* Section heading */
.section-head { padding:22px 24px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.section-head h2 { margin:0; font-size:1.125rem; font-weight:600; line-height:1.35; letter-spacing:-0.01em; color:var(--black); }
.section-head p { margin:4px 0 0; color:var(--muted); font-size:.88rem; }
@media(max-width:900px) { .vp-grid { grid-template-columns:1fr; } .vp { padding:14px; } }
@media(max-width:600px) {
    .vtable,.vtable thead,.vtable tbody,.vtable tr,.vtable th,.vtable td { display:block; width:100%; }
    .vtable thead { display:none; }
    .vtable tr { border-bottom:1px solid var(--border); padding:8px 0; }
    .vtable td { border-bottom:0; padding:8px 14px; }
}
</style>
</head>
<body>
<div class="vp">

    <!-- Header -->
    <div class="vcard vp-header">
        <h1>Manage Product Variations</h1>
        <p>Create global variation types and values reusable across all products (Color, RAM, Storage, Size, Packets…)</p>
    </div>

    <!-- Add forms -->
    <div class="vp-grid">
        <!-- Add Variation Type -->
        <div class="vcard vp-form">
            <h2>Add Variation Type</h2>
            <form method="POST">
                <div class="vp-field">
                    <label>Variation Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="type_name" placeholder="e.g. Color, RAM, Storage, Size" required>
                </div>
                <div class="vp-field">
                    <label>Display Type <?= adminTooltipIcon('Values show as clickable pill buttons.', 'addDtypeTooltip') ?></label>
                    <select name="display_type" id="addDisplayType" onchange="toggleAddColorHint(this.value)">
                        <option value="button">Button / Text</option>
                        <option value="color">Color Swatch</option>
                        <option value="image">Image Swatch</option>
                    </select>
                </div>
                <div class="vp-field">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
                <button class="vp-btn" type="submit" name="add_variation_type">Add Variation Type</button>
            </form>
        </div>

        <!-- Add Variation Value -->
        <div class="vcard vp-form">
            <h2>Add Variation Value</h2>
            <form method="POST">
                <div class="vp-field">
                    <label>Select Variation Type <span class="req-star" aria-hidden="true">*</span></label>
                    <select name="value_type_id" id="addValueTypeSelect" onchange="updateColorFieldVisibility()" required>
                        <option value="">— Select Type —</option>
                        <?php foreach ($allTypes as $t): ?>
                        <option value="<?= $t['id'] ?>" data-dtype="<?= htmlspecialchars($t['display_type']) ?>">
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vp-field">
                    <label>Value Name <span class="req-star" aria-hidden="true">*</span></label>
                    <input type="text" name="value_name" placeholder="e.g. Black, 8GB, 256GB, 1 Pack" required>
                </div>
                <div class="vp-field" id="colorFieldWrap" style="display:none">
                    <label>Color Code</label>
                    <div class="color-row visible">
                        <input type="color" id="colorPicker" value="#000000" oninput="syncColorInput(this)">
                        <input type="text" name="color_code" id="colorText" placeholder="#000000" oninput="syncColorPicker(this)">
                    </div>
                </div>
                <div class="vp-field">
                    <label>Sort Order</label>
                    <input type="number" name="value_sort_order" value="0" min="0">
                </div>
                <button class="vp-btn" type="submit" name="add_variation_value">Add Value</button>
            </form>
        </div>
    </div>

    <!-- Existing Types Table -->
    <div class="vcard">
        <div class="section-head">
            <div>
                <h2>Existing Variation Types &amp; Values</h2>
                <p>All global variation types and their values. Used across products.</p>
            </div>
        </div>
        <div style="padding:0 24px 24px">
        <?php if (empty($typesWithValues)): ?>
            <div class="empty-state">No variation types found. Add your first variation type above.</div>
        <?php else: ?>
            <div class="vtable-wrap">
                <table class="vtable">
                    <thead>
                        <tr>
                            <th style="width:180px">Variation Type</th>
                            <th style="width:110px">Display Type</th>
                            <th>Values</th>
                            <th style="width:160px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($typesWithValues as $type): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($type['name']) ?></strong><br>
                            <span style="color:var(--muted);font-size:.78rem"><?= htmlspecialchars($type['slug']) ?></span>
                        </td>
                        <td>
                            <span class="vbadge">
                                <?php if ($type['display_type'] === 'color'): ?>
                                    <span class="vbadge-dot" style="background:linear-gradient(135deg,#f00,#00f)"></span> Color
                                <?php elseif ($type['display_type'] === 'image'): ?>
                                    🖼 Image
                                <?php else: ?>
                                    ⊞ Button
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <div class="val-chips">
                            <?php if (empty($type['values'])): ?>
                                <span style="color:var(--muted);font-size:.82rem">No values yet.</span>
                            <?php else: ?>
                                <?php foreach ($type['values'] as $val): ?>
                                <div class="val-chip">
                                    <?php if ($type['display_type'] === 'color' && !empty($val['color_code'])): ?>
                                        <span class="chip-swatch" style="background:<?= htmlspecialchars($val['color_code']) ?>"></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($val['value']) ?>
                                    <span class="chip-actions">
                                        <a class="vp-btn-sm"
                                           href="?edit_value=<?= $val['id'] ?>"
                                           onclick="event.preventDefault(); openEditValueModal(<?= htmlspecialchars(json_encode($val)) ?>, '<?= htmlspecialchars($type['display_type']) ?>')">Edit</a>
                                        <a class="vp-btn-sm delete-action" href="?delete_value=<?= $val['id'] ?>"
                                           data-confirm="Delete value &quot;<?= htmlspecialchars($val['value']) ?>&quot;?">Del</a>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a class="vp-btn" style="font-size:.78rem;height:36px;padding:0 12px"
                                   href="?edit_type=<?= $type['id'] ?>"
                                   onclick="event.preventDefault(); openEditTypeModal(<?= htmlspecialchars(json_encode($type)) ?>)">Edit</a>
                                <a class="vp-btn delete-action" style="font-size:.78rem;height:36px;padding:0 12px"
                                   href="?delete_type=<?= $type['id'] ?>"
                                   data-confirm="Delete variation type &quot;<?= htmlspecialchars($type['name']) ?>&quot; and all its values?">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        </div>
    </div>

</div>

<!-- Edit Type Modal -->
<div class="vp-overlay" id="editTypeOverlay">
    <div class="vp-modal">
        <h3>Edit Variation Type</h3>
        <form method="POST">
            <input type="hidden" name="edit_type_id" id="etId">
            <div class="vp-field">
                <label>Variation Name <span class="req-star" aria-hidden="true">*</span></label>
                <input type="text" name="edit_type_name" id="etName" required>
            </div>
            <div class="vp-field">
                <label>Display Type</label>
                <select name="edit_display_type" id="etDisplayType">
                    <option value="button">Button / Text</option>
                    <option value="color">Color Swatch</option>
                    <option value="image">Image Swatch</option>
                </select>
            </div>
            <div class="vp-field">
                <label>Sort Order</label>
                <input type="number" name="edit_sort_order" id="etSortOrder" min="0">
            </div>
            <div class="modal-actions">
                <button type="button" class="vp-btn" onclick="closeModal('editTypeOverlay')">Cancel</button>
                <button type="submit" class="vp-btn" name="edit_variation_type">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Value Modal -->
<div class="vp-overlay" id="editValueOverlay">
    <div class="vp-modal">
        <h3>Edit Variation Value</h3>
        <form method="POST">
            <input type="hidden" name="edit_value_id" id="evId">
            <div class="vp-field">
                <label>Value Name <span class="req-star" aria-hidden="true">*</span></label>
                <input type="text" name="edit_value_name" id="evName" required>
            </div>
            <div class="vp-field" id="evColorWrap" style="display:none">
                <label>Color Code</label>
                <div class="color-row visible">
                    <input type="color" id="evColorPicker" oninput="evSyncText(this)">
                    <input type="text" name="edit_color_code" id="evColorText" placeholder="#000000" oninput="evSyncPicker(this)">
                </div>
            </div>
            <div class="vp-field">
                <label>Sort Order</label>
                <input type="number" name="edit_value_sort_order" id="evSortOrder" min="0">
            </div>
            <div class="modal-actions">
                <button type="button" class="vp-btn" onclick="closeModal('editValueOverlay')">Cancel</button>
                <button type="submit" class="vp-btn" name="edit_variation_value">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Modal -->
<div class="vp-overlay" id="confirmOverlay">
    <div class="vp-modal">
        <h3>Confirm Delete</h3>
        <p id="confirmMsg" style="color:var(--muted);font-size:.92rem">Are you sure?</p>
        <div class="modal-actions">
            <button class="vp-btn" onclick="closeModal('confirmOverlay')">Cancel</button>
            <button class="vp-btn" id="confirmYes">Delete</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="vp-toast <?= $toastType==='error'?'is-error':'' ?>" id="vpToast"
     data-msg="<?= htmlspecialchars($toast, ENT_QUOTES) ?>">
    <?= htmlspecialchars($toast) ?>
</div>

<script>
// ---- Color picker sync ----
function syncColorInput(picker) {
    document.getElementById('colorText').value = picker.value;
}
function syncColorPicker(input) {
    const v = input.value;
    if (/^#[0-9a-f]{6}$/i.test(v)) document.getElementById('colorPicker').value = v;
}
function evSyncText(picker) {
    document.getElementById('evColorText').value = picker.value;
}
function evSyncPicker(input) {
    const v = input.value;
    if (/^#[0-9a-f]{6}$/i.test(v)) document.getElementById('evColorPicker').value = v;
}

// ---- Display type hint ----
const hints = {button:'Values show as clickable pill buttons.',color:'Values show as circular color swatches.',image:'Values show as small image swatches.'};
function toggleAddColorHint(val) {
    const tooltip = document.getElementById('addDtypeTooltip');
    if (tooltip) {
        tooltip.setAttribute('data-tooltip', hints[val] || hints.button);
    }
}

// ---- Show/hide color field in "Add Value" form ----
function updateColorFieldVisibility() {
    const sel = document.getElementById('addValueTypeSelect');
    const opt = sel.options[sel.selectedIndex];
    const dtype = opt ? opt.dataset.dtype : '';
    document.getElementById('colorFieldWrap').style.display = dtype === 'color' ? 'block' : 'none';
}

// ---- Modals ----
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEditTypeModal(data) {
    document.getElementById('etId').value          = data.id;
    document.getElementById('etName').value        = data.name;
    document.getElementById('etDisplayType').value = data.display_type;
    document.getElementById('etSortOrder').value   = data.sort_order;
    openModal('editTypeOverlay');
}

function openEditValueModal(data, dtype) {
    document.getElementById('evId').value        = data.id;
    document.getElementById('evName').value      = data.value;
    document.getElementById('evSortOrder').value = data.sort_order || 0;
    const colorWrap = document.getElementById('evColorWrap');
    if (dtype === 'color') {
        colorWrap.style.display = 'block';
        const c = data.color_code || '#000000';
        document.getElementById('evColorText').value   = c;
        document.getElementById('evColorPicker').value = /^#[0-9a-f]{6}$/i.test(c) ? c : '#000000';
    } else {
        colorWrap.style.display = 'none';
    }
    openModal('editValueOverlay');
}

// ---- Confirm delete ----
let pendingHref = '';
document.querySelectorAll('.delete-action').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        pendingHref = this.href;
        document.getElementById('confirmMsg').textContent = this.dataset.confirm || 'Are you sure you want to delete this?';
        openModal('confirmOverlay');
    });
});
document.getElementById('confirmYes').addEventListener('click', function() {
    if (pendingHref) window.location.href = pendingHref;
});
document.querySelectorAll('.vp-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeModal(overlay.id);
    });
});

// ---- Toast ----
const toast = document.getElementById('vpToast');
if (toast && toast.dataset.msg) {
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

// open modals if server directed
<?php if ($editType):  ?>openEditTypeModal(<?= json_encode($editType) ?>);<?php endif; ?>
<?php if ($editValue):
    $et = $model->getVariationTypeById($editValue['variation_type_id']);
    $editDtype = $et ? $et['display_type'] : 'button';
?>openEditValueModal(<?= json_encode($editValue) ?>, '<?= $editDtype ?>');<?php endif; ?>
</script>
</body>
</html>

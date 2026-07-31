<?php
/**
 * Group products picker (accessories / frequently bought together).
 *
 * Expected vars:
 * - $groupProducts (array) currently selected children
 * - $groupExcludeProductId (int) parent product id to exclude from search
 */
$groupProducts = $groupProducts ?? [];
$groupExcludeProductId = (int) ($groupExcludeProductId ?? 0);
?>
<div class="ep-card pg-card" style="margin-top:20px;padding:24px">
    <div style="margin-bottom:16px">
        <h3 style="margin:0;font-size:1.05rem;font-weight:800;color:#111">Group Products</h3>
        <p style="margin:4px 0 0;color:#6b7280;font-size:.88rem">
            Link accessories (cables, adapters, cases, etc.). Customers can add them with this product from the product page.
        </p>
    </div>

    <div class="pg-picker">
        <label class="pg-label" for="pgSearchInput">Search &amp; add products</label>
        <div class="pg-search-wrap">
            <input type="search" id="pgSearchInput" class="pg-search" placeholder="Type product name, SKU, or ID…" autocomplete="off">
            <div id="pgSearchResults" class="pg-results" hidden></div>
        </div>

        <div id="pgSelectedList" class="pg-selected">
            <?php if (empty($groupProducts)): ?>
                <p class="pg-empty" id="pgEmptyMsg">No group products added yet.</p>
            <?php else: ?>
                <p class="pg-empty" id="pgEmptyMsg" hidden>No group products added yet.</p>
            <?php endif; ?>

            <?php foreach ($groupProducts as $gp):
                $gpId = (int) ($gp['product_id'] ?? 0);
                if ($gpId <= 0) continue;
                $gpName = (string) ($gp['product_name'] ?? ('Product #' . $gpId));
                $gpSku = trim((string) ($gp['product_sku'] ?? ''));
                $gpImg = (string) ($gp['image_url'] ?? '');
                $sale = isset($gp['sale_price']) && is_numeric($gp['sale_price']) ? (float) $gp['sale_price'] : 0;
                $regular = isset($gp['regular_price']) && is_numeric($gp['regular_price']) ? (float) $gp['regular_price'] : 0;
                $price = $sale > 0 ? $sale : $regular;
            ?>
                <div class="pg-item" data-id="<?= $gpId ?>">
                    <input type="hidden" name="group_product_ids[]" value="<?= $gpId ?>">
                    <?php if ($gpImg !== ''): ?>
                        <img class="pg-item-img" src="<?= htmlspecialchars($gpImg, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <div class="pg-item-img pg-item-img--placeholder"></div>
                    <?php endif; ?>
                    <div class="pg-item-meta">
                        <div class="pg-item-name"><?= htmlspecialchars($gpName, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="pg-item-sub">
                            #<?= $gpId ?>
                            <?php if ($gpSku !== ''): ?> · <?= htmlspecialchars($gpSku, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            <?php if ($price > 0): ?> · Rs. <?= number_format($price) ?><?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="pg-remove" title="Remove">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.pg-label { display:block; font-size:.82rem; font-weight:700; color:#374151; margin-bottom:6px; }
.pg-search-wrap { position:relative; max-width:520px; }
.pg-search {
    width:100%; height:42px; padding:0 14px; border:1px solid #e5e7eb; border-radius:10px;
    font-size:.9rem; background:#fff; outline:none;
}
.pg-search:focus { border-color:#facc15; box-shadow:0 0 0 3px rgba(250,204,21,.25); }
.pg-results {
    position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:40;
    background:#fff; border:1px solid #e5e7eb; border-radius:12px;
    box-shadow:0 12px 28px rgba(0,0,0,.1); max-height:280px; overflow:auto;
}
.pg-result {
    display:flex; align-items:center; gap:10px; padding:10px 12px; cursor:pointer;
    border-bottom:1px solid #f3f4f6;
}
.pg-result:last-child { border-bottom:0; }
.pg-result:hover { background:#fffbeb; }
.pg-result-img, .pg-item-img {
    width:40px; height:40px; object-fit:cover; border-radius:8px; background:#f3f4f6; flex-shrink:0;
}
.pg-item-img--placeholder { background:#f3f4f6; }
.pg-result-name { font-size:.88rem; font-weight:700; color:#111; }
.pg-result-sub { font-size:.75rem; color:#6b7280; margin-top:2px; }
.pg-selected { margin-top:16px; display:flex; flex-direction:column; gap:8px; max-width:640px; }
.pg-empty { margin:0; color:#9ca3af; font-size:.88rem; }
.pg-item {
    display:flex; align-items:center; gap:12px; padding:10px 12px;
    border:1px solid #e5e7eb; border-radius:12px; background:#fff;
}
.pg-item-meta { flex:1; min-width:0; }
.pg-item-name { font-size:.9rem; font-weight:700; color:#111; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pg-item-sub { font-size:.75rem; color:#6b7280; margin-top:2px; }
.pg-remove {
    width:32px; height:32px; border:1px solid #fecaca; border-radius:8px;
    background:#fff; color:#ef4444; font-size:1.2rem; line-height:1; cursor:pointer;
}
.pg-remove:hover { background:#fef2f2; }
</style>

<script>
(function () {
    const searchInput = document.getElementById('pgSearchInput');
    const resultsEl = document.getElementById('pgSearchResults');
    const listEl = document.getElementById('pgSelectedList');
    const emptyMsg = document.getElementById('pgEmptyMsg');
    if (!searchInput || !resultsEl || !listEl) return;

    const excludeId = <?= (int) $groupExcludeProductId ?>;
    const adminBase = <?= json_encode(rtrim((defined('BASE_PATH') ? BASE_PATH : ''), '/')) ?>;
    let debounceTimer = null;
    let abortCtrl = null;

    function searchUrl(q) {
        const path = '/admin/ajax-search-products.php?q=' + encodeURIComponent(q) + '&exclude=' + excludeId;
        if (adminBase && path.indexOf(adminBase) !== 0) return adminBase + path;
        return path;
    }

    function selectedIds() {
        return Array.from(listEl.querySelectorAll('input[name="group_product_ids[]"]'))
            .map(function (el) { return parseInt(el.value, 10); })
            .filter(Boolean);
    }

    function syncEmpty() {
        if (!emptyMsg) return;
        const hasItems = listEl.querySelectorAll('.pg-item').length > 0;
        emptyMsg.hidden = hasItems;
    }

    function formatPrice(sale, regular) {
        const s = parseFloat(sale) || 0;
        const r = parseFloat(regular) || 0;
        const p = s > 0 ? s : r;
        return p > 0 ? ('Rs. ' + Math.round(p).toLocaleString()) : '';
    }

    function addProduct(p) {
        const id = parseInt(p.product_id, 10);
        if (!id || selectedIds().indexOf(id) !== -1) return;

        const item = document.createElement('div');
        item.className = 'pg-item';
        item.dataset.id = String(id);

        const price = formatPrice(p.sale_price, p.regular_price);
        const sku = (p.product_sku || '').trim();
        const imgHtml = p.image_url
            ? '<img class="pg-item-img" src="' + String(p.image_url).replace(/"/g, '&quot;') + '" alt="">'
            : '<div class="pg-item-img pg-item-img--placeholder"></div>';

        item.innerHTML =
            '<input type="hidden" name="group_product_ids[]" value="' + id + '">' +
            imgHtml +
            '<div class="pg-item-meta">' +
                '<div class="pg-item-name"></div>' +
                '<div class="pg-item-sub"></div>' +
            '</div>' +
            '<button type="button" class="pg-remove" title="Remove">&times;</button>';

        item.querySelector('.pg-item-name').textContent = p.product_name || ('Product #' + id);
        item.querySelector('.pg-item-sub').textContent =
            '#' + id + (sku ? ' · ' + sku : '') + (price ? ' · ' + price : '');

        listEl.appendChild(item);
        syncEmpty();
        resultsEl.hidden = true;
        resultsEl.innerHTML = '';
        searchInput.value = '';
    }

    listEl.addEventListener('click', function (e) {
        const btn = e.target.closest('.pg-remove');
        if (!btn) return;
        const item = btn.closest('.pg-item');
        if (item) item.remove();
        syncEmpty();
    });

    function renderResults(products) {
        resultsEl.innerHTML = '';
        const already = selectedIds();
        const filtered = (products || []).filter(function (p) {
            return already.indexOf(parseInt(p.product_id, 10)) === -1;
        });

        if (!filtered.length) {
            resultsEl.innerHTML = '<div class="pg-result"><div class="pg-result-name">No products found</div></div>';
            resultsEl.hidden = false;
            return;
        }

        filtered.forEach(function (p) {
            const row = document.createElement('div');
            row.className = 'pg-result';
            const price = formatPrice(p.sale_price, p.regular_price);
            const sku = (p.product_sku || '').trim();
            row.innerHTML =
                (p.image_url
                    ? '<img class="pg-result-img" src="' + String(p.image_url).replace(/"/g, '&quot;') + '" alt="">'
                    : '<div class="pg-result-img pg-item-img--placeholder"></div>') +
                '<div><div class="pg-result-name"></div><div class="pg-result-sub"></div></div>';
            row.querySelector('.pg-result-name').textContent = p.product_name || ('Product #' + p.product_id);
            row.querySelector('.pg-result-sub').textContent =
                '#' + p.product_id + (sku ? ' · ' + sku : '') + (price ? ' · ' + price : '');
            row.addEventListener('click', function () { addProduct(p); });
            resultsEl.appendChild(row);
        });
        resultsEl.hidden = false;
    }

    searchInput.addEventListener('input', function () {
        const q = searchInput.value.trim();
        clearTimeout(debounceTimer);
        if (q.length < 1) {
            resultsEl.hidden = true;
            resultsEl.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(function () {
            if (abortCtrl) abortCtrl.abort();
            abortCtrl = new AbortController();
            fetch(searchUrl(q), { signal: abortCtrl.signal, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.status === 'success') renderResults(data.products || []);
                })
                .catch(function () { /* ignore abort/network */ });
        }, 250);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.pg-search-wrap')) {
            resultsEl.hidden = true;
        }
    });
})();
</script>

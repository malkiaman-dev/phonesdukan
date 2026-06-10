<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Include the sidebar and header
include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';

// Fetch all contact messages
$query = "SELECT id, name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC";
$stmt = $conn->query($query);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contact Messages - Phones Dukan</title>
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

        body { background: var(--bg); color: var(--black); }

        .msg-wrap { max-width: 1320px; margin: 0 auto; padding: 20px; }

        .msg-header,
        .msg-toolbar,
        .msg-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .msg-header { padding: 20px 24px; margin-bottom: 14px; }
        .msg-title {
            font-size: clamp(1.5rem, 2vw, 1.75rem);
            font-weight: 600;
            line-height: 1.25;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .msg-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
        }

        .msg-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 14px 16px;
            margin-bottom: 14px;
            overflow: visible;
            position: relative;
            z-index: 5;
        }

        .msg-left, .msg-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .msg-left { justify-content: flex-start; }
        .msg-right { justify-content: flex-end; }

        .msg-input {
            width: 340px;
            max-width: 100%;
            height: 48px;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0 14px;
            outline: none;
            background: #fff;
            color: var(--black);
        }
        .msg-input:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }

        .msg-search-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }

        .msg-btn {
            height: 44px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid #e6bd00;
            background: #f7cf04;
            color: #111111 !important;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none !important;
            box-shadow: 0 4px 14px rgba(247, 207, 4, 0.22);
            transition: background .2s ease, border-color .2s ease, box-shadow .2s ease, transform .15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .msg-btn:hover {
            background: #e6bd00;
            color: #111111 !important;
            border-color: #d4af00;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(247, 207, 4, 0.28);
        }

        .msg-btn-outline {
            border: 1.5px solid var(--border);
            background: #fff;
            color: var(--black) !important;
            box-shadow: none;
        }
        .msg-btn-outline:hover {
            border-color: #f7cf04;
            background: #fffef8;
            color: var(--black) !important;
            transform: translateY(-1px);
            box-shadow: 0 0 0 3px rgba(247, 207, 4, 0.15);
        }

        .msg-btn.view {
            min-width: 64px;
            height: 38px;
            padding: 0 12px;
            font-size: 0.82rem;
        }

        .msg-native-select {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
            overflow: hidden !important;
        }

        .msg-select-wrap { position: relative; min-width: 150px; }
        .msg-select-display {
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
        .msg-select-display::after {
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
        .msg-select-display:hover,
        .msg-select-wrap.is-open .msg-select-display {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }
        .msg-select-options {
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
            max-height: 240px;
            overflow: auto;
        }
        .msg-select-wrap.is-open .msg-select-options { display: block; }
        .msg-select-option {
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
        .msg-select-option:hover { background: var(--light-yellow); }
        .msg-select-option.is-selected { background: var(--yellow); }

        .msg-card { overflow: hidden; }
        .msg-table-wrap { overflow-x: auto; }
        .msg-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .msg-table th, .msg-table td {
            padding: 14px;
            text-align: left;
            border: 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            vertical-align: top;
            background: transparent;
        }
        .msg-table th {
            background: #f9fafb;
            color: var(--black);
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .msg-table tbody tr:hover { background: var(--light-yellow); }
        .msg-table td.message,
        .msg-table td.subject {
            max-width: 320px;
            white-space: normal;
            word-break: break-word;
        }

        .msg-pagebar {
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .msg-page-controls {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-left: auto;
        }
        .msg-page-btn {
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
        .msg-page-btn:hover { border-color: var(--yellow); background: var(--light-yellow); }
        .msg-page-btn.is-active { border-color: var(--yellow); background: var(--yellow); color: var(--black); }
        .msg-page-btn:disabled { opacity: .45; cursor: not-allowed; }
        .msg-page-meta { color: var(--muted); font-size: 0.85rem; font-weight: 700; }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-content .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .msg-empty {
            text-align: center;
            color: var(--muted);
            font-weight: 700;
            padding: 22px;
        }

        @media (max-width: 900px) {
            .msg-input { width: 100%; }
            .msg-search-group { width: 100%; }
        }
    </style>
    <!-- Main Content -->
    <div class="msg-wrap">
        <div class="msg-header">
            <h2 class="msg-title">Manage Contact Messages</h2>
            <p class="msg-subtitle">Review and respond to incoming customer inquiries.</p>
        </div>

        <div class="msg-toolbar">
            <div class="msg-left">
                <div class="msg-select-wrap" data-msg-select data-msg-filter>
                    <select id="msgSubjectFilter" class="msg-native-select">
                        <option value="">All Subjects</option>
                        <?php
                        $subjectValues = [];
                        foreach ($messages as $m) {
                            $subj = trim((string)($m['subject'] ?? ''));
                            if ($subj !== '') $subjectValues[$subj] = true;
                        }
                        foreach (array_keys($subjectValues) as $subj): ?>
                            <option value="<?= htmlspecialchars($subj) ?>"><?= htmlspecialchars($subj) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="msg-select-display" data-msg-display>All Subjects</button>
                    <div class="msg-select-options">
                        <button type="button" class="msg-select-option" data-value="">All Subjects</button>
                        <?php foreach (array_keys($subjectValues) as $subj): ?>
                            <button type="button" class="msg-select-option" data-value="<?= htmlspecialchars($subj) ?>"><?= htmlspecialchars($subj) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="msg-select-wrap" data-msg-select data-msg-perpage>
                    <select id="msgPerPage" class="msg-native-select">
                        <option value="20">20 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                    </select>
                    <button type="button" class="msg-select-display" data-msg-display>20 / page</button>
                    <div class="msg-select-options">
                        <button type="button" class="msg-select-option" data-value="20">20 / page</button>
                        <button type="button" class="msg-select-option" data-value="50">50 / page</button>
                        <button type="button" class="msg-select-option" data-value="100">100 / page</button>
                    </div>
                </div>

                <button type="button" class="msg-btn msg-btn-outline" id="msgExportCsv">Export CSV</button>
            </div>

            <div class="msg-right">
                <div class="msg-search-group">
                    <input type="search" id="msgSearch" class="msg-input" placeholder="Search by name, email, subject or message...">
                    <button type="button" class="msg-btn" id="msgSearchBtn">Search</button>
                </div>
            </div>
        </div>

        <div class="msg-card">
            <div class="msg-table-wrap">
                <table class="msg-table" id="msgTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $message) { ?>
                                <?php
                                $decoded_message = html_entity_decode($message['message'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $clean_message = str_replace(['<br />', '<br>'], ' ', $decoded_message);
                                $subjectText = trim((string)($message['subject'] ?? ''));
                                $searchBlob = strtolower(
                                    ((string)($message['name'] ?? '')) . ' ' .
                                    ((string)($message['email'] ?? '')) . ' ' .
                                    $subjectText . ' ' .
                                    $clean_message
                                );
                                ?>
                                <tr data-subject="<?= htmlspecialchars($subjectText, ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>">
                                    <td><?= htmlspecialchars($message['id']) ?></td>
                                    <td><?= htmlspecialchars($message['name']) ?></td>
                                    <td><?= htmlspecialchars($message['email']) ?></td>
                                    <td class="subject"><?= htmlspecialchars($subjectText !== '' ? $subjectText : 'No Subject') ?></td>
                                    <td class="message"><?= htmlspecialchars($clean_message) ?></td>
                                    <td><?= date('d-M-Y H:i', strtotime($message['created_at'])) ?></td>
                                    <td>
                                        <button class="msg-btn view" data-message-id="<?= htmlspecialchars($message['id']) ?>">View</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="msg-empty">No contact messages found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="msg-pagebar">
            <div class="msg-page-controls" id="msgPagination"></div>
            <div class="msg-page-meta" id="msgPageMeta">Page 1 of 1</div>
        </div>
    </div>

    <!-- Message Details Modal -->
    <div id="msg-modal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <div id="msg-detail-content">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const modalContainerId = "custom-msg-modal";
        const table = document.getElementById("msgTable");
        const search = document.getElementById("msgSearch");
        const searchBtn = document.getElementById("msgSearchBtn");
        const subjectFilter = document.getElementById("msgSubjectFilter");
        const perPageEl = document.getElementById("msgPerPage");
        const exportBtn = document.getElementById("msgExportCsv");
        const paginationEl = document.getElementById("msgPagination");
        const pageMetaEl = document.getElementById("msgPageMeta");

        let currentPage = 1;

        // Handle View button clicks
        document.querySelectorAll(".msg-btn.view").forEach(function (button) {
            button.addEventListener("click", function () {
                let messageId = this.getAttribute("data-message-id");

                fetch("get_message_details.php?message_id=" + messageId)
                    .then(response => response.text())
                    .then(data => {
                        let modalContainer = document.getElementById(modalContainerId);

                        if (!modalContainer) {
                            modalContainer = document.createElement("div");
                            modalContainer.id = modalContainerId;
                            modalContainer.className = "modal";
                            document.body.appendChild(modalContainer);
                        }

                        modalContainer.innerHTML = data;
                        modalContainer.style.display = "flex";

                        // Attach close event
                        setTimeout(() => {
                            const closeButton = modalContainer.querySelector(".close");
                            if (closeButton) {
                                closeButton.addEventListener("click", function () {
                                    modalContainer.style.display = "none";
                                });
                            }
                        }, 100);
                    })
                    .catch(error => console.error("Error loading message details:", error));
            });
        });

        // Close modal when clicking outside
        document.addEventListener("click", function (event) {
            const modalContainer = document.getElementById(modalContainerId);
            if (modalContainer && event.target === modalContainer) {
                modalContainer.style.display = "none";
            }
        });

        // Custom dropdowns
        document.querySelectorAll("[data-msg-select]").forEach((wrap) => {
            const nativeSelect = wrap.querySelector("select");
            const display = wrap.querySelector("[data-msg-display]");
            const options = Array.from(wrap.querySelectorAll(".msg-select-option"));
            if (!nativeSelect || !display || options.length === 0) return;

            function sync() {
                const selected = options.find(opt => opt.dataset.value === nativeSelect.value);
                display.textContent = selected ? selected.textContent.trim() : (nativeSelect.options[nativeSelect.selectedIndex]?.text || "Select");
                options.forEach(opt => opt.classList.toggle("is-selected", opt.dataset.value === nativeSelect.value));
            }

            display.addEventListener("click", function (e) {
                e.stopPropagation();
                document.querySelectorAll(".msg-select-wrap.is-open").forEach(function (o) {
                    if (o !== wrap) o.classList.remove("is-open");
                });
                wrap.classList.toggle("is-open");
            });

            options.forEach((opt) => {
                opt.addEventListener("click", function () {
                    nativeSelect.value = this.dataset.value;
                    nativeSelect.dispatchEvent(new Event("change", { bubbles: true }));
                    sync();
                    wrap.classList.remove("is-open");
                    currentPage = 1;
                    applyFiltersAndPagination();
                });
            });
            sync();
        });

        document.addEventListener("click", function () {
            document.querySelectorAll(".msg-select-wrap.is-open").forEach((wrap) => wrap.classList.remove("is-open"));
        });

        function renderPagination(totalPages) {
            if (!paginationEl) return;
            paginationEl.innerHTML = "";
            if (totalPages <= 1) return;

            function makeBtn(label, page, disabled, active) {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.className = "msg-page-btn" + (active ? " is-active" : "");
                btn.textContent = label;
                btn.disabled = !!disabled;
                btn.addEventListener("click", function () {
                    if (disabled) return;
                    currentPage = page;
                    applyFiltersAndPagination();
                });
                return btn;
            }

            paginationEl.appendChild(makeBtn("Prev", Math.max(1, currentPage - 1), currentPage === 1, false));
            const windowSize = 2;
            const start = Math.max(1, currentPage - windowSize);
            const end = Math.min(totalPages, currentPage + windowSize);
            for (let p = start; p <= end; p++) {
                paginationEl.appendChild(makeBtn(String(p), p, false, p === currentPage));
            }
            paginationEl.appendChild(makeBtn("Next", Math.min(totalPages, currentPage + 1), currentPage === totalPages, false));
        }

        function applyFiltersAndPagination() {
            if (!table || !subjectFilter || !perPageEl) return;
            const rows = Array.from(table.querySelectorAll("tbody tr"));
            const q = (search?.value || "").trim().toLowerCase();
            const subjectVal = (subjectFilter.value || "");
            const perPage = parseInt(perPageEl.value || "20", 10) || 20;

            const matched = [];
            rows.forEach((row) => {
                if (row.querySelector(".msg-empty")) {
                    row.style.display = "none";
                    return;
                }
                const hay = row.dataset.search || "";
                const rowSubject = row.dataset.subject || "";
                const searchMatch = q === "" || hay.includes(q);
                const subjectMatch = subjectVal === "" || rowSubject === subjectVal;
                const show = searchMatch && subjectMatch;
                row.dataset._match = show ? "1" : "0";
                if (show) matched.push(row);
            });

            const total = matched.length;
            const totalPages = Math.max(1, Math.ceil(total / perPage));
            if (currentPage > totalPages) currentPage = totalPages;

            rows.forEach((row) => {
                if (!row.querySelector(".msg-empty")) row.style.display = "none";
            });
            const start = (currentPage - 1) * perPage;
            matched.slice(start, start + perPage).forEach((row) => row.style.display = "");

            if (pageMetaEl) pageMetaEl.textContent = "Page " + currentPage + " of " + totalPages;
            renderPagination(totalPages);
        }

        if (search && searchBtn) {
            search.addEventListener("input", function () {
                currentPage = 1;
                applyFiltersAndPagination();
            });
            searchBtn.addEventListener("click", function () {
                currentPage = 1;
                applyFiltersAndPagination();
            });
        }

        if (subjectFilter) {
            subjectFilter.addEventListener("change", function () {
                currentPage = 1;
                applyFiltersAndPagination();
            });
        }
        if (perPageEl) {
            perPageEl.addEventListener("change", function () {
                currentPage = 1;
                applyFiltersAndPagination();
            });
        }

        if (exportBtn && table) {
            exportBtn.addEventListener("click", function () {
                const headers = Array.from(table.querySelectorAll("thead th")).map((th) => (th.textContent || "").trim());
                const rows = Array.from(table.querySelectorAll("tbody tr")).filter((tr) => tr.dataset._match !== "0" && !tr.querySelector(".msg-empty"));
                const lines = [headers.join(",")];
                rows.forEach((tr) => {
                    const cols = Array.from(tr.querySelectorAll("td")).map((td) => {
                        const value = (td.textContent || "").trim().replace(/\s+/g, " ");
                        return '"' + value.replace(/"/g, '""') + '"';
                    });
                    lines.push(cols.join(","));
                });
                const csv = lines.join("\n");
                const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "contact-messages.csv";
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            });
        }

        applyFiltersAndPagination();
    });
    </script>
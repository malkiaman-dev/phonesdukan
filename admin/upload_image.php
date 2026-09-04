<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = __DIR__ . '/../public/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
    $originalName = preg_replace("/[^a-zA-Z0-9_-]/", "", $originalName);
    $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $fileName = $originalName . '.' . $fileExtension;

    $counter = 1;
    while (file_exists($uploadDir . $fileName)) {
        $fileName = $originalName . "_" . $counter . '.' . $fileExtension;
        $counter++;
    }

    $filePath = $uploadDir . $fileName;
    // Always store a site-relative path so images survive domain/base-path changes.
    $fileUrl = normalizeStoredUploadPath('/public/uploads/' . $fileName);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary, status) VALUES (NULL, ?, 0, 1)");
        $stmt->execute([$fileUrl]);
        $imageId = $conn->lastInsertId();

        if ($imageId) {
            $altText = $_POST['alt_text'] ?? '';
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $caption = $_POST['caption'] ?? '';

            $metaStmt = $conn->prepare("INSERT INTO image_metadata (image_id, alt_text, title, description, caption) VALUES (?, ?, ?, ?, ?)");
            $metaStmt->execute([$imageId, $altText, $title, $description, $caption]);
        }

        $viewUrl = url(ltrim($fileUrl, '/'));
        echo "<p class='success'>Image uploaded successfully! <a href='$viewUrl' target='_blank'>View Image</a></p>";
    } else {
        echo "<p class='error'>Error uploading file.</p>";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --light-yellow: #fffbeb;
            --white: #ffffff;
            --border: #e5e7eb;
            --muted: #6b7280;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff;
            color: var(--black);
        }
        .up-wrap {
            padding: 12px;
        }
        .up-title {
            margin: 0 0 14px;
            font-size: 1.35rem;
            font-weight: 800;
        }
        #drop-area {
            width: 100%;
            min-height: 190px;
            border: 1px dashed var(--border);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            background-color: #fff;
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
            cursor: pointer;
            margin-bottom: 14px;
            color: var(--muted);
            font-weight: 700;
            padding: 12px;
        }
        #drop-area:hover, #drop-area.highlight {
            border-color: var(--yellow);
            background-color: var(--light-yellow);
            box-shadow: 0 10px 22px rgba(17, 17, 17, 0.06);
            color: var(--black);
        }
        #preview {
            width: 100%;
            height: auto;
            max-height: 180px;
            margin-top: 10px;
            border-radius: 10px;
            display: none;
            object-fit: contain;
            border: 1px solid var(--border);
            background: #fff;
        }
        label {
            display: block;
            margin: 8px 0 6px;
            font-size: 0.88rem;
            font-weight: 700;
        }
        input, textarea {
            width: 100%;
            padding: 10px 12px;
            margin: 0;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            box-sizing: border-box;
        }
        textarea { min-height: 92px; resize: vertical; }
        input:focus, textarea:focus, input:focus-visible, textarea:focus-visible {
            outline: none !important;
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }
        button {
            width: 100%;
            height: 44px;
            background-color: var(--black);
            color: #fff;
            border: 1px solid var(--black);
            padding: 0 12px;
            font-size: 0.92rem;
            font-weight: 800;
            border-radius: 12px;
            cursor: pointer;
            transition: color .15s ease;
            margin-top: 12px;
        }
        button:hover { color: var(--yellow); }
        .success, .error {
            margin: 0 0 10px;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 800;
            font-size: 0.88rem;
        }
        .success {
            background: #111111;
            color: #ffffff;
            border: 1px solid #111111;
        }
        .success a { color: #facc15; text-decoration: none; }
        .error {
            background: var(--light-yellow);
            color: var(--black);
            border: 1px solid var(--yellow);
        }
    </style>
</head>
<body>
<div class="up-wrap">
    <h2 class="up-title">Upload Image</h2>

    <form action="" method="POST" enctype="multipart/form-data">
        <div id="drop-area">
            <p>Drag & Drop or Click to Upload</p>
            <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" required>
            <img id="preview" src="" alt="">
        </div>

        <label>Alt Text</label>
        <input type="text" name="alt_text">

        <label>Title</label>
        <input type="text" name="title">

        <label>Description</label>
        <textarea name="description"></textarea>

        <label>Caption</label>
        <input type="text" name="caption">

        <button type="submit">Upload Image</button>
    </form>
</div>

<script>
    let dropArea = document.getElementById("drop-area");
    let fileInput = document.getElementById("imageInput");
    let preview = document.getElementById("preview");

    dropArea.addEventListener("click", () => fileInput.click());

    fileInput.addEventListener("change", function(event) {
        let file = event.target.files[0];
        previewImage(file);
    });

    dropArea.addEventListener("dragover", (event) => {
        event.preventDefault();
        dropArea.classList.add("highlight");
    });

    dropArea.addEventListener("dragleave", () => {
        dropArea.classList.remove("highlight");
    });

    dropArea.addEventListener("drop", (event) => {
        event.preventDefault();
        dropArea.classList.remove("highlight");

        let file = event.dataTransfer.files[0];
        fileInput.files = event.dataTransfer.files;
        previewImage(file);
    });

    function previewImage(file) {
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = "block";
            };
            reader.readAsDataURL(file);
        }
    }
</script>
</body>
</html>
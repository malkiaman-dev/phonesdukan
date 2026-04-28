<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/db.php';
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = __DIR__ . '/../public/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
    $originalName = preg_replace("/[^a-zA-Z0-9_-]/", "", $originalName);
    $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $fileName = $originalName . '.' . $fileExtension;

    $counter = 1;
    while (file_exists($uploadDir . $fileName)) {
        $fileName = $originalName . "_" . $counter . '.' . $fileExtension;
        $counter++;
    }

    $filePath = $uploadDir . $fileName;
    $fileUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/public/uploads/' . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (NULL, ?, 0)");
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

        echo "<p class='success'>Image uploaded successfully! <a href='$fileUrl' target='_blank'>View Image</a></p>";
    } else {
        echo "<p class='error'>Error uploading file.</p>";
    }
}
?>

    <title>Pro Image Upload</title>
    <style>

        h2 {
            color: #333;
            font-size: 22px;
        }
        #drop-area {
            width: 100%;
            height: 200px;
            border: 2px dashed #6c757d;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            background-color: #f1f3f5;
            transition: 0.3s;
            cursor: pointer;
            margin-bottom: 15px;
        }
        #drop-area:hover, #drop-area.highlight {
            border-color: #007bff;
            background-color: #e9f5ff;
        }
        #preview {
            width: 100%;
            height: auto;
            max-height: 180px;
            margin-top: 10px;
            border-radius: 5px;
            display: none;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            margin: 6px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            width: 100%;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .success {
            color: green;
            font-weight: bold;
            margin-top: 10px;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>


<div class="container">
    <h2>Upload an Image</h2>

    <form action="" method="POST" enctype="multipart/form-data">
        <div id="drop-area">
            <p>Drag & Drop or Click to Upload</p>
            <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;" required>
            <img id="preview" src="" alt="">
        </div>

        <label>Alt Text:</label>
        <input type="text" name="alt_text">

        <label>Title:</label>
        <input type="text" name="title">

        <label>Description:</label>
        <textarea name="description"></textarea>

        <label>Caption:</label>
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


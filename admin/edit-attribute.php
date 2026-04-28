<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}
// Include the sidebar only if the admin is logged in
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
// Include the database connection file
require_once __DIR__ . '/../database/db.php';

// Check if the attribute is being edited
$attribute = null; // Initialize the attribute variable
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];

    // Fetch the existing attribute details from the database
    $conn = (new Database())->getConnection();
    $query = "SELECT * FROM product_attributes WHERE attribute_id = :attribute_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attribute_id', $edit_id);
    $stmt->execute();

    // Get the attribute details
    $attribute = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle the edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_attribute'])) {
    $attribute_id = $_POST['attribute_id'];
    $attribute_name = $_POST['attribute_name'];
    $attribute_value = $_POST['attribute_value'];

    // Update the attribute in the database
    $conn = (new Database())->getConnection();
    $query = "UPDATE product_attributes SET attribute_name = :attribute_name, attribute_value = :attribute_value WHERE attribute_id = :attribute_id";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(':attribute_id', $attribute_id);
    $stmt->bindParam(':attribute_name', $attribute_name);
    $stmt->bindParam(':attribute_value', $attribute_value);

    if ($stmt->execute()) {
        echo "Attribute updated successfully!";
        // Redirect to prevent form resubmission
        header("Location: manage-attributes.php");
        exit;
    } else {
        echo "Error updating attribute.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attribute</title>
</head>
<body>
    <h1>Edit Product Attribute</h1>

    <!-- Edit Attribute Form -->
    <?php if ($attribute): ?>
    <form method="POST" action="">
        <input type="hidden" name="attribute_id" value="<?php echo $attribute['attribute_id']; ?>">

        <label for="attribute_name">Attribute Name:</label>
        <input type="text" name="attribute_name" value="<?php echo htmlspecialchars($attribute['attribute_name']); ?>" required><br>

        <label for="attribute_value">Attribute Value:</label>
        <input type="text" name="attribute_value" value="<?php echo htmlspecialchars($attribute['attribute_value']); ?>" required><br>

        <button type="submit" name="edit_attribute">Update Attribute</button>
    </form>
    <?php else: ?>
        <p>Attribute not found!</p>
    <?php endif; ?>

</body>
</html>

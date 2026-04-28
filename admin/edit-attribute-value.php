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

// Check if the attribute value is being edited
if (isset($_GET['edit_value_id'])) {
    $edit_value_id = $_GET['edit_value_id'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // Fetch the existing attribute value details from the database
    $query = "SELECT * FROM product_attribute_values WHERE value_id = :value_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value_id', $edit_value_id);
    $stmt->execute();

    // Get the attribute value details
    $attribute_value = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle the edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_value'])) {
    $value_id = $_POST['value_id'];
    $value = $_POST['value'];

    // Update the attribute value in the database
    $conn = (new Database())->getConnection();
    $query = "UPDATE product_attribute_values SET value = :value WHERE value_id = :value_id";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(':value_id', $value_id);
    $stmt->bindParam(':value', $value);

    if ($stmt->execute()) {
        echo "Value updated successfully!";
        // Redirect to prevent form resubmission
        header("Location: manage-attributes.php");
        exit;
    } else {
        echo "Error updating value.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attribute Value</title>
</head>
<body>
    <h1>Edit Attribute Value</h1>

    <!-- Edit Attribute Value Form -->
    <?php if ($attribute_value): ?>
    <form method="POST" action="">
        <input type="hidden" name="value_id" value="<?php echo $attribute_value['value_id']; ?>">

        <label for="value">Attribute Value:</label>
        <input type="text" name="value" value="<?php echo htmlspecialchars($attribute_value['value']); ?>" required><br>

        <button type="submit" name="edit_value">Update Value</button>
    </form>
    <?php else: ?>
        <p>Attribute Value not found!</p>
    <?php endif; ?>

</body>
</html>

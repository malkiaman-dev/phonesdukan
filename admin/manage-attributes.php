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

// Check if the attribute is being added
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_attribute'])) {
    $attribute_name = $_POST['attribute_name'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to insert a new attribute into the product_attributes table
    $query = "INSERT INTO product_attributes (attribute_name) VALUES (:attribute_name)";
    $stmt = $conn->prepare($query);

    // Bind the values to the query
    $stmt->bindParam(':attribute_name', $attribute_name);

    // Execute the query
    if ($stmt->execute()) {
        echo "Attribute added successfully!";
    } else {
        echo "Error adding attribute.";
    }
}

// Check if the value is being added
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_value'])) {
    $attribute_id = $_POST['attribute_id'];
    $value = $_POST['value'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to insert a new value for the attribute
    $query = "INSERT INTO product_attribute_values (attribute_id, value) VALUES (:attribute_id, :value)";
    $stmt = $conn->prepare($query);

    // Bind the values to the query
    $stmt->bindParam(':attribute_id', $attribute_id);
    $stmt->bindParam(':value', $value);

    // Execute the query
    if ($stmt->execute()) {
        echo "Value added successfully!";
    } else {
        echo "Error adding value.";
    }
}

// Check if the attribute is being deleted
if (isset($_GET['delete_attribute_id'])) {
    $delete_attribute_id = $_GET['delete_attribute_id'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to delete the attribute
    $query = "DELETE FROM product_attributes WHERE attribute_id = :attribute_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attribute_id', $delete_attribute_id);

    // Execute the query
    if ($stmt->execute()) {
        echo "Attribute deleted successfully!";
    } else {
        echo "Error deleting attribute.";
    }
}

// Check if the attribute value is being deleted
if (isset($_GET['delete_value_id'])) {
    $delete_value_id = $_GET['delete_value_id'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to delete the attribute value
    $query = "DELETE FROM product_attribute_values WHERE value_id = :value_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value_id', $delete_value_id);

    // Execute the query
    if ($stmt->execute()) {
        echo "Value deleted successfully!";
    } else {
        echo "Error deleting value.";
    }
}

// Fetch existing attributes from the database
$conn = (new Database())->getConnection();
$query = "SELECT * FROM product_attributes";
$stmt = $conn->prepare($query);
$stmt->execute();

// Fetch all attributes as an associative array
$attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing attribute values
$values_query = "SELECT * FROM product_attribute_values";
$values_stmt = $conn->prepare($values_query);
$values_stmt->execute();

// Fetch all attribute values as an associative array
$attribute_values = $values_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attributes</title>
</head>
<body>
    <h1>Manage Product Attributes</h1>

    <!-- Add New Attribute Form -->
    <h2>Add New Attribute</h2>
    <form method="POST" action="">
        <label for="attribute_name">Attribute Name:</label>
        <input type="text" name="attribute_name" required><br>

        <button type="submit" name="add_attribute">Add Attribute</button>
    </form>

    <h2>Add New Value to Attribute</h2>
    <!-- Add Value Form -->
    <form method="POST" action="">
        <label for="attribute_id">Select Attribute:</label>
        <select name="attribute_id" required>
            <?php foreach ($attributes as $attribute) : ?>
                <option value="<?php echo $attribute['attribute_id']; ?>"><?php echo $attribute['attribute_name']; ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="value">Attribute Value:</label>
        <input type="text" name="value" required><br>

        <button type="submit" name="add_value">Add Value</button>
    </form>

    <h2>Existing Attributes and Their Values</h2>
    <!-- Display Attributes in a Table -->
    <table border="1">
        <thead>
            <tr>
                <th>Attribute Name</th>
                <th>Attribute Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($attributes as $attribute) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($attribute['attribute_name']); ?></td>
                    <td>
                        <!-- Display attribute values -->
                        <?php
                            $attribute_id = $attribute['attribute_id'];
                            foreach ($attribute_values as $value) {
                                if ($value['attribute_id'] == $attribute_id) {
                                    echo htmlspecialchars($value['value']) . " ";
                                    // Edit Value Link
                                    echo "<a href='edit-attribute-value.php?edit_value_id=" . $value['value_id'] . "'>Edit</a> ";
                                    // Delete Value Link
                                    echo "<a href='?delete_value_id=" . $value['value_id'] . "' onclick=\"return confirm('Are you sure you want to delete this value?');\">Delete</a><br>";
                                }
                            }
                        ?>
                    </td>
                    <td>
                        <!-- Edit Link for Attribute -->
                        <a href="edit-attribute.php?edit_id=<?php echo $attribute['attribute_id']; ?>">Edit</a> |
                        <!-- Delete Link for Attribute -->
                        <a href="?delete_attribute_id=<?php echo $attribute['attribute_id']; ?>" onclick="return confirm('Are you sure you want to delete this attribute?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>

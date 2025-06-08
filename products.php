<?php
include 'auth.php';
include 'db.php';

// Check if user is an admin
checkAdmin();

// Get current user ID
$userID = $_SESSION['user_id'];

// Add Product
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stockLevel = $_POST['stockLevel'];

   // Insert into Products table
$stmt = $pdo->prepare("INSERT INTO Products (Name, Category, Price, StockLevel, UserID) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$name, $category, $price, $stockLevel, $userID]);

// Get the last inserted ProductID
$productID = $pdo->lastInsertId();

// Insert initial stock into Stock table
$stmt = $pdo->prepare("INSERT INTO Stock (ProductID, Quantity, SupplierID, StockDate) VALUES (?, ?, NULL, NOW())");
$stmt->execute([$productID, $stockLevel]);

    header("Location: products.php");
    exit();
}

// Delete Product and Related Records
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Delete from Sales table first
        $stmt = $pdo->prepare("DELETE FROM Sales WHERE ProductID = ?");
        $stmt->execute([$id]);

        // Delete from Stock table
        $stmt = $pdo->prepare("DELETE FROM Stock WHERE ProductID = ?");
        $stmt->execute([$id]);

        // Now delete from Products table
        $stmt = $pdo->prepare("DELETE FROM Products WHERE ProductID = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        header("Location: products.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to delete product: " . $e->getMessage();
    }
}

// Update Product
if (isset($_POST['update'])) {
    $id = $_POST['product_id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stockLevel = $_POST['stockLevel'];

    $stmt = $pdo->prepare("UPDATE Products SET Name = ?, Category = ?, Price = ?, StockLevel = ? WHERE ProductID = ?");
    $stmt->execute([$name, $category, $price, $stockLevel, $id]);
    header("Location: products.php");
    exit();
}

// Fetch All Products with User Information
if (isAdmin()) {
    // Admins can see all products with user information
   $stmt = $pdo->query("
    SELECT p.* 
    FROM Products p
    ORDER BY p.ProductID DESC
");

} else {
    // Staff can only see their own products
    $stmt = $pdo->prepare("
        SELECT p.*, u.Username as AddedBy 
        FROM Products p
        LEFT JOIN Users u ON p.UserID = u.UserID
        WHERE p.UserID = ?
        ORDER BY p.ProductID DESC
    ");
    $stmt->execute([$userID]);
}
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Products</h1>

    <!-- Add Product Form -->
    <div class="form-container">
        <h2>Add New Product</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" placeholder="Product Name" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" placeholder="Category" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" id="price" name="price" placeholder="Price" required>
            </div>
            <div class="form-group">
                <label for="stockLevel">Stock Level</label>
                <input type="number" id="stockLevel" name="stockLevel" placeholder="Stock Level" required>
            </div>
            <button type="submit" name="add" class="btn-primary">Add Product</button>
        </form>
    </div>

    <!-- Edit Product Form -->
    <?php if (isset($_GET['edit'])): 
        $id = $_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM Products WHERE ProductID = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(); ?>
        <div class="form-container">
            <h2>Edit Product</h2>
            <form method="POST">
                <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                <div class="form-group">
                    <label for="edit-name">Product Name</label>
                    <input type="text" id="edit-name" name="name" value="<?= $product['Name'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-category">Category</label>
                    <input type="text" id="edit-category" name="category" value="<?= $product['Category'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-price">Price</label>
                    <input type="number" step="0.01" id="edit-price" name="price" value="<?= $product['Price'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-stockLevel">Stock Level</label>
                    <input type="number" id="edit-stockLevel" name="stockLevel" value="<?= $product['StockLevel'] ?>" required>
                </div>
                <button type="submit" name="update" class="btn-primary">Update Product</button>
                <a href="products.php" class="btn-secondary">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- Product Table -->
    <div class="table-container">
        <h2>Product List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (₱)</th>
                    <th>Stock Level</th>
                    <?php if (isAdmin()): ?>
                    <th>Added By</th>
                    <?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $row): ?>
                    <tr>
                        <td><?= $row['ProductID'] ?></td>
                        <td><?= $row['Name'] ?></td>
                        <td><?= $row['Category'] ?></td>
                        <td><?= number_format($row['Price'], 2) ?></td>
                        <td><?= $row['StockLevel'] ?></td>
                        <?php if (isAdmin()): ?>
                        <td><?= $row['AddedBy'] ?? 'System' ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="products.php?edit=<?= $row['ProductID'] ?>" class="btn-edit">Edit</a>
                            <a href="products.php?delete=<?= $row['ProductID'] ?>" class="btn-delete" onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
</div>
</body>
</html>
<?php
include 'auth.php';
include 'db.php';

// Get current user ID
$userID = $_SESSION['user_id'];

// Add Stock
if (isset($_POST['add'])) {
    $productID = $_POST['product_id'];
    $supplierID = $_POST['supplier_id'];
    $quantity = $_POST['quantity'];
    $dateAdded = $_POST['date_added'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Fetch current stock level for the product
        $stmt = $pdo->prepare("SELECT StockLevel FROM Products WHERE ProductID = ?");
        $stmt->execute([$productID]);
        $currentStock = $stmt->fetchColumn();

        // Calculate new stock level after this addition
        $newStockLevel = $currentStock + $quantity;

        // Insert into Stock table (with StockLevelAfter)
        $stmt = $pdo->prepare("INSERT INTO Stock (ProductID, SupplierID, QuantityAdded, DateAdded, UserID, StockLevelAfter) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$productID, $supplierID, $quantity, $dateAdded, $userID, $newStockLevel]);
        
        // Insert the stock change into StockHistory table (optional, for auditing)
        $stmt = $pdo->prepare("INSERT INTO StockHistory (ProductID, OldStockLevel, NewStockLevel, QuantityAdded, DateAdded, UserID) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$productID, $currentStock, $newStockLevel, $quantity, $dateAdded, $userID]);

        // Update product's current stock level in the Products table
        $stmt = $pdo->prepare("UPDATE Products SET StockLevel = ? WHERE ProductID = ?");
        $stmt->execute([$newStockLevel, $productID]);
        
        // Commit transaction
        $pdo->commit();
        header("Location: stock.php");
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Fetch Products and Suppliers for Form Selection
// If admin, show all products and suppliers
if (isAdmin()) {
    $productsQuery = $pdo->query("SELECT * FROM Products ORDER BY Name");
    $suppliersQuery = $pdo->query("SELECT * FROM Suppliers ORDER BY Name");
} else {
    // Staff sees all products and suppliers (no filtering by UserID)
    $productsQuery = $pdo->query("SELECT * FROM Products ORDER BY Name");
    $suppliersQuery = $pdo->query("SELECT * FROM Suppliers ORDER BY Name");


}

// Fetch Stock Entries with product names and user information
if (isAdmin()) {
    // Admins see all stock entries
    $stockQuery = $pdo->query("
        SELECT s.StockID, p.Name AS ProductName, sup.Name AS SupplierName, 
               s.QuantityAdded, s.DateAdded, s.StockLevelAfter, u.Username as AddedBy
        FROM Stock s
        JOIN Products p ON s.ProductID = p.ProductID
        JOIN Suppliers sup ON s.SupplierID = sup.SupplierID
        LEFT JOIN Users u ON s.UserID = u.UserID
        ORDER BY s.DateAdded DESC
    ");
} else {
    // Staff only see stock entries they added
    $stockQuery = $pdo->prepare("
        SELECT s.StockID, p.Name AS ProductName, sup.Name AS SupplierName, 
               s.QuantityAdded, s.DateAdded, s.StockLevelAfter, u.Username as AddedBy
        FROM Stock s
        JOIN Products p ON s.ProductID = p.ProductID
        JOIN Suppliers sup ON s.SupplierID = sup.SupplierID
        LEFT JOIN Users u ON s.UserID = u.UserID
        WHERE s.UserID = ?
        ORDER BY s.DateAdded DESC
    ");
    $stockQuery->execute([$userID]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Stock</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Stock Management</h1>

    <!-- Add Stock Form -->
    <div class="form-container">
        <h2>Add Stock</h2>
        <form method="POST">
            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Select Product</option>
                    <?php while ($row = $productsQuery->fetch()): ?>
                        <option value="<?= $row['ProductID'] ?>"><?= $row['Name'] ?> (Current Stock: <?= $row['StockLevel'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" required>
                    <option value="">Select Supplier</option>
                    <?php while ($row = $suppliersQuery->fetch()): ?>
                        <option value="<?= $row['SupplierID'] ?>"><?= $row['Name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" placeholder="Quantity" required>
            </div>
            
            <div class="form-group">
                <label for="date_added">Date</label>
                <input type="date" id="date_added" name="date_added" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <button type="submit" name="add" class="btn-primary">Add Stock</button>
        </form>
    </div>

    <!-- Stock Table -->
    <div class="table-container">
        <h2>Stock History</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Supplier</th>
                    <th>Quantity Added</th>
                    <th>Date Added</th>
                    <th>Stock After Addition</th>
                    <?php if (isAdmin()): ?>
                    <th>Added By</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stockQuery->fetch()): ?>
                    <tr>
                        <td><?= $row['StockID'] ?></td>
                        <td><?= $row['ProductName'] ?></td>
                        <td><?= $row['SupplierName'] ?></td>
                        <td><?= $row['QuantityAdded'] ?></td>
                        <td><?= date('M d, Y', strtotime($row['DateAdded'])) ?></td>
                        <td><?= $row['StockLevelAfter'] ?></td>
                        <?php if (isAdmin()): ?>
                        <td><?= $row['AddedBy'] ?? 'System' ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
</div>
</body>
</html>
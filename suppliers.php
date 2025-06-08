<?php
include 'auth.php';
include 'db.php';

// Check if user is an admin
checkAdmin();

// Add Supplier
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];

    $stmt = $pdo->prepare("INSERT INTO Suppliers (Name, ContactInfo) VALUES (?, ?)");
    $stmt->execute([$name, $contact]);
    header("Location: suppliers.php");
    exit();
}

// Delete Supplier and Related Stock Entries
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Delete stock entries related to the supplier
        $stmt = $pdo->prepare("DELETE FROM Stock WHERE SupplierID = ?");
        $stmt->execute([$id]);

        // Now delete the supplier
        $stmt = $pdo->prepare("DELETE FROM Suppliers WHERE SupplierID = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        header("Location: suppliers.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to delete supplier: " . $e->getMessage();
    }
}


// Fetch Suppliers
$stmt = $pdo->query("SELECT * FROM Suppliers");
$suppliers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Suppliers</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Suppliers</h1>

    <!-- Add Supplier Form -->
    <div class="form-container">
        <h2>Add New Supplier</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Supplier Name</label>
                <input type="text" id="name" name="name" placeholder="Supplier Name" required>
            </div>
            <div class="form-group">
                <label for="contact">Contact Information</label>
                <input type="text" id="contact" name="contact" placeholder="Contact Info" required>
            </div>
            <button type="submit" name="add" class="btn-primary">Add Supplier</button>
        </form>
    </div>

    <!-- Supplier Table -->
    <div class="table-container">
        <h2>Supplier List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact Info</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $row): ?>
                    <tr>
                        <td><?= $row['SupplierID'] ?></td>
                        <td><?= $row['Name'] ?></td>
                        <td><?= $row['ContactInfo'] ?></td>
                        <td>
                            <a href="suppliers.php?delete=<?= $row['SupplierID'] ?>" class="btn-delete" onclick="return confirm('Delete this supplier?')">Delete</a>
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
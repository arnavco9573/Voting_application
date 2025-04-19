<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle candidate addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_candidate'])) {
    $name = $_POST['name'];
    $position = $_POST['position'];
    
    // Handle file upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'assets/';
        $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photo = $fileName;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO candidates (name, position, photo) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $position, $photo])) {
        $_SESSION['message'] = "Candidate added successfully!";
        header("Location: admin.php");
        exit;
    } else {
        $message = "Failed to add candidate.";
    }
}

// Handle candidate deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['message'] = "Candidate deleted successfully!";
        header("Location: admin.php");
        exit;
    } else {
        $message = "Failed to delete candidate.";
    }
}

// Get all candidates
$candidates = $pdo->query("SELECT * FROM candidates ORDER BY position, name")->fetchAll();

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting System - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Panel</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        
        <?php if ($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <main>
            <section class="admin-section">
                <h2>Add New Candidate</h2>
                <form action="admin.php" method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="name">Candidate Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" required>
                    </div>
                    <div class="form-group">
                        <label for="photo">Photo (Optional)</label>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>
                    <button type="submit" name="add_candidate" class="btn">Add Candidate</button>
                </form>
            </section>
            
            <section class="admin-section">
                <h2>Candidates List</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Votes</th>
                            <th>Photo</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td><?php echo $candidate['id']; ?></td>
                                <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                <td><?php echo $candidate['votes']; ?></td>
                                <td>
                                    <?php if ($candidate['photo']): ?>
                                        <img src="assets/<?php echo htmlspecialchars($candidate['photo']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="table-photo">
                                    <?php else: ?>
                                        No photo
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="admin.php?delete=<?php echo $candidate['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this candidate?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            
            <section class="admin-section">
                <h2>Voters List</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Voted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo $user['has_voted'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
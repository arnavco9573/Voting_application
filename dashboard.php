<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Check if user has already voted
$stmt = $pdo->prepare("SELECT has_voted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$has_voted = $user['has_voted'];

// Get candidates for voting
$candidates = $pdo->query("SELECT * FROM candidates")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting System - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            <nav>
                <?php if ($role == 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <?php if ($has_voted): ?>
                <div class="alert success">
                    You have already voted. Thank you for participating!
                </div>
                <h2>Current Results</h2>
                <div class="results">
                    <?php
                    $positions = $pdo->query("SELECT DISTINCT position FROM candidates")->fetchAll();
                    foreach ($positions as $position):
                        $candidates = $pdo->prepare("SELECT * FROM candidates WHERE position = ? ORDER BY votes DESC");
                        $candidates->execute([$position['position']]);
                        $candidates = $candidates->fetchAll();
                    ?>
                        <div class="position-results">
                            <h3><?php echo htmlspecialchars($position['position']); ?></h3>
                            <?php foreach ($candidates as $candidate): ?>
                                <div class="candidate-result">
                                    <div class="candidate-photo">
                                        <?php if ($candidate['photo']): ?>
                                            <img src="assets/<?php echo htmlspecialchars($candidate['photo']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>">
                                        <?php else: ?>
                                            <div class="photo-placeholder"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="candidate-info">
                                        <h4><?php echo htmlspecialchars($candidate['name']); ?></h4>
                                        <div class="vote-bar" style="width: <?php echo ($candidate['votes'] / max(1, array_sum(array_column($candidates, 'votes'))) * 100); ?>%">
                                            <?php echo $candidate['votes']; ?> votes
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <h2>Cast Your Vote</h2>
                <form action="vote.php" method="POST" class="voting-form">
                    <?php
                    $positions = $pdo->query("SELECT DISTINCT position FROM candidates")->fetchAll();
                    foreach ($positions as $position):
                        $candidates = $pdo->prepare("SELECT * FROM candidates WHERE position = ?");
                        $candidates->execute([$position['position']]);
                        $candidates = $candidates->fetchAll();
                    ?>
                        <div class="position-group">
                            <h3><?php echo htmlspecialchars($position['position']); ?></h3>
                            <div class="candidates-list">
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="candidate-option">
                                        <input type="radio" 
                                               id="candidate_<?php echo $candidate['id']; ?>" 
                                               name="vote[<?php echo htmlspecialchars($position['position']); ?>]" 
                                               value="<?php echo $candidate['id']; ?>" required>
                                        <label for="candidate_<?php echo $candidate['id']; ?>">
                                            <?php if ($candidate['photo']): ?>
                                                <img src="assets/<?php echo htmlspecialchars($candidate['photo']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>">
                                            <?php else: ?>
                                                <div class="photo-placeholder"></div>
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($candidate['name']); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn">Submit Vote</button>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
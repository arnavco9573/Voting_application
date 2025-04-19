<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if user has already voted
$stmt = $pdo->prepare("SELECT has_voted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['has_voted']) {
    $_SESSION['error'] = "You have already voted!";
    header("Location: dashboard.php");
    exit;
}

// Process votes
try {
    $pdo->beginTransaction();
    
    foreach ($_POST['vote'] as $position => $candidate_id) {
        // Record the vote
        $stmt = $pdo->prepare("INSERT INTO votes (user_id, candidate_id, position) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $candidate_id, $position]);
        
        // Update candidate's vote count
        $stmt = $pdo->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
        $stmt->execute([$candidate_id]);
    }
    
    // Mark user as voted
    $stmt = $pdo->prepare("UPDATE users SET has_voted = TRUE WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $pdo->commit();
    $_SESSION['success'] = "Your vote has been recorded successfully!";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "An error occurred while processing your vote: " . $e->getMessage();
}

header("Location: dashboard.php");
exit;
?>
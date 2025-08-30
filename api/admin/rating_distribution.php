<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
require_once '../../config/session.php';
require_once '../../config/database.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT rating, COUNT(*) as count 
        FROM survey_answers 
        WHERE rating BETWEEN 1 AND 5
        GROUP BY rating 
        ORDER BY rating ASC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    // Initialize array with 0 for all ratings
    $distribution = [0, 0, 0, 0, 0]; // Index 0-4 for ratings 1-5
    
    foreach ($results as $row) {
        $rating = intval($row['rating']);
        if ($rating >= 1 && $rating <= 5) {
            $distribution[$rating - 1] = intval($row['count']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $distribution,
        'total' => array_sum($distribution)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data distribusi rating: ' . $e->getMessage()
    ]);
}
?>

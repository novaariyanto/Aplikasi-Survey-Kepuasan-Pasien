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
    // Get satisfaction distribution with proper validation
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN rating BETWEEN 1 AND 3 THEN 1 END) as not_satisfied,
            COUNT(CASE WHEN rating BETWEEN 4 AND 5 THEN 1 END) as satisfied,
            COUNT(*) as total
        FROM survey_answers
        WHERE rating BETWEEN 1 AND 5
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    $notSatisfied = intval($result['not_satisfied']);
    $satisfied = intval($result['satisfied']);
    $total = intval($result['total']);
    
    echo json_encode([
        'success' => true,
        'not_satisfied' => $notSatisfied,
        'satisfied' => $satisfied,
        'total' => $total,
        'has_data' => $total > 0
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data distribusi kepuasan: ' . $e->getMessage()
    ]);
}
?>

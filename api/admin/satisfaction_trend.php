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
        SELECT 
            DATE(sr.created_at) as date,
            COUNT(CASE WHEN sa.rating >= 4 THEN 1 END) as satisfied,
            COUNT(sa.rating) as total
        FROM survey_responses sr
        LEFT JOIN survey_answers sa ON sr.id = sa.response_id
        WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND sa.rating BETWEEN 1 AND 5
        GROUP BY DATE(sr.created_at)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    $labels = [];
    $data = [];
    
    // Generate last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d/m', strtotime($date));
        
        // Find data for this date
        $found = false;
        foreach ($results as $row) {
            if ($row['date'] === $date && $row['total'] > 0) {
                $percentage = round(($row['satisfied'] / $row['total']) * 100, 1);
                $data[] = $percentage;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $data[] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'data' => $data,
        'debug' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data tren kepuasan: ' . $e->getMessage()
    ]);
}
?>

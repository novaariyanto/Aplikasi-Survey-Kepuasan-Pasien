<?php
header('Content-Type: application/json');
require_once '../../config/session.php';
require_once '../../config/database.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$responseId = $_GET['id'] ?? null;

if (!$responseId || !is_numeric($responseId)) {
    echo json_encode(['success' => false, 'message' => 'ID respons tidak valid']);
    exit();
}

try {
    // Get response data
    $stmt = $pdo->prepare("SELECT nomr, saran, created_at FROM survey_responses WHERE id = ?");
    $stmt->execute([$responseId]);
    $response = $stmt->fetch();
    
    if (!$response) {
        echo json_encode(['success' => false, 'message' => 'Data respons tidak ditemukan']);
        exit();
    }
    
    // Get answers with questions
    $stmt = $pdo->prepare("
        SELECT 
            sa.rating,
            q.question_text
        FROM survey_answers sa
        JOIN questions q ON sa.question_id = q.id
        WHERE sa.response_id = ?
        ORDER BY q.id ASC
    ");
    $stmt->execute([$responseId]);
    $answers = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'answers' => $answers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil detail survei'
    ]);
}
?>

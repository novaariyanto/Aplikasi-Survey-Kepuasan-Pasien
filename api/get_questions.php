<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    // Get active questions from database
    $stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE is_active = 1 ORDER BY id ASC");
    $stmt->execute();
    
    $questions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $questions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data pertanyaan: ' . $e->getMessage()
    ]);
}
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Data tidak valid');
    }
    
    $nomr = sanitizeInput($input['nomr'] ?? '');
    $saran = sanitizeInput($input['saran'] ?? '');
    $answers = $input['answers'] ?? [];
    
    // Validation
    if (empty($nomr)) {
        throw new Exception('Nomor Rekam Medis wajib diisi');
    }
    
    if (empty($answers) || !is_array($answers)) {
        throw new Exception('Jawaban survei tidak valid');
    }
    
    // Validate NOMR format (basic validation)
    if (strlen($nomr) < 3 || strlen($nomr) > 20) {
        throw new Exception('Format Nomor Rekam Medis tidak valid');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert survey response
    $stmt = $pdo->prepare("INSERT INTO survey_responses (nomr, saran) VALUES (?, ?)");
    $stmt->execute([$nomr, $saran]);
    
    $responseId = $pdo->lastInsertId();
    
    // Insert survey answers
    $stmt = $pdo->prepare("INSERT INTO survey_answers (response_id, question_id, rating) VALUES (?, ?, ?)");
    
    foreach ($answers as $questionId => $rating) {
        // Validate rating
        $rating = intval($rating);
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Rating tidak valid untuk pertanyaan ID: ' . $questionId);
        }
        
        // Validate question exists
        $checkStmt = $pdo->prepare("SELECT id FROM questions WHERE id = ? AND is_active = 1");
        $checkStmt->execute([$questionId]);
        if (!$checkStmt->fetch()) {
            throw new Exception('Pertanyaan tidak valid: ID ' . $questionId);
        }
        
        $stmt->execute([$responseId, $questionId, $rating]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Survei berhasil dikirim',
        'response_id' => $responseId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

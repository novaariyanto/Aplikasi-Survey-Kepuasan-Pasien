<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

// Check admin authentication
requireAdminLogin();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Laporan_Survei_Kepuasan_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

try {
    // Get all survey data with details
    $stmt = $pdo->prepare("
        SELECT 
            sr.nomr,
            sr.saran,
            sr.created_at,
            q.question_text,
            sa.rating
        FROM survey_responses sr
        JOIN survey_answers sa ON sr.id = sa.response_id
        JOIN questions q ON sa.question_id = q.id
        ORDER BY sr.created_at DESC, q.id ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll();
    
    // Get questions for header
    $stmt = $pdo->prepare("SELECT question_text FROM questions WHERE is_active = 1 ORDER BY id ASC");
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Group data by response
$responses = [];
foreach ($data as $row) {
    $nomr = $row['nomr'];
    if (!isset($responses[$nomr])) {
        $responses[$nomr] = [
            'nomr' => $row['nomr'],
            'saran' => $row['saran'],
            'created_at' => $row['created_at'],
            'answers' => []
        ];
    }
    $responses[$nomr]['answers'][] = [
        'question' => $row['question_text'],
        'rating' => $row['rating']
    ];
}

// Output Excel content
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<meta name="ProgId" content="Excel.Sheet">';
echo '<meta name="Generator" content="Microsoft Excel 11">';
echo '<style>';
echo '.header { background-color: #4a90e2; color: white; font-weight: bold; text-align: center; }';
echo '.center { text-align: center; }';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th, td { border: 1px solid #000; padding: 5px; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<h2 style="text-align: center;">Laporan Survei Kepuasan Pasien RSUD Soewondo</h2>';
echo '<p style="text-align: center;">Tanggal Export: ' . date('d F Y H:i:s') . '</p>';

echo '<table>';
echo '<tr class="header">';
echo '<th>No</th>';
echo '<th>NOMR</th>';
echo '<th>Tanggal</th>';
foreach ($questions as $index => $question) {
    echo '<th>Q' . ($index + 1) . '</th>';
}
echo '<th>Saran</th>';
echo '</tr>';

$no = 1;
foreach ($responses as $response) {
    echo '<tr>';
    echo '<td class="center">' . $no++ . '</td>';
    echo '<td>' . htmlspecialchars($response['nomr']) . '</td>';
    echo '<td class="center">' . date('d/m/Y H:i', strtotime($response['created_at'])) . '</td>';
    
    // Create array to match questions with answers
    $answerMap = [];
    foreach ($response['answers'] as $answer) {
        $answerMap[$answer['question']] = $answer['rating'];
    }
    
    // Output ratings in correct order
    foreach ($questions as $question) {
        $rating = $answerMap[$question] ?? '-';
        echo '<td class="center">' . $rating . '</td>';
    }
    
    echo '<td>' . htmlspecialchars($response['saran'] ?: '-') . '</td>';
    echo '</tr>';
}

echo '</table>';

// Summary statistics
echo '<br><br>';
echo '<h3>Ringkasan Statistik</h3>';
echo '<table>';
echo '<tr class="header"><th>Metrik</th><th>Nilai</th></tr>';

// Calculate statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM survey_responses");
$stmt->execute();
$totalResponses = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM survey_answers");
$stmt->execute();
$avgRating = round($stmt->fetch()['avg_rating'], 2);

$stmt = $pdo->prepare("SELECT COUNT(*) as satisfied FROM survey_answers WHERE rating >= 4");
$stmt->execute();
$satisfied = $stmt->fetch()['satisfied'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_answers FROM survey_answers");
$stmt->execute();
$totalAnswers = $stmt->fetch()['total_answers'];

$satisfactionPercentage = $totalAnswers > 0 ? round(($satisfied / $totalAnswers) * 100, 1) : 0;

echo '<tr><td>Total Responden</td><td class="center">' . $totalResponses . '</td></tr>';
echo '<tr><td>Rating Rata-rata</td><td class="center">' . $avgRating . '/5</td></tr>';
echo '<tr><td>Tingkat Kepuasan</td><td class="center">' . $satisfactionPercentage . '%</td></tr>';

echo '</table>';

echo '</body>';
echo '</html>';
?>

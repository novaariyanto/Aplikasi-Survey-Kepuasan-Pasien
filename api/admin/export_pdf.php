<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

// Check admin authentication
requireAdminLogin();

// Set headers for HTML download that can be printed to PDF
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="Laporan_Survei_Kepuasan_' . date('Y-m-d') . '.html"');
header('Cache-Control: max-age=0');

try {
    // Get statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM survey_responses");
    $stmt->execute();
    $totalResponses = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM survey_answers");
    $stmt->execute();
    $avgRating = round($stmt->fetch()['avg_rating'], 2);
    
    $stmt = $pdo->prepare("
        SELECT 
            q.question_text,
            AVG(sa.rating) as avg_rating,
            COUNT(sa.rating) as total_responses
        FROM questions q
        LEFT JOIN survey_answers sa ON q.id = sa.question_id
        WHERE q.is_active = 1
        GROUP BY q.id, q.question_text
        ORDER BY q.id ASC
    ");
    $stmt->execute();
    $questionStats = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as satisfied FROM survey_answers WHERE rating >= 4");
    $stmt->execute();
    $satisfied = $stmt->fetch()['satisfied'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_answers FROM survey_answers");
    $stmt->execute();
    $totalAnswers = $stmt->fetch()['total_answers'];
    
    $satisfactionPercentage = $totalAnswers > 0 ? round(($satisfied / $totalAnswers) * 100, 1) : 0;
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Generate HTML content for PDF
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Survei Kepuasan - RSUD Soewondo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #4a90e2; }
        .title { font-size: 20px; margin: 10px 0; }
        .date { color: #666; }
        .stats { display: flex; justify-content: space-around; margin: 30px 0; }
        .stat-box { text-align: center; padding: 20px; border: 1px solid #ddd; }
        .stat-number { font-size: 32px; font-weight: bold; color: #4a90e2; }
        .stat-label { color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .center { text-align: center; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; }
        .print-btn { background: #4a90e2; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 20px; }
        @media print {
            .print-btn { display: none; }
            body { margin: 0; }
        }
    </style>
    <script>
        function printPage() {
            window.print();
        }
    </script>
</head>
<body>
<button class="print-btn" onclick="printPage()">📄 Print/Save as PDF</button>';

$html .= '<div class="header">
    <div class="logo">RSUD Soewondo</div>
    <div class="title">Laporan Survei Kepuasan Pasien Rawat Inap</div>
    <div class="date">Tanggal Export: ' . date('d F Y H:i:s') . '</div>
</div>';

$html .= '<div class="stats">
    <div class="stat-box">
        <div class="stat-number">' . $totalResponses . '</div>
        <div class="stat-label">Total Responden</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">' . $avgRating . '/5</div>
        <div class="stat-label">Rating Rata-rata</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">' . $satisfactionPercentage . '%</div>
        <div class="stat-label">Tingkat Kepuasan</div>
    </div>
</div>';

$html .= '<h3>Statistik Detail per Pertanyaan</h3>
<table>
    <tr>
        <th width="5%">No</th>
        <th width="60%">Pertanyaan</th>
        <th width="15%">Total Respons</th>
        <th width="20%">Rating Rata-rata</th>
    </tr>';

foreach ($questionStats as $index => $stat) {
    $avgRating = round($stat['avg_rating'], 2);
    $html .= '<tr>
        <td class="center">' . ($index + 1) . '</td>
        <td>' . htmlspecialchars($stat['question_text']) . '</td>
        <td class="center">' . $stat['total_responses'] . '</td>
        <td class="center">' . $avgRating . '</td>
    </tr>';
}

$html .= '</table>';

// Add interpretation
$html .= '<h3>Interpretasi Hasil</h3>
<p><strong>Tingkat Kepuasan:</strong> ' . $satisfactionPercentage . '% responden memberikan rating 4-5 (Puas hingga Sangat Puas)</p>
<p><strong>Rating Rata-rata:</strong> ' . $avgRating . ' dari skala 1-5</p>';

if ($avgRating >= 4.0) {
    $html .= '<p><strong>Kesimpulan:</strong> Secara keseluruhan, pasien merasa puas dengan pelayanan RSUD Soewondo.</p>';
} elseif ($avgRating >= 3.0) {
    $html .= '<p><strong>Kesimpulan:</strong> Pelayanan dinilai cukup baik, namun masih ada ruang untuk perbaikan.</p>';
} else {
    $html .= '<p><strong>Kesimpulan:</strong> Diperlukan perbaikan signifikan dalam kualitas pelayanan.</p>';
}

$html .= '<div class="footer">
    <p>© 2024 RSUD Soewondo - Sistem Survei Kepuasan Pasien</p>
    <p>Laporan ini digenerate secara otomatis oleh sistem pada ' . date('d F Y H:i:s') . '</p>
</div>';

$html .= '</body></html>';

// For a simple implementation, we'll use DomPDF-like approach
// In production, you should use proper PDF libraries
echo $html;
?>

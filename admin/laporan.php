<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireAdminLogin();

// Get report statistics
try {
    // Average rating per question
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
    
    // Overall statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM survey_responses");
    $stmt->execute();
    $totalResponses = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM survey_answers");
    $stmt->execute();
    $overallAvg = round($stmt->fetch()['avg_rating'], 2);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as satisfied FROM survey_answers WHERE rating >= 4");
    $stmt->execute();
    $satisfied = $stmt->fetch()['satisfied'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_answers FROM survey_answers");
    $stmt->execute();
    $totalAnswers = $stmt->fetch()['total_answers'];
    
    $satisfactionPercentage = $totalAnswers > 0 ? round(($satisfied / $totalAnswers) * 100, 1) : 0;
    
} catch (Exception $e) {
    $error = 'Gagal mengambil data laporan: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - RSUD RAA Soewondo Pati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .sidebar {
            min-height: 100vh;
        }
        /* Custom Chart Styles */
        .chart-bar {
            transition: all 0.3s ease;
            transform-origin: bottom;
        }
        .chart-bar:hover {
            transform: scaleY(1.05);
            filter: brightness(1.1);
        }
        .chart-segment {
            transition: all 0.3s ease;
        }
        .chart-segment:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
        }
        @keyframes growBar {
            from {
                transform: scaleY(0);
            }
            to {
                transform: scaleY(1);
            }
        }
        .bar-animate {
            animation: growBar 0.8s ease-out;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .sidebar {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar bg-gray-800 text-white w-64 p-6 no-print">
            <div class="mb-8">
                <h1 class="text-xl font-bold">
                    <i class="fas fa-hospital mr-2"></i>
                    RSUD RAA Soewondo Pati
                </h1>
                <p class="text-gray-300 text-sm">Admin Panel</p>
            </div>
            
            <nav class="space-y-4">
                <a href="dashboard.php" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="data_survei.php" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-database mr-3"></i>
                    Data Survei
                </a>
                <a href="laporan.php" class="flex items-center px-4 py-2 bg-blue-600 rounded-lg">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Laporan
                </a>
                <a href="manajemen_user.php" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-users mr-3"></i>
                    Manajemen User
                </a>
                <a href="manajemen_pertanyaan.php" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-question-circle mr-3"></i>
                    Manajemen Pertanyaan
                </a>
                <hr class="border-gray-600">
                <a href="logout.php" class="flex items-center px-4 py-2 hover:bg-red-600 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-6 main-content">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Laporan Statistik</h2>
                        <p class="text-gray-600">Analisis kepuasan pasien rawat inap RSUD Soewondo</p>
                    </div>
                    <div class="no-print flex gap-4">
                        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-print mr-2"></i>
                            Print
                        </button>
                        <a href="../api/admin/export_excel.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                            <i class="fas fa-file-excel mr-2"></i>
                            Export Excel
                        </a>
                        <a href="../api/admin/export_pdf.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                            <i class="fas fa-file-pdf mr-2"></i>
                            Export PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Report Header Info -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Laporan Survei Kepuasan Pasien</h3>
                    <p class="text-gray-600">RSUD Soewondo - Periode: <?php echo date('d F Y'); ?></p>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo $totalResponses; ?></div>
                    <div class="text-gray-600">Total Responden</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-yellow-600 mb-2"><?php echo $overallAvg; ?>/5</div>
                    <div class="text-gray-600">Rating Rata-rata</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2"><?php echo $satisfactionPercentage; ?>%</div>
                    <div class="text-gray-600">Tingkat Kepuasan</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Average Rating per Question Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Rata-rata Rating per Pertanyaan</h3>
                    <div id="questionRatingChart" class="h-80">
                        <div class="flex items-center justify-center h-full text-gray-500">Memuat data...</div>
                    </div>
                </div>

                <!-- Satisfaction Distribution -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribusi Kepuasan</h3>
                    <div id="satisfactionChart" class="h-80">
                        <div class="flex items-center justify-center h-full text-gray-500">Memuat data...</div>
                    </div>
                </div>
            </div>

            <!-- Detailed Statistics Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Statistik Detail per Pertanyaan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Respons</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Rating Rata-rata</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($questionStats as $index => $stat): ?>
                                <?php 
                                $avgRating = round($stat['avg_rating'], 2);
                                $category = '';
                                $categoryClass = '';
                                
                                if ($avgRating >= 4.5) {
                                    $category = 'Sangat Baik';
                                    $categoryClass = 'bg-green-100 text-green-800';
                                } elseif ($avgRating >= 4.0) {
                                    $category = 'Baik';
                                    $categoryClass = 'bg-blue-100 text-blue-800';
                                } elseif ($avgRating >= 3.0) {
                                    $category = 'Cukup';
                                    $categoryClass = 'bg-yellow-100 text-yellow-800';
                                } elseif ($avgRating >= 2.0) {
                                    $category = 'Kurang';
                                    $categoryClass = 'bg-orange-100 text-orange-800';
                                } else {
                                    $category = 'Sangat Kurang';
                                    $categoryClass = 'bg-red-100 text-red-800';
                                }
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $index + 1; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($stat['question_text']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        <?php echo $stat['total_responses']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        <div class="flex items-center justify-center">
                                            <span class="mr-2 font-medium"><?php echo $avgRating; ?></span>
                                            <div class="flex text-yellow-400">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star text-xs <?php echo $i <= round($avgRating) ? '' : 'text-gray-300'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $categoryClass; ?>">
                                            <?php echo $category; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report Footer -->
            <div class="mt-8 text-center text-gray-500 text-sm">
                <p>Laporan digenerate pada: <?php echo date('d F Y H:i:s'); ?></p>
                <p>© 2024 RSUD Soewondo - Sistem Survei Kepuasan Pasien</p>
            </div>
        </div>
    </div>

    <script>
        // Question stats data from PHP
        const questionStats = [
            <?php foreach ($questionStats as $index => $stat): ?>
            {
                label: 'Q<?php echo $index + 1; ?>',
                value: <?php echo round($stat['avg_rating'], 2); ?>,
                text: '<?php echo addslashes($stat['question_text']); ?>',
                responses: <?php echo $stat['total_responses']; ?>
            },
            <?php endforeach; ?>
        ];

        // Function to create bar chart with Tailwind CSS
        function createQuestionChart() {
            const container = document.getElementById('questionRatingChart');
            
            if (questionStats.length === 0) {
                container.innerHTML = `
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <i class="fas fa-chart-bar text-4xl mb-4 opacity-50"></i>
                            <p>Belum ada data pertanyaan</p>
                        </div>
                    </div>
                `;
                return;
            }

            const maxValue = 5;
            
            let html = '<div class="h-full flex flex-col">';
            
            // Chart area
            html += '<div class="flex-1 flex items-end justify-between px-4 pb-4 space-x-2">';
            
            questionStats.forEach((stat, index) => {
                const height = (stat.value / maxValue) * 100;
                const color = stat.value >= 4 ? '#22c55e' : stat.value >= 3 ? '#eab308' : '#ef4444';
                
                html += `
                    <div class="flex-1 flex flex-col items-center group">
                        <div class="relative w-full max-w-16">
                            <div class="bg-gray-200 rounded-t-lg relative overflow-hidden" style="height: 200px;">
                                <div class="chart-bar bar-animate absolute bottom-0 w-full rounded-t-lg transition-all duration-300 hover:opacity-80 cursor-pointer" 
                                     style="height: ${height}%; background-color: ${color};"
                                     title="${stat.text} - Rating: ${stat.value}/5 (${stat.responses} respons)">
                                </div>
                            </div>
                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                ${stat.value}/5
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-center font-medium text-gray-600">${stat.label}</div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Y-axis labels
            html += '<div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-gray-500 pr-2">';
            for (let i = 5; i >= 0; i--) {
                html += `<div>${i}</div>`;
            }
            html += '</div>';
            
            // Legend and summary
            html += '<div class="mt-4 grid grid-cols-3 gap-4 text-center">';
            
            const avgRating = questionStats.reduce((sum, stat) => sum + stat.value, 0) / questionStats.length;
            const totalResponses = questionStats.reduce((sum, stat) => sum + stat.responses, 0);
            const excellentCount = questionStats.filter(stat => stat.value >= 4).length;
            
            html += `
                <div class="bg-blue-50 p-3 rounded-lg">
                    <div class="text-sm text-blue-600">Rata-rata</div>
                    <div class="text-lg font-semibold text-blue-800">${avgRating.toFixed(1)}/5</div>
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <div class="text-sm text-green-600">Memuaskan</div>
                    <div class="text-lg font-semibold text-green-800">${excellentCount}/${questionStats.length}</div>
                </div>
                <div class="bg-purple-50 p-3 rounded-lg">
                    <div class="text-sm text-purple-600">Total Respons</div>
                    <div class="text-lg font-semibold text-purple-800">${totalResponses}</div>
                </div>
            `;
            
            html += '</div></div>';
            
            container.innerHTML = html;
        }

        // Function to create satisfaction pie chart with Tailwind CSS
        function createSatisfactionChart() {
            const container = document.getElementById('satisfactionChart');
            
            fetch('../api/admin/satisfaction_distribution.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.has_data) {
                        const total = data.not_satisfied + data.satisfied;
                        const satisfiedPercentage = (data.satisfied / total) * 100;
                        const notSatisfiedPercentage = (data.not_satisfied / total) * 100;
                        
                        let html = '<div class="h-full flex flex-col items-center justify-center space-y-6">';
                        
                        // Create donut chart using CSS
                        html += '<div class="relative w-48 h-48">';
                        
                        // Background circle
                        html += '<div class="absolute inset-0 rounded-full bg-gray-200"></div>';
                        
                        // Satisfied segment
                        if (data.satisfied > 0) {
                            html += `
                                <div class="absolute inset-0 rounded-full chart-segment" 
                                     style="background: conic-gradient(from 0deg, #22c55e 0deg, #22c55e ${satisfiedPercentage * 3.6}deg, transparent ${satisfiedPercentage * 3.6}deg);"
                                     title="Puas: ${data.satisfied} (${satisfiedPercentage.toFixed(1)}%)">
                                </div>
                            `;
                        }
                        
                        // Not satisfied segment
                        if (data.not_satisfied > 0) {
                            html += `
                                <div class="absolute inset-0 rounded-full chart-segment" 
                                     style="background: conic-gradient(from ${satisfiedPercentage * 3.6}deg, #ef4444 ${satisfiedPercentage * 3.6}deg, #ef4444 360deg, transparent 360deg);"
                                     title="Tidak Puas: ${data.not_satisfied} (${notSatisfiedPercentage.toFixed(1)}%)">
                                </div>
                            `;
                        }
                        
                        // Center circle with percentage
                        html += `
                            <div class="absolute inset-8 bg-white rounded-full shadow-inner flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">${satisfiedPercentage.toFixed(1)}%</div>
                                    <div class="text-xs text-gray-500">Puas</div>
                                </div>
                            </div>
                        `;
                        
                        html += '</div>';
                        
                        // Legend
                        html += '<div class="space-y-3 w-full max-w-xs">';
                        
                        // Satisfied
                        html += `
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                                    <span class="text-sm font-medium text-green-800">Puas (4-5)</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-green-800">${data.satisfied}</div>
                                    <div class="text-xs text-green-600">${satisfiedPercentage.toFixed(1)}%</div>
                                </div>
                            </div>
                        `;
                        
                        // Not satisfied
                        html += `
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-red-500 rounded-full mr-3"></div>
                                    <span class="text-sm font-medium text-red-800">Tidak Puas (1-3)</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-red-800">${data.not_satisfied}</div>
                                    <div class="text-xs text-red-600">${notSatisfiedPercentage.toFixed(1)}%</div>
                                </div>
                            </div>
                        `;
                        
                        html += '</div>';
                        
                        // Summary
                        html += `
                            <div class="bg-gray-50 p-4 rounded-lg w-full max-w-xs">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-gray-800">${total}</div>
                                    <div class="text-sm text-gray-600">Total Respons</div>
                                </div>
                            </div>
                        `;
                        
                        html += '</div>';
                        
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="flex items-center justify-center h-full text-gray-500">
                                <div class="text-center">
                                    <i class="fas fa-chart-pie text-4xl mb-4 opacity-50"></i>
                                    <p>Belum ada data kepuasan</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching satisfaction distribution:', error);
                    container.innerHTML = `
                        <div class="flex items-center justify-center h-full text-red-500">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                <p>Gagal memuat grafik kepuasan</p>
                            </div>
                        </div>
                    `;
                });
        }

        // Load charts when page is ready
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                createQuestionChart();
                createSatisfactionChart();
            }, 100);
        });
    </script>
</body>
</html>

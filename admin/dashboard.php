<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireAdminLogin();

// Get statistics
try {
    // Total responses
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM survey_responses");
    $stmt->execute();
    $totalResponses = $stmt->fetch()['total'];
    
    // Average rating
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM survey_answers");
    $stmt->execute();
    $avgRating = round($stmt->fetch()['avg_rating'], 2);
    
    // Satisfaction percentage (rating >= 4)
    $stmt = $pdo->prepare("SELECT COUNT(*) as satisfied FROM survey_answers WHERE rating >= 4");
    $stmt->execute();
    $satisfied = $stmt->fetch()['satisfied'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_answers FROM survey_answers");
    $stmt->execute();
    $totalAnswers = $stmt->fetch()['total_answers'];
    
    $satisfactionPercentage = $totalAnswers > 0 ? round(($satisfied / $totalAnswers) * 100, 1) : 0;
    
    // Recent responses
    $stmt = $pdo->prepare("
        SELECT sr.nomr, sr.created_at, 
               COUNT(sa.id) as total_answers,
               COALESCE(sr.saran, '-') as saran
        FROM survey_responses sr 
        LEFT JOIN survey_answers sa ON sr.id = sa.response_id 
        GROUP BY sr.id 
        ORDER BY sr.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentResponses = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Gagal mengambil data statistik: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - RSUD RAA Soewondo Pati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">

    <style>
        .bg-hospital {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar {
            min-height: 100vh;
        }
        /* Custom Chart Styles */
        .chart-bar {
            transition: all 0.3s ease;
        }
        .chart-bar:hover {
            opacity: 0.8;
            transform: scaleY(1.05);
        }
        .chart-segment {
            transition: all 0.3s ease;
        }
        .chart-segment:hover {
            transform: scale(1.05);
            filter: brightness(1.1);
        }
        .progress-bar {
            transition: width 1s ease-in-out;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .chart-animate {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar bg-gray-800 text-white w-64 p-6">
            <div class="mb-8">
                <h1 class="text-xl font-bold">
                    <i class="fas fa-hospital mr-2"></i>
                    RSUD RAA Soewondo Pati
                </h1>
                <p class="text-gray-300 text-sm">Admin Panel</p>
            </div>
            
            <nav class="space-y-4">
                <a href="dashboard.php" class="flex items-center px-4 py-2 bg-blue-600 rounded-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="data_survei.php" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-database mr-3"></i>
                    Data Survei
                </a>
                <a href="laporan.php" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg">
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
        <div class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-gray-600">Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Responden</h3>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $totalResponses; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-star text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Rating Rata-rata</h3>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $avgRating; ?>/5</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-smile text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Tingkat Kepuasan</h3>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $satisfactionPercentage; ?>%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fas fa-calendar-day text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Hari Ini</h3>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) as today FROM survey_responses WHERE DATE(created_at) = CURDATE()");
                                $stmt->execute();
                                echo $stmt->fetch()['today'];
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Rating Distribution Chart -->
                <div class="bg-white rounded-lg shadow p-6 chart-animate">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribusi Rating</h3>
                    <div id="ratingChart" class="flex items-center justify-center h-64">
                        <div class="text-gray-500">Memuat data...</div>
                    </div>
                </div>

                <!-- Satisfaction Trend -->
                <div class="bg-white rounded-lg shadow p-6 chart-animate">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tren Kepuasan (7 Hari Terakhir)</h3>
                    <div id="trendChart" class="h-64">
                        <div class="flex items-center justify-center h-full text-gray-500">Memuat data...</div>
                    </div>
                </div>
            </div>

            <!-- Recent Responses -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Respons Terbaru</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($recentResponses)): ?>
                        <p class="text-gray-500 text-center py-8">Belum ada data survei</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NOMR</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawaban</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saran</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recentResponses as $response): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($response['nomr']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($response['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $response['total_answers']; ?> pertanyaan
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                                <?php echo htmlspecialchars($response['saran']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="data_survei.php" class="text-blue-600 hover:text-blue-800">
                                Lihat semua data survei →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Rating distribution data
        let ratingData = [];
        let trendData = [];

        // Function to create doughnut chart with Tailwind CSS
        function createRatingChart(data) {
            const container = document.getElementById('ratingChart');
            const total = data.reduce((sum, value) => sum + value, 0);
            
            if (total === 0) {
                container.innerHTML = `
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <i class="fas fa-chart-pie text-4xl mb-4 opacity-50"></i>
                            <p>Belum ada data survei</p>
                        </div>
                    </div>
                `;
                return;
            }

            const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
            const labels = ['1 ⭐', '2 ⭐', '3 ⭐', '4 ⭐', '5 ⭐'];
            
            let html = '<div class="space-y-4">';
            
            // Create pie chart using CSS
            html += '<div class="relative mx-auto w-48 h-48 mb-6">';
            let currentAngle = 0;
            
            data.forEach((value, index) => {
                if (value > 0) {
                    const percentage = (value / total) * 100;
                    const angle = (value / total) * 360;
                    
                    html += `
                        <div class="absolute inset-0 rounded-full chart-segment" 
                             style="background: conic-gradient(from ${currentAngle}deg, ${colors[index]} 0deg, ${colors[index]} ${angle}deg, transparent ${angle}deg); z-index: ${5-index};"
                             title="${labels[index]}: ${value} (${percentage.toFixed(1)}%)">
                        </div>
                    `;
                    currentAngle += angle;
                }
            });
            
            // Add center circle
            html += '<div class="absolute inset-6 bg-white rounded-full shadow-inner flex items-center justify-center">';
            html += `<div class="text-center"><div class="text-2xl font-bold text-gray-800">${total}</div><div class="text-sm text-gray-500">Total</div></div>`;
            html += '</div></div>';
            
            // Add legend
            html += '<div class="grid grid-cols-1 gap-2">';
            data.forEach((value, index) => {
                if (value > 0) {
                    const percentage = (value / total) * 100;
                    html += `
                        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="w-4 h-4 rounded-full mr-3" style="background-color: ${colors[index]}"></div>
                                <span class="text-sm text-gray-700">${labels[index]}</span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900">${value}</div>
                                <div class="text-xs text-gray-500">${percentage.toFixed(1)}%</div>
                            </div>
                        </div>
                    `;
                }
            });
            html += '</div></div>';
            
            container.innerHTML = html;
        }

        // Function to create line chart with Tailwind CSS
        function createTrendChart(labels, data) {
            const container = document.getElementById('trendChart');
            
            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <i class="fas fa-chart-line text-4xl mb-4 opacity-50"></i>
                            <p>Belum ada data tren</p>
                        </div>
                    </div>
                `;
                return;
            }

            const maxValue = Math.max(...data, 100);
            const minValue = Math.min(...data, 0);
            
            let html = '<div class="space-y-4 p-4">';
            
            // Create line chart using CSS
            html += '<div class="relative h-40">';
            
            // Y-axis labels
            html += '<div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-gray-500 pr-2">';
            for (let i = 5; i >= 0; i--) {
                html += `<div>${(i * 20)}%</div>`;
            }
            html += '</div>';
            
            // Chart area
            html += '<div class="ml-8 h-full relative border-l border-b border-gray-200">';
            
            // Grid lines
            for (let i = 0; i <= 5; i++) {
                const y = (i / 5) * 100;
                html += `<div class="absolute w-full border-t border-gray-100" style="bottom: ${y}%"></div>`;
            }
            
            // Data points and line
            const points = data.map((value, index) => {
                const x = (index / (data.length - 1)) * 100;
                const y = (value / 100) * 100;
                return { x, y, value };
            });
            
            // Draw line
            let pathData = '';
            points.forEach((point, index) => {
                if (index === 0) {
                    pathData += `M ${point.x}% ${100 - point.y}%`;
                } else {
                    pathData += ` L ${point.x}% ${100 - point.y}%`;
                }
            });
            
            html += `
                <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="${pathData}" stroke="#3b82f6" stroke-width="0.5" fill="none" vector-effect="non-scaling-stroke"/>
                    <path d="${pathData} L 100% 100% L 0% 100% Z" fill="rgba(59, 130, 246, 0.1)"/>
                </svg>
            `;
            
            // Data points
            points.forEach((point, index) => {
                html += `
                    <div class="absolute w-3 h-3 bg-blue-500 rounded-full border-2 border-white shadow-sm transform -translate-x-1/2 -translate-y-1/2 hover:scale-125 transition-transform cursor-pointer" 
                         style="left: ${point.x}%; bottom: ${point.y}%"
                         title="${labels[index]}: ${point.value}%">
                    </div>
                `;
            });
            
            html += '</div>';
            
            // X-axis labels
            html += '<div class="ml-8 mt-2 flex justify-between text-xs text-gray-500">';
            labels.forEach(label => {
                html += `<div>${label}</div>`;
            });
            html += '</div>';
            
            html += '</div>';
            
            // Summary stats
            const avgValue = data.reduce((sum, val) => sum + val, 0) / data.length;
            const maxDay = labels[data.indexOf(Math.max(...data))];
            
            html += `
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <div class="text-sm text-blue-600">Rata-rata</div>
                        <div class="text-lg font-semibold text-blue-800">${avgValue.toFixed(1)}%</div>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg">
                        <div class="text-sm text-green-600">Tertinggi</div>
                        <div class="text-lg font-semibold text-green-800">${Math.max(...data)}%</div>
                        <div class="text-xs text-green-600">${maxDay}</div>
                    </div>
                </div>
            `;
            
            html += '</div>';
            
            container.innerHTML = html;
        }

        // Load rating distribution data
        function loadRatingChart() {
            fetch('../api/admin/rating_distribution.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data && Array.isArray(data.data)) {
                        createRatingChart(data.data);
                    } else {
                        document.getElementById('ratingChart').innerHTML = `
                            <div class="flex items-center justify-center h-full text-red-500">
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                    <p>Error loading chart data</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching rating distribution:', error);
                    document.getElementById('ratingChart').innerHTML = `
                        <div class="flex items-center justify-center h-full text-red-500">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                <p>Gagal memuat grafik</p>
                            </div>
                        </div>
                    `;
                });
        }

        // Load satisfaction trend data
        function loadTrendChart() {
            fetch('../api/admin/satisfaction_trend.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.labels && data.data && Array.isArray(data.labels) && Array.isArray(data.data)) {
                        createTrendChart(data.labels, data.data);
                    } else {
                        document.getElementById('trendChart').innerHTML = `
                            <div class="flex items-center justify-center h-full text-red-500">
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                    <p>Error loading trend data</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching satisfaction trend:', error);
                    document.getElementById('trendChart').innerHTML = `
                        <div class="flex items-center justify-center h-full text-red-500">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                <p>Gagal memuat grafik tren</p>
                            </div>
                        </div>
                    `;
                });
        }

        // Load charts when page is ready
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                loadRatingChart();
                loadTrendChart();
            }, 100);
        });
    </script>
</body>
</html>

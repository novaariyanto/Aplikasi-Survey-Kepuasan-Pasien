<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireAdminLogin();

// Get all survey responses with question count
try {
    $stmt = $pdo->prepare("
        SELECT 
            sr.id,
            sr.nomr,
            sr.saran,
            sr.created_at,
            COUNT(sa.id) as total_answers,
            AVG(sa.rating) as avg_rating
        FROM survey_responses sr 
        LEFT JOIN survey_answers sa ON sr.id = sa.response_id 
        GROUP BY sr.id 
        ORDER BY sr.created_at DESC
    ");
    $stmt->execute();
    $responses = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Gagal mengambil data survei: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Survei - RSUD RAA Soewondo Pati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
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
                <a href="dashboard.php" class="flex items-center px-4 py-2 hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="data_survei.php" class="flex items-center px-4 py-2 bg-blue-600 rounded-lg">
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
                <h2 class="text-3xl font-bold text-gray-800">Data Survei</h2>
                <p class="text-gray-600">Kelola dan lihat detail data survei kepuasan pasien</p>
            </div>

            <!-- Export Buttons -->
            <div class="mb-6 flex gap-4">
                <a href="../api/admin/export_excel.php" 
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                    <i class="fas fa-file-excel mr-2"></i>
                    Export Excel
                </a>
                <a href="../api/admin/export_pdf.php" 
                   class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i>
                    Export PDF
                </a>
            </div>

            <!-- Survey Data Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Respons Survei</h3>
                </div>
                <div class="p-6">
                    <?php if (isset($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php elseif (empty($responses)): ?>
                        <p class="text-gray-500 text-center py-8">Belum ada data survei</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table id="surveyTable" class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NOMR</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawaban</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating Avg</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saran</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($responses as $index => $response): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo $index + 1; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($response['nomr']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($response['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $response['total_answers']; ?> pertanyaan
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex items-center">
                                                    <span class="mr-1"><?php echo round($response['avg_rating'], 1); ?></span>
                                                    <div class="flex text-yellow-400">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= round($response['avg_rating']) ? '' : 'text-gray-300'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                                <?php echo htmlspecialchars($response['saran'] ?: '-'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button 
                                                    onclick="showDetail(<?php echo $response['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded">
                                                    <i class="fas fa-eye mr-1"></i>
                                                    Detail
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Detail Jawaban Survei</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="modalContent" class="p-6 overflow-y-auto max-h-[60vh]">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#surveyTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                "pageLength": 25,
                "order": [[2, "desc"]], // Sort by date descending
                "columnDefs": [
                    { "orderable": false, "targets": 6 } // Disable sorting on action column
                ]
            });
        });

        // Show detail modal
        async function showDetail(responseId) {
            try {
                const response = await fetch(`../api/admin/get_survey_detail.php?id=${responseId}`);
                const data = await response.json();
                
                if (data.success) {
                    const content = document.getElementById('modalContent');
                    content.innerHTML = `
                        <div class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">Informasi Pasien</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm text-gray-600">NOMR:</label>
                                        <p class="font-medium">${data.response.nomr}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Tanggal:</label>
                                        <p class="font-medium">${new Date(data.response.created_at).toLocaleString('id-ID')}</p>
                                    </div>
                                </div>
                                ${data.response.saran ? `
                                    <div class="mt-4">
                                        <label class="text-sm text-gray-600">Saran:</label>
                                        <p class="mt-1 text-gray-800">${data.response.saran}</p>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-4">Jawaban Per Pertanyaan</h4>
                                <div class="space-y-4">
                                    ${data.answers.map((answer, index) => `
                                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                                            <h5 class="font-medium text-gray-800 mb-2">${index + 1}. ${answer.question_text}</h5>
                                            <div class="flex items-center">
                                                <div class="flex text-yellow-400 mr-3">
                                                    ${Array.from({length: 5}, (_, i) => `
                                                        <i class="fas fa-star ${i < answer.rating ? '' : 'text-gray-300'}"></i>
                                                    `).join('')}
                                                </div>
                                                <span class="text-sm text-gray-600">
                                                    ${answer.rating}/5 - ${getRatingLabel(answer.rating)}
                                                </span>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('detailModal').classList.remove('hidden');
                } else {
                    alert('Gagal memuat detail survei');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat detail');
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        // Get rating label
        function getRatingLabel(rating) {
            const labels = {
                1: 'Sangat Tidak Puas',
                2: 'Tidak Puas',
                3: 'Cukup',
                4: 'Puas',
                5: 'Sangat Puas'
            };
            return labels[rating] || '';
        }

        // Close modal when clicking outside
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

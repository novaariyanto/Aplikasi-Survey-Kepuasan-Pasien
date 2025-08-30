<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireAdminLogin();

$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $questionText = trim($_POST['question_text'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($questionText)) {
                throw new Exception('Teks pertanyaan wajib diisi');
            }
            
            if (strlen($questionText) > 255) {
                throw new Exception('Teks pertanyaan maksimal 255 karakter');
            }
            
            // Add new question
            $stmt = $pdo->prepare("INSERT INTO questions (question_text, is_active) VALUES (?, ?)");
            $stmt->execute([$questionText, $isActive]);
            
            $message = 'Pertanyaan berhasil ditambahkan';
            $messageType = 'success';
            
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $questionText = trim($_POST['question_text'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($questionText)) {
                throw new Exception('Teks pertanyaan wajib diisi');
            }
            
            if (strlen($questionText) > 255) {
                throw new Exception('Teks pertanyaan maksimal 255 karakter');
            }
            
            // Update question
            $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$questionText, $isActive, $id]);
            
            $message = 'Pertanyaan berhasil diperbarui';
            $messageType = 'success';
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            // Check if question has been answered
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM survey_answers WHERE question_id = ?");
            $stmt->execute([$id]);
            $answerCount = $stmt->fetch()['count'];
            
            if ($answerCount > 0) {
                throw new Exception('Pertanyaan tidak dapat dihapus karena sudah ada jawaban survei yang terkait');
            }
            
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = 'Pertanyaan berhasil dihapus';
            $messageType = 'success';
            
        } elseif ($action === 'toggle_status') {
            $id = intval($_POST['id'] ?? 0);
            $isActive = intval($_POST['is_active'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE questions SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $id]);
            
            $message = 'Status pertanyaan berhasil diperbarui';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get all questions
try {
    $stmt = $pdo->prepare("
        SELECT 
            q.id,
            q.question_text,
            q.is_active,
            q.created_at,
            COUNT(sa.id) as answer_count
        FROM questions q
        LEFT JOIN survey_answers sa ON q.id = sa.question_id
        GROUP BY q.id
        ORDER BY q.id ASC
    ");
    $stmt->execute();
    $questions = $stmt->fetchAll();
} catch (Exception $e) {
    $message = 'Gagal mengambil data pertanyaan: ' . $e->getMessage();
    $messageType = 'error';
    $questions = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pertanyaan - RSUD RAA Soewondo Pati</title>
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
                <a href="manajemen_pertanyaan.php" class="flex items-center px-4 py-2 bg-blue-600 rounded-lg">
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
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Manajemen Pertanyaan</h2>
                        <p class="text-gray-600">Kelola pertanyaan survei kepuasan pasien</p>
                    </div>
                    <button onclick="showAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Pertanyaan
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="mb-6">
                    <div class="<?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border px-4 py-3 rounded-md">
                        <div class="flex items-center">
                            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Questions Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Pertanyaan Survei</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table id="questionTable" class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jawaban</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($questions as $index => $question): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $index + 1; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-md">
                                            <?php echo htmlspecialchars($question['question_text']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo $question['answer_count']; ?> jawaban
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" 
                                                           <?php echo $question['is_active'] ? 'checked' : ''; ?>
                                                           onchange="toggleStatus(<?php echo $question['id']; ?>, this.checked)"
                                                           class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out">
                                                    <span class="ml-2 text-sm <?php echo $question['is_active'] ? 'text-green-600' : 'text-red-600'; ?>">
                                                        <?php echo $question['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            <?php echo date('d/m/Y', strtotime($question['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                            <div class="flex space-x-2 justify-center">
                                                <button onclick="showEditModal(<?php echo $question['id']; ?>, '<?php echo htmlspecialchars($question['question_text'], ENT_QUOTES); ?>', <?php echo $question['is_active']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded">
                                                    <i class="fas fa-edit mr-1"></i>
                                                    Edit
                                                </button>
                                                <?php if ($question['answer_count'] == 0): ?>
                                                    <button onclick="confirmDelete(<?php echo $question['id']; ?>)" 
                                                            class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded">
                                                        <i class="fas fa-trash mr-1"></i>
                                                        Hapus
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-400 px-3 py-1" title="Tidak dapat dihapus karena sudah ada jawaban">
                                                        <i class="fas fa-lock mr-1"></i>
                                                        Terkunci
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="questionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Tambah Pertanyaan</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form id="questionForm" method="POST">
                    <div class="p-6 space-y-4">
                        <input type="hidden" id="action" name="action" value="add">
                        <input type="hidden" id="questionId" name="id" value="">
                        
                        <div>
                            <label for="question_text" class="block text-sm font-medium text-gray-700 mb-1">
                                Teks Pertanyaan <span class="text-red-500">*</span>
                            </label>
                            <textarea id="question_text" name="question_text" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Masukkan teks pertanyaan survei..." maxlength="255"></textarea>
                            <p class="text-sm text-gray-500 mt-1">Maksimal 255 karakter</p>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" checked
                                       class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out">
                                <span class="ml-2 text-sm text-gray-700">Aktifkan pertanyaan (tampilkan di survei)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 p-6 border-t border-gray-200">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Forms -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="deleteId" name="id" value="">
    </form>

    <form id="toggleForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" id="toggleId" name="id" value="">
        <input type="hidden" id="toggleStatus" name="is_active" value="">
    </form>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#questionTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                "pageLength": 10,
                "order": [[0, "asc"]], // Sort by number ascending
                "columnDefs": [
                    { "orderable": false, "targets": 5 } // Disable sorting on action column
                ]
            });
        });

        // Show add modal
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Pertanyaan';
            document.getElementById('action').value = 'add';
            document.getElementById('questionId').value = '';
            document.getElementById('question_text').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('questionModal').classList.remove('hidden');
        }

        // Show edit modal
        function showEditModal(id, questionText, isActive) {
            document.getElementById('modalTitle').textContent = 'Edit Pertanyaan';
            document.getElementById('action').value = 'edit';
            document.getElementById('questionId').value = id;
            document.getElementById('question_text').value = questionText;
            document.getElementById('is_active').checked = isActive == 1;
            document.getElementById('questionModal').classList.remove('hidden');
        }

        // Close modal
        function closeModal() {
            document.getElementById('questionModal').classList.add('hidden');
        }

        // Toggle status
        function toggleStatus(id, isActive) {
            document.getElementById('toggleId').value = id;
            document.getElementById('toggleStatus').value = isActive ? 1 : 0;
            document.getElementById('toggleForm').submit();
        }

        // Confirm delete
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus pertanyaan ini?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('questionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Character counter for textarea
        document.getElementById('question_text').addEventListener('input', function() {
            const maxLength = this.getAttribute('maxlength');
            const currentLength = this.value.length;
            const help = this.nextElementSibling;
            help.textContent = `${currentLength}/${maxLength} karakter`;
            
            if (currentLength > maxLength - 20) {
                help.classList.add('text-orange-500');
            } else {
                help.classList.remove('text-orange-500');
            }
        });
    </script>
</body>
</html>

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
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                throw new Exception('Username dan password wajib diisi');
            }
            
            if (strlen($password) < 6) {
                throw new Exception('Password minimal 6 karakter');
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception('Username sudah digunakan');
            }
            
            // Add new admin
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashedPassword]);
            
            $message = 'User admin berhasil ditambahkan';
            $messageType = 'success';
            
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username)) {
                throw new Exception('Username wajib diisi');
            }
            
            // Check if username already exists (except current user)
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                throw new Exception('Username sudah digunakan');
            }
            
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('Password minimal 6 karakter');
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $hashedPassword, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                $stmt->execute([$username, $id]);
            }
            
            $message = 'User admin berhasil diperbarui';
            $messageType = 'success';
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            // Don't allow deleting current user
            if ($id == $_SESSION['admin_id']) {
                throw new Exception('Tidak dapat menghapus akun yang sedang digunakan');
            }
            
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = 'User admin berhasil dihapus';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get all admins
try {
    $stmt = $pdo->prepare("SELECT id, username, created_at FROM admins ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll();
} catch (Exception $e) {
    $message = 'Gagal mengambil data admin: ' . $e->getMessage();
    $messageType = 'error';
    $admins = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - RSUD RAA Soewondo Pati</title>
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
                <a href="manajemen_user.php" class="flex items-center px-4 py-2 bg-blue-600 rounded-lg">
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
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Manajemen User</h2>
                        <p class="text-gray-600">Kelola akun administrator sistem</p>
                    </div>
                    <button onclick="showAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Tambah Admin
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

            <!-- Admin Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Administrator</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table id="adminTable" class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($admins as $index => $admin): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $index + 1; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($admin['username']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                    <i class="fas fa-user mr-1"></i>
                                                    Aktif (Anda)
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                    <i class="fas fa-user-check mr-1"></i>
                                                    Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="showEditModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')" 
                                                        class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded">
                                                    <i class="fas fa-edit mr-1"></i>
                                                    Edit
                                                </button>
                                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                    <button onclick="confirmDelete(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')" 
                                                            class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded">
                                                        <i class="fas fa-trash mr-1"></i>
                                                        Hapus
                                                    </button>
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
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Tambah Admin</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form id="userForm" method="POST">
                    <div class="p-6 space-y-4">
                        <input type="hidden" id="action" name="action" value="add">
                        <input type="hidden" id="userId" name="id" value="">
                        
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                Username <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="username" name="username" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Masukkan username">
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password <span id="passwordRequired" class="text-red-500">*</span>
                            </label>
                            <input type="password" id="password" name="password"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Masukkan password (minimal 6 karakter)">
                            <p id="passwordHelp" class="text-sm text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password</p>
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

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="deleteId" name="id" value="">
    </form>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#adminTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                "pageLength": 10,
                "order": [[2, "desc"]], // Sort by created date descending
                "columnDefs": [
                    { "orderable": false, "targets": 4 } // Disable sorting on action column
                ]
            });
        });

        // Show add modal
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Admin';
            document.getElementById('action').value = 'add';
            document.getElementById('userId').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordRequired').style.display = 'inline';
            document.getElementById('passwordHelp').style.display = 'none';
            document.getElementById('userModal').classList.remove('hidden');
        }

        // Show edit modal
        function showEditModal(id, username) {
            document.getElementById('modalTitle').textContent = 'Edit Admin';
            document.getElementById('action').value = 'edit';
            document.getElementById('userId').value = id;
            document.getElementById('username').value = username;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHelp').style.display = 'block';
            document.getElementById('userModal').classList.remove('hidden');
        }

        // Close modal
        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        // Confirm delete
        function confirmDelete(id, username) {
            if (confirm(`Apakah Anda yakin ingin menghapus admin "${username}"?`)) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

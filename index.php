<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei Kepuasan Pasien - RSUD RAA Soewondo Pati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .star-rating {
            display: flex;
            gap: 4px;
            margin: 8px 0;
        }
        .star {
            font-size: 24px;
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .star:hover,
        .star.active {
            color: #fbbf24;
        }
        .star:hover {
            transform: scale(1.1);
        }
        .rating-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
            text-align: center;
        }
        .bg-hospital {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-hospital text-white py-6 mb-8">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-3xl font-bold mb-2">
                <i class="fas fa-hospital mr-3"></i>
                RSUD RAA Soewondo Pati
            </h1>
            <p class="text-lg opacity-90">Survei Kepuasan Pasien Rawat Inap</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 max-w-2xl">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Survei Kepuasan Layanan</h2>
                <p class="text-gray-600">Bantuan Anda sangat berharga untuk meningkatkan kualitas pelayanan kami</p>
            </div>

            <!-- Alert Messages -->
            <div id="alert-container" class="hidden mb-4"></div>

            <!-- Survey Form -->
            <form id="surveyForm" class="space-y-6">
                <!-- NOMR Input -->
                <div class="space-y-2">
                    <label for="nomr" class="block text-sm font-medium text-gray-700">
                        Nomor Rekam Medis (NOMR) <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nomr" 
                        name="nomr" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Masukkan nomor rekam medis Anda">
                </div>

                <!-- Questions Container -->
                <div id="questions-container" class="space-y-6">
                    <!-- Questions will be loaded here via JavaScript -->
                </div>

                <!-- Saran Input -->
                <div class="space-y-2">
                    <label for="saran" class="block text-sm font-medium text-gray-700">
                        Saran dan Masukan (Opsional)
                    </label>
                    <textarea 
                        id="saran" 
                        name="saran" 
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Tuliskan saran atau masukan Anda untuk peningkatan pelayanan..."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Kirim Survei
                    </button>
                </div>
            </form>
        </div>

        <!-- Thank You Message (Hidden by default) -->
        <div id="thankYouMessage" class="hidden bg-green-50 border border-green-200 rounded-lg p-6 text-center">
            <div class="text-green-600 mb-4">
                <i class="fas fa-check-circle text-4xl"></i>
            </div>
            <h3 class="text-xl font-bold text-green-800 mb-2">Terima Kasih!</h3>
            <p class="text-green-700">Terima kasih telah mengisi survei kepuasan kami. Masukan Anda sangat berharga untuk meningkatkan kualitas pelayanan RSUD Soewondo.</p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-12 bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2024 RSUD RAA Soewondo Pati. Semua hak dilindungi.</p>
        </div>
    </footer>

    <script>
        // Rating labels
        const ratingLabels = [
            '', // Index 0 (tidak digunakan)
            'Sangat Tidak Puas',
            'Tidak Puas', 
            'Cukup',
            'Puas',
            'Sangat Puas'
        ];

        // Survey answers storage
        let surveyAnswers = {};

        // Load questions when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadQuestions();
        });

        // Load questions from database
        async function loadQuestions() {
            try {
                const response = await fetch('api/get_questions.php');
                const questions = await response.json();
                
                if (questions.success) {
                    renderQuestions(questions.data);
                } else {
                    showAlert('error', 'Gagal memuat pertanyaan survei');
                }
            } catch (error) {
                showAlert('error', 'Terjadi kesalahan saat memuat pertanyaan');
                console.error('Error:', error);
            }
        }

        // Render questions to the page
        function renderQuestions(questions) {
            const container = document.getElementById('questions-container');
            container.innerHTML = '';

            questions.forEach((question, index) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'bg-gray-50 p-4 rounded-lg border';
                questionDiv.innerHTML = `
                    <div class="space-y-3">
                        <h3 class="text-lg font-medium text-gray-800">
                            ${index + 1}. ${question.question_text} <span class="text-red-500">*</span>
                        </h3>
                        <div class="star-rating" data-question-id="${question.id}">
                            ${Array.from({length: 5}, (_, i) => 
                                `<span class="star" data-rating="${i + 1}">
                                    <i class="fas fa-star"></i>
                                </span>`
                            ).join('')}
                        </div>
                        <div class="rating-label" id="label-${question.id}">
                            Pilih rating untuk pertanyaan ini
                        </div>
                    </div>
                `;
                container.appendChild(questionDiv);

                // Add click event listeners for stars
                const stars = questionDiv.querySelectorAll('.star');
                stars.forEach(star => {
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.dataset.rating);
                        const questionId = parseInt(this.closest('.star-rating').dataset.questionId);
                        setRating(questionId, rating);
                    });

                    star.addEventListener('mouseenter', function() {
                        const rating = parseInt(this.dataset.rating);
                        const questionId = parseInt(this.closest('.star-rating').dataset.questionId);
                        highlightStars(questionId, rating);
                    });
                });

                // Reset highlights on mouse leave
                questionDiv.querySelector('.star-rating').addEventListener('mouseleave', function() {
                    const questionId = parseInt(this.dataset.questionId);
                    const currentRating = surveyAnswers[questionId] || 0;
                    highlightStars(questionId, currentRating);
                });
            });
        }

        // Set rating for a question
        function setRating(questionId, rating) {
            surveyAnswers[questionId] = rating;
            highlightStars(questionId, rating);
            
            const label = document.getElementById(`label-${questionId}`);
            label.textContent = ratingLabels[rating];
            label.className = 'rating-label text-blue-600 font-medium';
        }

        // Highlight stars based on rating
        function highlightStars(questionId, rating) {
            const starRating = document.querySelector(`[data-question-id="${questionId}"]`);
            const stars = starRating.querySelectorAll('.star');
            
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // Handle form submission
        document.getElementById('surveyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const nomr = document.getElementById('nomr').value.trim();
            const saran = document.getElementById('saran').value.trim();

            // Validation
            if (!nomr) {
                showAlert('error', 'Nomor Rekam Medis wajib diisi');
                return;
            }

            // Check if all questions are answered
            const questionsContainer = document.getElementById('questions-container');
            const totalQuestions = questionsContainer.querySelectorAll('.star-rating').length;
            
            if (Object.keys(surveyAnswers).length !== totalQuestions) {
                showAlert('error', 'Semua pertanyaan harus dijawab');
                return;
            }

            // Disable submit button
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';

            try {
                const response = await fetch('api/submit_survey.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nomr: nomr,
                        saran: saran,
                        answers: surveyAnswers
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // Hide form and show thank you message
                    document.querySelector('.bg-white').style.display = 'none';
                    document.getElementById('thankYouMessage').classList.remove('hidden');
                } else {
                    showAlert('error', result.message || 'Gagal mengirim survei');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Kirim Survei';
                }
            } catch (error) {
                showAlert('error', 'Terjadi kesalahan saat mengirim survei');
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Kirim Survei';
            }
        });

        // Show alert message
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700';
            const iconClass = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
            
            alertContainer.innerHTML = `
                <div class="${alertClass} border px-4 py-3 rounded-md">
                    <div class="flex items-center">
                        <i class="fas ${iconClass} mr-2"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
            alertContainer.classList.remove('hidden');
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                alertContainer.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>
</html>

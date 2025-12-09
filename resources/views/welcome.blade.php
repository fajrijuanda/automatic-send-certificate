<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate Sender</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <style>
        :root {
            --primary: #FFB7B2;
            --secondary: #B5EAD7;
            --accent: #C7CEEA;
            --text: #555;
            --bg-gradient: linear-gradient(135deg, #FF9AA2 0%, #FFB7B2 25%, #FFDAC1 50%, #E2F0CB 75%, #B5EAD7 100%);
        }

        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text);
            background: url('{{ asset("images/bg.png") }}') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.4);
            z-index: -1;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 50px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
            z-index: 10;
        }

        .logo {
            height: 50px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 50px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .hero-text {
            flex: 1;
            min-width: 300px;
            padding-right: 50px;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: -webkit-linear-gradient(45deg, #FF6F61, #6B5B95);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #666;
            margin-bottom: 30px;
        }

        .hero-image {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }

        .hero-image img {
            width: 100%;
            max-width: 500px;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 450px;
            margin-top: 20px;
            border: 1px solid rgba(255,255,255,0.8);
            position: relative;
            z-index: 5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #777;
        }

        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: #fff;
        }

        .btn-primary {
            display: inline-block;
            background: linear-gradient(45deg, #FF9AA2, #ff80ab);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            box-shadow: 0 5px 15px rgba(255, 128, 171, 0.4);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Loading Overlay */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            z-index: 1000;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .loading-mascot {
            width: 150px;
            margin-bottom: 20px;
            animation: bounce 1s infinite alternate;
        }

        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-20px); }
        }

        .loading-text {
            font-size: 1.5rem;
            color: #FF9AA2;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .loading-dots::after {
            content: '.';
            animation: dots 1.5s steps(5, end) infinite;
        }

        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60% { content: '...'; }
            80%, 100% { content: ''; }
        }

        /* Success Modal */
        #success-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        #success-modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 30px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: scale(0.7);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        #success-modal.active .modal-content {
            transform: scale(1);
        }

        .modal-mascot {
            width: 100px;
            margin-bottom: 15px;
        }

        .modal-title {
            font-size: 2rem;
            color: #B5EAD7;
            color: #4db6ac;
            margin: 0;
            margin-bottom: 10px;
        }

        .modal-body {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 25px;
        }

        .btn-modal {
            background: var(--primary);
            color: white;
            padding: 10px 30px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            cursor: pointer;
            border: none;
        }

        /* Confetti decoration */
        .confetti-piece {
            position: absolute;
            width: 10px; height: 10px;
            background: #ffd54f;
            top: -10px;
            opacity: 0;
        }

    </style>
</head>
<body>

    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <img src="{{ asset('images/mascot.png') }}" alt="Processing..." class="loading-mascot">
        <div class="loading-text">Sending Certificates<span class="loading-dots"></span></div>
        <p style="color: #999; margin-top: 10px;">Please wait while we split and email your files.</p>
    </div>

    <!-- Success Modal -->
    <div id="success-modal">
        <div class="modal-content">
            <img src="{{ asset('images/mascot.png') }}" alt="Success!" class="modal-mascot">
            <h2 class="modal-title">Yay! Done!</h2>
            <div class="modal-body" id="success-message">
                Successfully processed 0 certificates!
            </div>
            <button class="btn-modal" onclick="closeModal()">Awesome!</button>
        </div>
    </div>

    <nav class="navbar">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">
        <div style="font-weight: bold; color: #aaa;">AUTOMATIC MAILER</div>
    </nav>

    <div class="container">
        
        <div class="hero-text">
            <h1>Send Certificates Effortlessly</h1>
            <p>Upload your recipient list and your bulk certificate PDF. We'll split the pages and email each certificate to the right person automatically.</p>
            
            <div class="card">
                <form id="certificate-form" action="{{ route('send.certificates') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group">
                        <label>1. Upload List (Excel)</label>
                        <input type="file" name="excel_file" class="file-upload" accept=".xlsx,.xls,.csv" required style="width: 100%; box-sizing: border-box;">
                    </div>

                    <div class="form-group">
                        <label>2. Upload Certificates (PDF)</label>
                        <input type="file" name="pdf_file" class="file-upload" accept=".pdf" required style="width: 100%; box-sizing: border-box;">
                    </div>

                    <button type="submit" class="btn-primary">Process & Send</button>
                </form>
            </div>
        </div>

        <div class="hero-image">
            <img src="{{ asset('images/hero.png') }}" alt="Sending Certificates">
        </div>

    </div>

    <!-- Script for AJAX -->
    <script>
        const form = document.getElementById('certificate-form');
        const loadingOverlay = document.getElementById('loading-overlay');
        const successModal = document.getElementById('success-modal');
        const successMessage = document.getElementById('success-message');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show Loading
            loadingOverlay.style.display = 'flex';

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest', // Important for Laravel to detect wantsJson
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingOverlay.style.display = 'none';
                
                if (data.success) {
                    successMessage.innerHTML = `Successfully sent <b>${data.count}</b> certificates!<br>All emails have been delivered.`;
                    successModal.classList.add('active');
                } else {
                     // Handle error (maybe just alert? For now let's assume simple alert for error)
                     alert('Error: ' + (data.message || 'Something went wrong.'));
                }
            })
            .catch(error => {
                loadingOverlay.style.display = 'none';
                alert('An unexpected error occurred. Please try again.');
                console.error(error);
            });
        });

        function closeModal() {
            successModal.classList.remove('active');
            // user might want to reset form?
            form.reset();
        }
    </script>
</body>
</html>

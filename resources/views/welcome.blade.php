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
            /* Use the generated background image if available, else gradient */
            background: url('{{ asset("images/bg.png") }}') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }

        /* Overlay to ensure readability if BG is too busy */
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 128, 171, 0.6);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
        }

    </style>
</head>
<body>

    <nav class="navbar">
        <!-- Logo -->
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo">
        <div style="font-weight: bold; color: #aaa;">AUTOMATIC MAILER</div>
    </nav>

    <div class="container">
        
        <div class="hero-text">
            <h1>Send Certificates Effortlessly</h1>
            <p>Upload your recipient list and your bulk certificate PDF. We'll split the pages and email each certificate to the right person automatically.</p>
            
            <div class="card">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif
                
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('send.certificates') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group">
                        <label>1. Upload List (Excel)</label>
                        <input type="file" name="excel_file" class="file-upload" accept=".xlsx,.xls,.csv" required style="width: 100%; box-sizing: border-box;">
                        <small style="color: #999;">Columns: Name, Email</small>
                    </div>

                    <div class="form-group">
                        <label>2. Upload Certificates (PDF)</label>
                        <input type="file" name="pdf_file" class="file-upload" accept=".pdf" required style="width: 100%; box-sizing: border-box;">
                        <small style="color: #999;">Single PDF with all certificates</small>
                    </div>

                    <button type="submit" class="btn-primary">Process & Send</button>
                </form>
            </div>
        </div>

        <div class="hero-image">
            <img src="{{ asset('images/hero.png') }}" alt="Sending Certificates">
        </div>

    </div>

</body>
</html>

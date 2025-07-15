<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - My Laravel App</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-wrapper">
        <div class="main-content">
            <div class="content-container">
                <h1>WhatsApp Blast - KamarOTO</h1>
                <p>Sistem Informasi Internal untuk mengelola dan menjalankan WhatsApp Blast — solusi efektif dalam mengirim pesan broadcast ke pelanggan.</p>
                <!-- Kamu bisa tambahkan ilustrasi, gambar, atau logo di sini -->
            </div>

            <div class="login-container">
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <h2>Register</h2>

                    @if ($errors->any())
                        <div class="error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="input-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}">
                    </div>

                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="username" name="username" id="username" required">
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>

                    <div class="input-group">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required>
                    </div>

                    <div class="input-group">
                        <button type="submit">Daftar</button>
                    </div>

                    <div class="bottom-link">
                        Sudah punya akun? <a href="{{ route('login') }}">Login</a>
                    </div>
                </form>
            </div>
        </div>
        <footer class="footer">
            &copy; {{ date('Y') }} PT KamarOTO Teknologi Indonesia. All rights reserved.
        </footer>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT KamarOTO</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth_content.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-wrapper">
        <div class="main-content">
            <x-auth_content />

            <div class="login-container">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <h2>Login</h2>

                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" name="name" id="username" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>

                    <div class="input-group">
                        <button type="submit">Login</button>
                    </div>

                    <div class="bottom-link">
                        Belum punya akun? <a href="{{ route('register') }}">Daftar</a>
                    </div>
                </form>
            </div>
        </div>

        <footer class="footer">
            &copy; {{ date('Y') }} KamarOTO. All rights reserved.
        </footer>
    </div>
</body>
</html>

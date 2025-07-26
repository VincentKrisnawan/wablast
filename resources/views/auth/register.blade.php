<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - My Laravel App</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth_content.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-wrapper">
        <div class="main-content">
            <x-auth_content />

            <div class="login-container">
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <h2>Register</h2>

                    <!-- @if ($errors->any())
                        <div class="error">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif -->

                    <div class="input-group">
                        <label for="name">Username</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}">
                    </div>

                    <div class="input-group">
                        <label for="username">Email </label>
                        <input type="email" name="email" id="username" required">
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>

                    <div class="input-group">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required>
                    </div>

                    @if ($errors->any())
                        <div class="error-messages">
                            @foreach ($errors->all() as $error)
                                <span>{{ $error }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="input-group">
                        <button type="submit">Daftar</button>
                    </div>

                    <div class="bottom-link">
                        Sudah punya akun? <a href="/">Login</a>
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

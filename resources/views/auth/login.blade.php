<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Laravel App</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <h2>Login</h2>

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
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}">
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
    <div class="footer">
        <p class="text-center">&copy; {{ date('Y') }} PT KamarOTO Teknologi Indonesia. All rights reserved.</p>
        <!-- <p class="text-center">Developed by <a href="https://github.com yourusername">Your Name</a></p> -->
    </div>
</body>
</html>

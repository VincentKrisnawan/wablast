<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            {{-- Pastikan Anda memiliki gambar profile.png di public/images --}}
            <img src="{{ asset('images/profile.png') }}" alt="Profile" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
            <div class="d-flex flex-column">
                {{-- Tampilkan nama dan email user yang sedang login --}}
                @auth
                    <strong class="text-dark">{{ Auth::user()->name }}</strong>
                    <small class="text-muted">{{ Auth::user()->email }}</small>
                @endauth
            </div>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
            aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarMenu">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a> {{-- Arahkan ke rute dashboard Anda --}}
                </li>

                {{--
                    Tombol "Lihat Data Terakhir" ini akan muncul secara dinamis.
                    Variabel $latest_batch_id disediakan secara global oleh AppServiceProvider.
                --}}
                <li class="nav-item">
                    <a href="{{ route('contacts.all') }}" class="nav-link">Lihat Semua Data</a>
                </li>

                <li class="nav-item ms-2">
                    {{-- Form untuk logout yang aman menggunakan metode POST --}}
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link nav-link text-danger">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

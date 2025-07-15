<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WABLAST Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <x-navbar />

    <div class="container my-4">
        
        {{-- Grid Konten Utama --}}
        <div class="row g-4">
            {{-- Kolom Kiri untuk Upload dan Sesi --}}
            <div class="col-lg-8">
                <div class="d-flex flex-column gap-4">
                    <div class="card-item">
                        <h5 class="card-title">Upload Kontak Baru</h5>
                        <form action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="file_kontak" class="form-label">Pilih file Excel/CSV</label>
                                <input class="form-control @error('file_kontak') is-invalid @enderror" type="file" id="file_kontak" name="file_kontak" required>
                                @error('file_kontak')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-upload">Upload & Buat Sesi</button>
                        </form>
                    </div>

                    <div class="card-item">
                        <h5 class="card-title">Daftar Sesi Pengiriman</h5>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Session</th>
                                        <th>Jumlah Kontak</th>
                                        <th>Terkirim</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sessions as $session)
                                    <tr>
                                        <td>{{ $session->session_number }}</td>
                                        <td>
                                            @if($loop->last && $latest_batch && $latest_batch->total_contacts % 100 != 0)
                                                {{ $latest_batch->total_contacts % 100 }}
                                            @else
                                                100
                                            @endif
                                        </td>
                                        <td>{{ $session->messages_count }}</td>
                                        <td>
                                            @php
                                                $statusClass = 'status-pending';
                                                if ($session->status == 'done') $statusClass = 'status-berhasil';
                                                if ($session->status == 'in_progress') $statusClass = 'status-inprogress';
                                            @endphp
                                            <span class="status {{ $statusClass }}">{{ strtoupper($session->status) }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-kirim btn-sm" data-session-id="{{ $session->id }}" @if($session->status != 'pending') disabled @endif>Kirim</button>
                                            <form action="{{ route('session.destroy', $session) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Anda yakin ingin menghapus sesi ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center p-4">
                                            Belum ada sesi. Silakan upload file kontak terlebih dahulu.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan untuk Template --}}
            <div class="col-lg-4">
                <div class="card-item">
                    <h5 class="card-title">Template Pesan</h5>
                    <form action="{{ route('template.store') }}" method="POST">
                        @csrf
                        @if($latest_batch_id)
                            <input type="hidden" name="batch_id" value="{{ $latest_batch_id }}">
                        @endif
                        <div class="mb-3">
                            <label for="template_text" class="form-label">Isi Pesan Anda:</label>
                            <textarea id="template_text" name="template_text" class="message-box @error('template_text') is-invalid @enderror" rows="10" placeholder="Tulis template pesan Anda di sini..." required>{{ $template_text }}</textarea>
                             @error('template_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-simpan" @if(!$latest_batch_id) disabled @endif>Simpan Template</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bagian Reset Data --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="card-item bg-light">
                     <h5 class="card-title text-danger">Zona Berbahaya</h5>
                     <p class="text-muted">Tindakan ini akan menghapus semua batch, kontak, sesi, template, dan file yang telah diupload. Tindakan ini tidak dapat dibatalkan.</p>
                     {{-- PERBAIKAN: Hapus onsubmit dan tambahkan id="reset-form" --}}
                     <form id="reset-form" action="{{ route('data.cleanup') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100">Reset Semua Data & File</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sendButtons = document.querySelectorAll('.btn-kirim');

            sendButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const sessionId = this.dataset.sessionId;
                    const url = `/sessions/${sessionId}/send`;

                    button.disabled = true; // Disable the button immediately

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            // If response is not OK (e.g., 400, 500), parse error message
                            return response.json().then(errorData => {
                                throw new Error(errorData.message || 'Terjadi kesalahan saat mengirim pesan.');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // If we reach here, the request was successful
                        const row = button.closest('tr');
                        const statusCell = row.querySelector('.status');

                        // Update status to in_progress only on success
                        statusCell.textContent = 'IN_PROGRESS';
                        statusCell.classList.remove('status-pending', 'status-berhasil');
                        statusCell.classList.add('status-inprogress');

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: error.message || 'Terjadi kesalahan yang tidak terduga.'
                        });
                        // Re-enable button on any error
                        button.disabled = false;
                    });
                });
            });
        });

        // Periksa apakah ada pesan 'success' dari session
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        @endif

        // Periksa apakah ada pesan 'error' dari session
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '{{ session('error') }}'
            });
        @endif

        // PERBAIKAN: Tambahkan event listener untuk form reset
        document.getElementById('reset-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah form dari submit langsung

            Swal.fire({
                title: 'Anda Yakin?',
                text: "Semua data dan file yang terupload akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika user menekan "Ya", submit form secara manual
                    event.target.submit();
                }
            });
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WABLAST Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pagination.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <x-navbar />

    <div class="container my-4">
        <div class="row g-4">
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
                                            {{-- PERBAIKAN: Ganti $loop->last dengan perbandingan nomor sesi --}}
                                            {{-- Ini akan memastikan sisa kontak hanya ditampilkan di sesi terakhir yang sebenarnya --}}
                                            @if($latest_batch && $session->session_number == $total_session_count && $latest_batch->total_contacts % 100 != 0)
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

                        {{-- Tampilkan link pagination di bawah tabel --}}
                        @if ($sessions->hasPages())
                            <div class="mt-4 d-flex justify-content-center">
                                {{ $sessions->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-item">
                    <h5 class="card-title">Template Pesan</h5>
                    <form action="{{ route('template.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="template_text" class="form-label">Isi Pesan Anda:</label>
                            <textarea id="template_text" name="template_text" class="message-box @error('template_text') is-invalid @enderror" rows="10" placeholder="Tulis template pesan Anda di sini..." required>{{ $template_text }}</textarea>
                             @error('template_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-simpan">Simpan Template</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card-item bg-light">
                     <h5 class="card-title text-danger">Zona Berbahaya</h5>
                     <p class="text-muted">Tindakan ini akan menghapus semua batch, kontak, sesi, template, dan file yang telah diupload. Tindakan ini tidak dapat dibatalkan.</p>
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
                            const contentType = response.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                return response.json().then(errorData => {
                                    throw new Error(errorData.message || 'Terjadi kesalahan saat mengirim pesan.');
                                });
                            } else {
                                // If it's not JSON, it's likely an HTML error page or plain text
                                return response.text().then(text => {
                                    console.error('Server responded with non-JSON:', text);
                                    throw new Error('Terjadi kesalahan pada server. Silakan coba lagi nanti.');
                                });
                            }
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
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '{{ session('error') }}'
            });
        @endif

        document.getElementById('reset-form').addEventListener('submit', function(event) {
            event.preventDefault();
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
                    event.target.submit();
                }
            });
        });
    </script>
</body>
</html>

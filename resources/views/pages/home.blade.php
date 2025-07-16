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
                        <form id="upload-form" action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data">
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
                                    <tr id="session-row-{{ $session->id }}">
                                        <td>Sesi {{ $sessions->total() - (($sessions->currentPage() - 1) * $sessions->perPage()) - $loop->index }}</td>
                                        <td>
                                            @php
                                                $totalSessionsForThisBatch = ceil($session->batch->total_contacts / 100);
                                            @endphp
                                            @if($session->session_number == $totalSessionsForThisBatch && $session->batch->total_contacts % 100 != 0)
                                                {{ $session->batch->total_contacts % 100 }}
                                            @else
                                                100
                                            @endif
                                        </td>
                                        <td id="sent-count-{{ $session->id }}">{{ $session->messages_count }}</td>
                                        <td>
                                            @php
                                                $statusClass = 'status-pending';
                                                if ($session->status == 'done') $statusClass = 'status-berhasil';
                                                if ($session->status == 'in progress') $statusClass = 'status-inprogress';
                                            @endphp
                                            <span class="status {{ $statusClass }}">{{ strtoupper($session->status) }}</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-kirim btn-sm" data-session-id="{{ $session->id }}" @if($session->status != 'pending') disabled @endif>Kirim</button>
                                            {{-- PERBAIKAN: Hapus onsubmit dan tambahkan class="delete-form" --}}
                                            <form class="delete-form" action="{{ route('session.destroy', $session) }}" method="POST" style="display: inline-block;">
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

                        @if ($sessions->hasPages())
                            <x-pagination :paginator="$sessions" />
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
                     <h5 class="card-title text-danger">Peringatan!</h5>
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
            
            // ... (Script polling dan tombol kirim tidak berubah)

            const uploadForm = document.getElementById('upload-form');
            if (uploadForm) {
                uploadForm.addEventListener('submit', function() {
                    const uploadButton = uploadForm.querySelector('.btn-upload');
                    if (uploadButton) {
                        uploadButton.disabled = true;
                        uploadButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengupload...`;
                    }
                });
            }

            // PERBAIKAN: Tambahkan logika untuk tombol hapus
            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault(); // Mencegah form dari submit langsung

                    Swal.fire({
                        title: 'Anda Yakin?',
                        text: "Sesi dan kontak terkait akan dihapus secara permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const deleteButton = form.querySelector('button[type="submit"]');
                            deleteButton.disabled = true;
                            deleteButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                            
                            // Submit form setelah konfirmasi
                            event.target.submit();
                        }
                    });
                });
            });


            // ... (Sisa script lainnya)
            function startPolling(sessionId) {
                const row = document.getElementById(`session-row-${sessionId}`);
                if (!row) return;

                const button = row.querySelector('.btn-kirim');
                const statusTd = row.querySelector('.status').parentElement;
                const sentCountCell = document.getElementById(`sent-count-${sessionId}`);

                const pollInterval = setInterval(() => {
                    const url = `/session/${sessionId}/status?_=${new Date().getTime()}`;

                    fetch(url)
                        .then(res => {
                            if (!res.ok) {
                                throw new Error('Pemeriksaan status server gagal. Silakan refresh halaman.');
                            }
                            return res.json();
                        })
                        .then(statusData => {
                            if (sentCountCell) {
                                sentCountCell.textContent = statusData.sent_count;
                            }
                            
                            if (statusData.status === 'in_progress') {
                                statusTd.innerHTML = `<span class="status status-inprogress">IN_PROGRESS</span>`;
                                button.disabled = true;
                                button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                            } else if (statusData.status === 'done') {
                                clearInterval(pollInterval);
                                statusTd.innerHTML = `<span class="status status-berhasil">DONE</span>`;
                                button.innerHTML = 'Kirim';
                                button.disabled = true;
                            } else if (statusData.status === 'failed') {
                                clearInterval(pollInterval);
                                statusTd.innerHTML = `<span class="status status-terkendala">FAILED</span>`;
                                button.disabled = false;
                                button.innerHTML = 'Kirim Ulang';
                            }
                        })
                        .catch(err => {
                            console.error('Polling error:', err);
                            Swal.fire({ icon: 'error', title: 'Polling Gagal', text: err.message });
                            clearInterval(pollInterval);
                        });
                }, 3000);
            }

            const sendButtons = document.querySelectorAll('.btn-kirim');
            sendButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const sessionId = this.dataset.sessionId;
                    
                    this.disabled = true;
                    this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                    const statusTd = this.closest('tr').querySelector('.status').parentElement;
                    statusTd.innerHTML = `<span class="status status-inprogress">STARTING</span>`;

                    fetch(`/session/${sessionId}/send`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => { throw new Error(errorData.message || 'Gagal memulai proses.'); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.fire({ icon: 'info', title: 'Info', text: data.message, timer: 2500, showConfirmButton: false });
                        startPolling(sessionId);
                    })
                    .catch(error => {
                        Swal.fire({ icon: 'error', title: 'Gagal Memulai!', text: error.message });
                        this.disabled = false;
                        this.innerHTML = 'Kirim';
                        statusTd.innerHTML = `<span class="status status-pending">PENDING</span>`;
                    });
                });
            });

            const allSessionRows = document.querySelectorAll('.data-table tbody tr');
            allSessionRows.forEach(row => {
                const statusSpan = row.querySelector('.status');
                if (statusSpan && statusSpan.textContent.trim().toUpperCase() === 'IN PROGRESS') {
                    const button = row.querySelector('.btn-kirim');
                    if (button) {
                        button.disabled = true;
                        button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                        
                        const sessionId = button.dataset.sessionId;
                        startPolling(sessionId);
                    }
                }
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

            const resetForm = document.getElementById('reset-form');
            if(resetForm) {
                resetForm.addEventListener('submit', function(event) {
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
            }

            const textarea = document.getElementById('template_text');
            if (textarea) {
                const autoResize = () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                };
                textarea.addEventListener('input', autoResize, false);
                autoResize();
            }
        });
    </script>
</body>
</html>

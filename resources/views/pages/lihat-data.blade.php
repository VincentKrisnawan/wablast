<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Data Kontak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    {{-- Hubungkan ke file CSS navbar dan paginasi --}}
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pagination.css') }}">

    <style>
        body { 
            background-color: #f8fafc; 
            font-family: 'Poppins', sans-serif;
        }
        .card {
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .table th {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    {{-- Tambahkan komponen navbar di sini --}}
    <x-navbar />

    <div class="container my-4">
        <div class="card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Semua Data Kontak</h4>
                        <p class="mb-0 text-muted">Menampilkan semua kontak yang pernah diupload.</p>
                    </div>
                    <div>
                        <a href="{{ route('home') }}" class="btn btn-secondary">Kembali ke Home</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Nama</th>
                                <th>No HP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $index => $contact)
                                <tr>
                                    {{-- Menampilkan nomor urut sesuai halaman paginasi --}}
                                    <td>{{ $contacts->firstItem() + $index }}</td>
                                    <td>{{ $contact->nama }}</td>
                                    <td>{{ $contact->no_hp }}</td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- Menyesuaikan colspan karena kolom lebih sedikit --}}
                                    <td colspan="3" class="text-center py-4">Tidak ada data kontak yang ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Tampilkan link pagination di tengah --}}
                @if ($contacts->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $contacts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

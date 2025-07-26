<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Data Kontak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    {{-- Hubungkan ke file CSS navbar dan paginasi kustom --}}
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
                        <h5 class="mb-0">Semua Data Kontak</h5>
                        <p class="mb-0 text-muted">Menampilkan semua kontak yang pernah diupload.</p>
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
                                @if(Auth::user()->role === 'admin')
                                    <th>Uploader</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $index => $contact)
                            <tr>
                                <td>{{ $contacts->firstItem() + $index }}</td>
                                <td>{{ $contact->nama }}</td>
                                <td>{{ $contact->no_hp }}</td>
                                @if(Auth::user()->role === 'admin')
                                    <td>{{ $contact->batch->user->email ?? 'N/A' }}</td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                {{-- Sesuaikan colspan --}}
                                <td colspan="{{ Auth::user()->role === 'admin' ? '4' : '3' }}" class="text-center py-4">
                                    Tidak ada data kontak yang ditemukan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Panggil komponen paginasi kustom --}}
                <div class="mt-3">
                    <x-pagination :paginator="$contacts" />
                </div>

            </div>
        </div>
    </div>
</body>
</html>

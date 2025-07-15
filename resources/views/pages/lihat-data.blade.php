<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kontak - Batch #{{ $batch->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f9; }
        .table-responsive { max-height: 80vh; }
        .table th { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Detail Kontak</h4>
                        <p class="mb-0 text-muted">Batch #{{ $batch->id }} - Diupload pada {{ $batch->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    <a href="{{ route('home') }}" class="btn btn-secondary">Kembali ke Home</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark" style="position: sticky; top: 0;">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>No HP</th>
                                {{-- Loop untuk membuat header dari kolom JSON --}}
                                @foreach ($jsonColumns as $column)
                                    <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $index => $contact)
                                @php
                                    // Decode data JSON untuk baris ini
                                    $jsonData = json_decode($contact->data_json, true) ?? [];
                                @endphp
                                <tr>
                                    <td>{{ $contacts->firstItem() + $index }}</td>
                                    <td>{{ $contact->nama }}</td>
                                    <td>{{ $contact->no_hp }}</td>
                                    {{-- Loop untuk mengisi data dari kolom JSON --}}
                                    @foreach ($jsonColumns as $column)
                                        <td>{{ $jsonData[$column] ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($jsonColumns) }}" class="text-center">Tidak ada data kontak untuk batch ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Tampilkan link pagination --}}
                <div class="mt-3">
                    {{ $contacts->links() }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>

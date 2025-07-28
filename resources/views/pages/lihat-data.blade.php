@extends('layouts.app')    

@section('title', 'Data Overview - WABLAST')

@section('content')
<link rel="stylesheet" href="{{ asset('css/data_overview.css') }}">

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
                            <td colspan="{{ Auth::user()->role === 'admin' ? '4' : '3' }}" class="text-center py-4">
                                Tidak ada data kontak yang ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <x-pagination :paginator="$contacts" />
            </div>

        </div>
    </div>
</div>
@endsection
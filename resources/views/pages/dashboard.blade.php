@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/pagination.css') }}">

<div class="container py-4">
    <h5 class="mb-4">Dashboard Overview</h5>

    {{-- Kartu Statistik --}}
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Pesan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalMessages }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Terkirim</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $sentMessages }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Dibaca</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $readMessages }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Dibalas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $repliedMessages }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Pesan dengan Accordion --}}
    <div class="mt-4">
        <h5>Detail Pesan Terkirim</h5>
        <div class="accordion" id="sessionsAccordion">
            @forelse ($sessions as $session)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ $session->id }}">
                        <button 
                            class="accordion-button {{ $activeSession == $session->id ? '' : 'collapsed' }}" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#collapse-{{ $session->id }}" 
                            aria-expanded="{{ $activeSession == $session->id ? 'true' : 'false' }}" 
                            aria-controls="collapse-{{ $session->id }}"
                        >
                            <div class="d-flex justify-content-between w-100">
                                <span>
                                    Sesi {{ ($sessions->currentPage() - 1) * $sessions->perPage() + $loop->iteration }} - 
                                    Status: <strong class="ms-1">{{ strtoupper($session->status) }}</strong>
                                </span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse-{{ $session->id }}" class="accordion-collapse collapse {{ $activeSession == $session->id ? 'show' : '' }}" aria-labelledby="heading-{{ $session->id }}" data-bs-parent="#sessionsAccordion">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>No. HP</th>
                                            <th>Tanggal Kirim</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($messages[$session->id] as $message)
                                            <tr>
                                                <td>{{ $message->contact->nama ?? 'N/A' }}</td>
                                                <td>{{ $message->contact->no_hp ?? 'N/A' }}</td>
                                                <td>{{ $message->sent_at ? \Carbon\Carbon::parse($message->sent_at)->diffForHumans() : 'N/A' }}</td>
                                                <td>
                                                    @php
                                                        $badgeClass = 'bg-secondary'; // Default
                                                        if ($message->status == 'sent') $badgeClass = 'bg-info';
                                                        if ($message->status == 'read') $badgeClass = 'bg-success';
                                                        if ($message->status == 'replied') $badgeClass = 'bg-primary';
                                                        if ($message->status == 'failed') $badgeClass = 'bg-danger';
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ strtoupper($message->status) }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">Tidak ada pesan untuk sesi ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-center mt-3">
                                @if (isset($messages[$session->id]) && $messages[$session->id]->hasPages())
                                    {{ $messages[$session->id]->appends(['session' => $session->id])->links('components.pagination') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-light">Belum ada sesi pengiriman yang tercatat.</div>
            @endforelse
        </div>

        {{-- Paginasi untuk sesi --}}
        @if ($sessions->hasPages())
            <x-pagination :paginator="$sessions" />
        @endif
    </div>
</div>

{{-- PERBAIKAN: Tambahkan Bootstrap JS di sini agar dropdown (accordion) berfungsi --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection

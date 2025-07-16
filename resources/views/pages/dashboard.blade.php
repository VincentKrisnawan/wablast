@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Dashboard Overview</h1>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Messages</h5>
                    <p class="card-text">{{ $totalMessages }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sent Messages</h5>
                    <p class="card-text">{{ $sentMessages }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Read Messages</h5>
                    <p class="card-text">{{ $readMessages }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Replied Messages</h5>
                    <p class="card-text">{{ $repliedMessages }}</p>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4">
        <h2>Detail Pesan Terkirim</h2>
        @foreach ($sessions as $session)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <a href="{{ route('dashboard', ['session' => $session->id]) }}" class="btn btn-link" data-toggle="collapse" data-target="#session-{{ $session->id }}" aria-expanded="{{ $activeSession == $session->id ? 'true' : 'false' }}">
                            Sesi #{{ $session->id }} - {{ $session->status }}
                        </a>
                    </h5>
                </div>

                <div id="session-{{ $session->id }}" class="collapse {{ $activeSession == $session->id ? 'show' : '' }}">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>No. HP</th>
                                    <th>Tanggal Kirim</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($messages[$session->id] as $message)
                                    <tr>
                                        <td>{{ $message->contact->nama }}</td>
                                        <td>{{ $message->contact->no_hp }}</td>
                                        <td>{{ $message->sent_at ? \Carbon\Carbon::parse($message->sent_at)->diffForHumans() : 'N/A' }}</td>
                                        <td>{{ $message->status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $messages[$session->id]->links() }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

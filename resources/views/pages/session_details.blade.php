@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Session Details for Session #{{ $session->session_number }}</h1>

    <p><strong>Batch ID:</strong> {{ $session->batch_id }}</p>
    <p><strong>Status:</strong> {{ $session->status }}</p>
    <p><strong>Started At:</strong> {{ $session->started_at }}</p>
    <p><strong>Ended At:</strong> {{ $session->ended_at }}</p>

    <h2>Messages in this Session</h2>
    @if($session->messages->isEmpty())
        <p>No messages found for this session.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Contact Name</th>
                    <th>Contact Phone</th>
                    <th>Message Text</th>
                    <th>Status</th>
                    <th>Sent At</th>
                    <th>Read At</th>
                    <th>Replied At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($session->messages as $message)
                    <tr>
                        <td>{{ $message->contact->name ?? 'N/A' }}</td>
                        <td>{{ $message->contact->no_hp ?? 'N/A' }}</td>
                        <td>{{ $message->message_text }}</td>
                        <td>{{ $message->status }}</td>
                        <td>{{ $message->sent_at }}</td>
                        <td>{{ $message->read_at }}</td>
                        <td>{{ $message->replied_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

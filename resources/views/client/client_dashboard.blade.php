@extends('layouts.app')

@section('title', 'ProHand')

@section('content')
<main class="main">
    <div class="fixed-wrapper">
        <header class="header"> 
            <div class="header-left">
                <div class="logo">
                    <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="ProHand Logo" class="logo-img">
                    <div class="title">
                        <h1>Pro<span>Hand</span></h1>
                        <h2>Dashboard</h2>
                    </div>

                    <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                        <i class="fa-solid fa-bars"></i>
                    </label>
                </div>
            </div>
            <div class="header-right">
                <form class="search-form" method="GET" action="{{ route('client.dashboard') }}">
                    <div class="search-wrapper">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search">
                        @if (request()->has('search'))
                            <a href="{{ route('client.dashboard') }}" class="clear">Clear</a>
                        @endif
                    </div>
                    <button type="submit" class="btn-search">Search</button>
                </form>
            </div>
        </header>
    </div>

    <div class="dashboard">
        <div class="contents">
            <section class="totals">
                <h3>Errand Status Metrics</h3>
                <div class="total-cards-container">
                    <div class="total-card bg-pending">
                        <div class="card-header">
                            <span class="status-circle circle-pending"></span>
                            <h3>Pending</h3>
                        </div>
                        <p>{{ number_format($status_counts['pending'] ?? 0) }}</p>
                    </div>

                    <div class="total-card bg-accepted">
                        <div class="card-header">
                            <span class="status-circle circle-accepted"></span>
                            <h3>Pending Payment</h3>
                        </div>
                        <p>{{ number_format($status_counts['pending_payment'] ?? 0) }}</p>
                    </div>

                    <div class="total-card bg-process">
                        <div class="card-header">
                            <span class="status-circle circle-process"></span>
                            <h3>In Process</h3>
                        </div>
                        <p>{{ number_format($status_counts['in_process'] ?? 0) }}</p>
                    </div>

                    <div class="total-card bg-total">
                        <div class="card-header">
                            <span class="status-circle circle-total"></span>
                            <h3>Total Active Errands</h3>
                        </div>
                        <p>{{ number_format($total_errands ?? 0) }}</p>
                    </div>
                </div>
            </section>
        
            <div class="client-errands-table">
                <h4>My Errands</h4>
                <div class="table-scroll">
                    <table>
                        <tr>
                            <th>Errand ID</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Created At</th>
                            <th>Deadline</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        @forelse($errands as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->title }}</td>
                            <td>{{ $row->location }}</td>
                            <td>{{ $row->created_at->format('F j, Y g:i A') }}</td>
                            <td>{{ $row->deadline ? $row->deadline->format('F j, Y g:i A') : 'N/A' }}</td>
                            <td>₱{{ number_format($row->fee, 2) }}</td>
                            <td>
                                <span class="status-badge status-{{ $row->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $row->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="button-row">
                                    <a href="{{ route('errands.show', $row->id) }}" class="btn-view">View</a>
                                    @if ($row->status === 'pending')
                                        <form action="{{ route('errands.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Delete permanently?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    @elseif ($row->status === 'pending_payment')
                                        <form action="{{ route('errands.cancel', $row->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn-cancel">Cancel</button>
                                        </form>
                                    @elseif ($row->status === 'in_process' && !$row->cancel_requested_by_client)
                                        <form action="{{ route('errands.request-cancel', $row->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn-cancel">Cancel</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                            <tr><td colspan="8">No errands found.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
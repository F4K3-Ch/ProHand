@extends('layouts.app')

@section('title', 'Client Dashboard')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

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
                    <div class="search-wrapper">
                        <div class="search-bar">
                            <input
                                type="text"
                                id="errandSearch"
                                placeholder="Search errands..."
                                autocomplete="off"
                            >
                            <button type="button" class="btn-clear" id="clearSearchButton">Clear</button>
                        </div>
                        <button type="button" class="btn-search" id="searchButton">Search</button>
                    </div>
                </div>
            </header>
        </div>
        
        <div class="dashboard">
            <div
                class="error-success-message"
                id="flashMessageContainer"
                data-error="{{ session('error') }}"
                data-success="{{ session('success') }}"
                style="display: none;"
            >
                <p id="flashMessageText"></p>
            </div>

            <div class="contents">
                <section class="totals">
                    <h3>Errand Status Metrics</h3>
                    <div class="total-cards-container">
                        <div class="total-card bg-pending" id="metric-card-pending" style="display: none;">
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending</h3>
                            </div>
                            <p id="metric-pending"></p>
                        </div>

                        <div class="total-card bg-accepted" id="metric-card-pending-payment" style="display: none;">
                            <div class="card-header">
                                <span class="status-circle circle-accepted"></span>
                                <h3>Pending Payment</h3>
                            </div>
                            <p id="metric-pending-payment"></p>
                        </div>

                        <div class="total-card bg-process" id="metric-card-in-process" style="display: none;">
                            <div class="card-header">
                                <span class="status-circle circle-process"></span>
                                <h3>In Process</h3>
                            </div>
                            <p id="metric-in-process"></p>
                        </div>

                        <div class="total-card bg-completed" id="metric-card-pending-completion" style="display: none;">
                            <div class="card-header">
                                <span class="status-circle circle-complete"></span>
                                <h3>Pending Completion</h3>
                            </div>
                            <p id="metric-pending-completion"></p>
                        </div>

                        <div class="total-card bg-failed" id="metric-card-pending-failure" style="display: none;">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Pending Failure</h3>
                            </div>
                            <p id="metric-pending-failure"></p>
                        </div>

                        <div class="total-card bg-failed" id="metric-card-dispute" style="display: none;">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Dispute</h3>
                            </div>
                            <p id="metric-dispute"></p>
                        </div>

                        <div class="total-card bg-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Active Errands</h3>
                            </div>
                            <p id="metric-total"><span class="circle-spinner" style="width: 15px; height: 15px; border-width: 2px;"></span></p>
                        </div>
                    </div>
                </section>

                <div class="client-errands-table">
                    <div class="table-header-controls">
                        <h4>My Errands</h4>

                        <div class="rows-per-page">
                            <label for="perPageSelect">
                                <h4>Show:</h4>
                            </label>

                            <select id="perPageSelect" class="form-control">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Errand ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Created At</th>
                                    <th>Deadline</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody id="errands-table-body">
                                <tr>
                                    <td class="no-found" colspan="8">
                                        <span class="circle-spinner" style="width: 20px; height: 20px; border-width: 2px;"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pagination-container" class="pagination-container"></div>
                </div>
            </div>
        </div>
    </main>
@endsection    

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async function () {

        const metricSpinner = document.getElementById('metric-spinner');

        try {
            const response = await fetch('/api/client/dashboard/active-metrics', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-API-Key': "{{ config('services.my_api.api_key') }}",
                },
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Failed to load dashboard data.'
                );
            }

            const metrics = result.metrics;

            document.getElementById('metric-pending').textContent =
                metrics.pending;

            document.getElementById('metric-pending-payment').textContent =
                metrics.pending_payment;

            document.getElementById('metric-in-process').textContent =
                metrics.in_process;

            document.getElementById('metric-pending-completion').textContent =
                metrics.pending_completion;

            document.getElementById('metric-pending-failure').textContent =
                metrics.pending_failure;

            document.getElementById('metric-dispute').textContent =
                metrics.dispute;

            document.getElementById('metric-total').textContent =
                metrics.total;

            showMetricCard('metric-card-pending', metrics.pending);
            showMetricCard('metric-card-pending-payment', metrics.pending_payment);
            showMetricCard('metric-card-in-process', metrics.in_process);
            showMetricCard('metric-card-pending-completion', metrics.pending_completion);
            showMetricCard('metric-card-pending-failure', metrics.pending_failure);
            showMetricCard('metric-card-dispute', metrics.dispute);

        } catch (error) {
            console.error('Dashboard error:', error);

            document.getElementById('metric-total').textContent = '—';

        } finally {
            if (metricSpinner) {
                metricSpinner.style.display = 'none';
            }
        }
    });

    function showMetricCard(cardId, value) {
        const card = document.getElementById(cardId);

        if (card && value > 0) {
            card.style.display = 'flex';
        }
    }
</script>
@endpush
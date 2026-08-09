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
                        <input type="text" name="search" value="" placeholder="Search">
                    </div>
                    <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
            </header>
        </div>
        
        <div class="dashboard">
            <div class="error-success-message" id="flashMessageContainer" style="display: none;">
                <p class="success" id="flashMessageText"></p>
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
                            <p id="metric-total">Loading...</p>
                        </div>
                    </div>
                </section>

                <div class="client-errands-table">
                    <div class="table-header-controls">
                        <h4>My Errands</h4>
                        <div class="rows-per-page">
                            <label for="perPageSelect"><h4>Show: </h4></label>
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
                                <tr><td class="no-found" colspan="8">Loading errands...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pagination-container" class="pagination-container">

                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const message = sessionStorage.getItem('success_message');
            const container = document.getElementById('flashMessageContainer');
            const text = document.getElementById('flashMessageText');

            if (message && container && text) {
                text.textContent = message;
                container.style.display = 'block';
                sessionStorage.removeItem('success_message');

                setTimeout(() => {
                    container.style.display = 'none';
                }, 3000);
            }

            const token = localStorage.getItem('token');

            if (!token) {
                return;
            }

            fetch('/api/client/metrics', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to retrieve errand metrics.');
                }

                return response.json();
            })
            .then(metrics => {
                const metricCards = {
                    pending: 'metric-card-pending',
                    pending_payment: 'metric-card-pending-payment',
                    in_process: 'metric-card-in-process',
                    pending_completion: 'metric-card-pending-completion',
                    pending_failure: 'metric-card-pending-failure',
                    dispute: 'metric-card-dispute'
                };

                Object.entries(metricCards).forEach(([key, cardId]) => {
                    const card = document.getElementById(cardId);
                    const value = document.getElementById('metric-' + key);

                    if (!card || !value) {
                        return;
                    }

                    const count = Number(metrics[key] || 0);

                    if (count > 0) {
                        value.textContent = count;
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });

                const totalValue = document.getElementById('metric-total');

                if (totalValue) {
                    totalValue.textContent = Number(metrics.total || 0);
                }
            })
            .catch(error => {
                console.error('Errand metrics error:', error);
            });
        });
    </script>
@endpush
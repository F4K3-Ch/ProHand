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
                        <img
                            src="{{ asset('img/ProHand(Adapt).png') }}"
                            alt="ProHand Logo"
                            class="logo-img"
                        >

                        <div class="title">
                            <h1>Pro<span>Hand</span></h1>
                            <h2>Dashboard</h2>
                        </div>

                        <label
                            for="sidebar-toggle-check"
                            class="sidebar-toggle-btn"
                            aria-label="Toggle Navigation Menu"
                        >
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

                            <button
                                type="button"
                                class="btn-clear"
                                id="clearSearchButton"
                            >
                                Clear
                            </button>
                        </div>

                        <button
                            type="button"
                            class="btn-search"
                            id="searchButton"
                        >
                            Search
                        </button>
                    </div>
                </div>
            </header>
        </div>

        <div class="dashboard">
            <div
                class="error-success-message"
                id="flashMessageContainer"
                style="display: none;"
            >
                <p
                    class="success"
                    id="flashMessageText"
                ></p>
            </div>

            <div class="contents">

                <section class="totals">
                    <h3>Errand Status Metrics</h3>

                    <div class="total-cards-container">

                        <div
                            class="total-card bg-pending"
                            id="metric-card-pending"
                            style="display: none;"
                        >
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending</h3>
                            </div>

                            <p id="metric-pending">0</p>
                        </div>

                        <div
                            class="total-card bg-accepted"
                            id="metric-card-pending-payment"
                            style="display: none;"
                        >
                            <div class="card-header">
                                <span class="status-circle circle-accepted"></span>
                                <h3>Pending Payment</h3>
                            </div>

                            <p id="metric-pending-payment">0</p>
                        </div>

                        <div
                            class="total-card bg-process"
                            id="metric-card-in-process"
                            style="display: none;"
                        >
                            <div class="card-header">
                                <span class="status-circle circle-process"></span>
                                <h3>In Process</h3>
                            </div>

                            <p id="metric-in-process">0</p>
                        </div>

                        <div
                            class="total-card bg-completed"
                            id="metric-card-pending-completion"
                            style="display: none;"
                        >
                            <div class="card-header">
                                <span class="status-circle circle-complete"></span>
                                <h3>Pending Completion</h3>
                            </div>

                            <p id="metric-pending-completion">0</p>
                        </div>

                        <div
                            class="total-card bg-failed"
                            id="metric-card-pending-failure"
                            style="display: none;"
                        >
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Pending Failure</h3>
                            </div>

                            <p id="metric-pending-failure">0</p>
                        </div>

                        <div
                            class="total-card bg-failed"
                            id="metric-card-dispute"
                            style="display: none;"
                        >
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Dispute</h3>
                            </div>

                            <p id="metric-dispute">0</p>
                        </div>

                        <div class="total-card bg-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Active Errands</h3>
                            </div>

                            <p id="metric-total">
                                <span
                                    class="circle-spinner"
                                    style="width: 15px; height: 15px; border-width: 2px;"
                                ></span>
                            </p>
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

                            <select
                                id="perPageSelect"
                                class="form-control"
                            >
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
                                    <td
                                        class="no-found"
                                        colspan="8"
                                    >
                                        <span
                                            class="circle-spinner"
                                            style="width: 20px; height: 20px; border-width: 2px;"
                                        ></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        id="pagination-container"
                        class="pagination-container"
                    ></div>

                </div>

            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const tableBody = document.getElementById('errands-table-body');
            const apiKey = document.querySelector('meta[name="api-key"]').content;
            const Origin = document.querySelector('meta[name="api-origin"]').content;

            try {
                const response = await fetch('/api/errands/client', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'x-api-key': apiKey,
                        'Origin': Origin
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message || 'Failed to fetch errands.'
                    );
                }

                const errands = data.errands;

                tableBody.innerHTML = '';

                if (!errands || errands.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td class="no-found" colspan="8">
                                No errands found.
                            </td>
                        </tr>
                    `;

                    document.getElementById('metric-total').textContent = '0';

                    return;
                }

                document.getElementById('metric-total').textContent =
                    errands.length;

                errands.forEach(function (errand) {
                    const row = document.createElement('tr');

                    row.innerHTML = `
                        <td>${escapeHtml(errand.id)}</td>
                        <td>${escapeHtml(errand.title)}</td>
                        <td>${escapeHtml(errand.category)}</td>
                        <td>${formatDate(errand.created_at)}</td>
                        <td>${formatDate(errand.deadline)}</td>
                        <td>₱${Number(errand.fee || 0).toFixed(2)}</td>
                        <td>${escapeHtml(errand.status)}</td>
                        <td>
                            <button
                                type="button"
                                class="btn-view-errand"
                                data-id="${errand.id}"
                            >
                                View
                            </button>
                        </td>
                    `;

                    tableBody.appendChild(row);
                });

                updateStatusMetrics(errands);

            } catch (error) {
                console.error('Errand fetch error:', error);

                tableBody.innerHTML = `
                    <tr>
                        <td class="no-found" colspan="8">
                            ${escapeHtml(
                                error.message || 'Unable to load errands.'
                            )}
                        </td>
                    </tr>
                `;

                document.getElementById('metric-total').textContent = '0';
            }
        });

        function formatDate(date) {
            if (!date) {
                return 'N/A';
            }

            return new Date(date).toLocaleString('en-US', {
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: 'long',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateStatusMetrics(errands) {
            const statusCounts = {
                pending: 0,
                pending_payment: 0,
                in_process: 0,
                pending_completion: 0,
                pending_failure: 0,
                dispute: 0
            };

            errands.forEach(function (errand) {
                const status = String(
                    errand.status || ''
                ).toLowerCase();

                if (
                    Object.prototype.hasOwnProperty.call(
                        statusCounts,
                        status
                    )
                ) {
                    statusCounts[status]++;
                }
            });

            updateMetric(
                'metric-card-pending',
                'metric-pending',
                statusCounts.pending
            );

            updateMetric(
                'metric-card-pending-payment',
                'metric-pending-payment',
                statusCounts.pending_payment
            );

            updateMetric(
                'metric-card-in-process',
                'metric-in-process',
                statusCounts.in_process
            );

            updateMetric(
                'metric-card-pending-completion',
                'metric-pending-completion',
                statusCounts.pending_completion
            );

            updateMetric(
                'metric-card-pending-failure',
                'metric-pending-failure',
                statusCounts.pending_failure
            );

            updateMetric(
                'metric-card-dispute',
                'metric-dispute',
                statusCounts.dispute
            );
        }

        function updateMetric(cardId, metricId, value) {
            const card = document.getElementById(cardId);
            const metric = document.getElementById(metricId);

            if (value > 0) {
                card.style.display = 'block';
                metric.textContent = value;
            } else {
                card.style.display = 'none';
                metric.textContent = '0';
            }
        }
    </script>
@endpush
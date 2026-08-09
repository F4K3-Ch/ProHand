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
        document.addEventListener('DOMContentLoaded', function () {

            const tableBody = document.getElementById('errands-table-body');
            const paginationContainer = document.getElementById('pagination-container');
            const perPageSelect = document.getElementById('perPageSelect');
            const searchInput = document.getElementById('errandSearch');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');


            // API helper
            async function apiFetch(url) {
                const response = await fetch(url, {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Request failed: ${response.status}`);
                }

                return response.json();
            }

            // Flash message
            const message = sessionStorage.getItem('success_message');
            const messageContainer = document.getElementById('flashMessageContainer');
            const messageText = document.getElementById('flashMessageText');

            if (message && messageContainer && messageText) {
                messageText.textContent = message;
                messageContainer.style.display = 'block';

                sessionStorage.removeItem('success_message');

                setTimeout(() => {
                    messageContainer.style.display = 'none';
                }, 3000);
            }

            // Metrics
            async function fetchMetrics() {
                try {
                    const metrics = await apiFetch('/api/client/activeMetrics');

                    const cards = {
                        pending: 'metric-card-pending',
                        pending_payment: 'metric-card-pending-payment',
                        in_process: 'metric-card-in-process',
                        pending_completion: 'metric-card-pending-completion',
                        pending_failure: 'metric-card-pending-failure',
                        dispute: 'metric-card-dispute'
                    };

                    Object.entries(cards).forEach(([key, cardId]) => {
                        const card = document.getElementById(cardId);
                        const value = document.getElementById(`metric-${key}`);

                        if (!card || !value) {
                            return;
                        }

                        const count = Number(metrics[key] || 0);

                        value.textContent = count;
                        card.style.display = count > 0 ? 'flex' : 'none';
                    });

                    const totalValue = document.getElementById('metric-total');

                    if (totalValue) {
                        totalValue.textContent = Number(metrics.total || 0);
                    }

                } catch (error) {
                    console.error('Metrics error:', error);
                }
            }

            // Errands
            async function fetchErrands(page = 1) {
                const perPage = perPageSelect?.value || 10;
                const search = searchInput?.value.trim() || '';

                tableBody.innerHTML = `
                    <tr>
                        <td class="no-found" colspan="8">
                            <span class="circle-spinner"
                                style="width: 20px; height: 20px; border-width: 2px;">
                            </span>
                        </td>
                    </tr>
                `;

                const params = new URLSearchParams({
                    page,
                    per_page: perPage
                });

                if (search) {
                    params.append('search', search);
                }

                try {
                    const data = await apiFetch(
                        `/api/client/activeErrands?${params.toString()}`
                    );

                    renderErrands(data.data);
                    renderPagination(data);

                } catch (error) {
                    console.error('Errands error:', error);

                    tableBody.innerHTML = `
                        <tr>
                            <td class="no-found" colspan="8">
                                Failed to load errands.
                            </td>
                        </tr>
                    `;

                    paginationContainer.innerHTML = '';
                }
            }

            // Render errands
            function renderErrands(errands) {
                if (!errands?.length) {
                    tableBody.innerHTML = `
                        <tr>
                            <td class="no-found" colspan="8">
                                No errands found.
                            </td>
                        </tr>
                    `;
                    return;
                }

                tableBody.innerHTML = errands.map(errand => `
                    <tr>
                        <td>${escapeHtml(errand.id)}</td>
                        <td>${escapeHtml(errand.title)}</td>
                        <td>${escapeHtml(formatCategory(errand.category))}</td>
                        <td>${escapeHtml(formatDate(errand.created_at))}</td>
                        <td>${escapeHtml(formatDate(errand.deadline))}</td>
                        <td>₱${Number(errand.fee).toFixed(2)}</td>
                        <td>
                            <span class="status-badge status-${escapeHtml(errand.status)}">
                                ${escapeHtml(formatStatus(errand.status))}
                            </span>
                        </td>
                        <td>
                            <div class="button-row">
                                <button type="button" class="btn-view">
                                    View
                                </button>

                                <button type="button" class="btn-delete">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }

            // Pagination
            function renderPagination(data) {
                paginationContainer.innerHTML = '';

                if (data.last_page <= 1) {
                    return;
                }

                const previous = document.createElement('button');

                previous.textContent = 'Previous';
                previous.disabled = data.current_page === 1;

                previous.onclick = () => {
                    fetchErrands(data.current_page - 1);
                };

                paginationContainer.appendChild(previous);

                for (let page = 1; page <= data.last_page; page++) {
                    const button = document.createElement('button');

                    button.textContent = page;

                    if (page === data.current_page) {
                        button.classList.add('active');
                    }

                    button.onclick = () => {
                        fetchErrands(page);
                    };

                    paginationContainer.appendChild(button);
                }

                const next = document.createElement('button');

                next.textContent = 'Next';
                next.disabled = data.current_page === data.last_page;

                next.onclick = () => {
                    fetchErrands(data.current_page + 1);
                };

                paginationContainer.appendChild(next);
            }

            // Formatting
            function formatText(value) {
                return String(value || '')
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, letter => letter.toUpperCase());
            }

            function formatCategory(value) {
                return formatText(value);
            }

            function formatStatus(value) {
                return formatText(value);
            }

            function formatDate(date) {
                if (!date) {
                    return '-';
                }

                return new Date(date).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            // Search
            function updateClearButton() {
                if (!clearSearchButton) {
                    return;
                }

                clearSearchButton.style.display =
                    searchInput?.value.trim()
                        ? 'inline-block'
                        : 'none';
            }

            searchButton?.addEventListener('click', () => {
                fetchErrands(1);
            });

            searchInput?.addEventListener('input', updateClearButton);

            searchInput?.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    fetchErrands(1);
                }
            });

            clearSearchButton?.addEventListener('click', () => {
                searchInput.value = '';
                updateClearButton();
                fetchErrands(1);
            });

            perPageSelect?.addEventListener('change', () => {
                fetchErrands(1);
            });

            // Initial load
            updateClearButton();
            fetchMetrics();
            fetchErrands();

        });
    </script>
@endpush
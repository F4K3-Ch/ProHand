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
                            <input type="text" id="errandSearch" placeholder="Search errands..." autocomplete="off">
                            <button type="button" class="btn-clear" id="clearSearchButton">Clear</button>
                        </div>

                        <button type="button" class="btn-search" id="searchButton">Search</button>
                    </div>
                </div>
            </header>
        </div>

        <div class="dashboard">
            <div class="error-success-message">
                <p class="success" id="flashMessageText" style="display: none;"></p>
                <p class="error" id="errorMessageText" style="display: none;"></p>
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

                            <p id="metric-total">
                                <span class="circle-spinner" style="width: 15px; height: 15px; border-width: 2px;"></span>
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
                                <tr id="table-loading-row">
                                    <td class="no-found" colspan="8">
                                        <span class="circle-spinner" style="width: 20px; height: 20px; border-width: 2px;"></span>
                                    </td>
                                </tr>

                                <tr id="table-empty-row" style="display: none;">
                                    <td class="no-found" colspan="8">
                                        No errands found.
                                    </td>
                                </tr>

                                <tr id="table-error-row" style="display: none;">
                                    <td class="no-found" colspan="8">
                                        Failed to load errands.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pagination-container" class="pagination-container" style="display: none;">
                        <button type="button" id="pagination-previous">Previous</button>
                        <div id="pagination-pages"></div>
                        <button type="button" id="pagination-next">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <template id="errand-row-template">
        <tr class="errand-row">
            <td class="errand-id"></td>
            <td class="errand-title"></td>
            <td class="errand-category"></td>
            <td class="errand-created-at"></td>
            <td class="errand-deadline"></td>
            <td class="errand-fee"></td>

            <td>
                <span class="status-badge errand-status-badge"></span>
            </td>

            <td>
                <div class="button-row">
                    <a href="#" class="btn-view">View</a>
                    <button type="button" class="btn-delete">Delete</button>
                </div>
            </td>
        </tr>
    </template>

    <template id="pagination-page-template">
        <button type="button" class="pagination-page"></button>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.getElementById('errands-table-body');
            const paginationContainer = document.getElementById('pagination-container');
            const paginationPages = document.getElementById('pagination-pages');
            const paginationPrevious = document.getElementById('pagination-previous');
            const paginationNext = document.getElementById('pagination-next');

            const loadingRow = document.getElementById('table-loading-row');
            const emptyRow = document.getElementById('table-empty-row');
            const errorRow = document.getElementById('table-error-row');

            const perPageSelect = document.getElementById('perPageSelect');
            const searchInput = document.getElementById('errandSearch');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');

            const errandRowTemplate = document.getElementById('errand-row-template');
            const paginationPageTemplate = document.getElementById('pagination-page-template');

            const flashMessageText = document.getElementById('flashMessageText');
            const errorMessageText = document.getElementById('errorMessageText');

            async function apiFetch(url) {
                const response = await fetch(url, {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Request failed: ' + response.status);
                }

                return response.json();
            }

            function showSuccess(message) {
                errorMessageText.textContent = '';
                errorMessageText.style.display = 'none';

                flashMessageText.textContent = message;
                flashMessageText.style.display = 'block';

                setTimeout(function() {
                    flashMessageText.textContent = '';
                    flashMessageText.style.display = 'none';
                }, 3000);
            }

            function showError(message) {
                flashMessageText.textContent = '';
                flashMessageText.style.display = 'none';

                errorMessageText.textContent = message;
                errorMessageText.style.display = 'block';
            }

            async function deleteErrand(errandId) {
                const confirmed = confirm(
                    'Are you sure you want to delete this errand?'
                );

                if (!confirmed) {
                    return;
                }

                try {
                    const response = await fetch(
                        '/api/client/errands/delete/' + errandId,
                        {
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json'
                            }
                        }
                    );

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    showSuccess(data.message);

                    fetchMetrics();
                    fetchErrands();

                } catch (error) {
                    console.error('Failed to delete errand:', error);

                    showError(
                        error.error ||
                        error.message ||
                        'Failed to delete errand.'
                    );
                }
            }

            async function fetchMetrics() {
                try {
                    const metrics = await apiFetch('/api/client/active-metrics');

                    const cards = {
                        pending: 'metric-card-pending',
                        pending_payment: 'metric-card-pending-payment',
                        in_process: 'metric-card-in-process',
                        pending_completion: 'metric-card-pending-completion',
                        pending_failure: 'metric-card-pending-failure',
                        dispute: 'metric-card-dispute'
                    };

                    Object.entries(cards).forEach(function([key, cardId]) {
                        const card = document.getElementById(cardId);
                        const value = document.getElementById('metric-' + key);

                        if (!card || !value) return;

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

            function hideTableStates() {
                loadingRow.style.display = 'none';
                emptyRow.style.display = 'none';
                errorRow.style.display = 'none';
            }

            function showTableLoading() {
                hideTableStates();
                loadingRow.style.display = 'table-row';
            }

            function showTableEmpty() {
                hideTableStates();
                emptyRow.style.display = 'table-row';
            }

            function showTableError() {
                hideTableStates();
                errorRow.style.display = 'table-row';
            }

            function removeErrandRows() {
                tableBody.querySelectorAll('.errand-row').forEach(function(row) {
                    row.remove();
                });
            }

            async function fetchErrands(page) {
                page = page || 1;

                const perPage = perPageSelect?.value || 10;
                const search = searchInput?.value.trim() || '';

                showTableLoading();

                const params = new URLSearchParams({
                    page: page,
                    per_page: perPage
                });

                if (search) {
                    params.append('search', search);
                }

                try {
                    const data = await apiFetch(
                        '/api/client/active-errands?' + params.toString()
                    );

                    renderErrands(data.data);
                    renderPagination(data);
                } catch (error) {
                    console.error('Errands error:', error);

                    removeErrandRows();
                    showTableError();

                    paginationContainer.style.display = 'none';
                }
            }

            function renderErrands(errands) {
                removeErrandRows();

                if (!errands || !errands.length) {
                    showTableEmpty();
                    paginationContainer.style.display = 'none';
                    return;
                }

                hideTableStates();

                errands.forEach(function(errand) {
                    const row = errandRowTemplate.content.cloneNode(true);

                    row.querySelector('.errand-id').textContent = errand.id;

                    row.querySelector('.errand-title').textContent =
                        errand.title;

                    row.querySelector('.errand-category').textContent =
                        formatCategory(errand.category);

                    row.querySelector('.errand-created-at').textContent =
                        formatDate(errand.created_at);

                    row.querySelector('.errand-deadline').textContent =
                        formatDate(errand.deadline);

                    row.querySelector('.errand-fee').textContent =
                        '₱' + Number(errand.fee).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                    const statusBadge =
                        row.querySelector('.errand-status-badge');

                    statusBadge.textContent =
                        formatStatus(errand.status);

                    statusBadge.classList.add(
                        'status-' + errand.status
                    );

                    const viewButton =
                        row.querySelector('.btn-view');

                    viewButton.href =
                        '/client/view-errand/' + errand.id;

                    const deleteButton =
                        row.querySelector('.btn-delete');

                    deleteButton.addEventListener('click', function() {
                        deleteErrand(errand.id);
                    });

                    tableBody.appendChild(row);
                });
            }

            function renderPagination(data) {
                paginationPages.innerHTML = '';

                if (data.last_page <= 1) {
                    paginationContainer.style.display = 'none';
                    return;
                }

                paginationContainer.style.display = 'flex';

                paginationPrevious.disabled =
                    data.current_page === 1;

                paginationNext.disabled =
                    data.current_page === data.last_page;

                paginationPrevious.onclick = function() {
                    fetchErrands(data.current_page - 1);
                };

                paginationNext.onclick = function() {
                    fetchErrands(data.current_page + 1);
                };

                for (let page = 1; page <= data.last_page; page++) {
                    const button =
                        paginationPageTemplate.content.cloneNode(true);

                    const pageButton =
                        button.querySelector('.pagination-page');

                    pageButton.textContent = page;

                    if (page === data.current_page) {
                        pageButton.classList.add('active');
                    }

                    pageButton.onclick = function() {
                        fetchErrands(page);
                    };

                    paginationPages.appendChild(button);
                }
            }

            function formatText(value) {
                return String(value || '')
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, function(letter) {
                        return letter.toUpperCase();
                    });
            }

            function formatCategory(value) {
                return formatText(value);
            }

            function formatStatus(value) {
                return formatText(value);
            }

            function formatDate(date) {
                if (!date) return '-';

                return new Date(date).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }

            function updateClearButton() {
                if (!clearSearchButton) return;

                clearSearchButton.style.display =
                    searchInput?.value.trim()
                        ? 'inline-block'
                        : 'none';
            }

            searchButton?.addEventListener('click', function() {
                fetchErrands(1);
            });

            searchInput?.addEventListener('input', updateClearButton);

            searchInput?.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    fetchErrands(1);
                }
            });

            clearSearchButton?.addEventListener('click', function() {
                searchInput.value = '';
                updateClearButton();
                fetchErrands(1);
            });

            perPageSelect?.addEventListener('change', function() {
                fetchErrands(1);
            });

            updateClearButton();
            fetchMetrics();
            fetchErrands();
        });
    </script>
@endpush
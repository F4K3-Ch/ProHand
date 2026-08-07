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
            <div class="error-success-message" id="flashMessageContainer" style="display: none;">
                <p class="success" id="flashMessageText"></p>
            </div>

            <div class="contents">
                <section class="totals">
                    <h3>Errand Status Metrics</h3>
                    <div class="total-cards-container">
                        <div class="total-card bg-pending">
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending</h3>
                            </div>
                            <p id="metric-pending">0</p>
                        </div>
                        <div class="total-card bg-accepted">
                            <div class="card-header">
                                <span class="status-circle circle-accepted"></span>
                                <h3>Pending Payment</h3>
                            </div>
                            <p id="metric-pending-payment">0</p>
                        </div>
                        <div class="total-card bg-process">
                            <div class="card-header">
                                <span class="status-circle circle-process"></span>
                                <h3>In Process</h3>
                            </div>
                            <p id="metric-in-process">0</p>
                        </div>
                        <div class="total-card bg-completed">
                            <div class="card-header">
                                <span class="status-circle circle-complete"></span>
                                <h3>Pending Completion</h3>
                            </div>
                            <p id="metric-pending-completion">0</p>
                        </div>
                        <div class="total-card bg-failed">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Pending Failured</h3>
                            </div>
                            <p id="metric-pending-failure">0</p>
                        </div>
                        <div class="total-card bg-failed">
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
                            <p id="metric-total">0</p>
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
                        <!-- JS will inject buttons here -->
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        let currentPage = 1;

        document.addEventListener("DOMContentLoaded", function () {
            // 1. Flash messages
            const successMsg = sessionStorage.getItem('success_message');
            if (successMsg) {
                const container = document.getElementById('flashMessageContainer');
                const textEl = document.getElementById('flashMessageText');
                
                textEl.textContent = successMsg;
                container.style.display = 'block';
                sessionStorage.removeItem('success_message');
            }

            // 2. Initial fetch
            fetchDashboardData(1);

            // 3. Listen for changes on "Rows per page" selector
            const perPageSelect = document.getElementById('perPageSelect');
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function() {
                    currentPage = 1;
                    fetchDashboardData(currentPage);
                });
            }
        });

        // The unified function to fetch metrics AND table rows
        function fetchDashboardData(page = 1) {
            currentPage = page;
            const token = localStorage.getItem('token');
            const perPageSelect = document.getElementById('perPageSelect');
            const perPage = perPageSelect ? perPageSelect.value : 10;
            
            // Note: If you add a search input later, grab its value and append &search=... to the URL
            let url = `/api/client/dashboard-data?page=${page}&per_page=${perPage}`;

            fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                // --- A. UPDATE METRICS ---
                document.getElementById('metric-pending').textContent = data.status_counts.pending || 0;
                document.getElementById('metric-pending-payment').textContent = data.status_counts.pending_payment || 0;
                document.getElementById('metric-in-process').textContent = data.status_counts.in_process || 0;
                document.getElementById('metric-pending-completion').textContent = data.status_counts.pending_completion || 0;
                document.getElementById('metric-pending-failure').textContent = data.status_counts.pending_failure || 0;
                document.getElementById('metric-dispute').textContent = data.status_counts.dispute || 0;
                document.getElementById('metric-total').textContent = data.total_errands || 0;

                // --- B. POPULATE TABLE ROWS ---
                const tbody = document.getElementById('errands-table-body');
                tbody.innerHTML = '';

                // Because we used ->paginate(), the records are inside data.errands.data
                const errandsList = data.errands.data || [];

                if (errandsList.length > 0) {
                    errandsList.forEach(row => {
                        const createdAt = new Date(row.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
                        const deadline = new Date(row.deadline).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
                        const feeFormatted = '₱' + parseFloat(row.fee).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                        // View-only dummy buttons
                        let actionButtons = `
                            <div class="button-row">
                                <a href="view_errand_info.php?errand_id=${row.id}&from=dashboard" class="btn-view" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        `;

                        if (row.status === 'pending') {
                            actionButtons += `<button type="button" class="btn-delete" onclick="alert('Placeholder: Delete Errand ID ${row.id}')">Delete</button>`;
                        } else if (row.status === 'pending_payment') {
                            actionButtons += `<button type="button" class="btn-cancel" onclick="alert('Placeholder: Cancel Errand ID ${row.id}')">Cancel</button>`;
                        } else if (row.status === 'in_process') {
                            if (Number(row.cancel_requested_by_client) === 0) {
                                actionButtons += `<button type="button" class="btn-cancel" onclick="alert('Placeholder: Request Cancel for ID ${row.id}')">Cancel</button>`;
                            } else {
                                actionButtons += `<button type="button" disabled class="btn-cancel-disabled">Cancel</button>`;
                            }
                        }

                        actionButtons += `</div>`;

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row.id}</td>
                            <td>${escapeHtml(row.title)}</td>
                            <td>${escapeHtml(row.category)}</td>
                            <td>${createdAt}</td>
                            <td>${deadline}</td>
                            <td>${feeFormatted}</td>
                            <td><span class="status-badge status-${row.status}">${data.status_labels[row.status] || row.status}</span></td>
                            <td>${actionButtons}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td class="no-found" colspan="8">No errands found.</td></tr>`;
                }

                // --- C. RENDER PAGINATION ---
                renderPagination(data.errands);
            })
            .catch(error => {
                console.error('Error fetching dashboard data:', error);
            });
        }

        // Handles rendering Page 1, Page 2, Next, Prev dynamically
        function renderPagination(paginator) {
            const container = document.getElementById('pagination-container');
            if (!container) return;
            container.innerHTML = '';

            if (paginator.last_page <= 1) return;

            // Previous Button
            if (paginator.current_page > 1) {
                const prevBtn = document.createElement('button');
                prevBtn.textContent = 'Prev';
                prevBtn.className = 'btn-pagination';
                prevBtn.onclick = () => fetchDashboardData(paginator.current_page - 1);
                container.appendChild(prevBtn);
            }

            // Page Number Buttons
            for (let i = 1; i <= paginator.last_page; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = 'btn-pagination';
                if (i === paginator.current_page) {
                    pageBtn.style.fontWeight = 'bold';
                    pageBtn.style.backgroundColor = '#ccc';
                }
                pageBtn.onclick = () => fetchDashboardData(i);
                container.appendChild(pageBtn);
            }

            // Next Button
            if (paginator.current_page < paginator.last_page) {
                const nextBtn = document.createElement('button');
                nextBtn.textContent = 'Next';
                nextBtn.className = 'btn-pagination';
                nextBtn.onclick = () => fetchDashboardData(paginator.current_page + 1);
                container.appendChild(nextBtn);
            }
        }

        function escapeHtml(str) {
            return str ? str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;") : '';
        }
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('sidebar')
    @include('layouts.sidebar.admin')
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
                            <input type="text" id="errandSearch" placeholder="Search errands...">
                            <button type="button" class="btn-clear" id="clearSearchButton">Clear</button>
                        </div>
                        <button type="button" class="btn-search" id="searchButton">Search</button>
                    </div>
                </div>
            </header>
        </div>

        <div class="dashboard">
            <div class="error-success-message">
                @if(session('success'))
                    <p class="success" id="sessionSuccessMessage">{{ session('success') }}</p>
                @endif

                @if(session('error'))
                    <p class="error" id="sessionErrorMessage">{{ session('error') }}</p>
                @endif

                <p class="success" id="successMessageText" style="display: none;"></p>
                <p class="error" id="errorMessageText" style="display: none;"></p>
            </div>

            <div class="contents">
                <section class="totals">
                    <h3>Errand Status Metrics</h3>
                    <div class="total-cards-container">
                        <div class="total-card bg-pending" id="metric-card-pending" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending</h3>
                            </div>
                            <p id="metric-pending"></p>
                        </div>

                        <div class="total-card bg-accepted" id="metric-card-pending-payment" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-accepted"></span>
                                <h3>Pending Payment</h3>
                            </div>
                            <p id="metric-pending-payment"></p>
                        </div>

                        <div class="total-card bg-process" id="metric-card-in-process" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-process"></span>
                                <h3>In Process</h3>
                            </div>
                            <p id="metric-in-process"></p>
                        </div>

                        <div class="total-card bg-completed" id="metric-card-pending-completion" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-complete"></span>
                                <h3>Pending Completion</h3>
                            </div>
                            <p id="metric-pending-completion"></p>
                        </div>

                        <div class="total-card bg-failed" id="metric-card-pending-failure" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Pending Failure</h3>
                            </div>
                            <p id="metric-pending-failure"></p>
                        </div>

                        <div class="total-card bg-failed" id="metric-card-dispute" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Dispute</h3>
                            </div>
                            <p id="metric-dispute"></p>
                        </div>

                        <div class="total-card bg-total" id="metric-card-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Active Errands</h3>
                            </div>
                            <p id="metric-total">
                                <span id="metric-spinner" class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                            </p>
                        </div>
                    </div>
                </section>

                <div class="admin-errands-table">
                    <div class="table-header-controls">
                        <h4>Active Errands</h4>
                        <div class="rows-per-page">
                            <label for="perPageSelect"><h4>Show:</h4></label>
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
                                        <span class="circle-spinner" style="width:20px;height:20px;border-width:2px;"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pagination-container" class="pagination-container">
                        <button type="button" id="pagination-previous">Previous</button>
                        <div id="pagination-pages"></div>
                        <button type="button" id="pagination-next">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <template id="errand-row-template">
        <tr>
            <td class="errand-id"></td>
            <td class="errand-title"></td>
            <td class="errand-category"></td>
            <td class="errand-created-at"></td>
            <td class="errand-deadline"></td>
            <td class="errand-fee"></td>
            <td><span class="errand-status"></span></td>
            <td>
                <div class="button-row">
                    <a href="#" class="btn-view errand-view-link">View</a>
                    <span class="action-slot"></span>
                </div>
            </td>
        </tr>
    </template>

    <template id="errand-empty-template">
        <tr>
            <td class="no-found" colspan="8">No errands found.</td>
        </tr>
    </template>

    <template id="errand-error-template">
        <tr>
            <td class="no-found errand-error-text" colspan="8">Unable to load errands.</td>
        </tr>
    </template>

    <template id="pagination-button-template">
        <button type="button" class="pagination-button"></button>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const tableBody = document.getElementById('errands-table-body');
            const paginationContainer = document.getElementById('pagination-container');
            const searchInput = document.getElementById('errandSearch');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');
            const perPageSelect = document.getElementById('perPageSelect');
            const errorText = document.getElementById('errorMessageText');

            let currentPage = 1;
            let currentSearch = '';

            loadMetrics();
            loadErrands();
            updateClearButton();

            function displayGlobalError(message){
                if(!errorText) return;
                errorText.textContent = message;
                errorText.style.display = 'block';
                setTimeout(() => { errorText.style.display = 'none'; }, 5000);
            }

            async function loadMetrics(){
                try{
                    const response = await fetch('/api/admin/dashboard/active-metrics', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-API-KEY': apiKey,
                            'X-MY-ORIGIN' : origin
                        },
                        credentials: 'same-origin'
                    });
                    const result = await response.json();

                    if(!response.ok || !result.success){
                        throw new Error(result.message || 'Failed to load dashboard data.');
                    }

                    const metrics = result.metrics;

                    document.getElementById('metric-pending').textContent = metrics.pending;
                    document.getElementById('metric-pending-payment').textContent = metrics.pending_payment;
                    document.getElementById('metric-in-process').textContent = metrics.in_process;
                    document.getElementById('metric-pending-completion').textContent = metrics.pending_completion;
                    document.getElementById('metric-pending-failure').textContent = metrics.pending_failure;
                    document.getElementById('metric-dispute').textContent = metrics.dispute;
                    document.getElementById('metric-total').textContent = metrics.total;

                    const cardToggles = {
                        'metric-card-pending': metrics.pending,
                        'metric-card-pending-payment': metrics.pending_payment,
                        'metric-card-in-process': metrics.in_process,
                        'metric-card-pending-completion': metrics.pending_completion,
                        'metric-card-pending-failure': metrics.pending_failure,
                        'metric-card-dispute': metrics.dispute
                    };

                    Object.entries(cardToggles).forEach(([cardId, val]) => {
                        const card = document.getElementById(cardId);
                        if (card) {
                            card.style.display = val > 0 ? 'flex' : 'none';
                        }
                    });
                    
                    const totalCard = document.getElementById('metric-card-total');
                    if(totalCard) totalCard.style.display = 'flex';

                }catch(error){
                    console.error('Metrics error:', error);
                    displayGlobalError(error.message);
                }
            }

            async function loadErrands(page){
                if(page) currentPage = page;

                showLoading();

                const params = new URLSearchParams({
                    page: currentPage,
                    per_page: perPageSelect.value
                });

                if(currentSearch !== ''){
                    params.append('search', currentSearch);
                }

                try{
                    const response = await fetch('/api/admin/dashboard/active-errands?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-API-KEY': apiKey,
                            'X-MY-ORIGIN' : origin
                        },
                        credentials: 'same-origin'
                    });
                    const result = await response.json();

                    if(!response.ok || !result.success){
                        throw new Error(result.message || 'Failed to load errands.');
                    }

                    displayErrands(result.errands);
                    displayPagination(result.pagination);
                }catch(error){
                    console.error('Errands error:', error);
                    displayGlobalError(error.message);
                }
            }

            function displayErrands(errands){
                tableBody.innerHTML = '';

                if(!errands || errands.length === 0){
                    tableBody.appendChild(document.getElementById('errand-empty-template').content.cloneNode(true));
                    return;
                }

                const template = document.getElementById('errand-row-template');

                errands.forEach(function(errand){
                    const row = template.content.cloneNode(true);

                    row.querySelector('.errand-id').textContent = errand.id;
                    row.querySelector('.errand-title').textContent = errand.title;
                    row.querySelector('.errand-category').textContent = errand.category;
                    row.querySelector('.errand-created-at').textContent = errand.created_at;
                    row.querySelector('.errand-deadline').textContent = errand.deadline;
                    row.querySelector('.errand-fee').textContent = errand.fee;

                    const status = row.querySelector('.errand-status');
                    status.textContent = errand.status;
                    status.className = errand.status_class;

                    row.querySelector('.errand-view-link').href = '/admin/view-errand/' + encodeURIComponent(errand.id);

                    const actionSlot = row.querySelector('.action-slot');

                    tableBody.appendChild(row);
                });
            }

            function displayPagination(pagination){
                const previousButton = document.getElementById('pagination-previous');
                const nextButton = document.getElementById('pagination-next');
                const pagesContainer = document.getElementById('pagination-pages');

                pagesContainer.innerHTML = '';

                if(!pagination || pagination.last_page <= 1){
                    paginationContainer.style.display = 'none';
                    return;
                }

                paginationContainer.style.display = 'flex';
                previousButton.disabled = pagination.current_page === 1;
                nextButton.disabled = pagination.current_page === pagination.last_page;

                for(let page = 1; page <= pagination.last_page; page++){
                    createPaginationButton(page, page === pagination.current_page);
                }

                previousButton.onclick = () => {
                    if(pagination.current_page > 1) loadErrands(pagination.current_page - 1);
                };

                nextButton.onclick = () => {
                    if(pagination.current_page < pagination.last_page) loadErrands(pagination.current_page + 1);
                };
            }

            function createPaginationButton(page, active){
                const template = document.getElementById('pagination-button-template');
                const button = template.content.cloneNode(true);
                const element = button.querySelector('.pagination-button');

                element.textContent = page;

                if(active){
                    element.classList.add('active');
                }else{
                    element.addEventListener('click', () => loadErrands(page));
                }

                document.getElementById('pagination-pages').appendChild(button);
                const pagesContainer = document.getElementById('pagination-pages');
                pagesContainer.innerHTML = '';
            }
        });
    </script>
@endpush
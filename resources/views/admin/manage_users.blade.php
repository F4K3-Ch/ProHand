@extends('layouts.app')

@section('title', 'Manage Users - Prohand')

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
                            <h2>Manage Users</h2>
                        </div>

                        <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="search-wrapper">
                        <div class="search-bar">
                            <input type="text" id="errandSearch" placeholder="Search users...">
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
                    <h3>Users Metrics</h3>
                    <div class="total-cards-container">
                        <div class="total-card bg-admin" id="metric-card-admin">
                            <div class="card-header">
                                <span class="status-circle circle-admin"></span>
                                <h3>Admin</h3>
                            </div>
                            <p id="metric-admin">
                                <span id="metric-spinner" class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                            </p>
                        </div>

                        <div class="total-card bg-tasker" id="metric-card-tasker">
                            <div class="card-header">
                                <span class="status-circle circle-tasker"></span>
                                <h3>Tasker</h3>
                            </div>
                            <p id="metric-tasker">
                                <span id="metric-spinner" class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                            </p>
                        </div>

                        <div class="total-card bg-client" id="metric-card-client">
                            <div class="card-header">
                                <span class="status-circle circle-client"></span>
                                <h3>Client</h3>
                            </div>
                            <p id="metric-client">
                                <span id="metric-spinner" class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                            </p>
                        </div>

                        <div class="total-card bg-total" id="metric-card-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Users</h3>
                            </div>
                            <p id="metric-total">
                                <span id="metric-spinner" class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                            </p>
                        </div>
                    </div>
                </section>

                <div id="welcome-banner" class="welcome-banner-card" style="display: none;">
                    <div class="welcome-banner-text">
                        <h4>Need to add a new user account?</h4>
                        <p>Administrators can create and manage accounts across all three user roles: Client, Tasker, and Admin.</p>
                    </div>
                    <a href="#" class="btn-welcome">
                        <span><i class="fa-solid fa-circle-plus"></i></span> Add User
                    </a>
                </div>

                <div class="users-table">
                    <div class="table-header-controls">
                        <h4>My Errands</h4>
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
                                    <th>User ID</th>
                                    <th>Picture</th>
                                    <th>Email</th>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Contact</th>
                                    <th>Created At</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">

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

    <template id="users-row-template">
        <tr>
            <td class="user-id"></td>
            <td>
                <img src="" alt="Profile" class="table-profile-img user-picture">
            </td>
            <td class="user-email"></td>
            <td class="user-last-name"></td>
            <td class="user-first-name"></td>
            <td class="user-middle-name"></td>
            <td class="user-contact"></td>
            <td class="user-created-at"></td>
            <td>
                <span class="role-badge user-role-badge"></span>
            </td>
            <td>
                <div class="button-row">
                    <a href="#" class="btn-view user-view-link">View</a>
                </div>
            </td>
        </tr>
    </template>

    <template id="users-empty-template">
        <tr>
            <td class="no-found" colspan="8">No users found.</td>
        </tr>
    </template>

    <template id="users-error-template">
        <tr>
            <td class="no-found errand-error-text" colspan="8">Unable to load users.</td>
        </tr>
    </template>

    <template id="users-loading-template">
        <tr>
            <td class="no-found" colspan="8">
                <span class="circle-spinner" style="width:20px;height:20px;border-width:2px;"></span>
            </td>
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
            const pagesContainer = document.getElementById('pagination-pages');
            const searchInput = document.getElementById('errandSearch');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');
            const perPageSelect = document.getElementById('perPageSelect');
            const welcomeBanner = document.getElementById('welcome-banner');

            let currentPage = 1;
            let currentSearch = '';

            loadMetrics();
            loadErrands();
            updateClearButtonState();

            const errorMessageText = document.getElementById('errorMessageText');
            function displayGlobalError(message){
                if(!errorMessageText) return;
                errorMessageText.textContent = message;
                errorMessageText.style.display = 'flex';
                setTimeout(() => { 
                    errorMessageText.style.display = 'none'; 
                }, 5000);
            }

            function showLoading(){
                tableBody.innerHTML = '';
                tableBody.appendChild(document.getElementById('errand-loading-template').content.cloneNode(true));
            }

            async function loadMetrics(){
                try{
                    const response = await fetch('/api/tasker/your-task/accepted-metrics', {
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

                    document.getElementById('metric-accepted').textContent = metrics.accepted;
                    document.getElementById('metric-in-progress').textContent = metrics.in_progress;
                    document.getElementById('metric-awaiting-confirmation').textContent = metrics.awaiting_confirmation;
                    document.getElementById('metric-dispute').textContent = metrics.dispute;
                    document.getElementById('metric-total').textContent = metrics.total;
                    
                    const totalCard = document.getElementById('metric-card-total');

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
                    const response = await fetch('/api/tasker/your-task/accepted-errands?' + params.toString(), {
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

                    if (currentSearch === '') {
                        if(welcomeBanner) welcomeBanner.style.display = 'flex';
                    } else {
                        if(welcomeBanner) welcomeBanner.style.display = 'none';
                    }

                    displayErrands(result.errands);
                    displayPagination(result.pagination);
                }catch(error){
                    console.error('Errands error:', error);
                    displayGlobalError(error.message);
                    tableBody.innerHTML = '';
                    tableBody.appendChild(document.getElementById('errand-error-template').content.cloneNode(true));
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

                    row.querySelector('.errand-view-link').href =
                        '/tasker/view-errand/' + encodeURIComponent(errand.id) +
                        '?from=tasker.your_task';

                    tableBody.appendChild(row);
                });
            }

            function displayPagination(pagination){
                const previousButton = document.getElementById('pagination-previous');
                const nextButton = document.getElementById('pagination-next');

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

                pagesContainer.appendChild(button);
            }

            searchButton.addEventListener('click', function() {
                currentSearch = searchInput.value.trim();
                currentPage = 1;
                loadErrands();
                updateClearButtonState();
            });

            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    currentSearch = searchInput.value.trim();
                    currentPage = 1;
                    loadErrands();
                    updateClearButtonState();
                }
            });

            clearSearchButton.addEventListener('click', function() {
                searchInput.value = '';
                currentSearch = '';
                currentPage = 1;
                loadErrands();
                updateClearButtonState();
            });

            perPageSelect.addEventListener('change', function() {
                currentPage = 1;
                loadErrands();
            });

            function updateClearButtonState() {
                if(clearSearchButton) {
                    clearSearchButton.style.display = searchInput.value ? 'flex' : 'none';
                }
            }
        });
    </script>
@endpush
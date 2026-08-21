@extends('layouts.app')

@section('title', 'Tasker Dashboard')

@section('sidebar')
    @include('layouts.sidebar.tasker')
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
                            <input type="text" id="errandSearch" placeholder="Search available errands...">
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
                    <h3>Available Errand Metrics</h3>
                    <div class="total-cards-container">
                        <div class="total-card bg-pending" id="metric-card-pending">
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending Errands</h3>
                            </div>
                            <p id="metric-pending">
                                <span id="metric-spinner" class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                            </p>
                        </div>
                    </div>
                </section>

                <div class="tasker-errands-table">
                    <div class="table-header-controls">
                        <h4>Available Pending Errands</h4>
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
                    <button type="button" class="btn-accept">Accept</button>
                </div>
            </td>
        </tr>
    </template>

    <template id="errand-empty-template">
        <tr>
            <td class="no-found" colspan="8">No available pending errands found.</td>
        </tr>
    </template>

    <template id="errand-error-template">
        <tr>
            <td class="no-found" colspan="8" class="errand-error-text">Unable to load errands.</td>
        </tr>
    </template>

    <template id="errand-loading-template">
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
            const metricSpinner = document.getElementById('metric-spinner');
            const tableBody = document.getElementById('errands-table-body');
            const paginationContainer = document.getElementById('pagination-container');
            const searchInput = document.getElementById('errandSearch');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');
            const perPageSelect = document.getElementById('perPageSelect');
            let currentPage = 1;
            let currentSearch = '';

            loadMetrics();
            loadErrands();
            updateClearButton();

            async function loadMetrics(){
                try{
                    const response = await fetch('/api/tasker/dashboard/pending-metrics',{
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-API-KEY': typeof apiKey !== 'undefined' ? apiKey : '',
                            'X-MY-ORIGIN' : typeof origin !== 'undefined' ? origin : ''
                        },
                        credentials: 'same-origin'
                    });
                    const result = await response.json();

                    if(!response.ok || !result.success){
                        throw new Error(result.message || 'Failed to load dashboard metrics.');
                    }

                    const metrics = result.metrics;

                    document.getElementById('metric-pending').textContent = metrics.pending;

                }catch(error){
                    console.error('Metrics error:', error);
                    document.getElementById('metric-pending').textContent = '—';
                }finally{
                    if(metricSpinner) metricSpinner.style.display = 'none';
                }
            }

            async function loadErrands(page){
                if(page) currentPage = page;

                showLoading();

                const params = new URLSearchParams();
                params.append('page', currentPage);
                params.append('per_page', perPageSelect.value);

                if(currentSearch !== ''){
                    params.append('search', currentSearch);
                }

                try{
                    const response = await fetch('/api/tasker/dashboard/pending-errands?' + params.toString(),{
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-API-KEY': typeof apiKey !== 'undefined' ? apiKey : '',
                            'X-MY-ORIGIN' : typeof origin !== 'undefined' ? origin : ''
                        },
                        credentials: 'same-origin'
                    });
                    const result = await response.json();

                    if(!response.ok || !result.success){
                        throw new Error(result.message || 'Failed to load pending errands.');
                    }

                    displayErrands(result.errands);
                    displayPagination(result.pagination);
                }catch(error){
                    console.error('Errands error:', error);
                    showError(error.message || 'Unable to load errands.');
                }
            }

            function displayErrands(errands){
                tableBody.innerHTML = '';

                if(!errands || errands.length === 0){
                    const template = document.getElementById('errand-empty-template');
                    tableBody.appendChild(template.content.cloneNode(true));
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

                    row.querySelector('.errand-view-link').href = '/tasker/view-errand/' + encodeURIComponent(errand.id);

                    const acceptBtn = row.querySelector('.btn-accept');
                    
                    acceptBtn.addEventListener('click', async function(){
                        var customMessage = 'Are you sure you want to accept errand #' + errand.id + ' ("' + errand.title + '")?';
                        var isConfirmed = confirm(customMessage); 
                        
                        if(!isConfirmed){
                            return;
                        }

                        try {
                            const response = await fetch('/api/tasker/errands/' + errand.id + '/accept', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': typeof apiKey !== 'undefined' ? apiKey : '',
                                    'X-MY-ORIGIN' : typeof origin !== 'undefined' ? origin : ''
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to accept errand.');
                            }

                            const successMessageText = document.getElementById('successMessageText');
                            if (successMessageText) {
                                successMessageText.textContent = result.message || 'Errand accepted successfully!';
                                successMessageText.style.display = 'block';

                                setTimeout(() => {
                                    successMessageText.textContent = '';
                                    successMessageText.style.display = 'none';
                                }, 3000);
                            }

                            loadMetrics();
                            loadErrands();

                        } catch(error) {
                            console.error('Accept error:', error);
                            const errorText = document.getElementById('errorMessageText');
                            if(errorText) {
                                errorText.textContent = error.message;
                                errorText.style.display = 'block';
                                setTimeout(() => { errorText.style.display = 'none'; }, 4000);
                            }
                        }
                    });

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

                previousButton.onclick = function(){
                    if(pagination.current_page > 1){
                        loadErrands(pagination.current_page - 1);
                    }
                };

                nextButton.onclick = function(){
                    if(pagination.current_page < pagination.last_page){
                        loadErrands(pagination.current_page + 1);
                    }
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
                    element.addEventListener('click', function(){
                        loadErrands(page);
                    });
                }

                document.getElementById('pagination-pages').appendChild(button);
            }

            function showLoading(){
                const template = document.getElementById('errand-loading-template');
                tableBody.innerHTML = '';
                tableBody.appendChild(template.content.cloneNode(true));
            }

            function showError(message){
                const template = document.getElementById('errand-error-template');
                const row = template.content.cloneNode(true);

                row.querySelector('.errand-error-text').textContent = message;

                tableBody.innerHTML = '';
                tableBody.appendChild(row);
                paginationContainer.innerHTML = '';
            }

            function updateClearButton(){
                clearSearchButton.style.display = searchInput.value.trim() !== '' ? 'inline-block' : 'none';
            }

            searchButton.addEventListener('click', function(event){
                event.preventDefault();
                currentSearch = searchInput.value.trim();
                currentPage = 1;
                loadErrands();
            });

            searchInput.addEventListener('keydown', function(event){
                if(event.key === 'Enter'){
                    event.preventDefault();
                    currentSearch = searchInput.value.trim();
                    currentPage = 1;
                    loadErrands();
                }
            });

            searchInput.addEventListener('input', function(){
                updateClearButton();
            });

            clearSearchButton.addEventListener('click', function(event){
                event.preventDefault(); 
                searchInput.value = '';
                currentSearch = '';
                currentPage = 1;
                updateClearButton();
                loadErrands();
            });

            perPageSelect.addEventListener('change', function(){
                currentPage = 1;
                loadErrands();
            });
        });
    </script>
@endpush
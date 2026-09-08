@extends('layouts.app')

@section('title', 'Admin History - ProHand')

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
                            <h2>History Errands</h2>
                        </div>

                        <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="search-wrapper">
                        <div class="search-bar">
                            <input type="text" id="errandSearch" placeholder="Search finished errands...">
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
                        <div class="total-card bg-completed" id="metric-card-completed" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-completed"></span>
                                <h3>Completed</h3>
                            </div>
                            <p id="metric-completed"></p>
                        </div>

                        <div class="total-card bg-failed" id="metric-card-failed" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Failed</h3>
                            </div>
                            <p id="metric-failed"></p>
                        </div>

                        <div class="total-card bg-total" id="metric-card-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total History Errands</h3>
                            </div>
                            <p id="metric-total">
                                <span id="metric-spinner" class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                            </p>
                        </div>
                    </div>
                </section>

                <div class="errands-container">
                    <div class="table-header-controls">
                        <h4>History Errands</h4>
                        <div class="rows-per-page">
                            <label for="perPageSelect"><h4>Show:</h4></label>
                            <select id="perPageSelect" class="form-control">
                                <option value="4">4</option>
                                <option value="8" selected>8</option>
                                <option value="16">16</option>
                                <option value="32">32</option>
                            </select>
                        </div>
                    </div>

                    <div id="errands-cards-grid" class="errands-cards-grid">

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

    <template id="errand-card-template">
        <div class="errand-post-card">
            <div class="errand-card-top">
                <span class="errand-id-badge">ID: <span class="errand-id"></span></span>
                <span class="errand-status"></span>
            </div>
            <h4 class="errand-title"></h4>
            <div class="errand-card-details">
                <p><span><i class="fa-solid fa-folder-open"></i> Category</span> <span class="errand-category"></span></p>
                <p><span><i class="fa-solid fa-location-dot"></i> Location</span> <span class="errand-location"></span></p>
                <p><span><i class="fa-solid fa-calendar-plus"></i> Created</span> <span class="errand-created-at"></span></p>
                <p><span><i class="fa-solid fa-hourglass-end"></i> Finished At</span> <span class="errand-finished-at"></span></p>
                <p><span><i class="fa-solid fa-peso-sign"></i> Fee</span> <strong class="errand-fee"></strong></p>
            </div>
            <div class="errand-card-actions">
                <a href="#" class="btn-view"><i class="fa-solid fa-eye"></i> View</a>
                <button type="button" class="btn-delete"><i class="fa-solid fa-trash"></i> Delete</button>
            </div>
        </div>
    </template>

    <template id="errand-empty-template">
        <div class="errand-card-message no-found">No errands found.</div>
    </template>

    <template id="errand-error-template">
        <div class="errand-card-message no-found errand-error-text">Unable to load errands.</div>
    </template>

    <template id="errand-loading-template">
        <div class="errand-card-message no-found">
            <span class="circle-spinner" style="width:20px;height:20px;border-width:2px;"></span>
        </div>
    </template>

    <template id="pagination-button-template">
        <button type="button" class="pagination-button"></button>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const cardsGrid = document.getElementById('errands-cards-grid');
            const paginationContainer = document.getElementById('pagination-container');
            const pagesContainer = document.getElementById('pagination-pages');
            const searchInput = document.getElementById('errandSearch');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');
            const perPageSelect = document.getElementById('perPageSelect');

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
                cardsGrid.innerHTML = '';
                cardsGrid.appendChild(document.getElementById('errand-loading-template').content.cloneNode(true));
            }

            async function loadMetrics(){
                try{
                    const response = await fetch('/api/admin/history/history-metrics', {
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

                    document.getElementById('metric-completed').textContent = metrics.completed;
                    document.getElementById('metric-failed').textContent = metrics.failed;
                    document.getElementById('metric-total').textContent = metrics.total;

                    const cardToggles = {
                        'metric-card-completed': metrics.completed,
                        'metric-card-failed': metrics.failed
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
                    const response = await fetch('/api/admin/history/history-errands?' + params.toString(), {
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
                        throw new Error(result.message || 'Failed to load finished errands.');
                    }

                    displayErrands(result.errands);
                    displayPagination(result.pagination);
                }catch(error){
                    console.error('Errands error:', error);
                    displayGlobalError(error.message);
                    cardsGrid.innerHTML = '';
                    cardsGrid.appendChild(document.getElementById('errand-error-template').content.cloneNode(true));
                }
            }

            function displayErrands(errands){
                cardsGrid.innerHTML = '';

                if(!errands || errands.length === 0){
                    cardsGrid.appendChild(document.getElementById('errand-empty-template').content.cloneNode(true));
                    return;
                }

                const template = document.getElementById('errand-card-template');

                errands.forEach(function(errand){
                    const card = template.content.cloneNode(true);

                    card.querySelector('.errand-id').textContent = errand.id;
                    card.querySelector('.errand-title').textContent = errand.title;
                    card.querySelector('.errand-category').textContent = errand.category;
                    card.querySelector('.errand-location').textContent = errand.location;
                    card.querySelector('.errand-created-at').textContent = errand.created_at;
                    card.querySelector('.errand-finished-at').textContent = errand.finished_at;
                    card.querySelector('.errand-fee').textContent = errand.fee;

                    const status = card.querySelector('.errand-status');
                    status.textContent = errand.status;
                    status.className = errand.status_class;

                    card.querySelector('.btn-view').href =
                        '/admin/view-errand/' + errand.id +
                        '?from=admin.history';

                    const deleteBtn = card.querySelector('.btn-delete');
                    if(deleteBtn){
                        deleteBtn.addEventListener('click', async function(){
                            const customTitle = 'Confirm Deletion';
                            const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                            const isConfirmed = await showConfirmation(customTitle, customMessage);
                            
                            if(!isConfirmed) return;
                            // Proceed with deletion
                        });
                    }
                    cardsGrid.appendChild(card);
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
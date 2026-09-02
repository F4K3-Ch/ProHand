@extends('layouts.app')

@section('title', 'Your Task - Prohand')

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
                            <h2>Your Task</h2>
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
                        <div class="total-card bg-accepted" id="metric-card-accepted" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-accepted"></span>
                                <h3>Accepted</h3>
                            </div>
                            <p id="metric-accepted"></p>
                        </div>

                        <div class="total-card bg-in-progress" id="metric-card-in-progress" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-progress"></span>
                                <h3>In Progress</h3>
                            </div>
                            <p id="metric-in-progress"></p>
                        </div>

                        <div class="total-card bg-awaiting-confirmation" id="metric-card-awaiting-confirmation" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-awaiting-confirmation"></span>
                                <h3>Awaiting Confirmation</h3>
                            </div>
                            <p id="metric-awaiting-confirmation"></p>
                        </div>

                        <div class="total-card bg-dispute" id="metric-card-dispute" style="display:none;">
                            <div class="card-header">
                                <span class="status-circle circle-dispute"></span>
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

                <div class="client-errands-table">
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
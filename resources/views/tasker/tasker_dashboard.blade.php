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
                        <input type="text" name="search" value="" placeholder="Search">
                    </div>
                    <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
            </header>
        </div>

        <div class="dashboard">
            
        </div>
    </main>
@endsection
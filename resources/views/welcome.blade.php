<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>ProHand</title>
    <script>
        const savedTheme = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', savedTheme || 'dark');
    </script>
</head>
<body class="landing-page">

    <header class="navbar">
        <div class="logo">
            <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="ProHand Logo" class="logo-img">
            <p>Pro<span>Hand</span></p>
        </div>
        <div class="nav-actions">
            <button type="button" id="landingThemeToggle" class="landing-theme-toggle">
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="/login" class="login-btn">Login</a>
            <a href="/register" class="register-btn">Register</a>
        </div>
    </header>

    <main class="index-info-container">
        <section class="hero">
            <div class="hero-badge"><i class="fa-solid fa-handshake"></i> Your Local Errand Partner</div>
            <h1>Simplify Your Day.<br>Let Pro<span>Hand</span> <span>Handle the Rest.</span></h1>
            <p>Post your errands, choose your category, set your own price, and let our taskers handle the rest efficiently.</p>
            <a href="/register" class="get-started"><i class="fa-solid fa-arrow-right"></i> Get Started Now</a>
        </section>

        <section class="services">
            <h2 class="section-title">How It <span>Works</span></h2>
            <p class="section-subtitle">Getting your errands done is simple with ProHand.</p>

            <div class="services-container">
                <div class="service-card">
                    <div class="service-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <h3>Post Your <span>Errand</span></h3>
                    <p>Describe what you need done, choose a category, set your budget, and post your errand for taskers to see.</p>
                </div>

                <div class="service-card">
                    <div class="service-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                    <h3>Wait for <span>Acceptance</span></h3>
                    <p>Once your errand is posted, simply wait for an available tasker to accept it. No need to search for one yourself.</p>
                </div>

                <div class="service-card">
                    <div class="service-icon"><i class="fa-solid fa-list-check"></i></div>
                    <h3>Track & <span>Confirm</span></h3>
                    <p>Follow your errand's progress and confirm its completion once the tasker has finished the task.</p>
                </div>
            </div>

            <section class="support-section">
                <div class="support-icon"><i class="fa-solid fa-envelope"></i></div>
                <div class="support-content">
                    <h2>Need <span>Help</span> With Your <span>Errand?<span></h2>
                    <p>If you experience an issue with a tasker or have a concern about your errand, please contact us through email.</p>
                    <span class="support-email">prohandsupport@gmail.com</span>
                </div>
            </section>
        </section>

        <section class="final-cta">
            <h2>Ready to <span>hand</span> off your to-do <span>list?</span></h2>
            <p>Join ProHand and make your everyday errands easier.</p>
            <a href="/register" class="get-started"><i class="fa-solid fa-user-plus"></i> Join ProHand Today</a>
        </section>

        <footer class="index-footer">
            <p>&copy; 2026 ProHand. All rights reserved.</p>
        </footer>
    </main>

</body>
</html>

<script>
    const themeToggle = document.getElementById('landingThemeToggle');
    const html = document.documentElement;

    function updateThemeButton() {
        const dark = html.getAttribute('data-theme') === 'dark';

        themeToggle.innerHTML = dark
            ? '<i class="fa-solid fa-sun"></i>'
            : '<i class="fa-solid fa-moon"></i>';
    }

    updateThemeButton();

    themeToggle.addEventListener('click', function () {
        const dark = html.getAttribute('data-theme') === 'dark';
        const theme = dark ? 'light' : 'dark';

        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        updateThemeButton();
    });
</script>
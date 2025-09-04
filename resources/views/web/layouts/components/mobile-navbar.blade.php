<!-- Bottom Navigation Bar (Mobile) -->
<nav class="bottom-bar lg:hidden">
    <div class="bottom-bar-boxes">
        <div class="bottom-bar-box">
            <button class="bottom-bar-button" onclick="toggleSidebar()">
                <svg class="bottom-bar-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16M4 12h16M4 18h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </button>
        </div>
        <div class="bottom-bar-box">
            <a href="{{ route('web.search')}}" class="bottom-bar-button">
                <svg class="bottom-bar-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        </div>
        <div class="bottom-bar-box">
            <a href="{{ route('web.new-cars')}}" class="bottom-bar-button">
                <svg class="bottom-bar-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </a>
        </div>
        <div class="bottom-bar-box">
            <a href="{{ route('web.home')}}" class="bottom-bar-button">
                <svg class="bottom-bar-icon" viewBox="0 0 24 24" fill="none">
                    <path d="M3 12l2-2 7-7 7 7 2 2M5 10v10h3m10-11v10h-3" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        </div>
    </div>
</nav>

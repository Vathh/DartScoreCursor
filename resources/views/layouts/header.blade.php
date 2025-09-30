<header class="bg-dark-bg text-light-white py-6 border-b-1 border-light-green">
    <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-2xl font-bold text-light-green">DartScore</h1>
        <nav class="flex ">
            <a href="/" class="nav-btn">Strona główna</a>

            <a href='{{ route('league.leagues') }}' class="nav-btn">Ligi</a>
            <a href='{{ route('season.seasons') }}' class="nav-btn">Sezony</a>
            <a href='{{ route('tournament.tournaments') }}' class="nav-btn">Turnieje</a>

            @guest
                <a href='{{ route('pages.loginPanel') }}' class="nav-btn">Zaloguj się</a>
            @endguest

            @auth
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button class="nav-btn hover:cursor-pointer">Wyloguj się</button>
                </form>
            @endauth
        </nav>
    </div>
</header>

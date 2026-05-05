<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Glade - Trusted Auctions</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|playfair-display:400,400i,500,500i,600,600i,700" rel="stylesheet" />

    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    
    <style>
        .font-serif {
            font-family: 'Playfair Display', ui-serif, Georgia, Cambria, "Times New Roman", Times, serif !important;
        }
        .font-sans {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif !important;
        }
        /* Custom Select Styling */
        select option {
            color: #111827; /* Tailwind gray-900 */
        }
    </style>
</head>
<body class="font-sans antialiased text-white bg-blue-900 selection:bg-white/30">
    <div class="relative min-h-screen w-full bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('images/glade_bg.png') }}');">
        <!-- Subtle gradient overlay for readability -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#2167a3]/60 via-[#2167a3]/20 to-transparent pointer-events-none"></div>

        <!-- Content Container -->
        <div class="relative z-10 w-full max-w-7xl mx-auto px-6 py-8 h-full flex flex-col min-h-screen">
            <!-- Navbar -->
            <header class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-1">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-white">
                        <circle cx="14" cy="20" r="10" fill="currentColor"/>
                        <circle cx="28" cy="20" r="8" fill="currentColor" fill-opacity="0.5"/>
                        <path d="M14 6 C16 6 18 8 18 10 C18 12 16 14 14 14 C12 14 10 12 10 10 C10 8 12 6 14 6 Z" fill="currentColor"/>
                    </svg>
                    <span class="text-[1.7rem] font-serif tracking-wide font-medium mt-1 ml-1">Glade</span>
                </div>

                <!-- Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-[15px] font-medium opacity-90 ml-12">
                    <a href="#" class="hover:opacity-100 transition">Home</a>
                    <a href="#" class="hover:opacity-100 transition">Live Auctions</a>
                    <a href="#" class="hover:opacity-100 transition">Upcoming Auctions</a>
                    <a href="#" class="hover:opacity-100 transition">Featured Properties</a>
                    <a href="#" class="hover:opacity-100 transition">How It Works</a>
                </nav>

                <!-- Auth / CTA -->
                <div class="flex items-center gap-4 ml-auto">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-6 py-2.5 bg-white text-gray-900 rounded-lg text-sm font-semibold hover:bg-gray-100 transition shadow-sm">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-6 py-2.5 bg-white text-gray-900 rounded-lg text-[15px] font-semibold hover:bg-gray-100 transition shadow-sm">
                                Log In
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-6 py-2.5 bg-white text-gray-900 rounded-lg text-[15px] font-semibold hover:bg-gray-100 transition shadow-sm">
                                    Register Now
                                </a>
                            @endif
                        @endauth
                    @else
                        <a href="#" class="px-6 py-2.5 bg-white text-gray-900 rounded-lg text-[15px] font-semibold hover:bg-gray-100 transition shadow-sm">
                            Register Now
                        </a>
                    @endif
                </div>
            </header>

            <!-- Hero Content -->
            <main class="flex-grow flex flex-col justify-center max-w-3xl mt-12 md:mt-24 pb-24">
                <h1 class="text-5xl md:text-6xl lg:text-[5.5rem] leading-[1.05] font-serif font-semibold tracking-tight text-white mb-6 drop-shadow-md">
                    Find your <span class="italic font-semibold opacity-95">Dream</span><br>
                    <span class="italic font-semibold opacity-95">Property</span> at<br>
                    Trusted Auctions.
                </h1>
                
                <p class="text-lg md:text-[21px] text-white/90 leading-[1.6] mb-10 max-w-xl font-light drop-shadow-sm">
                    Backed by banks and leading auction companies, we make property bidding simple, transparent, and scam-free.
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-5 mb-14">
                    <a href="#" class="px-6 py-3.5 bg-white text-gray-900 rounded-lg font-bold hover:bg-gray-100 transition shadow-sm text-sm">
                        Browse Live Auctions
                    </a>
                    <a href="#" class="px-6 py-3.5 bg-transparent border border-white/60 text-white rounded-lg font-semibold hover:bg-white/10 transition text-sm">
                        Browse Live Auctions
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 flex flex-wrap md:flex-nowrap gap-3 items-center shadow-lg max-w-fit">
                    <!-- Type Dropdown -->
                    <div class="relative min-w-[130px]">
                        <select class="w-full appearance-none bg-transparent border border-white/30 rounded-lg px-4 py-2.5 text-white text-[15px] focus:outline-none focus:border-white/60 cursor-pointer">
                            <option value="" disabled selected>Type</option>
                            <option value="house">House</option>
                            <option value="apartment">Apartment</option>
                            <option value="land">Land</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white/70">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Areas Dropdown -->
                    <div class="relative min-w-[130px]">
                        <select class="w-full appearance-none bg-transparent border border-white/30 rounded-lg px-4 py-2.5 text-white text-[15px] focus:outline-none focus:border-white/60 cursor-pointer">
                            <option value="" disabled selected>Areas</option>
                            <option value="downtown">Downtown</option>
                            <option value="suburbs">Suburbs</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white/70">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Price Dropdown -->
                    <div class="relative min-w-[130px]">
                        <select class="w-full appearance-none bg-transparent border border-white/30 rounded-lg px-4 py-2.5 text-white text-[15px] focus:outline-none focus:border-white/60 cursor-pointer">
                            <option value="" disabled selected>Price</option>
                            <option value="under500k">Under $500k</option>
                            <option value="500k-1m">$500k - $1M</option>
                            <option value="over1m">Over $1M</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white/70">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Search Button -->
                    <button class="bg-white text-gray-900 px-8 py-2.5 rounded-lg font-bold text-[15px] hover:bg-gray-100 transition whitespace-nowrap shadow-sm ml-2">
                        Search
                    </button>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

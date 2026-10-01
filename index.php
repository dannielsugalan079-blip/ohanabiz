<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OHANA Business Consultancy Inc.</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f6f3ee;
            color: #2d3748;
        }
    </style>
</head>
<body class="bg-[#f6f3ee] text-gray-800 font-sans selection:bg-[#1c482c] selection:text-white">

    <!-- Top Announcement Bar -->
    <div class="bg-[#ebd9cb]/60 backdrop-blur-sm text-xs py-2 px-6 text-gray-700 flex justify-between items-center max-w-7xl mx-auto rounded-b-xl shadow-xs font-medium">
        <span class="flex items-center gap-2"><i class="fa-solid fa-bullhorn text-[#1c482c]"></i> Need assistance with government or corporate documents? Contact us today!</span>
        <div class="flex items-center gap-4">
            <span class="flex items-center gap-2"><i class="fa-solid fa-phone text-[#1c482c]"></i> Call Us: +63 912 345 6789</span>
            <span class="text-gray-300">|</span>
            <!-- Employee & Supervisor Portal Link -->
            <a href="employee_login.php" class="text-gray-700 hover:text-[#1c482c] font-semibold flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-id-badge text-[#1c482c]"></i> Employee & Supervisor Portal
            </a>
            <span class="text-gray-300">|</span>
            <!-- Admin Portal Link -->
            <a href="admin_login.php" class="text-gray-600 hover:text-[#1c482c] font-semibold flex items-center gap-1 transition-colors">
                <i class="fa-solid fa-user-shield text-[#1c482c]"></i> Admin Portal
            </a>
        </div>
    </div>

    <!-- Navigation Header -->
    <header class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between sticky top-0 z-50 bg-[#f6f3ee]/90 backdrop-blur-md border-b border-gray-200/50">
        <div class="flex items-center space-x-3 group cursor-pointer">
            <div class="h-12 w-12 flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-105">
                <!-- Vector Logo -->
                <svg viewBox="0 0 500 500" class="w-full h-full drop-shadow-sm">
                    <defs>
                        <linearGradient id="headGradLeft" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#80c242"/>
                            <stop offset="100%" stop-color="#4a9332"/>
                        </linearGradient>
                        <linearGradient id="headGradCenter" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#82c442"/>
                            <stop offset="100%" stop-color="#519835"/>
                        </linearGradient>
                        <linearGradient id="headGradRight" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#80c242"/>
                            <stop offset="100%" stop-color="#4e9533"/>
                        </linearGradient>
                    </defs>

                    <!-- Roof -->
                    <polygon points="250,20 425,125 75,125" fill="#1b3810"/>

                    <!-- Main Frame Box -->
                    <rect x="105" y="145" width="290" height="260" fill="none" stroke="#1b3810" stroke-width="12"/>

                    <!-- Heads -->
                    <circle cx="250" cy="205" r="36" fill="url(#headGradCenter)"/>
                    <circle cx="168" cy="235" r="35" fill="url(#headGradLeft)"/>
                    <circle cx="330" cy="235" r="35" fill="url(#headGradRight)"/>

                    <!-- Body / Arm Structure -->
                    <polygon points="105,320 63,340 63,380 115,380 115,340" fill="#008843"/>
                    <polygon points="63,340 105,320 105,342 63,362" fill="#006330"/>

                    <polygon points="395,320 437,340 437,380 385,380 385,340" fill="#008843"/>
                    <polygon points="437,340 395,320 395,342 437,362" fill="#006330"/>

                    <path d="M 105,320 L 168,285 L 250,260 L 332,285 L 395,320 L 395,399 L 310,399 L 310,295 L 282,295 L 282,399 L 218,399 L 218,295 L 190,295 L 190,399 L 105,399 Z" fill="#009c48"/>

                    <path d="M 250,260 L 282,295 L 282,399 L 250,399 Z" fill="#00833d" opacity="0.3"/>
                    <path d="M 168,285 L 190,295 L 190,399 L 168,399 Z" fill="#00833d" opacity="0.2"/>
                    <path d="M 332,285 L 355,295 L 355,399 L 332,399 Z" fill="#00833d" opacity="0.2"/>
                </svg>
            </div>
            <div>
                <span class="font-black text-xl leading-none block text-[#1b3810] tracking-wider">OHANA</span>
                <span class="text-[9px] tracking-wider text-[#1b3810] uppercase block font-bold mt-0.5">BUSINESS CONSULTANCY INC.</span>
            </div>
        </div>

        <nav class="hidden md:flex space-x-8 text-sm font-medium items-center">
            <a href="#" data-target="home" class="nav-link relative text-[#1c482c] font-semibold pb-1 group transition-colors">
                Home
                <span class="indicator absolute left-0 bottom-0 w-full h-[2px] bg-[#1c482c] transition-all duration-300"></span>
            </a>
            <a href="#about" data-target="about" class="nav-link relative text-gray-600 hover:text-[#1c482c] pb-1 group transition-colors">
                About Us
                <span class="indicator absolute left-0 bottom-0 w-0 h-[2px] bg-[#1c482c] transition-all duration-300 group-hover:w-full"></span>
            </a>
            <a href="#services" data-target="services" class="nav-link relative text-gray-600 hover:text-[#1c482c] pb-1 group transition-colors">
                Services
                <span class="indicator absolute left-0 bottom-0 w-0 h-[2px] bg-[#1c482c] transition-all duration-300 group-hover:w-full"></span>
            </a>
            <a href="#location" data-target="location" class="nav-link relative text-gray-600 hover:text-[#1c482c] pb-1 group transition-colors">
                Location
                <span class="indicator absolute left-0 bottom-0 w-0 h-[2px] bg-[#1c482c] transition-all duration-300 group-hover:w-full"></span>
            </a>
            <a href="#faq" data-target="faq" class="nav-link relative text-gray-600 hover:text-[#1c482c] pb-1 group transition-colors">
                FAQ
                <span class="indicator absolute left-0 bottom-0 w-0 h-[2px] bg-[#1c482c] transition-all duration-300 group-hover:w-full"></span>
            </a>
        </nav>

        <!-- Login & Account Creation Buttons -->
        <div class="flex items-center gap-3">
            <a href="login.php" class="text-xs font-semibold text-[#1c482c] hover:text-[#153721] px-3 py-2 rounded-lg transition-colors">
                Client Sign In
            </a>
            <a href="client_create_account.php" class="bg-[#1c482c] hover:bg-[#153721] text-white text-xs font-semibold py-3 px-5 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5">
                Create Account
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="max-w-7xl mx-auto px-6 py-16 grid md:grid-cols-12 gap-12 items-center">
        <div class="md:col-span-7 space-y-6">
            <div class="inline-flex items-center gap-2 bg-[#e8ded3] text-[#1c482c] text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-wider shadow-2xs">
                <i class="fa-solid fa-certificate"></i> DOCUMENT SERVICES & CONSULTATION
            </div>
            <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 leading-[1.15]">
                Your Reliable Partner in Business & Public Services.
            </h1>
            <p class="text-gray-600 text-sm md:text-base leading-relaxed max-w-xl">
                We handle government, business, and document processing needs efficiently, ensuring convenience and accuracy for every individual and enterprise we serve.
            </p>
            <div class="flex flex-wrap gap-3 pt-2">
                <a href="client_create_account.php" class="bg-[#8b4513] hover:bg-[#72380f] text-white text-xs font-semibold py-3.5 px-7 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Get Started / Register
                </a>
                <a href="#services" class="bg-[#ebd9cb] hover:bg-[#e0ccbe] text-gray-800 text-xs font-semibold py-3.5 px-7 rounded-xl transition-all duration-300">
                    Explore Services
                </a>
            </div>
            
            <div class="pt-8 grid grid-cols-3 gap-6 border-t border-gray-300/60 max-w-lg">
                <div class="space-y-0.5">
                    <p class="text-lg font-extrabold text-gray-900">Fast & Easy</p>
                    <p class="text-xs text-gray-500 font-medium">Document Processing</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-lg font-extrabold text-gray-900">100% Secure</p>
                    <p class="text-xs text-gray-500 font-medium">Confidential Handling</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-lg font-extrabold text-gray-900">Trusted Partner</p>
                    <p class="text-xs text-gray-500 font-medium">By Thousands</p>
                </div>
            </div>
        </div>

        <div class="md:col-span-5 flex justify-center">
            <div class="bg-white p-7 rounded-3xl shadow-xl shadow-stone-200/50 border border-gray-100 max-w-sm w-full text-center space-y-5 transition-transform duration-300 hover:shadow-2xl">
                <!-- Main Hero Business Card Logo -->
                <div class="w-56 h-56 mx-auto flex flex-col items-center justify-center p-4 bg-[#fbf9f6] rounded-2xl border border-gray-100 shadow-inner">
                    <svg viewBox="0 0 500 500" class="w-full h-36 drop-shadow-xs">
                        <defs>
                            <linearGradient id="headGradLeft2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#80c242"/>
                                <stop offset="100%" stop-color="#4a9332"/>
                            </linearGradient>
                            <linearGradient id="headGradCenter2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#82c442"/>
                                <stop offset="100%" stop-color="#519835"/>
                            </linearGradient>
                            <linearGradient id="headGradRight2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#80c242"/>
                                <stop offset="100%" stop-color="#4e9533"/>
                            </linearGradient>
                        </defs>

                        <!-- Roof -->
                        <polygon points="250,20 425,125 75,125" fill="#1b3810"/>

                        <!-- Frame -->
                        <rect x="105" y="145" width="290" height="260" fill="none" stroke="#1b3810" stroke-width="12"/>

                        <!-- Heads -->
                        <circle cx="250" cy="205" r="36" fill="url(#headGradCenter2)"/>
                        <circle cx="168" cy="235" r="35" fill="url(#headGradLeft2)"/>
                        <circle cx="330" cy="235" r="35" fill="url(#headGradRight2)"/>

                        <!-- Arms & Body -->
                        <polygon points="105,320 63,340 63,380 115,380 115,340" fill="#008843"/>
                        <polygon points="63,340 105,320 105,342 63,362" fill="#006330"/>

                        <polygon points="395,320 437,340 437,380 385,380 385,340" fill="#008843"/>
                        <polygon points="437,340 395,320 395,342 437,362" fill="#006330"/>

                        <path d="M 105,320 L 168,285 L 250,260 L 332,285 L 395,320 L 395,399 L 310,399 L 310,295 L 282,295 L 282,399 L 218,399 L 218,295 L 190,295 L 190,399 L 105,399 Z" fill="#009c48"/>

                        <path d="M 250,260 L 282,295 L 282,399 L 250,399 Z" fill="#00833d" opacity="0.3"/>
                        <path d="M 168,285 L 190,295 L 190,399 L 168,399 Z" fill="#00833d" opacity="0.2"/>
                        <path d="M 332,285 L 355,295 L 355,399 L 332,399 Z" fill="#00833d" opacity="0.2"/>
                    </svg>
                    <span class="text-xl font-black text-[#1b3810] tracking-widest mt-1 block">OHANA</span>
                    <span class="text-[8px] font-bold text-[#1b3810] tracking-tight block">BUSINESS CONSULTANCY INC.</span>
                </div>
                <div>
                    <h3 class="font-bold text-base text-gray-900">OHANA Business Consultancy Inc.</h3>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Your one-stop destination for all document processing, legal consultation, and corporate assistance needs.</p>
                </div>
                <div class="bg-[#f9f7f4] p-3.5 rounded-xl text-left text-xs space-y-2 text-gray-600 border border-gray-200/60">
                    <p class="flex items-center gap-2"><i class="fa-regular fa-clock text-[#1c482c]"></i><span class="font-semibold text-gray-800">Operating Hours:</span> Mon - Sat: 8:00 AM - 5:00 PM</p>
                    <p class="flex items-center gap-2"><i class="fa-solid fa-location-dot text-[#1c482c]"></i><span class="font-semibold text-gray-800">Location:</span> Main Street, Business District</p>
                </div>
                <div class="flex justify-center space-x-4 text-gray-400 text-base pt-1">
                    <a href="#" class="w-9 h-9 rounded-full bg-[#f9f7f4] flex items-center justify-center hover:bg-[#1c482c] hover:text-white transition-all"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="w-9 h-9 rounded-full bg-[#f9f7f4] flex items-center justify-center hover:bg-[#1c482c] hover:text-white transition-all"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="w-9 h-9 rounded-full bg-[#f9f7f4] flex items-center justify-center hover:bg-[#1c482c] hover:text-white transition-all"><i class="fa-regular fa-envelope"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section id="about" class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <span class="text-xs font-bold text-[#1c482c] uppercase tracking-widest bg-emerald-100/60 px-3 py-1 rounded-full">Core Purpose & Direction</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-2">Our Mission & Vision</h2>
            <p class="text-xs md:text-sm text-gray-500 max-w-lg mx-auto mt-1.5">Guiding our operations to deliver top-notch document services with integrity and speed.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-2xl shadow-md shadow-stone-200/40 border border-gray-100 space-y-4 hover:shadow-lg transition-all">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-[#1c482c] flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900">Empowering Individuals & Local Enterprises</h3>
                <p class="text-xs md:text-sm text-gray-600 leading-relaxed">
                    Our mission is to streamline document handling and regulatory requirements, giving clients full peace of mind through reliable, legal, and rapid execution of public and private paperwork.
                </p>
                <a href="#" class="text-xs text-[#1c482c] font-bold flex items-center gap-1.5 pt-2 hover:translate-x-1 transition-transform">
                    <span>Learn About Our Commitment</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-md shadow-stone-200/40 border border-gray-100 space-y-4 hover:shadow-lg transition-all">
                <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-sm shadow-xs">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900">To Be the Premier Service Provider Nationally</h3>
                <p class="text-xs md:text-sm text-gray-600 leading-relaxed">
                    We envision a future where public and corporate documentation is hassle-free, fully digital, and easily accessible to every citizen and business entity across the region.
                </p>
                <a href="#" class="text-xs text-orange-600 font-bold flex items-center gap-1.5 pt-2 hover:translate-x-1 transition-transform">
                    <span>Read Our Long-Term Goals</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 flex items-start space-x-3.5 shadow-xs hover:shadow-md transition-all">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-[#1c482c] flex items-center justify-center text-sm shrink-0"><i class="fa-solid fa-shield"></i></div>
                <div>
                    <h4 class="font-bold text-xs text-gray-900">Integrity</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Transparent process & honest communication.</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 flex items-start space-x-3.5 shadow-xs hover:shadow-md transition-all">
                <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-sm shrink-0"><i class="fa-solid fa-bolt"></i></div>
                <div>
                    <h4 class="font-bold text-xs text-gray-900">Efficiency</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Fast turnaround times for all transactions.</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 flex items-start space-x-3.5 shadow-xs hover:shadow-md transition-all">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm shrink-0"><i class="fa-solid fa-handshake"></i></div>
                <div>
                    <h4 class="font-bold text-xs text-gray-900">Reliability</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Dependable team for critical documents.</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 flex items-start space-x-3.5 shadow-xs hover:shadow-md transition-all">
                <div class="w-9 h-9 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center text-sm shrink-0"><i class="fa-solid fa-heart"></i></div>
                <div>
                    <h4 class="font-bold text-xs text-gray-900">Customer First</h4>
                    <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Tailored support for individual needs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <span class="text-xs font-bold text-[#1c482c] uppercase tracking-widest bg-emerald-100/60 px-3 py-1 rounded-full">What We Offer</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-2">Comprehensive Services for Everyone</h2>
            <p class="text-xs md:text-sm text-gray-500 max-w-lg mx-auto mt-1.5">Explore our range of document processing, corporate, and public support services.</p>
        </div>

        <div class="grid md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 flex flex-col justify-between shadow-md shadow-stone-200/40 hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-[#1c482c] flex items-center justify-center text-sm mb-4 shadow-xs"><i class="fa-solid fa-passport"></i></div>
                    <h3 class="font-bold text-base text-gray-900 mb-3">Primary Documentation</h3>
                    <ul class="text-xs text-gray-600 space-y-2.5 list-disc list-inside">
                        <li>PSA Birth, Marriage, Death Cert.</li>
                        <li>NBI & Police Clearance</li>
                        <li>Passport Appointment Assistance</li>
                        <li>Government ID Applications</li>
                    </ul>
                </div>
                <a href="login.php" class="mt-8 block text-center py-2.5 bg-[#f4efea] hover:bg-[#1c482c] hover:text-white text-gray-700 text-xs rounded-xl font-semibold transition-all">Inquire Document</a>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 flex flex-col justify-between shadow-md shadow-stone-200/40 hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-sm mb-4 shadow-xs"><i class="fa-solid fa-briefcase"></i></div>
                    <h3 class="font-bold text-base text-gray-900 mb-3">Business & Corporate Services</h3>
                    <ul class="text-xs text-gray-600 space-y-2.5 list-disc list-inside">
                        <li>DTI / SEC Business Registration</li>
                        <li>Mayor's Permit & BIR Filing</li>
                        <li>SSS, PhilHealth, Pag-IBIG Setup</li>
                        <li>Annual Business Renewal</li>
                    </ul>
                </div>
                <a href="login.php" class="mt-8 block text-center py-2.5 bg-[#f4efea] hover:bg-[#1c482c] hover:text-white text-gray-700 text-xs rounded-xl font-semibold transition-all">Register Business</a>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 flex flex-col justify-between shadow-md shadow-stone-200/40 hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-yellow-100 text-yellow-700 flex items-center justify-center text-sm mb-4 shadow-xs"><i class="fa-solid fa-file-contract"></i></div>
                    <h3 class="font-bold text-base text-gray-900 mb-3">Legal & Notarial Assistance</h3>
                    <ul class="text-xs text-gray-600 space-y-2.5 list-disc list-inside">
                        <li>Affidavits & Special Power of Attorney</li>
                        <li>Contracts & Agreements Drafting</li>
                        <li>Notarization Services</li>
                        <li>Deed of Sale Consultation</li>
                    </ul>
                </div>
                <a href="login.php" class="mt-8 block text-center py-2.5 bg-[#f4efea] hover:bg-[#1c482c] hover:text-white text-gray-700 text-xs rounded-xl font-semibold transition-all">Get Legal Help</a>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 flex flex-col justify-between shadow-md shadow-stone-200/40 hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-sm mb-4 shadow-xs"><i class="fa-solid fa-print"></i></div>
                    <h3 class="font-bold text-base text-gray-900 mb-3">Online & Printing Services</h3>
                    <ul class="text-xs text-gray-600 space-y-2.5 list-disc list-inside">
                        <li>High-Quality Document Printing</li>
                        <li>Scanning & Digital Encoding</li>
                        <li>Online Form Fill-outs</li>
                        <li>ID Photo & Layout Creation</li>
                    </ul>
                </div>
                <a href="login.php" class="mt-8 block text-center py-2.5 bg-[#f4efea] hover:bg-[#1c482c] hover:text-white text-gray-700 text-xs rounded-xl font-semibold transition-all">Print or Edit</a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <span class="text-xs font-bold text-[#1c482c] uppercase tracking-widest bg-emerald-100/60 px-3 py-1 rounded-full">Simple Step-By-Step Process</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-2">How to Get Started with OHANA</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8 text-center">
            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-md shadow-stone-200/40 hover:shadow-lg transition-all">
                <div class="w-12 h-12 rounded-2xl bg-[#8b4513] text-white font-bold flex items-center justify-center mx-auto mb-4 text-sm shadow-md">1</div>
                <h3 class="font-bold text-base text-gray-900 mb-2">Create Account & Register</h3>
                <p class="text-xs md:text-sm text-gray-500 leading-relaxed">Sign up on our portal or visit our office branch directly with your initial requirements.</p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-md shadow-stone-200/40 hover:shadow-lg transition-all">
                <div class="w-12 h-12 rounded-2xl bg-[#1c482c] text-white font-bold flex items-center justify-center mx-auto mb-4 text-sm shadow-md">2</div>
                <h3 class="font-bold text-base text-gray-900 mb-2">Document Processing & Review</h3>
                <p class="text-xs md:text-sm text-gray-500 leading-relaxed">Our experienced team handles the verification and coordination with government agencies.</p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-md shadow-stone-200/40 hover:shadow-lg transition-all">
                <div class="w-12 h-12 rounded-2xl bg-[#8b4513] text-white font-bold flex items-center justify-center mx-auto mb-4 text-sm shadow-md">3</div>
                <h3 class="font-bold text-base text-gray-900 mb-2">Receive & Collect Documents</h3>
                <p class="text-xs md:text-sm text-gray-500 leading-relaxed">Get your processed documents ready for pickup at our office or sent via courier.</p>
            </div>
        </div>
    </section>

    <!-- Location & Contact Info Section -->
    <section id="location" class="max-w-7xl mx-auto px-6 py-16 grid md:grid-cols-12 gap-8 items-start">
        <div class="md:col-span-7 bg-white p-8 rounded-3xl border border-gray-100 shadow-xl shadow-stone-200/50 space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-extrabold text-gray-900">Malolos Branch Location</h2>
                <span class="text-xs bg-emerald-100 text-[#1c482c] px-3 py-1 rounded-full font-bold">Open Today</span>
            </div>
            <p class="text-xs text-gray-500">Conveniently situated near major terminals, schools, and city offices.</p>
            
            <div class="space-y-4 text-xs text-gray-700">
                <div class="flex items-start gap-3 bg-[#fbf9f6] p-4 rounded-2xl border border-gray-100">
                    <i class="fa-solid fa-location-dot text-[#1c482c] text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold block text-gray-900 mb-0.5">Office Address</span>
                        <p class="text-gray-500">Ground Floor Topico Bldg., Malolos, Bulacan, Philippines</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-[#fbf9f6] p-4 rounded-2xl border border-gray-100">
                    <i class="fa-solid fa-phone text-[#1c482c] text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold block text-gray-900 mb-0.5">Phone & Landline</span>
                        <p class="text-gray-500">+63 912 345 6789 / (02) 8123 4567</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-[#fbf9f6] p-4 rounded-2xl border border-gray-100">
                    <i class="fa-regular fa-clock text-[#1c482c] text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold block text-gray-900 mb-0.5">Operating Hours</span>
                        <p class="text-gray-500">Always Open • Accepting inquiries 24/7 online</p>
                    </div>
                </div>
            </div>

            <div class="pt-2 flex gap-3">
                <a href="tel:+639123456789" class="flex-1 text-center bg-[#1c482c] hover:bg-[#153721] text-white py-3 text-xs font-semibold rounded-xl shadow-md transition-all">Call Us Direct</a>
                <a href="mailto:info@ohanaconsultancy.com" class="flex-1 text-center bg-[#f4efea] hover:bg-[#e8ded3] py-3 text-xs font-semibold rounded-xl text-gray-700 transition-colors">Email Us</a>
            </div>
        </div>

        <div class="md:col-span-5 space-y-6">
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xl shadow-stone-200/50 space-y-3">
                <span class="text-xs font-bold text-gray-800 block px-2">Locate Our Office</span>
                <div class="w-full h-64 rounded-2xl overflow-hidden relative shadow-inner border border-gray-200/60">
                    <iframe 
                        src="https://maps.google.com/maps?q=Topico+Bldg,+Malolos,+Bulacan,+Philippines&t=&z=16&ie=UTF8&iwloc=&output=embed" 
                        class="w-full h-full border-0 rounded-2xl" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        title="OHANA Business Consultancy - Malolos Branch">
                    </iframe>
                </div>
                <a href="https://www.google.com/maps/dir/?api=1&destination=Topico+Bldg,+Malolos,+Bulacan,+Philippines" target="_blank" rel="noopener noreferrer" class="text-xs text-[#1c482c] font-bold block text-center pt-2 pb-1 hover:underline">
                    Open in Google Maps & Navigation <i class="fa-solid fa-arrow-up-right-from-square text-[9px] ml-1"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Why Businesses Trust OHANA -->
    <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <span class="text-xs font-bold text-[#1c482c] uppercase tracking-widest bg-emerald-100/60 px-3 py-1 rounded-full">Our Guarantees</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-2">Why Businesses Trust OHANA</h2>
            <p class="text-xs md:text-sm text-gray-500 max-w-lg mx-auto mt-1.5">We are committed to delivering exceptional reliability and accuracy in every document we handle.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 text-left space-y-2.5 shadow-md shadow-stone-200/40 hover:-translate-y-1 transition-all">
                <h4 class="font-bold text-sm text-gray-900">100% Legit & Legally Compliant</h4>
                <p class="text-xs text-gray-500 leading-relaxed">All documents are processed strictly following national agency guidelines.</p>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 text-left space-y-2.5 shadow-md shadow-stone-200/40 hover:-translate-y-1 transition-all">
                <h4 class="font-bold text-sm text-gray-900">Affordable & Transparent</h4>
                <p class="text-xs text-gray-500 leading-relaxed">No hidden fees or unexpected charges. Clear pricing from the start.</p>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 text-left space-y-2.5 shadow-md shadow-stone-200/40 hover:-translate-y-1 transition-all">
                <h4 class="font-bold text-sm text-gray-900">Dedicated Support</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Personalized assistance for complex transactions and follow-ups.</p>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 text-left space-y-2.5 shadow-md shadow-stone-200/40 hover:-translate-y-1 transition-all">
                <h4 class="font-bold text-sm text-gray-900">Confidential & Secure</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Your personal data and documents are handled with strict privacy.</p>
            </div>
        </div>
    </section>

    <!-- FAQ Accordion Section -->
    <section id="faq" class="max-w-4xl mx-auto px-6 py-16">
        <div class="text-center mb-12">
            <span class="text-xs font-bold text-[#1c482c] uppercase tracking-widest bg-emerald-100/60 px-3 py-1 rounded-full">Got Questions?</span>
            <h2 class="text-3xl font-extrabold text-gray-900 mt-2">Frequently Asked Questions</h2>
            <p class="text-xs md:text-sm text-gray-500 max-w-lg mx-auto mt-1.5">Find quick answers to common questions about our document processing services.</p>
        </div>

        <div class="space-y-4 text-xs">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-md shadow-stone-200/40">
                <p class="font-bold text-gray-900 text-sm">Q: How long does it take to process PSA birth or marriage certificates?</p>
                <p class="text-gray-600 mt-1.5 leading-relaxed">A: Standard processing typically takes 3 to 5 working days depending on location and agency availability.</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-md shadow-stone-200/40">
                <p class="font-bold text-gray-900 text-sm">Q: Do I need to visit your physical office to request a service?</p>
                <p class="text-gray-600 mt-1.5 leading-relaxed">A: You can visit our main office branch or create an account online to manage your document requirements.</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-md shadow-stone-200/40">
                <p class="font-bold text-gray-900 text-sm">Q: What are the payment methods accepted by OHANA Business Consultancy?</p>
                <p class="text-gray-600 mt-1.5 leading-relaxed">A: We accept GCash, Maya, Bank Transfer, and Cash payments at our main office branch.</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-md shadow-stone-200/40">
                <p class="font-bold text-gray-900 text-sm">Q: Can you handle urgent or rush business registration?</p>
                <p class="text-gray-600 mt-1.5 leading-relaxed">A: Yes, we offer expedited processing options for corporate and business registration requirements.</p>
            </div>
        </div>
    </section>

    <!-- Bottom Call-to-Action Banner -->
    <section class="max-w-7xl mx-auto px-6 my-12">
        <div class="bg-gradient-to-r from-[#1c482c] via-[#2d5a3f] to-[#72380f] rounded-3xl p-8 md:p-12 text-white flex flex-col md:flex-row justify-between items-center gap-8 shadow-xl">
            <div class="space-y-2 text-center md:text-left">
                <span class="text-xs font-bold text-emerald-200 uppercase tracking-widest block bg-white/10 w-fit px-3 py-1 rounded-full mx-auto md:mx-0">Get Started Today</span>
                <h3 class="text-2xl md:text-3xl font-extrabold">Have Documents or Consultation to Settle Today?</h3>
                <p class="text-xs md:text-sm text-emerald-100/90 max-w-xl">Create your account now or visit our office for immediate support and rapid document turnaround.</p>
            </div>
            <div class="flex flex-wrap gap-3 shrink-0 justify-center">
                <a href="client_create_account.php" class="bg-white text-gray-900 hover:bg-gray-100 text-xs font-bold py-3.5 px-7 rounded-xl shadow-md transition-all">
                    Register Account
                </a>
                <a href="tel:+639123456789" class="bg-white/10 hover:bg-white/20 text-white text-xs font-semibold py-3.5 px-7 rounded-xl border border-white/25 transition-all">
                    Call Direct Line
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#f0eae1] border-t border-gray-200/80 pt-16 pb-8 text-xs text-gray-600">
        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-4 gap-10 mb-12">
            <div class="space-y-4">
                <div class="flex items-center space-x-2.5">
                    <div class="h-8 w-8">
                        <svg viewBox="0 0 500 500" class="w-full h-full">
                            <polygon points="250,20 425,125 75,125" fill="#1b3810"/>
                            <rect x="105" y="145" width="290" height="260" fill="none" stroke="#1b3810" stroke-width="14"/>
                            <circle cx="250" cy="205" r="36" fill="#66bb32"/>
                            <circle cx="168" cy="235" r="35" fill="#66bb32"/>
                            <circle cx="330" cy="235" r="35" fill="#66bb32"/>
                            <polygon points="105,320 63,340 63,380 115,380 115,340" fill="#008843"/>
                            <polygon points="395,320 437,340 437,380 385,380 385,340" fill="#008843"/>
                            <path d="M 105,320 L 168,285 L 250,260 L 332,285 L 395,320 L 395,399 L 310,399 L 310,295 L 282,295 L 282,399 L 218,399 L 218,295 L 190,295 L 190,399 L 105,399 Z" fill="#009c48"/>
                        </svg>
                    </div>
                    <span class="font-bold text-sm text-[#1c482c] tracking-wide">OHANA Consultancy</span>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Your dependable partner for all public documents, business permits, and legal consultancy needs.
                </p>
            </div>

            <div>
                <h4 class="font-bold text-gray-900 mb-4 text-xs uppercase tracking-wider">Account Portal</h4>
                <ul class="space-y-2.5 text-xs">
                    <li><a href="login.php" class="hover:text-[#1c482c] hover:underline transition-colors">Client Login</a></li>
                    <li><a href="client_create_account.php" class="hover:text-[#1c482c] hover:underline transition-colors">Create Client Account</a></li>
                    <li><a href="employee_login.php" class="hover:text-[#1c482c] hover:underline transition-colors"><i class="fa-solid fa-id-badge text-[10px]"></i> Employee & Supervisor Portal</a></li>
                    <!-- Admin Portal Link -->
                    <li><a href="admin_login.php" class="hover:text-[#1c482c] font-semibold hover:underline transition-colors text-emerald-800"><i class="fa-solid fa-lock text-[10px]"></i> Admin Portal Access</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold text-gray-900 mb-4 text-xs uppercase tracking-wider">Services</h4>
                <ul class="space-y-2.5 text-xs">
                    <li><a href="#services" class="hover:text-[#1c482c] hover:underline transition-colors">Primary Documentation</a></li>
                    <li><a href="#services" class="hover:text-[#1c482c] hover:underline transition-colors">Business & Corporate</a></li>
                    <li><a href="#services" class="hover:text-[#1c482c] hover:underline transition-colors">Legal Assistance</a></li>
                    <li><a href="#services" class="hover:text-[#1c482c] hover:underline transition-colors">Printing & Layout</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold text-gray-900 mb-4 text-xs uppercase tracking-wider">Contact Info</h4>
                <ul class="space-y-2.5 text-xs text-gray-500">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-location-dot w-4 text-gray-400"></i> Ground Floor Topico Bldg., Malolos, Bulacan</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-phone w-4 text-gray-400"></i> +63 912 345 6789</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-envelope w-4 text-gray-400"></i> info@ohanaconsultancy.com</li>
                </ul>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 border-t border-gray-300/60 pt-6 text-center text-xs text-gray-400 font-medium">
            &copy; 2026 OHANA Business Consultancy Inc. All Rights Reserved.
        </div>
    </footer>

    <!-- Script to dynamically update active navigation highlight -->
    <script>
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let scrollY = window.pageYOffset;

            sections.forEach(section => {
                const sectionHeight = section.offsetHeight;
                const sectionTop = section.offsetTop - 100;
                const sectionId = section.getAttribute('id');

                if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                    navLinks.forEach(link => {
                        link.classList.remove('text-[#1c482c]', 'font-semibold');
                        link.classList.add('text-gray-600');
                        const indicator = link.querySelector('.indicator');
                        indicator.classList.remove('w-full');
                        indicator.classList.add('w-0');

                        if (link.getAttribute('data-target') === sectionId) {
                            link.classList.remove('text-gray-600');
                            link.classList.add('text-[#1c482c]', 'font-semibold');
                            const activeIndicator = link.querySelector('.indicator');
                            activeIndicator.classList.remove('w-0');
                            activeIndicator.classList.add('w-full');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
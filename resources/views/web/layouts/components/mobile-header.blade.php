<!-- Mobile Header -->
<header class="block lg:hidden py-2 bg-white text-black">
    <nav>
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center">

                <a href="{{ route('web.home')}}" class="ml-3 w-28 block">
                    <img src="img/homepage/logo-new.png" alt="eltawkeel Logo" class="w-full h-auto" />
                </a>

                <ul class="list-none flex items-center justify-center gap-4">
                    <li>
                        <a href="{{ $setting->facebook }}" target="_blank" rel="noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11.4" height="21.3"
                                viewBox="0 0 11.449 21.377" fill="#d03b37">
                                <path
                                    d="M12.308,12.025,12.9,8.156H9.19V5.645a1.934,1.934,0,0,1,2.181-2.09h1.688V.261a20.58,20.58,0,0,0-3-.261C7.006,0,5.008,1.853,5.008,5.207V8.156h-3.4v3.869h3.4v9.352H9.19V12.025Z"
                                    transform="translate(-1.609)" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ $setting->instagram }}" target="_blank" rel="noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17.8" height="17.8"
                                viewBox="0 0 17.869 17.869" fill="#d03b37">
                                <path
                                    d="M13.031,0h-8.2A4.843,4.843,0,0,0,0,4.837v8.2a4.842,4.842,0,0,0,4.837,4.837h8.2a4.842,4.842,0,0,0,4.837-4.837v-8.2A4.843,4.843,0,0,0,13.031,0Zm-4.1,13.821A4.886,4.886,0,1,1,13.82,8.935,4.891,4.891,0,0,1,8.934,13.821Zm5-8.62A1.444,1.444,0,1,1,15.38,3.757,1.445,1.445,0,0,1,13.937,5.2Z"
                                    transform="translate(0.329 0)" />
                                <path d="M149.51,146.02a3.49,3.49,0,1,0,3.49,3.49A3.494,3.494,0,0,0,149.51,146.02Z"
                                    transform="translate(-140.247 -140.575)" />
                                <circle cx="0.4" cy="0.4" r="0.4" transform="translate(13.6 3.4)" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ $setting->youtube }}" target="_blank" rel="noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="19.6" height="13.7"
                                viewBox="0 0 19.607 13.728" fill="#d03b37">
                                <path
                                    d="M9.8,13.728c-.061,0-6.139,0-7.672-.42A2.457,2.457,0,0,1,.4,11.58,25.918,25.918,0,0,1,0,6.864,26.012,26.012,0,0,1,.4,2.149,2.514,2.514,0,0,1,2.132.4,59.846,59.846,0,0,1,9.8,0c.061,0,6.155,0,7.672.42A2.455,2.455,0,0,1,19.2,2.149a24.684,24.684,0,0,1,.4,4.732,26.058,26.058,0,0,1-.4,4.716,2.457,2.457,0,0,1-1.729,1.728A59.836,59.836,0,0,1,9.8,13.728ZM7.85,3.925V9.8l5.1-2.939-5.1-2.94Z" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

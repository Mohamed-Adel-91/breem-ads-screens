<aside class="sidebar">
    <div class="container">
        <div class="flex justify-between items-center">
            <div class="flex flex-col justify-between items-start">
                <a class="ml-3 w-28 block" href="{{ route('web.home') }}">
                    <img src="img/homepage/logo-new.png" alt="التوكيل" />
                </a>
            </div>
            @auth('user')
            @php $user = auth('user')->user(); @endphp
            <div class="sidebar-profile">
                <a href="{{ route('web.profile') }}">
                    <div class="sidebar-profile-img">
                        <img src="{{ $user?->image_path ?? 'img/homepage/profile-pic.png' }}"
                            alt="{{ $user?->full_name }}" />
                    </div>
                    <div>
                        <h5 class="font-arabic">{{ $user?->full_name }}</h5>
                    </div>
                </a>


            </div>
            @endauth
        </div>
        <div class="sidebar-items">
            <div class="sidebar-items-grid">
                <div class="sidebar-item">
                    <a href="{{ route('web.new-cars') }}" class="sidebar-a">
                        <span class="sidebar-item-icon">
                            <svg id="Group_25293" data-name="Group 25293" xmlns="http://www.w3.org/2000/svg"
                                width="52.264" height="53.353" viewBox="0 0 52.264 53.353">
                                <defs>
                                    <clipPath id="clipPath">
                                        <rect id="Rectangle_2860" data-name="Rectangle 2860" width="52.264"
                                            height="53.353" fill="none" stroke="#d03b37" stroke-width="2"></rect>
                                    </clipPath>
                                </defs>
                                <g id="Group_25292" data-name="Group 25292" clip-path="url(#clipPath)">
                                    <path id="Path_15868" data-name="Path 15868"
                                        d="M53.055,120.663l-1.439,5.353a8.205,8.205,0,0,0-1.853,5.194v16.681a2.185,2.185,0,0,0,2.179,2.179h7.626a2.185,2.185,0,0,0,2.179-2.179v-4.357H83.534v4.357a2.185,2.185,0,0,0,2.179,2.179h7.626a2.185,2.185,0,0,0,2.179-2.179V131.21a8.205,8.205,0,0,0-1.853-5.194l-3.5-13.026a4.357,4.357,0,0,0-4.208-3.227H67.194"
                                        transform="translate(-44.343 -97.806)" fill="none" stroke="#d03b37"
                                        stroke-miterlimit="10" stroke-width="2"></path>
                                    <path id="Path_15869" data-name="Path 15869"
                                        d="M36.845,29.763a11.687,11.687,0,0,0,7.081,7.081,11.686,11.686,0,0,0-7.081,7.081,11.686,11.686,0,0,0-7.081-7.081A11.687,11.687,0,0,0,36.845,29.763Z"
                                        transform="translate(-26.522 -26.521)" fill="none" stroke="#d03b37"
                                        stroke-miterlimit="10" stroke-width="2"></path>
                                    <path id="Path_15870" data-name="Path 15870"
                                        d="M140.476,169.567H121l2.636-9.8h25.5l2.636,9.8"
                                        transform="translate(-107.821 -142.359)" fill="none" stroke="#d03b37"
                                        stroke-miterlimit="10" stroke-width="2"></path>
                                    <line id="Line_1653" data-name="Line 1653" x2="2.179"
                                        transform="translate(34.834 27.208)" fill="none" stroke="#d03b37"
                                        stroke-miterlimit="10" stroke-width="2"></line>
                                    <path id="Path_15871" data-name="Path 15871" d="M20,0"
                                        transform="translate(26.817 4.332)" fill="none" stroke="#d03b37"
                                        stroke-width="2"></path>
                                    <path id="Path_15872" data-name="Path 15872" d="M0,0"
                                        transform="translate(47.907 5.421)" fill="none" stroke="#d03b37"
                                        stroke-width="2"></path>
                                    <circle id="Ellipse_521" data-name="Ellipse 521" cx="3.813" cy="3.813"
                                        r="3.813" transform="translate(10.868 32.655)" fill="none" stroke="#d03b37"
                                        stroke-miterlimit="10" stroke-width="2"></circle>
                                    <circle id="Ellipse_522" data-name="Ellipse 522" cx="3.813" cy="3.813"
                                        r="3.813" transform="translate(38.102 32.655)" fill="none" stroke="#d03b37"
                                        stroke-miterlimit="10" stroke-width="2"></circle>
                                </g>
                            </svg>
                        </span>
                        <span class="sidebar-item-label font-arabic">السيارات الجديدة</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="{{ route('web.comparison') }}" class="sidebar-a">
                        <span class="sidebar-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="19.9" height="13.628"
                                viewBox="0 0 22 22">
                                <g id="sort" transform="translate(-143.756 525.756) rotate(-90)">
                                    <rect id="Rectangle_1361" data-name="Rectangle 1361" width="22"
                                        height="22" transform="translate(503.756 143.756)" fill="none"></rect>
                                    <path id="Path_7564" data-name="Path 7564"
                                        d="M550.389,167.915a.6.6,0,0,0-.844,0l-1.667,1.667V157.6a.6.6,0,1,0-1.193,0v11.985l-1.667-1.667a.6.6,0,0,0-.844.844l2.685,2.685a.6.6,0,0,0,.844,0l2.685-2.685A.6.6,0,0,0,550.389,167.915Z"
                                        transform="translate(-28.051 -9.106)" fill="#d03b37"></path>
                                    <path id="Path_7565" data-name="Path 7565"
                                        d="M520.389,156.86l-2.684-2.685a.6.6,0,0,0-.092-.075.467.467,0,0,0-.047-.026.6.6,0,0,0-.055-.029,282.616,282.616,0,0,0-.111-.034.606.606,0,0,0-.234,0c-.017,0-.033.01-.05.015a.529.529,0,0,0-.061.019c-.019.008-.037.019-.056.03a.5.5,0,0,0-.047.025.6.6,0,0,0-.091.075l-2.685,2.685a.6.6,0,1,0,.844.844l1.667-1.667v11.985a.6.6,0,1,0,1.193,0V156.038l1.667,1.667a.6.6,0,1,0,.844-.844Z"
                                        transform="translate(-7.001 -7.001)" fill="#d03b37"></path>
                                </g>
                            </svg>
                        </span>
                        <span class="sidebar-item-label font-arabic">مقارنات السيارات</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="{{ route('web.news') }}" class="sidebar-a">
                        <span class="sidebar-item-icon">
                            <svg id="Group_25305" data-name="Group 25305" xmlns="http://www.w3.org/2000/svg"
                                width="52.787" height="40.465" viewBox="0 0 52.787 40.465">
                                <defs>
                                    <clipPath id="clipPath">
                                        <rect id="Rectangle_2864" data-name="Rectangle 2864" width="52.787"
                                            height="40.465" fill="#d03b37"></rect>
                                    </clipPath>
                                </defs>
                                <g id="Group_25303" data-name="Group 25303" clip-path="url(#clipPath)">
                                    <path id="Path_15873" data-name="Path 15873"
                                        d="M49.006,0H3.781A3.785,3.785,0,0,0,0,3.78v32.9a3.785,3.785,0,0,0,3.781,3.781H49.006a3.785,3.785,0,0,0,3.781-3.781V3.78A3.785,3.785,0,0,0,49.006,0m1.885,36.684a1.887,1.887,0,0,1-1.885,1.885H3.781A1.887,1.887,0,0,1,1.9,36.683V3.78A1.887,1.887,0,0,1,3.781,1.895H49.006A1.887,1.887,0,0,1,50.891,3.78Z"
                                        transform="translate(0 0.001)" fill="#d03b37"></path>
                                    <path id="Path_15874" data-name="Path 15874"
                                        d="M52.684,8.231H15.6a.948.948,0,0,0-.948.948V41.507a.948.948,0,0,0,.948.948H52.684a.948.948,0,0,0,.948-.948V9.179a.948.948,0,0,0-.948-.948m-.948,32.328H16.551V10.127H51.736Z"
                                        transform="translate(-9.099 -5.111)" fill="#d03b37"></path>
                                    <path id="Path_15875" data-name="Path 15875"
                                        d="M122.267,41.556a2.483,2.483,0,0,0-2.48,2.48v4a2.48,2.48,0,0,0,4.96,0v-4a2.483,2.483,0,0,0-2.48-2.48m.585,6.476a.585.585,0,0,1-1.169,0v-4a.585.585,0,0,1,1.169,0Z"
                                        transform="translate(-74.375 -25.802)" fill="#d03b37"></path>
                                    <path id="Path_15876" data-name="Path 15876"
                                        d="M8.312,36.959a.948.948,0,0,0-.948.948V48.453a.948.948,0,0,0,1.9,0V37.907a.948.948,0,0,0-.948-.948"
                                        transform="translate(-4.572 -22.948)" fill="#d03b37"></path>
                                    <rect id="Rectangle_2861" data-name="Rectangle 2861" width="29.547"
                                        height="1.896" transform="translate(10.27 18.915)" fill="#d03b37"></rect>
                                    <rect id="Rectangle_2862" data-name="Rectangle 2862" width="29.547"
                                        height="1.896" transform="translate(10.27 24.319)" fill="#d03b37"></rect>
                                    <rect id="Rectangle_2863" data-name="Rectangle 2863" width="29.547"
                                        height="1.896" transform="translate(10.27 29.723)" fill="#d03b37"></rect>
                                    <path id="Path_15877" data-name="Path 15877"
                                        d="M30.914,24.81l3.575,5.348h.924V23.352H34.55V28.7l-3.575-5.344h-.924v6.806h.864Z"
                                        transform="translate(-18.658 -14.499)" fill="#d03b37"></path>
                                    <path id="Path_15878" data-name="Path 15878"
                                        d="M53.315,29.355H49.137V27.038H52.9v-.8H49.137V24.155h4.02v-.8H48.236v6.806h5.079Z"
                                        transform="translate(-29.95 -14.499)" fill="#d03b37"></path>
                                    <path id="Path_15879" data-name="Path 15879"
                                        d="M66.012,30.158l1.444-5.186q.093-.33.2-.8.032.148.213.8L69.3,30.158h.869l1.866-6.806h-.905l-1.068,4.373q-.209.836-.339,1.481A18.261,18.261,0,0,0,69.2,26.8l-.975-3.445H67.136l-1.3,4.6q-.047.167-.306,1.258-.121-.692-.288-1.393l-1.035-4.462h-.924l1.806,6.806Z"
                                        transform="translate(-39.294 -14.499)" fill="#d03b37"></path>
                                    <path id="Path_15880" data-name="Path 15880"
                                        d="M91.549,29.123a2.506,2.506,0,0,1-.91.153,2.589,2.589,0,0,1-1.045-.2,1.533,1.533,0,0,1-.685-.529,1.873,1.873,0,0,1-.281-.838l-.85.074A2.3,2.3,0,0,0,88.153,29a2.171,2.171,0,0,0,.98.81,3.919,3.919,0,0,0,1.548.269,3.117,3.117,0,0,0,1.312-.267,2.051,2.051,0,0,0,.894-.748,1.851,1.851,0,0,0,.311-1.024,1.689,1.689,0,0,0-.283-.968,2.063,2.063,0,0,0-.877-.694,8.98,8.98,0,0,0-1.507-.434,3.588,3.588,0,0,1-1.358-.485.774.774,0,0,1-.265-.6.9.9,0,0,1,.374-.722,1.9,1.9,0,0,1,1.191-.3,1.834,1.834,0,0,1,1.186.33,1.393,1.393,0,0,1,.471.975l.863-.065a2.058,2.058,0,0,0-.334-1.072,1.926,1.926,0,0,0-.889-.717,3.41,3.41,0,0,0-1.335-.244,3.242,3.242,0,0,0-1.249.232,1.811,1.811,0,0,0-.854.68,1.728,1.728,0,0,0-.293.963,1.551,1.551,0,0,0,.239.847A1.84,1.84,0,0,0,89,26.4a6.2,6.2,0,0,0,1.314.425,11.956,11.956,0,0,1,1.212.332,1.376,1.376,0,0,1,.613.4.886.886,0,0,1,.186.56.975.975,0,0,1-.193.583,1.262,1.262,0,0,1-.587.42"
                                        transform="translate(-54.501 -14.309)" fill="#d03b37"></path>
                                </g>
                            </svg>
                        </span>
                        <span class="sidebar-item-label font-arabic">أخبار السيارات</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="{{ route('web.videos') }}" class="sidebar-a">
                        <span class="sidebar-item-icon">
                            <svg id="Group_25308" data-name="Group 25308" xmlns="http://www.w3.org/2000/svg"
                                width="51.963" height="38.047" viewBox="0 0 51.963 38.047">
                                <defs>
                                    <clipPath id="clipPath">
                                        <rect id="Rectangle_2865" data-name="Rectangle 2865" width="51.963"
                                            height="38.047" fill="#d03b37"></rect>
                                    </clipPath>
                                </defs>
                                <g id="Group_25307" data-name="Group 25307" clip-path="url(#clipPath)">
                                    <path id="Path_15881" data-name="Path 15881"
                                        d="M51.02,0H.943A.943.943,0,0,0,0,.942V37.1a.943.943,0,0,0,.943.943H51.02a.943.943,0,0,0,.943-.943V.942A.943.943,0,0,0,51.02,0m-.943,36.162H1.886V1.885H50.077Z"
                                        transform="translate(0 0.001)" fill="#d03b37"></path>
                                    <path id="Path_15882" data-name="Path 15882"
                                        d="M58.783,38.872a.943.943,0,0,0,.943,0l8.855-5.112a.943.943,0,0,0,0-1.633l-8.855-5.113a.943.943,0,0,0-1.415.817V38.055a.942.942,0,0,0,.472.817M60.2,29.463l6.026,3.479L60.2,36.421Z"
                                        transform="translate(-36.313 -16.744)" fill="#d03b37"></path>
                                    <path id="Path_15883" data-name="Path 15883"
                                        d="M51.4,36.053A10.582,10.582,0,1,0,40.82,25.47,10.594,10.594,0,0,0,51.4,36.053m0-19.278a8.7,8.7,0,1,1-8.7,8.7,8.706,8.706,0,0,1,8.7-8.7"
                                        transform="translate(-25.421 -9.272)" fill="#d03b37"></path>
                                    <path id="Path_15884" data-name="Path 15884"
                                        d="M17.454,78.032h3.178v.61a.943.943,0,0,0,.943.943h3.072a.943.943,0,0,0,.943-.943v-.61H53.156a.943.943,0,0,0,.943-.943V74.016a.943.943,0,0,0-.943-.943H25.591v-.61a.943.943,0,0,0-.943-.943H21.575a.943.943,0,0,0-.943.943v.61H17.454a.943.943,0,0,0-.943.943v3.073a.943.943,0,0,0,.943.943m8.136-3.073H52.213v1.186H25.591Zm-3.072,2.13V73.405H23.7V77.7H22.518ZM18.4,74.959h2.235v1.186H18.4Z"
                                        transform="translate(-10.282 -44.539)" fill="#d03b37"></path>
                                </g>
                            </svg>

                        </span>
                        <span class="sidebar-item-label font-arabic">فيديوهات</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="{{ route('web.installment') }}" class="sidebar-a">
                        <span class="sidebar-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="44.608" height="40.52"
                                viewBox="0 0 44.608 40.52">
                                <g id="Group_21951" data-name="Group 21951" transform="translate(-655.145 -412.604)">
                                    <path id="Path_7681" data-name="Path 7681"
                                        d="M715.306,538.37a.272.272,0,0,1-.038,0c-2.019-.068-6.31-1.691-11.3-3.646-1.629-.637-3.036-1.189-3.613-1.351a.861.861,0,0,1,.468-1.658c.658.185,2.032.724,3.772,1.4,3.521,1.38,8.842,3.464,10.714,3.529h.026c2.655,0,14.532-6.6,16.566-10.249.439-.785.379-1.065.378-1.068-1.021-.8-8.94,2.4-10.238,3.382a.867.867,0,0,1-1.207-.155.854.854,0,0,1,.14-1.2c.389-.307,10.033-5.359,12.447-3.3.543.462.981,1.389-.017,3.178C731.013,531.53,718.55,538.37,715.306,538.37Z"
                                        transform="translate(-34.253 -85.246)" fill="#d03b37"></path>
                                    <path id="Path_7682" data-name="Path 7682"
                                        d="M710.714,516.37a8.148,8.148,0,0,1-1.51-.145l-1.281-.259a60.139,60.139,0,0,1-6.618-1.59.862.862,0,0,1,.519-1.644,57.383,57.383,0,0,0,6.436,1.544l1.278.256c2.219.427,3.639-.4,3.776-.961.1-.4-.533-1.23-2.677-1.878a23.171,23.171,0,0,1-7.487-3.953,11.067,11.067,0,0,0-2.968-1.776c-2.49-.77-6.307.319-8.8,1.661a.86.86,0,1,1-.817-1.515c2.888-1.557,7.141-2.714,10.136-1.787a12.414,12.414,0,0,1,3.465,2.03,21.436,21.436,0,0,0,6.971,3.694c4.157,1.255,3.995,3.33,3.845,3.935C714.627,515.447,712.87,516.37,710.714,516.37Z"
                                        transform="translate(-26.867 -70.209)" fill="#d03b37"></path>
                                    <path id="Path_7683" data-name="Path 7683"
                                        d="M740.654,445.9a4.452,4.452,0,0,1-4.166-2.808A4.392,4.392,0,0,1,739,437.318a4.315,4.315,0,0,1,3.328.011,4.467,4.467,0,0,1-.045,8.273A4.6,4.6,0,0,1,740.654,445.9Zm0-7.188a2.6,2.6,0,0,0-1,.2.153.153,0,0,1-.025.009,2.67,2.67,0,0,0-1.533,3.534,2.732,2.732,0,1,0,3.574-3.539A2.6,2.6,0,0,0,740.649,438.713Z"
                                        transform="translate(-62.238 -18.734)" fill="#d03b37"></path>
                                    <path id="Path_7684" data-name="Path 7684"
                                        d="M777.274,487.336a4.412,4.412,0,0,1-3.293-1.462,4.337,4.337,0,0,1-1.158-3.167,4.462,4.462,0,0,1,7.748-2.808.053.053,0,0,1,.011.012,4.445,4.445,0,0,1-3.309,7.425Zm-.018-7.183a2.617,2.617,0,0,0-1.793.709,2.723,2.723,0,0,0-.919,1.921,2.633,2.633,0,0,0,.709,1.928,2.726,2.726,0,1,0,4.053-3.645A2.807,2.807,0,0,0,777.255,480.153Z"
                                        transform="translate(-90.401 -50.562)" fill="#d03b37"></path>
                                    <path id="Path_7685" data-name="Path 7685"
                                        d="M790.28,421.523a4.586,4.586,0,0,1-.782-.068,4.48,4.48,0,0,1-3.628-5.15,4.464,4.464,0,0,1,8.037-1.829l.01.016a4.466,4.466,0,0,1-1.083,6.213A4.372,4.372,0,0,1,790.28,421.523Zm-.007-7.2a2.726,2.726,0,0,0-2.707,2.274,2.76,2.76,0,0,0,2.228,3.157,2.694,2.694,0,0,0,2.05-.464,2.778,2.778,0,0,0,.66-3.82A2.745,2.745,0,0,0,790.273,414.328Z"
                                        transform="translate(-100.376)" fill="#d03b37"></path>
                                    <path id="Path_7686" data-name="Path 7686"
                                        d="M658.994,524.085a.864.864,0,0,1-.842-.684l-2.988-14.133a.863.863,0,0,1,.666-1.021l4.812-1.012a.864.864,0,0,1,1.02.665l2.989,14.132a.863.863,0,0,1-.666,1.021l-4.812,1.014A.885.885,0,0,1,658.994,524.085Zm-1.967-14.331L659.66,522.2l3.126-.658L660.153,509.1Z"
                                        transform="translate(0 -72.684)" fill="#d03b37"></path>
                                </g>
                            </svg>
                        </span>
                        <span class="sidebar-item-label font-arabic">التقسيط</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="{{ route('web.insurance') }}" class="sidebar-a">
                        <span class="sidebar-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="38.796" height="43.88"
                                viewBox="0 0 38.796 43.88">
                                <g id="Group_21957" data-name="Group 21957" transform="translate(-25.718 -21.012)">
                                    <path id="Path_7687" data-name="Path 7687"
                                        d="M62.538,40.273a47.518,47.518,0,0,0,1.216-10.537L45.115,21.762,26.476,29.736a42.9,42.9,0,0,0,.471,6.44"
                                        transform="translate(0)" fill="none" stroke="#d03b37"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                    <path id="Path_7688" data-name="Path 7688"
                                        d="M38.564,82.011A28.747,28.747,0,0,0,50.932,93.13,28.583,28.583,0,0,0,62.82,82.762"
                                        transform="translate(-5.817 -28.988)" fill="none" stroke="#d03b37"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                    <path id="Path_7689" data-name="Path 7689"
                                        d="M27.386,49.543c.126.9.285,1.858.486,2.87"
                                        transform="translate(-0.439 -13.367)" fill="none" stroke="#d03b37"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                    <g id="Group_21956" data-name="Group 21956" transform="translate(28.059 36.679)">
                                        <g id="Group_21952" data-name="Group 21952"
                                            transform="translate(25.727 8.587)">
                                            <path id="Path_7690" data-name="Path 7690"
                                                d="M82.106,67.062a2.971,2.971,0,1,1-2.991,2.95A2.974,2.974,0,0,1,82.106,67.062Z"
                                                transform="translate(-79.115 -67.062)" fill="none"
                                                stroke="#d03b37" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="1.5"></path>
                                        </g>
                                        <g id="Group_21953" data-name="Group 21953"
                                            transform="translate(3.667 8.436)">
                                            <path id="Path_7691" data-name="Path 7691"
                                                d="M39.589,66.771a2.971,2.971,0,1,1-2.991,2.95A2.974,2.974,0,0,1,39.589,66.771Z"
                                                transform="translate(-36.598 -66.771)" fill="none"
                                                stroke="#d03b37" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="1.5"></path>
                                        </g>
                                        <path id="Path_7692" data-name="Path 7692"
                                            d="M33.2,62.273l-2.347-.012a1.327,1.327,0,0,1-1.32-1.336L29.559,57c.013-1.925,1.11-2.282,1.762-2.324.292-.019.7-.026,1.2-.023.533,0,1.058.019,1.332.029,4.874-4.073,5.079-4.072,5.3-4.07l.463-.01c1.2-.028,4.374-.1,6.828-.084a13.533,13.533,0,0,1,3.218.191c.953.325,5.537,3.714,6.762,4.627l3.993.03c1.6.011,3.966,1.847,3.953,3.717l-.022,2.91a.5.5,0,0,1-.506.5L61.2,62.475"
                                            transform="translate(-29.53 -50.512)" fill="none" stroke="#d03b37"
                                            stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                        <line id="Line_1583" data-name="Line 1583" x1="16.147" y1="0.11"
                                            transform="translate(9.58 11.809)" fill="none" stroke="#d03b37"
                                            stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></line>
                                        <g id="Group_21954" data-name="Group 21954"
                                            transform="translate(14.652 2.367)">
                                            <path id="Path_7693" data-name="Path 7693"
                                                d="M67.038,57.528l-8.444-.054a.441.441,0,0,1-.446-.383l-.38-2.016"
                                                transform="translate(-57.769 -55.075)" fill="none"
                                                stroke="#d03b37" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="1.5"></path>
                                        </g>
                                        <g id="Group_21955" data-name="Group 21955"
                                            transform="translate(7.934 4.046)">
                                            <path id="Path_7694" data-name="Path 7694"
                                                d="M52.364,59.031l-7.1.015a.452.452,0,0,1-.418-.272.4.4,0,0,1,.118-.463"
                                                transform="translate(-44.821 -58.31)" fill="none" stroke="#d03b37"
                                                stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                                            </path>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </span>
                        <span class="sidebar-item-label font-arabic">التأمين</span>
                    </a>
                </div>
                <div class="sidebar-item">
                    <a href="{{ route('web.service-centers') }}" class="sidebar-a">
                        <span class="sidebar-item-icon">
                            <svg id="Group_25313" data-name="Group 25313" xmlns="http://www.w3.org/2000/svg"
                                width="48.695" height="45.381" viewBox="0 0 48.695 45.381">
                                <defs>
                                    <clipPath id="clipPath">
                                        <rect id="Rectangle_2866" data-name="Rectangle 2866" width="48.695"
                                            height="45.381" fill="#d03b37"></rect>
                                    </clipPath>
                                </defs>
                                <g id="Group_25312" data-name="Group 25312" clip-path="url(#clipPath)">
                                    <path id="Path_15885" data-name="Path 15885"
                                        d="M16.192,23.5a1.045,1.045,0,0,1-.741-.307l-4.3-4.3L9.006,21.039a2.644,2.644,0,0,1-3.733,0l-4.5-4.5a2.643,2.643,0,0,1,0-3.733L12.026,1.549A4.362,4.362,0,0,1,15.66.448l8.132,1.626a1.927,1.927,0,0,1,1.57,1.275,1.927,1.927,0,0,1-.6,1.931l-5.233,5.233,4.2,4.2a1.048,1.048,0,0,1-1.482,1.482l-4.938-4.937a1.048,1.048,0,0,1,0-1.482l5.714-5.714L15.248,2.5a2.3,2.3,0,0,0-1.74.527L2.254,14.285a.547.547,0,0,0,0,.769l4.5,4.5a.548.548,0,0,0,.768,0l2.885-2.885a1.079,1.079,0,0,1,1.482,0l5.042,5.041a1.048,1.048,0,0,1-.741,1.79"
                                        transform="translate(0 -0.251)" fill="#d03b37"></path>
                                    <path id="Path_15886" data-name="Path 15886"
                                        d="M87.455,91.585a2.987,2.987,0,0,1-2.149-.844L74.552,79.988a1.048,1.048,0,0,1,1.482-1.482L86.788,89.259c.414.414,1.444.308,2.258-.506L91.7,86.1c.815-.814.92-1.844.506-2.258L81.231,72.872a1.048,1.048,0,0,1,1.482-1.482L93.687,82.36c1.3,1.3,1.073,3.644-.506,5.223l-2.653,2.652a4.427,4.427,0,0,1-3.073,1.35"
                                        transform="translate(-48.306 -46.248)" fill="#d03b37"></path>
                                    <path id="Path_15887" data-name="Path 15887"
                                        d="M21.808,45.382a10.07,10.07,0,0,1-3.915-.806,1.048,1.048,0,0,1-.335-1.707l4.4-4.4a.852.852,0,0,0,0-1.207l-2.69-2.69a.874.874,0,0,0-1.207,0l-4.4,4.405a1.048,1.048,0,0,1-1.708-.335,9,9,0,0,1,1.6-9.923A8.417,8.417,0,0,1,19.572,26.3a9.867,9.867,0,0,1,2.532.335L37.334,11.4a8.781,8.781,0,0,1,1.952-8.992A8.4,8.4,0,0,1,45.292,0a10.073,10.073,0,0,1,3.916.806,1.048,1.048,0,0,1,.335,1.707l-4.4,4.4a.854.854,0,0,0,0,1.208l2.69,2.688a.872.872,0,0,0,1.206,0l4.4-4.4a1.048,1.048,0,0,1,1.707.335,9,9,0,0,1-1.6,9.923,8.421,8.421,0,0,1-6.016,2.415h0a9.913,9.913,0,0,1-2.976-.463L29.623,33.553a8.9,8.9,0,0,1-1.809,9.418,8.4,8.4,0,0,1-6.006,2.411m-1.544-2.256a7.726,7.726,0,0,0,1.544.16,6.323,6.323,0,0,0,4.524-1.8,6.926,6.926,0,0,0,1.1-7.767,1.049,1.049,0,0,1,.218-1.163L43.537,16.674a1.048,1.048,0,0,1,1.12-.236,7.934,7.934,0,0,0,2.873.55,6.339,6.339,0,0,0,4.534-1.8A6.626,6.626,0,0,0,53.7,9.118l-3.18,3.18a2.948,2.948,0,0,1-4.171,0l-2.69-2.689a2.95,2.95,0,0,1,0-4.173l3.179-3.179a7.705,7.705,0,0,0-1.545-.16,6.326,6.326,0,0,0-4.524,1.8,6.821,6.821,0,0,0-1.25,7.407,1.048,1.048,0,0,1-.237,1.119L23.145,28.557a1.048,1.048,0,0,1-1.073.253,7.886,7.886,0,0,0-2.5-.416,6.336,6.336,0,0,0-4.533,1.8A6.626,6.626,0,0,0,13.4,36.264l3.18-3.18a2.95,2.95,0,0,1,4.173,0l2.689,2.689a2.949,2.949,0,0,1,0,4.173Z"
                                        transform="translate(-7.255 -0.001)" fill="#d03b37"></path>
                                </g>
                            </svg>
                        </span>
                        <span class="sidebar-item-label font-arabic">مراكز الخدمة</span>
                    </a>
                </div>
                {{-- <div class="sidebar-item">
                    <a href="{{ asset('faqs')}}" class="sidebar-a">
                <span class="sidebar-item-icon">
                    <svg id="Group_25315" data-name="Group 25315" xmlns="http://www.w3.org/2000/svg"
                        width="52.279" height="42.76" viewBox="0 0 52.279 42.76">
                        <defs>
                            <clipPath id="clipPath">
                                <rect id="Rectangle_2867" data-name="Rectangle 2867" width="52.279"
                                    height="42.76" fill="#d03b37">
                                </rect>
                            </clipPath>
                        </defs>
                        <g id="Group_25314" data-name="Group 25314" clip-path="url(#clipPath)">
                            <path id="Path_15888" data-name="Path 15888"
                                d="M73.748,85.55a1.031,1.031,0,0,1,1.039-1.039h5.292a.945.945,0,0,1,0,1.89H75.827v2.011h3.645a.945.945,0,0,1,0,1.89H75.827V93a1.039,1.039,0,0,1-2.079,0Z"
                                transform="translate(-65.452 -75.005)" fill="#d03b37"></path>
                            <path id="Path_15889" data-name="Path 15889"
                                d="M139.035,91.452l3.294-7.438a1.285,1.285,0,0,1,1.215-.823h.121a1.267,1.267,0,0,1,1.2.823l3.294,7.438a1.012,1.012,0,0,1,.108.418.985.985,0,0,1-.986,1,1.059,1.059,0,0,1-1-.716l-.634-1.485h-4.158l-.661,1.552a1.021,1.021,0,0,1-.958.648.956.956,0,0,1-.958-.972,1.1,1.1,0,0,1,.121-.446m5.845-2.619-1.309-3.118-1.309,3.118Z"
                                transform="translate(-123.288 -73.833)" fill="#d03b37"></path>
                            <path id="Path_15890" data-name="Path 15890"
                                d="M229.879,87.985v-.027a5.024,5.024,0,0,1,10.043-.027v.027a4.848,4.848,0,0,1-.837,2.713l.4.338a1.02,1.02,0,0,1,.378.783.974.974,0,0,1-1.66.7l-.513-.459a5.15,5.15,0,0,1-2.808.81,4.841,4.841,0,0,1-5.008-4.859m4.914,1.066a.979.979,0,0,1,.972-.985.958.958,0,0,1,.688.283l1.04.931a3.312,3.312,0,0,0,.256-1.3v-.027a2.871,2.871,0,0,0-2.862-2.97,2.826,2.826,0,0,0-2.835,2.943v.027a2.871,2.871,0,0,0,2.862,2.97,2.84,2.84,0,0,0,1.242-.257l-.985-.837a1.021,1.021,0,0,1-.378-.783"
                                transform="translate(-204.02 -73.726)" fill="#d03b37"></path>
                            <path id="Path_15891" data-name="Path 15891"
                                d="M7,36.3A1.294,1.294,0,0,1,5.708,35V28.781H3.019A3.022,3.022,0,0,1,0,25.762V3.019A3.022,3.022,0,0,1,3.019,0H41.542a3.022,3.022,0,0,1,3.019,3.019V25.762a3.022,3.022,0,0,1-3.019,3.019H15.333L7.9,35.936A1.293,1.293,0,0,1,7,36.3M3.019,2.587a.432.432,0,0,0-.431.431V25.762a.432.432,0,0,0,.431.431H7A1.294,1.294,0,0,1,8.3,27.487v4.476l5.619-5.408a1.294,1.294,0,0,1,.9-.361h26.73a.432.432,0,0,0,.431-.431V3.019a.432.432,0,0,0-.431-.431Z"
                                transform="translate(0 0)" fill="#d03b37"></path>
                            <path id="Path_15892" data-name="Path 15892"
                                d="M119.707,104.435a1.294,1.294,0,0,1-.9-.362l-7.121-6.853H86.554a2.953,2.953,0,0,1-2.949-2.949v-1.15a1.294,1.294,0,0,1,.4-.932l4.114-3.959a1.294,1.294,0,0,1,.9-.362h26.731a.432.432,0,0,0,.431-.431V70.786a1.294,1.294,0,0,1,1.294-1.294h6.063a2.953,2.953,0,0,1,2.949,2.949v21.83a2.953,2.953,0,0,1-2.949,2.949H121v5.921a1.294,1.294,0,0,1-1.294,1.294M86.192,93.671v.6a.363.363,0,0,0,.362.362h25.656a1.294,1.294,0,0,1,.9.362l5.306,5.106V95.927a1.294,1.294,0,0,1,1.294-1.294h3.823a.362.362,0,0,0,.362-.362V72.441a.362.362,0,0,0-.362-.362h-4.769V87.437a3.022,3.022,0,0,1-3.018,3.019H89.534Z"
                                transform="translate(-74.2 -61.675)" fill="#d03b37"></path>
                        </g>
                    </svg>
                </span>
                <span class="sidebar-item-label font-arabic">الأسئلة الشائعة</span>
                </a>
            </div> --}}
            @guest('user')
            <div class="sidebar-item">
                <a href="{{ route('web.login') }}" class="sidebar-a">
                    <span class="sidebar-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75">
                            </path>
                        </svg>
                    </span>
                    <span class="sidebar-item-label font-arabic">تسجيل الدخول</span>
                </a>
            </div>
            @endguest

            @auth('user')
            <div class="sidebar-item">
                <a href="#" class="sidebar-a">
                    <form id="logout-form-mobile" action="{{ route('web.logout') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <button type="submit" id="logoutBtnMobile" class="sidebar-a">
                            <span class="sidebar-item-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25"
                                    viewBox="0 0 25 25" fill="none">
                                    <path
                                        d="M13.25 22.25H19.25C20.9075 22.25 22.25 20.9075 22.25 19.25V5.75C22.25 4.0925 20.9075 2.75 19.25 2.75H13.25C11.5925 2.75 10.25 4.0925 10.25 5.75V8C10.25 8.4125 10.5875 8.75 11 8.75C11.4125 8.75 11.75 8.4125 11.75 8V5.75C11.75 4.925 12.425 4.25 13.25 4.25H19.25C20.075 4.25 20.75 4.925 20.75 5.75V19.25C20.75 20.075 20.075 20.75 19.25 20.75H13.25C12.425 20.75 11.75 20.075 11.75 19.25V17C11.75 16.5875 11.4125 16.25 11 16.25C10.5875 16.25 10.25 16.5875 10.25 17V19.25C10.25 20.9075 11.5925 22.25 13.25 22.25Z"
                                        fill="currentColor" />
                                    <path
                                        d="M2.9675 11.969L6.7175 8.21902C6.78694 8.14958 6.86937 8.0945 6.96009 8.05692C7.05082 8.01934 7.14805 8 7.24625 8C7.34445 8 7.44168 8.01934 7.53241 8.05692C7.62313 8.0945 7.70556 8.14958 7.775 8.21902C7.84444 8.28845 7.89952 8.37089 7.93709 8.46161C7.97467 8.55233 7.99401 8.64957 7.99401 8.74777C7.99401 8.84596 7.97467 8.9432 7.93709 9.03392C7.89952 9.12465 7.84444 9.20708 7.775 9.27652L5.3075 11.744H15.5C15.9125 11.744 16.25 12.0815 16.25 12.494C16.25 12.9065 15.9125 13.244 15.5 13.244H5.3075L7.775 15.7115C7.87966 15.8163 7.95083 15.9497 7.97948 16.095C8.00813 16.2403 7.99296 16.3908 7.93589 16.5274C7.87883 16.664 7.78245 16.7806 7.65899 16.8624C7.53553 16.9441 7.39056 16.9873 7.2425 16.9865C7.0475 16.9865 6.86 16.9115 6.71 16.769L2.96 13.019C2.89047 12.9496 2.83531 12.8672 2.79768 12.7765C2.76004 12.6858 2.74067 12.5885 2.74067 12.4903C2.74067 12.392 2.76004 12.2948 2.79768 12.204C2.83531 12.1133 2.89047 12.0309 2.96 11.9615L2.9675 11.969Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <span class="sidebar-item-label font-arabic">تسجيل الخروج</span>
                        </button>
                    </form>
                </a>
            </div>
            @endauth
        </div>
    </div>
    </div>
</aside>
<div class="sidebar-overlay"></div>

@push('scripts-js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtnMobile = document.getElementById('logoutBtnMobile');
        if (logoutBtnMobile) {
            logoutBtnMobile.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'هل أنت متأكد من تسجيل الخروج؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'نعم',
                    confirmButtonColor: '#d03b37',
                    cancelButtonText: 'لا'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('logout-form-mobile').submit();
                    }
                });
            });
        }
    });
</script>
@endpush

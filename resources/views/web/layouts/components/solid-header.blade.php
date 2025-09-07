<header class="secondheader">
    <div class="container">
        <nav class="navbar navbar-expand-lg ">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarTogglerDemo03" aria-controls="navbarTogglerDemo03" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand" href="#">
                    <img src="img/logo.png" alt=""></a>
                <div class="collapse navbar-collapse" id="navbarTogglerDemo03">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-4 pages">
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="{{ route('web.home', $lang ) }}">الرئيسية</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('web.whoweare', $lang ) }}">من نحن</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " href="{{ route('web.contactUs', $lang ) }}">تواصل معنا</a>
                        </li>
                    </ul>
                </div>
                <div class="collapse navbar-collapse" id="navbarTogglerDemo03">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 pages">
                        <li class="nav-item">
                            <a class="nav-link" href="#">99654334+</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="#">عربى</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</header>

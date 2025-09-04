@if (Session::has('error'))
    <div class="mb-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert"
            style="color: #fff;display: flex;justify-content: space-between;">
            {{ Session::get('error') }}
            {{-- <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><span>x</span></button> --}}
        </div>
    </div>
@endif
@if (Session::has('success'))
    <div class="mb-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="color: #fff;display: flex;justify-content: space-between;">
            {{ Session::get('success') }}
            {{-- <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><span>x</span></button> --}}
        </div>
    </div>
@endif
@if ($errors->any())
    <div class="mb-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert"
            style="color: #fff;display: flex;justify-content: space-between;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            {{-- <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><span>x</span></button> --}}
        </div>
    </div>
@endif

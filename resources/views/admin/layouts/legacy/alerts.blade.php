@if (session('error'))
    <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        {{ session('error') }}
    </div>
@endif

@if (session('success'))
    <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
        {{ session('success') }}
    </div>
@endif

@if (session('status'))
    <div class="rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-md border border-red-200 bg-red-50 p-4">
        <h3 class="text-sm font-semibold text-red-800">{{ __('There were some problems with your input:') }}</h3>
        <ul class="mt-2 list-disc space-y-1 ps-5 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

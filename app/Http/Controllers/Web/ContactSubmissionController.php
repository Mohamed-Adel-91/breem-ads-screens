<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactSubmissionController extends Controller
{
    public function store(Request $request)
    {
        $type = strtolower((string) $request->route('type'));
        Log::debug('Contact submit route params', [
            'lang' => $request->route('lang'),
            'type' => $type,
            'path' => $request->path(),
        ]);
        $allowed = ['ads', 'screens', 'create', 'faq'];
        if (!in_array($type, $allowed, true)) {
            Log::warning('Contact submit route received invalid type', ['type' => $type, 'path' => $request->path()]);
            abort(404);
        }

        $commonRules = [
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ];

        $specific = [];
        switch ($type) {
            case 'ads':
                $specific = [
                    'ad_production' => ['nullable', 'in:produce,have'],
                    'branches_count' => ['nullable', 'integer', 'min:0'],
                    'duration' => ['nullable', 'string', 'max:50'],
                    'business_type' => ['nullable', 'string', 'max:255'],
                    'target_customers' => ['nullable', 'string', 'max:255'],
                    'places' => ['nullable', 'array'],
                    'places.*' => ['string', 'max:255'],
                    'details' => ['nullable', 'string'],
                ];
                break;
            case 'screens':
                $specific = [
                    'screens_count' => ['nullable', 'integer', 'min:0'],
                    'have_screens' => ['nullable', 'in:yes,no'],
                    'branches_count' => ['nullable', 'integer', 'min:0'],
                    'daily_customers_avg' => ['nullable', 'string', 'max:255'],
                    'details' => ['nullable', 'string'],
                ];
                break;
            case 'create':
                $specific = [
                    'business_type' => ['nullable', 'string', 'max:255'],
                    'details' => ['nullable', 'string'],
                ];
                break;
            case 'faq':
                $specific = [
                    'question' => ['nullable', 'string'],
                ];
                break;
        }

        $validated = $request->validate(array_merge($commonRules, $specific));

        try {
            $payload = $validated;
            $submission = ContactSubmission::create([
                'type' => $type,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'payload' => $payload,
            ]);

            Log::info('Contact submission stored', ['id' => $submission->id, 'type' => $type]);

            return back()->with('swal', ['type' => 'success', 'text' => __('شكرا! سنعاود الاتصال بك قريباً.')]);
        } catch (\Throwable $e) {
            Log::error('Contact submission error', ['type' => $type, 'msg' => $e->getMessage()]);
            return back()->withInput()->with('swal', ['type' => 'error', 'text' => __('حدث خطأ غير متوقع. حاول مرة أخرى.')]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\Facades\Activity;

class SettingController extends Controller
{
    public function edit()
    {
        $data = Setting::firstOrFail();
        return view('admin.settings.edit')->with([
            'pageName' => 'تعديل الإعدادات',
            'data' => $data,
        ]);
    }

    public function update(SettingsRequest $request)
    {
        $setting = Setting::firstOrFail();
        if (!$setting) {
            return redirect()->back()->with('error', 'لم يتم العثور على الإعدادات.');
        }
        $setting->update($request->validated());
        activity()
            ->performedOn($setting)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties($request->validated())
            ->log('Updated Settings');
        session()->flash('success', 'تم تحديث الإعدادات بنجاح');
        return back();
    }
}

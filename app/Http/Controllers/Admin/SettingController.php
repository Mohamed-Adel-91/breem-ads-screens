<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function edit(string $lang)
    {
        $data = Setting::firstOrFail();
        return view('admin.settings.edit')->with([
            'pageName' => 'تعديل الإعدادات',
            'data' => $data,
            'lang' => $lang,
        ]);
    }

    public function update(SettingsRequest $request, string $lang)
    {
        $setting = Setting::firstOrFail();
        if (!$setting) {
            return redirect()->route('admin.settings.edit', ['lang' => $lang])->with('error', 'لم يتم العثور على الإعدادات.');
        }
        $setting->update($request->validated());
        activity()
            ->performedOn($setting)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties($request->validated())
            ->log('Updated Settings');
        session()->flash('success', 'تم تحديث الإعدادات بنجاح');
        return redirect()->route('admin.settings.edit', ['lang' => $lang]);
    }
}

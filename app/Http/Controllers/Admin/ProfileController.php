<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\ChangeAdminPasswordRequest;
use App\Models\Admin;
use App\Models\AdminOtp;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\OTPMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function edit()
    {
        $admin = Auth::guard('admin')->user();

        return view('admin.profile.edit')->with([
            'pageName' => 'ملفي الشخصي',
            'admin' => $admin,
        ]);
    }

    public function update(ProfileRequest $request)
    {
        $id    = Auth::guard('admin')->id();
        $admin = Admin::findOrFail($id);
        $validatedData = $request->validated();
        $folder = Admin::UPLOAD_FOLDER;
        if (array_key_exists('profile_picture', $validatedData)) {
            unset($validatedData['profile_picture']);
        }
        $admin->update($validatedData);
        $this->fileService->updateFiles($admin, $request, ['profile_picture'], $folder);
        activity()
            ->performedOn($admin)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties($validatedData)
            ->log('Updated Profile');
        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updatePassword(ChangeAdminPasswordRequest $request)
    {
        $id    = Auth::guard('admin')->id();
        $admin = Admin::findOrFail($id);
        $data  = $request->validated();

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->with('error', 'كلمة المرور الحالية غير صحيحة.');
        }

        $otp = random_int(100000, 999999);

        AdminOtp::updateOrCreate(
            ['admin_id' => $admin->id],
            [
                'otp_code'   => $otp,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]
        );

        Mail::to($admin->email)->send(new OTPMail($otp));

        session([
            'password_otp_admin_id' => $admin->id,
            'new_admin_password'    => $data['password'],
        ]);

        return view('admin.profile.otp');
    }

    public function verifyPasswordOtp(Request $request)
    {
        $request->validate(['otp' => 'required']);

        $adminId   = session('password_otp_admin_id');
        $newPass   = session('new_admin_password');

        if (!$adminId || !$newPass) {
            return redirect()->route('admin.profile.edit');
        }

        $record = AdminOtp::where('admin_id', $adminId)->first();

        if ($record && $record->otp_code === $request->otp) {
            $admin = Admin::findOrFail($adminId);
            $admin->password = $newPass;
            $admin->save();

            AdminOtp::where('admin_id', $adminId)->delete();

            session()->forget(['password_otp_admin_id', 'new_admin_password']);

            activity()
                ->performedOn($admin)
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties('Updated Password')
                ->log('Updated Password');

            return redirect()->route('admin.profile.edit')->with('success', 'تم تحديث كلمة المرور بنجاح.');
        }

        return back()->with('error', 'رمز التحقق غير صحيح.');
    }
}

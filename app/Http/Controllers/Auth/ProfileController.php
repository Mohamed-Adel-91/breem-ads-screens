<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::guard('user')->user();

        $user->load([
            'orders' => fn($q) => $q->orderBy('created_at', 'desc'),
            'orders.bookingCarClone',
            'orders.term.model',
        ]);

        return view('web.pages.auth.profile')->with([
            'user' => $user,
            'bookingOrders' => $user->orders,
        ]);
    }

    public function update(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user();
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'mobile' => ['required', 'string', 'max:255', 'unique:users,mobile,' . $user->id],
            'address' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
        $user->fill($data)->save();
        $folder = User::UPLOAD_FOLDER;
        $this->fileService->updateFiles($user, $request, ['image'], $folder);
        return redirect()->back()->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    public function updatePassword(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user();
        $request->validate([
            'current_password' => ['required', 'current_password:user'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة']);
        }
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();
        return redirect()->back()->with('success', 'تم تحديث كلمة المرور بنجاح');
    }
}

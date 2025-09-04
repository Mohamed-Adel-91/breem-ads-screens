<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\KnowMoreRequest;
use App\Models\KnowMore;

class KnowMoreController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = KnowMore::with('carModel')->orderByDesc('id')->paginate(25);
        return view('admin.car_module.know_more.index')->with([
            'pageName' => 'قائمة فيديو اعرف المزيد',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.car_module.know_more.form')->with([
            'pageName' => 'إنشاء فيديو اعرف المزيد',
        ]);
    }

    public function store(KnowMoreRequest $request)
    {
        $payload  = $request->validated();
        $folder   = KnowMore::UPLOAD_FOLDER;
        $knowMore = KnowMore::create(collect($payload)->except('video')->all());
        if ($request->hasFile('video')) {
            $this->fileService->storeFiles($knowMore, $request, ['video'], $folder);
        } else {
            if ($request->filled('video')) {
                $knowMore->video = $request->input('video'); // YouTube link
                $knowMore->save();
            }
        }
        $this->fileService->storeFiles($knowMore, $request, ['image'], $folder);
        return redirect()->route('admin.know_more.index')
            ->with('success', 'تم انشاء الفيديو الخاص باعرف المزيد عن السيارة بنجاح.');
    }
    public function edit($id)
    {
        $data = KnowMore::findOrFail($id);
        return view('admin.car_module.know_more.form')->with([
            'pageName' => 'تعديل فيديو اعرف المزيد',
            'data' => $data,
        ]);
    }

    public function update(KnowMoreRequest $request, $id)
    {
        $knowMore = KnowMore::findOrFail($id);
        $payload  = $request->validated();
        $folder   = KnowMore::UPLOAD_FOLDER;
        $knowMore->update(collect($payload)->except('video')->all());
        if ($request->hasFile('video')) {
            if ($knowMore->video && !preg_match('/^https?:\/\//i', $knowMore->video)) {
                $this->fileService->deleteFile($knowMore->video, $folder);
            }
            $this->fileService->updateFiles($knowMore, $request, ['video'], $folder);
        } else {
            if ($request->filled('video')) {
                if ($knowMore->video && !preg_match('/^https?:\/\//i', $knowMore->video)) {
                    $this->fileService->deleteFile($knowMore->video, $folder);
                }
                $knowMore->video = $request->input('video');
                $knowMore->save();
            }
        }
        $this->fileService->updateFiles($knowMore, $request, ['image'], $folder);
        return redirect()->route('admin.know_more.index', $request->query())
            ->with('success', 'تم تحديث الفيديو الخاص باعرف المزيد عن السيارة بنجاح.');
    }

    public function destroy($id)
    {
        $knowMore = KnowMore::findOrFail($id);
        $folder = KnowMore::UPLOAD_FOLDER;
        $this->fileService->deleteFile($knowMore->video, $folder);
        $this->fileService->deleteFile($knowMore->image, $folder);
        $knowMore->delete();
        return redirect()->route('admin.know_more.index')->with('success', 'تم حذف الفيديو الخاص باعرف المزيد عن السيارة بنجاح.');
    }
}

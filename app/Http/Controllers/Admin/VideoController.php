<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\VideoRequest;
use App\Models\Video;

class VideoController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }
    public function index()
    {
        $data = Video::orderBy('ord')->paginate(25);
        return view('admin.videos.index')->with([
            'pageName' => 'قائمة الفيديوهات',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.videos.form')->with([
            'pageName' => 'إضافة فيديو',
        ]);
    }

    public function store(VideoRequest $request)
    {
        $videos = Video::create($request->validated());
        $folder = Video::UPLOAD_FOLDER;
        $this->fileService->storeFiles($videos, $request, ['thumb_image'], $folder);
        return redirect()->route('admin.videos.index')->with('success', 'تم إنشاء الفيديو بنجاح.');
    }

    public function edit($id)
    {
        $data = Video::findOrFail($id);
        return view('admin.videos.form')->with([
            'pageName' => 'تعديل الفيديو',
            'data' => $data,
        ]);
    }

    public function update(VideoRequest $request, $id)
    {
        $video = Video::findOrFail($id);
        $video->update($request->validated());
        $folder = Video::UPLOAD_FOLDER;
        $this->fileService->updateFiles($video, $request, ['thumb_image'], $folder);
        return redirect()->route('admin.videos.index', $request->query())->with('success', 'تم تحديث الفيديو بنجاح.');
    }

    public function destroy($id)
    {
        $video = Video::findOrFail($id);
        $folder = Video::UPLOAD_FOLDER;
        $this->fileService->deleteFile($video->thumb_image, $folder);
        $video->delete();
        return redirect()->route('admin.videos.index')->with('success', 'تم حذف الفيديو بنجاح.');
    }
}

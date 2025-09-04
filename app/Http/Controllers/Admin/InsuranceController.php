<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceRequest;
use App\Helpers\FeaturesFormatter;
use App\Models\Insurance;
use Illuminate\Http\Request;

class InsuranceController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = Insurance::orderBy('id', 'desc')->paginate(25);
        return view('admin.finance_and_payment.insurance_programs.index')->with([
            'pageName' => 'قائمة برامج التأمين',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create(Request $request)
    {
        $featuresForForm = FeaturesFormatter::normalize($request->old('features', []));
        return view('admin.finance_and_payment.insurance_programs.form')->with([
            'pageName' => 'إنشاء برنامج تأمين',
            'featuresForForm' => $featuresForForm,
        ]);
    }

    public function store(InsuranceRequest $request)
    {
        $data = $request->validated();
        $data['features'] = FeaturesFormatter::sanitize($request->input('features', []));
        $program = Insurance::create($data);
        $folder = Insurance::UPLOAD_FOLDER;
        $this->fileService->storeFiles($program, $request, ['company_logo'], $folder);
        return redirect()->route('admin.insurances.index')->with('success', 'تم إنشاء برنامج التأمين بنجاح.');
    }

    public function edit(Request $request, $id)
    {
        $data = Insurance::findOrFail($id);
        $featuresForForm = FeaturesFormatter::normalize($request->old('features', $data->features ?? []));
        return view('admin.finance_and_payment.insurance_programs.form')->with([
            'pageName' => 'تعديل برنامج تأمين',
            'data' => $data,
            'featuresForForm' => $featuresForForm,
        ]);
    }

    public function update(InsuranceRequest $request, $id)
    {
        $item = Insurance::findOrFail($id);
        $data = $request->validated();
        $data['features'] = FeaturesFormatter::sanitize($request->input('features', []));
        $item->update($data);
        $folder = Insurance::UPLOAD_FOLDER;
        $this->fileService->updateFiles($item, $request, ['company_logo'], $folder);
        return redirect()->route('admin.insurances.index', $request->query())->with('success', 'تم تحديث برنامج التأمين بنجاح.');
    }

    public function destroy($id)
    {
        $item = Insurance::findOrFail($id);
        $folder = Insurance::UPLOAD_FOLDER;
        $this->fileService->deleteFile($folder, 'company_logo');
        $item->delete();
        return redirect()->route('admin.insurances.index')->with('success', 'تم حذف برنامج التأمين بنجاح.');
    }
}

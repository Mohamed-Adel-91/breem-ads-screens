<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Helpers\FeaturesFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\InstallmentProgramRequest;
use App\Models\InstallmentProgram;
use App\Models\Bank;
use Illuminate\Http\Request;

class InstallmentProgramController extends Controller
{
    protected FileServiceInterface $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index()
    {
        $data = InstallmentProgram::with('bank')->orderBy('id', 'desc')->paginate(25);
        return view('admin.finance_and_payment.installment_programs.index')->with([
            'pageName' => 'قائمة برامج التقسيط',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create(Request $request)
    {
        $featuresForForm = FeaturesFormatter::normalize($request->old('features', []));
        return view('admin.finance_and_payment.installment_programs.form')->with([
            'pageName' => 'إنشاء برنامج تقسيط',
            'banks' => Bank::all(),
            'featuresForForm' => $featuresForForm,

        ]);
    }

    public function store(InstallmentProgramRequest $request)
    {
        $data = $request->validated();
        $data['features'] = FeaturesFormatter::sanitize($request->input('features', []));
        $program = InstallmentProgram::create($data);
        $folder = InstallmentProgram::UPLOAD_FOLDER;
        $this->fileService->storeFiles($program, $request, ['card_image'], $folder);
        return redirect()->route('admin.installment_programs.index')->with('success', 'تم إنشاء برنامج التقسيط بنجاح.');
    }

    public function edit(Request $request, $id)
    {
        $data = InstallmentProgram::findOrFail($id);
        $featuresForForm = FeaturesFormatter::normalize($request->old('features', $data->features ?? []));
        return view('admin.finance_and_payment.installment_programs.form')->with([
            'pageName' => 'تعديل برنامج تقسيط',
            'data' => $data,
            'banks' => Bank::all(),
            'featuresForForm' => $featuresForForm,
        ]);
    }

    public function update(InstallmentProgramRequest $request, $id)
    {
        $program = InstallmentProgram::findOrFail($id);
        $data = $request->validated();
        $data['features'] = FeaturesFormatter::sanitize($request->input('features', []));
        $program->update($data);
        $folder = InstallmentProgram::UPLOAD_FOLDER;
        $this->fileService->updateFiles($program, $request, ['card_image'], $folder);
        return redirect()->route('admin.installment_programs.index', $request->query())->with('success', 'تم تحديث برنامج التقسيط بنجاح.');
    }

    public function destroy($id)
    {
        $program = InstallmentProgram::findOrFail($id);
        $folder = InstallmentProgram::UPLOAD_FOLDER;
        $this->fileService->deleteFile($folder, 'card_image');
        $program->delete();
        return redirect()->route('admin.installment_programs.index')->with('success', 'تم حذف برنامج التقسيط بنجاح.');
    }
}

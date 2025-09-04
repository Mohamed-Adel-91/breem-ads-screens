<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BankRequest;
use App\Models\Bank;

class BankController extends Controller
{
    public function index()
    {
        $data = Bank::orderBy('id', 'desc')->paginate(25);
        return view('admin.finance_and_payment.banks.index')->with([
            'pageName' => 'قائمة البنوك',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.finance_and_payment.banks.form')->with([
            'pageName' => 'إنشاء بنك',
        ]);
    }

    public function store(BankRequest $request)
    {
        Bank::create($request->validated());
        return redirect()->route('admin.banks.index')->with('success', 'تم إنشاء البنك بنجاح.');
    }

    public function edit($id)
    {
        $data = Bank::findOrFail($id);
        return view('admin.finance_and_payment.banks.form')->with([
            'pageName' => 'تعديل بنك',
            'data' => $data,
        ]);
    }

    public function update(BankRequest $request, $id)
    {
        $item = Bank::findOrFail($id);
        $item->update($request->validated());
        return redirect()->route('admin.banks.index', $request->query())->with('success', 'تم تحديث البنك بنجاح.');
    }

    public function destroy($id)
    {
        $item = Bank::findOrFail($id);
        $item->delete();
        return redirect()->route('admin.banks.index')->with('success', 'تم حذف البنك بنجاح.');
    }
}

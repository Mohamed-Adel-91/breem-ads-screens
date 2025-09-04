<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Writer;
use Illuminate\Http\Request;

class WriterController extends Controller
{
    public function index()
    {
        $data = Writer::orderBy('id')->paginate(25);
        return view('admin.news.writers.index')->with([
            'pageName' => 'قائمة الكتّاب',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function create()
    {
        return view('admin.news.writers.form')->with([
            'pageName' => 'إنشاء كاتب',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        Writer::create($request->only('name'));
        return redirect()->route('admin.writers.index')->with('success', 'تم إنشاء الكاتب بنجاح.');
    }

    public function edit($id)
    {
        $data = Writer::findOrFail($id);
        return view('admin.news.writers.form')->with([
            'pageName' => 'تعديل كاتب',
            'data' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $writer = Writer::findOrFail($id);
        $writer->update($request->only('name'));
        return redirect()->route('admin.writers.index')->with('success', 'تم تحديث الكاتب بنجاح.');
    }

    public function destroy($id)
    {
        $writer = Writer::findOrFail($id);
        $writer->delete();
        return redirect()->route('admin.writers.index')->with('success', 'تم حذف الكاتب بنجاح.');
    }
}

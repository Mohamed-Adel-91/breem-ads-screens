<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\RoutesHelper;
use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeoMetaController extends Controller
{

    public function index()
    {
        $data = SeoMeta::orderBy('created_at', 'desc')->paginate(25);
        return view('admin.seo_metas.index', compact('data'))
            ->with('pageName', 'إدارة ميتا سيو');
    }

    public function create()
    {
        $pagesRoutes = RoutesHelper::getFrontendRoutes();

        return view('admin.seo_metas.form', compact('pagesRoutes'))
            ->with('pageName', 'إنشاء ميتا سيو');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'page'           => 'required|unique:seo_metas,page',
            'title'          => 'required|array',
            'description'    => 'nullable|array',
            'keywords'       => 'nullable|array',
            'og_title'       => 'nullable|array',
            'og_description' => 'nullable|array',
            'canonical'      => 'nullable|string',
        ]);

        $seoMeta = SeoMeta::create($data);
        activity()
            ->performedOn($seoMeta)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties($data)
            ->log('Created SEO Meta');
        return redirect()->route('admin.seo_metas.index')
            ->with('success', 'تم إنشاء بيانات تحسين محركات البحث بنجاح.');
    }

    public function edit($id)
    {
        $seoMeta = SeoMeta::findOrFail($id);
        $pagesRoutes = RoutesHelper::getFrontendRoutes();

        return view('admin.seo_metas.form', compact('seoMeta', 'pagesRoutes'))
            ->with('pageName', 'تعديل ميتا سيو');
    }
    public function update(Request $request, $id)
    {
        $seoMeta = SeoMeta::findOrFail($id);

        $data = $request->validate([
            'page'           => 'required|unique:seo_metas,page,' . $seoMeta->id,
            'title'          => 'required|array',
            'description'    => 'nullable|array',
            'keywords'       => 'nullable|array',
            'og_title'       => 'nullable|array',
            'og_description' => 'nullable|array',
            'canonical'      => 'nullable|string',
        ]);

        $seoMeta->update($data);
        activity()
            ->performedOn($seoMeta)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties($data)
            ->log('Updated SEO Meta');

        return redirect()->route('admin.seo_metas.index', $request->query())
            ->with('success', 'تم تحديث بيانات تحسين محركات البحث بنجاح.');
    }
    public function destroy($id)
    {
        $seoMeta = SeoMeta::findOrFail($id);
        $seoMeta->delete();
        activity()
            ->performedOn($seoMeta)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['name' => $seoMeta->name])
            ->log('Deleted SEO Meta');
        return redirect()->route('admin.seo_metas.index')
            ->with('success', 'تم حذف بيانات تحسين محركات البحث بنجاح.');
    }
}

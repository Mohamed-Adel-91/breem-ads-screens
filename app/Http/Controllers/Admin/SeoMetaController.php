<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\RoutesHelper;
use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use App\Support\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeoMetaController extends Controller
{

    public function index()
    {
        $data = SeoMeta::orderBy('created_at', 'desc')->paginate(25);
        return view('admin.seo_metas.index', compact('data'))
            ->with('pageName', Lang::t('admin.pages.seo_metas.index', 'إدارة ميتا سيو'));
    }

    public function create()
    {
        $pagesRoutes = RoutesHelper::getFrontendRoutes();

        return view('admin.seo_metas.form', compact('pagesRoutes'))
            ->with('pageName', Lang::t('admin.pages.seo_metas.create', 'إنشاء ميتا سيو'));
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
            ->with('success', Lang::t('admin.flash.seo_metas.created', 'تم إنشاء بيانات تحسين محركات البحث بنجاح.'));
    }

    public function edit(string $lang, SeoMeta $seoMeta)
    {
        $pagesRoutes = RoutesHelper::getFrontendRoutes();

        return view('admin.seo_metas.form', compact('seoMeta', 'pagesRoutes'))
            ->with('pageName', Lang::t('admin.pages.seo_metas.edit', 'تعديل ميتا سيو'));
    }
    public function update(Request $request, string $lang, SeoMeta $seoMeta)
    {
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

        return redirect()->route('admin.seo_metas.index', array_merge(['lang' => $lang], $request->query()))
            ->with('success', Lang::t('admin.flash.seo_metas.updated', 'تم تحديث بيانات تحسين محركات البحث بنجاح.'));
    }
    public function destroy(string $lang, SeoMeta $seoMeta)
    {
        $seoMeta->delete();
        activity()
            ->performedOn($seoMeta)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['name' => $seoMeta->name])
            ->log('Deleted SEO Meta');
        return redirect()->route('admin.seo_metas.index', ['lang' => $lang])
            ->with('success', Lang::t('admin.flash.seo_metas.deleted', 'تم حذف بيانات تحسين محركات البحث بنجاح.'));
    }
}

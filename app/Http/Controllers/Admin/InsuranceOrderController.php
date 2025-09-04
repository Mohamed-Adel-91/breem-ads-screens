<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceOrder;
use App\Models\User;
use App\Models\CarTerm;
use App\Models\Insurance;
use App\Traits\DeleteFileTrait;

class InsuranceOrderController extends Controller
{
    use DeleteFileTrait;

    public function index()
    {
        $data = InsuranceOrder::with(['user','carTerm','insurance'])->orderBy('id','desc')->paginate(25);
        return view('admin.finance_and_payment.insurance_orders.index')->with([
            'pageName' => 'قائمة طلبات التأمين',
            'data' => $data,
            'filters' => [],
        ]);
    }

    public function show($id)
    {
        $data = InsuranceOrder::findOrFail($id);
        return view('admin.finance_and_payment.insurance_orders.show')->with([
            'pageName' => 'عرض طلب تأمين',
            'data' => $data,
            'users' => User::all(),
            'carTerms' => CarTerm::all(),
            'insurances' => Insurance::all(),
        ]);
    }


}

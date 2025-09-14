<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;

class ContactSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $submissions = ContactSubmission::query()
            ->latest()
            ->paginate(20);

        return view('admin.contact_submissions.index', compact('submissions'));
    }

    public function destroy(ContactSubmission $submission)
    {
        $submission->delete();
        return back()->with('swal', ['type' => 'success', 'text' => __('Deleted successfully')]);
    }
}

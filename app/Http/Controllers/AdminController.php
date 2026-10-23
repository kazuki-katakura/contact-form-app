<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request): View
    {
        $contacts = Contact::with(['category', 'tags'])
            ->filter($request->validated())
            ->newest()
            ->paginate(7)
            ->withQueryString();

        return view('admin.index', [
            'contacts' => $contacts,
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    public function show(Contact $contact): View
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', ['contact' => $contact]);
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('admin.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact.index', [
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();

        return view('contact.confirm', [
            'validated' => $validated,
            'category' => Category::find($validated['category_id']),
            'tags' => Tag::whereIn('id', $validated['tag_ids'] ?? [])->get(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        Contact::createWithTags($request->validated());

        return redirect()->route('contact.thanks');
    }

    public function thanks(): View
    {
        return view('contact.thanks');
    }

}

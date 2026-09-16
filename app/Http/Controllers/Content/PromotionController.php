<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Content\StorePromotionRequest;
use App\Http\Requests\Content\UpdatePromotionRequest;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $promotions = Promotion::orderByDesc('starts_on')->paginate(20);

        return view('content.promotions.index', compact('promotions'));
    }






    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $promotion = new Promotion();

        return view('content.promotions.create', compact('promotion'));
    }





    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $data = $request->validated();
     
        unset($data['image']);

        $data['created_by'] = auth()->id();

         if ($request->hasFile('image')) {
             // Returns something like "promotions/8xKq...jpg" — that path is what we store.
             $data['image_path'] = $request->file('image')->store('promotions', 'public');
    }

    $promotion = Promotion::create($data);

    return redirect()->route('content.promotions.index')
        ->with('success', "\"{$promotion->title}\" saved.");
    }





    /**
     * Display the specified resource.
     */
    public function show(Promotion $promotion): View
    {
        $promotion->load('creator');
        
        return view('content.promotions.show', compact('promotion'));
        
    }










    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Promotion $promotion): View
    {
        return view('content.promotions.edit', compact('promotion'));

    }






    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
         $data = $request->validated();
            unset($data['image']);

        if ($request->hasFile('image')) {
            // Replacing the image: delete the old file first, or it stays on disk forever.
            if ($promotion->image_path) {
                Storage::disk('public')->delete($promotion->image_path);
            }

        $data['image_path'] = $request->file('image')->store('promotions', 'public');  }
                                                                                                                                                                                    



        $promotion->update($data);

        return redirect()->route('content.promotions.index')
            ->with('success', "\"{$promotion->title}\" updated.");
    }











    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promotion $promotion): RedirectResponse
    {
        if ($promotion->image_path) {
            Storage::disk('public')->delete($promotion->image_path);
        }

        $promotion->delete();

        return redirect()->route('content.promotions.index')
            ->with('success', 'Promotion deleted');
    }
}

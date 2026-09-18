<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Module 5 — Content, Map & Reporting. Owner: Ahmed Safhaan.
 *
 * The public front page, replacing the scaffold placeholder that said "Scaffold is
 * running" and was always meant to be deleted once this existed.
 *
 * DELIBERATELY MINIMAL — SAFHAAN, THE REST IS YOURS.
 * BUILD_CONTRACT.md §3 gives this view two collections:
 *
 *     ['promotions' => Collection of Promotion,   // Promotion::live()
 *      'featuredEvents' => Collection of ParkEvent]
 *
 * Neither is passed here. This exists only so the front page is not an apology, and the
 * content work the contract actually asks for is left for you to write and to claim.
 * Promotion::live() and Promotion::forModule() are already on your model and ready.
 */
class HomeController extends Controller
{
    /**
     * GET / — public.
     */
    public function index(): View
    {
        return view('home');
    }
}

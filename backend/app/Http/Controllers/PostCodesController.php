<?php

namespace App\Http\Controllers;

use App\Models\PostCode;
use Illuminate\Http\Request;

class PostCodesController extends Controller
{
    // Get all postcodes paginated
    public function index()
    {
        $postCodes = PostCode::paginate(20);

        return response()->json([
            'postcodes' => $postCodes,
        ]);
    }

    // Get postcodes with partial string match
    public function search(Request $request)
    {
        $request->validate([
            'postcode' => 'required|string|max:10',
        ]);

        $postCodes = PostCode::where('postcode', 'like', '%' . $request->postcode . '%')
            ->simplePaginate(20)
            ->withQueryString();

        return response()->json([
            'postcodes' => $postCodes,
        ]);
    }

    // Get postcodes near a location lat/long and paginate
    public function near(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $postCodes = PostCode::closeTo($request->latitude, $request->longitude)
            ->simplePaginate(20)
            ->withQueryString();

        return response()->json([
            'postcodes' => $postCodes,
        ]);
    }
}

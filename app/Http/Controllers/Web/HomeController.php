<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $minPrices = DB::table('room_types')
            ->select('hotel_id', DB::raw('MIN(price_per_night) as min_price'))
            ->where('status', 'active')
            ->groupBy('hotel_id');

        $hotels = DB::table('hotels')
            ->leftJoinSub($minPrices, 'prices', function ($join) {
                $join->on('hotels.id', '=', 'prices.hotel_id');
            })
            ->select(
                'hotels.id',
                'hotels.name',
                'hotels.slug',
                'hotels.city',
                'hotels.district',
                'hotels.address',
                'hotels.description',
                'hotels.star_rating',
                'hotels.status',
                'prices.min_price'
            )
            ->where('hotels.status', 'active')
            ->limit(8)
            ->get();

        $cities = DB::table('hotels')
            ->select('city', DB::raw('COUNT(*) as total'))
            ->where('status', 'active')
            ->groupBy('city')
            ->orderBy('city')
            ->get();

        return view('home', compact('hotels', 'cities'));
    }
}
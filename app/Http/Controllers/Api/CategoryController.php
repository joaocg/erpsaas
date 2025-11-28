<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->where(function ($query) use ($request): void {
                $query->whereNull('user_id');

                if ($request->user()) {
                    $query->orWhere('user_id', $request->user()->id);
                }
            })
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $category = Category::create([
            'user_id' => $request->user()->id,
            ...$data,
        ]);

        return response()->json($category, 201);
    }
}

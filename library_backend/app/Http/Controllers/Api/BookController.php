<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::orderBy('title', 'asc')->get();

        return response()->json([
            'message' => 'Books retrieved successfully.',
            'data' => $books,
        ], 200);
    }
}
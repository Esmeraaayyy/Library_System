<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Loan::with([
            'borrower:id,name,email',
            'book:id,title,isbn,author',
        ]);

        if ($user->role === 'borrower') {
            $query->where('borrower_id', $user->id);
        } elseif (!in_array($user->role, ['librarian', 'supervisor'])) {
            return response()->json([
                'message' => 'You are not authorized to view borrowings.',
            ], 403);
        }

        $borrowings = $query
            ->orderByDesc('due_at')
            ->get();

        return response()->json([
            'message' => 'Borrowing records retrieved successfully.',
            'data' => $borrowings,
        ], 200);
    }
}
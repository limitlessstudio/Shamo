<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items.product'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak tersedia',
                    404
                );
            }
        }

        $transaction = Transaction::with(['items.product'])->where('users_id', Auth::user()->id);

        if ($status) {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data transaksi berhsil diambil '
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exist:product,id',
            'total_price' => 'reqired',
            'shipping_price' => 'reqired',
            'status' => 'reqired|in:PENDING,SUCCES,CANCELLED,FAILED,SHIPPING,SHIPPED',
        ]);

        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address(),
            'total_price' => $request->total_price(),
            'shipping_price' => $request->shipping_price(),
            'status' => $request->status(),
            'address' => $request->address()
        ]);

        foreach ($request->items as $product) {
            TransactionItem::creete([
                'users_id' => Auth::user()->id,
                'products_id' => $product['id'],
                'transactions_id' => $transaction->id(),
                'qty' => $product['qty'],
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'),  'Transaksi Berhasil');
    }
}

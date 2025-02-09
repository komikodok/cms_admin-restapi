<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ConfirmTransactionController extends Controller
{
    public function confirm(Request $request, string $id) {
        $transaction = Transaction::with('payment')->where('id', $id)->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'errors',
                'message' => 'Transaction data not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        $payment = $transaction->payment;

        if (!$payment) {
            return response()->json([
                'status' => 'errors',
                'message' => 'Payment data not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            if ($payment->status === 'success') {
                $transaction->update(['status' => 'confirmed']);
            } else {
                $transaction->update(['status' => 'canceled']);
                $payment->update(['status' => 'failed']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'errors',
                'message' => 'Failed to update transaction status.',
                'errors' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Status updated successfully.'
        ], Response::HTTP_OK);
    }
}

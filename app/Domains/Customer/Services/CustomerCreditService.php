<?php

namespace App\Domains\Customer\Services;

use App\Models\Customer;
use App\Models\CustomerCreditTransaction;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerCreditService
{
    public function balance(Customer $customer): float
    {
        $credits = CustomerCreditTransaction::query()
            ->where('customer_id', $customer->id)
            ->whereIn('type', CustomerCreditTransaction::CREDIT_TYPES)
            ->sum('amount');
        $debits = CustomerCreditTransaction::query()
            ->where('customer_id', $customer->id)
            ->whereIn('type', CustomerCreditTransaction::DEBIT_TYPES)
            ->sum('amount');

        return round(max(0, (float) $credits - (float) $debits), 2);
    }

    public function recent(Customer $customer, int $limit = 10)
    {
        return CustomerCreditTransaction::query()
            ->where('customer_id', $customer->id)
            ->with(['order', 'returnRequest'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function creditForReturn(ReturnRequest $returnRequest, ?string $note = null): CustomerCreditTransaction
    {
        return DB::transaction(function () use ($returnRequest, $note) {
            $return = ReturnRequest::query()
                ->with(['order', 'customer'])
                ->whereKey($returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($return->customer_id === null || ! $return->customer) {
                throw new InvalidArgumentException('Return request is missing a customer.');
            }

            if ((float) $return->refund_amount <= 0) {
                throw new InvalidArgumentException('Return refund amount must be greater than zero.');
            }

            $existing = CustomerCreditTransaction::query()
                ->where('return_request_id', $return->id)
                ->where('type', CustomerCreditTransaction::RETURN_REFUND_CREDIT)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $balanceAfter = round($this->balance($return->customer) + (float) $return->refund_amount, 2);

            try {
                return CustomerCreditTransaction::query()->create([
                    'customer_id' => $return->customer_id,
                    'type' => CustomerCreditTransaction::RETURN_REFUND_CREDIT,
                    'amount' => (float) $return->refund_amount,
                    'balance_after' => $balanceAfter,
                    'order_id' => $return->order_id,
                    'return_request_id' => $return->id,
                    'source' => 'return_refund',
                    'description' => $note ?: 'Refund for return '.$return->return_number,
                    'created_by' => Auth::id(),
                ]);
            } catch (QueryException $exception) {
                if (($exception->errorInfo[0] ?? null) === '23000') {
                    return CustomerCreditTransaction::query()
                        ->where('return_request_id', $return->id)
                        ->where('type', CustomerCreditTransaction::RETURN_REFUND_CREDIT)
                        ->firstOrFail();
                }

                throw $exception;
            }
        });
    }

    public function debitForOrder(Customer $customer, Order $order, float $amount): ?CustomerCreditTransaction
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customer, $order, $amount) {
            $existing = CustomerCreditTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', CustomerCreditTransaction::ORDER_REDEMPTION_DEBIT)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $balance = $this->lockedBalance($customer);

            if ($amount > $balance) {
                throw new InvalidArgumentException('Customer Credit balance is not sufficient.');
            }

            $balanceAfter = round($balance - $amount, 2);

            try {
                return CustomerCreditTransaction::query()->create([
                    'customer_id' => $customer->id,
                    'type' => CustomerCreditTransaction::ORDER_REDEMPTION_DEBIT,
                    'amount' => $amount,
                    'balance_after' => $balanceAfter,
                    'order_id' => $order->id,
                    'source' => 'checkout_redemption',
                    'description' => 'Customer Credit used for order '.$order->order_number,
                    'created_by' => Auth::id(),
                    'idempotency_key' => CustomerCreditTransaction::ORDER_REDEMPTION_DEBIT.':'.$order->id,
                ]);
            } catch (QueryException $exception) {
                if (($exception->errorInfo[0] ?? null) === '23000') {
                    return CustomerCreditTransaction::query()
                        ->where('order_id', $order->id)
                        ->where('type', CustomerCreditTransaction::ORDER_REDEMPTION_DEBIT)
                        ->firstOrFail();
                }

                throw $exception;
            }
        });
    }

    public function restoreForCancelledOrder(Order $order, ?string $note = null): ?CustomerCreditTransaction
    {
        $amount = round((float) $order->customer_credit_used, 2);

        if ($amount <= 0 || ! $order->customer_id || ! $order->customer) {
            return null;
        }

        return DB::transaction(function () use ($order, $note, $amount) {
            $existing = CustomerCreditTransaction::query()
                ->where('order_id', $order->id)
                ->where('type', CustomerCreditTransaction::ORDER_CANCELLATION_CREDIT)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $balanceAfter = round($this->lockedBalance($order->customer) + $amount, 2);

            try {
                return CustomerCreditTransaction::query()->create([
                    'customer_id' => $order->customer_id,
                    'type' => CustomerCreditTransaction::ORDER_CANCELLATION_CREDIT,
                    'amount' => $amount,
                    'balance_after' => $balanceAfter,
                    'order_id' => $order->id,
                    'source' => 'order_cancellation',
                    'description' => $note ?: 'Customer Credit restored for cancelled order '.$order->order_number,
                    'created_by' => Auth::id(),
                    'idempotency_key' => CustomerCreditTransaction::ORDER_CANCELLATION_CREDIT.':'.$order->id,
                ]);
            } catch (QueryException $exception) {
                if (($exception->errorInfo[0] ?? null) === '23000') {
                    return CustomerCreditTransaction::query()
                        ->where('order_id', $order->id)
                        ->where('type', CustomerCreditTransaction::ORDER_CANCELLATION_CREDIT)
                        ->firstOrFail();
                }

                throw $exception;
            }
        });
    }

    public function maximumUsable(Customer $customer, float $payable): float
    {
        return round(min($this->balance($customer), max(0, $payable)), 2);
    }

    private function lockedBalance(Customer $customer): float
    {
        CustomerCreditTransaction::query()
            ->where('customer_id', $customer->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);

        return $this->balance($customer);
    }
}

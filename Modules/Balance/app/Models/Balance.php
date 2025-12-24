<?php

namespace Modules\Balance\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Balance extends Model
{
    protected $fillable = [
        'available_balance',
        'pending_balance',
        'total_balance',
        'balanceable_id',
        'balanceable_type',
    ];
    protected $casts = [
        'available_balance' => 'decimal:3',
        'pending_balance' => 'decimal:3',
        'total_balance' => 'decimal:3',
    ];
    public function balanceable(): MorphTo
    {
        return $this->morphTo();
    }
    public function addFunds(float $amount): self
    {
        DB::transaction(function () use ($amount) {
            $this->increment('available_balance', $amount);
            $this->increment('total_balance', $amount);
        });

        return $this->refresh();
    }
    public function deductFunds(float $amount): bool
    {
        if ($this->available_balance < $amount) {
            return false;
        }

        DB::transaction(function () use ($amount) {
            $this->decrement('available_balance', $amount);
            $this->decrement('total_balance', $amount);
        });

        return true;
    }
    public function moveToPending(float $amount): bool
    {
        if ($this->available_balance < $amount) {
            return false; // Insufficient funds
        }

        DB::transaction(function () use ($amount) {
            $this->decrement('available_balance', $amount);
            $this->increment('pending_balance', $amount);
        });

        return true;
    }
    public function releaseFromPending(float $amount): bool
    {
        if ($this->pending_balance < $amount) {
            return false; // Insufficient pending funds
        }

        DB::transaction(function () use ($amount) {
            $this->decrement('pending_balance', $amount);
            $this->increment('available_balance', $amount);
        });

        return true;
    }
}

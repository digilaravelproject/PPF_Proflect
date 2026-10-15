<?php
namespace App\Services;
use App\Models\Claim;
use App\Models\Subscription;
use App\Models\WarrantyCode;
use Illuminate\Support\Facades\DB;
class PendingClaimService {
 public function __construct(private readonly CustomerNotificationService $notifications) {}
 public function finalizeFor(Subscription $subscription, ?int $claimId = null): ?Claim { return DB::transaction(function() use($subscription,$claimId){ $claim=Claim::where('user_id',$subscription->user_id)->where('status','awaiting_subscription')->whereNull('subscription_id')->when($claimId,fn($query)=>$query->whereKey($claimId))->latest('id')->lockForUpdate()->first(); if(!$claim)return null; WarrantyCode::whereKey($claim->warranty_code_id)->where('used_by_user_id',$subscription->user_id)->update(['subscription_id'=>$subscription->id]); $claim->update(['subscription_id'=>$subscription->id,'status'=>'pending']); $subscription->forceFill(['claimed_at'=>now()])->save(); $claim=$claim->fresh(['user']); $this->notifications->claimReceived($claim); return $claim; }); }
}

<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\DeepLinkToken;
use Modules\Store\Models\Store;
use Modules\Product\Models\Product;

class DeepLinkController extends Controller
{
    use ApiResponse;
    public function generateProduct($productId, Request $request)
    {
        $referrerCode = $request->user()->referral_code ?? null;
        return $this->generateLink('product',$productId,$referrerCode);
    }

    public function generateStore($storeId, Request $request)
    {
        $referrerCode = $request->user()->referral_code ?? null;
        return $this->generateLink('store',$storeId,$referrerCode);
    }

    private function generateLink($type,$id,$referrerCode=null)
    {
        if($type=='product') Product::findOrFail($id);
        else Store::findOrFail($id);

        $token = Str::random(16);
        DeepLinkToken::create([
            'token'=>$token,
            'type'=>$type,
            'target_id'=>$id,
            'referrer_code'=>$referrerCode
        ]);

        $url = env('DEEP_DOMAIN')."/r/$token";

        return $this->successResponse([
            'share_url' => $url,
        ], __('message.success'));
    }

    public function redirect(Request $request, $token)
    {
        $record = DeepLinkToken::where('token',$token)->firstOrFail();
        $type = $record->type;
        $id = $record->target_id;

        $ua = strtolower($request->userAgent());
        $isAndroid = str_contains($ua,'android');
        $isIOS = str_contains($ua,'iphone') || str_contains($ua,'ipad');

        $scheme = env('APP_SCHEME')."://$type/$id?token=$token&ref=".$record->referrer_code;
        $universal = env('DEEP_DOMAIN')."/open/$type/$id?token=$token&ref=".$record->referrer_code;
        $play = env('PLAY_STORE_URL');
        $ios = env('IOS_STORE_URL');

        $record->update([
            'platform'=>$isAndroid?'android':'ios',
            'click_ip'=>$request->ip(),
            'clicked_at'=>now()
        ]);

        if(!$isAndroid && !$isIOS) return redirect("/$type/$id");

        return view('deeplink.redirect',compact('scheme','universal','play','ios'));
    }

    public function resolve(Request $request)
    {
        $token = $request->token;
        $record = DeepLinkToken::where('token',$token)->firstOrFail();
        return [
            'type'=>$record->type,
            'target_id'=>$record->target_id,
            'referrer_code'=>$record->referrer_code
        ];
    }
}

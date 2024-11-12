<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackageRequest;
use App\Models\Customers;
use App\Models\SubcriptionPackage;
use App\Models\SubscriptionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SubcriptionPackagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subcriptionPackages = SubcriptionPackage::all();
        return Inertia::render('Packages/Index', ['data' => $subcriptionPackages]);
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function getPackages()
    {
        $result = SubcriptionPackage::active()->get();
        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PackageRequest $request)
    {
        if($request->validated()){
            $data=$request->all();
            $data['created_at']=now();
            SubcriptionPackage::create($data);
            $data= SubcriptionPackage::all();
            return response()->json(['check'=>true,'data'=>$data]);

        }
    }
// ========================================================
public function buyPackages(Request $request){
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'subscription_package_id' => 'required|exists:subcription_packages,id',
            'login_provider' => 'required|string',
            'auth_method' => 'required|string',
            'serverVerificationData'=>'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => 'error', 'msg' => $validator->errors()->first()]);
        }
        $customer = Customers::where('device_id', $request->device_id)->first();
        
        if (!$customer) {
            return response()->json(['error' => 'Customer not found.'], 404);
        }
        if (!$customer->email) {
            $customer->email = $request->email;
            $customer->save();
        }
        // Create the subscription history entry
        $subscriptionHistory = SubscriptionHistory::create([
            'customer_id' => $customer->id,
            'subscription_package_id' => $request->subscription_package_id,
            'login_provider' => $request->login_provider,
            'auth_method' => $request->auth_method,
            'serverVerificationData'=>$request->serverVerificationData
        ]);
        $subscriptionPackage = SubcriptionPackage::find($request->subscription_package_id);
        if (!$subscriptionPackage) {
            return response()->json(['error' => 'Subscription package not found.'], 404);
        }
        $customer->updateRememberTokenAndExpiry($subscriptionPackage->duration, $request->platform);
        return response()->json(['check' => true]);
}
    /**
     * Display the specified resource.
     */
    public function getToken(Request $request,$id){
        $result = SubscriptionHistory::where('serverVerificationData', $id)->first();
        $customer_id=$result->customer_id;
        $customer=Customers::where('id',$customer_id)->first();
        Customers::where('id',$customer_id)->update(['device_id'=>$request->device_id,'updated_at'=>now()]);
        $token=$customer->remember_token;
        return response()->json(['token'=>$token]);
    }
        /**
     * Display the specified resource.
     */
    public function Reset(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubcriptionPackage $subcriptionPackage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PackageRequest $request, $id)
    {
        $data=$request->all();
            $data['updated_at']=now();
            SubcriptionPackage::where('id',$id)->update($data);
            $data= SubcriptionPackage::all();
            return response()->json(['check'=>true,'data'=>$data]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubcriptionPackage $subcriptionPackage,$id)
    {
        SubcriptionPackage::where('id', $id)->delete();
        $data = SubcriptionPackage::all();
        return response()->json(['check' => true, 'data' => $data]);
    }
}
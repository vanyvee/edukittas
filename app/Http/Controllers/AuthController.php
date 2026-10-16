<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
public function __construct(protected AuthService $auth)
    {
    }
 
public function showRegister()
{
    return view('register');
}

public function me(Request $request)
{
    foreach (['customer', 'web', 'admin'] as $guard) {
        if (Auth::guard($guard)->check()) {
            $user = Auth::guard($guard)->user();

            return response()->json([
                'guard' => $guard,
                'user' => $user,
            ]);
        }
    }

    return response()->json([
        'guard' => null,
        'user' => null,
    ]);
}
public function login(Request $request)
{

   $request->validate([
       'username' => 'required|string',
       'password' => 'required|string',
    ]);

   $detectedGuard = $this->getGuard($request->username);

   if (!Auth::guard($detectedGuard['name'])->attempt([  'username' => $request->username,'password' => $request->password   ])) {
   throw ValidationException::withMessages([ 'username' => ['Invalid credentials'],]);
   }

if($request->hasSession()){  
    $request->session()->regenerate();
    }
    $user = Auth::guard($detectedGuard['name'])->user();
    return response()->json([  'guard' => $detectedGuard['name'], 'user'=> $user,]);
}


public function logout(Request $request)
{
    foreach(['web','admin','customer'] as $guard){
        if(Auth::guard($guard)->check() && $request->hasSession()){
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
        }
    }
    return response()->json(['message'=> 'out']);
}








    
    public function switchSchool(Request $request): JsonResponse
    {
        $data = $request->validate(['school_id' => ['required', 'exists:schools,id']]);
 
        try {
            $this->auth->switchSchool($data['school_id']);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
 
        return response()->json(['school_id' => $data['school_id']]);
    }


    // public function logout(Request $request)
    // {
    //     $cookieNames = [ 'admin_token',  'student_token',  'user_token', 'guardian_token',  'director_token',  ];
    
    //     foreach ($cookieNames as $cookieName) {
    //         $cookieValue = $request->cookie($cookieName);
    //         if (!$cookieValue) {
    //             continue;
    //         }
    //         // Split guard and token
    //         [$guard, $token] = explode('|', $cookieValue);
    //         // Delete token from database
    //         $accessToken = PersonalAccessToken::findToken($token);
    //         if ($accessToken) {
    //             $accessToken->delete();
    //             Cache::forget('sanctum_token_'.sha1($token));
    //         }
    //         // Delete cookie
    //         return response()->json(['message' => "Logged out from {$guard}"])
    //                          ->cookie(cookie()->forget($cookieName));
    //     }
    
    //     return response()->json(['message' => 'No active session found'], 401);
    // }
    

    private function getGuard(string $username): array
    {
        $prefix = substr($username, 0, 1);
        return match ($prefix) {
            '$' => ['name'  => 'web', 'model' => \App\Models\User::class, ],
            '&' => ['name'  => 'admin', 'model' => \App\Models\Admin::class, ],
            default => ['name'=> 'customer' , 'model'=>\App\Models\Customer::class,],
        };
    }

}


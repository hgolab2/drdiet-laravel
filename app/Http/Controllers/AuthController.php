<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\TokenRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;



class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }




    /**
     * @OA\Get(
     *     path="/getuser",
     *     operationId="getUser",
     *     tags={"Auth"},
     *     summary="Get Authenticated User",
     *     description="Retrieve the authenticated user's profile data",
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved successfully",
     *         @OA\JsonContent(

     *         )
     *     )
     * )
     */

    public function getUser(Request $request)
    {
        $user = Auth::user();
        return response()->json([
            'user' => $user
        ], 200);
    }





    public function deleteUser(Request $request)
    {
        $user = Auth::user();
        $user->deleted = true;
        $user->deleted_at = Carbon::now();
        $user->save();
        $delete_time = $user->deleted_at->addDays(3);
        return response()->json([
            'user' => $user->username,
            'Time to request deletion' => $user->deleted_at,
            'Time to delete' => $delete_time->toDateString(),
            'status' => 'حساب کاربری شما سه روز دیگر حذف خواهد شد. در صورت وارد شدن به حساب کاربری این درخواست شما لغو می‌شود.',
        ], 200);
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flush(); //clears out all the exisiting sessions
        return redirect('/loginform');
        //dd('ddd');
        // Auth::logout();
        // $request->session()->invalidate(); // انقضا جلسه
        // $request->session()->regenerateToken(); // بازتولید توکن CSRF
    }


    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'new_password' => 'required|string|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $user = Auth::user();
        $password = $request->input('password');
        $new_password = $request->input('new_password');
        $re_new_password = $request->input('new_password_confirmation');
        if (Hash::check($password, $user->password)) {
            if ($new_password === $re_new_password) {
                $user->password = Hash::make($new_password);
                $user->save();
                return response()->json(['status' => true, 'detail' => 'پسورد با موفقیت تغییر کرد.'], 202);
            } else {
                return response()->json(['status' => false, 'detail' => 'پسورد و تکرار پسورد مطابقت ندارد.'], 400);
            }
        } else {
            return response()->json(['status' => false, 'detail' => 'پسورد صحیح نیست.'], 400);
        }
    }


    public function getAllUsers(Request $request)
    {
        $users = User::all();
        return response()->json(['users' => $users], 200);
    }
}

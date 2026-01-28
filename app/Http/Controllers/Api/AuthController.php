<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'last_name' => $googleUser->getName(),


            ]
        );

        $tokenResult = $user->createToken('authToken');
        $token = $tokenResult->accessToken;

        return redirect()->away('https://di3t-club.com/login/callback?token=' . $token);
    }
    /**
     * @OA\Post(
     *     path="/api/createUser",
     *     operationId="createUser",
     *     tags={"Authentication"},
     *     summary="Create a new user",
     *     description="Create a new user with the provided data",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","phone","first_name","last_name","password","password_confirmation"},
     *             @OA\Property(property="username", type="string", example="hossein123"),
     *             @OA\Property(property="phone", type="string", example="09123456789"),
     *             @OA\Property(property="email", type="string", format="email", example="hossein@example.com"),
     *             @OA\Property(property="first_name", type="string", example="Hossein"),
     *             @OA\Property(property="last_name", type="string", example="Golab"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male"),
     *             @OA\Property(property="birth_date", type="string", format="date"),
     *             @OA\Property(property="password", type="string", format="password", example="12345678"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="12345678")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر جدید با موفقیت ایجاد شد."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="phone", type="string", example="09123456789"),
     *                 @OA\Property(property="email", type="string", example="hossein@example.com"),
     *                 @OA\Property(property="first_name", type="string", example="Hossein"),
     *                 @OA\Property(property="last_name", type="string", example="Golab"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="birth_date", type="string", format="date"),
     *                 @OA\Property(property="created_at", type="string", example="2025-08-03T15:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-08-03T15:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={
     *                 "username": {"The username field is required."},
     *                 "password": {"The password confirmation does not match."}
     *             })
     *         )
     *     )
     * )
     */

    public function createUser(Request $request)
    {
        // اعتبارسنجی ورودی‌ها
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|unique:diet_users,phone',
            'email' => 'required|email|unique:diet_users,email', // ایمیل ضروری میشه
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'gender' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // داده‌ها را می‌گیریم
        $data = $request->only([
            'phone', 'email', 'first_name', 'last_name',
            'gender', 'birth_date'
        ]);

        // تولید پسورد ۵ رقمی
        $plainPassword = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

        // هش کردن رمز عبور
        $data['password'] = Hash::make($plainPassword);

        // مقادیر پیش‌فرض
        $data['inactive'] = 0;

        $data['login_token'] = Str::random(64);

        // ساخت کاربر جدید
        $user = User::create($data);

        $loginLink = 'https://di3t-club.com/login/callback-email?token=' . $data['login_token'];
        // ارسال ایمیل به کاربر
        Mail::send('emails.welcome_password', [
            'user' => $user,
            'password' => $plainPassword,
            'loginLink' => $loginLink,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                    ->subject('Di3t Club');
        });

        return response()->json([
            'status' => true,
            'message' => 'کاربر جدید با موفقیت ایجاد شد و پسورد به ایمیل ارسال شد.',
            'user' => $user
        ], 201);
    }
}

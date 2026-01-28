<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\TokenRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\Session;
use App\Models\VerifyLogin;
use Carbon\Carbon;
/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="عملیات‌های ورود، ثبت‌نام، فراموشی رمز عبور و تأیید موبایل"
 * )
 */
class LoginController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login-token",
     *     summary="Login user by permanent token",
     *     description="Logs in a user using their permanent login_token and returns an access token.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="a1b2c3d4e5f6g7h8i9j0")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access", type="string", example="1|XoA3pQe6k9..."),
     *             @OA\Property(property="refresh", type="string", example="1|XoA3pQe6k9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid token")
     *         )
     *     )
     * )
     */
    public function loginToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = User::where('login_token', $request->token)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        if ($user->inactive == 1) {
            return response()->json(['status' => false, 'detail' => 'User is not active.'], 400);
        }

        $tokenResult = $user->createToken('Personal Access Token');

        $token = $tokenResult->token;
        $token->save();
        return response()->json([
            'access' => $tokenResult->accessToken,
            'refresh' => $tokenResult->accessToken,
        ]);

        return response()->json([
            'access' => $token,
            'refresh' => $token,
        ]);

    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login user and get access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "password"},
     *             @OA\Property(property="username", type="string", example="demo"),
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access", type="string"),
     *             @OA\Property(property="refresh", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid credentials"
     *     )
     * )
     */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'detail' => $validator->errors()], 400);
        }

        $user = User::where('email', $request->username)->first();

        if ($user && Hash::check($request->password, $user->password)) {

            if ($user->inactive == 1) {
                return response()->json(['status' => false, 'detail' => 'User is not active.'], 400);
            }

            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            $token->save();

            // گرفتن رول‌ها
            $roles = $user->getRoleNames(); // خروجی: ["admin", "editor", ...]

            return response()->json([
                'access' => $tokenResult->accessToken,
                'refresh' => $tokenResult->accessToken,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $roles,
                ]
            ]);
        } else {
            return response()->json(['status' => false, 'detail' => 'Invalid credentials.'], 400);
        }
    }



    /**
     * @OA\Post(
     *     path="/register",
     *     operationId="register",
     *     summary="ثبت‌نام کاربر جدید",
     *     description="این متد برای ثبت‌نام کاربران جدید استفاده می‌شود.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="mobile", type="string", example="09123456789"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="code", type="string", example="12345"),
     *             @OA\Property(property="confirmPassword", type="string", example="password123"),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="birthDate", type="string", example="1990-01-01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="ثبت‌نام موفقیت‌آمیز"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="خطا در ثبت‌نام"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',  // اصلاح شده
            'last_name' => 'required',  // اصلاح شده
            'mobile' => 'required',  // اصلاح شده
            'password' => 'required',  // اصلاح شده
            'code' => 'required',
            'confirmPassword' => 'required',  // اصلاح شده
            'gender' => 'nullable',
            'birthDate' => 'nullable',
        ]);
        $verify = VerifyLogin::where('mobile', $request->mobile)->where('expiredate', '>=', Carbon::now())->latest()->first();
        if ($verify["code"] != $request->code) {
            return response()->json(['status' => false, 'detail' => $validator->errors()], 401);
        }
        if ($validator->fails()) {
            return response()->json(['status' => false, 'detail' => $validator->errors()], 400);
        }
        $data = $request->all();
        $input['gender'] = !empty($data["gender"]) ? $data["gender"] : null;
        $input['birth_date'] = validateAndConvertDate($data["birthDate"]);
        $input['phone'] = $data["mobile"];
        $input['password'] = Hash::make($data['password']);
        $input['username'] = $data['mobile'];
        $input["is_superuser"] = 0;
        //$input["is_staff"] = 0;
        $input["is_active"] = 1;
        $input["first_name"] = $data['first_name'];
        $input["last_name"] = $data['last_name'];
        //$input["date_joined"] = Carbon::now();
        //$input["deleted"] = 0;
        $user = User::create($input);
        return response()->json(['phone' => $user->phone], 201);
    }


    /**
     * @OA\Post(
     *     path="/forget",
     *     operationId="forget",
     *     summary="فراموشی رمز عبور",
     *     description="این متد برای بازنشانی رمز عبور از طریق کد تأیید ارسال‌شده استفاده می‌شود.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="mobile", type="string", example="09123456789"),
     *             @OA\Property(property="code", type="string", example="12345"),
     *             @OA\Property(property="password", type="string", example="newpassword123"),
     *             @OA\Property(property="confirmPassword", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="رمز عبور با موفقیت بازنشانی شد"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="خطا در فرایند بازنشانی رمز عبور"
     *     )
     * )
     */
    public function forget(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'code' => 'required|string',
            'password' => 'required|string',
            'confirmPassword' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $verify = VerifyLogin::where('mobile', $request->mobile)->where('expiredate', '>=', Carbon::now())->latest()->first();
        if ($verify["code"] != $request->code) {
            return response()->json(['status' => false, 'detail' => $validator->errors()], 401);
        }
        $inputs["password"] = Hash::make($request->password);;
        $user = User::where('username', $request->mobile)->first();
        //dd($user);
        $user->update($inputs);
        return 1;
    }
}

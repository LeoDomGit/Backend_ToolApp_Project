<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\Createuser;
use Inertia\Inertia;

class UserController extends Controller
{
    protected $model;
    public function __construct()
    {
        $this->model = User::class;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = $this->model::with('roles')->get();
        $roles = Roles::all();
        return Inertia::render('Users/Index', ['roles' => $roles, 'users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }
    /**
     * Display the specified resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data = $request->all();
        $password = random_int(10000, 99999);
        $data['password'] = Hash::make($password);
        $data['role_id'] = 1;
        User::create($data);
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
        ];
        Mail::to($request->email)->send(new Createuser($data));
        $users = $this->model::with('roles')->get();
        return response()->json(['check' => true, 'data' => $users]);
    }
    /**
     * Display the specified resource.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email|unique:users,email',
            'idRole' => 'exists:roles,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data = $request->all();
        if ($request->has('status')) {
            User::where('id', $id)->update(['status' => $request->status, 'updated_at' => now()]);
        } else {
            User::where('id', $id)->update($data);
        }

        $users = User::with('roles')->get();
        return response()->json(['check' => true, 'data' => $users]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function RegisterWithEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data = $request->all();
        $password = random_int(10000, 99999);
        $data['password'] = Hash::make($password);
        $data['name'] = $request->email;
        $data['idRole'] = 2;
        User::create($data);
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
        ];
        Mail::to($request->email)->send(new Createuser($data));
        $finduser = User::where('email', $request->email)->first();
        $token = $finduser->createToken('user')->plainTextToken;
        return response()->json(['check' => true, 'token' => $token, 'id' => Auth::id()]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function LoginWithEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $finduser = User::where('email', $request->email)->where('status', 1)->first();
        if (!$finduser) {
            return response()->json(['check' => false, 'msg' => 'Tài khoản bị khoá. Vui lòng liên hệ admin']);
        }
        $token = $finduser->createToken('user')->plainTextToken;
        return response()->json(['check' => true, 'token' => $token, 'id' => Auth::id()]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($identifier)
    {
        User::where('id', $identifier)->delete();
        $data = $this->model::with('roles')->get();
        return response()->json(['check' => true, 'data' => $data], 200);
    }
    public function LoginIndex()
    {
        return Inertia::render('Users/Login');
    }

    public function Logout()
    {
        Session::forget('user');
        Auth::logout();
        request()->session()->regenerate();
        return redirect()->route('login');
    }
    public function Login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data = $request->all();
        if (Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 1], isset($data['remember_token']) ? true : false)) {
            Session::regenerateToken();
            $data = Auth::user();
            Session::put('user_session', $data);
            return response()->json(['check' => true, 'msg' => 'Đăng nhập thành công!'], 200);
        } else {
            return response()->json(['check' => false, 'msg' => 'Sai email hoặc mật khẩu']);
        }
    }
    public function checkLoginEmailAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $user = User::where('email', $request->email)->where('status', 1)->first();
        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();
            Session::put('user', $user);
            return response()->json(['check' => true, 'msg' => 'Đăng nhập thành công!'], 200);
        } else {
            // Return failure response if no user is found
            return response()->json(['check' => false, 'msg' => 'Login failed. User not found.']);
        }
    }
    public function checkLoginAdmin1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $data = $request->all();
            if (Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 1], isset($data['remember_token']) ? true : false)) {
                Session::regenerateToken();
                $data = Auth::user();
                Session::put('user', $data);
                return response()->json(['check' => true, 'msg' => 'Đăng nhập thành công!'], 200);
            } else {
                return response()->json(['check' => false, 'msg' => 'Sai email hoặc mật khẩu']);
            }
        } else {
            return response()->json(['check' => false, 'msg' => 'Login failed. User not found.']);
        }
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json(['check' => false, 'msg' => $validator->errors()->first()]);
        }
        $data = $request->all();
        $password = random_int(10000, 99999);
        $data['password'] = Hash::make($password);
        $data['idRole'] = 2;
        User::create($data);
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
        ];
        Mail::to($request->email)->send(new Createuser($data));
        $users = $this->model::with('roles')->get();
        return response()->json(['check' => true]);
    }
}

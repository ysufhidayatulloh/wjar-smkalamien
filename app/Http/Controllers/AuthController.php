<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPassword;
use App\Models\Guru;
use App\Models\Admin;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Token;
use App\Services\OtpService;
use Illuminate\Support\Str;
use App\Mail\VerifikasiAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;

class AuthController extends Controller
{
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function index()
    {
        if (session('admin') != null) {
            return redirect('/admin');
        }

        return view('auth.login', [
            "title" => "W-JAR | Login Account",
            "admin" => Admin::all()
        ]);
    }
    public function login(Request $request)
    {
        $admin = Admin::firstWhere('email', $request->input('email'));
        if ($admin) {
            if (Hash::check($request->input('password'), $admin->password)) {
                $request->session()->put('id', $admin->id);
                $request->session()->put('email', $admin->email);
                $request->session()->put('role', 1);
                return redirect()->intended('/admin')->with('pesan', "
                    <script>
                        swal({
                            title: 'Berhasil!',
                            text: 'login berhasil',
                            type: 'success',
                            padding: '2em'
                        })
                    </script>
                ");
            } else {
                return redirect('/')->with('pesan', "
                    <script>
                        swal({
                            title: 'Login Failed!',
                            text: 'Password salah',
                            type: 'error',
                            padding: '2em'
                        })
                    </script>
                ");
            }
        }

        $guru = Guru::firstWhere('email', $request->input('email'));
        if ($guru) {

            if ($guru->is_active == 0) {
                return redirect('/')->with('pesan', "
                    <script>
                        swal({
                            title: 'Login Failed!',
                            text: 'akun tidak aktif',
                            type: 'error',
                            padding: '2em'
                        })
                    </script>
                ");
            }

            if (Hash::check($request->input('password'), $guru->password)) {
                $request->session()->put('id', $guru->id);
                $request->session()->put('email', $guru->email);
                $request->session()->put('nama_guru', $guru->nama_guru);
                $request->session()->put('role', 2);
                return redirect()->intended('/guru')->with('pesan', "
                    <script>
                        swal({
                            title: 'Berhasil!',
                            text: 'login berhasil',
                            type: 'success',
                            padding: '2em'
                        })
                    </script>
                ");
            } else {
                return redirect('/')->with('pesan', "
                    <script>
                        swal({
                            title: 'Login Failed!',
                            text: 'Password salah',
                            type: 'error',
                            padding: '2em'
                        })
                    </script>
                ");
            }
        }

        $siswa = Siswa::firstWhere('email', $request->input('email'));
        if ($siswa) {

            if ($siswa->is_active == 0) {
                return redirect('/')->with('pesan', "
                    <script>
                        swal({
                            title: 'Login Failed!',
                            text: 'akun tidak aktif',
                            type: 'error',
                            padding: '2em'
                        })
                    </script>
                ");
            }

            if (Hash::check($request->input('password'), $siswa->password)) {
                $request->session()->put('id', $siswa->id);
                $request->session()->put('email', $siswa->email);
                $request->session()->put('role', 3);
                return redirect()->intended('/siswa')->with('pesan', "
                    <script>
                        swal({
                            title: 'Berhasil!',
                            text: 'login berhasil',
                            type: 'success',
                            padding: '2em'
                        })
                    </script>
                ");
            } else {
                return redirect('/')->with('pesan', "
                    <script>
                        swal({
                            title: 'Login Failed!',
                            text: 'Password salah',
                            type: 'error',
                            padding: '2em'
                        })
                    </script>
                ");
            }
        }

        return redirect('/')->with('pesan', "
            <script>
                swal({
                    title: 'Login Failed!',
                    text: 'Akun tidak ditemukan',
                    type: 'error',
                    padding: '2em'
                })
            </script>
        ");
    }

    public function install()
    {
        $admin = Admin::all();
        if ($admin->count() != 0) {
            return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'Akun admin sudah dibuat',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ");
        }

        return view('auth.install', [
            "title" => "Installasi Admin"
        ]);
    }
    public function install_(Request $request)
    {
        $validate = $request->validate([
            'nama_admin' => 'required',
            'email' => 'required|email:dns',
            'password' => 'required',
        ]);

        $validate['password'] = Hash::make($validate['password']);
        Admin::create($validate);
        return redirect('/')->with('pesan', "
            <script>
                swal({
                    title: 'Berhasil!',
                    text: 'Akun admin berhasil dibuat',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }

    public function register()
    {
        $admin = Admin::all();
        if ($admin->count() == 0) {
            return redirect()->action([AuthController::class, 'index']);
        }

        return view('auth.register', [
            "title" => "W-JAR | Register Account",
            "kelas" => Kelas::all()
        ]);
    }
    public function register_(Request $request)
    {
        if ($request->input('saya_siswa')) {
            $validate = $request->validate([
                'email' => 'unique:admins'
            ]);
            $validate = $request->validate([
                'email' => 'unique:guru'
            ]);

            $validate = $request->validate([
                'nis' => 'required|unique:siswa',
                'nama' => 'required',
                'gender' => 'required',
                'kelas_id' => 'required',
                'email' => 'required|email:dns|unique:siswa',
                'password' => 'required',
            ]);

            $validate['nama_siswa'] = $validate['nama'];
            $validate['password'] = Hash::make($validate['password']);

            $tokens = [
                'token' => Str::random(40),
                'email' => $validate['email'],
                'key' => 'aktivasi',
                'role' => 3,
            ];
            $details = [
                'nama' => $request->nama,
                'email' => $request->email,
                'password' => $request->password,
                'token' => $tokens['token']
            ];
            Mail::to("$request->email")->send(new VerifikasiAkun($details));
            Siswa::create($validate);
            Token::create($tokens);

            return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Berhasil!',
                        text: 'silahkan buka email untuk aktivasi akunmu',
                        type: 'success',
                        padding: '2em'
                    })
                </script>
            ");
        }

        if ($request->input('saya_siswa') == null) {
            $validate = $request->validate([
                'email' => 'unique:admins'
            ]);
            $validate = $request->validate([
                'email' => 'unique:siswa'
            ]);
            $validate = $request->validate([
                'nama' => 'required',
                'gender' => 'required',
                'email' => 'required|email:dns|unique:guru',
                'password' => 'required',
            ]);

            $validate['nama_guru'] = $validate['nama'];
            $validate['password'] = Hash::make($validate['password']);


            $tokens = [
                'token' => Str::random(40),
                'email' => $validate['email'],
                'key' => 'aktivasi',
                'role' => 2,
            ];

            $details = [
                'nama' => $request->nama,
                'email' => $request->email,
                'password' => $request->password,
                'token' => $tokens['token']
            ];
            Mail::to("$request->email")->send(new VerifikasiAkun($details));
            Guru::create($validate);
            Token::create($tokens);
            return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Berhasil!',
                        text: 'silahkan buka email untuk aktivasi akunmu',
                        type: 'success',
                        padding: '2em'
                    })
                </script>
            ");
        }
    }
    public function aktivasi(Token $token)
    {
        if ($token->created_at->diffInMinutes() > 60) {

            if ($token->role == 2) {
                Guru::where('email', $token->email)
                    ->delete();
            } else {
                Siswa::where('email', $token->email)
                    ->delete();
            }
            Token::where('id', $token->id)
                ->delete();

            return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Token Expired!',
                        text: 'token sudah kadaluarsa, silahkan lakukan daftar ulang',
                        type: 'warning',
                        padding: '2em'
                    })
                </script>
            ");
        }

        if ($token->role == 2) {
            Guru::where('email', $token->email)
                ->update(['is_active' => 1]);
        } else {
            Siswa::where('email', $token->email)
                ->update(['is_active' => 1]);
        }

        Token::where('id', $token->id)
            ->delete();

        return redirect('/')->with('pesan', "
            <script>
                swal({
                    title: 'Success!',
                    text: 'Akun sudah aktif, silahkan login',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }

    public function recovery()
    {
        $admin = Admin::all();
        if ($admin->count() == 0) {
            return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'Akun admin belum dibuat',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ");
        }

        return view('auth.recovery', [
            'title' => 'Lupa Password'
        ]);
    }
    public function recovery_(Request $request)
    {
        $user = null;
        $admin = Admin::firstWhere('email', $request->input('email'));
        if ($admin) {
            $user = $admin;
        }
        $guru = Guru::firstWhere('email', $request->input('email'));
        if ($guru) {
            $user = $guru;
        }
        $siswa = Siswa::firstWhere('email', $request->input('email'));
        if ($siswa) {
            $user = $siswa;
        }

        if ($user == null) {
            return redirect('/recovery')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'email tidak ditemukan, silahkan coba lagi',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ");
        }

        $tokens = [
            'token' => Str::random(40),
            'email' => $request->email,
            'key' => 'password',
            'role' => $user->role
        ];
        $details = [
            'email' => $request->email,
            'token' => $tokens['token']
        ];
        Mail::to("$request->email")->send(new ForgotPassword($details));
        Token::create($tokens);

        return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Berhasil!',
                        text: 'silahkan buka email untuk validasi lupa password',
                        type: 'success',
                        padding: '2em'
                    })
                </script>
            ");
    }

    public function generateOtp(Request $request)
    {

        $request->validate([
            'email' => 'required|email'
        ]);

        $user = Guru::firstWhere('email', $request->email) ??
                Siswa::firstWhere('email', $request->email);

        if (!$user) {
            return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'Email tidak ditemukan',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ");
        }

        
        $otp = $this->otpService->generateTOTP();

        $tokens = [
            'token' => Str::random(40),
            'email' => $request->email,
            'key' => 'password',
            'role' => $user->role
        ];
        $details = [
            'email' => $request->email,
            'otp' => $otp,
            'token' => $tokens['token']
        ];

        
        session([
            'otp' => $otp, 
            'otp_email' => $request->email, 
            'token' => $tokens['token']
        ]);

        
        Mail::to("$request->email")->send(new ForgotPassword($details));
        Token::create($tokens);

        return redirect('verify_otp')->with('pesan', 'OTP telah dikirim ke email Anda.');
    }

    public function resendOtp(Request $request)
    {
        
        $request->validate([
            'email' => 'required|email'
        ]);

        
        $user = Guru::firstWhere('email', $request->email) ??
                Siswa::firstWhere('email', $request->email);

        
        if (!$user) {
            return redirect()->back()->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'Email tidak ditemukan.',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ");
        }

        
        $otp = $this->otpService->generateTOTP();

        
        session(['otp' => $otp, 'otp_email' => $request->email]);

        
        $token = Str::random(40);
        $tokens = [
            'token' => $token,
            'email' => $request->email,
            'key' => 'password',
            'role' => $user->role
        ];

        
        $details = [
            'email' => $request->email,
            'otp' => $otp,
            'token' => $token
        ];

        session([
            'otp' => $otp, 
            'otp_email' => $request->email, 
            'token' => $tokens['token']
        ]);

        
        Mail::to($request->email)->send(new ForgotPassword($details));
        Token::create($tokens);

        
        return redirect('verify_otp')->with('pesan', 'OTP telah dikirim ke email Anda.');
    }

    public function verifyOtp(Token $token, Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);
    
        
        $storedOtp = session('otp');
        $email = session('otp_email');
        $storedToken = session('token');
    
        
        if ($this->otpService->verifyTOTP($request->otp) && $request->otp == $storedOtp) {
            
            if (!$storedToken) {
                return redirect()->back()->with('pesan', 'Token tidak valid.');
            }
            
            
            session()->forget(['otp', 'otp_email', 'token']);
            
            
            return redirect("/change_password/{$storedToken}")->with('pesan', 'OTP valid, silakan ubah password Anda.');
        }
    
        
        return redirect()->back()->with('pesan', 'OTP tidak valid atau sudah kadaluarsa.');
    }


    public function change_password(Token $token)
    {
        if ($token->created_at->diffInMinutes() > 60) {
            Token::where('id', $token->id)
                ->delete();

            return redirect('/')->with('pesan', "
                <script>
                    swal({
                        title: 'Token Expired!',
                        text: 'token sudah kadaluarsa, silahkan ulangi proses',
                        type: 'warning',
                        padding: '2em'
                    })
                </script>
            ");
        }

        return view('auth.change-password', [
            'token' => $token
        ]);
    }
    
    public function change_password_(Token $token, Request $request)
    {
        $password = bcrypt($request->password);
        if ($token->role == 1) {
            Admin::where('email', $token->email)
                ->update(['password' => $password]);
        }
        if ($token->role == 2) {
            Guru::where('email', $token->email)
                ->update(['password' => $password]);
        }
        if ($token->role == 3) {
            Siswa::where('email', $token->email)
                ->update(['password' => $password]);
        }
        Token::where('id', $token->id)
            ->delete();

        return redirect('/')->with('pesan', "
            <script>
                swal({
                    title: 'Berhasil!',
                    text: 'password telah di ubah, silahkan login',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }

    public function logout(Request $request)
    {
        $request->session()->remove('id');
        $request->session()->remove('email');
        $request->session()->remove('role');

        return redirect('/')->with('pesan', "
            <script>
                swal({
                    title: 'Berhasil!',
                    text: 'Anda telah logout',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");

        return redirect('/');
    }
}

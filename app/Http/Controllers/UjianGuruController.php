<?php

namespace App\Http\Controllers;

use App\Exports\EssayExport;
use App\Exports\PgExport;
use App\Models\Guru;
use App\Models\Ujian;
use App\Models\Gurukelas;
use App\Models\Gurumapel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Imports\PgImport;
use App\Mail\NotifUjian;
use App\Models\BanksoalModel;
use App\Models\DetailbankessayModel;
use App\Models\DetailbankpgModel;
use App\Models\DetailEssay;
use App\Models\DetailUjian;
use App\Models\EmailSettings;
use App\Models\EssaySiswa;
use App\Models\PgSiswa;
use App\Models\Siswa;
use App\Models\WaktuUjian;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class UjianGuruController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('guru.ujian.index', [
            'title' => 'Data Ujian',
            'plugin' => '
                <link rel="stylesheet" type="text/css" href="' . url("/assets/cbt-malela") . '/plugins/table/datatable/datatables.css">
                <link rel="stylesheet" type="text/css" href="' . url("/assets/cbt-malela") . '/plugins/table/datatable/dt-global_style.css">
                <script src="' . url("/assets/cbt-malela") . '/plugins/table/datatable/datatables.js"></script>
                <script src="https://cdn.datatables.net/fixedcolumns/4.1.0/js/dataTables.fixedColumns.min.js"></script>
            ',
            'menu' => [
                'menu' => 'ujian',
                'expanded' => 'ujian'
            ],
            'guru' => Guru::firstWhere('id', session()->get('id')),
            'ujian' => Ujian::where('guru_id', session()->get('id'))->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('guru.ujian.create', [
            'title' => 'Tambah Ujian Pilihan Ganda',
            'plugin' => '
                <link href="' . url("/assets/cbt-malela") . '/plugins/file-upload/file-upload-with-preview.min.css" rel="stylesheet" type="text/css" />
                <script src="' . url("/assets/cbt-malela") . '/plugins/file-upload/file-upload-with-preview.min.js"></script>
                <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
                <link rel="stylesheet" type="text/css" href="' . url("/assets/cbt-malela") . '/plugins/table/datatable/datatables.css">
                <link rel="stylesheet" type="text/css" href="' . url("/assets/cbt-malela") . '/plugins/table/datatable/dt-global_style.css">
                <script src="' . url("/assets/cbt-malela") . '/plugins/table/datatable/datatables.js"></script>
            ',
            'menu' => [
                'menu' => 'ujian',
                'expanded' => 'ujian'
            ],
            'guru' => Guru::firstWhere('id', session()->get('id')),
            'guru_kelas' => Gurukelas::where('guru_id', session()->get('id'))->get(),
            'guru_mapel' => Gurumapel::where('guru_id', session()->get('id'))->get(),
            'bank_soal' => BanksoalModel::where('guru_id', session()->get('id'))->get(),
        ]);
    }
    public function create_essay()
    {
        return view('guru.ujian.create-essay', [
            'title' => 'Tambah Ujian Essay',
            'plugin' => '
                <link href="' . url("/assets/cbt-malela") . '/plugins/file-upload/file-upload-with-preview.min.css" rel="stylesheet" type="text/css" />
                <script src="' . url("/assets/cbt-malela") . '/plugins/file-upload/file-upload-with-preview.min.js"></script>
                <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
            ',
            'menu' => [
                'menu' => 'ujian',
                'expanded' => 'ujian'
            ],
            'guru' => Guru::firstWhere('id', session()->get('id')),
            'guru_kelas' => Gurukelas::where('guru_id', session()->get('id'))->get(),
            'guru_mapel' => Gurumapel::where('guru_id', session()->get('id'))->get(),
            'bank_soal' => BanksoalModel::where('guru_id', session()->get('id'))->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $siswa = Siswa::where('kelas_id', $request->kelas)->get();
        if ($siswa->count() == 0) {
            return redirect('/guru/ujian/create')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'belum ada siswa di kelas tersebut!',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ")->withInput();
        }

        $kode = Str::random(30);
        $ujian = [
            'kode' => $kode,
            'nama' => $request->nama,
            'jenis' => 0,
            'guru_id' => session()->get('id'),
            'kelas_id' => $request->kelas,
            'mapel_id' => $request->mapel,
            'jam' => $request->jam,
            'menit' => $request->menit,
            'acak' => $request->acak,
        ];

        $detail_ujian = [];
        $index = 0;
        $nama_soal =  $request->soal;
        foreach ($nama_soal as $soal) {
            array_push($detail_ujian, [
                'kode' => $kode,
                'soal' => $soal,
                'pg_1' => 'A. ' . $request->pg_1[$index],
                'pg_2' => 'B. ' . $request->pg_2[$index],
                'pg_3' => 'C. ' . $request->pg_3[$index],
                'pg_4' => 'D. ' . $request->pg_4[$index],
                'pg_5' => 'E. ' . $request->pg_5[$index],
                'jawaban' => $request->jawaban[$index]
            ]);

            $index++;
        }

        $email_siswa = '';
        $waktu_ujian = [];
        foreach ($siswa as $s) {
            $email_siswa .= $s->email . ',';

            array_push($waktu_ujian, [
                'kode' => $kode,
                'siswa_id' => $s->id
            ]);
        }

        $email_siswa = Str::replaceLast(',', '', $email_siswa);
        $email_siswa = explode(',', $email_siswa);

        $email_settings = EmailSettings::first();
        if ($email_settings->notif_ujian == '1') {
            $details = [
                'nama_guru' => session()->get('nama_guru'),
                'nama_ujian' => $request->nama,
                'jam' => $request->jam,
                'menit' => $request->menit,
            ];
            Mail::to($email_siswa)->send(new NotifUjian($details));
        }


        Ujian::insert($ujian);
        DetailUjian::insert($detail_ujian);
        WaktuUjian::insert($waktu_ujian);

        return redirect('/guru/ujian')->with('pesan', "
            <script>
                swal({
                    title: 'Success!',
                    text: 'ujian sudah di posting!',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }
    public function pg_excel(Request $request)
    {
        $siswa = Siswa::where('kelas_id', $request->e_kelas)->get();
        if ($siswa->count() == 0) {
            return redirect('/guru/ujian/create')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'belum ada siswa di kelas tersebut!',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ")->withInput();
        }

        $kode = Str::random(30);
        $ujian = [
            'kode' => $kode,
            'nama' => $request->e_nama_ujian,
            'jenis' => 0,
            'guru_id' => session()->get('id'),
            'kelas_id' => $request->e_kelas,
            'mapel_id' => $request->e_mapel,
            'jam' => $request->e_jam,
            'menit' => $request->e_menit,
            'acak' => $request->e_acak,
        ];

        $email_siswa = '';
        $waktu_ujian = [];
        foreach ($siswa as $s) {
            $email_siswa .= $s->email . ',';

            array_push($waktu_ujian, [
                'kode' => $kode,
                'siswa_id' => $s->id
            ]);
        }

        $email_siswa = Str::replaceLast(',', '', $email_siswa);
        $email_siswa = explode(',', $email_siswa);

        $email_settings = EmailSettings::first();
        if ($email_settings->notif_ujian == '1') {
            $details = [
                'nama_guru' => session()->get('nama_guru'),
                'nama_ujian' => $request->e_nama_ujian,
                'jam' => $request->e_jam,
                'menit' => $request->e_menit,
            ];
            Mail::to($email_siswa)->send(new NotifUjian($details));
        }

        Ujian::insert($ujian);
        Excel::import(new PgImport($kode), $request->excel);
        WaktuUjian::insert($waktu_ujian);

        return redirect('/guru/ujian')->with('pesan', "
            <script>
                swal({
                    title: 'Success!',
                    text: 'ujian sudah di posting!',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }
    public function store_bank_pg(Request $request)
    {
        $siswa = Siswa::where('kelas_id', $request->b_kelas)->get();
        if ($siswa->count() == 0) {
            return redirect('/guru/ujian/create')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'belum ada siswa di kelas tersebut!',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ")->withInput();
        }

        $detail_bank_soal = DetailbankpgModel::where('kode', $request->kode_bank)->get();

        if ($detail_bank_soal->count() == 0) {
            return redirect('/guru/ujian/create')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'Data Bank soal tidak ditemukan!',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ")->withInput();
        }

        $kode = Str::random(30);
        $ujian = [
            'kode' => $kode,
            'nama' => $request->b_nama_ujian,
            'jenis' => 0,
            'guru_id' => session()->get('id'),
            'kelas_id' => $request->b_kelas,
            'mapel_id' => $request->b_mapel,
            'jam' => $request->b_jam,
            'menit' => $request->b_menit,
            'acak' => $request->b_acak,
        ];

        $detail_ujian = [];
        $index = 0;
        foreach ($detail_bank_soal as $soal) {
            array_push($detail_ujian, [
                'kode' => $kode,
                'soal' => $soal->soal,
                'pg_1' => $soal->pg_1,
                'pg_2' => $soal->pg_2,
                'pg_3' => $soal->pg_3,
                'pg_4' => $soal->pg_4,
                'pg_5' => $soal->pg_5,
                'jawaban' => $soal->jawaban
            ]);

            $index++;
        }

        $email_siswa = '';
        $waktu_ujian = [];
        foreach ($siswa as $s) {
            $email_siswa .= $s->email . ',';

            array_push($waktu_ujian, [
                'kode' => $kode,
                'siswa_id' => $s->id
            ]);
        }

        $email_siswa = Str::replaceLast(',', '', $email_siswa);
        $email_siswa = explode(',', $email_siswa);

        $email_settings = EmailSettings::first();
        if ($email_settings->notif_ujian == '1') {
            $details = [
                'nama_guru' => session()->get('nama_guru'),
                'nama_ujian' => $request->b_nama_ujian,
                'jam' => $request->b_jam,
                'menit' => $request->b_menit,
            ];
            Mail::to($email_siswa)->send(new NotifUjian($details));
        }


        Ujian::insert($ujian);
        DetailUjian::insert($detail_ujian);
        WaktuUjian::insert($waktu_ujian);

        return redirect('/guru/ujian')->with('pesan', "
            <script>
                swal({
                    title: 'Success!',
                    text: 'ujian sudah di posting!',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }

    public function store_essay(Request $request)
    {
        $siswa = Siswa::where('kelas_id', $request->kelas)->get();
        if ($siswa->count() == 0) {
            return redirect('/guru/ujian_essay')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'belum ada siswa di kelas tersebut!',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ")->withInput();
        }

        $kode = Str::random(30);
        $ujian = [
            'kode' => $kode,
            'nama' => $request->nama,
            'jenis' => 1,
            'guru_id' => session()->get('id'),
            'kelas_id' => $request->kelas,
            'mapel_id' => $request->mapel,
            'jam' => $request->jam,
            'menit' => $request->menit,
            
        ];

        $detail_ujian = [];
        $index = 0;
        $nama_soal =  $request->soal;
        foreach ($nama_soal as $soal) {
            array_push($detail_ujian, [
                'kode' => $kode,
                'soal' => $soal
            ]);

            $index++;
        }

        $email_siswa = '';
        $waktu_ujian = [];
        foreach ($siswa as $s) {
            $email_siswa .= $s->email . ',';

            array_push($waktu_ujian, [
                'kode' => $kode,
                'siswa_id' => $s->id
            ]);
        }

        $email_siswa = Str::replaceLast(',', '', $email_siswa);
        $email_siswa = explode(',', $email_siswa);

        $email_settings = EmailSettings::first();
        if ($email_settings->notif_ujian == '1') {
            $details = [
                'nama_guru' => session()->get('nama_guru'),
                'nama_ujian' => $request->nama,
                'jam' => $request->jam,
                'menit' => $request->menit,
            ];
            Mail::to($email_siswa)->send(new NotifUjian($details));
        }

        Ujian::insert($ujian);
        DetailEssay::insert($detail_ujian);
        WaktuUjian::insert($waktu_ujian);

        return redirect('/guru/ujian')->with('pesan', "
            <script>
                swal({
                    title: 'Success!',
                    text: 'ujian sudah di posting!',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }
    public function store_bank_essay(Request $request)
    {
        $siswa = Siswa::where('kelas_id', $request->b_kelas)->get();
        if ($siswa->count() == 0) {
            return redirect('/guru/ujian_essay')->with('pesan', "
                <script>
                    swal({
                        title: 'Error!',
                        text: 'belum ada siswa di kelas tersebut!',
                        type: 'error',
                        padding: '2em'
                    })
                </script>
            ")->withInput();
        }

        $detail_bank_soal = DetailbankessayModel::where('kode', $request->kode_bank)->get();

        $kode = Str::random(30);
        $ujian = [
            'kode' => $kode,
            'nama' => $request->b_nama_ujian,
            'jenis' => 1,
            'guru_id' => session()->get('id'),
            'kelas_id' => $request->b_kelas,
            'mapel_id' => $request->b_mapel,
            'jam' => $request->b_jam,
            'menit' => $request->b_menit,
            
        ];

        $detail_ujian = [];
        $index = 0;
        foreach ($detail_bank_soal as $soal) {
            array_push($detail_ujian, [
                'kode' => $kode,
                'soal' => $soal->soal
            ]);

            $index++;
        }

        $email_siswa = '';
        $waktu_ujian = [];
        foreach ($siswa as $s) {
            $email_siswa .= $s->email . ',';

            array_push($waktu_ujian, [
                'kode' => $kode,
                'siswa_id' => $s->id
            ]);
        }

        $email_siswa = Str::replaceLast(',', '', $email_siswa);
        $email_siswa = explode(',', $email_siswa);

        $email_settings = EmailSettings::first();
        if ($email_settings->notif_ujian == '1') {
            $details = [
                'nama_guru' => session()->get('nama_guru'),
                'nama_ujian' => $request->b_nama_ujian,
                'jam' => $request->b_jam,
                'menit' => $request->b_menit,
            ];
            Mail::to($email_siswa)->send(new NotifUjian($details));
        }

        Ujian::insert($ujian);
        DetailEssay::insert($detail_ujian);
        WaktuUjian::insert($waktu_ujian);

        return redirect('/guru/ujian')->with('pesan', "
            <script>
                swal({
                    title: 'Success!',
                    text: 'ujian sudah di posting!',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ujian  $ujian
     * @return \Illuminate\Http\Response
     */
    public function show(Ujian $ujian)
    {
        return view('guru.ujian.show', [
            'title' => 'Detail Ujian Pilihan Ganda',
            'plugin' => '
                <link href="' . url("/assets") . '/ew/css/style.css" rel="stylesheet" type="text/css" />
                <script src="' . url("/assets") . '/ew/js/examwizard.js"></script>
            ',
            'menu' => [
                'menu' => 'ujian',
                'expanded' => 'ujian'
            ],
            'guru' => Guru::firstWhere('id', session()->get('id')),
            'ujian' => $ujian,
        ]);
    }
    public function pg_siswa($kode, $siswa_id)
    {
        $ujian_siswa = PgSiswa::where('kode', $kode)
            ->where('siswa_id', $siswa_id)
            ->get();
        return view('guru.ujian.show-siswa', [
            'title' => 'Detail Ujian Siswa',
            'plugin' => '
                <link href="' . url("/assets") . '/ew/css/style.css" rel="stylesheet" type="text/css" />
                <script src="' . url("/assets") . '/ew/js/examwizard.js"></script>
            ',
            'menu' => [
                'menu' => 'ujian',
                'expanded' => 'ujian'
            ],
            'guru' => Guru::firstWhere('id', session()->get('id')),
            'ujian_siswa' => $ujian_siswa,
            'ujian' => Ujian::firstWhere('kode', $kode),
            'siswa' => Siswa::firstWhere('id', $siswa_id)
        ]);
    }

    public function show_essay(Ujian $ujian)
    {
        return view('guru.ujian.show-essay', [
            'title' => 'Detail Ujian Essay',
            'plugin' => '
                <link href="' . url("/assets") . '/ew/css/style.css" rel="stylesheet" type="text/css" />
                <script src="' . url("/assets") . '/ew/js/examwizard.js"></script>
            ',
            'menu' => [
                'menu' => 'ujian',
                'expanded' => 'ujian'
            ],
            'guru' => Guru::firstWhere('id', session()->get('id')),
            'ujian' => $ujian,
        ]);
    }
    public function essay_siswa($kode, $siswa_id)
    {
        $ujian_siswa = EssaySiswa::where('kode', $kode)
            ->where('siswa_id', $siswa_id)
            ->get();
        return view('guru.ujian.show-essay-siswa', [
            'title' => 'Detail Ujian Essay Siswa',
            'plugin' => '
                <link href="' . url("/assets") . '/ew/css/style.css" rel="stylesheet" type="text/css" />
                <script src="' . url("/assets") . '/ew/js/examwizard.js"></script>
            ',
            'menu' => [
                'menu' => 'ujian',
                'expanded' => 'ujian'
            ],
            'guru' => Guru::firstWhere('id', session()->get('id')),
            'ujian_siswa' => $ujian_siswa,
            'ujian' => Ujian::firstWhere('kode', $kode),
            'siswa' => Siswa::firstWhere('id', $siswa_id)
        ]);
    }
    public function nilai_essay(Request $request)
    {
        EssaySiswa::where('id', $request->id)
            ->update(['nilai' => $request->nilai]);

        return 'berhasil dinilai';
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Ujian  $ujian
     * @return \Illuminate\Http\Response
     */
    public function edit(Ujian $ujian)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ujian  $ujian
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Ujian $ujian)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ujian  $ujian
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ujian $ujian)
    {

        WaktuUjian::where('kode', $ujian->kode)
            ->delete();

        if ($ujian->jenis == 0) {
            DetailUjian::where('kode', $ujian->kode)
                ->delete();

            PgSiswa::where('kode', $ujian->kode)
                ->delete();
        } else {
            DetailEssay::where('kode', $ujian->kode)
                ->delete();

            EssaySiswa::where('kode', $ujian->kode)
                ->delete();
        }

        Ujian::destroy($ujian->id);

        return redirect('/guru/ujian')->with('pesan', "
            <script>
                swal({
                    title: 'Success!',
                    text: 'ujian di hapus!',
                    type: 'success',
                    padding: '2em'
                })
            </script>
        ");
    }


    public function ujian_cetak($kode)
    {
        return view('guru.ujian.cetak-pg', [
            'ujian' => Ujian::firstWhere('kode', $kode)
        ]);
    }
    public function ujian_ekspor($kode)
    {
        $ujian =  Ujian::firstWhere('kode', $kode);
        $nama_kelas = $ujian->kelas->nama_kelas;
        return Excel::download(new PgExport($ujian), "nilai-pg-kelas-$nama_kelas.xlsx");
    }

    public function essay_cetak($kode)
    {
        return view('guru.ujian.cetak-essay', [
            'ujian' => Ujian::firstWhere('kode', $kode)
        ]);
    }
    public function essay_ekspor($kode)
    {
        $ujian =  Ujian::firstWhere('kode', $kode);
        $nama_kelas = $ujian->kelas->nama_kelas;
        return Excel::download(new EssayExport($ujian), "nilai-essay-kelas-$nama_kelas.xlsx");
    }

    public function ujian_reset($kode, $siswa_id) {
        $ujian = Ujian::firstWhere('kode', $kode);
        if ($ujian->jenis == 0) {
            // ujian pg
            $waktu_ujian = [
                'waktu_berakhir' => null, 
                'selesai' => null 
            ];
            WaktuUjian::where('kode', $kode)
                ->where('siswa_id', $siswa_id)
                ->update($waktu_ujian);
            PgSiswa::where('kode', $kode)
                ->where('siswa_id', $siswa_id)
                ->delete();
            
            return redirect('/guru/ujian' . '/' . $kode)->with('pesan', "
                <script>
                    swal({
                        title: 'Success!',
                        text: 'ujian siswa di reset!',
                        type: 'success',
                        padding: '2em'
                    })
                </script>
            ");
        }
        if ($ujian->jenis == 1) {
            // ujian essay
            $waktu_ujian = [
                'waktu_berakhir' => null, 
                'selesai' => null 
            ];
            WaktuUjian::where('kode', $kode)
                ->where('siswa_id', $siswa_id)
                ->update($waktu_ujian);
            EssaySiswa::where('kode', $kode)
                ->where('siswa_id', $siswa_id)
                ->delete();
            
            return redirect('/guru/ujian_essay' . '/' . $kode)->with('pesan', "
                <script>
                    swal({
                        title: 'Success!',
                        text: 'ujian siswa di reset!',
                        type: 'success',
                        padding: '2em'
                    })
                </script>
            ");
        }
    }
    public function ujian_reset_all($kode) {
        $ujian = Ujian::firstWhere('kode', $kode);
        if ($ujian->jenis == 0) {
            // ujian pg
            $waktu_ujian = [
                'waktu_berakhir' => null, 
                'selesai' => null 
            ];
            WaktuUjian::where('kode', $kode)
                ->update($waktu_ujian);
            PgSiswa::where('kode', $kode)
                ->delete();
            
            return redirect('/guru/ujian' . '/' . $kode)->with('pesan', "
                <script>
                    swal({
                        title: 'Success!',
                        text: 'ujian semua siswa di reset!',
                        type: 'success',
                        padding: '2em'
                    })
                </script>
            ");
        }
        if ($ujian->jenis == 1) {
            // ujian essay
            $waktu_ujian = [
                'waktu_berakhir' => null, 
                'selesai' => null 
            ];
            WaktuUjian::where('kode', $kode)
                ->update($waktu_ujian);
            EssaySiswa::where('kode', $kode)
                ->delete();
            
            return redirect('/guru/ujian_essay' . '/' . $kode)->with('pesan', "
                <script>
                    swal({
                        title: 'Success!',
                        text: 'ujian semua siswa di reset!',
                        type: 'success',
                        padding: '2em'
                    })
                </script>
            ");
        }
    }
}

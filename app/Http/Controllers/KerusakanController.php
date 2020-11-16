<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;
use App\Provinsi;
use App\KabupatenKota;
use App\Kecamatan;
use App\DesaKelurahan;
use App\Kerusakan;
use App\KerusakanDetail;
use App\KerusakanSurveyor;
use App\KerusakanKlasifikasi;
use App\Gedung;
use App\Komponen;
use App\KomponenOpsi;
use App\User;
use Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KerusakanController extends Controller
{
    public function index() {
        $kerusakan = Kerusakan::select('kerusakan.id as id','gedung.nama as nama_gedung', 'gedung_ketegori.nama as jenis_gd', 'gedung.alamat as alamat')
                                ->join('gedung', 'kerusakan.id_gedung', '=', 'gedung.id')
                                ->join('gedung_ketegori', 'gedung.id_gedung_kategori', '=', 'gedung_ketegori.id')
                                ->get();
        return view('Kerusakan/master_kerusakan', compact('kerusakan'));
    }

    public function pilihanGedung() {
        $gedung = Gedung::get();
        return view('Kerusakan/tambah_master_kerusakan', compact('gedung'));
    }

    public function formKerusakanSurveyor($id) {
        $input = Gedung::find($id);
        $session_name = Session::get('name');
        $surveyor = User::where('name', '=', $session_name)->first();
        return view('Kerusakan/formulir_kerusakan_surveyor', compact('input', 'surveyor'));
    }

    public function inputFormSurveyor(Request $request) {
        $id_gedung = $request->id_gedung;
        $tanggal = $request->tanggal;
        $jam = $request->jam;
        $tanggal_jam = $tanggal." ".$jam;
        $id_user = $request->id_user;
        Session::put('opd', $request->opd);
        Session::put('nomor_aset', $request->nomor_aset);
        Session::put('surveyor1', $request->surveyor1);
        Session::put('surveyor2', $request->surveyor2);
        Session::put('surveyor3', $request->surveyor3);
        Session::put('pwopd1', $request->pwopd1);
        Session::put('pwopd2', $request->pwopd2);


        return redirect()->action('KerusakanController@formIdentifikasiKerusakan', ['id_gedung' => $id_gedung, 'id_user' => $id_user]);
    }

    public function formIdentifikasiKerusakan($id_gedung, $id_user) {   
        $id_parents = DB::table('komponen as prnt')
            ->select('prnt.id_parent as id')
            ->join('komponen as chld', 'chld.id', '=', 'prnt.id_parent')
            ->groupBy('prnt.id_parent')
            ->get()->pluck('id')->toArray();
        $komponens = DB::table('komponen')
            ->select('id', 'nama')
            ->whereIn('id', $id_parents)
            ->get();
        foreach ($komponens as $parent) {
            $subKomponen = DB::table('komponen as kom')
                ->select('kom.id', 'kom.id_parent', 'kom.nama', 'kom.bobot', 'sat.id as id_satuan', 'sat.nama as satuan')
                ->join('satuan as sat', 'sat.id', '=', 'kom.id_satuan')
                ->where('id_parent', $parent->id)
                ->get();
            $parent->numberOfSub = count($subKomponen);
            $parent->subKomponen = $subKomponen;
        }
                    
        $gedung = Gedung::where('id', $id_gedung)->first();
        $daerah = Gedung::select('gedung.kode_provinsi', 'gedung.kode_kabupaten', 'gedung.kode_kecamatan', 'gedung.kode_kelurahan')->where('id', $id_gedung)->first();
        $provinsi = Provinsi::select('provinsi.nama as nama_provinsi')->where('id_prov', $daerah->kode_provinsi)->first();
        $kab_kota = KabupatenKota::select('kota.nama as nama_kota')->where('id_kota', $daerah->kode_kabupaten)->first();
        $kecamatan = Kecamatan::select('kecamatan.nama as nama_kecamatan')->where('id_kec', $daerah->kode_kecamatan)->first();
        $desa_kelurahan = DesaKelurahan::select('kelurahan.nama as nama_kelurahan')->where('id_kel', $daerah->kode_kelurahan)->first();
                  
        return view('Kerusakan/create_formulir_klasifikasi_kerusakan', compact('komponens', 'gedung', 'daerah', 'provinsi', 'kab_kota', 'kecamatan', 'desa_kelurahan', 'id_gedung', 'id_user'));
    }
    private function setCellDropdown($sheet, $cellAddr){
        $validation = $sheet->getCell($cellAddr)
            ->getDataValidation();
        $validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
        $validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Input error');
        $validation->setError('Value is not in list.');
        $validation->setPromptTitle('Pick from list');
        $validation->setPrompt('Please pick a value from the drop-down list.');
        $validation->setFormula1('"Item A,Item B,Item C"');
    }
    public function exportKerusakan(Request $request){
        
        $temp_file = storage_path('excel_template').'/temp_kerusakan.xlsx';
        $output_file = storage_path('app/public/excel/kerusakan').'/temp_kerusakan.xlsx';

        /** Load $inputFileName to a Spreadsheet object **/
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($temp_file);
        $sheet = $spreadsheet->getActiveSheet();
        
        /**
         * Header - detail gedung
         */

        $addr = [
            'OPD' => 'G3',
            'nama bangunan' => 'G4',
            'momor asset' => 'G5',
            'alamat' => 'G6',
            'alamat detail' => 'G7',
            'tgl survey' => 'G8'
        ];
        
        // petugas survey

        $sheet->insertNewRowBefore(10, 1);

        $writer = new Xlsx($spreadsheet);
        $writer->save($output_file);
        return $output_file;
        return redirect('master_gedung.xlsx');
    }

    public function submitKlasifikasiKerusakan(Request $request){
        $_klasifikasiKerusakan = [0.20, 0.40, 0.60, 0.80, 1.00];
        $timeUpload = strtotime("now");
        
        //File Bukti
        foreach ($request->gambar_bukti as $index => $gambar_bukti) {
            $buktiExt       = $gambar_bukti->getClientOriginalExtension();
            $buktiFileName  = "bukti_".$timeUpload.'_'.$index.'.'.$buktiExt;
            $gambar_bukti->move('bukti', $buktiFileName);
        }

        //Files Denah
        foreach ($request->sketsa_denah as $index => $denah) {  
            $denahExt    = $denah->getClientOriginalExtension();
            $denahFileName   =   "denah_".$timeUpload.'_'.$index.'.'.$denahExt;
            $denah->move('denah', $denahFileName);
        }

        // Create data ke table kerusakan
        $newKerusakan = new Kerusakan;
        $newKerusakan->id_gedung         = $request->id_gedung;
        $newKerusakan->tanggal           = date('Y-m-d H:i:s');
        $newKerusakan->opd               = Session::get('opd');
        $newKerusakan->nomor_aset        = Session::get('nomor_aset');
        $newKerusakan->petugas_survei1   = Session::get('surveyor1');
        $newKerusakan->petugas_survei2   = Session::get('surveyor2'); 
        $newKerusakan->petugas_survei3   = Session::get('surveyor3');
        $newKerusakan->perwakilan_opd1   = Session::get('pwopd1');
        $newKerusakan->perwakilan_opd2   = Session::get('pwopd2');
        $newKerusakan->created_at        = date('Y-m-d H:i:s');
        $newKerusakan->save();
        
        // Create data ke table kerusakan surveyor
        $newKerusakanSurveyor = new KerusakanSurveyor;
        $newKerusakanSurveyor->id_kerusakan  = $newKerusakan->id;
        $newKerusakanSurveyor->id_user       = $request->id_user;
        $newKerusakanSurveyor->save();

        $satuans = $request->get('satuans');
        foreach ($satuans as $index => $satuan) {
            $newKerusakanDetail = new KerusakanDetail;
            $newKerusakanDetail->id_kerusakan       = $newKerusakan->id;
            $newKerusakanDetail->id_komponen        = $request->get('komponens')[$index];
            $newKerusakanDetail->tingkat_kerusakan  = $request->get('tk_value')[$index];

            if($satuan == 1){
                $newKerusakanDetail->id_komponen_opsi   =  $request->get('val_estimasi_'.$index)[0];
                $newKerusakanDetail->save();
            }else if($satuan == 2){
                $newKerusakanDetail->save();
                $inputPersentases = $request->get('val_persentase_'.$index);
                foreach ($inputPersentases as $indexInput => $input) {
                    $input_klasifikasi = new KerusakanKlasifikasi;
                    $input_klasifikasi->id_kerusakan_detail     = $newKerusakanDetail->id;
                    $input_klasifikasi->nilai_input_klasifikasi = ($input) ? $input : 0;
                    $input_klasifikasi->klasifikasi             = $_klasifikasiKerusakan[$indexInput];
                    $input_klasifikasi->save();
                }
            }else{
                $newKerusakanDetail->jumlah             = $request->get('val_jumlah_unit_'.$index)[0];
                $newKerusakanDetail->save();
                $inputUnits = $request->get('val_unit_'.$index);
                foreach ($inputUnits as $indexInput => $input) {
                    $input_klasifikasi = new KerusakanKlasifikasi;
                    $input_klasifikasi->id_kerusakan_detail     = $newKerusakanDetail->id;
                    $input_klasifikasi->nilai_input_klasifikasi = ($input) ? $input : 0;
                    $input_klasifikasi->klasifikasi             = $_klasifikasiKerusakan[$indexInput];
                    $input_klasifikasi->save();
                }
            }

        }

        return redirect()
            ->action('KerusakanController@index')
            ->with(['success' => 'Kerusakan berhasil ditambahkan.']);
    }

    public function getDataKomponenOpsi(Request $request) {
        $data = $request->all();
        $id_komponen = $data['id_komponen'];
        $dataOpsi = KomponenOpsi::where('id_komponen', $id_komponen)->get();
        return response()->json(['dataOpsi' => $dataOpsi]);
    }

    public function hitungEstimasiKerusakan(Request $request) {
        // request data via ajax
        $data = $request->all();
        $id_komponen = $data['id_komponen'];
        $id_komponen_opsi = $data['id_komponen_opsi'];

        // query data bobot komponen dan nilai opsi
        $bobot = Komponen::select('komponen.bobot as bobot')->where('id', $id_komponen)->first();
        $nilai = KomponenOpsi::select('komponen_opsi.nilai as nilai')->where('id', $id_komponen_opsi)->first();

        // menghitung nilai estimasi kerusakan pada sebuah komponen
        if ($bobot->bobot == null) {
            $hasil_estimasi = $nilai->nilai / 100;
        } else {
            $hasil_estimasi = ($nilai->nilai / 100) * $bobot->bobot / 100;
        }

        // mengirim nilai estimasi kerusakan ke view
        return response()->json(['hasil_estimasi' => $hasil_estimasi]);
    }

    public function hitungKerusakanPersen(Request $request) {
        // request data via ajax
        $data = $request->all();
        $id_komponen = $data['id_komponen'];
        $sum_hasil = $data['sum_hasil'];

        // query data bobot komponen
        $bobot = Komponen::select('komponen.bobot as bobot')->where('id', $id_komponen)->first();

        // menghitung nilai klasifikasi kerusakan pada sebuah komponen
        if ($bobot->bobot == null) {
            $hasil_persen = $sum_hasil;
        } else {
            $hasil_persen = $sum_hasil * $bobot->bobot / 100;
        }
        
        // mengirim nilai estimasi kerusakan ke view
        return response()->json(['hasil_persen' => $hasil_persen, 'bobot' => $bobot->bobot]);
    }

    public function hitungKerusakanUnit(Request $request) {
        // request data via ajax
        $data = $request->all();
        $id_komponen = $data['id_komponen'];
        $sum_hasil = $data['sum_hasil'];

        // query data bobot komponen
        $bobot = Komponen::select('komponen.bobot as bobot')->where('id', $id_komponen)->first();

        // menghitung nilai klasifikasi kerusakan pada sebuah komponen
        if ($bobot->bobot == null) {
            $hasil_unit = $sum_hasil / 100;
        } else {
            $hasil_unit = $sum_hasil * $bobot->bobot / 100;
        }
        
        // mengirim nilai estimasi kerusakan ke view
        return response()->json(['hasil_unit' => $hasil_unit, 'bobot' => $bobot->bobot]);
    }

    public function hapusKerusakan($id) {
        $data = Kerusakan::where('id', $id)->first();
        $data->delete();
        return redirect('master_kerusakan');
    }

    public function viewKerusakan($id) {
        $kerusakan = Kerusakan::select('kerusakan.id as id_kerusakan',
                                       'kerusakan.opd as opd', 
                                       'kerusakan.nomor_aset as nomor_aset',
                                       'kerusakan.petugas_survei1 as petugas_survei1',
                                       'kerusakan.petugas_survei2 as petugas_survei2',
                                       'kerusakan.petugas_survei3 as petugas_survei3',
                                       'kerusakan.perwakilan_opd1 as perwakilan_opd1',
                                       'kerusakan.perwakilan_opd2 as perwakilan_opd2',
                                       'kerusakan.tanggal as tanggal',
                                       'gedung.id as id_gedung',
                                       'gedung.nama as nama_gedung',
                                       'gedung.luas as luas', 
                                       'gedung.jumlah_lantai as jml_lantai')
                                ->join('gedung', 'kerusakan.id_gedung', '=', 'gedung.id')
                                ->where('kerusakan.id', $id)
                                ->first();
        
        $gedung = Gedung::select('gedung.id as id_gedung')->join('kerusakan', 'gedung.id', '=', 'kerusakan.id_gedung')->where('kerusakan.id', $id)->first();
        $daerah = Gedung::select('gedung.kode_provinsi', 'gedung.kode_kabupaten', 'gedung.kode_kecamatan', 'gedung.kode_kelurahan')->where('id', $gedung->id_gedung)->first();
        $provinsi = Provinsi::select('provinsi.nama as nama_provinsi')->where('id_prov', $daerah->kode_provinsi)->first();
        $kab_kota = KabupatenKota::select('kota.nama as nama_kota')->where('id_kota', $daerah->kode_kabupaten)->first();
        $kecamatan = Kecamatan::select('kecamatan.nama as nama_kecamatan')->where('id_kec', $daerah->kode_kecamatan)->first();
        $desa_kelurahan = DesaKelurahan::select('kelurahan.nama as nama_kelurahan')->where('id_kel', $daerah->kode_kelurahan)->first();
        
        $komponen = DB::table('komponen as t1')
            ->select('t2.id as id_komponen',
                     't1.nama as nama_komponen', 
                     't2.nama as sub_komponen', 
                     'satuan.id as id_satuan', 
                     'satuan.nama as nama_satuan',
                     'kerusakan_detail.tingkat_kerusakan'
                     )
            ->rightjoin('komponen as t2', 't1.id', '=', 't2.id_parent')
            ->join('satuan', 't2.id_satuan', '=', 'satuan.id')
            ->join('kerusakan_detail', 't2.id', '=', 'kerusakan_detail.id_komponen')
            ->orderBy('t1.id', 'asc')->get();

        return view('Kerusakan/view_kerusakan', compact('kerusakan', 'provinsi', 'kab_kota', 'kecamatan', 'desa_kelurahan', 'komponen'));
    }

    public function postSubmitKerusakan(Request $request) {
        $data = $request->all();
        //Log::info($data);

        $id_gedung = $data['id_gedung'];
        $id_komp = $data['id_komp'];
        $tanggal_jam = $data['tanggal_jam'];
        $id_komponen_opsi = $data['id_komp_opsi'];
        $jumlah = $data['jumlah'];
        $tingkat_kerusakan = $data['tingkat_kerusakan'];
        $input_nilai_klsf = $data['input_nilai_klsf'];
        $klasifikasi = $data['klasifikasi'];
        Log::info($data);

        // Create data ke table kerusakan
        $input_tbl_kerusakan = new Kerusakan;
        $input_tbl_kerusakan->id_gedung = $id_gedung;
        $input_tbl_kerusakan->tanggal = $tanggal_jam;
        $input_tbl_kerusakan->opd = Session::get('opd');
        $input_tbl_kerusakan->nomor_aset = Session::get('nomor_aset');
        $input_tbl_kerusakan->petugas_survei1 = Session::get('surveyor1');
        $input_tbl_kerusakan->petugas_survei2 = Session::get('surveyor2'); 
        $input_tbl_kerusakan->petugas_survei3 = Session::get('surveyor3');
        $input_tbl_kerusakan->perwakilan_opd1 = Session::get('pwopd1');
        $input_tbl_kerusakan->perwakilan_opd2 = Session::get('pwopd2');
        $input_tbl_kerusakan->save();

        // Create data ke table kerusakan surveyor
        $input_tbl_surveyor = new KerusakanSurveyor;
        $input_tbl_surveyor->id_kerusakan =  $input_tbl_kerusakan->id;
        $input_tbl_surveyor->id_user = $request->id_user;
        $input_tbl_surveyor->save();

        // Create data ke table kerusakan_detail
        for ($i = 0; $i < count($id_komp); $i++) {
            $input_detail = new KerusakanDetail;
            $input_detail->id_kerusakan = $input_tbl_kerusakan->id;
            $input_detail->id_komponen = $id_komp[$i];
            $input_detail->id_komponen_opsi = $id_komponen_opsi[$i];
            $input_detail->jumlah = $jumlah[$i];
            $input_detail->tingkat_kerusakan = $tingkat_kerusakan[$i];
            $input_detail->save();

            for ($j = 0; $j < count($klasifikasi); $j++) {
                for ($k = 0; $k < count($klasifikasi[$j]); $k++) {
                    $input_klasifikasi = new KerusakanKlasifikasi;
                    $input_klasifikasi->id_kerusakan_detail = $input_detail->id;
                    $input_klasifikasi->nilai_input_klasifikasi = $input_nilai_klsf[$j][$k];
                    $input_klasifikasi->klasifikasi = $klasifikasi[$j][$k];
                    $input_klasifikasi->save();
                }
            }

        }
        
        return response()->json(['message', 'Input sukses']);
    }

    public function editFormKerusakan($id) {
        $kerusakan = Kerusakan::select('kerusakan.id as id_kerusakan',
                                       'kerusakan.opd as opd', 
                                       'kerusakan.nomor_aset as nomor_aset',
                                       'kerusakan.petugas_survei1 as petugas_survei1',
                                       'kerusakan.petugas_survei2 as petugas_survei2',
                                       'kerusakan.petugas_survei3 as petugas_survei3',
                                       'kerusakan.perwakilan_opd1 as perwakilan_opd1',
                                       'kerusakan.perwakilan_opd2 as perwakilan_opd2',
                                       'kerusakan.tanggal as tanggal',
                                       'gedung.id as id_gedung',
                                       'gedung.nama as nama_gedung',
                                       'gedung.alamat as alamat',
                                       'gedung.luas as luas', 
                                       'gedung.jumlah_lantai as jml_lantai')
                                ->join('gedung', 'kerusakan.id_gedung', '=', 'gedung.id')
                                ->where('kerusakan.id', $id)
                                ->first();
        
        return view('kerusakan/edit_formulir_penilaian_kerusakan', compact('kerusakan'));
    }

    public function postEditFormSurveyor(Request $request) {
        $id_kerusakan = $request->id_kerusakan;
        $tanggal = $request->tanggal;
        $jam = $request->jam;
        $tanggal_jam = $tanggal." ".$jam;
        $id_user = $request->id_user;
        Session::put('opd', $request->opd);
        Session::put('nomor_aset', $request->nomor_aset);
        Session::put('surveyor1', $request->surveyor1);
        Session::put('surveyor2', $request->surveyor2);
        Session::put('surveyor3', $request->surveyor3);
        Session::put('pwopd1', $request->pwopd1);
        Session::put('pwopd2', $request->pwopd2);


        return redirect()->action('KerusakanController@formEditIdentifikasiKerusakan', ['id_kerusakan' => $id_kerusakan]);
    }

    public function formEditIdentifikasiKerusakan($id_kerusakan) {
        $kerusakan = Kerusakan::select('kerusakan.id as id_kerusakan',
                                       'kerusakan.opd as opd', 
                                       'kerusakan.nomor_aset as nomor_aset',
                                       'kerusakan.petugas_survei1 as petugas_survei1',
                                       'kerusakan.petugas_survei2 as petugas_survei2',
                                       'kerusakan.petugas_survei3 as petugas_survei3',
                                       'kerusakan.perwakilan_opd1 as perwakilan_opd1',
                                       'kerusakan.perwakilan_opd2 as perwakilan_opd2',
                                       'kerusakan.tanggal as tanggal',
                                       'gedung.id as id_gedung',
                                       'gedung.nama as nama_gedung',
                                       'gedung.luas as luas', 
                                       'gedung.jumlah_lantai as jml_lantai')
                                ->join('gedung', 'kerusakan.id_gedung', '=', 'gedung.id')
                                ->where('kerusakan.id', $id_kerusakan)
                                ->first();
        
        $gedung = Gedung::select('gedung.id as id_gedung')->join('kerusakan', 'gedung.id', '=', 'kerusakan.id_gedung')->where('kerusakan.id', $id_kerusakan)->first();
        $daerah = Gedung::select('gedung.kode_provinsi', 'gedung.kode_kabupaten', 'gedung.kode_kecamatan', 'gedung.kode_kelurahan')->where('id', $gedung->id_gedung)->first();
        $provinsi = Provinsi::select('provinsi.nama as nama_provinsi')->where('id_prov', $daerah->kode_provinsi)->first();
        $kab_kota = KabupatenKota::select('kota.nama as nama_kota')->where('id_kota', $daerah->kode_kabupaten)->first();
        $kecamatan = Kecamatan::select('kecamatan.nama as nama_kecamatan')->where('id_kec', $daerah->kode_kecamatan)->first();
        $desa_kelurahan = DesaKelurahan::select('kelurahan.nama as nama_kelurahan')->where('id_kel', $daerah->kode_kelurahan)->first();
        
        $komponen = DB::table('komponen as t1')
            ->select('t2.id as id_komponen',
                     't1.nama as nama_komponen', 
                     't2.nama as sub_komponen', 
                     'satuan.id as id_satuan', 
                     'satuan.nama as nama_satuan',
                     'kerusakan_detail.tingkat_kerusakan'
                     )
            ->rightjoin('komponen as t2', 't1.id', '=', 't2.id_parent')
            ->join('satuan', 't2.id_satuan', '=', 'satuan.id')
            ->join('kerusakan_detail', 't2.id', '=', 'kerusakan_detail.id_komponen')
            ->orderBy('t1.id', 'asc')->get();
        return view('Kerusakan/edit_view_master_kerusakan', compact('kerusakan', 'provinsi', 'kab_kota', 'kecamatan', 'desa_kelurahan', 'komponen'));
    }

    public function postSubmitEditKerusakan(Request $request) {
        $data = $request->all();
        //Log::info($data);

        $id_gedung = $data['id_gedung'];
        $id_komp = $data['id_komp'];
        $tanggal_jam = $data['tanggal_jam'];
        $id_komponen_opsi = $data['id_komp_opsi'];
        $jumlah = $data['jumlah'];
        $tingkat_kerusakan = $data['tingkat_kerusakan'];
        $input_nilai_klsf = $data['input_nilai_klsf'];
        $klasifikasi = $data['klasifikasi'];
        Log::info($data);

        // Create data ke table kerusakan
        $input_tbl_kerusakan = new Kerusakan;
        $input_tbl_kerusakan->id_gedung = $id_gedung;
        $input_tbl_kerusakan->tanggal = $tanggal_jam;
        $input_tbl_kerusakan->opd = Session::get('opd');
        $input_tbl_kerusakan->nomor_aset = Session::get('nomor_aset');
        $input_tbl_kerusakan->petugas_survei1 = Session::get('surveyor1');
        $input_tbl_kerusakan->petugas_survei2 = Session::get('surveyor2'); 
        $input_tbl_kerusakan->petugas_survei3 = Session::get('surveyor3');
        $input_tbl_kerusakan->perwakilan_opd1 = Session::get('pwopd1');
        $input_tbl_kerusakan->perwakilan_opd2 = Session::get('pwopd2');
        $input_tbl_kerusakan->save();

        // Create data ke table kerusakan surveyor
        $input_tbl_surveyor = new KerusakanSurveyor;
        $input_tbl_surveyor->id_kerusakan =  $input_tbl_kerusakan->id;
        $input_tbl_surveyor->id_user = $request->id_user;
        $input_tbl_surveyor->save();

        // Create data ke table kerusakan_detail
        for ($i = 0; $i < count($id_komp); $i++) {
            $input_detail = new KerusakanDetail;
            $input_detail->id_kerusakan = $input_tbl_kerusakan->id;
            $input_detail->id_komponen = $id_komp[$i];
            $input_detail->id_komponen_opsi = $id_komponen_opsi[$i];
            $input_detail->jumlah = $jumlah[$i];
            $input_detail->tingkat_kerusakan = $tingkat_kerusakan[$i];
            $input_detail->save();

            for ($j = 0; $j < count($klasifikasi); $j++) {
                for ($k = 0; $k < count($klasifikasi[$j]); $k++) {
                    $input_klasifikasi = new KerusakanKlasifikasi;
                    $input_klasifikasi->id_kerusakan_detail = $input_detail->id;
                    $input_klasifikasi->nilai_input_klasifikasi = $input_nilai_klsf[$j][$k];
                    $input_klasifikasi->klasifikasi = $klasifikasi[$j][$k];
                    $input_klasifikasi->save();
                }
            }

        }
        
        return response()->json(['message', 'Input sukses']);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use App\Exports\EmployeeExport;
use App\Imports\EmployeeImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $data = Employee::all();

        if ($request->ajax()) {

            // Filter data berdasarkan kolom
            if ($request->has('search') && !empty($request->input('search')['value'])) {
                $searchValue = $request->input('search')['value'];
                $data->where(function ($query) use ($searchValue) {
                    $query->where('nama', 'like', '%' . $searchValue . '%')
                        ->orWhere('nim', 'like', '%' . $searchValue . '%')
                        ->orWhere('jurusan', 'like', '%' . $searchValue . '%');
                });
            }
            if($request->has('search')){
                $data = Employee::where('nama','LIKE','%' .$request->search.'%')->paginate(10);
                Session::put('halaman_url', request()->fullUrl());
            } else{
                $data = Employee::paginate(10);
                Session::put('halaman_url', request()->fullUrl());
            }

            // Menghitung total data tanpa filtering
            $totalData = $data->count();

            // Menghitung total data setelah filtering
            $filteredData = $data->get();

            // Pagination
            $start = $request->input('start');
            $length = $request->input('length');
            $data = $data->skip($start)->take($length)->get();

            // Bentuk response JSON sesuai format DataTables
            $response = [
                "draw" => intval($request->input('draw')),
                "recordsTotal" => $totalData,
                "recordsFiltered" => $filteredData->count(),
                "data" => $data,
            ];

            return response()->json($response);
        }

        return view('datamahasiswa', ['data' => $data]);
    }

    public function tambahmahasiswa(){
        return view('tambahdata');
    }

    public function insertdata(Request $request){
        //dd($request->all());
        $this->validate($request,[
            'nama' =>'required|min:7|max:20',
            'nim' => 'required|min:8|max:9',
        ]);

        $data = Employee::create($request->all());
        if($request->hasfile('foto')){
            $request->file('foto')->move('fotomahasiswa/', $request->file('foto')->getClientOriginalName());
            $data->foto = $request->file('foto')->getClientOriginalName();
            $data->save();
        }
        return redirect()->route('mahasiswa')->with('success', 'Data Berhasil Di Tambahkan');
    }
    public function tampilkandata($id){

        $data = Employee::find($id);
        //dd($data);

        return view('tampildata', compact('data'));
    }

    public function updatedata(Request $request, $id){
        $data = Employee::find($id);
        $data->update($request->all());
        if(session('halaman_url')){
            return Redirect(session('halaman_url'))->with('success','Data Berhasil Di Update');
        }
        return redirect()->route('mahasiswa')->with('success',' Data Berhasil Di Update');
    }

    public function delete($id){
        $data = Employee::find($id);
        $data->delete();
        return redirect()->route('mahasiswa')->with('success',' Data Berhasil Di Hapus');
    }

    public function exportpdf(){
        $data = Employee::all();

        view()->share('data', $data);
        $pdf = PDF::loadview('datamahasiswa-pdf');
        return $pdf->download('data.pdf');
    }

    public function exportexcel(){
        return Excel::download(new EmployeeExport, 'datamahasiswa.xlsx');
    }

    public function importexcel(Request $request){
        $data = $request->file('file');

        $namafile = $data->getClientOriginalName();
        $data->move('EmployeeData', $namafile);

        Excel::import(new EmployeeImport, \public_path('EmployeeData/'.$namafile));
        return \redirect()->back();
    }

}

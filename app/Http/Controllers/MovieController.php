<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreMovieRequest;

class MovieController extends Controller
{

    public function index()
    {

        $query = Movie::latest();
        if (request('search')) {
            $query->where('judul', 'like', '%' . request('search') . '%')
                ->orWhere('sinopsis', 'like', '%' . request('search') . '%');
        }
        $movies = $query->paginate(6)->withQueryString();
        return view('homepage', compact('movies'));
    }

    public function detail($id)
    {
        $movie = Movie::find($id);
        return view('detail', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('input', compact('categories'));
    }

    public function store(StoreMovieRequest $request)
    {
          // Ambil data yang sudah tervalidasi
    $validated = $request->validated();

    // Simpan file foto jika ada
    if ($request->hasFile('foto_sampul')) {
        $validated['foto_sampul'] = $request->file('foto_sampul')->store('movie_covers', 'public');
    }

    // Simpan data ke database
    Movie::create($validated);

        return redirect('/')->with('success', 'Data berhasil disimpan');
    }

    public function data()
    {
        $movies = Movie::latest()->paginate(10);
        return view('data-movies', compact('movies'));
    }

    public function form_edit($id)
    {
        $movie = Movie::find($id);
        $categories = Category::all();
        return view('form-edit', compact('movie', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $this->validateRequest($request);
    
        $movie = Movie::findOrFail($id);
    
        $data = $request->only(['judul', 'sinopsis', 'category_id', 'tahun', 'pemain']);
    
        if ($request->hasFile('foto_sampul')) {
            $fileName = $this->handleCoverImage($request, $movie->foto_sampul);
            $data['foto_sampul'] = $fileName;
        }
    
        $movie->update($data);
    
        return redirect('/movies/data')->with('success', 'Data berhasil diperbarui');
    }
    
    private function validateRequest(Request $request)
    {
        Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'sinopsis' => 'required|string',
            'tahun' => 'required|integer',
            'pemain' => 'required|string',
            'foto_sampul' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ])->validate();
    }
    
    private function handleCoverImage(Request $request, $oldFileName = null)
    {
        $randomName = Str::uuid()->toString();
        $fileExtension = $request->file('foto_sampul')->getClientOriginalExtension();
        $fileName = $randomName . '.' . $fileExtension;
    
        $request->file('foto_sampul')->move(public_path('images'), $fileName);
    
        if ($oldFileName && File::exists(public_path('images/' . $oldFileName))) {
            File::delete(public_path('images/' . $oldFileName));
        }
    
        return $fileName;
    }
    

    public function delete($id)
    {
        $movie = Movie::findOrFail($id);

        // Delete the movie's photo if it exists
        if (File::exists(public_path('images/' . $movie->foto_sampul))) {
            File::delete(public_path('images/' . $movie->foto_sampul));
        }

        // Delete the movie record from the database
        $movie->delete();

        return redirect('/movies/data')->with('success', 'Data berhasil dihapus');
    }
}
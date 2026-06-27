<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        return view('section.index');
    }

    public function data()
    {
        $section = Section::orderBy('id_section', 'desc')->get();

        return datatables()
            ->of($section)
            ->addIndexColumn()
            ->addColumn('aksi', function ($section) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="editForm(`'. route('section.update', $section->id_section) .'`)" class="btn btn-xs btn-primary btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="deleteData(`'. route('section.destroy', $section->id_section) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_section' => 'required|string|max:255|unique:section,nama_section',
        ]);

        Section::create($request->only('nama_section'));

        return response()->json('Data saved successfully', 200);
    }

    public function show($id)
    {
        $section = Section::find($id);

        return response()->json($section);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_section' => 'required|string|max:255|unique:section,nama_section,' . $id . ',id_section',
        ]);

        $section = Section::find($id);
        $section->update($request->only('nama_section'));

        return response()->json('Data saved successfully', 200);
    }

    public function destroy($id)
    {
        $section = Section::find($id);
        $section->delete();

        return response(null, 204);
    }
}

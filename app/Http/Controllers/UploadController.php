<?php

namespace App\Http\Controllers;

use App\Models\TempFile;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * see @link https://github.com/rahulhaque/laravel-filepond/blob/master/src/Services/FilepondService.php
 */


class UploadController extends Controller
{
    public function store(Request $request)
    {
        if ($request->header("-method") == "DELETE") {
            $file = TempFile::where('folder', $request->folder)->first();
            if ($file) {
                Storage::deleteDirectory($file->folder);
                $file->delete();
            }
        } else {
            $mimeTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'];

            if ($request->hasFile('image') && in_array(File::mimeType($request->file('image')), $mimeTypes)) {
                $file = $request->file('image');
                $filename = Str::slug(str_replace("'", '', $file->getClientOriginalName()));
                $folder = 'tmp/' . uniqid() . '-' . now()->timestamp;
                Storage::putFileAs($folder, $file, $filename);

                TempFile::create([
                    'folder' => $folder,
                    'filename' => $filename,
                ]);

                return $folder;
            }
        }

        return response('Failed upload', 500);
    }

    /**
     * Filepond will send a DELETE request to this method
     *
     * @param Request $request
     * @return void
     */
    public function revert(Request $request)
    {
        if (empty($content = $request->getContent())) {
            return response('Reverted file', 200);
        }

        // TempFileモデルよりカラム`folder'で検索した最初のレコードを取得
        $file = TempFile::where('folder', $content)->first();
        if ($file) {
            Storage::deleteDirectory($file->folder);
            $file->delete();
        }

        return response('Reverted file', 200);
    }
}

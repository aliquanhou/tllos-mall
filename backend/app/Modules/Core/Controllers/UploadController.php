<?php
namespace App\Modules\Core\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends BaseController
{
    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file|max:102400']);
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $type = $file->getMimeType();
        $folder = strpos($type, 'video') !== false ? 'videos' : 'images';
        $filename = date('Ymd') . '/' . Str::random(20) . '.' . $ext;
        $file->storeAs('public/uploads/' . $folder, $filename);
        $url = '/storage/uploads/' . $folder . '/' . $filename;
        return $this->success(['url' => $url, 'full_url' => config('app.url') . $url, 'name' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'type' => $type]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['file' => 'required|image|max:10240']);
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $filename = date('Ymd') . '/' . Str::random(20) . '.' . $ext;
        $file->storeAs('public/uploads/images', $filename);
        $url = '/storage/uploads/images/' . $filename;
        return $this->success(['url' => $url, 'full_url' => config('app.url') . $url]);
    }

    public function uploadVideo(Request $request)
    {
        $request->validate(['file' => 'required|mimes:mp4,avi,mov,wmv|max:102400']);
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $filename = date('Ymd') . '/' . Str::random(20) . '.' . $ext;
        $file->storeAs('public/uploads/videos', $filename);
        $url = '/storage/uploads/videos/' . $filename;
        return $this->success(['url' => $url, 'full_url' => config('app.url') . $url]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CaptureToken;
use App\Models\File;
use App\Models\FileDownloadLink;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Kris\LaravelFormBuilder\FormBuilder;
use Kris\LaravelFormBuilder\FormBuilderTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{

    use FormBuilderTrait, ForceLoginTrait;

    /**
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return Factory|View
     */
    public function index(Request $request, FormBuilder $formBuilder)
    {
        $files = File::findByCurrentUser($request, $formBuilder);

        return view('files')->with([
            'files'  => collect($files),
            'upload' => true,
        ]);
    }

    /**
     * @param  int  $id
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return bool|Factory|JsonResponse|RedirectResponse|View
     */
    public function file($id, Request $request, FormBuilder $formBuilder)
    {
        if (!$id) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, $formBuilder, (int) $id, 1)->first();

        if (!$file) {
            return $this->forceLogin($request);
        }

        if ($request->ajax()) {
            return response()->json([
                'html'       => view('partials.file.item')->with(['file' => $file, 'upload' => false])->toHtml(),
                'updated_at' => $file->updated_at->format(File::DATE_FORMAT),
                'success'    => true,
            ]);
        } else {
            return view('files')->with([
                'files'  => [$file],
                'upload' => false,
            ]);
        }
    }

    /**
     * @param $id
     * @param  Request  $request
     *
     * @return bool|RedirectResponse|BinaryFileResponse
     * @throws Exception
     */
    public function download($id, Request $request)
    {
        if (!$id) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, null, (int) $id, 1)->first();

        if (!$file) {
            return $this->forceLogin($request);
        }

        return $file->download();
    }

    /**
     * @param $id
     * @param $token
     * @param  Request  $request
     *
     * @return JsonResponse|RedirectResponse|void
     */
    public function downloadWithToken($id, $token, Request $request)
    {
        if (!$id || !$token) {
            return redirect()->back();
        }

        CaptureToken::setIfEmpty($token);

        /** @var FileDownloadLink $downloadLink */
        $downloadLink = FileDownloadLink::query()
            ->where('file_id', (int) $id)
            ->where('token', (string) $token)
            ->first();

        if (!$downloadLink) {
            return $this->forceLogin($request);
        }

        return $downloadLink->file->download();
    }

    /**
     * @param $id
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return bool|RedirectResponse|Redirector
     * @throws Exception
     */
    public function store($id, Request $request, FormBuilder $formBuilder)
    {
        if (!$id) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, $formBuilder, (int) $id)->first();

        if (!$file) {
            return $this->forceLogin($request);
        }

        if ($file->status ^ File::STATUS_INPUT_NEEDED) {
            return redirect()->back();
        }

        if (!$file->form->isValid()) {
            return redirect()->back()->withErrors($file->form->getErrors())->withInput();
        }

        $file->saveInputSettings($file->form->getFieldValues());

        return redirect('files/'.$file->id);
    }

    /**
     * Upload one or more files and associate to the db while copying to a persistent location.
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function upload(Request $request)
    {
        $errors = $uploaded = $routes = [];

        /** @var UploadedFile $uploadedFile */
        foreach ($request->allFiles() as $uploadedFiles) {
            if (!is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }
            foreach ($uploadedFiles as $uploadedFile) {
                $name = $uploadedFile->getClientOriginalName();
                try {
                    $file            = File::createAndMove($uploadedFile, File::MODE_HASH, $request);
                    $uploaded[$name] = $uploadedFile->getClientOriginalName();
                    $routes[$name]   = route('file', ['id' => $file->id]);
                } catch (Exception $e) {
                    $errors[$name] = $e->getMessage();
                }
            }
        }

        return response()->json([
            'success' => $uploaded,
            'errors'  => $errors,
            'routes'  => $routes,
        ], count($uploaded) ? 200 : 409);
    }
}

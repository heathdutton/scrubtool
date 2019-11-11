<?php

namespace App\Http\Controllers;

use App\File;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Kris\LaravelFormBuilder\FormBuilder;
use Kris\LaravelFormBuilder\FormBuilderTrait;

class FileController extends Controller
{

    use FormBuilderTrait;

    /**
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
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
     * Later can be a page for the results of a single file upload.
     *
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function file(Request $request, FormBuilder $formBuilder)
    {
        if (empty($request->id) || (int) $request->id < 1) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, $formBuilder, (int) $request->id, 1)->first();

        if (!$file) {
            return $this->forceLogin($request) ?? response()->isNotFound();
        }

        if ($request->ajax()) {
            return response()->json([
                'html'       => view('partials.file')->with(['file' => $file,])->toHtml(),
                'updated_at' => $file->updated_at->getTimestamp(),
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
     * @param  Request  $request
     *
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    private function forceLogin(Request $request)
    {
        $redirectPath = null;
        if (
            !$request->user()
            && $request->getRequestUri() !== $request->session()->get('url.intended')
        ) {
            // User likely needs to log in.
            $request->session()->put('url.intended', $request->getRequestUri());
            $redirectPath = 'login';
        } else {
            // File no longer exists.
            $redirectPath = 'files';
        }
        if ($redirectPath) {
            if ($request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'redirect' => route($redirectPath),
                ]);
            } else {
                return response()->redirectTo($redirectPath);
            }
        }
    }

    /**
     * @param  Request  $request
     *
     * @return bool|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws Exception
     */
    public function download(Request $request)
    {
        if (empty($request->id) || (int) $request->id < 1) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, null, (int) $request->id, 1)->first();

        if (!$file) {
            return $this->forceLogin($request) ?? response()->isNotFound();
        }

        if (Carbon::now() > new Carbon($file->available_til ?? '', 'UTC')) {
            // The file should have been deleted by now, likely the job failed.
            $file->delete();

            return response()->isNotFound();
        }

        return $file->download();
    }

    /**
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, FormBuilder $formBuilder)
    {
        if (empty($request->id) || (int) $request->id < 1) {
            return redirect()->back();
        }

        /** @var File $file */
        $file = File::findByCurrentUser($request, $formBuilder, (int) $request->id)->first();

        if ($file->status ^ File::STATUS_INPUT_NEEDED) {
            return redirect()->back();
        }

        if (!$file) {
            return $this->forceLogin($request) ?? response()->isNotFound();
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
     * @return \Illuminate\Http\JsonResponse
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
        ]);
    }
}

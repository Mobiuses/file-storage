<?php
declare(strict_types=1);

namespace App\Modules\File\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\File\DTO\FileUploadDTO;
use App\Modules\File\Http\Requests\FileUploadRequest;
use App\Modules\File\Managers\FileStorageManager;
use App\Modules\File\Repositories\FileRepositoryInterface;
use App\Modules\File\Services\FileServiceInterface;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    public function __construct(
        protected FileServiceInterface $fileService,
        protected FileRepositoryInterface $repository,
        protected FileStorageManager $storageManager
    ) {}

    public function index(): JsonResponse
    {
        try {
            $files = $this->fileService->getAllFiles();
            return response()->json($files);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(FileUploadRequest $request): JsonResponse
    {
        try {
            $uploadedFile = $request->file('file');
            $dto = FileUploadDTO::fromUploadedFile($uploadedFile);
            $fileDTO = $this->fileService->uploadFile($dto);

            return response()->json($fileDTO->toArray(), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $file = $this->fileService->getFileById($id);

            if (!$file) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return response()->json($file->toArray());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function download(string $id)
    {
        try {
            $file = $this->repository->find($id);

            if (!$file) {
                return response()->json(['error' => 'File not found'], 404);
            }

            if (!$this->storageManager->exists($file->path)) {
                return response()->json(['error' => 'File not found in storage'], 404);
            }

            return $this->storageManager->download($file->path, $file->original_name);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $success = $this->fileService->deleteFile($id);

            if (!$success) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return response()->json(['message' => 'File deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

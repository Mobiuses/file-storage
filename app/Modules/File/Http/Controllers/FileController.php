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
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function __construct(
        protected readonly FileServiceInterface $fileService,
        protected readonly FileRepositoryInterface $repository,
        protected readonly FileStorageManager $storageManager
    ) {}

    public function index(): JsonResponse
    {
        try {
            $files = $this->fileService->getAllFiles();
            return response()->json($files);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(FileUploadRequest $request): JsonResponse
    {
        try {
            $uploadedFile = $request->file('file');
            $dto = FileUploadDTO::fromUploadedFile($uploadedFile);
            $fileDTO = $this->fileService->uploadFile($dto);

            return response()->json($fileDTO->toArray(), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $file = $this->fileService->getFileById($id);

            if (!$file) {
                return response()->json(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            return response()->json($file->toArray());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function download(string $id)
    {
        try {
            $file = $this->repository->find($id);

            if (!$file) {
                return response()->json(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            if (!$this->storageManager->exists($file->path)) {
                return response()->json(['error' => 'File not found in storage'], Response::HTTP_NOT_FOUND);
            }

            return $this->storageManager->download($file->path, $file->original_name);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $success = $this->fileService->deleteFile($id);

            if (!$success) {
                return response()->json(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['message' => 'File deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

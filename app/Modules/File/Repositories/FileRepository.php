<?php
declare(strict_types=1);

namespace App\Modules\File\Repositories;

use App\Modules\File\Models\File;
use Illuminate\Database\Eloquent\Collection;

class FileRepository implements FileRepositoryInterface
{
    public function all(): Collection
    {
        return File::orderBy('created_at', 'desc')->get();
    }

    public function find(string $id): ?File
    {
        return File::find($id);
    }

    public function create(array $data): File
    {
        return File::create($data);
    }

    public function delete(string $id): bool
    {
        $file = $this->find($id);

        if (!$file) {
            return false;
        }

        return $file->delete();
    }

    public function findExpired(): Collection
    {
        return File::where('expires_at', '<=', now())->get();
    }
}

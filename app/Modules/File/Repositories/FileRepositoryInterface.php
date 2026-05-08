<?php
declare(strict_types=1);

namespace App\Modules\File\Repositories;

use App\Modules\File\Models\File;
use Illuminate\Database\Eloquent\Collection;

interface FileRepositoryInterface
{
    public function all(): Collection;

    public function find(string $id): ?File;

    public function create(array $data): File;

    public function delete(string $id): bool;

    public function findExpired(): Collection;
}

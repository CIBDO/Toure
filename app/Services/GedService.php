<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GedService
{
    public static function resolveDocumentable(string $typeKey, int $id): ?Model
    {
        $types = config('ged.documentable_types', []);
        if (!isset($types[$typeKey])) {
            return null;
        }
        $class = $types[$typeKey];
        return $class::find($id);
    }

    public static function documentableTypeToClass(string $typeKey): ?string
    {
        $types = config('ged.documentable_types', []);
        return $types[$typeKey] ?? null;
    }

    public static function storeFile(Document $document, $file): string
    {
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $name = $document->uuid . '.' . strtolower($ext);
        $entity = class_basename($document->documentable_type);
        $folder = 'ged/' . strtolower($entity) . '/' . $document->documentable_id;
        return $file->storeAs($folder, $name, 'local');
    }

    public static function deleteFile(string $path): bool
    {
        if (!Storage::disk('local')->exists($path)) {
            return true;
        }
        return Storage::disk('local')->delete($path);
    }
}

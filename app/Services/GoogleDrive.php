<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Минимальный клиент Google Drive API v3 (только папки).
 * Доступ — через сервисный аккаунт: JSON-ключ + корневая папка, к которой этому аккаунту дан доступ.
 * Без лишних пакетов: токен получаем сами (JWT RS256 через openssl).
 */
class GoogleDrive
{
    private const API = 'https://www.googleapis.com/drive/v3/files';
    private const FOLDER = 'application/vnd.google-apps.folder';

    public static function enabled(): bool
    {
        return (bool) config('shop.drive.root_id') && is_file((string) config('shop.drive.key_file'));
    }

    public static function rootId(): string
    {
        return (string) config('shop.drive.root_id');
    }

    /** Email сервисного аккаунта из файла ключа (для подсказок в ошибках) */
    public static function serviceEmail(): ?string
    {
        $key = json_decode((string) @file_get_contents((string) config('shop.drive.key_file')), true);

        return is_array($key) ? ($key['client_email'] ?? null) : null;
    }

    public static function url(string $folderId): string
    {
        return 'https://drive.google.com/drive/folders/' . $folderId;
    }

    /** Токен доступа (живёт ~1 час, кэшируем на 50 минут) */
    private function token(): string
    {
        return Cache::remember('gdrive_token', 3000, function () {
            $key = json_decode((string) file_get_contents(config('shop.drive.key_file')), true);
            if (! is_array($key) || empty($key['client_email']) || empty($key['private_key'])) {
                throw new RuntimeException('Файл ключа Google Диска повреждён или это не ключ сервисного аккаунта.');
            }
            $b64 = fn (string $d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
            $now = time();
            $unsigned = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $b64(json_encode([
                'iss' => $key['client_email'],
                'scope' => 'https://www.googleapis.com/auth/drive',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            if (! openssl_sign($unsigned, $sig, $key['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Не удалось подписать запрос к Google (проверьте расширение openssl в PHP).');
            }
            $res = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . $b64($sig),
            ])->throw();

            return (string) $res->json('access_token');
        });
    }

    private function http()
    {
        return Http::withToken($this->token())->acceptJson()->timeout(20);
    }

    /** Папка с таким именем внутри родителя (чтобы не плодить дубли) */
    public function findFolder(string $name, string $parentId): ?string
    {
        $esc = fn (string $v) => str_replace(['\\', "'"], ['\\\\', "\\'"], $v);
        $res = $this->http()->get(self::API, [
            'q' => "name = '" . $esc($name) . "' and '" . $esc($parentId) . "' in parents and mimeType = '" . self::FOLDER . "' and trashed = false",
            'fields' => 'files(id)',
            'pageSize' => 1,
        ])->throw();

        return $res->json('files.0.id');
    }

    public function createFolder(string $name, string $parentId): string
    {
        $res = $this->http()->post(self::API . '?fields=id', [
            'name' => $name,
            'mimeType' => self::FOLDER,
            'parents' => [$parentId],
        ])->throw();

        return (string) $res->json('id');
    }

    /**
     * Гарантирует папку: создаёт, если нет; переименовывает и переносит, если имя/родитель изменились.
     * Возвращает [id, создана ли только что].
     */
    public function ensureFolder(?string $id, string $name, string $parentId): array
    {
        $name = trim(mb_substr(preg_replace('/\s+/u', ' ', $name), 0, 120)) ?: 'Без названия';

        if ($id) {
            $res = $this->http()->get(self::API . '/' . $id, ['fields' => 'name,parents,trashed']);
            if ($res->status() !== 404 && ! $res->json('trashed')) {
                $res->throw();
                $parents = (array) $res->json('parents', []);
                $params = [];
                if (! in_array($parentId, $parents, true)) {
                    $params['addParents'] = $parentId;
                    $params['removeParents'] = implode(',', $parents);
                }
                if ($res->json('name') !== $name || $params) {
                    $this->http()->withQueryParameters($params + ['fields' => 'id'])
                        ->patch(self::API . '/' . $id, ['name' => $name])->throw();
                }

                return [$id, false];
            }
            // Папку удалили на Диске — создадим заново
        }

        $found = $this->findFolder($name, $parentId);
        if ($found) {
            return [$found, false];
        }

        return [$this->createFolder($name, $parentId), true];
    }
}

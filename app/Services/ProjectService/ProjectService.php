<?php
declare(strict_types=1);

namespace App\Services\ProjectService;

use App\Models\Shop;
use App\Services\CoreService;
use Illuminate\Support\Facades\Http;

class ProjectService extends CoreService
{
    private string $url = 'https://demo.githubit.com/api/v2/server/notification';

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Shop::class;
    }

    public function activationKeyCheck(string|null $code = null, string|null $id = null): bool|string
    {
        if (!$this->checkLocal()) {

            $params = [
                'code'  => !empty($code) ? $code : config('credential.purchase_code'),
                'id'    => !empty($id) ? $id : config('credential.purchase_id'),
                'ip'    => request()->server('SERVER_ADDR'),
                'host'  => request()->getSchemeAndHttpHost()
            ];

            $response = Http::post($this->url, $params);

            return $response->body();
        }

        return json_encode([
            'local'     => true,
            'active'    => true,
            'key'       => config('credential.purchase_code'),
        ]);
    }

    public function checkLocal(): bool
    {
        return true;
    }
}

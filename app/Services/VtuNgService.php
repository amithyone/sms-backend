<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class VtuNgService
{
	private string $baseUrl;
	private string $jwtUrl;
	private ?string $username;
	private ?string $password;
	private ?string $pin;
	private SimpleHttpClient $http;

	public function __construct()
	{
		// VTU v2 base (public endpoints under /wp-json/api/v2)
		$this->baseUrl = rtrim(config('services.vtu.vtu_ng.base_url', 'https://vtu.ng/wp-json/api/v2'), '/') . '/';
		// JWT token endpoint (not under api/v2)
		$this->jwtUrl = rtrim(config('services.vtu.vtu_ng.jwt_url', 'https://vtu.ng/wp-json/jwt-auth/v1/token'), '/');
		$this->username = config('services.vtu.vtu_ng.username');
		$this->password = config('services.vtu.vtu_ng.password');
		$this->pin = config('services.vtu.vtu_ng.pin');
		$this->http = new SimpleHttpClient([
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			],
			'timeout' => 30,
		]);
	}

	public function setProviderConfig($provider): void
	{
		$this->baseUrl = rtrim($provider->api_url, '/') . '/';
		$this->username = $provider->username;
		$this->password = $provider->password;
		$this->pin = $provider->pin;
	}

	private function isConfigured(): bool
	{
		return !empty($this->username) && !empty($this->password);
	}

	private function getJwtToken(): ?string
	{
		$cacheKey = 'vtu_ng_jwt_token';
		$token = Cache::get($cacheKey);
		if ($token) { return $token; }
		try {
			$resp = $this->http->post($this->jwtUrl, [
				'username' => (string) $this->username,
				'password' => (string) $this->password,
			]);
			if (!$resp->successful()) {
				Log::error('VTU.ng JWT token HTTP error', ['status' => $resp->status(), 'body' => $resp->body()]);
				return null;
			}
			$data = $resp->json();
			$token = $data['token'] ?? null;
			if (!$token) {
				Log::error('VTU.ng JWT token missing in response', ['json' => $data]);
				return null;
			}
			$minutes = (int) config('services.vtu.vtu_ng.token_cache_minutes', (int) env('VTU_NG_TOKEN_CACHE_MINUTES', 10080));
			Cache::put($cacheKey, $token, now()->addMinutes($minutes));
			return $token;
		} catch (Exception $e) {
			Log::error('VTU.ng JWT token exception', ['message' => $e->getMessage()]);
			return null;
		}
	}

	private function authClient(): SimpleHttpClient
	{
		$token = $this->getJwtToken();
		if (!$token) {
			throw new Exception('Unable to obtain VTU.ng auth token');
		}
		return $this->http->withHeaders(['Authorization' => 'Bearer ' . $token]);
	}

	private function getApiBase(): string
	{
		$root = rtrim($this->baseUrl, '/');
		if (str_ends_with($root, '/api/v2')) { return $root . '/'; }
		if (str_ends_with($root, 'api/v2')) { return $root . '/'; }
		return $root . '/api/v2/';
	}

	public function getAirtimeNetworks(): array
	{
		// v2 does not expose networks list explicitly; controller provides static list
		return [];
	}

	public function getDataNetworks(): array
	{
		// v2 does not expose networks list explicitly; controller provides static list
		return [];
	}

	public function getDataBundles(string $network): array
	{
		if (!$this->isConfigured()) { return []; }
		try {
			$serviceId = strtolower($network);
			$url = $this->getApiBase() . 'variations/data?service_id=' . urlencode($serviceId);
			$response = $this->http->get($url);
			if ($response->successful()) {
				$data = $response->json();
				// v2 sample: { code: 'success', data: [...] }
				if (($data['code'] ?? '') === 'success') {
					return $data['data'] ?? [];
				}
				Log::warning('VTU.ng getDataBundles v2 non-success', ['json' => $data]);
			}
			return [];
		} catch (Exception $e) {
			Log::error('VTU.ng getDataBundles exception', ['message' => $e->getMessage()]);
			return [];
		}
	}

	public function purchaseAirtime(string $network, string $phone, float $amount, string $reference): array
	{
		if (!$this->isConfigured()) { throw new Exception('VTU.ng not configured'); }
		try {
			$client = $this->authClient();
			$url = $this->getApiBase() . 'airtime';
			$payload = [
				'request_id' => $reference,
				'phone' => $phone,
				'service_id' => strtolower($network),
				'amount' => $amount,
			];
			$response = $client->post($url, $payload);
			$data = $response->json();
			return [
				'success' => ($data['code'] ?? '') === 'success',
				'data' => $data,
				'message' => $data['message'] ?? ($response->successful() ? 'Airtime request sent' : 'Airtime purchase failed')
			];
		} catch (Exception $e) {
			Log::error('VTU.ng airtime exception', ['message' => $e->getMessage()]);
			return [ 'success' => false, 'data' => null, 'message' => $e->getMessage() ];
		}
	}

	public function purchaseDataBundle(string $network, string $phone, string $plan, string $reference): array
	{
		if (!$this->isConfigured()) { throw new Exception('VTU.ng not configured'); }
		try {
			$client = $this->authClient();
			$url = $this->getApiBase() . 'data';
			$payload = [
				'request_id' => $reference,
				'phone' => $phone,
				'service_id' => strtolower($network),
				'variation_id' => $plan,
			];
			$response = $client->post($url, $payload);
			$data = $response->json();
			if (($data['code'] ?? '') !== 'success') {
				Log::error('VTU.ng data v2 returned failure', ['json' => $data]);
			}
			return [
				'success' => ($data['code'] ?? '') === 'success',
				'data' => $data,
				'message' => $data['message'] ?? ($response->successful() ? 'Data request sent' : 'Data purchase failed')
			];
		} catch (Exception $e) {
			Log::error('VTU.ng data exception', ['message' => $e->getMessage()]);
			return [ 'success' => false, 'data' => null, 'message' => $e->getMessage() ];
		}
	}

	public function getTransactionStatus(string $reference): array
	{
		if (!$this->isConfigured()) { throw new Exception('VTU.ng not configured'); }
		try {
			$client = $this->authClient();
			$url = $this->getApiBase() . 'requery';
			$response = $client->post($url, ['request_id' => $reference]);
			return $response->successful() ? $response->json() : ['status' => 'error'];
		} catch (Exception $e) {
			return ['status' => 'error', 'message' => $e->getMessage()];
		}
	}

	public function getBalance(): array
	{
		if (!$this->isConfigured()) { return ['success' => false]; }
		try {
			$client = $this->authClient();
			$url = $this->getApiBase() . 'balance';
			$response = $client->get($url);
			if ($response->successful()) {
				$data = $response->json();
				if (($data['code'] ?? '') === 'success') {
					return [ 'success' => true, 'balance' => $data['data']['balance'] ?? 0, 'currency' => $data['data']['currency'] ?? 'NGN', 'provider' => $data ];
				}
			}
			return ['success' => false];
		} catch (Exception $e) {
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	public function validatePhoneNumber(string $phone, string $network): bool
	{
		return preg_match('/^0\d{10}$/', $phone) === 1;
	}

	private function mapBettingServiceId(string $serviceId): string
	{
		$map = [
			'bet9ja' => 'Bet9ja',
			'betking' => 'BetKing',
			'sportybet' => 'SportyBet',
			'betway' => 'Betway',
			'1xbet' => '1xBet',
			'nairabet' => 'NairaBet',
			'merrybet' => 'MerryBet',
			'msport' => 'MSport',
			'bangbet' => 'BangBet',
			'livescorebet' => 'LiveScore Bet',
			'betpawa' => 'BetPawa',
			'betano' => 'Betano',
		];
		$k = strtolower(trim($serviceId));
		return $map[$k] ?? $serviceId; // fallback to as-is
	}

	private function bettingServiceIdCandidates(string $serviceId): array
	{
		$base = trim($serviceId);
		$lower = strtolower($base);
		$mapExact = $this->mapBettingServiceId($serviceId);
		$candidates = array_values(array_unique(array_filter([
			$mapExact,
			$base,
			ucfirst($lower),
			strtoupper(substr($lower,0,1)) . substr($lower,1),
			$lower,
			str_replace('-', '', $lower),
			str_replace(' ', '', $mapExact),
			str_replace(' ', '', $base),
			str_replace([' ', '-'], '', $lower),
		])));
		return $candidates;
	}

	public function verifyCustomer(string $serviceId, string $customerId, ?string $variationId = null): array
	{
		if (!$this->isConfigured()) { throw new Exception('VTU.ng not configured'); }
		$client = $this->authClient();
		$url = $this->getApiBase() . 'verify-customer';
		$last = null;
		foreach ($this->bettingServiceIdCandidates($serviceId) as $sid) {
			$payload = [ 'service_id' => $sid, 'customer_id' => $customerId ];
			if ($variationId) { $payload['variation_id'] = $variationId; }
			$resp = $client->post($url, $payload);
			$data = $resp->json();
			if (($data['code'] ?? '') === 'success') {
				return [ 'success' => true, 'data' => $data, 'message' => $data['message'] ?? 'Verified' ];
			}
			$last = ['payload' => $payload, 'data' => $data];
		}
		Log::error('VTU.ng verify-customer failure (all candidates tried)', [ 'serviceId' => $serviceId, 'attempts' => $this->bettingServiceIdCandidates($serviceId), 'last' => $last ]);
		return [ 'success' => false, 'data' => $last['data'] ?? null, 'message' => ($last['data']['message'] ?? 'Verification failed') ];
	}

	public function purchaseBetting(string $serviceId, string $customerId, float $amount, string $reference): array
	{
		if (!$this->isConfigured()) { throw new Exception('VTU.ng not configured'); }
		$client = $this->authClient();
		$url = $this->getApiBase() . 'betting';
		$last = null;
		foreach ($this->bettingServiceIdCandidates($serviceId) as $sid) {
			$payload = [ 'request_id' => $reference, 'customer_id' => $customerId, 'service_id' => $sid, 'amount' => $amount ];
			$resp = $client->post($url, $payload);
			$data = $resp->json();
			if (($data['code'] ?? '') === 'success') {
				return [ 'success' => true, 'data' => $data, 'message' => $data['message'] ?? 'Betting funded' ];
			}
			$last = ['payload' => $payload, 'data' => $data];
		}
		Log::error('VTU.ng betting purchase failure (all candidates tried)', [ 'serviceId' => $serviceId, 'attempts' => $this->bettingServiceIdCandidates($serviceId), 'last' => $last ]);
		return [ 'success' => false, 'data' => $last['data'] ?? null, 'message' => ($last['data']['message'] ?? 'Betting funding failed') ];
	}

	public function getElectricityProviders(): array
	{
		// Could fetch from VTU docs; using a stable list for now
		return [
			[ 'id' => 'ikeja-electric', 'name' => 'Ikeja Electric' ],
			[ 'id' => 'eko-electric', 'name' => 'Eko Electric' ],
			[ 'id' => 'abuja-electric', 'name' => 'Abuja Electric' ],
			[ 'id' => 'ibadan-electric', 'name' => 'Ibadan Electric' ],
			[ 'id' => 'kaduna-electric', 'name' => 'Kaduna Electric' ],
			[ 'id' => 'portharcourt-electric', 'name' => 'Port Harcourt Electric' ],
			[ 'id' => 'jos-electric', 'name' => 'Jos Electric' ],
			[ 'id' => 'kano-electric', 'name' => 'Kano Electric' ],
		];
	}

	public function verifyElectricityCustomer(string $serviceId, string $customerId, ?string $variationId = null): array
	{
		$client = $this->authClient();
		$url = $this->getApiBase() . 'verify-customer';
		$payload = [ 'service_id' => $serviceId, 'customer_id' => $customerId ];
		if ($variationId) { $payload['variation_id'] = $variationId; }
		$resp = $client->post($url, $payload);
		$data = $resp->json();
		return [ 'success' => ($data['code'] ?? '') === 'success', 'data' => $data, 'message' => $data['message'] ?? '' ];
	}

	public function purchaseElectricity(string $serviceId, string $customerId, string $variationId, float $amount, string $reference): array
	{
		$client = $this->authClient();
		$url = $this->getApiBase() . 'electricity';
		$payload = [
			'request_id' => $reference,
			'customer_id' => $customerId,
			'service_id' => $serviceId,
			'variation_id' => $variationId,
			'amount' => $amount,
		];
		$resp = $client->post($url, $payload);
		$data = $resp->json();
		return [ 'success' => ($data['code'] ?? '') === 'success', 'data' => $data, 'message' => $data['message'] ?? '' ];
	}
}

<?php

namespace App\Services;

class SimpleHttpResponse
{
	private int $statusCode;
	private string $body;
	private array $headers;

	public function __construct(int $statusCode, string $body, array $headers = [])
	{
		$this->statusCode = $statusCode;
		$this->body = $body;
		$this->headers = $headers;
	}

	public function successful(): bool
	{
		return $this->statusCode >= 200 && $this->statusCode < 300;
	}

	public function status(): int
	{
		return $this->statusCode;
	}

	public function body(): string
	{
		return $this->body;
	}

	public function json(): array
	{
		$decoded = json_decode($this->body, true);
		return is_array($decoded) ? $decoded : [];
	}
}

class SimpleHttpClient
{
	private array $defaultHeaders = [];
	private int $timeoutSeconds = 12;

	public function __construct(array $options = [])
	{
		if (isset($options['headers']) && is_array($options['headers'])) {
			$this->defaultHeaders = $this->normalizeHeaders($options['headers']);
		}
		if (isset($options['timeout']) && is_int($options['timeout'])) {
			$this->timeoutSeconds = $options['timeout'];
		}
	}

	public function withHeaders(array $headers): self
	{
		$clone = clone $this;
		$clone->defaultHeaders = array_merge($clone->defaultHeaders, $this->normalizeHeaders($headers));
		return $clone;
	}

	public function timeout(int $seconds): self
	{
		$this->timeoutSeconds = $seconds;
		return $this;
	}

	public function get(string $url, array $query = []): SimpleHttpResponse
	{
		if (!empty($query)) {
			$queryString = http_build_query($query);
			$url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
		}
		return $this->request('GET', $url, []);
	}

	public function post(string $url, array $data = [], array $headers = []): SimpleHttpResponse
	{
		$client = empty($headers) ? $this : $this->withHeaders($headers);
		return $client->request('POST', $url, $data);
	}

	public function put(string $url, array $data = [], array $headers = []): SimpleHttpResponse
	{
		$client = empty($headers) ? $this : $this->withHeaders($headers);
		return $client->request('PUT', $url, $data);
	}

	public function patch(string $url, array $data = [], array $headers = []): SimpleHttpResponse
	{
		$client = empty($headers) ? $this : $this->withHeaders($headers);
		return $client->request('PATCH', $url, $data);
	}

	public function delete(string $url, array $data = [], array $headers = []): SimpleHttpResponse
	{
		$client = empty($headers) ? $this : $this->withHeaders($headers);
		return $client->request('DELETE', $url, $data);
	}

	private function request(string $method, string $url, array $data): SimpleHttpResponse
	{
		$headersList = $this->buildHeadersList($this->defaultHeaders);
		$body = '';
		$contentType = $this->defaultHeaders['content-type'] ?? 'application/json';

		if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
			if (stripos($contentType, 'application/json') !== false) {
				$body = json_encode($data);
			} else {
				$body = http_build_query($data);
			}
		}

		if (function_exists('curl_init')) {
			return $this->requestWithCurl($method, $url, $headersList, $body);
		}
		return $this->requestWithStreams($method, $url, $headersList, $body);
	}

	private function requestWithCurl(string $method, string $url, array $headersList, string $body): SimpleHttpResponse
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		// Total timeout and connection timeout
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(1, min(5, $this->timeoutSeconds - 1)));
		// Abort if transfer is too slow for too long
		curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
		curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, max(5, (int) floor($this->timeoutSeconds / 2)));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headersList);

		if ($method !== 'GET') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$responseBody = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
		$error = curl_error($ch);
		curl_close($ch);

		if ($responseBody === false) {
			$responseBody = json_encode(['error' => $error ?: 'HTTP request failed or timed out']);
		}

		return new SimpleHttpResponse($statusCode, (string) $responseBody, []);
	}

	private function requestWithStreams(string $method, string $url, array $headersList, string $body): SimpleHttpResponse
	{
		$context = stream_context_create([
			'http' => [
				'method' => $method,
				'header' => implode("\r\n", $headersList),
				'content' => $body,
				'timeout' => $this->timeoutSeconds,
				'ignore_errors' => true
			]
		]);

		$responseBody = @file_get_contents($url, false, $context);
		$statusLine = is_array($http_response_header ?? null) && count($http_response_header) > 0 ? $http_response_header[0] : '';
		$statusCode = 0;
		if (preg_match('/\s(\d{3})\s/', $statusLine, $m)) {
			$statusCode = (int) $m[1];
		}

		if ($responseBody === false) {
			$responseBody = json_encode(['error' => 'HTTP request failed']);
		}

		return new SimpleHttpResponse($statusCode, (string) $responseBody, []);
	}

	private function normalizeHeaders(array $headers): array
	{
		$normalized = [];
		foreach ($headers as $key => $value) {
			$normalized[strtolower($key)] = $value;
		}
		return $normalized;
	}

	private function buildHeadersList(array $headers): array
	{
		$list = [];
		foreach ($headers as $key => $value) {
			$list[] = $this->normalizeHeaderKey($key) . ': ' . $value;
		}
		return $list;
	}

	private function normalizeHeaderKey(string $key): string
	{
		return implode('-', array_map(fn($p) => ucfirst($p), explode('-', str_replace('_', '-', $key))));
	}
}

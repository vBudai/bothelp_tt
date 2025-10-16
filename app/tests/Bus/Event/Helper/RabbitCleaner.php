<?php

namespace App\Tests\Bus\Event\Helper;

use App\Tests\Dto\RabbitConnectionDto;

readonly class RabbitCleaner
{
    private RabbitConnectionDto $conn;
    public function __construct()
    {
        $this->conn = $this->parseRabbitDsnFromEnv();
    }

    public function deleteAllRabbitQueues(): void
    {
        $queues = $this->getAllQueues();
        foreach ($queues as $queue) {
            $apiMethod = "queues/%2F/$queue";

            $params = [
                'mode'  => 'delete',
                'name'  => $queue,
                'vhost' => $this->conn->path,
            ];

            $response = $this->makeCurlRequest(
                $apiMethod,
                'DELETE',
                $params
            );
            if($response['code'] !== 204){
                throw new \RuntimeException('Unable to delete queue');
            }
        }
    }

    /**
     * @return string[]
     */
    private function getAllQueues(): array
    {
        $apiMethod = "queues";
        $queues = $this->makeCurlRequest($apiMethod, 'GET');

        $names = [];
        foreach ($queues['body'] as $queue) {
            if (isset($queue['name'])) {
                $names[] = $queue['name'];
            }
        }

        return $names;
    }

    private function parseRabbitDsnFromEnv(): RabbitConnectionDto
    {
        $dsn = $_ENV['MESSENGER_TRANSPORT_DSN'];

        $parts = parse_url($dsn);
        return new RabbitConnectionDto(
            'http',
            $parts['host'],
            $parts['port'],
            $parts['user'],
            $parts['pass'],
            $parts['path']
        );
    }

    private function makeCurlRequest(string $apiMethod, string $httpMethod, array $params = []): array
    {
        $url = sprintf(
            '%s://%s:15672/api/%s',
            $this->conn->scheme,
            $this->conn->host,
            $apiMethod
        );

        $ch = curl_init($url);
        $auth = $this->conn->user . ':' . $this->conn->password;

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
        }

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException("Curl error: $err (URL: $url)");
        }

        return [
            'code' => $httpCode,
            'body' => json_decode($body, true),
        ];
    }
}

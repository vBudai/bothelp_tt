<?php

namespace App\Tests\Bus\Event\Helper;

use App\Tests\Dto\RabbitConnectionDto;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class RabbitCleaner
{
    private HttpClientInterface $httpClient;
    private RabbitConnectionDto $conn;

    public function __construct()
    {
        $this->conn = $this->parseRabbitDsnFromEnv();
        $this->httpClient = HttpClient::create();
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

            $response = $this->sendRequest(
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
        $queues = $this->sendRequest($apiMethod, 'GET');

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

    /**
     * @throws TransportExceptionInterface
     */
    private function sendRequest(string $apiMethod, string $httpMethod, array $params = []): array
    {
        $url = sprintf(
            '%s://%s:15672/api/%s',
            $this->conn->scheme,
            $this->conn->host,
            $apiMethod
        );

        $options = [
            'auth_basic' => [$this->conn->user, $this->conn->password]
        ];

        if ($params) {
            $options['json'] = $params;
        }

        $response = $this->httpClient->request($httpMethod, $url, $options);

        return [
            'code' => $response->getStatusCode(),
            'body' => json_decode($response->getContent(false), true),
        ];
    }
}

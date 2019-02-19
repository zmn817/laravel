<?php

namespace ThirtyThree\Signature;

use GuzzleHttp\Psr7;
use RuntimeException;
use App\Models\AppClient;
use Illuminate\Http\Request;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class Signature
{
    const ISO8601_BASIC = 'Ymd\THis\Z';
    const UNSIGNED_PAYLOAD = 'UNSIGNED-PAYLOAD';

    protected $unsigned;

    public function __construct(array $options = [])
    {
        $this->unsigned = isset($options['unsigned-body']) ? $options['unsigned-body'] : false;
    }

    public function validateRequest(Request $request)
    {
        $authorization = $request->header('Authorization');
        if (empty($authorization)) {
            abort(403, 'Authorization is empty');
        }
        if (! preg_match('/HMAC-SHA256 Credential=(.*), SignedHeaders=(.*), Signature=(.*)/', $authorization, $matches)) {
            abort(403, 'Authorization is not well-formed');
        }
        $accessKey = $matches[1];
        $client = AppClient::where('access_key', $accessKey)->first();
        if (empty($client)) {
            abort(403, 'AccessKey is not exists');
        }
        if (! empty($client->expire_at)) {
            if ($client->expire_at->isPast()) {
                abort(403, 'AccessKey expired');
            }
        }
        $secretKey = $client->secret_key;

        $headers = $matches[2];
        $signature = $matches[3];

        $ldt = $request->header('X-Tim-Date');
        if (abs(strtotime($ldt) - time()) > 7200) {
            abort(403, 'Request expired');
        }
        $sdt = substr($ldt, 0, 8);

        $guzzleRequest = new GuzzleRequest($request->getMethod(), $request->getUri(), $request->header(), $request->getContent());
        $parsed = $this->parseRequest($guzzleRequest);
        $parsed['headers']['X-Tim-Date'] = [$ldt];

        $payload = $this->getPayload($guzzleRequest);
        if ($payload == self::UNSIGNED_PAYLOAD) {
            $parsed['headers']['X-Tim-Content-Sha256'] = [$payload];
        }

        $context = $this->readContext($parsed, $headers, $payload);
        $toSign = $this->createStringToSign($ldt, $context['creq']);

        $signingKey = $this->getSigningKey(
            $sdt,
            $secretKey
        );
        $correctSignature = hash_hmac('sha256', $toSign, $signingKey);
        if ($signature !== $correctSignature) {
            return response()->json([
                'message' => 'Signature is invalid',
                'context' => $context['creq'],
                'status_code' => 403,
            ], 403);
        }

        return $client->id;
    }

    public function signRequest(RequestInterface $request, $accessKey, $secretKey)
    {
        $ldt = gmdate(self::ISO8601_BASIC);
        $sdt = substr($ldt, 0, 8);
        $parsed = $this->parseRequest($request);
        $parsed['headers']['X-Tim-Date'] = [$ldt];

        $payload = $this->getPayload($request);

        if ($payload == self::UNSIGNED_PAYLOAD) {
            $parsed['headers']['X-Tim-Content-Sha256'] = [$payload];
        }

        $context = $this->createContext($parsed, $payload);
        $toSign = $this->createStringToSign($ldt, $context['creq']);

        $signingKey = $this->getSigningKey(
            $sdt,
            $secretKey
        );
        $signature = hash_hmac('sha256', $toSign, $signingKey);
        $parsed['headers']['Authorization'] = [
            'HMAC-SHA256 '
            ."Credential={$accessKey}, "
            ."SignedHeaders={$context['headers']}, Signature={$signature}",
        ];

        return $this->buildRequest($parsed);
    }

    protected function getPayload(RequestInterface $request)
    {
        if ($this->unsigned && $request->getUri()->getScheme() == 'https') {
            return self::UNSIGNED_PAYLOAD;
        }
        // Calculate the request signature payload
        if ($request->hasHeader('X-Tim-Content-Sha256')) {
            // Handle streaming operations (e.g. Glacier.UploadArchive)
            return $request->getHeaderLine('X-Tim-Content-Sha256');
        }

        if (! $request->getBody()->isSeekable()) {
            throw new RuntimeException('Cannot make hash of sha256');
        }

        try {
            return Psr7\hash($request->getBody(), 'sha256');
        } catch (\Exception $e) {
            throw new RuntimeException('Cannot make hash of sha256');
        }
    }

    protected function createCanonicalizedPath($path)
    {
        $doubleEncoded = rawurlencode(ltrim($path, '/'));

        return '/'.str_replace('%2F', '/', $doubleEncoded);
    }

    protected function createStringToSign($longDate, $creq)
    {
        $hash = hash('sha256', $creq);

        return "HMAC-SHA256\n{$longDate}\n{$hash}";
    }

    protected function createContext(array $parsedRequest, $payload)
    {
        // The following headers are not signed because signing these headers
        // would potentially cause a signature mismatch when sending a request
        // through a proxy or if modified at the HTTP client level.
        static $blacklist = [
            'cache-control' => true,
            'content-type' => true,
            'content-length' => true,
            'expect' => true,
            'max-forwards' => true,
            'pragma' => true,
            'range' => true,
            'te' => true,
            'if-match' => true,
            'if-none-match' => true,
            'if-modified-since' => true,
            'if-unmodified-since' => true,
            'if-range' => true,
            'accept' => true,
            'authorization' => true,
            'proxy-authorization' => true,
            'from' => true,
            'referer' => true,
            'user-agent' => true,
        ];

        $canon = $parsedRequest['method']."\n"
            .$this->createCanonicalizedPath($parsedRequest['path'])."\n"
            .$this->getCanonicalizedQuery($parsedRequest['query'])."\n";

        $aggregate = [];
        foreach ($parsedRequest['headers'] as $key => $values) {
            $key = strtolower($key);
            if (! isset($blacklist[$key])) {
                foreach ($values as $v) {
                    $aggregate[$key][] = $v;
                }
            }
        }

        $canonHeaders = [];
        foreach ($aggregate as $k => $v) {
            if (count($v) > 0) {
                sort($v);
            }
            $canonHeaders[] = $k.':'.preg_replace('/\s+/', ' ', implode(',', $v));
        }

        $signedHeadersString = implode(';', array_keys($aggregate));
        $canon .= implode("\n", $canonHeaders)."\n\n"
            .$signedHeadersString."\n"
            .$payload;

        return ['creq' => $canon, 'headers' => $signedHeadersString];
    }

    protected function readContext(array $parsedRequest, $headers, $payload)
    {
        $canon = $parsedRequest['method']."\n"
            .$this->createCanonicalizedPath($parsedRequest['path'])."\n"
            .$this->getCanonicalizedQuery($parsedRequest['query'])."\n";

        $signedHeadersString = $headers;
        $aggregate = explode(';', $headers);

        $canonHeaders = [];
        foreach ($aggregate as $k) {
            foreach ($parsedRequest['headers'] as $key => $v) {
                $key = strtolower($key);
                if ($key == $k) {
                    if (count($v) > 0) {
                        sort($v);
                    }
                    $canonHeaders[] = $k.':'.preg_replace('/\s+/', ' ', implode(',', $v));
                }
            }
        }

        $canon .= implode("\n", $canonHeaders)."\n\n"
            .$signedHeadersString."\n"
            .$payload;

        return ['creq' => $canon, 'headers' => $signedHeadersString];
    }

    protected function getSigningKey($shortDate, $secretKey)
    {
        $dateKey = hash_hmac(
            'sha256',
            $shortDate,
            $secretKey,
            true
        );

        return hash_hmac(
            'sha256',
            'tim_request',
            $dateKey,
            true
        );
    }

    protected function getCanonicalizedQuery(array $query)
    {
        unset($query['X-Tim-Signature']);

        if (! $query) {
            return '';
        }

        $qs = '';
        ksort($query);
        foreach ($query as $k => $v) {
            if (! is_array($v)) {
                $qs .= rawurlencode($k).'='.rawurlencode($v).'&';
            } else {
                sort($v);
                foreach ($v as $value) {
                    $qs .= rawurlencode($k).'='.rawurlencode($value).'&';
                }
            }
        }

        return substr($qs, 0, -1);
    }

    protected function parseRequest(RequestInterface $request)
    {
        // Clean up any previously set headers.
        /** @var RequestInterface $request */
        $request = $request
            ->withoutHeader('X-Tim-Date')
            ->withoutHeader('Date')
            ->withoutHeader('Authorization');
        $uri = $request->getUri();

        return [
            'method' => $request->getMethod(),
            'path' => $uri->getPath(),
            'query' => Psr7\parse_query($uri->getQuery()),
            'uri' => $uri,
            'headers' => $request->getHeaders(),
            'body' => $request->getBody(),
            'version' => $request->getProtocolVersion(),
        ];
    }

    protected function buildRequest(array $req)
    {
        if ($req['query']) {
            $req['uri'] = $req['uri']->withQuery(Psr7\build_query($req['query']));
        }

        return new Psr7\Request(
            $req['method'],
            $req['uri'],
            $req['headers'],
            $req['body'],
            $req['version']
        );
    }
}

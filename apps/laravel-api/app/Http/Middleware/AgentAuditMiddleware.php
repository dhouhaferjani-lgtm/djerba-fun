<?php

namespace App\Http\Middleware;

use App\Models\AgentAuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentAuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = (int) (($endTime - $startTime) * 1000);

        $agent = $request->attributes->get('agent');

        if ($agent) {
            // Extract action from route
            $action = $this->extractAction($request);

            // Sanitize request data (remove sensitive information)
            $requestData = $this->sanitizeRequestData($request);

            // Create audit log asynchronously
            dispatch(function () use ($agent, $action, $requestData, $response, $request, $duration) {
                AgentAuditLog::create([
                    'agent_id' => $agent->id,
                    'action' => $action,
                    'request_data' => $requestData,
                    'response_status' => $response->getStatusCode(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'duration_ms' => $duration,
                ]);
            })->afterResponse();
        }

        return $response;
    }

    /**
     * Extract action name from request.
     */
    protected function extractAction(Request $request): string
    {
        $route = $request->route();

        if ($route && $route->getName()) {
            return $route->getName();
        }

        $method = $request->method();
        $path = $request->path();

        return "{$method} {$path}";
    }

    /**
     * Sanitize request data to remove sensitive information.
     */
    protected function sanitizeRequestData(Request $request): array
    {
        $data = [
            'method' => $request->method(),
            'path' => $request->path(),
            'query' => $request->query(),
            'body' => $request->except([
                'password',
                'api_key',
                'api_secret',
                'token',
                'credit_card',
                'cvv',
            ]),
        ];

        // Limit size of body data
        if (isset($data['body']) && is_array($data['body'])) {
            $json = json_encode($data['body']);
            if (strlen($json) > 10000) {
                $data['body'] = ['_truncated' => 'Body too large for logging'];
            }
        }

        return $data;
    }
}

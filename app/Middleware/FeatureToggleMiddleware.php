<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Services\FeatureToggleService;

/**
 * Subclass to enforce a feature_toggles row.
 * Example: class CommentsFeatureMiddleware extends FeatureToggleMiddleware {
 *     protected string $featureKey = 'comments';
 * }
 */
class FeatureToggleMiddleware implements Middleware
{
    protected string $featureKey = '';

    public function handle(Request $request, callable $next): void
    {
        if ($this->featureKey === '' || FeatureToggleService::enabled($this->featureKey)) {
            $next($request);
            return;
        }
        if ($request->isAjax() || $request->isJson()) {
            Response::json(['error' => 'feature_disabled', 'feature' => $this->featureKey], 404);
            return;
        }
        Response::notFound('Feature unavailable.');
    }
}

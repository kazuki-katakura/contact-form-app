<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        if ($e instanceof ModelNotFoundException && $request->is('api/*')) {
            return response()->json([
                'error' => 'お問い合わせが見つかりませんでした。',
            ], 404);
        }

        return parent::render($request, $e);
    }

    /**
     * API へのリクエストは Accept ヘッダーに関わらず JSON でエラーを返す。
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }
}

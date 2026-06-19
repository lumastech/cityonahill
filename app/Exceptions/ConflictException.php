<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConflictException extends Exception
{
    public function __construct(string $message = 'A conflict was detected.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return back()->withErrors(['conflict' => $this->getMessage()])->getTargetUrl()
            ? back()->withErrors(['conflict' => $this->getMessage()])
            : response($this->getMessage(), 409);
    }
}

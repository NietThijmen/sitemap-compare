<?php

namespace App\Service;

class RetryService
{
    /**
     * Retry a callable function a specified number of times with optional sleep and error handling.
     *
     * @param  int  $times  how many times to retry
     * @param  callable  $callback  the function to execute
     * @param  int  $sleep  milliseconds to wait between retries
     * @param  callable|null  $onError  function to call on each error
     * @param  callable|null  $onFail  function to call if all retries fail
     * @return mixed the result of the callback
     *
     * @throws \Exception if all retries fail and no onFail is provided
     */
    public static function Retry(
        int $times,
        callable $callback,
        int $sleep = 0,
        ?callable $onError = null,
        ?callable $onFail = null
    ): mixed {
        $attempts = 0;
        beginning:
        $attempts++;
        try {
            return $callback($attempts);
        } catch (\Exception $e) {
            if ($attempts >= $times) {
                if ($onFail) {
                    $onFail($e, $attempts);

                    return null;
                }

                throw $e;
            }
            if ($onError) {
                $onError($e, $attempts);
            }
            if ($sleep > 0) {
                usleep($sleep * 1000); // Convert milliseconds to microseconds
            }
            goto beginning;
        }

        return null; // Yes, I know this is unreachable, but phpstan complains without it
    }
}

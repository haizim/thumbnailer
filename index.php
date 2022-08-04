<?php
include 'vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dsn = $_ENV['DSN'] ?? 'https://e5c3126aacd44fa2b87b2a363b2bc27f@o1243391.ingest.sentry.io/6398682';
\Sentry\init([
    'dsn' => $dsn,
    'traces_sample_rate' => 0.5
 ]);

\Sentry\configureScope(function (\Sentry\State\Scope $scope) : void {
    $scope->setUser([
        'ip' => $_SERVER['REMOTE_ADDR'],
    ]);
});

try {
    $url = str_replace(" ", "%20", $_GET['u']);
    $width = $_GET['w'] ?? 500;
    $quality = $_GET['q'] ?? 5;

    $transName = str_replace('http://', '', $url);
    $transName = str_replace('https://', '', $transName);

    $transactionContext = new \Sentry\Tracing\TransactionContext();
    $transactionContext->setName($transName);
    $transactionContext->setOp('http.request');

    // Start the transaction
    $transaction = \Sentry\startTransaction($transactionContext);

    // Set the current transaction as the current span so we can retrieve it later
    \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

    // Setup the context for the test operation span
    $spanContext = new \Sentry\Tracing\SpanContext();
    $spanContext->setOp('all_operation');

    // Start the span
    $span1 = $transaction->startChild($spanContext);

    // Set the current span to the span we just started
    \Sentry\SentrySdk::getCurrentHub()->setSpan($span1);
    
    if ($quality > 100) {
        errorImage('max. quality must 0-100', 30);
        die();
    }
    
    $data = file_get_contents($url);
    
    $im = imagecreatefromstring($data);
    if ($im !== false) {
        // header('Content-Type: image/png');
        header('Content-Type: image/jpeg');
        // imagepng($im, null, $quality, PNG_NO_FILTER);
        // imagejpeg($im, null, $quality);
        $im = imagescale($im, $width);
        imagejpeg($im);
        imagedestroy($im);
    }
    else {
        errorImage();
    }

    // Finish the span
    $span1->finish();

    // Set the current span back to the transaction since we just finished the previous span
    \Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

    // Finish the transaction, this submits the transaction and it's span to Sentry
    $transaction->finish();
} catch(\Throwable $e) {
    \Sentry\captureException($e);
    errorImage();
}

function errorImage($text = "failed to get image", $x = 50, $y = 125) {
    // https://www.php.net/manual/en/function.imagejpeg.php
    // Create a blank image and add some text
    $im = imagecreatetruecolor(258, 258);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 5, $x, $y, $text, $text_color);
    imagestring($im, 2, 85, 152, 'by: haizim.one', $text_color);

    // Set the content type header - in this case image/jpeg
    header('Content-Type: image/jpeg');

    // Output the image
    imagejpeg($im);

    // Free up memory
    imagedestroy($im);
}
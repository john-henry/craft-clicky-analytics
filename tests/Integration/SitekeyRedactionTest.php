<?php

/**
 * The sitekey must not end up in a log line or on a screen.
 *
 * Guzzle puts the whole request URI into the message of any HTTP status error,
 * and the sitekey rides on every Clicky call as a query parameter, so the raw
 * message is the credential in plain text. _request() logs that message and
 * wraps it in the exception it throws; ping() hands it back to the settings
 * screen as JSON.
 */

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use johnhenry\clickyanalytics\ClickyAnalytics;

describe('Clicky API error messages', function() {
    it('keeps the sitekey out of the exception a failed request throws', function() {
        $api = clickyApiWithMock([new Response(401, [], 'Unauthorized')]);

        try {
            $api->getStats(['visitors'], 'today');
            $this->fail('Expected the request to throw.');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->not->toContain('test-sitekey')
                ->and($e->getMessage())->toContain('sitekey=***')
                ->and($e->getMessage())->toContain('401');
        }
    });

    it('keeps the sitekey out of a 500 as well', function() {
        $api = clickyApiWithMock([new Response(500, [], 'boom')]);

        try {
            $api->getStats(['visitors'], 'today');
            $this->fail('Expected the request to throw.');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->not->toContain('test-sitekey');
        }
    });

    it('keeps the sitekey out of what the connection test shows the admin', function() {
        clickyApiWithMock([new ConnectException(
            'cURL error 7: Failed to connect (see https://api.clicky.com/api/stats/4?sitekey=test-sitekey)',
            new Request('GET', 'https://api.clicky.com/api/stats/4'),
        )]);

        $result = ClickyAnalytics::getInstance()->getApi()->ping('12345', 'test-sitekey');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->not->toContain('test-sitekey')
            ->and($result['message'])->toContain('sitekey=***');
    });

    it('leaves a message with no sitekey in it alone', function() {
        $api = clickyApiWithMock([new ConnectException(
            'cURL error 28: Operation timed out',
            new Request('GET', 'https://api.clicky.com/api/stats/4'),
        )]);

        try {
            $api->getStats(['visitors'], 'today');
            $this->fail('Expected the request to throw.');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toContain('cURL error 28')
                ->and($e->getMessage())->not->toContain('***');
        }
    });
});

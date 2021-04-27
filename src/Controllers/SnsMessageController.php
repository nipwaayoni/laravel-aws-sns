<?php

namespace Nipwaayoni\SnsHandler\Controllers;

use Illuminate\Routing\Controller;
use Nipwaayoni\SnsHandler\SnsBroker;
use Nipwaayoni\SnsHandler\SnsConfirmSubscriptionException;
use Nipwaayoni\SnsHandler\SnsException;
use Nipwaayoni\SnsHandler\SnsHttpRequest;
use Nipwaayoni\SnsHandler\SnsUnknownTopicArnException;

/**
 * Class SnsMessageController
 * @package Nipwaayoni\SnsHandler\Controllers
 *
 * @codeCoverageIgnore
 */
class SnsMessageController extends Controller
{
    /**
     * @var SnsBroker
     */
    private $snsBroker;

    public function __construct(SnsBroker $snsBroker)
    {
        $this->snsBroker = $snsBroker;
    }

    public function handle(SnsHttpRequest $request)
    {
        try {
            $this->snsBroker->handleRequest($request);
        } catch (SnsUnknownTopicArnException $e) {
            return response(null, 404);
        } catch (SnsConfirmSubscriptionException $e) {
            return response(null, 502);
        } catch (SnsException $e) {
            return response(null, 500);
        }

        return response()->json();
    }
}

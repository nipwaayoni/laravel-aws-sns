<?php

namespace MiamiOH\SnsHandler\Controllers;

use Illuminate\Routing\Controller;
use MiamiOH\SnsHandler\SnsBroker;
use MiamiOH\SnsHandler\SnsConfirmSubscriptionException;
use MiamiOH\SnsHandler\SnsException;
use MiamiOH\SnsHandler\SnsHttpRequest;
use MiamiOH\SnsHandler\SnsUnknownTopicArnException;

/**
 * Class SnsMessageController
 * @package MiamiOH\SnsHandler\Controllers
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

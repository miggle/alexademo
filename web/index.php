<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Alexa\Request\Certificate;
use Alexa\Request\Request as AlexaRequest;

$app = new Silex\Application();

// Monolog - uncomment to use the Monolog service.
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => PATH/TO/LOG/FILE,
));

/**
 * Simple hello miggle example Alexa skill.
 */
$app->post('/alexa', function(Request $request) use ($app) {
  $app_id = 'amzn1.ask.skill.1785ffaf-8e86-41bb-a9cb-50cdd2b3f261';

  $content = $request->getContent();
  if (!empty($content)) {
    try {
      // Create certificate based on request headers.
      $certificate = new Certificate(
        $request->headers->get('signaturecertchainurl'),
        $request->headers->get('signature')
      );

      // Create new
      $alexa = new AlexaRequest($content, $app_id);
      $alexa->setCertificateDependency($certificate);
      $alexa = $alexa->fromData();

      // Debug the request data if you like.
       $app['monolog']->debug($alexa->rawData);


      // Prep the response.
      $response = new \Alexa\Response\Response();

      // Respond to starting the skill.
      if ($alexa instanceof \Alexa\Request\LaunchRequest) {
        $response->respond('Hi, I am miggle example skill. What can I do for you?');
        return $app->json($response->render(), 200);
      }

      if ($alexa instanceof \Alexa\Request\IntentRequest) {
        // Check the intent.
        switch ($alexa->intentName) {
          case 'MiggleIntent':
            // Say hello.
            $response->respond('Hello miggle, this is an example skill');
            $response->endSession();
            break;
          case 'DayToday':
            // Get the date from the request.
            $date_string = $alexa->getSlot('Date');

            try {
              $date = new DateTime($date_string);
              $day = $date->format('l');
              $response->respond(sprintf('The day on that date is %s', $day));
              $response->endSession();
            }
            catch (\Exception $e) {
              $response->reprompt('Sorry, I didn\'t understand that date. Please try again');
            }
            break;
          default:
            throw new InvalidArgumentException();
        }
      }


      return new JsonResponse($response->render());
    }
    catch (\InvalidArgumentException $e) {
      //$app['monolog']->crit(sprintf('Fail %s', $e->getMessage()));
      return new JsonResponse(array('fail', $e->getMessage()), 500);
    }
    catch (\Exception $e) {
      // Fallback.
    }
  }

  return new JsonResponse(NULL, 500);
});

$app->run();

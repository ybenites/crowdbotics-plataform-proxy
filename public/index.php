<?php

use GuzzleHttp\Client;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\Exception\HttpNotFoundException;


require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->setBasePath("/proxy-crowdbotics-api");

// $app->addErrorMiddleware(false, true, true);
$app->get('/name', function (Request $request, Response $response, $args) {
    $response->getBody()->write("welcome");
    return $response;
});

$routeCollector = $app->getRouteCollector();
$routeCollector->setCacheFile(__DIR__.'/../CachePages/cache.file');

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:8080')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('access-control-expose-headers', 'Set-Cookie');
});

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->group('/api/v1', function ($app) {
    $app->post('/login/', function (Request $request, Response $response, array $args) {
        $request_body = $request->getParsedBody();

        $client = new Client([
            'cookies' => true,
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);
    
        $dataResponse = $client->request('POST', 'https://hiring-example-25770.botics.co/rest-auth/login/',['body' => json_encode(
            [
                'email' => $request_body['email'],
                'password' => $request_body['password']
            ]
        )]);

        $setCookie = $dataResponse->getHeader('Set-Cookie');
                
        $response->getBody()->write($dataResponse->getBody()->getContents());
        $status = $dataResponse->getStatusCode();
    
        return $response
        ->withStatus($status)
        ->withHeader('Set-Cookie', $setCookie);
    });

    $app->get('/apps/', function (Request $request, Response $response, array $args) {
        $request_headers = $request->getHeaders();        
        $request_options = [
            'headers' => ['Cookie' => $request_headers['Cookie'][0]]
        ];

        $client = new Client([
            'cookies' => true,
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);
    
        $dataResponse = $client->request('GET', 'https://hiring-example-25770.botics.co/api/v1/apps/', 
                                            $request_options);        
                
        $response->getBody()->write($dataResponse->getBody()->getContents());
        $status = $dataResponse->getStatusCode();
    
        return $response
        ->withStatus($status);
    });

    $app->post('/apps/', function (Request $request, Response $response, array $args) {
        $request_headers = $request->getHeaders();

        $CSRFToken = substr(strstr($request_headers['Cookie'][0],';', true), 10);
        $request_options = [
            'headers' => [
                'Cookie' => $request_headers['Cookie'][0],
                'X-CSRFToken' => $CSRFToken
            ]
        ];

        $client = new Client([
            'cookies' => true,
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);
    
        $dataResponse = $client->request('POST', 'http://hiring-example-25770.botics.co/api/v1/apps/',
                                    $request_options);

        $response->getBody()->write($dataResponse->getBody()->getContents());
        $status = $dataResponse->getStatusCode();
    
        return $response
        ->withStatus($status);
    });

    $app->map(["DELETE","OPTIONS"],"/apps/:id/", function(Request $request, Response $response, array $args) {
        $request_headers = $request->getHeaders();
        $request_body = $request->getParsedBody();
        $id = $args["id"];

        $CSRFToken = substr(strstr($request_headers['Cookie'][0],';', true), 10);
        $request_options = [
            'body' => json_encode($request_body),
            'headers' => [
                'Cookie' => $request_headers['Cookie'][0],
                'X-CSRFToken' => $CSRFToken
            ]
        ];

        $client = new Client([
            'cookies' => true,
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);
    
        // $dataResponse = $client->request('DELETE', 'http://hiring-example-25770.botics.co/api/v1/apps/'+$id+'/',
        //                             $request_options);

        print_r($dataResponse);
        print_r("asas");
        $response->getBody()->write($dataResponse->getBody()->getContents());
        $status = $dataResponse->getStatusCode();
    
        return $response
        ->withStatus($status);
    });
});


$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

$app->run(); 
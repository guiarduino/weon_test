<?php

$router->post('/webhook/whatsapp/{number}', 'WebhookController@whatsapp');

$router->get('/messages', 'MessageController@find');
$router->get('/messages/{id}', 'MessageController@show');
$router->get('/messages/provider/{provider_id}', 'MessageController@findByProviderId');
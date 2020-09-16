<?php
declare(strict_types=1);

/** @var \Laravel\Lumen\Routing\Router $router */

// MailChimp group
$router->group(['prefix' => 'mailchimp', 'namespace' => 'MailChimp'], function () use ($router) {
    // Lists group
    $router->group(['prefix' => 'lists'], function () use ($router) {

        // Mailchimp > List endpoints
        $router->get('/', 'ListsController@all');
        $router->get('/{listId}', 'ListsController@show');
        $router->post('/', 'ListsController@create');
        $router->patch('/{listId}', 'ListsController@update');
        $router->delete('/{listId}', 'ListsController@remove');

        // Mailchimp > Member endpoints
        $router->get('/{list_id}/members', 'MembersController@all');
        $router->get('/{list_id}/members/{member_id}', 'MembersController@show');
        $router->post('/{list_id}/members', 'MembersController@create');
        $router->patch('/{list_id}/members/{member_id}', 'MembersController@update');
        $router->delete('/{list_id}/members/{member_id}', 'MembersController@remove');
    });
});
